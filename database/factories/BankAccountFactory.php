<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'account_number' => 'ACC-' . $this->faker->unique()->numerify('######'),
            'balance' => $this->faker->randomFloat(2, 100, 5000),
        ];
    }
}
