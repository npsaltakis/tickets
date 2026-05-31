<?php

namespace App\Models;

use CodeIgniter\Model;

class DiscountCodeModel extends Model
{
    protected $table      = 'discount_codes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'code', 'description', 'type', 'value',
        'max_uses', 'used_count', 'event_id',
        'expires_at', 'is_active',
    ];

    public function findValid(string $code, int $eventId = 0): ?array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        $row = $this->where('code', $code)
            ->where('is_active', 1)
            ->groupStart()
                ->where('max_uses', null)
                ->orWhere('used_count <', db_connect()->escapeIdentifiers('max_uses'), false)
            ->groupEnd()
            ->groupStart()
                ->where('expires_at', null)
                ->orWhere('expires_at >=', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->groupStart()
                ->where('event_id', null)
                ->orWhere('event_id', $eventId)
            ->groupEnd()
            ->first();

        return $row ?: null;
    }

    public function applyDiscount(array $discountCode, float $originalAmount): float
    {
        $type  = (string) ($discountCode['type'] ?? 'percent');
        $value = (float) ($discountCode['value'] ?? 0);

        if ($type === 'percent') {
            $discounted = $originalAmount * (1 - min($value, 100) / 100);
        } else {
            $discounted = max(0, $originalAmount - $value);
        }

        return round($discounted, 2);
    }

    public function incrementUsed(int $id): void
    {
        $this->set('used_count', 'used_count + 1', false)->where('id', $id)->update();
    }
}
