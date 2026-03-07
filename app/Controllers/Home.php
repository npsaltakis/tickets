<?php

namespace App\Controllers;

use App\Models\EventModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Home extends BaseController
{
    public function index(): string
    {
        $eventModel = new EventModel();

        $events = $eventModel
            ->orderBy('event_date', 'ASC')
            ->findAll();

        return view('events/index', [
            'events' => $events,
            'pageTitle' => 'All Events | Ticketing System',
        ]);
    }

    public function show(string $slug): string
    {
        $eventModel = new EventModel();

        $event = $eventModel->where('slug', $slug)->first();

        if (empty($event)) {
            throw PageNotFoundException::forPageNotFound('Event not found');
        }

        return view('events/show', [
            'event' => $event,
            'pageTitle' => $event['title'] . ' | Ticketing System',
        ]);
    }
}
