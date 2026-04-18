<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $protectFields = true;
    protected $allowedFields = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'status',
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
        'first_name' => ['label' => 'First name', 'rules' => 'if_exist|required|max_length[100]'],
        'last_name'  => ['label' => 'Last name',  'rules' => 'if_exist|required|max_length[100]'],
        'email'      => ['label' => 'Email',      'rules' => 'if_exist|required|valid_email|max_length[191]'],
        'password'   => ['label' => 'Password',   'rules' => 'if_exist|permit_empty|max_length[255]'],
        'role'       => ['label' => 'Role',       'rules' => 'if_exist|required|in_list[admin,client]'],
        'status'     => ['label' => 'Status',     'rules' => 'if_exist|permit_empty|in_list[active,inactive,banned]'],
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
