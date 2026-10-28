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

    /**
     * The waiting list is off unless `waitlist.enabled = true` is set in .env.
     */
    public static function isEnabled(): bool
    {
        return filter_var(env('waitlist.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }
}
