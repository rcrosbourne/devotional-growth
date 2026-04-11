<?php

declare(strict_types=1);

use App\Actions\LinkPartner;
use App\Models\User;

it('links two users as partners bidirectionally', function (): void {
    $user = User::factory()->create();
    $partner = User::factory()->create();
    $action = resolve(LinkPartner::class);

    $action->handle($user, $partner);

    $user->refresh();
    $partner->refresh();

    expect($user->partner_id)->toBe($partner->id)
        ->and($partner->partner_id)->toBe($user->id);
});

it('overwrites existing partner links', function (): void {
    $user = User::factory()->create();
    $oldPartner = User::factory()->create();
    $newPartner = User::factory()->create();
    $action = resolve(LinkPartner::class);

    $action->handle($user, $oldPartner);
    $action->handle($user, $newPartner);

    $user->refresh();
    $newPartner->refresh();

    expect($user->partner_id)->toBe($newPartner->id)
        ->and($newPartner->partner_id)->toBe($user->id);
});

it('uses a database transaction for atomicity', function (): void {
    $user = User::factory()->create();
    $partner = User::factory()->create();
    $action = resolve(LinkPartner::class);

    $action->handle($user, $partner);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'partner_id' => $partner->id,
    ]);
    $this->assertDatabaseHas('users', [
        'id' => $partner->id,
        'partner_id' => $user->id,
    ]);
});
