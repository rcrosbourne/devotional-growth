<?php

declare(strict_types=1);

use App\Models\User;

test('to array', function (): void {
    $user = User::factory()->create()->refresh();

    expect(array_keys($user->toArray()))
        ->toBe([
            'id',
            'name',
            'email',
            'email_verified_at',
            'two_factor_confirmed_at',
            'created_at',
            'updated_at',
            'partner_id',
            'is_admin',
        ]);
});

test('partner returns belongs to relationship', function (): void {
    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();

    expect($user->partner)
        ->toBeInstanceOf(User::class)
        ->id->toBe($partner->id);
});

test('is admin returns true when user is admin', function (): void {
    $user = User::factory()->admin()->create();

    expect($user->isAdmin())->toBeTrue();
});

test('is admin returns false when user is not admin', function (): void {
    $user = User::factory()->create();

    expect($user->isAdmin())->toBeFalse();
});

test('has partner returns true when partner is linked', function (): void {
    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();

    expect($user->hasPartner())->toBeTrue();
});

test('has partner returns false when no partner is linked', function (): void {
    $user = User::factory()->create();

    expect($user->hasPartner())->toBeFalse();
});
