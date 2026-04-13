<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\ThemeWithEntriesGenerator;
use App\Models\Theme;
use App\Models\User;
use App\Notifications\ContentGeneratedForReview;
use Illuminate\Support\Facades\Notification;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

final readonly class GenerateThemeWithEntries
{
    public function __construct(
        private CreateTheme $createTheme,
        private CreateDevotionalEntry $createDevotionalEntry,
    ) {}

    /**
     * @return array{theme: Theme, entry_count: int}
     *
     * @throws Throwable
     */
    public function handle(User $admin, string $prompt): array
    {
        /** @var StructuredAgentResponse $response */
        $response = (new ThemeWithEntriesGenerator)->prompt($prompt);

        /** @var array{theme_name: string, theme_description: string, entries: array<array{title: string, body: string, scripture_refs: array<string>, reflection_prompts: array<string>, adventist_insights: string}>} $content */
        $content = $response->toArray();

        $theme = $this->createTheme->handle(
            $admin,
            $content['theme_name'],
            $content['theme_description'],
        );

        $entries = $content['entries'];

        foreach ($entries as $entry) {
            $this->createDevotionalEntry->handle($theme, [
                'title' => $entry['title'],
                'body' => $entry['body'],
                'scripture_references' => $entry['scripture_refs'],
                'reflection_prompts' => implode("\n", $entry['reflection_prompts']),
                'adventist_insights' => $entry['adventist_insights'],
            ]);
        }

        $admins = User::query()->where('is_admin', true)->get();

        Notification::send($admins, new ContentGeneratedForReview($theme, count($entries)));

        return [
            'theme' => $theme->load('entries'),
            'entry_count' => count($entries),
        ];
    }
}
