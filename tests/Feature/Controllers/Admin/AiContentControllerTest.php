<?php

declare(strict_types=1);

use App\Ai\Agents\DevotionalContentGenerator;
use App\Enums\AiGenerationStatus;
use App\Models\AiGenerationLog;
use App\Models\DevotionalEntry;
use App\Models\Theme;
use App\Models\User;
use Laravel\Ai\Image;

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

// Save (save generated content as devotion)

it('saves generated content to an existing theme', function (): void {
    Image::fake();

    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);
    $log = AiGenerationLog::factory()->completed()->create(['admin_id' => $admin->id]);

    $response = $this->actingAs($admin)
        ->postJson(route('admin.ai-content.save'), [
            'ai_generation_log_id' => $log->id,
            'theme_id' => $theme->id,
        ]);

    $response->assertOk()
        ->assertJsonPath('entry.theme.id', $theme->id);

    expect(DevotionalEntry::query()->count())->toBe(1);
    expect($log->refresh()->devotional_entry_id)->not->toBeNull();
});

it('saves generated content and creates a new theme', function (): void {
    Image::fake();

    $admin = User::factory()->admin()->create();
    $log = AiGenerationLog::factory()->completed()->create(['admin_id' => $admin->id]);

    $response = $this->actingAs($admin)
        ->postJson(route('admin.ai-content.save'), [
            'ai_generation_log_id' => $log->id,
            'new_theme_name' => 'Walking in Wisdom',
            'new_theme_description' => 'A series on wisdom',
        ]);

    $response->assertOk()
        ->assertJsonPath('entry.theme.name', 'Walking in Wisdom');

    expect(Theme::query()->where('name', 'Walking in Wisdom')->exists())->toBeTrue();
    expect(DevotionalEntry::query()->count())->toBe(1);
});

it('requires either a theme_id or new_theme_name', function (): void {
    $admin = User::factory()->admin()->create();
    $log = AiGenerationLog::factory()->completed()->create(['admin_id' => $admin->id]);

    $response = $this->actingAs($admin)
        ->postJson(route('admin.ai-content.save'), [
            'ai_generation_log_id' => $log->id,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('new_theme_name');
});

it('validates ai_generation_log_id exists', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->postJson(route('admin.ai-content.save'), [
            'ai_generation_log_id' => 9999,
            'new_theme_name' => 'Test',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('ai_generation_log_id');
});

it('denies non-admin from saving ai content', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('admin.ai-content.save'), [
            'ai_generation_log_id' => 1,
            'new_theme_name' => 'Test',
        ]);

    $response->assertForbidden();
});

it('still saves the entry when image generation fails', function (): void {
    Image::fake(fn () => throw new RuntimeException('Image provider error'));

    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);
    $log = AiGenerationLog::factory()->completed()->create(['admin_id' => $admin->id]);

    $response = $this->actingAs($admin)
        ->postJson(route('admin.ai-content.save'), [
            'ai_generation_log_id' => $log->id,
            'theme_id' => $theme->id,
        ]);

    $response->assertOk()
        ->assertJsonPath('entry.theme.id', $theme->id);

    expect(DevotionalEntry::query()->count())->toBe(1);
    expect($log->refresh()->devotional_entry_id)->not->toBeNull();
});

it('passes the themes prop to the generate page', function (): void {
    $admin = User::factory()->admin()->create();
    Theme::factory()->create(['name' => 'Alpha Theme', 'created_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->get(route('admin.ai-content.create'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/ai-content/generate')
            ->has('themes', 1)
        );
});
