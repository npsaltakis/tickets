<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\PaymentModel;
use App\Models\TicketModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class Home extends BaseController
{
    private EventModel $eventModel;
    private TicketModel $ticketModel;
    private PaymentModel $paymentModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->ticketModel = new TicketModel();
        $this->paymentModel = new PaymentModel();
    }

    public function index(): string
    {
        $events = $this->eventModel
            ->where('status', 'active')
            ->orderBy('start_date', 'ASC')
            ->findAll();

        return view('events/index', [
            'events' => $this->attachRemainingSeats($events),
            'pageTitle' => 'All Events | Ticketing System',
        ]);
    }

    public function show(string $slug): string
    {
        $event = $this->eventModel->where('slug', $slug)->first();

        if (empty($event)) {
            throw PageNotFoundException::forPageNotFound('Event not found');
        }

        $event['remaining_seats'] = $this->getRemainingSeats($event);

        return view('events/show', [
            'event' => $event,
            'pageTitle' => $event['title'] . ' | Ticketing System',
            'paypalClientId' => $this->getPayPalClientId(),
        ]);
    }

    public function create(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        return view('events/create', [
            'pageTitle' => lang('App.eventCreatePageTitle'),
        ]);
    }

    public function store(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        helper('text');

        $title = trim((string) $this->request->getPost('title'));
        $description = trim((string) $this->request->getPost('description'));
        $imageUrl = trim((string) $this->request->getPost('image'));
        $location = trim((string) $this->request->getPost('location'));
        $startDatePart = trim((string) $this->request->getPost('start_date'));
        $startTimePart = trim((string) $this->request->getPost('start_time'));
        $endDatePart = trim((string) $this->request->getPost('end_date'));
        $endTimePart = trim((string) $this->request->getPost('end_time'));
        $capacity = trim((string) $this->request->getPost('capacity'));
        $eventType = trim((string) $this->request->getPost('event_type'));
        $minDonation = trim((string) $this->request->getPost('min_donation'));
        $status = trim((string) $this->request->getPost('status'));
        $uploadedImage = $this->request->getFile('image_upload');

        if (
            $title === ''
            || $description === ''
            || $location === ''
            || $startDatePart === ''
            || $startTimePart === ''
            || $endDatePart === ''
            || $endTimePart === ''
            || $capacity === ''
            || $eventType === ''
            || $status === ''
        ) {
            return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateRequiredFields'));
        }

        if (! ctype_digit($capacity) || (int) $capacity < 1) {
            return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateInvalidCapacity'));
        }

        $allowedTypes = ['free', 'donation'];
        if (! in_array($eventType, $allowedTypes, true)) {
            return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateInvalidType'));
        }

        $allowedStatuses = ['active', 'inactive', 'cancelled'];
        if (! in_array($status, $allowedStatuses, true)) {
            return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateInvalidStatus'));
        }

        $startTimestamp = strtotime($startDatePart . ' ' . $startTimePart);
        $endTimestamp = strtotime($endDatePart . ' ' . $endTimePart);
        if ($startTimestamp === false || $endTimestamp === false || $endTimestamp < $startTimestamp) {
            return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateInvalidDates'));
        }

        $normalizedMinDonation = null;
        if ($eventType === 'donation') {
            if ($minDonation === '' || ! is_numeric($minDonation) || (float) $minDonation < 0) {
                return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateInvalidDonation'));
            }

            $normalizedMinDonation = number_format((float) $minDonation, 2, '.', '');
        }

        $hasUpload = $uploadedImage instanceof UploadedFile && $uploadedImage->getError() !== UPLOAD_ERR_NO_FILE;

        if ($imageUrl !== '' && $hasUpload) {
            return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateImageSourceConflict'));
        }

        $finalImage = null;

        if ($imageUrl !== '') {
            if (! filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateInvalidImageUrl'));
            }

            $finalImage = $imageUrl;
        } elseif ($hasUpload) {
            if (! $uploadedImage->isValid()) {
                return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateInvalidImageUpload'));
            }

            $finalImage = $this->storeEventImage($uploadedImage);

            if ($finalImage === null) {
                return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateInvalidImageUpload'));
            }
        }

        $baseSlug = url_title($title, '-', true);
        if ($baseSlug === '') {
            $baseSlug = 'event';
        }

        $slug = $baseSlug;
        $counter = 2;
        while ($this->eventModel->where('slug', $slug)->first() !== null) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $this->eventModel->insert([
            'title' => $title,
            'slug' => $slug,
            'description' => $description,
            'image' => $finalImage,
            'location' => $location,
            'start_date' => date('Y-m-d H:i:s', $startTimestamp),
            'end_date' => date('Y-m-d H:i:s', $endTimestamp),
            'capacity' => (int) $capacity,
            'event_type' => $eventType,
            'min_donation' => $normalizedMinDonation,
            'status' => $status,
        ]);

        return redirect()->to(base_url('events/' . $slug))->with('event_info', lang('App.eventCreateSuccess'));
    }

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

        [$requestedSeats, $donationAmount, $error] = $this->validateDonationBookingRequest($event);
        if ($error !== null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $error]);
        }

        [$accessToken, $tokenError] = $this->getPayPalAccessToken();
        if ($accessToken === null) {
            return $this->response->setStatusCode(500)->setJSON(['message' => lang('App.paypalConfigurationError')]);
        }

        $customId = implode('|', [
            'event:' . (int) $event['id'],
            'user:' . (int) session()->get('user_id'),
            'seats:' . $requestedSeats,
            'donation:' . number_format($donationAmount, 2, '.', ''),
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
                            'value' => number_format($donationAmount, 2, '.', ''),
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

        if ($this->paymentModel->where('paypal_transaction_id', $captureId)->first() !== null) {
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
        $donationAmount = (float) ($capture['amount']['value'] ?? 0);
        $currency = (string) ($capture['amount']['currency_code'] ?? 'EUR');

        if ($requestedSeats < 1 || $donationAmount <= 0) {
            log_message('error', 'PayPal capture invalid values. seats={seats} amount={amount} currency={currency} body={body}', [
                'seats' => $requestedSeats,
                'amount' => $donationAmount,
                'currency' => $currency,
                'body' => $paypalResponse->getBody(),
            ]);

            return $this->response->setStatusCode(422)->setJSON(['message' => lang('App.paypalCaptureFailed')]);
        }

        $remainingSeats = $this->getRemainingSeats($event);
        if ($requestedSeats > $remainingSeats) {
            return $this->response->setStatusCode(409)->setJSON(['message' => lang('App.bookingEventUnavailable')]);
        }

        $ticketCodes = [];
        $splitAmounts = $this->splitAmountAcrossSeats($donationAmount, $requestedSeats);

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

        $bookingMessage = lang('App.bookingSuccess');
        if (! $this->sendBookingConfirmationEmail($event, $requestedSeats, $ticketCodes, $donationAmount, $currency)) {
            $bookingMessage .= ' ' . lang('App.bookingEmailFailed');
        }

        session()->setFlashdata('event_info', $bookingMessage);

        return $this->response->setJSON([
            'redirectUrl' => base_url('events/' . $slug),
        ]);
    }

    private function storeEventImage(UploadedFile $uploadedImage): ?string
    {
        $extension = strtolower((string) $uploadedImage->getExtension());
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (! in_array($extension, $allowedExtensions, true)) {
            return null;
        }

        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId < 1) {
            return null;
        }

        $relativeDirectory = 'assets/images/' . $userId;
        $targetDirectory = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
            return null;
        }

        $fileName = $uploadedImage->getRandomName();
        $uploadedImage->move($targetDirectory, $fileName, true);

        return base_url($relativeDirectory . '/' . $fileName);
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }

    private function attachRemainingSeats(array $events): array
    {
        foreach ($events as &$event) {
            $event['remaining_seats'] = $this->getRemainingSeats($event);
        }

        unset($event);

        return $events;
    }

    private function getRemainingSeats(array $event): int
    {
        $capacity = isset($event['capacity']) ? (int) $event['capacity'] : 0;
        $bookedSeats = $this->ticketModel
            ->where('event_id', $event['id'])
            ->where('status', 'valid')
            ->countAllResults();

        return max($capacity - $bookedSeats, 0);
    }

    private function generateTicketCode(): string
    {
        do {
            $ticketCode = strtoupper(bin2hex(random_bytes(6)));
        } while ($this->ticketModel->where('ticket_code', $ticketCode)->first() !== null);

        return $ticketCode;
    }

    private function sendBookingConfirmationEmail(array $event, int $requestedSeats, array $ticketCodes, float $donationAmount, string $currency): bool
    {
        $recipientEmail = trim((string) session()->get('user_email'));
        if ($recipientEmail === '') {
            return false;
        }

        $userName = trim((string) session()->get('user_name'));
        $startDate = ! empty($event['start_date']) ? date('d/m/Y H:i', strtotime((string) $event['start_date'])) : '-';
        $endDate = ! empty($event['end_date']) ? date('d/m/Y H:i', strtotime((string) $event['end_date'])) : '-';
        $location = trim((string) ($event['location'] ?? ''));
        $ticketCodesText = implode(PHP_EOL, $ticketCodes);
        $messageParts = [
            lang('App.bookingEmailGreeting') . ($userName !== '' ? ' ' . $userName : ''),
            lang('App.bookingEmailIntro'),
            lang('App.bookingEmailEventLabel') . ': ' . (string) ($event['title'] ?? '-'),
            lang('App.bookingEmailSeatsLabel') . ': ' . $requestedSeats,
            lang('App.bookingEmailStartLabel') . ': ' . $startDate,
            lang('App.bookingEmailEndLabel') . ': ' . $endDate,
            lang('App.bookingEmailLocationLabel') . ': ' . ($location !== '' ? $location : '-'),
        ];

        if ($donationAmount > 0) {
            $messageParts[] = lang('App.bookingEmailDonationLabel') . ': ' . $currency . ' ' . number_format($donationAmount, 2);
        }

        $messageParts[] = lang('App.bookingEmailTicketCodesLabel') . ':' . PHP_EOL . $ticketCodesText;
        $messageParts[] = lang('App.bookingEmailFooter');

        try {
            $emailService = service('email');
            $emailService->setTo($recipientEmail);
            $emailService->setSubject(lang('App.bookingEmailSubject'));
            $emailService->setMessage(implode(PHP_EOL . PHP_EOL, $messageParts));

            return $emailService->send();
        } catch (Throwable) {
            return false;
        }
    }

    private function validateDonationBookingRequest(array $event): array
    {
        if ((string) ($event['status'] ?? '') !== 'active') {
            return [0, 0.0, lang('App.bookingEventUnavailable')];
        }

        if (($event['event_type'] ?? 'free') !== 'donation') {
            return [0, 0.0, lang('App.donationBookingPending')];
        }

        $requestedSeats = (int) $this->getRequestValue('seats');
        if ($requestedSeats < 1) {
            return [0, 0.0, lang('App.bookingInvalidSeatCount')];
        }

        $remainingSeats = $this->getRemainingSeats($event);
        if ($requestedSeats > $remainingSeats) {
            return [0, 0.0, strtr(lang('App.seatsLimitError'), ['{max}' => (string) $remainingSeats])];
        }

        $donationAmountRaw = trim($this->getRequestValue('donation_amount'));
        if ($donationAmountRaw === '' || ! is_numeric($donationAmountRaw)) {
            return [0, 0.0, lang('App.donationAmountRequired')];
        }

        $donationAmount = (float) $donationAmountRaw;
        $minimumDonation = (float) ($event['min_donation'] ?? 0);

        if ($donationAmount < $minimumDonation) {
            return [0, 0.0, strtr(lang('App.donationMinimumError'), [
                '{min}' => number_format($minimumDonation, 2),
            ])];
        }

        return [$requestedSeats, $donationAmount, null];
    }

    private function getRequestValue(string $key): string
    {
        $postValue = $this->request->getPost($key);
        if ($postValue !== null && $postValue !== '') {
            return is_scalar($postValue) ? trim((string) $postValue) : '';
        }

        $varValue = $this->request->getVar($key);
        if ($varValue !== null && $varValue !== '') {
            return is_scalar($varValue) ? trim((string) $varValue) : '';
        }

        $rawInput = $this->request->getRawInput();
        if (is_array($rawInput) && array_key_exists($key, $rawInput)) {
            $rawValue = $rawInput[$key];
            return is_scalar($rawValue) ? trim((string) $rawValue) : '';
        }

        $jsonBody = $this->request->getJSON(true);
        if (is_array($jsonBody) && array_key_exists($key, $jsonBody)) {
            $jsonValue = $jsonBody[$key];
            return is_scalar($jsonValue) ? trim((string) $jsonValue) : '';
        }

        $body = (string) $this->request->getBody();
        if ($body !== '') {
            parse_str($body, $parsedBody);
            if (is_array($parsedBody) && array_key_exists($key, $parsedBody)) {
                $parsedValue = $parsedBody[$key];
                return is_scalar($parsedValue) ? trim((string) $parsedValue) : '';
            }
        }

        return '';
    }

    private function getPayPalOrderDetails(string $orderId, string $accessToken): array
    {
        try {
            $response = service('curlrequest')->get(rtrim($this->getPayPalBaseUrl(), '/') . '/v2/checkout/orders/' . urlencode($orderId), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'http_errors' => false,
                'verify' => $this->shouldVerifySsl(),
                'timeout' => 20,
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'PayPal order details request failed: {message}', ['message' => $exception->getMessage()]);

            return [];
        }

        $data = json_decode($response->getBody(), true);
        if (! is_array($data)) {
            log_message('error', 'PayPal order details invalid response [{status}]: {body}', [
                'status' => $response->getStatusCode(),
                'body' => $response->getBody(),
            ]);

            return [];
        }

        return $data;
    }

    private function getPayPalAccessToken(): array
    {
        $clientId = $this->getPayPalClientId();
        $secret = $this->getPayPalSecret();
        $baseUrl = rtrim($this->getPayPalBaseUrl(), '/');

        if ($clientId === '' || $secret === '' || $baseUrl === '') {
            log_message('error', 'PayPal environment is incomplete. clientId={clientId} secret={secret} baseUrl={baseUrl}', [
                'clientId' => $clientId !== '' ? 'set' : 'missing',
                'secret' => $secret !== '' ? 'set' : 'missing',
                'baseUrl' => $baseUrl !== '' ? $baseUrl : 'missing',
            ]);

            return [null, 'config'];
        }

        try {
            $response = service('curlrequest')->post($baseUrl . '/v1/oauth2/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $secret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ],
                'http_errors' => false,
                'verify' => $this->shouldVerifySsl(),
                'timeout' => 20,
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'PayPal token request exception: {message}', ['message' => $exception->getMessage()]);

            return [null, 'request'];
        }

        $data = json_decode($response->getBody(), true);
        $accessToken = is_array($data) ? ($data['access_token'] ?? null) : null;

        if (! is_string($accessToken) || $accessToken === '') {
            log_message('error', 'PayPal token request failed [{status}]: {body}', [
                'status' => $response->getStatusCode(),
                'body' => $response->getBody(),
            ]);

            return [null, 'token'];
        }

        return [$accessToken, null];
    }

    private function shouldVerifySsl(): bool
    {
        return env('CI_ENVIRONMENT', 'production') === 'production';
    }

    private function getPayPalClientId(): string
    {
        return trim((string) (env('paypal.clientId') ?: env('paypal_clientId', '')));
    }

    private function getPayPalSecret(): string
    {
        return trim((string) (env('paypal.secret') ?: env('paypal_secret', '')));
    }

    private function getPayPalBaseUrl(): string
    {
        return trim((string) (env('paypal.baseUrl') ?: env('PAYPAL_BASE_URL', '')));
    }

    private function parsePayPalCustomId(string $customId): array
    {
        $parts = explode('|', $customId);
        $data = [];

        foreach ($parts as $part) {
            [$key, $value] = array_pad(explode(':', $part, 2), 2, null);
            if ($key !== null && $value !== null) {
                $data[$key] = $value;
            }
        }

        return [
            'event_id' => isset($data['event']) ? (int) $data['event'] : 0,
            'user_id' => isset($data['user']) ? (int) $data['user'] : 0,
            'seats' => isset($data['seats']) ? (int) $data['seats'] : 0,
            'donation' => isset($data['donation']) ? (float) $data['donation'] : 0.0,
        ];
    }

    private function splitAmountAcrossSeats(float $totalAmount, int $seats): array
    {
        $totalCents = (int) round($totalAmount * 100);
        $baseCents = intdiv($totalCents, $seats);
        $remainder = $totalCents % $seats;
        $amounts = [];

        for ($i = 0; $i < $seats; $i++) {
            $amounts[] = number_format(($baseCents + ($i < $remainder ? 1 : 0)) / 100, 2, '.', '');
        }

        return $amounts;
    }

    public function myEvents(): RedirectResponse|string
    {
        if (session()->get('is_logged_in') !== true) {
            return redirect()->to(base_url('login'))->with('login_info', lang('App.bookingLoginRequired'));
        }

        $userId = (int) session()->get('user_id');
        $rows = $this->ticketModel
            ->select([
                'tickets.id AS ticket_id',
                'tickets.ticket_code',
                'tickets.donation_amount',
                'tickets.payment_status',
                'tickets.created_at AS booked_at',
                'events.id AS event_id',
                'events.slug',
                'events.title',
                'events.image',
                'events.location',
                'events.start_date',
                'events.end_date',
                'events.event_type',
                'events.status',
            ])
            ->join('events', 'events.id = tickets.event_id')
            ->where('tickets.user_id', $userId)
            ->where('tickets.status', 'valid')
            ->orderBy('events.start_date', 'ASC')
            ->orderBy('tickets.created_at', 'DESC')
            ->findAll();

        $events = [];

        foreach ($rows as $row) {
            $eventId = (int) ($row['event_id'] ?? 0);
            if ($eventId < 1) {
                continue;
            }

            if (! isset($events[$eventId])) {
                $events[$eventId] = [
                    'event_id' => $eventId,
                    'slug' => (string) ($row['slug'] ?? ''),
                    'title' => (string) ($row['title'] ?? ''),
                    'image' => (string) ($row['image'] ?? ''),
                    'location' => (string) ($row['location'] ?? ''),
                    'start_date' => $row['start_date'] ?? null,
                    'end_date' => $row['end_date'] ?? null,
                    'event_type' => (string) ($row['event_type'] ?? 'free'),
                    'status' => (string) ($row['status'] ?? 'inactive'),
                    'tickets_count' => 0,
                    'donation_total' => 0.0,
                    'booked_at' => $row['booked_at'] ?? null,
                    'ticket_codes' => [],
                    'payment_statuses' => [],
                ];
            }

            $events[$eventId]['tickets_count']++;
            $events[$eventId]['donation_total'] += (float) ($row['donation_amount'] ?? 0);
            $events[$eventId]['ticket_codes'][] = (string) ($row['ticket_code'] ?? '');
            $events[$eventId]['payment_statuses'][(string) ($row['payment_status'] ?? '')] = true;

            if (! empty($row['booked_at']) && (empty($events[$eventId]['booked_at']) || strtotime((string) $row['booked_at']) > strtotime((string) $events[$eventId]['booked_at']))) {
                $events[$eventId]['booked_at'] = $row['booked_at'];
            }
        }

        foreach ($events as &$event) {
            $event['donation_total'] = number_format((float) $event['donation_total'], 2, '.', '');
            $event['payment_summary'] = isset($event['payment_statuses']['paid']) ? 'paid' : 'free';
        }

        unset($event);

        return view('events/my_events', [
            'events' => array_values($events),
            'pageTitle' => lang('App.myEventsPageTitle'),
        ]);
    }
}
