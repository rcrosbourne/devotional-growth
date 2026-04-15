<?php

declare(strict_types=1);

namespace App\Services;

final readonly class ImagePromptBuilder
{
    public const string DISTRIBUTION_CONTENT = 'content';

    public const string DISTRIBUTION_COVER = 'cover';

    /**
     * Build a content-led image prompt. Content leads the prompt; the subject
     * (landscape or narrative story) is derived from the subjectAnchor so the
     * image visually reflects the source content.
     *
     * @param  string  $contentContext  Prose content block placed at the top of the prompt (title + short excerpt).
     * @param  string  $subjectAnchor  Short string the subject scene should reflect — usually the title, or for lessons the memory verse text.
     * @param  string  $distribution  self::DISTRIBUTION_CONTENT (30% landscape / 70% story) or self::DISTRIBUTION_COVER (70% / 30%).
     */
    public function build(
        string $contentContext,
        string $subjectAnchor,
        string $distribution = self::DISTRIBUTION_CONTENT,
    ): string {
        $subject = $this->subject($subjectAnchor, $distribution);
        $style = $this->randomStyle();

        return $contentContext
            .'The scene, mood, and symbolism must visually reflect the content above. Avoid generic imagery. '
            .$subject
            .sprintf('Render the image in a %s style. ', $style)
            .'The image should evoke spiritual reflection. '
            .'Do not include any text or words in the image. '
            .'Avoid overt religious iconography unless the theme calls for it.';
    }

    public function subject(string $subjectAnchor, string $distribution = self::DISTRIBUTION_CONTENT): string
    {
        return $this->pickSubjectKind($distribution) === 'landscape'
            ? $this->landscapeSubject($subjectAnchor)
            : $this->storySubject($subjectAnchor);
    }

    public function pickSubjectKind(string $distribution): string
    {
        $landscapeWeight = $distribution === self::DISTRIBUTION_COVER ? 70 : 30;

        return random_int(1, 100) <= $landscapeWeight ? 'landscape' : 'story';
    }

    public function landscapeSubject(string $subjectAnchor): string
    {
        return sprintf(
            'Subject: a landscape or atmospheric scene that fits the mood and meaning of "%s". '
            .'No people. Let the setting — natural light, weather, environment — carry the theme. ',
            $subjectAnchor,
        );
    }

    public function storySubject(string $subjectAnchor): string
    {
        if ($this->includesPeople()) {
            return sprintf(
                'Subject: a narrative scene that captures the emotion and meaning of "%s", including %s. ',
                $subjectAnchor,
                $this->peopleDescriptor(),
            );
        }

        return sprintf(
            'Subject: a narrative scene that captures the emotion and meaning of "%s", without depicting people. '
            .'Use symbolic objects, settings, or implied human presence. ',
            $subjectAnchor,
        );
    }

    public function includesPeople(): bool
    {
        return random_int(1, 2) === 1;
    }

    public function peopleDescriptor(): string
    {
        return random_int(1, 100) <= 60
            ? $this->caribbeanDescriptor()
            : $this->otherDescriptor();
    }

    public function caribbeanDescriptor(): string
    {
        return collect([
            'a person of Caribbean descent',
            'a small group of Caribbean people',
            'a multiethnic Caribbean congregation',
            'individuals of African-Caribbean descent',
            'an Afro-Caribbean family in an everyday moment',
        ])->random();
    }

    public function otherDescriptor(): string
    {
        return collect([
            'a person of South Asian descent',
            'a person of East Asian descent',
            'a person of Caucasian descent',
            'a multiethnic group of people from diverse backgrounds',
        ])->random();
    }

    public function randomStyle(): string
    {
        return collect([
            'watercolor painting',
            'oil painting with rich textures',
            'soft digital illustration',
            'warm photorealistic style',
            'impressionist painting',
            'contemporary mixed-media art',
            'stained glass art',
            'pencil sketch with color washes',
            'stylized flat illustration',
        ])->random();
    }

    /**
     * Extract the first sentence (or a short prefix) from a block of text.
     * Used by callers to build a dense, content-led prompt excerpt.
     */
    public function firstSentence(string $text, int $maxChars = 200): string
    {
        $cleaned = mb_trim(strip_tags($text));

        if ($cleaned === '') {
            return '';
        }

        $sentence = preg_match('/^(.+?[.!?])(?:\s|$)/u', $cleaned, $matches) === 1 ? $matches[1] : $cleaned;

        return mb_substr($sentence, 0, $maxChars);
    }
}
