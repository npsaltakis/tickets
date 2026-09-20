<?php

namespace App\Models;

use CodeIgniter\Model;

class WaitlistModel extends Model
{
    protected $table         = 'waitlist';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['event_id', 'user_id', 'notified_at'];
}
