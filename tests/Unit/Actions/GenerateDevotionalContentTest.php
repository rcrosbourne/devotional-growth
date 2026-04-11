<?php

declare(strict_types=1);

use App\Actions\GenerateDevotionalContent;
use App\Ai\Agents\DevotionalContentGenerator;
use App\Enums\AiGenerationStatus;
use App\Models\AiGenerationLog;
use App\Models\User;

it('creates a completed ai generation log on successful generation', function (): void {
    DevotionalContentGenerator::fake();

    $admin = User::factory()->admin()->create();
    $action = resolve(GenerateDevotionalContent::class);

    $log = $action->handle($admin, 'Write a devotional about faith');

    expect($log)->toBeInstanceOf(AiGenerationLog::class)
        ->and($log->admin_id)->toBe($admin->id)
        ->and($log->prompt)->toBe('Write a devotional about faith')
        ->and($log->status)->toBe(AiGenerationStatus::Completed)
        ->and($log->generated_content)->toBeArray()
        ->and($log->generated_content)->toHaveKeys(['title', 'body', 'scripture_refs', 'reflection_prompts', 'adventist_insights'])
        ->and($log->error_message)->toBeNull();
});

it('prompts the agent with the provided prompt', function (): void {
    DevotionalContentGenerator::fake();

    $admin = User::factory()->admin()->create();
    $action = resolve(GenerateDevotionalContent::class);

    $action->handle($admin, 'Write a devotional about forgiveness');

    DevotionalContentGenerator::assertPrompted(fn ($prompt) => $prompt->contains('forgiveness'));
});

it('creates a failed ai generation log when the agent throws an exception', function (): void {
    DevotionalContentGenerator::fake(function (): never {
        throw new RuntimeException('AI provider returned an error.');
    });

    $admin = User::factory()->admin()->create();
    $action = resolve(GenerateDevotionalContent::class);

    $log = $action->handle($admin, 'Write a devotional about faith');

    expect($log->status)->toBe(AiGenerationStatus::Failed)
        ->and($log->error_message)->toBe('AI provider returned an error.')
        ->and($log->generated_content)->toBeNull();
});

it('persists the log in the database', function (): void {
    DevotionalContentGenerator::fake();

    $admin = User::factory()->admin()->create();
    $action = resolve(GenerateDevotionalContent::class);

    $action->handle($admin, 'Write about hope');

    expect(AiGenerationLog::query()->count())->toBe(1);
});
