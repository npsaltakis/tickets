<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table      = 'categories';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['name', 'slug', 'color'];
    protected $validationRules = [
        'name'  => ['label' => 'Name', 'rules' => 'if_exist|required|max_length[191]'],
        'slug'  => ['label' => 'Slug', 'rules' => 'if_exist|required|max_length[191]'],
        'color' => ['label' => 'Color', 'rules' => 'if_exist|permit_empty|max_length[7]'],
    ];
}
