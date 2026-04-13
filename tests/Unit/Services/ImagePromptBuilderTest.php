<?php

declare(strict_types=1);

use App\Services\ImagePromptBuilder;

it('builds a prompt containing the context and common elements', function (): void {
    $builder = new ImagePromptBuilder();

    $prompt = $builder->build('For a lesson titled "Walking in Faith". ');

    expect($prompt)
        ->toContain('Walking in Faith')
        ->toContain('spiritual reflection')
        ->toContain('Do not include any text or words');
});

it('generates varied prompts across multiple calls', function (): void {
    $builder = new ImagePromptBuilder();

    $prompts = collect(range(1, 20))->map(fn (): string => $builder->build('Test context. '));

    expect($prompts->unique()->count())->toBeGreaterThan(1);
});

it('returns a landscape subject with no people instruction', function (): void {
    $builder = new ImagePromptBuilder();

    $subject = $builder->landscapeSubject();

    expect($subject)
        ->toContain('no people')
        ->toContain('Set the scene in');
});

it('returns a story subject referencing the title', function (): void {
    $builder = new ImagePromptBuilder();

    $subject = $builder->storySubject('Grace and Mercy');

    expect($subject)
        ->toContain('Grace and Mercy')
        ->toContain('emotion and meaning');
});

it('returns a people subject with setting and ethnicity', function (): void {
    $builder = new ImagePromptBuilder();

    $subject = $builder->peopleSubject();

    expect($subject)
        ->toContain('Set the scene in')
        ->toContain('When depicting people, feature');
});

it('selects from multiple art styles', function (): void {
    $builder = new ImagePromptBuilder();

    $styles = collect(range(1, 30))->map(fn (): string => $builder->randomStyle());

    expect($styles->unique()->count())->toBeGreaterThan(3);
});
