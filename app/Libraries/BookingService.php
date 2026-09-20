<?php

namespace App\Libraries;

use App\Models\DiscountCodeModel;
use App\Models\EventModel;
use App\Models\PayPalCaptureModel;
use App\Models\PaymentModel;
use App\Models\TicketModel;
use Throwable;

/**
 * Booking rules that must run atomically: capacity, per-user limits, discount usage,
 * idempotent PayPal fulfilment, cancellations and refunds.
 */
class BookingService
{
    public const DEFAULT_MAX_SEATS_PER_USER = 10;

    private TicketModel $tickets;
    private PaymentModel $payments;
    private PayPalCaptureModel $captures;
    private EventModel $events;
    private DiscountCodeModel $discounts;
    private PayPalClient $paypal;

    public function __construct()
    {
        $this->tickets   = new TicketModel();
        $this->payments  = new PaymentModel();
        $this->captures  = new PayPalCaptureModel();
        $this->events    = new EventModel();
        $this->discounts = new DiscountCodeModel();
        $this->paypal    = new PayPalClient();
    }

    public static function maxSeatsPerUser(): int
    {
        return max(1, (int) (env('booking.maxSeatsPerUser') ?: self::DEFAULT_MAX_SEATS_PER_USER));
    }

    /**
     * Splits an amount across seats without losing cents.
     *
     * @return list<string>
     */
    public static function splitAmount(float $totalAmount, int $seats): array
    {
        $seats      = max(1, $seats);
        $totalCents = (int) round($totalAmount * 100);
        $baseCents  = intdiv($totalCents, $seats);
        $remainder  = $totalCents % $seats;
        $amounts    = [];

        for ($i = 0; $i < $seats; $i++) {
            $amounts[] = number_format(($baseCents + ($i < $remainder ? 1 : 0)) / 100, 2, '.', '');
        }

        return $amounts;
    }

    public static function amountsMatch(float $expected, float $actual): bool
    {
        return abs(round($expected, 2) - round($actual, 2)) < 0.00001;
    }

    public function remainingSeats(array $event): int
    {
        $capacity = (int) ($event['capacity'] ?? 0);
        $booked   = $this->tickets
            ->where('event_id', (int) $event['id'])
            ->where('status', 'valid')
            ->countAllResults();

        return max($capacity - $booked, 0);
    }

    /**
     * How many more seats this user may still book for the event.
     */
    public function userAllowance(int $eventId, int $userId): int
    {
        $held = $this->tickets
            ->where('event_id', $eventId)
            ->where('user_id', $userId)
            ->where('status', 'valid')
            ->countAllResults();

        return max(self::maxSeatsPerUser() - $held, 0);
    }

    /**
     * Resolves the donation total, optionally applying a discount code.
     *
     * @return array{0: float, 1: array|null, 2: string|null} [total, discountRow, errorKey]
     */
    public function resolveTotal(int $eventId, int $seats, float $perSeat, string $code, bool $strict = true): array
    {
        $total    = round($seats * $perSeat, 2);
        $code     = strtoupper(trim($code));
        $discount = null;

        if ($code !== '') {
            $discount = $strict
                ? $this->discounts->findValid($code, $eventId)
                : $this->discounts->findByCode($code, $eventId);

            if ($discount === null) {
                return [$total, null, 'App.discountCodesNotFound'];
            }

            $total = $this->discounts->applyDiscount($discount, $total);

            if ($total < 0.01) {
                return [$total, $discount, 'App.discountTotalTooLow'];
            }
        }

        return [$total, $discount, null];
    }

    /**
     * @return array{status: string, codes: list<string>}
     */
    public function bookFree(array $event, int $userId, int $seats): array
    {
        return $this->createTickets($event, $userId, $seats, null);
    }

    /**
     * @param array{captureId: string, total: float, currency: string, discountId: int|null} $payment
     *
     * @return array{status: string, codes: list<string>}
     */
    public function bookPaid(array $event, int $userId, int $seats, array $payment): array
    {
        return $this->createTickets($event, $userId, $seats, $payment);
    }

