<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AiGenerationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiGenerationLog>
 */
final class AiGenerationLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_id' => User::factory()->admin(),
            'prompt' => fake()->sentence(),
            'generated_content' => null,
            'status' => 'pending',
            'error_message' => null,
            'devotional_entry_id' => null,
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
            'generated_content' => [
                'title' => fake()->sentence(4),
                'body' => fake()->paragraphs(3, true),
                'scripture_refs' => ['John 3:16'],
                'reflection_prompts' => [fake()->sentence()],
                'adventist_insights' => fake()->sentence(),
            ],
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'failed',
            'error_message' => 'AI provider returned an error.',
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'approved',
            'generated_content' => [
                'title' => fake()->sentence(4),
                'body' => fake()->paragraphs(3, true),
                'scripture_refs' => ['John 3:16'],
                'reflection_prompts' => [fake()->sentence()],
                'adventist_insights' => fake()->sentence(),
            ],
        ]);
    }
}
