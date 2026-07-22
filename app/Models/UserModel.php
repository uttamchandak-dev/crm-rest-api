<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['email', 'password_hash', 'full_name', 'role'];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = '';
    protected $returnType       = 'array';

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }
}
