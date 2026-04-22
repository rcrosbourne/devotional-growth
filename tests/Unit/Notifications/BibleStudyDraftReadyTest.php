<?php

declare(strict_types=1);

use App\Enums\AiGenerationStatus;
use App\Models\AiGenerationLog;
use App\Models\BibleStudyTheme;
use App\Models\User;
use App\Notifications\BibleStudyDraftReady;

it('returns a ready payload when a theme is present', function (): void {
    $admin = User::factory()->admin()->create();
    $log = AiGenerationLog::factory()->completed()->create(['admin_id' => $admin->id]);
    $theme = BibleStudyTheme::factory()->draft()->create(['slug' => 'forgiveness']);

    $payload = new BibleStudyDraftReady($log, 'Forgiveness', $theme)->toArray($admin);

    expect($payload['status'])->toBe('ready')
        ->and($payload['theme_id'])->toBe($theme->id)
        ->and($payload['theme_slug'])->toBe('forgiveness')
        ->and($payload['theme_title'])->toBe('Forgiveness')
        ->and($payload['log_id'])->toBe($log->id)
        ->and($payload['error_excerpt'])->toBeNull()
        ->and($payload['message'])->toContain('ready to review');
});

it('returns a failed payload when the theme is null', function (): void {
    $admin = User::factory()->admin()->create();
    $log = AiGenerationLog::factory()->create([
        'admin_id' => $admin->id,
        'status' => AiGenerationStatus::Failed,
        'error_message' => 'AI unavailable',
    ]);

    $payload = new BibleStudyDraftReady($log, 'Forgiveness', null)->toArray($admin);

    expect($payload['status'])->toBe('failed')
        ->and($payload['theme_id'])->toBeNull()
        ->and($payload['theme_slug'])->toBeNull()
        ->and($payload['error_excerpt'])->toBe('AI unavailable')
        ->and($payload['message'])->toContain('failed');
});

it('uses the database channel', function (): void {
    $admin = User::factory()->admin()->create();
    $log = AiGenerationLog::factory()->create(['admin_id' => $admin->id]);

    $channels = new BibleStudyDraftReady($log, 'Forgiveness', null)->via($admin);

    expect($channels)->toBe(['database']);
});
