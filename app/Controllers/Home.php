<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\TicketModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class Home extends BaseController
{
    private EventModel $eventModel;
    private TicketModel $ticketModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->ticketModel = new TicketModel();
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

        if (!ctype_digit($capacity) || (int) $capacity < 1) {
            return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateInvalidCapacity'));
        }

        $allowedTypes = ['free', 'donation'];
        if (!in_array($eventType, $allowedTypes, true)) {
            return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateInvalidType'));
        }

        $allowedStatuses = ['active', 'inactive', 'cancelled'];
        if (!in_array($status, $allowedStatuses, true)) {
            return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateInvalidStatus'));
        }

        $startTimestamp = strtotime($startDatePart . ' ' . $startTimePart);
        $endTimestamp = strtotime($endDatePart . ' ' . $endTimePart);
        if ($startTimestamp === false || $endTimestamp === false || $endTimestamp < $startTimestamp) {
            return redirect()->back()->withInput()->with('event_error', lang('App.eventCreateInvalidDates'));
        }

        $normalizedMinDonation = null;
        if ($eventType === 'donation') {
            if ($minDonation === '' || !is_numeric($minDonation) || (float) $minDonation < 0) {
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
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
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

        if (! $this->sendBookingConfirmationEmail($event, $requestedSeats, $ticketCodes)) {
            $bookingMessage .= ' ' . lang('App.bookingEmailFailed');
        }

        return redirect()->to(base_url('events/' . $slug))->with('event_info', $bookingMessage);
    }

    private function storeEventImage(UploadedFile $uploadedImage): ?string
    {
        $extension = strtolower((string) $uploadedImage->getExtension());
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($extension, $allowedExtensions, true)) {
            return null;
        }

        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId < 1) {
            return null;
        }

        $relativeDirectory = 'assets/images/' . $userId;
        $targetDirectory = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
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

    private function sendBookingConfirmationEmail(array $event, int $requestedSeats, array $ticketCodes): bool
    {
        $recipientEmail = trim((string) session()->get('user_email'));
        if ($recipientEmail === '') {
            return false;
        }

        $userName = trim((string) session()->get('user_name'));
        $startDate = !empty($event['start_date']) ? date('d/m/Y H:i', strtotime((string) $event['start_date'])) : '-';
        $endDate = !empty($event['end_date']) ? date('d/m/Y H:i', strtotime((string) $event['end_date'])) : '-';
        $location = trim((string) ($event['location'] ?? ''));
        $ticketCodesText = implode(PHP_EOL, $ticketCodes);

        $message = implode(PHP_EOL . PHP_EOL, [
            lang('App.bookingEmailGreeting') . ($userName !== '' ? ' ' . $userName : ''),
            lang('App.bookingEmailIntro'),
            lang('App.bookingEmailEventLabel') . ': ' . (string) ($event['title'] ?? '-'),
            lang('App.bookingEmailSeatsLabel') . ': ' . $requestedSeats,
            lang('App.bookingEmailStartLabel') . ': ' . $startDate,
            lang('App.bookingEmailEndLabel') . ': ' . $endDate,
            lang('App.bookingEmailLocationLabel') . ': ' . ($location !== '' ? $location : '-'),
            lang('App.bookingEmailTicketCodesLabel') . ':' . PHP_EOL . $ticketCodesText,
            lang('App.bookingEmailFooter'),
        ]);

        try {
            $emailService = service('email');
            $emailService->setTo($recipientEmail);
            $emailService->setSubject(lang('App.bookingEmailSubject'));
            $emailService->setMessage($message);

            return $emailService->send();
        } catch (Throwable) {
            return false;
        }
    }
}
