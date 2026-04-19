<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class EventAdminController extends EventBaseController
{
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
}
