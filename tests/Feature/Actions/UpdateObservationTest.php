<?php

declare(strict_types=1);

use App\Actions\UpdateObservation;
use App\Models\Observation;
use App\Models\User;
use Illuminate\Support\Facades\Date;

it('updates the observation body text', function (): void {
    $user = User::factory()->create();
    $observation = Observation::factory()->for($user)->create(['body' => 'Original text']);
    $action = resolve(UpdateObservation::class);

    $updated = $action->handle($observation, 'Updated text');

    expect($updated->body)->toBe('Updated text');
    expect($observation->fresh()->body)->toBe('Updated text');
});

it('sets the edited_at timestamp on update', function (): void {
    Date::setTestNow('2026-04-11 10:00:00');

    $user = User::factory()->create();
    $observation = Observation::factory()->for($user)->create(['edited_at' => null]);
    $action = resolve(UpdateObservation::class);

    $action->handle($observation, 'Edited text');

    expect($observation->fresh()->edited_at->toDateTimeString())->toBe('2026-04-11 10:00:00');

    Date::setTestNow();
});

it('updates edited_at on subsequent edits', function (): void {
    Date::setTestNow('2026-04-11 10:00:00');

    $user = User::factory()->create();
    $observation = Observation::factory()->for($user)->create(['edited_at' => now()->subHour()]);
    $action = resolve(UpdateObservation::class);

    $action->handle($observation, 'Re-edited text');

    expect($observation->fresh()->edited_at->toDateTimeString())->toBe('2026-04-11 10:00:00');

    Date::setTestNow();
});
