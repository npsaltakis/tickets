<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminLogModel extends Model
{
    protected $table = 'admin_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'action',
        'target_type',
        'admin_id',
        'admin_email',
        'ip_address',
        'context',
        'created_at',
    ];

    protected $useTimestamps = false;
}
