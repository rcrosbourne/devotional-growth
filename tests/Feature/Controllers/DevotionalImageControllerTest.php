<?php

declare(strict_types=1);

use App\Jobs\GenerateDevotionalImageJob;
use App\Models\DevotionalEntry;
use App\Models\GeneratedImage;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    Queue::fake();
});

// Store

it('dispatches a job to generate an image for a devotional entry', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();

    $response = $this->actingAs($user)
        ->post(route('entries.generate-image', $entry));

    $response->assertRedirect();

    Queue::assertPushed(GenerateDevotionalImageJob::class, fn (GenerateDevotionalImageJob $job): bool => $job->entry->id === $entry->id && $job->replace === false);
});

it('dispatches a job with replace flag when replace is true', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();
    GeneratedImage::factory()->for($entry)->create();

    $response = $this->actingAs($user)
        ->post(route('entries.generate-image', $entry), ['replace' => true]);

    $response->assertRedirect();

    Queue::assertPushed(GenerateDevotionalImageJob::class, fn (GenerateDevotionalImageJob $job): bool => $job->entry->id === $entry->id && $job->replace);
});

it('returns existing image without dispatching job when replace is false', function (): void {
    $user = User::factory()->create();
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();
    GeneratedImage::factory()->for($entry)->create();

    $response = $this->actingAs($user)
        ->post(route('entries.generate-image', $entry), ['replace' => false]);

    $response->assertRedirect();

    Queue::assertNotPushed(GenerateDevotionalImageJob::class);
});

// Authentication

it('redirects unauthenticated users to login', function (): void {
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();

    $response = $this->post(route('entries.generate-image', $entry));

    $response->assertRedirectToRoute('login');
});

it('rejects unverified users', function (): void {
    $user = User::factory()->unverified()->create();
    $entry = DevotionalEntry::factory()->for(Theme::factory())->create();

    $response = $this->actingAs($user)
        ->postJson(route('entries.generate-image', $entry));

    $response->assertForbidden();
});

it('returns 404 for a non-existent entry', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('entries.generate-image', 99999));

    $response->assertNotFound();
});
