<?php

namespace Database\Factories;

use App\Models\Transfer;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferFactory extends Factory
{
    protected $model = Transfer::class;

    public function definition(): array
    {
        return [
            'from_account_id' => BankAccount::factory(),
            'to_account_id' => BankAccount::factory(),
            'amount' => $this->faker->randomFloat(2, 1, 1000),
        ];
    }
}
