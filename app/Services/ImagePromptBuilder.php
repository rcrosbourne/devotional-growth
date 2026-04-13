<?php

declare(strict_types=1);

namespace App\Services;

final readonly class ImagePromptBuilder
{
    /**
     * Build a varied image prompt for any spiritual content in this application.
     *
     * @param  string  $context  The main content-specific portion of the prompt (title, scripture, theme description, etc.)
     */
    public function build(string $context): string
    {
        $style = $this->randomStyle();
        $subject = $this->randomSubject($context);

        return sprintf('Create an inspirational image in a %s style. ', $style)
            .$context
            .$subject
            .'The image should evoke spiritual reflection. '
            .'Do not include any text or words in the image.';
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
     * @param  string  $title  Used for story-based subjects to reference the content theme
     */
    public function randomSubject(string $title): string
    {
        return collect([
            'landscape' => $this->landscapeSubject(),
            'story' => $this->storySubject($title),
            'people' => $this->peopleSubject(),
        ])->random();
    }

    public function landscapeSubject(): string
    {
        $landscape = collect([
            'a Caribbean coastal scene with turquoise water and sandy shores at sunrise',
            'a lush tropical garden with vibrant flowers and greenery',
            'a mountainous landscape bathed in golden hour light',
            'a peaceful riverside with overhanging trees and dappled sunlight',
            'a starlit night sky over a quiet hillside',
            'a rolling countryside with wildflowers under dramatic clouds',
            'a misty forest trail with shafts of sunlight breaking through the canopy',
            'a calm lake reflecting a vivid sunset',
            'an olive grove on a hillside in warm afternoon light',
            'a desert oasis with palm trees and still water',
        ])->random();

        return sprintf('Focus on the scenery — no people. Set the scene in %s. ', $landscape);
    }

    public function storySubject(string $title): string
    {
        return sprintf(
            'Illustrate a scene or story inspired by the theme. '
            .'Depict the narrative visually rather than literally — capture the emotion and meaning of "%s". '
            .'The scene may or may not include people. ',
            $title,
        );
    }

    public function peopleSubject(): string
    {
        $setting = collect([
            'a Caribbean coastal setting',
            'a cozy indoor setting with warm lamplight and open Bibles',
            'an ancient Middle Eastern village reminiscent of biblical times',
            'a warm community gathering under a large tree',
            'a sunlit church courtyard with stone arches',
            'a tropical garden gathering space',
            'a hillside overlooking a valley',
        ])->random();

        $ethnicity = collect([
            'Black individuals of Caribbean descent',
            'a diverse mix of Black, Brown, and other ethnicities',
            'individuals of African descent',
            'a multiethnic group reflecting a Caribbean congregation',
            'individuals of various races and skin tones worshipping together',
            'South Asian and Black individuals together',
        ])->random();

        return sprintf('Set the scene in %s. ', $setting)
            .sprintf('When depicting people, feature %s. ', $ethnicity);
    }
}
