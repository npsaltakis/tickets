<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class BookingController extends EventBaseController
{
    public function book(string $slug): RedirectResponse
    {
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

        if ((int) ($event['bookings_enabled'] ?? 1) !== 1) {
            return redirect()->back()->with('event_error', lang('App.bookingClosedMessage'));
        }

        if (! $this->hasAcceptedBookingTerms()) {
            return redirect()->back()->withInput()->with('event_error', lang('App.eventBookingConsentError'));
        }

        $requestedSeats = (int) $this->request->getPost('seats');
        if ($requestedSeats < 1) {
            return redirect()->back()->with('event_error', lang('App.bookingInvalidSeatCount'));
        }

        $remainingSeats = $this->getRemainingSeats($event);
        if ($requestedSeats > $remainingSeats) {
            return redirect()->back()->with('event_error', strtr(lang('App.seatsLimitError'), [
                '{max}' => (string) $remainingSeats,
            ]));
        }

        if (($event['event_type'] ?? 'free') !== 'free') {
            return redirect()->back()->with('event_error', lang('App.donationBookingPending'));
        }

        $userId = (int) session()->get('user_id');
        $ticketCodes = [];

        for ($i = 0; $i < $requestedSeats; $i++) {
            $ticketCode = $this->generateTicketCode();
            $ticketCodes[] = $ticketCode;

            $this->ticketModel->insert([
                'event_id' => $event['id'],
                'user_id' => $userId,
                'ticket_code' => $ticketCode,
                'donation_amount' => 0.00,
                'payment_status' => 'free',
                'status' => 'valid',
            ]);
        }

        $bookingMessage = lang('App.bookingSuccess');

        if (! $this->sendBookingConfirmationEmail($event, $requestedSeats, $ticketCodes, 0.00, 'EUR')) {
            $bookingMessage .= ' ' . lang('App.bookingEmailFailed');
        }

        return redirect()->to(base_url('events/' . $slug))->with('event_info', $bookingMessage);
    }

    public function createDonationOrder(string $slug)
    {
        $event = $this->eventModel->where('slug', $slug)->first();

        if (empty($event)) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Event not found']);
        }

        if (session()->get('is_logged_in') !== true) {
            return $this->response->setStatusCode(401)->setJSON(['message' => lang('App.bookingLoginRequired')]);
        }

        if (! $this->hasAcceptedBookingTerms()) {
            return $this->response->setStatusCode(422)->setJSON(['message' => lang('App.eventBookingConsentError')]);
        }

        [$requestedSeats, $donationAmountPerSeat, $error] = $this->validateDonationBookingRequest($event);
        if ($error !== null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $error]);
        }

        $totalDonationAmount = $this->getExpectedDonationTotal($requestedSeats, $donationAmountPerSeat);

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

        $captureId = (string) ($capture['id'] ?? '');
        if ($captureId === '') {
            log_message('error', 'PayPal capture missing capture id: {body}', [
                'body' => $paypalResponse->getBody(),
            ]);

            return $this->response->setStatusCode(500)->setJSON(['message' => lang('App.paypalCaptureFailed')]);
        }

        if (
            $this->payPalCaptureModel->where('paypal_transaction_id', $captureId)->first() !== null
            || $this->paymentModel->where('paypal_transaction_id', $captureId)->first() !== null
        ) {
            session()->setFlashdata('event_info', lang('App.bookingSuccess'));

            return $this->response->setJSON([
                'redirectUrl' => base_url('events/' . $slug),
            ]);
        }

        $customId = (string) ($purchaseUnit['custom_id'] ?? '');
        if ($customId === '') {
            $orderDetails = $this->getPayPalOrderDetails($orderId, $accessToken);
            $customId = (string) ($orderDetails['purchase_units'][0]['custom_id'] ?? '');

            log_message('error', 'PayPal capture missing custom_id in capture response. order_details={details}', [
                'details' => json_encode($orderDetails),
            ]);
        }

        $bookingData = $this->parsePayPalCustomId($customId);

        if (
            empty($bookingData)
            || (int) ($bookingData['event_id'] ?? 0) !== (int) $event['id']
            || (int) ($bookingData['user_id'] ?? 0) !== (int) session()->get('user_id')
        ) {
            log_message('error', 'PayPal booking data mismatch. custom_id={customId} parsed={parsed} event={eventId} user={userId}', [
                'customId' => $customId,
                'parsed' => json_encode($bookingData),
                'eventId' => (int) $event['id'],
                'userId' => (int) session()->get('user_id'),
            ]);

            return $this->response->setStatusCode(422)->setJSON(['message' => lang('App.paypalCaptureFailed')]);
        }

        $requestedSeats = (int) ($bookingData['seats'] ?? 0);
        $donationPerSeat = (float) ($bookingData['donation'] ?? 0);
        $donationAmount = (float) ($capture['amount']['value'] ?? 0);
        $currency = (string) ($capture['amount']['currency_code'] ?? 'EUR');

        $expectedDonationAmount = $this->getExpectedDonationTotal($requestedSeats, $donationPerSeat);

        if ($requestedSeats < 1 || $donationPerSeat <= 0 || $donationAmount <= 0) {
            log_message('error', 'PayPal capture invalid values. seats={seats} perSeat={perSeat} amount={amount} currency={currency} body={body}', [
                'seats' => $requestedSeats,
                'perSeat' => $donationPerSeat,
                'amount' => $donationAmount,
                'currency' => $currency,
                'body' => $paypalResponse->getBody(),
            ]);

            return $this->response->setStatusCode(422)->setJSON(['message' => lang('App.paypalCaptureFailed')]);
        }

        if (! $this->amountsMatch($expectedDonationAmount, $donationAmount)) {
            log_message('error', 'PayPal capture amount mismatch. expected={expected} actual={actual} seats={seats} perSeat={perSeat} captureId={captureId}', [
                'expected' => $expectedDonationAmount,
                'actual' => $donationAmount,
                'seats' => $requestedSeats,
                'perSeat' => $donationPerSeat,
                'captureId' => $captureId,
            ]);

            return $this->response->setStatusCode(422)->setJSON(['message' => lang('App.paypalCaptureFailed')]);
        }

        $remainingSeats = $this->getRemainingSeats($event);
        if ($requestedSeats > $remainingSeats) {
            return $this->response->setStatusCode(409)->setJSON(['message' => lang('App.bookingEventUnavailable')]);
        }

        $ticketCodes = [];
        $splitAmounts = $this->splitAmountAcrossSeats($donationAmount, $requestedSeats);
        $db = \Config\Database::connect();

        try {
            $db->transException(true)->transStart();

            if (
                $this->payPalCaptureModel->where('paypal_transaction_id', $captureId)->first() !== null
                || $this->paymentModel->where('paypal_transaction_id', $captureId)->first() !== null
            ) {
                $db->transComplete();
                session()->setFlashdata('event_info', lang('App.bookingSuccess'));

                return $this->response->setJSON([
                    'redirectUrl' => base_url('events/' . $slug),
                ]);
            }

            $this->payPalCaptureModel->insert([
                'paypal_transaction_id' => $captureId,
            ]);

            for ($i = 0; $i < $requestedSeats; $i++) {
                $ticketCode = $this->generateTicketCode();
                $ticketCodes[] = $ticketCode;

                $ticketId = $this->ticketModel->insert([
                    'event_id' => $event['id'],
                    'user_id' => (int) session()->get('user_id'),
                    'ticket_code' => $ticketCode,
                    'donation_amount' => $splitAmounts[$i],
                    'payment_status' => 'paid',
                    'status' => 'valid',
                ], true);

                $this->paymentModel->insert([
                    'ticket_id' => (int) $ticketId,
                    'paypal_transaction_id' => $captureId,
                    'amount' => $splitAmounts[$i],
                    'currency' => $currency,
                    'payment_status' => 'completed',
                ]);
            }

            $db->transComplete();
        } catch (Throwable $exception) {
            $db->transRollback();

            if (
                $this->payPalCaptureModel->where('paypal_transaction_id', $captureId)->first() !== null
                || $this->paymentModel->where('paypal_transaction_id', $captureId)->first() !== null
            ) {
                session()->setFlashdata('event_info', lang('App.bookingSuccess'));

                return $this->response->setJSON([
                    'redirectUrl' => base_url('events/' . $slug),
                ]);
            }

            log_message('error', 'PayPal capture persistence failed. captureId={captureId} message={message}', [
                'captureId' => $captureId,
                'message' => $exception->getMessage(),
            ]);

            return $this->response->setStatusCode(500)->setJSON(['message' => lang('App.paypalCaptureFailed')]);
        }

        $bookingMessage = lang('App.bookingSuccess');
        if (! $this->sendBookingConfirmationEmail($event, $requestedSeats, $ticketCodes, $donationAmount, $currency)) {
            $bookingMessage .= ' ' . lang('App.bookingEmailFailed');
        }

        session()->setFlashdata('event_info', $bookingMessage);

        return $this->response->setJSON([
            'redirectUrl' => base_url('events/' . $slug),
        ]);
    }
}
