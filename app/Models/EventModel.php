<?php

namespace App\Models;

use CodeIgniter\Model;

class EventModel extends Model
{
    protected $table = 'events';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $protectFields = true;
    protected $allowedFields = [
        'title',
        'slug',
        'description',
        'image',
        'location',
        'address',
        'info_phone',
        'info_url',
        'start_date',
        'end_date',
        'capacity',
        'event_type',
        'event_format',
        'online_url',
        'online_access_notes',
        'min_donation',
        'status',
        'bookings_enabled',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'title'        => ['label' => 'Title',        'rules' => 'if_exist|required|max_length[255]'],
        'slug'         => ['label' => 'Slug',         'rules' => 'if_exist|required|max_length[191]'],
        'location'     => ['label' => 'Location',     'rules' => 'if_exist|required|max_length[255]'],
        'start_date'   => ['label' => 'Start date',   'rules' => 'if_exist|required'],
        'end_date'     => ['label' => 'End date',     'rules' => 'if_exist|required'],
        'capacity'     => ['label' => 'Capacity',     'rules' => 'if_exist|permit_empty|is_natural_no_zero'],
        'event_type'   => ['label' => 'Event type',   'rules' => 'if_exist|required|in_list[free,donation]'],
        'event_format' => ['label' => 'Event format', 'rules' => 'if_exist|required|in_list[physical,online,hybrid]'],
        'status'       => ['label' => 'Status',       'rules' => 'if_exist|required|in_list[active,inactive,cancelled]'],
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];
}
