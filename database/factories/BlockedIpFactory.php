<?php

namespace Database\Factories;

use App\Models\BlockedIp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedIp>
 */
class BlockedIpFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ip' => fake()->unique()->ipv4(),
            'reason' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => ['expires_at' => now()->subMinute()]);
    }
}
