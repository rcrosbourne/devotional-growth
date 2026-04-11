<?php

declare(strict_types=1);

use App\Ai\Agents\DevotionalContentGenerator;
use App\Enums\AiGenerationStatus;
use App\Models\AiGenerationLog;
use App\Models\User;

// Create (render generate page)

it('renders the ai content generation page for admins', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.ai-content.create'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/ai-content/generate'));
});

it('denies non-admin access to ai content generation page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('admin.ai-content.create'));

    $response->assertForbidden();
});

it('redirects unauthenticated users to login from ai content page', function (): void {
    $response = $this->get(route('admin.ai-content.create'));

    $response->assertRedirectToRoute('login');
});

// Store (generate content)

it('generates devotional content successfully', function (): void {
    DevotionalContentGenerator::fake();

    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->postJson(route('admin.ai-content.store'), [
            'prompt' => 'Write a devotional about faith',
        ]);

    $response->assertOk()
        ->assertJsonPath('log.status', AiGenerationStatus::Completed->value)
        ->assertJsonPath('log.prompt', 'Write a devotional about faith');

    expect(AiGenerationLog::query()->count())->toBe(1);
});

it('returns a failed log when ai generation fails', function (): void {
    DevotionalContentGenerator::fake(function (): never {
        throw new RuntimeException('Provider error');
    });

    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->postJson(route('admin.ai-content.store'), [
            'prompt' => 'Write a devotional about faith',
        ]);

    $response->assertOk()
        ->assertJsonPath('log.status', AiGenerationStatus::Failed->value)
        ->assertJsonPath('log.error_message', 'Provider error');
});

it('requires a prompt to generate content', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->postJson(route('admin.ai-content.store'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('prompt');
});

it('rejects a prompt exceeding max length', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->postJson(route('admin.ai-content.store'), [
            'prompt' => str_repeat('a', 2001),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('prompt');
});

it('denies non-admin from generating content', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('admin.ai-content.store'), [
            'prompt' => 'Write a devotional about faith',
        ]);

    $response->assertForbidden();
});

it('denies unauthenticated users from generating content', function (): void {
    $response = $this->postJson(route('admin.ai-content.store'), [
        'prompt' => 'Write a devotional about faith',
    ]);

    $response->assertUnauthorized();
});
