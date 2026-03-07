<?php

namespace App\Controllers;

use App\Models\EventModel;

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
}
