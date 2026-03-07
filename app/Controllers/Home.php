<?php

namespace App\Controllers;

use App\Models\EventModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Home extends BaseController
{
    private EventModel $eventModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
    }

    public function index(): string
    {
        $events = $this->eventModel
            ->orderBy('start_date', 'ASC')
            ->findAll();

        return view('events/index', [
            'events' => $events,
            'pageTitle' => 'All Events | Ticketing System',
        ]);
    }

    public function show(string $slug): string
    {
        $event = $this->eventModel->where('slug', $slug)->first();

        if (empty($event)) {
            throw PageNotFoundException::forPageNotFound('Event not found');
        }

        return view('events/show', [
            'event' => $event,
            'pageTitle' => $event['title'] . ' | Ticketing System',
        ]);
    }
}
