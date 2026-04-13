<?php

declare(strict_types=1);

use App\Actions\SabbathSchool\GenerateLessonImage;
use App\Actions\SabbathSchool\ImportQuarter;
use App\Jobs\GenerateLessonImageJob;
use App\Models\Lesson;
use App\Models\Quarterly;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Image;

// GenerateLessonImage action

it('skips image generation when lesson already has an image', function (): void {
    $lesson = Lesson::factory()->withImage()->create();
    $originalPath = $lesson->image_path;

    $action = new GenerateLessonImage();
    $action->handle($lesson);

    expect($lesson->fresh()->image_path)->toBe($originalPath);
});

it('builds a prompt with lesson details and varied style and subject', function (): void {
    $lesson = Lesson::factory()->create([
        'image_path' => null,
        'title' => 'Walking in Faith',
        'memory_text' => 'For we walk by faith, not by sight.',
        'memory_text_reference' => '2 Corinthians 5:7',
    ]);

    $action = new GenerateLessonImage();
    $method = new ReflectionMethod($action, 'buildPrompt');
    $prompt = $method->invoke($action, $lesson);

    expect($prompt)
        ->toContain('Walking in Faith')
        ->toContain('For we walk by faith, not by sight.')
        ->toContain('2 Corinthians 5:7')
        ->toContain('spiritual reflection and Bible study')
        ->toContain('Do not include any text or words');
});

it('generates varied prompts across multiple calls', function (): void {
    $lesson = Lesson::factory()->create([
        'image_path' => null,
        'title' => 'Walking in Faith',
        'memory_text' => 'For we walk by faith, not by sight.',
        'memory_text_reference' => '2 Corinthians 5:7',
    ]);

    $action = new GenerateLessonImage();
    $method = new ReflectionMethod($action, 'buildPrompt');

    $prompts = collect(range(1, 20))->map(fn (): mixed => $method->invoke($action, $lesson));

    expect($prompts->unique()->count())->toBeGreaterThan(1);
});

it('skips image generation and logs warning when lesson has no quarterly', function (): void {
    Log::spy();

    $lesson = Lesson::factory()->create(['image_path' => null]);

    // Simulate orphaned lesson by nullifying the loaded relationship
    $lesson->setRelation('quarterly', null);

    $action = new GenerateLessonImage();
    $action->handle($lesson);

    expect($lesson->fresh()->image_path)->toBeNull();

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message): bool => $message === 'GenerateLessonImage: Lesson has no quarterly')
        ->once();
});

// GenerateLessonImageJob

it('dispatches image generation job', function (): void {
    Queue::fake();

    $lesson = Lesson::factory()->create(['image_path' => null]);

    dispatch(new GenerateLessonImageJob($lesson));

    Queue::assertPushed(GenerateLessonImageJob::class, fn (GenerateLessonImageJob $job): bool => $job->lesson->id === $lesson->id);
});

// ImportQuarter dispatches image jobs

it('dispatches image generation jobs after import', function (): void {
    Queue::fake();

    $fixtureHtml = file_get_contents(base_path('tests/fixtures/ssnet_lesson_03.html'));
    Http::fake([
        'ssnet.org/lessons/26b/*' => Http::response($fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $action->handle('26b');

    Queue::assertPushed(GenerateLessonImageJob::class, 13);
});

it('does not dispatch jobs for lessons that already have images', function (): void {
    Queue::fake();

    $quarterly = Quarterly::factory()->create(['quarter_code' => '26b']);
    Lesson::factory()->withImage()->create([
        'quarterly_id' => $quarterly->id,
        'lesson_number' => 1,
    ]);

    $fixtureHtml = file_get_contents(base_path('tests/fixtures/ssnet_lesson_03.html'));
    Http::fake([
        'ssnet.org/lessons/26b/*' => Http::response($fixtureHtml, 200),
    ]);

    $action = resolve(ImportQuarter::class);
    $action->handle('26b');

    // 12 new lessons should get jobs, lesson 1 already has an image
    Queue::assertPushed(GenerateLessonImageJob::class, 12);
});

// Admin shows image count

it('admin index shows image generation progress', function (): void {
    $admin = App\Models\User::factory()->admin()->create();
    $quarterly = Quarterly::factory()->active()->create(['quarter_code' => '26b']);
    Lesson::factory()->withImage()->create(['quarterly_id' => $quarterly->id, 'lesson_number' => 1]);
    Lesson::factory()->create(['quarterly_id' => $quarterly->id, 'lesson_number' => 2, 'image_path' => null]);

    $this->actingAs($admin)
        ->get('/admin/sabbath-school')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('quarterlies.0.lessons_with_images_count', 1)
            ->where('quarterlies.0.lessons_count', 2)
        );
});
