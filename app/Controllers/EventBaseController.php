<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\PaymentModel;
use App\Models\TicketModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

abstract class EventBaseController extends BaseController
{
    protected EventModel $eventModel;
    protected TicketModel $ticketModel;
    protected PaymentModel $paymentModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->ticketModel = new TicketModel();
        $this->paymentModel = new PaymentModel();
    }

    protected function renderEventForm(?array $event = null): string
    {
        $isEditMode = ! empty($event);

        return view('events/create', [
            'event' => $event,
            'isEditMode' => $isEditMode,
            'pageTitle' => $isEditMode ? lang('App.eventEditPageTitle') : lang('App.eventCreatePageTitle'),
        ]);
    }

    protected function saveEvent(?array $existingEvent = null): RedirectResponse
    {
        helper('text');

        $title = trim((string) $this->request->getPost('title'));
        $description = trim((string) $this->request->getPost('description'));
        $imageUrl = trim((string) $this->request->getPost('image'));
        $location = trim((string) $this->request->getPost('location'));
        $address = trim((string) $this->request->getPost('address'));
        $infoPhone = trim((string) $this->request->getPost('info_phone'));
        $infoUrl = trim((string) $this->request->getPost('info_url'));
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
            || $address === ''
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

        if ($infoPhone !== '' && ! preg_match('/^[0-9+()\s.-]{6,25}$/', $infoPhone)) {
            return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateInvalidInfoPhone'));
        }

        if ($infoUrl !== '' && ! filter_var($infoUrl, FILTER_VALIDATE_URL)) {
            return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateInvalidInfoUrl'));
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

        $finalImage = $existingEvent['image'] ?? null;

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

        $slug = $this->generateUniqueSlug($title, isset($existingEvent['id']) ? (int) $existingEvent['id'] : null);

        $payload = [
            'title' => $title,
            'slug' => $slug,
            'description' => $description,
            'image' => $finalImage,
            'location' => $location,
            'address' => $address,
            'info_phone' => $infoPhone !== '' ? $infoPhone : null,
            'info_url' => $infoUrl !== '' ? $infoUrl : null,
            'start_date' => date('Y-m-d H:i:s', $startTimestamp),
            'end_date' => date('Y-m-d H:i:s', $endTimestamp),
            'capacity' => (int) $capacity,
            'event_type' => $eventType,
            'min_donation' => $normalizedMinDonation,
            'status' => $status,
        ];

        if ($existingEvent === null) {
            $this->eventModel->insert($payload);

            return redirect()->to(base_url('events/' . $slug))->with('event_info', lang('App.eventCreateSuccess'));
        }

        $this->eventModel->update((int) $existingEvent['id'], $payload);

        return redirect()->to(base_url('events/' . $slug))->with('event_info', lang('App.eventUpdateSuccess'));
    }

    protected function generateUniqueSlug(string $title, ?int $ignoreEventId = null): string
    {
        $baseSlug = url_title($title, '-', true);
        if ($baseSlug === '') {
            $baseSlug = 'event';
        }

        $slug = $baseSlug;
        $counter = 2;

        while (true) {
            $existing = $this->eventModel->where('slug', $slug)->first();
            if ($existing === null || ($ignoreEventId !== null && (int) ($existing['id'] ?? 0) === $ignoreEventId)) {
                return $slug;
            }

            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
    }

    protected function storeEventImage(UploadedFile $uploadedImage): ?string
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

    protected function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }

    protected function fetchEventBatch(string $query, int $offset, int $limit): array
    {
        $builder = $this->eventModel->builder();
        $builder->where('status', 'active');

        if ($query !== '') {
            $builder
                ->groupStart()
                ->like('title', $query)
                ->orLike('location', $query)
                ->orLike('description', $query)
                ->groupEnd();
        }

        $rows = $builder
            ->orderBy('start_date', 'ASC')
            ->limit($limit + 1, $offset)
            ->get()
            ->getResultArray();

        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            array_pop($rows);
        }

        return [$this->attachRemainingSeats($rows), $hasMore];
    }

    protected function attachRemainingSeats(array $events): array
    {
        if ($events === []) {
            return [];
        }

        $eventIds = array_values(array_filter(array_map(static fn (array $event): int => (int) ($event['id'] ?? 0), $events)));
        if ($eventIds === []) {
            return $events;
        }

        $bookedSeatRows = $this->ticketModel
            ->select('event_id, COUNT(id) AS booked_seats')
            ->whereIn('event_id', $eventIds)
            ->where('status', 'valid')
            ->groupBy('event_id')
            ->findAll();

        $bookedSeatsByEvent = [];
        foreach ($bookedSeatRows as $row) {
            $bookedSeatsByEvent[(int) ($row['event_id'] ?? 0)] = (int) ($row['booked_seats'] ?? 0);
        }

        foreach ($events as &$event) {
            $eventId = (int) ($event['id'] ?? 0);
            $capacity = (int) ($event['capacity'] ?? 0);
            $bookedSeats = $bookedSeatsByEvent[$eventId] ?? 0;
            $event['remaining_seats'] = max($capacity - $bookedSeats, 0);
        }

        unset($event);

        return $events;
    }

    protected function getRemainingSeats(array $event): int
    {
        $capacity = isset($event['capacity']) ? (int) $event['capacity'] : 0;
        $bookedSeats = $this->ticketModel
            ->where('event_id', $event['id'])
            ->where('status', 'valid')
            ->countAllResults();

        return max($capacity - $bookedSeats, 0);
    }

    protected function generateTicketCode(): string
    {
        do {
            $ticketCode = strtoupper(bin2hex(random_bytes(6)));
        } while ($this->ticketModel->where('ticket_code', $ticketCode)->first() !== null);

        return $ticketCode;
    }

    protected function sendBookingConfirmationEmail(array $event, int $requestedSeats, array $ticketCodes, float $donationAmount, string $currency): bool
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
        $greekMessageParts = [
            $this->localizedLine('App.bookingEmailGreeting', [], 'el') . ($userName !== '' ? ' ' . $userName : ''),
            $this->localizedLine('App.bookingEmailIntro', [], 'el'),
            $this->localizedLine('App.bookingEmailEventLabel', [], 'el') . ': ' . (string) ($event['title'] ?? '-'),
            $this->localizedLine('App.bookingEmailSeatsLabel', [], 'el') . ': ' . $requestedSeats,
            $this->localizedLine('App.bookingEmailStartLabel', [], 'el') . ': ' . $startDate,
            $this->localizedLine('App.bookingEmailEndLabel', [], 'el') . ': ' . $endDate,
            $this->localizedLine('App.bookingEmailLocationLabel', [], 'el') . ': ' . ($location !== '' ? $location : '-'),
        ];

        $englishMessageParts = [
            $this->localizedLine('App.bookingEmailGreeting', [], 'en') . ($userName !== '' ? ' ' . $userName : ''),
            $this->localizedLine('App.bookingEmailIntro', [], 'en'),
            $this->localizedLine('App.bookingEmailEventLabel', [], 'en') . ': ' . (string) ($event['title'] ?? '-'),
            $this->localizedLine('App.bookingEmailSeatsLabel', [], 'en') . ': ' . $requestedSeats,
            $this->localizedLine('App.bookingEmailStartLabel', [], 'en') . ': ' . $startDate,
            $this->localizedLine('App.bookingEmailEndLabel', [], 'en') . ': ' . $endDate,
            $this->localizedLine('App.bookingEmailLocationLabel', [], 'en') . ': ' . ($location !== '' ? $location : '-'),
        ];

        if ($donationAmount > 0) {
            $greekMessageParts[] = $this->localizedLine('App.bookingEmailDonationLabel', [], 'el') . ': ' . $currency . ' ' . number_format($donationAmount, 2);
            $englishMessageParts[] = $this->localizedLine('App.bookingEmailDonationLabel', [], 'en') . ': ' . $currency . ' ' . number_format($donationAmount, 2);
        }

        $greekMessageParts[] = $this->localizedLine('App.bookingEmailTicketCodesLabel', [], 'el') . ':' . PHP_EOL . $ticketCodesText;
        $greekMessageParts[] = $this->localizedLine('App.bookingEmailFooter', [], 'el');
        $englishMessageParts[] = $this->localizedLine('App.bookingEmailTicketCodesLabel', [], 'en') . ':' . PHP_EOL . $ticketCodesText;
        $englishMessageParts[] = $this->localizedLine('App.bookingEmailFooter', [], 'en');

        try {
            $emailService = service('email');
            $emailService->setTo($recipientEmail);
            $emailService->setSubject($this->bilingualSubject('App.bookingEmailSubject'));
            $emailService->setMessage($this->buildBilingualEmail($greekMessageParts, $englishMessageParts));

            return $emailService->send();
        } catch (Throwable) {
            return false;
        }
    }

    protected function validateDonationBookingRequest(array $event): array
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
        $minimumTotalDonation = $minimumDonation * $requestedSeats;

        if ($donationAmount < $minimumTotalDonation) {
            return [0, 0.0, strtr(lang('App.donationMinimumError'), [
                '{min}' => number_format($minimumTotalDonation, 2),
            ])];
        }

        return [$requestedSeats, $donationAmount, null];
    }

    protected function getRequestValue(string $key): string
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

    protected function getPayPalOrderDetails(string $orderId, string $accessToken): array
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

    protected function getPayPalAccessToken(): array
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

    protected function shouldVerifySsl(): bool
    {
        return env('CI_ENVIRONMENT', 'production') === 'production';
    }

    protected function getPayPalClientId(): string
    {
        return trim((string) (env('paypal.clientId') ?: env('paypal_clientId', '')));
    }

    protected function getPayPalSecret(): string
    {
        return trim((string) (env('paypal.secret') ?: env('paypal_secret', '')));
    }

    protected function getPayPalBaseUrl(): string
    {
        return trim((string) (env('paypal.baseUrl') ?: env('PAYPAL_BASE_URL', '')));
    }

    protected function parsePayPalCustomId(string $customId): array
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

    protected function splitAmountAcrossSeats(float $totalAmount, int $seats): array
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
}

