<?php

namespace App\Controllers;

use App\Models\TicketModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class EventAdminController extends EventBaseController
{
    public function index(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        $events = $this->eventModel
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $issuedMap = [];
        if (! empty($events)) {
            $ticketModel = new \App\Models\TicketModel();
            $rows = $ticketModel
                ->select('event_id, COUNT(*) as cnt')
                ->whereIn('event_id', array_column($events, 'id'))
                ->where('status', 'valid')
                ->where('payment_status !=', 'failed')
                ->groupBy('event_id')
                ->findAll();
            foreach ($rows as $row) {
                $issuedMap[(int) $row['event_id']] = (int) $row['cnt'];
            }
        }

        return view('events/admin_index', [
            'events'     => $events,
            'issuedMap'  => $issuedMap,
            'pageTitle'  => lang('App.adminEventsPageTitle'),
        ]);
    }

    public function export(): ResponseInterface
    {
        if (! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setBody('Unauthorized');
        }

        $events = $this->eventModel->orderBy('created_at', 'DESC')->findAll();

        $issuedMap = [];
        if (! empty($events)) {
            $ticketModel = new TicketModel();
            $rows = $ticketModel
                ->select('event_id, COUNT(*) as cnt')
                ->whereIn('event_id', array_column($events, 'id'))
                ->where('status', 'valid')
                ->where('payment_status !=', 'failed')
                ->groupBy('event_id')
                ->findAll();
            foreach ($rows as $row) {
                $issuedMap[(int) $row['event_id']] = (int) $row['cnt'];
            }
        }

        $csvRows = [['Title', 'Status', 'Start Date', 'End Date', 'Type', 'Min Donation', 'Capacity', 'Issued', 'Location', 'Format']];

        foreach ($events as $event) {
            $csvRows[] = [
                $event['title'] ?? '',
                $event['status'] ?? '',
                $event['start_date'] ?? '',
                $event['end_date'] ?? '',
                $event['event_type'] ?? 'free',
                $event['min_donation'] ?? '',
                $event['capacity'] ?? 0,
                $issuedMap[(int) ($event['id'] ?? 0)] ?? 0,
                $event['location'] ?? '',
                $event['event_format'] ?? 'physical',
            ];
        }

        $csv = '';
        foreach ($csvRows as $row) {
            $csv .= implode(',', array_map(
                static fn ($cell) => '"' . str_replace('"', '""', (string) $cell) . '"',
                $row
            )) . "\r\n";
        }

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="events-' . date('Y-m-d') . '.csv"')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    public function updateStatus(string $slug): ResponseInterface
    {
        if (! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $event = $this->eventModel->where('slug', $slug)->first();
        if (empty($event)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Not found']);
        }

        $newStatus = trim((string) $this->request->getPost('status'));
        if (! in_array($newStatus, ['active', 'inactive', 'cancelled'], true)) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Invalid status']);
        }

        $this->eventModel->update((int) $event['id'], ['status' => $newStatus]);

        if ($newStatus === 'cancelled' && (string) ($event['status'] ?? '') !== 'cancelled') {
            $this->notifyTicketHoldersCancellation($event);
        }

        $this->logAdminAction('event_status_update', 'event', [
            'event_id'   => (int) $event['id'],
            'slug'       => $slug,
            'old_status' => (string) ($event['status'] ?? ''),
            'new_status' => $newStatus,
        ]);

        return $this->response->setJSON(['success' => true, 'status' => $newStatus]);
    }

    public function bulk(): ResponseInterface
    {
        if (! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $action = trim((string) $this->request->getPost('action'));
        $slugs  = array_filter(array_map('trim', (array) $this->request->getPost('slugs')));

        if ($slugs === [] || ! in_array($action, ['delete', 'activate', 'deactivate', 'cancel'], true)) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Invalid request']);
        }

        $events = $this->eventModel->whereIn('slug', array_values($slugs))->findAll();
        if (empty($events)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'No events found']);
        }

        $eventIds = array_map(static fn ($e) => (int) $e['id'], $events);

        switch ($action) {
            case 'delete':
                foreach ($eventIds as $id) {
                    $this->eventModel->delete($id);
                }
                break;
            case 'activate':
                $this->eventModel->whereIn('id', $eventIds)->set(['status' => 'active'])->update();
                break;
            case 'deactivate':
                $this->eventModel->whereIn('id', $eventIds)->set(['status' => 'inactive'])->update();
                break;
            case 'cancel':
                foreach ($events as $ev) {
                    if ((string) ($ev['status'] ?? '') !== 'cancelled') {
                        $this->eventModel->update((int) $ev['id'], ['status' => 'cancelled']);
                        $this->notifyTicketHoldersCancellation($ev);
                    }
                }
                break;
        }

        $this->logAdminAction('event_bulk_' . $action, 'event', [
            'count' => count($eventIds),
            'slugs' => implode(', ', $slugs),
        ]);

        return $this->response->setJSON(['success' => true, 'count' => count($eventIds)]);
    }

    public function create(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        return $this->renderEventForm();
    }

    public function edit(string $slug): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        $event = $this->eventModel->where('slug', $slug)->first();

        if (empty($event)) {
            throw PageNotFoundException::forPageNotFound('Event not found');
        }

        return $this->renderEventForm($event);
    }

    public function store(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        return $this->saveEvent();
    }

    public function update(string $slug): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        $event = $this->eventModel->where('slug', $slug)->first();

        if (empty($event)) {
            throw PageNotFoundException::forPageNotFound('Event not found');
        }

        return $this->saveEvent($event);
    }

    public function duplicate(string $slug): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        $event = $this->eventModel->where('slug', $slug)->first();

        if (empty($event)) {
            throw PageNotFoundException::forPageNotFound('Event not found');
        }

        $title = trim((string) ($event['title'] ?? 'Event'));
        $copyTitle = $title . ' ' . lang('App.eventDuplicateTitleSuffix');
        $copySlug = $this->generateUniqueSlug($copyTitle);
        $payload = [
            'title' => $copyTitle,
            'slug' => $copySlug,
            'description' => $event['description'] ?? '',
            'image' => $event['image'] ?? null,
            'location' => $event['location'] ?? '',
            'address' => $event['address'] ?? null,
            'info_phone' => $event['info_phone'] ?? null,
            'info_url' => $event['info_url'] ?? null,
            'start_date' => $event['start_date'] ?? null,
            'end_date' => $event['end_date'] ?? null,
            'capacity' => (int) ($event['capacity'] ?? 1),
            'event_type' => $event['event_type'] ?? 'free',
            'event_format' => $event['event_format'] ?? 'physical',
            'online_url' => $event['online_url'] ?? null,
            'online_access_notes' => $event['online_access_notes'] ?? null,
            'min_donation' => $event['min_donation'] ?? null,
            'status' => 'inactive',
            'bookings_enabled' => (int) ($event['bookings_enabled'] ?? 1),
        ];

        $newEventId = $this->eventModel->insert($payload, true);

        $this->logAdminAction('event_duplicate', 'event', [
            'source_event_id' => (int) ($event['id'] ?? 0),
            'target_event_id' => is_numeric($newEventId) ? (int) $newEventId : 0,
            'source_slug' => $slug,
            'target_slug' => $copySlug,
        ]);

        return redirect()
            ->to(base_url('events/' . $copySlug . '/edit'))
            ->with('event_info', lang('App.eventDuplicateSuccess'));
    }

    public function delete(string $slug): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        $event = $this->eventModel->where('slug', $slug)->first();

        if (empty($event)) {
            throw PageNotFoundException::forPageNotFound('Event not found');
        }

        $this->eventModel->delete((int) $event['id']);

        $this->logAdminAction('event_delete', 'event', [
            'target_event_id' => (int) ($event['id'] ?? 0),
            'slug' => $slug,
            'title' => (string) ($event['title'] ?? ''),
        ]);

        return redirect()->to(base_url('/'))->with('event_info', lang('App.eventDeleteSuccess'));
    }

    public function deleted(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        $events = $this->eventModel
            ->onlyDeleted()
            ->orderBy('deleted_at', 'DESC')
            ->findAll();

        return view('events/deleted', [
            'events' => $events,
            'pageTitle' => lang('App.deletedEventsPageTitle'),
        ]);
    }

    public function restore(string $slug): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        $event = $this->eventModel->withDeleted()->where('slug', $slug)->first();

        if (empty($event) || empty($event['deleted_at'])) {
            return redirect()->to(base_url('events/deleted'))->with('event_error', lang('App.deletedEventsNotFound'));
        }

        db_connect()->table('events')
            ->where('id', (int) $event['id'])
            ->update(['deleted_at' => null]);

        $this->logAdminAction('event_restore', 'event', [
            'target_event_id' => (int) ($event['id'] ?? 0),
            'slug' => $slug,
            'title' => (string) ($event['title'] ?? ''),
        ]);

        return redirect()->to(base_url('events/' . $slug))->with('event_info', lang('App.eventRestoreSuccess'));
    }
}
