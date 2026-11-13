<?php

namespace App\Filters;

use App\Libraries\AuthContext;
use App\Models\AuthTokenModel;
use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class TokenAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['error' => 'Missing or malformed Authorization header']);
        }

        $token     = trim($matches[1]);
        $tokenHash = hash('sha256', $token);

        $tokenModel = new AuthTokenModel();
        $record     = $tokenModel->findActiveByHash($tokenHash);

        if (!$record) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['error' => 'Invalid or expired token']);
        }

        $userModel = new UserModel();
        $user      = $userModel->find($record['user_id']);

        if (!$user) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['error' => 'User not found for token']);
        }

        AuthContext::set($user);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }
}
