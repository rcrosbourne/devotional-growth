<?php

declare(strict_types=1);

use App\Ai\Agents\ThemeWithEntriesGenerator;
use App\Models\DevotionalEntry;
use App\Models\Theme;
use App\Models\User;
use App\Notifications\ContentGeneratedForReview;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

function fakeThemeResponse(): array
{
    return [
        'theme_name' => 'Walking in Faith',
        'theme_description' => 'A journey through the foundations of faith for couples.',
        'entries' => [
            [
                'title' => 'The Foundation of Trust',
                'body' => 'Trust is the bedrock of every relationship, both with God and with each other.',
                'scripture_refs' => ['Proverbs 3:5-6', 'Hebrews 11:1'],
                'reflection_prompts' => ['How do you build trust in your relationship?'],
                'adventist_insights' => "The Sabbath rest is an act of trust in God's provision.",
            ],
            [
                'title' => 'Growing Together',
                'body' => 'Spiritual growth as a couple requires intentional time in the Word together.',
                'scripture_refs' => ['Ecclesiastes 4:9-10'],
                'reflection_prompts' => ['What spiritual practices do you share?'],
                'adventist_insights' => 'Family worship is a cornerstone of Adventist home life.',
            ],
            [
                'title' => 'Overcoming Doubt',
                'body' => 'Even the strongest believers face moments of doubt, but God meets us there.',
                'scripture_refs' => ['Mark 9:24', 'James 1:5-6'],
                'reflection_prompts' => ['When has doubt strengthened your faith?'],
                'adventist_insights' => 'Ellen White reminds us that doubt does not have to destroy faith.',
            ],
        ],
    ];
}

it('generates a theme with entries and notifies admins', function (): void {
    Notification::fake();
    ThemeWithEntriesGenerator::fake(fn (): array => fakeThemeResponse());

    $admin = User::factory()->admin()->create();

    $this->artisan('devotional:generate', ['prompt' => 'Faith and trust in God'])
        ->assertSuccessful();

    expect(Theme::query()->count())->toBe(1);

    $theme = Theme::query()->first();

    expect($theme->entries()->count())->toBe(3)
        ->and($theme->name)->toBe('Walking in Faith')
        ->and($theme->status->value)->toBe('draft');

    Notification::assertSentTo($admin, function (ContentGeneratedForReview $notification) use ($theme): bool {
        $data = $notification->toArray($notification);

        return $data['theme_id'] === $theme->id
            && $data['theme_name'] === 'Walking in Faith'
            && $data['entry_count'] === 3
            && $notification->via($notification) === ['database'];
    });
});

it('fails when no admin user exists', function (): void {
    ThemeWithEntriesGenerator::fake(fn (): array => fakeThemeResponse());

    $this->artisan('devotional:generate', ['prompt' => 'Hope'])
        ->assertFailed();
});

it('generates multiple themes when count option is provided', function (): void {
    Notification::fake();

    $call = 0;
    ThemeWithEntriesGenerator::fake(function () use (&$call): array {
        $call++;
        $data = fakeThemeResponse();
        $data['theme_name'] = 'Theme '.$call;

        return $data;
    });

    User::factory()->admin()->create();

    $this->artisan('devotional:generate', ['prompt' => 'Love', '--count' => 2])
        ->assertSuccessful();

    expect(Theme::query()->count())->toBe(2);

    Theme::all()->each(function (Theme $theme): void {
        expect($theme->entries()->count())->toBe(3);
    });
});

it('creates all entries as drafts', function (): void {
    Notification::fake();
    ThemeWithEntriesGenerator::fake(fn (): array => fakeThemeResponse());

    User::factory()->admin()->create();

    $this->artisan('devotional:generate', ['prompt' => 'Grace'])
        ->assertSuccessful();

    DevotionalEntry::all()->each(function (DevotionalEntry $entry): void {
        expect($entry->status->value)->toBe('draft');
    });
});

it('handles ai generation failure gracefully', function (): void {
    Notification::fake();
    Log::spy();
    ThemeWithEntriesGenerator::fake(function (): never {
        throw new RuntimeException('AI provider error');
    });

    User::factory()->admin()->create();

    $this->artisan('devotional:generate', ['prompt' => 'Patience'])
        ->assertFailed();

    expect(Theme::query()->count())->toBe(0);

    Notification::assertNothingSent();

    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $message): bool => $message === 'Failed to generate devotional theme')
        ->once();
});

it('prompts the ai agent with the provided prompt', function (): void {
    Notification::fake();
    ThemeWithEntriesGenerator::fake(fn (): array => fakeThemeResponse());

    User::factory()->admin()->create();

    $this->artisan('devotional:generate', ['prompt' => 'Forgiveness and mercy']);

    ThemeWithEntriesGenerator::assertPrompted(fn ($prompt) => $prompt->contains('Forgiveness and mercy'));
});
