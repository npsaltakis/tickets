<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketModel extends Model
{
    protected $table = 'tickets';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $protectFields = true;
    protected $allowedFields = [
        'event_id',
        'user_id',
        'ticket_code',
        'donation_amount',
        'payment_status',
        'status',
        'checked_in_at',
        'checked_in_by',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'event_id'       => ['label' => 'Event',          'rules' => 'if_exist|required|is_natural_no_zero'],
        'user_id'        => ['label' => 'User',           'rules' => 'if_exist|required|is_natural_no_zero'],
        'ticket_code'    => ['label' => 'Ticket code',    'rules' => 'if_exist|required|max_length[191]'],
        'payment_status' => ['label' => 'Payment status', 'rules' => 'if_exist|required|in_list[pending,paid,free,failed]'],
        'status'         => ['label' => 'Status',         'rules' => 'if_exist|permit_empty|in_list[valid,cancelled]'],
        'checked_in_by'  => ['label' => 'Checked in by',  'rules' => 'if_exist|permit_empty|is_natural_no_zero'],
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
