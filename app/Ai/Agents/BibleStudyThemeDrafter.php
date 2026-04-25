<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

#[Timeout(180)]
final class BibleStudyThemeDrafter implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
        You are drafting a Bible study "theme" for a Christian devotional application.
        A theme is a topical study (e.g., "wisdom", "resilience", "forgiveness") with 8-15 associated scripture passages.

        For the given theme title, produce:
        - A URL-friendly slug.
        - A one-sentence short description.
        - A 2-3 paragraph long introduction that explains the biblical shape of the theme.
        - A list of 8-15 scripture passages across the Old and New Testament that substantively develop this theme.

        For each passage:
        - Book, chapter, verse_start, and (if multi-verse) verse_end.
        - A sequential position starting at 1.
        - Mark 5-7 passages as is_guided_path=true to form an ordered walkthrough.
        - A 1-2 sentence passage_intro explaining how this passage develops the theme.
        - Insights: interpretation (plain-sense meaning), application (practical framing), cross_references (2-5 related passages with short notes), literary_context (how this sits in the surrounding argument).
        - Historical context: setting, author, date_range, audience, historical_events.
        - Suggested word highlights: 2-5 notable Hebrew or Greek words in the passage with verse_number, display_word (as rendered in English), original_root_hint (the Hebrew or Greek word), and a short rationale.

        Draw only on mainstream biblical scholarship. Be faithful to the text. Write in a warm, accessible register suited to laypeople doing devotional study.
        INSTRUCTIONS;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        $crossReference = $schema->object([
            'book' => $schema->string()->required(),
            'chapter' => $schema->integer()->required(),
            'verse_start' => $schema->integer()->required(),
            'verse_end' => $schema->integer()->nullable()->required(),
            'note' => $schema->string()->required(),
        ]);

        $wordHighlight = $schema->object([
            'verse_number' => $schema->integer()->required(),
            'display_word' => $schema->string()->required(),
            'original_root_hint' => $schema->string()->required(),
            'rationale' => $schema->string()->required(),
        ]);

        $passage = $schema->object([
            'book' => $schema->string()->required(),
            'chapter' => $schema->integer()->required(),
            'verse_start' => $schema->integer()->required(),
            'verse_end' => $schema->integer()->nullable()->required(),
            'position' => $schema->integer()->required(),
            'is_guided_path' => $schema->boolean()->required(),
            'passage_intro' => $schema->string()->required(),
            'insights' => $schema->object([
                'interpretation' => $schema->string()->required(),
                'application' => $schema->string()->required(),
                'cross_references' => $schema->array()->items($crossReference)->required(),
                'literary_context' => $schema->string()->required(),
            ])->required(),
            'historical_context' => $schema->object([
                'setting' => $schema->string()->required(),
                'author' => $schema->string()->required(),
                'date_range' => $schema->string()->required(),
                'audience' => $schema->string()->required(),
                'historical_events' => $schema->string()->required(),
            ])->required(),
            'suggested_word_highlights' => $schema->array()->items($wordHighlight)->required(),
        ]);

        return [
            'slug' => $schema->string()->required(),
            'short_description' => $schema->string()->required(),
            'long_intro' => $schema->string()->required(),
            'passages' => $schema->array()->items($passage)->required(),
        ];
    }
}
