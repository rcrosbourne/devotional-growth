<?php

declare(strict_types=1);

use App\Services\ImagePromptBuilder;

it('leads with the content context and includes the standard constraints', function (): void {
    $builder = new ImagePromptBuilder();

    $prompt = $builder->build('For a devotional titled "Walking in Faith". Theme: Trust daily.', 'Walking in Faith');

    expect($prompt)
        ->toStartWith('For a devotional titled "Walking in Faith".')
        ->toContain('must visually reflect the content above')
        ->toContain('spiritual reflection')
        ->toContain('Do not include any text or words')
        ->toContain('Avoid overt religious iconography');
});

it('embeds the subject anchor inside the subject phrasing', function (): void {
    $builder = new ImagePromptBuilder();

    $prompts = collect(range(1, 30))->map(
        fn (): string => $builder->build('ctx', 'Grace and Mercy'),
    );

    expect($prompts->every(fn (string $p): bool => str_contains($p, 'Grace and Mercy')))->toBeTrue();
});

it('generates varied prompts across multiple calls', function (): void {
    $builder = new ImagePromptBuilder();

    $prompts = collect(range(1, 30))->map(fn (): string => $builder->build('ctx', 'Anchor'));

    expect($prompts->unique()->count())->toBeGreaterThan(3);
});

it('picks landscape about 30 percent of the time with content distribution', function (): void {
    $builder = new ImagePromptBuilder();

    $kinds = collect(range(1, 500))->map(
        fn (): string => $builder->pickSubjectKind(ImagePromptBuilder::DISTRIBUTION_CONTENT),
    );

    $landscapeCount = $kinds->filter(fn (string $k): bool => $k === 'landscape')->count();

    expect($kinds->unique()->values()->all())->toContain('landscape', 'story')
        ->and($landscapeCount)->toBeGreaterThan(100)->toBeLessThan(220);
});

it('picks landscape about 70 percent of the time with cover distribution', function (): void {
    $builder = new ImagePromptBuilder();

    $kinds = collect(range(1, 500))->map(
        fn (): string => $builder->pickSubjectKind(ImagePromptBuilder::DISTRIBUTION_COVER),
    );

    $landscapeCount = $kinds->filter(fn (string $k): bool => $k === 'landscape')->count();

    expect($kinds->unique()->values()->all())->toContain('landscape', 'story')
        ->and($landscapeCount)->toBeGreaterThan(280)->toBeLessThan(400);
});

it('landscape subject describes the scene without people', function (): void {
    $builder = new ImagePromptBuilder();

    $subject = $builder->landscapeSubject('Walking in Faith');

    expect($subject)
        ->toContain('landscape')
        ->toContain('Walking in Faith')
        ->toContain('No people');
});

it('story subject references the anchor', function (): void {
    $builder = new ImagePromptBuilder();

    $subjects = collect(range(1, 30))->map(
        fn (): string => $builder->storySubject('Grace and Mercy'),
    );

    expect($subjects->every(fn (string $s): bool => str_contains($s, 'Grace and Mercy')))->toBeTrue();
});

it('story subject includes people roughly half the time', function (): void {
    $builder = new ImagePromptBuilder();

    $subjects = collect(range(1, 500))->map(fn (): string => $builder->storySubject('Anchor'));

    $withPeople = $subjects->filter(fn (string $s): bool => str_contains($s, 'including'))->count();
    $withoutPeople = $subjects->filter(fn (string $s): bool => str_contains($s, 'without depicting people'))->count();

    expect($withPeople)->toBeGreaterThan(180)->toBeLessThan(320)
        ->and($withoutPeople)->toBeGreaterThan(180)->toBeLessThan(320);
});

it('includesPeople returns both true and false across many calls', function (): void {
    $builder = new ImagePromptBuilder();

    $results = collect(range(1, 50))->map(fn (): bool => $builder->includesPeople());

    expect($results->unique()->values()->all())->toContain(true, false);
});

it('people descriptor lands on Caribbean about 60 percent of the time', function (): void {
    $builder = new ImagePromptBuilder();

    $descriptors = collect(range(1, 500))->map(fn (): string => $builder->peopleDescriptor());

    $caribbean = $descriptors->filter(fn (string $d): bool => str_contains($d, 'Caribbean'))->count();

    expect($caribbean)->toBeGreaterThan(230)->toBeLessThan(370);
});

it('Caribbean descriptor always mentions Caribbean', function (): void {
    $builder = new ImagePromptBuilder();

    $descriptors = collect(range(1, 30))->map(fn (): string => $builder->caribbeanDescriptor());

    expect($descriptors->every(fn (string $d): bool => str_contains($d, 'Caribbean')))->toBeTrue()
        ->and($descriptors->unique()->count())->toBeGreaterThan(1);
});

it('other descriptor covers non-Caribbean identities', function (): void {
    $builder = new ImagePromptBuilder();

    $descriptors = collect(range(1, 50))->map(fn (): string => $builder->otherDescriptor());

    $joined = $descriptors->unique()->values()->implode('|');

    expect($joined)
        ->toContain('South Asian')
        ->toContain('East Asian')
        ->toContain('Caucasian')
        ->toContain('multiethnic');
});

it('selects from multiple art styles', function (): void {
    $builder = new ImagePromptBuilder();

    $styles = collect(range(1, 50))->map(fn (): string => $builder->randomStyle());

    expect($styles->unique()->count())->toBeGreaterThan(3);
});

it('subject delegates to landscape or story based on distribution', function (): void {
    $builder = new ImagePromptBuilder();

    $subjects = collect(range(1, 200))->map(
        fn (): string => $builder->subject('Anchor', ImagePromptBuilder::DISTRIBUTION_CONTENT),
    );

    $landscape = $subjects->filter(fn (string $s): bool => str_contains($s, 'landscape'))->count();
    $story = $subjects->filter(fn (string $s): bool => str_contains($s, 'narrative scene'))->count();

    expect($landscape)->toBeGreaterThan(0)
        ->and($story)->toBeGreaterThan(0);
});

it('firstSentence returns an empty string when the input is empty', function (): void {
    $builder = new ImagePromptBuilder();

    expect($builder->firstSentence(''))->toBe('');
    expect($builder->firstSentence('   '))->toBe('');
});

it('firstSentence extracts the first sentence when a terminator is present', function (): void {
    $builder = new ImagePromptBuilder();

    expect($builder->firstSentence('Trust in the Lord. Lean not on your understanding.'))
        ->toBe('Trust in the Lord.');

    expect($builder->firstSentence('Where is he? There he is.'))
        ->toBe('Where is he?');

    expect($builder->firstSentence('How great! Truly.'))
        ->toBe('How great!');
});

it('firstSentence returns the whole string when there is no terminator', function (): void {
    $builder = new ImagePromptBuilder();

    expect($builder->firstSentence('Trust in the Lord always'))
        ->toBe('Trust in the Lord always');
});

it('firstSentence truncates sentences longer than the max', function (): void {
    $builder = new ImagePromptBuilder();

    $long = str_repeat('a', 500);

    expect(mb_strlen($builder->firstSentence($long, 120)))->toBe(120);
});

it('firstSentence strips HTML tags', function (): void {
    $builder = new ImagePromptBuilder();

    expect($builder->firstSentence('<p>Hello there. Another sentence.</p>'))
        ->toBe('Hello there.');
});
