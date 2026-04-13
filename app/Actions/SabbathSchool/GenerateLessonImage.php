<?php

declare(strict_types=1);

namespace App\Actions\SabbathSchool;

use App\Models\Lesson;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;
use Throwable;

final readonly class GenerateLessonImage
{
    public function handle(Lesson $lesson): void
    {
        if ($lesson->image_path !== null) {
            return;
        }

        $prompt = $this->buildPrompt($lesson);

        try {
            // @codeCoverageIgnoreStart
            $response = Image::of($prompt)
                ->square()
                ->quality('medium')
                ->timeout(120)
                ->generate();

            $lesson->loadMissing('quarterly');

            /** @var \App\Models\Quarterly $quarterly */
            $quarterly = $lesson->quarterly;
            $directory = sprintf('images/sabbath-school/%s', $quarterly->quarter_code);

            /** @var string $path */
            $path = $response->store($directory, 'public');

            $fullPath = Storage::disk('public')->path($path);
            Process::run(['xattr', '-c', $fullPath]);

            $lesson->update([
                'image_path' => $path,
                'image_prompt' => $prompt,
            ]);
            // @codeCoverageIgnoreEnd
        } catch (Throwable $throwable) {
            Log::warning('GenerateLessonImage: Failed to generate image', [
                'lesson_id' => $lesson->id,
                'lesson_title' => $lesson->title,
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    private function buildPrompt(Lesson $lesson): string
    {
        $style = collect([
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

        $subject = collect([
            'landscape' => $this->buildLandscapeSubject(),
            'story' => $this->buildStorySubject($lesson),
            'people' => $this->buildPeopleSubject(),
        ])->random();

        return sprintf(
            'Create an inspirational image in a %s style for a Sabbath School Bible study lesson titled "%s". ',
            $style,
            $lesson->title,
        )
            .sprintf('Memory verse: "%s" (%s). ', $lesson->memory_text, $lesson->memory_text_reference)
            .$subject
            .'The image should evoke spiritual reflection and Bible study. '
            .'Do not include any text or words in the image.';
    }

    private function buildLandscapeSubject(): string
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

    private function buildStorySubject(Lesson $lesson): string
    {
        return sprintf(
            'Illustrate a scene or story inspired by the lesson theme and memory verse. '
            .'Depict the narrative visually rather than literally — capture the emotion and meaning of "%s". '
            .'The scene may or may not include people. ',
            $lesson->title,
        );
    }

    private function buildPeopleSubject(): string
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
