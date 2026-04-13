<?php

declare(strict_types=1);

use App\Models\Quarterly;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->create();
    $this->fixtureHtml = file_get_contents(base_path('tests/fixtures/ssnet_lesson_03.html'));
});

it('shows the admin sabbath school index for admins', function (): void {
    $this->actingAs($this->admin)
        ->get('/admin/sabbath-school')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('admin/sabbath-school/index'));
});

it('blocks non-admin users from the sabbath school admin', function (): void {
    $this->actingAs($this->user)
        ->get('/admin/sabbath-school')
        ->assertForbidden();
});

it('blocks unauthenticated users from the sabbath school admin', function (): void {
    $this->get('/admin/sabbath-school')
        ->assertRedirect();
});

it('lists imported quarterlies', function (): void {
    Quarterly::factory()->active()->create(['quarter_code' => '26b', 'title' => 'Test Quarter']);
    Quarterly::factory()->create(['quarter_code' => '26a', 'title' => 'Old Quarter']);

    $this->actingAs($this->admin)
        ->get('/admin/sabbath-school')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/sabbath-school/index')
            ->has('quarterlies', 2)
        );
});

it('imports a quarter via the import endpoint', function (): void {
    Http::fake([
        'ssnet.org/lessons/26b/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $this->actingAs($this->admin)
        ->post('/admin/sabbath-school/import', ['quarter_code' => '26b'])
        ->assertRedirect(route('admin.sabbath-school.index'));

    expect(Quarterly::query()->where('quarter_code', '26b')->exists())->toBeTrue();
});

it('imports the current quarter when no code is given', function (): void {
    Http::fake([
        'ssnet.org/lessons/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $this->actingAs($this->admin)
        ->post('/admin/sabbath-school/import', ['quarter_code' => ''])
        ->assertRedirect(route('admin.sabbath-school.index'));

    expect(Quarterly::query()->count())->toBe(1);
});

it('validates the quarter code format', function (): void {
    $this->actingAs($this->admin)
        ->post('/admin/sabbath-school/import', ['quarter_code' => 'invalid'])
        ->assertSessionHasErrors('quarter_code');
});

it('can re-sync an existing quarter', function (): void {
    Http::fake([
        'ssnet.org/lessons/26b/*' => Http::response($this->fixtureHtml, 200),
    ]);

    $quarterly = Quarterly::factory()->create(['quarter_code' => '26b']);

    $this->actingAs($this->admin)
        ->post(sprintf('/admin/sabbath-school/%d/sync', $quarterly->id))
        ->assertRedirect(route('admin.sabbath-school.index'));

    expect($quarterly->fresh()->last_synced_at)->not->toBeNull();
});

it('can set a quarter as active', function (): void {
    $q1 = Quarterly::factory()->active()->create(['quarter_code' => '26a']);
    $q2 = Quarterly::factory()->create(['quarter_code' => '26b']);

    $this->actingAs($this->admin)
        ->put(sprintf('/admin/sabbath-school/%d/activate', $q2->id))
        ->assertRedirect(route('admin.sabbath-school.index'));

    expect($q1->fresh()->is_active)->toBeFalse();
    expect($q2->fresh()->is_active)->toBeTrue();
});