    /**
     * Validates an already-captured PayPal payment and turns it into tickets.
     * Used by both the browser capture flow and the webhook.
     *
     * @param array{id?: string, amount?: array} $capture PayPal capture object
     * @param array{event_id: int, user_id: int, seats: int, donation: float, code?: string} $booking
     *
     * @return array{status: string, codes: list<string>, event: array|null, seats: int, amount: float, currency: string, user_id: int}
     */
    public function fulfillCapture(array $capture, array $booking, ?int $expectedUserId = null, ?int $expectedEventId = null): array
    {
        $captureId = (string) ($capture['id'] ?? '');
        $amount    = (float) ($capture['amount']['value'] ?? 0);
        $currency  = (string) ($capture['amount']['currency_code'] ?? 'EUR');
        $seats     = (int) ($booking['seats'] ?? 0);
        $userId    = (int) ($booking['user_id'] ?? 0);
        $event     = $this->events->find((int) ($booking['event_id'] ?? 0));

        $result = [
            'status'   => 'invalid',
            'codes'    => [],
            'event'    => $event ?: null,
            'seats'    => $seats,
            'amount'   => $amount,
            'currency' => $currency,
            'user_id'  => $userId,
        ];

        if (
            $captureId === ''
            || empty($event)
            || $seats < 1
            || $userId < 1
            || (float) ($booking['donation'] ?? 0) <= 0
            || $amount <= 0
            || ($expectedUserId !== null && $expectedUserId !== $userId)
            || ($expectedEventId !== null && $expectedEventId !== (int) $event['id'])
        ) {
            log_message('error', 'PayPal fulfilment rejected (invalid booking data). captureId={captureId} booking={booking}', [
                'captureId' => $captureId,
                'booking'   => json_encode($booking),
            ]);

            return $result;
        }

        [$expectedTotal, $discount, $discountError] = $this->resolveTotal(
            (int) $event['id'],
            $seats,
            (float) $booking['donation'],
            (string) ($booking['code'] ?? ''),
            false
        );

        if ($discountError !== null || ! self::amountsMatch($expectedTotal, $amount)) {
            log_message('error', 'PayPal capture amount mismatch. expected={expected} actual={actual} captureId={captureId}', [
                'expected'  => $expectedTotal,
                'actual'    => $amount,
                'captureId' => $captureId,
            ]);

            return $result;
        }

        $booked = $this->bookPaid($event, $userId, $seats, [
            'captureId'  => $captureId,
            'total'      => $amount,
            'currency'   => $currency,
            'discountId' => $discount !== null ? (int) $discount['id'] : null,
        ]);

        $result['status'] = $booked['status'];
        $result['codes']  = $booked['codes'];

        if (in_array($booked['status'], ['full', 'limit', 'discount'], true)) {
            [$refunded] = $this->paypal->refundCapture($captureId, null, $currency, 'Booking could not be completed');
            $result['status'] = $refunded ? 'refunded' : 'refund_failed';

            log_message('error', 'PayPal capture {captureId} could not be fulfilled ({reason}); auto refund {refund}.', [
                'captureId' => $captureId,
                'reason'    => $booked['status'],
                'refund'    => $refunded ? 'succeeded' : 'FAILED - refund manually',
            ]);
        }

        return $result;
    }

    /**
     * Marks tickets of a capture as refunded/cancelled after PayPal reported a refund (webhook).
     * Refunds we issued ourselves are ignored; partial external refunds are only logged.
     *
     * @return list<int> ids of event(s) that gained free seats
     */
    public function applyExternalRefund(string $captureId, string $refundId, float $refundAmount): array
    {
        $rows = $this->payments->where('paypal_transaction_id', $captureId)->findAll();
        $eventIds = [];
        $openTotal = 0.0;

        foreach ($rows as $payment) {
            if ($refundId !== '' && (string) ($payment['paypal_refund_id'] ?? '') === $refundId) {
                return [];
            }

            if ((string) $payment['payment_status'] !== 'refunded') {
                $openTotal += (float) $payment['amount'];
            }
        }

        if ($openTotal <= 0) {
            return [];
        }

        if ($refundAmount + 0.005 < $openTotal) {
            log_message('warning', 'Partial external refund of {amount} on capture {captureId} needs manual review.', [
                'amount'    => $refundAmount,
                'captureId' => $captureId,
            ]);

            return [];
        }

        foreach ($rows as $payment) {
            if ((string) $payment['payment_status'] === 'refunded') {
                continue;
            }

            $this->payments->update((int) $payment['id'], [
                'payment_status' => 'refunded',
                'refunded_at'    => date('Y-m-d H:i:s'),
            ]);

            $ticket = $this->tickets->find((int) $payment['ticket_id']);
            if (! empty($ticket)) {
                $this->tickets->update((int) $ticket['id'], [
                    'status'         => 'cancelled',
                    'cancelled_at'   => date('Y-m-d H:i:s'),
                    'payment_status' => 'refunded',
                ]);
                $eventIds[(int) $ticket['event_id']] = (int) $ticket['event_id'];
            }
        }

        return array_values($eventIds);
    }

