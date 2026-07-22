<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table            = 'customers';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['owner_id', 'name', 'email', 'phone', 'company', 'status'];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $returnType       = 'array';
    protected $validationRules  = [
        'name'   => 'required|min_length[2]|max_length[255]',
        'email'  => 'permit_empty|valid_email',
        'status' => 'permit_empty|in_list[lead,active,churned]',
    ];
}
