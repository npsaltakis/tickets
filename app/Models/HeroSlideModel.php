<?php

namespace App\Models;

use CodeIgniter\Model;

class HeroSlideModel extends Model
{
    protected $table      = 'hero_slides';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['type', 'event_id', 'image', 'title', 'subtitle', 'link_url', 'button_text', 'sort_order', 'is_active'];
}
