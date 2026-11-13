<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Models\AuthTokenModel;
use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;

class AuthController extends ResourceController
{
    public function register()
    {
        $rules = [
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'fullName' => 'required|min_length[2]',
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $userModel = new UserModel();

        $userId = $userModel->insert([
            'email'         => $this->request->getVar('email'),
            'password_hash' => password_hash($this->request->getVar('password'), PASSWORD_BCRYPT),
            'full_name'     => $this->request->getVar('fullName'),
            'role'          => $this->request->getVar('role') === 'admin' ? 'admin' : 'agent',
        ]);

        return $this->respondCreated($this->issueToken((int) $userId, $userModel->find($userId)));
    }

    public function login()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $userModel = new UserModel();
        $user      = $userModel->findByEmail($this->request->getVar('email'));

        if (!$user || !password_verify($this->request->getVar('password'), $user['password_hash'])) {
            return $this->failUnauthorized('Invalid credentials');
        }

        return $this->respond($this->issueToken((int) $user['id'], $user));
    }

    public function logout()
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $this->failUnauthorized('Missing token');
        }

        $tokenHash  = hash('sha256', trim($matches[1]));
        $tokenModel = new AuthTokenModel();
        $record     = $tokenModel->where('token_hash', $tokenHash)->first();

        if ($record) {
            $tokenModel->update($record['id'], ['revoked_at' => date('Y-m-d H:i:s')]);
        }

        return $this->respondNoContent();
    }

    public function me()
    {
        $user = AuthContext::user();
        unset($user['password_hash']);
        return $this->respond($user);
    }

    private function issueToken(int $userId, array $user): array
    {
        $plainToken = bin2hex(random_bytes(32));

        (new AuthTokenModel())->insert([
            'user_id'    => $userId,
            'token_hash' => hash('sha256', $plainToken),
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
        ]);

        unset($user['password_hash']);

        return [
            'token' => $plainToken,
            'user'  => $user,
        ];
    }
}
