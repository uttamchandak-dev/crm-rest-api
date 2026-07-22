<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthTokenModel extends Model
{
    protected $table         = 'auth_tokens';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['user_id', 'token_hash', 'created_at', 'expires_at', 'revoked_at'];
    protected $useTimestamps = false;
    protected $returnType    = 'array';

    public function findActiveByHash(string $hash): ?array
    {
        return $this->where('token_hash', $hash)
            ->where('revoked_at', null)
            ->groupStart()
                ->where('expires_at >=', date('Y-m-d H:i:s'))
                ->orWhere('expires_at', null)
            ->groupEnd()
            ->first();
    }
}
