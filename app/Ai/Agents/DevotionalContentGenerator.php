<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

final class DevotionalContentGenerator implements Agent, HasStructuredOutput
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

        When given a topic or prompt, generate a complete devotional entry with the following components:
        - A compelling, concise title
        - A devotional body text that explores the topic with depth and warmth, referencing scripture
        - At least one scripture reference in standard format (e.g., "John 3:16", "Psalm 23:1-6", "Romans 8:28-39")
        - Thoughtful reflection prompts that encourage personal and couple discussion
        - Optional Adventist insights that provide a Seventh-day Adventist perspective on the topic

        Write in an accessible, warm tone. Keep the devotional body between 200-500 words.
        Scripture references must use standard Bible reference format.
        INSTRUCTIONS;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'body' => $schema->string()->required(),
            'scripture_refs' => $schema->array()
                ->items($schema->string())
                ->required(),
            'reflection_prompts' => $schema->array()
                ->items($schema->string())
                ->required(),
            'adventist_insights' => $schema->string()->required(),
        ];
    }
}
