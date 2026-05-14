<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // Default password
            'remember_token' => Str::random(10),
            'role' => 'client',
            'last_login_at' => null,
            'is_active' => true,
            'phone' => $this->faker->phoneNumber(),
            'phone_mobile' => $this->faker->phoneNumber(),
            'timezone' => 'Africa/Tunis',
            'locale' => 'fr',
            'whatsapp' => $this->faker->phoneNumber(),
            'teams_email' => $this->faker->email(),
            'avatar' => null,
            'profile_completed' => false,
            'must_change_password' => false,
            'client_type' => $this->faker->randomElement(['client', 'user', null]),
            'teams_webhook_url' => null,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'birthday' => $this->faker->date(),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
        ];
    }
}