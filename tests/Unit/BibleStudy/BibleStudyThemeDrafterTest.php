<?php

declare(strict_types=1);

use App\Ai\Agents\BibleStudyThemeDrafter;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

it('declares the theme-draft structured output schema', function (): void {
    $agent = new BibleStudyThemeDrafter;
    $schema = $agent->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKeys(['slug', 'short_description', 'long_intro', 'passages']);
});

it('provides instructions', function (): void {
    $agent = new BibleStudyThemeDrafter;

    expect($agent->instructions())->toBeString()->not->toBe('');
});
