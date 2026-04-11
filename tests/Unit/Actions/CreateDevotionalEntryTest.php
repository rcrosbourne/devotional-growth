<?php

declare(strict_types=1);

use App\Actions\CreateDevotionalEntry;
use App\Enums\ContentStatus;
use App\Models\DevotionalEntry;
use App\Models\Theme;

it('creates a devotional entry with draft status', function (): void {
    $theme = Theme::factory()->create();
    $action = resolve(CreateDevotionalEntry::class);

    $entry = $action->handle($theme, [
        'title' => 'Walking in Faith',
        'body' => 'A devotional about walking in faith.',
        'reflection_prompts' => 'What does faith mean to you?',
        'adventist_insights' => 'Adventist perspective on faith.',
        'scripture_references' => ['John 3:16'],
    ]);

    expect($entry)->toBeInstanceOf(DevotionalEntry::class)
        ->and($entry->title)->toBe('Walking in Faith')
        ->and($entry->body)->toBe('A devotional about walking in faith.')
        ->and($entry->reflection_prompts)->toBe('What does faith mean to you?')
        ->and($entry->adventist_insights)->toBe('Adventist perspective on faith.')
        ->and($entry->status)->toBe(ContentStatus::Draft)
        ->and($entry->theme_id)->toBe($theme->id);
});

it('creates scripture references for the entry', function (): void {
    $theme = Theme::factory()->create();
    $action = resolve(CreateDevotionalEntry::class);

    $entry = $action->handle($theme, [
        'title' => 'Grace',
        'body' => 'A devotional about grace.',
        'scripture_references' => ['John 3:16', 'Romans 8:28-39'],
    ]);

    expect($entry->scriptureReferences)->toHaveCount(2);

    $first = $entry->scriptureReferences->first();
    expect($first->book)->toBe('John')
        ->and($first->chapter)->toBe(3)
        ->and($first->verse_start)->toBe(16)
        ->and($first->verse_end)->toBeNull();

    $second = $entry->scriptureReferences->last();
    expect($second->book)->toBe('Romans')
        ->and($second->chapter)->toBe(8)
        ->and($second->verse_start)->toBe(28)
        ->and($second->verse_end)->toBe(39);
});

it('sets display_order automatically', function (): void {
    $theme = Theme::factory()->create();
    $action = resolve(CreateDevotionalEntry::class);

    $entry1 = $action->handle($theme, [
        'title' => 'First',
        'body' => 'First entry.',
        'scripture_references' => ['John 1:1'],
    ]);

    $entry2 = $action->handle($theme, [
        'title' => 'Second',
        'body' => 'Second entry.',
        'scripture_references' => ['John 1:2'],
    ]);

    expect($entry1->display_order)->toBe(0)
        ->and($entry2->display_order)->toBe(1);
});

it('creates entry with null optional fields', function (): void {
    $theme = Theme::factory()->create();
    $action = resolve(CreateDevotionalEntry::class);

    $entry = $action->handle($theme, [
        'title' => 'Simple',
        'body' => 'A simple devotional.',
        'scripture_references' => ['Psalm 23:1'],
    ]);

    expect($entry->reflection_prompts)->toBeNull()
        ->and($entry->adventist_insights)->toBeNull();
});
