<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AuthTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh = true;
    protected $namespace = 'App';

    public function testRegisterCreatesUserAndReturnsToken(): void
    {
        $result = $this->withBodyFormat('json')->post('api/auth/register', [
            'email'    => 'jane@example.com',
            'password' => 'supersecret1',
            'fullName' => 'Jane Doe',
        ]);

        $result->assertStatus(201);
        $body = json_decode($result->getJSON(), true);

        $this->assertArrayHasKey('token', $body);
        $this->assertSame('jane@example.com', $body['user']['email']);
        $this->assertSame('agent', $body['user']['role']);
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $this->withBodyFormat('json')->post('api/auth/register', [
            'email' => 'dup@example.com', 'password' => 'supersecret1', 'fullName' => 'A',
        ]);

        $result = $this->withBodyFormat('json')->post('api/auth/register', [
            'email' => 'dup@example.com', 'password' => 'supersecret1', 'fullName' => 'B',
        ]);

        $result->assertStatus(400);
    }

    public function testLoginRejectsWrongPassword(): void
    {
        $this->withBodyFormat('json')->post('api/auth/register', [
            'email' => 'login@example.com', 'password' => 'supersecret1', 'fullName' => 'Login User',
        ]);

        $result = $this->withBodyFormat('json')->post('api/auth/login', [
            'email' => 'login@example.com', 'password' => 'wrongpassword',
        ]);

        $result->assertStatus(401);
    }

    public function testLoginSucceedsWithCorrectCredentials(): void
    {
        $this->withBodyFormat('json')->post('api/auth/register', [
            'email' => 'ok@example.com', 'password' => 'supersecret1', 'fullName' => 'OK User',
        ]);

        $result = $this->withBodyFormat('json')->post('api/auth/login', [
            'email' => 'ok@example.com', 'password' => 'supersecret1',
        ]);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('token', $body);
    }

    public function testProtectedRouteRejectsMissingToken(): void
    {
        $result = $this->get('api/customers');
        $result->assertStatus(401);
    }
}
