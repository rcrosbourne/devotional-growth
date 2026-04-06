<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SocialAccount>
 */
final class SocialAccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'google',
            'provider_id' => (string) fake()->unique()->randomNumber(9),
            'provider_token' => Str::random(40),
            'provider_refresh_token' => Str::random(40),
        ];
    }

    public function google(): self
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => 'google',
        ]);
    }

    public function apple(): self
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => 'apple',
        ]);
    }

    public function github(): self
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => 'github',
        ]);
    }
}
