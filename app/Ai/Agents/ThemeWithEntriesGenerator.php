<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

#[Timeout(180)]
final class ThemeWithEntriesGenerator implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
        You are a devotional content writer for a Christian devotional application.
        Your audience is a couple doing daily devotions together, primarily using the King James Version (KJV) of the Bible.
        Seventh-day Adventist beliefs and interpretations of scripture may be lightly woven into devotional content without heavy doctrinal emphasis.

        When given a topic or prompt, generate a complete devotional THEME with multiple devotional entries.

        You must generate:
        1. A theme name (short, compelling, 2-5 words)
        2. A theme description (1-2 sentences explaining the theme)
        3. Between 3 and 5 devotional entries that explore different aspects of the theme

        Each devotional entry must include:
        - A compelling, concise title
        - A devotional body text that explores the topic with depth and warmth, referencing scripture (200-500 words)
        - At least one scripture reference in standard format (e.g., "John 3:16", "Psalm 23:1-6", "Romans 8:28-39")
        - Thoughtful reflection prompts that encourage personal and couple discussion
        - Optional Adventist insights that provide a Seventh-day Adventist perspective on the topic

        The entries should progress logically through the theme, building on each other to create a cohesive devotional journey.
        Write in an accessible, warm tone. Scripture references must use standard Bible reference format.
        INSTRUCTIONS;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'theme_name' => $schema->string()->required(),
            'theme_description' => $schema->string()->required(),
            'entries' => $schema->array()
                ->min(3)
                ->max(5)
                ->items($schema->object([
                    'title' => $schema->string()->required(),
                    'body' => $schema->string()->required(),
                    'scripture_refs' => $schema->array()
                        ->items($schema->string())
                        ->required(),
                    'reflection_prompts' => $schema->array()
                        ->items($schema->string())
                        ->required(),
                    'adventist_insights' => $schema->string()->required(),
                ]))
                ->required(),
        ];
    }
}
