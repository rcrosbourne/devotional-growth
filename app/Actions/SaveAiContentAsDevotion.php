<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AiGenerationLog;
use App\Models\DevotionalEntry;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class SaveAiContentAsDevotion
{
    public function __construct(
        private CreateTheme $createTheme,
        private CreateDevotionalEntry $createDevotionalEntry,
    ) {}

    /**
     * @param  array{theme_id?: int, new_theme_name?: string, new_theme_description?: string}  $themeData
     */
    public function handle(User $admin, AiGenerationLog $log, array $themeData): DevotionalEntry
    {
        return DB::transaction(function () use ($admin, $log, $themeData): DevotionalEntry {
            if (isset($themeData['theme_id'])) {
                $theme = Theme::query()->findOrFail($themeData['theme_id']);
            } elseif (isset($themeData['new_theme_name'])) {
                $theme = $this->createTheme->handle(
                    $admin,
                    $themeData['new_theme_name'],
                    $themeData['new_theme_description'] ?? null,
                );
            } else {
                throw new InvalidArgumentException('Either theme_id or new_theme_name is required.');
            }

            /** @var array{title: string, body: string, scripture_refs: array<string>, reflection_prompts: array<string>, adventist_insights: string} $content */
            $content = $log->generated_content;

            $entry = $this->createDevotionalEntry->handle($theme, [
                'title' => $content['title'],
                'body' => $content['body'],
                'scripture_references' => $content['scripture_refs'],
                'reflection_prompts' => implode("\n", $content['reflection_prompts']),
                'adventist_insights' => $content['adventist_insights'],
            ]);

            $log->update(['devotional_entry_id' => $entry->id]);

            return $entry;
        });
    }
}