    /**
     * @param array{captureId: string, total: float, currency: string, discountId: int|null}|null $payment
     *
     * @return array{status: string, codes: list<string>}
     */
    private function createTickets(array $event, int $userId, int $seats, ?array $payment): array
    {
        $db      = db_connect();
        $eventId = (int) $event['id'];
        $codes   = [];

        $db->transBegin();

        try {
            // Serialise every booking of this event so capacity can never be oversold.
            if (in_array($db->DBDriver, ['MySQLi', 'Postgre'], true)) {
                $db->query('SELECT id FROM ' . $db->prefixTable('events') . ' WHERE id = ? FOR UPDATE', [$eventId]);
            }

            if ($payment !== null) {
                $exists = $this->captures->where('paypal_transaction_id', $payment['captureId'])->first() !== null
                    || $this->payments->where('paypal_transaction_id', $payment['captureId'])->first() !== null;

                if ($exists) {
                    $db->transRollback();

                    return ['status' => 'duplicate', 'codes' => []];
                }
            }

            if ($seats > $this->remainingSeats($event)) {
                $db->transRollback();

                return ['status' => 'full', 'codes' => []];
            }

            if ($seats > $this->userAllowance($eventId, $userId)) {
                $db->transRollback();

                return ['status' => 'limit', 'codes' => []];
            }

            $amounts = [];
            if ($payment !== null) {
                if ($payment['discountId'] !== null && ! $this->discounts->reserveUse((int) $payment['discountId'])) {
                    $db->transRollback();

                    return ['status' => 'discount', 'codes' => []];
                }

                if ($this->captures->insert(['paypal_transaction_id' => $payment['captureId']]) === false) {
                    throw new \RuntimeException('Could not record capture.');
                }

                $amounts = self::splitAmount($payment['total'], $seats);
            }

            for ($i = 0; $i < $seats; $i++) {
                $code    = $this->generateTicketCode();
                $codes[] = $code;

                $ticketId = $this->tickets->insert([
                    'event_id'        => $eventId,
                    'user_id'         => $userId,
                    'ticket_code'     => $code,
                    'donation_amount' => $payment !== null ? $amounts[$i] : 0.00,
                    'payment_status'  => $payment !== null ? 'paid' : 'free',
                    'status'          => 'valid',
                ], true);

                if ($ticketId === false) {
                    throw new \RuntimeException('Could not insert ticket.');
                }

                if ($payment !== null) {
                    $paymentId = $this->payments->insert([
                        'ticket_id'             => (int) $ticketId,
                        'paypal_transaction_id' => $payment['captureId'],
                        'amount'                => $amounts[$i],
                        'currency'              => $payment['currency'],
                        'payment_status'        => 'completed',
                    ]);

                    if ($paymentId === false) {
                        throw new \RuntimeException('Could not insert payment.');
                    }
                }
            }

            $db->table($db->prefixTable('waitlist'))->where('event_id', $eventId)->where('user_id', $userId)->delete();

            $db->transCommit();
        } catch (Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Booking transaction failed for event {event}: {message}', [
                'event'   => $eventId,
                'message' => $exception->getMessage(),
            ]);

            return ['status' => 'error', 'codes' => []];
        }

        return ['status' => 'created', 'codes' => $codes];
    }

    private function generateTicketCode(): string
    {
        do {
            $code = strtoupper(bin2hex(random_bytes(6)));
        } while ($this->tickets->where('ticket_code', $code)->first() !== null);

        return $code;
    }
}
