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
                'id', 'user_id', 'account_number', 'balance', 'created_at', 'updated_at'
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
            'X-API-KEY' => 'skfn0123'
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
            'X-API-KEY' => 'skfn0123'
        ])->getJson("/api/accounts/{$account->id}/balance");

        $response->assertStatus(205);
        $response->assertJson([
            'account_id' => $account->id,
            'balance' => 7500, // because 2500 + 5000 (demo logic)
        ]);
    }
}
