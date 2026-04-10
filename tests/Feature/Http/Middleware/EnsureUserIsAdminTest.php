<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware(['web', 'auth', EnsureUserIsAdmin::class])
        ->get('test/admin-only', fn (): Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response => response('ok'));
});

it('allows admin users to access the route', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('test/admin-only')
        ->assertOk();
});

it('rejects non-admin users with 403', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('test/admin-only')
        ->assertForbidden();
});

it('rejects unauthenticated users', function (): void {
    $this->get('test/admin-only')
        ->assertRedirect(route('login'));
});
