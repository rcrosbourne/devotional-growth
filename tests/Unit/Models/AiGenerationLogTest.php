<?php

declare(strict_types=1);

use App\Models\AiGenerationLog;
use App\Models\User;

test('to array', function (): void {
    $log = AiGenerationLog::factory()->create()->refresh();

    expect(array_keys($log->toArray()))
        ->toBe([
            'id',
            'admin_id',
            'prompt',
            'generated_content',
            'status',
            'error_message',
            'devotional_entry_id',
            'created_at',
            'updated_at',
        ]);
});

test('admin returns belongs to relationship', function (): void {
    $admin = User::factory()->admin()->create();
    $log = AiGenerationLog::factory()->create(['admin_id' => $admin->id]);

    expect($log->admin)
        ->toBeInstanceOf(User::class)
        ->id->toBe($admin->id);
});

test('factory defaults to pending status', function (): void {
    $log = AiGenerationLog::factory()->create();

    expect($log->status)->toBe('pending');
});

test('factory completed state sets status and generated content', function (): void {
    $log = AiGenerationLog::factory()->completed()->create();

    expect($log->status)->toBe('completed')
        ->and($log->generated_content)->toBeArray()
        ->and($log->generated_content)->toHaveKeys(['title', 'body', 'scripture_refs']);
});

test('factory failed state sets status and error message', function (): void {
    $log = AiGenerationLog::factory()->failed()->create();

    expect($log->status)->toBe('failed')
        ->and($log->error_message)->not->toBeNull();
});

test('factory approved state sets status and generated content', function (): void {
    $log = AiGenerationLog::factory()->approved()->create();

    expect($log->status)->toBe('approved')
        ->and($log->generated_content)->toBeArray();
});
