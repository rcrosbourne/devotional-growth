<?php

declare(strict_types=1);

use App\Models\User;

// Store (Link Partner)

it('links two users as partners', function (): void {
    $user = User::factory()->create();
    $partner = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('partner.store'), [
            'email' => $partner->email,
        ]);

    $response->assertRedirect();

    $user->refresh();
    $partner->refresh();

    expect($user->partner_id)->toBe($partner->id)
        ->and($partner->partner_id)->toBe($user->id);
});

it('validates partner email is required', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('partner.store'), []);

    $response->assertSessionHasErrors(['email']);
});

it('validates partner email must exist', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('partner.store'), [
            'email' => 'nonexistent@example.com',
        ]);

    $response->assertSessionHasErrors(['email']);
});

it('validates user cannot link themselves', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('partner.store'), [
            'email' => $user->email,
        ]);

    $response->assertSessionHasErrors(['email']);
});

it('validates partner email must be a valid email', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('partner.store'), [
            'email' => 'not-an-email',
        ]);

    $response->assertSessionHasErrors(['email']);
});

it('requires authentication to link a partner', function (): void {
    $response = $this->post(route('partner.store'), [
        'email' => 'test@example.com',
    ]);

    $response->assertRedirect(route('login'));
});

// Destroy (Unlink Partner)

it('unlinks the partner bidirectionally', function (): void {
    $partner = User::factory()->create();
    $user = User::factory()->withPartner($partner)->create();
    $partner->update(['partner_id' => $user->id]);

    $response = $this->actingAs($user)
        ->delete(route('partner.destroy'));

    $response->assertRedirect();

    $user->refresh();
    $partner->refresh();

    expect($user->partner_id)->toBeNull()
        ->and($partner->partner_id)->toBeNull();
});

it('handles unlinking when user has no partner', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->delete(route('partner.destroy'));

    $response->assertRedirect();
});

it('requires authentication to unlink a partner', function (): void {
    $response = $this->delete(route('partner.destroy'));

    $response->assertRedirect(route('login'));
});
