<?php

namespace App\Models;

use CodeIgniter\Model;

class NoteModel extends Model
{
    protected $table           = 'notes';
    protected $primaryKey      = 'id';
    protected $allowedFields   = ['customer_id', 'user_id', 'body'];
    protected $useTimestamps   = true;
    protected $dateFormat      = 'datetime';
    protected $createdField    = 'created_at';
    protected $updatedField    = '';
    protected $returnType      = 'array';
    protected $validationRules = [
        'body' => 'required|min_length[1]',
    ];
}
