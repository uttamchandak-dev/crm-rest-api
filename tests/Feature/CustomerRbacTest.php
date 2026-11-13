<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class CustomerRbacTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh = true;
    protected $namespace = 'App';

    private function registerAndGetToken(string $email, string $role = 'agent'): string
    {
        $result = $this->withBodyFormat('json')->post('api/auth/register', [
            'email'    => $email,
            'password' => 'supersecret1',
            'fullName' => 'Test User',
            'role'     => $role,
        ]);

        return json_decode($result->getJSON(), true)['token'];
    }

    private function authed(string $token): self
    {
        return $this->withHeaders(['Authorization' => "Bearer {$token}"])->withBodyFormat('json');
    }

    public function testAgentCanCreateAndListOwnCustomers(): void
    {
        $token = $this->registerAndGetToken('agent1@example.com');

        $create = $this->authed($token)->post('api/customers', ['name' => 'Globex Inc']);
        $create->assertStatus(201);

        $list = $this->authed($token)->get('api/customers');
        $list->assertStatus(200);

        $body = json_decode($list->getJSON(), true);
        $this->assertCount(1, $body);
        $this->assertSame('Globex Inc', $body[0]['name']);
    }

    public function testAgentCannotSeeAnotherAgentsCustomer(): void
    {
        $tokenA = $this->registerAndGetToken('agentA@example.com');
        $tokenB = $this->registerAndGetToken('agentB@example.com');

        $create = $this->authed($tokenA)->post('api/customers', ['name' => 'Private Co']);
        $customerId = json_decode($create->getJSON(), true)['id'];

        $show = $this->authed($tokenB)->get("api/customers/{$customerId}");
        $show->assertStatus(404);

        $listB = $this->authed($tokenB)->get('api/customers');
        $this->assertCount(0, json_decode($listB->getJSON(), true));
    }

    public function testAdminSeesAllCustomers(): void
    {
        $agentToken = $this->registerAndGetToken('agent2@example.com');
        $adminToken = $this->registerAndGetToken('admin1@example.com', 'admin');

        $this->authed($agentToken)->post('api/customers', ['name' => 'Agent Owned Co']);
        $this->authed($adminToken)->post('api/customers', ['name' => 'Admin Owned Co']);

        $list = $this->authed($adminToken)->get('api/customers');
        $this->assertCount(2, json_decode($list->getJSON(), true));
    }

    public function testOnlyAdminCanDeleteACustomer(): void
    {
        $agentToken = $this->registerAndGetToken('agent3@example.com');
        $adminToken = $this->registerAndGetToken('admin2@example.com', 'admin');

        $create = $this->authed($agentToken)->post('api/customers', ['name' => 'Deletable Co']);
        $customerId = json_decode($create->getJSON(), true)['id'];

        $deniedDelete = $this->authed($agentToken)->delete("api/customers/{$customerId}");
        $deniedDelete->assertStatus(403);

        $allowedDelete = $this->authed($adminToken)->delete("api/customers/{$customerId}");
        $allowedDelete->assertStatus(200);
    }

    public function testAddingANoteToAnOwnedCustomer(): void
    {
        $token = $this->registerAndGetToken('agent4@example.com');
        $create = $this->authed($token)->post('api/customers', ['name' => 'Note Target Co']);
        $customerId = json_decode($create->getJSON(), true)['id'];

        $note = $this->authed($token)->post("api/customers/{$customerId}/notes", [
            'body' => 'Called them, left a voicemail.',
        ]);
        $note->assertStatus(201);

        $list = $this->authed($token)->get("api/customers/{$customerId}/notes");
        $this->assertCount(1, json_decode($list->getJSON(), true));
    }
}
