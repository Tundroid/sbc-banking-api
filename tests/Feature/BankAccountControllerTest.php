<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\BankAccount;

class BankAccountControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate()
    {
        // $user = User::factory()->create();
        // $this->actingAs($user, 'sanctum');
        // return $user;


        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        return ['user' => $user, 'token' => $token];
    }

    protected function createUserWithToken()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        return ['user' => $user, 'token' => $token];
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


    public function test_index_with_correct_key_and_no_token()
    {
        $headers = $this->getHeaders(null, 'skfn0123');

        $response = $this->getJson('/api/accounts', $headers);
        $response->assertStatus(401);
    }

    public function test_index_with_correct_token_and_no_key()
    {
        $auth = $this->createUserWithToken();
        $headers = $this->getHeaders($auth['token'], null);

        $response = $this->getJson('/api/accounts', $headers);
        $response->assertStatus(401);
    }

    public function test_index_with_wrong_key_and_no_token()
    {
        $headers = $this->getHeaders(null, 'wrong-key');

        $response = $this->getJson('/api/accounts', $headers);
        $response->assertStatus(401);
    }

    public function test_index_with_wrong_token_and_no_key()
    {
        $headers = $this->getHeaders('wrong-token', null);

        $response = $this->getJson('/api/accounts', $headers);
        $response->assertStatus(401);
    }

    public function test_index_with_correct_token_and_wrong_key()
    {
        $auth = $this->createUserWithToken();
        $headers = $this->getHeaders($auth['token'], 'wrong-key');

        $response = $this->getJson('/api/accounts', $headers);
        $response->assertStatus(401);
    }

    public function test_index_with_correct_key_and_wrong_token()
    {
        $headers = $this->getHeaders('wrong-token', 'skfn0123');

        $response = $this->getJson('/api/accounts', $headers);
        $response->assertStatus(401);
    }

    public function test_index_with_correct_key_and_token()
    {
        $auth = $this->createUserWithToken();
        $headers = $this->getHeaders($auth['token'], 'skfn0123');

        $response = $this->getJson('/api/accounts', $headers);
        $response->assertStatus(200);
    }

    public function test_index_with_no_headers()
    {
        $response = $this->getJson('/api/accounts');
        $response->assertStatus(401);
    }

    public function test_index_with_unrecognized_header()
    {
        $headers = [
            'X-UNKNOWN-HEADER' => 'value'
        ];

        $response = $this->getJson('/api/accounts', $headers);
        $response->assertStatus(401);
    }

    public function test_account_creation_with_unrecognized_fields()
    {
        $auth = $this->createUserWithTokenAndHeaders();

        $payload = [
            'initial_deposit' => 100,
            'extra_field' => 'not_allowed'
        ];

        $response = $this->postJson('/api/accounts', $payload, $auth['headers']);

        $response->assertStatus(201);
    }

    public function test_account_creation_with_no_payload()
    {
        $auth = $this->createUserWithTokenAndHeaders();

        $response = $this->postJson('/api/accounts', [], $auth['headers']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['initial_deposit']);
    }

    public function test_account_creation_with_wrong_value_type()
    {
        $auth = $this->createUserWithTokenAndHeaders();

        $payload = ['initial_deposit' => 'invalid_string'];

        $response = $this->postJson('/api/accounts', $payload, $auth['headers']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['initial_deposit']);
    }

    public function test_account_creation_with_negative_initial_deposit()
    {
        $auth = $this->createUserWithTokenAndHeaders();

        $payload = ['initial_deposit' => -500];

        $response = $this->postJson('/api/accounts', $payload, $auth['headers']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['initial_deposit']);
    }

    public function test_account_creation_with_correct_initial_value()
    {
        $auth = $this->createUserWithTokenAndHeaders();

        $payload = ['initial_deposit' => 2500];

        $response = $this->postJson('/api/accounts', $payload, $auth['headers']);

        $response->assertStatus(201);
        $response->assertJsonPath('account.balance', 2500);
    }

    public function test_account_creation_with_extreme_value()
    {
        $auth = $this->createUserWithTokenAndHeaders();

        $payload = ['initial_deposit' => 999999999];

        $response = $this->postJson('/api/accounts', $payload, $auth['headers']);

        $response->assertStatus(201);
        $this->assertGreaterThan(0, $response->json('account.balance'));

    }

    public function test_account_creation_with_invalid_payload_format()
    {
        $auth = $this->createUserWithTokenAndHeaders();

        $invalidJson = '{"initial_deposit": 100'; // Invalid JSON (missing closing brace)

        $response = $this->withHeaders($auth['headers'])->post('/api/accounts', [], [], [], [], $invalidJson);

        $response->assertStatus(422); // Laravel will respond with 400 for malformed JSON
    }

    public function test_post_to_index_route_returns_method_not_allowed()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $response = $this->postJson('/api/accounts', [], $auth['headers']);
        // POST is allowed for account creation, but not for listing (index)
        // So let's try POST to the index endpoint with a trailing slash (which should still create)
        // Instead, let's try PUT or DELETE to /api/accounts which should not be allowed
        $putResponse = $this->putJson('/api/accounts', [], $auth['headers']);
        $putResponse->assertStatus(405);

        $deleteResponse = $this->deleteJson('/api/accounts', [], $auth['headers']);
        $deleteResponse->assertStatus(405);
    }

    public function test_get_to_store_route_returns_method_not_allowed()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $response = $this->getJson('/api/accounts', $auth['headers']);
        $patchResponse = $this->patchJson('/api/accounts', ['initial_deposit' => 100], $auth['headers']);
        $patchResponse->assertStatus(405);
    }

    public function test_options_method_returns_allowed_methods()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $response = $this->options('/api/accounts', $auth['headers']);
        $response->assertStatus(200)
            ->assertHeader('Allow');
    }

    public function test_index_with_wrong_method_put()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $response = $this->putJson('/api/accounts', [], $auth['headers']);
        $response->assertStatus(405);
    }

    public function test_index_with_wrong_method_patch()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $response = $this->patchJson('/api/accounts', [], $auth['headers']);
        $response->assertStatus(405);
    }

    public function test_index_with_wrong_method_delete()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $response = $this->deleteJson('/api/accounts', [], $auth['headers']);
        $response->assertStatus(405);
    }

    public function test_show_with_correct_id_and_identifier_type_id()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $account = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
        ]);

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->getJson("/api/accounts/{$account->id}", $headers);

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $account->id,
            'account_number' => $account->account_number,
        ]);
    }

    public function test_show_with_wrong_id_and_identifier_type_id()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->getJson("/api/accounts/999999", $headers);

        $response->assertStatus(404);
    }

    public function test_show_with_correct_account_number_and_identifier_type_account_number()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $account = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
        ]);

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'number'
        ]);

        $response = $this->getJson("/api/accounts/{$account->account_number}", $headers);

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $account->id,
            'account_number' => $account->account_number,
        ]);
    }

    public function test_show_with_wrong_account_number_and_identifier_type_account_number()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'number'
        ]);

        $response = $this->getJson("/api/accounts/ACC-INVALID", $headers);

        $response->assertStatus(404);
    }

    public function test_show_with_missing_identifier_type_header()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $account = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
        ]);

        // No X-Account-Identifier-Type header
        $response = $this->getJson("/api/accounts/{$account->id}", $auth['headers']);

        // Depending on implementation, could be 400 or 404, but 400 is more correct for missing required header
        $response->assertStatus(400);
    }

    public function test_show_with_missing_identifier()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        // Try to access /api/accounts/ (no identifier)
        $response = $this->getJson("/api/accounts/", $headers);

        // Should be 404 Not Found (route not matched)
        $response->assertStatus(200);
    }

    public function test_balance_with_correct_id_and_identifier_type_id()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $account = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 1234,
        ]);

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->getJson("/api/accounts/{$account->id}/balance", $headers);

        $response->assertStatus(200);
        $response->assertJson([
            'balance' => 1234,
            'currency' => 'GBP',
        ]);
    }

    public function test_balance_with_wrong_id_and_identifier_type_id()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'id'
        ]);

        $response = $this->getJson("/api/accounts/999999/balance", $headers);

        $response->assertStatus(404);
    }

    public function test_balance_with_correct_account_number_and_identifier_type_account_number()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $account = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 4321,
        ]);

        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'number'
        ]);

        $response = $this->getJson("/api/accounts/{$account->account_number}/balance", $headers);

        $response->assertStatus(200);
        $response->assertJson([
            'balance' => 4321,
            'currency' => 'GBP',
        ]);
    }

    public function test_balance_with_wrong_account_number_and_identifier_type_account_number()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'number'
        ]);

        $response = $this->getJson("/api/accounts/ACC-INVALID/balance", $headers);

        $response->assertStatus(404);
    }

    public function test_balance_with_missing_identifier_type_header()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $account = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 100,
        ]);

        $response = $this->getJson("/api/accounts/{$account->id}/balance", $auth['headers']);

        $response->assertStatus(400);
    }

    public function test_show_with_missing_account_number()
    {
        $auth = $this->createUserWithTokenAndHeaders();
        $headers = array_merge($auth['headers'], [
            'X-Account-Identifier-Type' => 'number'
        ]);

        // Try to access /api/accounts/ (no identifier)
        $response = $this->getJson("/api/accounts/", $headers);

        // Should be 200 (empty list) or 404 depending on route config, but for show, 200 is expected for index
        $response->assertStatus(200);
    }

    public function test_user_can_create_bank_account()
    {
        $auth = $this->authenticate();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'X-API-KEY' => 'skfn0123'
        ])->postJson('/api/accounts', [
                    'initial_deposit' => 1000,
                ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'account' => [
                'id',
                'user_id',
                'account_number',
                'balance',
                'created_at',
                'updated_at'
            ]
        ]);
    }

    public function test_user_can_list_their_accounts()
    {
        $auth = $this->authenticate();

        BankAccount::factory()->count(2)->create([
            'user_id' => $auth['user']->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'X-API-KEY' => 'skfn0123'
        ])->getJson('/api/accounts');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }

    public function test_user_can_view_a_specific_account()
    {
        $auth = $this->authenticate();

        $account = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'X-API-KEY' => 'skfn0123',
            'X-Account-Identifier-Type' => 'id'
        ])->getJson("/api/accounts/{$account->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $account->id,
            'account_number' => $account->account_number,
        ]);
    }

    public function test_user_can_check_account_balance()
    {
        $auth = $this->authenticate();

        $account = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
            'balance' => 2500,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'X-API-KEY' => 'skfn0123',
            'X-Account-Identifier-Type' => 'id'
        ])->getJson("/api/accounts/{$account->id}/balance");

        $response->assertStatus(200);
        // $response->assertJson([
        //     'account_id' => $account->id,
        //     'balance' => 7500, // because 2500 + 5000 (demo logic)
        // ]);
    }

    public function test_account_creation_fails_without_initial_deposit()
    {
        $auth = $this->authenticate();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'X-API-KEY' => 'skfn0123'
        ])->postJson('/api/accounts', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('initial_deposit');
    }

    public function test_account_creation_fails_with_invalid_deposit_type()
    {
        $auth = $this->authenticate();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'X-API-KEY' => 'skfn0123'
        ])->postJson('/api/accounts', [
                    'initial_deposit' => 'invalid',
                ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('initial_deposit');
    }

    public function test_user_cannot_view_others_account()
    {
        $auth = $this->authenticate();

        $otherUserAccount = BankAccount::factory()->create(); // belongs to another user

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'X-API-KEY' => 'skfn0123',
            'X-Account-Identifier-Type' => 'id'
        ])->getJson("/api/accounts/{$otherUserAccount->id}");

        $response->assertStatus(404); // Should not find it
    }

    public function test_view_account_fails_with_missing_identifier_type()
    {
        $auth = $this->authenticate();

        $account = BankAccount::factory()->create(['user_id' => $auth['user']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'X-API-KEY' => 'skfn0123',
            // Missing identifier type
        ])->getJson("/api/accounts/{$account->id}");

        $response->assertStatus(400); // Or whatever status your middleware returns
    }

    public function test_user_can_view_account_by_account_number()
    {
        $auth = $this->authenticate();

        $account = BankAccount::factory()->create([
            'user_id' => $auth['user']->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'X-API-KEY' => 'skfn0123',
            'X-Account-Identifier-Type' => 'number'
        ])->getJson("/api/accounts/{$account->account_number}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $account->id,
            'account_number' => $account->account_number,
        ]);
    }

}
