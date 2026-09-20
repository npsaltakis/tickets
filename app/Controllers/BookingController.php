<?php

namespace App\Controllers;

use App\Libraries\BookingService;
use App\Models\WaitlistModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class BookingController extends EventBaseController
{
    public function book(string $slug): RedirectResponse
    {
        if (! $this->passesBookingThrottle('free_book')) {
            return redirect()->back()->with('event_error', lang('App.bookingRateLimited'));
        }

        $event = $this->eventModel->where('slug', $slug)->first();

        if (empty($event)) {
            throw PageNotFoundException::forPageNotFound('Event not found');
        }

        if (session()->get('is_logged_in') !== true) {
            return redirect()->to(base_url('login'))->with('login_info', lang('App.bookingLoginRequired'));
        }

        if ((string) ($event['status'] ?? '') !== 'active') {
            return redirect()->back()->with('event_error', lang('App.bookingEventUnavailable'));
        }

        if (! empty($event['end_date']) && strtotime((string) $event['end_date']) !== false && strtotime((string) $event['end_date']) < time()) {
            return redirect()->back()->with('event_error', lang('App.bookingClosedMessage'));
        }

        if ((int) ($event['bookings_enabled'] ?? 1) !== 1) {
            return redirect()->back()->with('event_error', lang('App.bookingClosedMessage'));
        }

        if (! $this->hasPrivateAccess($event)) {
            return redirect()->to(base_url('events/' . $slug));
        }

        if (! $this->hasAcceptedBookingTerms()) {
            return redirect()->back()->withInput()->with('event_error', lang('App.eventBookingConsentError'));
        }

        $requestedSeats = (int) $this->request->getPost('seats');
        if ($requestedSeats < 1) {
            return redirect()->back()->with('event_error', lang('App.bookingInvalidSeatCount'));
        }

        if (($event['event_type'] ?? 'free') !== 'free') {
            return redirect()->back()->with('event_error', lang('App.donationBookingPending'));
        }

        $userId  = (int) session()->get('user_id');
        $booking = (new BookingService())->bookFree($event, $userId, $requestedSeats);

        if ($booking['status'] === 'created') {
            $this->recordConsent((int) $event['id']);
        }

        if ($booking['status'] !== 'created') {
            return redirect()->back()->with('event_error', $this->bookingFailureMessage($event, $booking['status'], $userId));
        }

        $ticketCodes = $booking['codes'];
        $bookingMessage = lang('App.bookingSuccess');

        if (! $this->sendBookingConfirmationEmail($event, $requestedSeats, $ticketCodes, 0.00, 'EUR')) {
            $bookingMessage .= ' ' . lang('App.bookingEmailFailed');
        }

        $this->notifyCapacityAfterBooking($event);

        return redirect()->to(base_url('events/' . $slug . '/success'))
            ->with('booking_success_codes', $ticketCodes)
            ->with('booking_success_message', $bookingMessage);
    }

    public function createDonationOrder(string $slug)
    {
        if (! $this->passesBookingThrottle('paypal_order')) {
            return $this->response->setStatusCode(429)->setJSON(['message' => lang('App.bookingRateLimited')]);
        }

        $event = $this->eventModel->where('slug', $slug)->first();

        if (empty($event)) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Event not found']);
        }

        if (session()->get('is_logged_in') !== true) {
            return $this->response->setStatusCode(401)->setJSON(['message' => lang('App.bookingLoginRequired')]);
        }

        if (! $this->hasPrivateAccess($event)) {
            return $this->response->setStatusCode(403)->setJSON(['message' => lang('App.privateEventTitle')]);
        }

        if (! $this->hasAcceptedBookingTerms()) {
            return $this->response->setStatusCode(422)->setJSON(['message' => lang('App.eventBookingConsentError')]);
        }

        $this->recordConsent((int) $event['id']);

        [$requestedSeats, $donationAmountPerSeat, $error] = $this->validateDonationBookingRequest($event);
        if ($error !== null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $error]);
        }

        $discountCode = strtoupper($this->getRequestValue('discount_code'));
        [$totalDonationAmount, , $discountError] = (new BookingService())->resolveTotal(
            (int) $event['id'],
            $requestedSeats,
            $donationAmountPerSeat,
            $discountCode,
            true,
            (int) session()->get('user_id')
        );

        if ($discountError !== null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => lang($discountError)]);
        }

        [$accessToken, $tokenError] = $this->getPayPalAccessToken();
        if ($accessToken === null) {
            return $this->response->setStatusCode(500)->setJSON(['message' => lang('App.paypalConfigurationError')]);
        }

        $customId = implode('|', [
            'event:' . (int) $event['id'],
            'user:' . (int) session()->get('user_id'),
            'seats:' . $requestedSeats,
            'donation:' . number_format($donationAmountPerSeat, 2, '.', ''),
        ]);

        if ($discountCode !== '') {
            $customId .= '|code:' . $discountCode;
        }

        try {
            $paypalResponse = service('curlrequest')->post(rtrim($this->getPayPalBaseUrl(), '/') . '/v2/checkout/orders', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'description' => 'Donation booking for ' . (string) ($event['title'] ?? 'Event'),
                        'custom_id' => $customId,
                        'amount' => [
                            'currency_code' => 'EUR',
                            'value' => number_format($totalDonationAmount, 2, '.', ''),
                        ],
                    ]],
                    'application_context' => [
                        'shipping_preference' => 'NO_SHIPPING',
                        'user_action' => 'PAY_NOW',
                    ],
                ],
                'http_errors' => false,
                'verify' => $this->shouldVerifySsl(),
                'timeout' => 20,
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'PayPal order request failed: {message}', ['message' => $exception->getMessage()]);

            return $this->response->setStatusCode(500)->setJSON(['message' => lang('App.paypalOrderCreateFailed')]);
        }

        $orderData = json_decode($paypalResponse->getBody(), true);
        $orderId = is_array($orderData) ? (string) ($orderData['id'] ?? '') : '';

        if ($orderId === '') {
            log_message('error', 'PayPal order creation failed [{status}]: {body}', [
                'status' => $paypalResponse->getStatusCode(),
                'body' => $paypalResponse->getBody(),
            ]);

            return $this->response->setStatusCode(500)->setJSON(['message' => lang('App.paypalOrderCreateFailed')]);
        }

        return $this->response->setJSON(['id' => $orderId]);
    }

    public function captureDonationOrder(string $slug)
    {
        if (! $this->passesBookingThrottle('paypal_capture')) {
            return $this->response->setStatusCode(429)->setJSON(['message' => lang('App.bookingRateLimited')]);
        }

        $event = $this->eventModel->where('slug', $slug)->first();

        if (empty($event)) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Event not found']);
        }

        if (session()->get('is_logged_in') !== true) {
            return $this->response->setStatusCode(401)->setJSON(['message' => lang('App.bookingLoginRequired')]);
        }

        $orderId = trim($this->getRequestValue('order_id'));
        if ($orderId === '') {
            log_message('error', 'PayPal capture missing order_id. post={post} raw={raw} method={method} contentType={contentType}', [
                'post' => json_encode($this->request->getPost()),
                'raw' => json_encode($this->request->getRawInput()),
                'method' => $this->request->getMethod(),
                'contentType' => $this->request->getHeaderLine('Content-Type'),
            ]);

            return $this->response->setStatusCode(422)->setJSON(['message' => lang('App.paypalCaptureFailed')]);
        }

        [$accessToken, $tokenError] = $this->getPayPalAccessToken();
        if ($accessToken === null) {
            return $this->response->setStatusCode(500)->setJSON(['message' => lang('App.paypalConfigurationError')]);
        }

        try {
            $paypalResponse = service('curlrequest')->post(rtrim($this->getPayPalBaseUrl(), '/') . '/v2/checkout/orders/' . urlencode($orderId) . '/capture', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => new \stdClass(),
                'http_errors' => false,
                'verify' => $this->shouldVerifySsl(),
                'timeout' => 20,
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'PayPal capture request failed: {message}', ['message' => $exception->getMessage()]);

            return $this->response->setStatusCode(500)->setJSON(['message' => lang('App.paypalCaptureFailed')]);
        }

        $captureData = json_decode($paypalResponse->getBody(), true);
        $purchaseUnit = is_array($captureData) ? ($captureData['purchase_units'][0] ?? null) : null;
        $capture = is_array($purchaseUnit) ? ($purchaseUnit['payments']['captures'][0] ?? null) : null;

        if (! is_array($purchaseUnit) || ! is_array($capture) || (string) ($capture['status'] ?? '') !== 'COMPLETED') {
            log_message('error', 'PayPal capture failed [{status}]: {body}', [
                'status' => $paypalResponse->getStatusCode(),
                'body' => $paypalResponse->getBody(),
            ]);

            return $this->response->setStatusCode(500)->setJSON(['message' => lang('App.paypalCaptureFailed')]);
        }

        $customId = (string) ($purchaseUnit['custom_id'] ?? '');
        if ($customId === '') {
            $orderDetails = $this->getPayPalOrderDetails($orderId, $accessToken);
            $customId = (string) ($orderDetails['purchase_units'][0]['custom_id'] ?? '');
        }

        $userId  = (int) session()->get('user_id');
        $result  = (new BookingService())->fulfillCapture($capture, $this->parsePayPalCustomId($customId), $userId, (int) $event['id']);
        $message = lang('App.bookingSuccess');

        switch ($result['status']) {
            case 'created':
                if (! $this->sendBookingConfirmationEmail($event, $result['seats'], $result['codes'], $result['amount'], $result['currency'])) {
                    $message .= ' ' . lang('App.bookingEmailFailed');
                }
                $this->notifyCapacityAfterBooking($event);
                session()->setFlashdata('event_info', $message);

                return $this->response->setJSON(['redirectUrl' => base_url('events/' . $slug)]);

            case 'duplicate':
                session()->setFlashdata('event_info', $message);

                return $this->response->setJSON(['redirectUrl' => base_url('events/' . $slug)]);

            case 'refunded':
                return $this->response->setStatusCode(409)->setJSON(['message' => lang('App.paypalAutoRefunded')]);

            case 'refund_failed':
                return $this->response->setStatusCode(409)->setJSON(['message' => lang('App.paypalRefundFailedContactAdmin')]);

            case 'error':
                // Payment is captured; the PayPal webhook will create the tickets when it retries.
                return $this->response->setStatusCode(500)->setJSON(['message' => lang('App.paypalPaidPendingTickets')]);

            default:
                return $this->response->setStatusCode(422)->setJSON(['message' => lang('App.paypalCaptureFailed')]);
        }
    }

    public function previewDiscount(string $slug)
    {
        if (! $this->passesBookingThrottle('discount_preview')) {
            return $this->response->setStatusCode(429)->setJSON(['message' => lang('App.bookingRateLimited')]);
        }

        $event = $this->eventModel->where('slug', $slug)->first();
        if (empty($event) || ($event['event_type'] ?? 'free') !== 'donation') {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Event not found']);
        }

        $seats   = max(1, (int) $this->getRequestValue('seats'));
        $perSeat = max((float) ($event['min_donation'] ?? 0), (float) $this->getRequestValue('donation_amount'));
        [$total, $discount, $error] = (new BookingService())->resolveTotal(
            (int) $event['id'],
            $seats,
            $perSeat,
            $this->getRequestValue('discount_code'),
            true,
            (int) session()->get('user_id')
        );

        if ($error !== null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => lang($error)]);
        }

        return $this->response->setJSON([
            'valid'       => true,
            'total'       => $total,
            'description' => (string) ($discount['description'] ?? ''),
        ]);
    }

    public function joinWaitlist(string $slug): RedirectResponse
    {
        $event = $this->eventModel->where('slug', $slug)->first();
        if (empty($event)) {
            throw PageNotFoundException::forPageNotFound('Event not found');
        }

        if (session()->get('is_logged_in') !== true) {
            return redirect()->to(base_url('login'))->with('login_info', lang('App.bookingLoginRequired'));
        }

        if ((string) ($event['status'] ?? '') !== 'active' || (int) ($event['bookings_enabled'] ?? 1) !== 1) {
            return redirect()->back()->with('event_error', lang('App.bookingEventUnavailable'));
        }

        if ($this->getRemainingSeats($event) > 0) {
            return redirect()->back()->with('event_error', lang('App.waitlistSeatsAvailable'));
        }

        $waitlist = new WaitlistModel();
        $userId   = (int) session()->get('user_id');
        $existing = $waitlist->where('event_id', (int) $event['id'])->where('user_id', $userId)->first();

        if ($existing === null) {
            $waitlist->insert(['event_id' => (int) $event['id'], 'user_id' => $userId]);
        }

        return redirect()->back()->with('event_info', lang('App.waitlistJoined'));
    }

    public function leaveWaitlist(string $slug): RedirectResponse
    {
        $event = $this->eventModel->where('slug', $slug)->first();
        if (empty($event)) {
            throw PageNotFoundException::forPageNotFound('Event not found');
        }

        (new WaitlistModel())
            ->where('event_id', (int) $event['id'])
            ->where('user_id', (int) session()->get('user_id'))
            ->delete();

        return redirect()->back()->with('event_info', lang('App.waitlistLeft'));
    }

    private function bookingFailureMessage(array $event, string $status, int $userId): string
    {
        return match ($status) {
            'full'  => strtr(lang('App.seatsLimitError'), ['{max}' => (string) $this->getRemainingSeats($event)]),
            'limit' => strtr(lang('App.seatsPerUserLimitError'), [
                '{max}' => (string) (new BookingService())->userAllowance((int) $event['id'], $userId),
            ]),
            default => lang('App.bookingGenericError'),
        };
    }

    private function notifyCapacityAfterBooking(array $event): void
    {
        $remaining = $this->getRemainingSeats($event);
        $capacity  = max(1, (int) ($event['capacity'] ?? 1));

        if ($remaining === 0) {
            $this->notifyAdminEventFull($event);

            return;
        }

        if ($remaining / $capacity <= 0.2) {
            $cacheKey = 'event_80pct_notified_' . (int) $event['id'];
            if (! cache()->get($cacheKey)) {
                $this->notifyAdminCapacityAlert($event, $remaining, $capacity);
                cache()->save($cacheKey, true, 86400);
            }
        }
    }
    private function passesBookingThrottle(string $scope): bool
    {
        $identity = (string) (session()->get('user_id') ?? $this->request->getIPAddress());
        $key = 'booking_rate_' . $scope . '_' . sha1($identity . '|' . $this->request->getIPAddress());
        $cache = cache();
        $attempts = (int) ($cache->get($key) ?? 0);

        if ($attempts >= 30) {
            return false;
        }

        $cache->save($key, $attempts + 1, 60);

        return true;
    }
}
