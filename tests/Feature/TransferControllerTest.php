<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\BankAccount;
use App\Models\Transfer;

class TransferControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithTokenAndHeaders()
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;
        $headers = [
            'Authorization' => "Bearer $token",
            'X-API-KEY' => 'skfn0123',
        ];
        return compact('user', 'token', 'headers');
    }

    protected function getHeaders($token = null, $apiKey = null, $extras = [])
    {
        $headers = [];

        if ($token !== null) {
            $headers['Authorization'] = "Bearer $token";
        }

        if ($apiKey !== null) {
            $headers['X-API-KEY'] = $apiKey;
        }

        return array_merge($headers, $extras);
    }

    // Tests for store method
    public function test_transfer_with_correct_id_and_identifier_type_id()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $fromAccount = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 1000,
        ]);
        $toAccount = BankAccount::factory()->create([
            'balance' => 500,
        ]);

        $payload = [
            'from_account' => $fromAccount->id,
            'to_account' => $toAccount->id,
            'amount' => 200,
        ];

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->postJson('/api/transfers', $payload, $headers);

        $response->assertStatus(201);
        $response->assertJson(['message' => 'Transfer successful']);
        $this->assertDatabaseHas('transfers', [
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'amount' => 200,
        ]);
        $this->assertDatabaseHas('bank_accounts', [
            'id' => $fromAccount->id,
            'balance' => 800,
        ]);
        $this->assertDatabaseHas('bank_accounts', [
            'id' => $toAccount->id,
            'balance' => 700,
        ]);
    }

    public function test_transfer_with_correct_account_number_and_identifier_type_number()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $fromAccount = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 1000,
        ]);
        $toAccount = BankAccount::factory()->create([
            'balance' => 500,
        ]);

        $payload = [
            'from_account' => $fromAccount->account_number,
            'to_account' => $toAccount->account_number,
            'amount' => 300,
        ];

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'number'
        ]);

        $response = $this->postJson('/api/transfers', $payload, $headers);

        $response->assertStatus(201);
        $response->assertJson(['message' => 'Transfer successful']);
        $this->assertDatabaseHas('transfers', [
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'amount' => 300,
        ]);
        $this->assertDatabaseHas('bank_accounts', [
            'id' => $fromAccount->id,
            'balance' => 700,
        ]);
        $this->assertDatabaseHas('bank_accounts', [
            'id' => $toAccount->id,
            'balance' => 800,
        ]);
    }

    public function test_transfer_with_insufficient_funds()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $fromAccount = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 100,
        ]);
        $toAccount = BankAccount::factory()->create([
            'balance' => 500,
        ]);

        $payload = [
            'from_account' => $fromAccount->id,
            'to_account' => $toAccount->id,
            'amount' => 200,
        ];

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->postJson('/api/transfers', $payload, $headers);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Insufficient funds']);
        $this->assertDatabaseMissing('transfers', [
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
        ]);
    }

    public function test_transfer_with_non_existent_from_account()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $toAccount = BankAccount::factory()->create([
            'balance' => 500,
        ]);

        $payload = [
            'from_account' => 999999,
            'to_account' => $toAccount->id,
            'amount' => 200,
        ];

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->postJson('/api/transfers', $payload, $headers);

        $response->assertStatus(422);
    }

    public function test_transfer_with_non_existent_to_account()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $fromAccount = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 1000,
        ]);

        $payload = [
            'from_account' => $fromAccount->id,
            'to_account' => 999999,
            'amount' => 200,
        ];

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->postJson('/api/transfers', $payload, $headers);

        $response->assertStatus(422);
    }

    public function test_transfer_with_same_from_and_to_account()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $account = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 1000,
        ]);

        $payload = [
            'from_account' => $account->id,
            'to_account' => $account->id,
            'amount' => 200,
        ];

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->postJson('/api/transfers', $payload, $headers);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['to_account']);
    }

    public function test_transfer_with_invalid_amount()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $fromAccount = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 1000,
        ]);
        $toAccount = BankAccount::factory()->create([
            'balance' => 500,
        ]);

        $payload = [
            'from_account' => $fromAccount->id,
            'to_account' => $toAccount->id,
            'amount' => -50,
        ];

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->postJson('/api/transfers', $payload, $headers);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    public function test_transfer_with_missing_fields()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $payload = [];

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->postJson('/api/transfers', $payload, $headers);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['from_account', 'to_account', 'amount']);
    }

    public function test_transfer_without_identifier_type_header()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $fromAccount = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 1000,
        ]);
        $toAccount = BankAccount::factory()->create([
            'balance' => 500,
        ]);

        $payload = [
            'from_account' => $fromAccount->id,
            'to_account' => $toAccount->id,
            'amount' => 200,
        ];

        $response = $this->postJson('/api/transfers', $payload, $auth['headers']);

        $response->assertStatus(400);
    }

    public function test_transfer_with_unauthorized_from_account()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $otherUser = User::factory()->create();
        $fromAccount = BankAccount::factory()->create([
            'user_id' => $otherUser->id,
            'balance' => 1000,
        ]);
        $toAccount = BankAccount::factory()->create([
            'balance' => 500,
        ]);

        $payload = [
            'from_account' => $fromAccount->id,
            'to_account' => $toAccount->id,
            'amount' => 200,
        ];

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->postJson('/api/transfers', $payload, $headers);

        $response->assertStatus(404);
    }

    public function test_transfer_with_invalid_json_payload()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $invalidJson = '{"from_account": 1, "to_account": 2, "amount": 100'; // Missing closing brace

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->withHeaders($headers)->post('/api/transfers', [], [], [], [], $invalidJson);

        $response->assertStatus(422);
    }

    public function test_transfer_with_no_token()
    {
        $headers = $this->getHeaders(null, 'skfn0123', ['X-Account-Identifier-Type' => 'id']);
        $response = $this->postJson('/api/transfers', [], $headers);
        $response->assertStatus(401);
    }

    public function test_transfer_with_no_api_key()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $headers = $this->getHeaders($auth['token'], null, ['X-Account-Identifier-Type' => 'id']);
        $response = $this->postJson('/api/transfers', [], $headers);
        $response->assertStatus(401);
    }

    public function test_transfer_with_wrong_method_get()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $headers = array_merge($auth['headers'], ['X-Account-Identifier-Type' => 'id']);
        $response = $this->getJson('/api/transfers', $headers);
        $response->assertStatus(405);
    }

    // Tests for history method
    public function test_history_with_correct_id_and_identifier_type_id()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $account = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 1000,
        ]);
        $otherAccount = BankAccount::factory()->create([
            'balance' => 500,
        ]);

        Transfer::factory()->create([
            'from_account_id' => $account->id,
            'to_account_id' => $otherAccount->id,
            'amount' => 200,
        ]);
        Transfer::factory()->create([
            'from_account_id' => $otherAccount->id,
            'to_account_id' => $account->id,
            'amount' => 300,
        ]);

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->getJson("/api/accounts/{$account->id}/transfers", $headers);

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'from_account_id',
                'to_account_id',
                'amount',
                'created_at',
                'updated_at',
            ]
        ]);
    }

    public function test_history_with_correct_account_number_and_identifier_type_number()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $account = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 1000,
        ]);
        $otherAccount = BankAccount::factory()->create([
            'balance' => 500,
        ]);

        Transfer::factory()->create([
            'from_account_id' => $account->id,
            'to_account_id' => $otherAccount->id,
            'amount' => 200,
        ]);

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'number'
        ]);

        $response = $this->getJson("/api/accounts/{$account->account_number}/transfers", $headers);

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'from_account_id',
                'to_account_id',
                'amount',
                'created_at',
                'updated_at',
            ]
        ]);
    }

    public function test_history_with_non_existent_account_id()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->getJson("/api/accounts/999999/transfers", $headers);

        $response->assertStatus(404);
    }

    public function test_history_with_non_existent_account_number()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'number'
        ]);

        $response = $this->getJson("/api/accounts/ACC-INVALID/transfers", $headers);

        $response->assertStatus(404);
    }

    public function test_history_with_missing_identifier_type_header()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $account = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 1000,
        ]);

        $response = $this->getJson("/api/accounts/{$account->id}/transfers", $auth['headers']);

        $response->assertStatus(400);
    }

    public function test_history_with_unauthorized_account()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $otherUser = User::factory()->create();
        $account = BankAccount::factory()->create([
            'user_id' => $otherUser->id,
            'balance' => 1000,
        ]);

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->getJson("/api/accounts/{$account->id}/transfers", $headers);

        $response->assertStatus(404);
    }

    public function test_history_with_no_transfers()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $account = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 1000,
        ]);

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->getJson("/api/accounts/{$account->id}/transfers", $headers);

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    public function test_history_with_wrong_method_post()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $account = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 1000,
        ]);

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->postJson("/api/accounts/{$account->id}/transfers", [], $headers);

        $response->assertStatus(405);
    }

    public function test_history_with_no_token()
    {
        $headers = $this->getHeaders(null, 'skfn0123', ['X-Account-Identifier-Type' => 'id']);
        $response = $this->getJson('/api/accounts/1/transfers', $headers);
        $response->assertStatus(401);
    }

    public function test_history_with_no_api_key()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $headers = $this->getHeaders($auth['token'], null, ['X-Account-Identifier-Type' => 'id']);
        $response = $this->getJson('/api/accounts/1/transfers', $headers);
        $response->assertStatus(401);
    }
}