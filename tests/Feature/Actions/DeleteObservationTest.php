<?php

declare(strict_types=1);

use App\Actions\DeleteObservation;
use App\Models\Observation;
use App\Models\User;

it('deletes the observation from the database', function (): void {
    $user = User::factory()->create();
    $observation = Observation::factory()->for($user)->create();
    $action = resolve(DeleteObservation::class);

    $action->handle($observation);

    expect(Observation::query()->count())->toBe(0);
});

it('does not affect other observations when deleting one', function (): void {
    $user = User::factory()->create();
    $observation1 = Observation::factory()->for($user)->create();
    $observation2 = Observation::factory()->for($user)->create();
    $action = resolve(DeleteObservation::class);

    $action->handle($observation1);

    expect(Observation::query()->count())->toBe(1);
    expect($observation2->fresh())->not->toBeNull();
});
