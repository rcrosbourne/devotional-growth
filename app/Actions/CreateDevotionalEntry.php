<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContentStatus;
use App\Models\DevotionalEntry;
use App\Models\Theme;
use App\Services\ScriptureReferenceParser;
use Illuminate\Support\Facades\DB;

final readonly class CreateDevotionalEntry
{
    public function __construct(private ScriptureReferenceParser $parser) {}

    /**
     * @param  array{title: string, body: string, reflection_prompts?: string|null, adventist_insights?: string|null, scripture_references: array<string>}  $data
     */
    public function handle(Theme $theme, array $data): DevotionalEntry
    {
        return DB::transaction(function () use ($theme, $data): DevotionalEntry {
            /** @var int $maxOrder */
            $maxOrder = $theme->entries()->max('display_order') ?? -1;

            $entry = $theme->entries()->create([
                'title' => $data['title'],
                'body' => $data['body'],
                'reflection_prompts' => $data['reflection_prompts'] ?? null,
                'adventist_insights' => $data['adventist_insights'] ?? null,
                'display_order' => $maxOrder + 1,
                'status' => ContentStatus::Draft,
            ]);

            foreach ($data['scripture_references'] as $rawReference) {
                $parsed = $this->parser->parse($rawReference);

                $entry->scriptureReferences()->create([
                    'book' => $parsed->book,
                    'chapter' => $parsed->chapter,
                    'verse_start' => $parsed->verse_start,
                    'verse_end' => $parsed->verse_end,
                    'raw_reference' => $parsed->raw_reference,
                ]);
            }

            return $entry->load('scriptureReferences');
        });
    }
}
