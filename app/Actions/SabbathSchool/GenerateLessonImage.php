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
        return sprintf(
            'Create a serene, inspirational image for a Sabbath School Bible study lesson titled "%s". ',
            $lesson->title,
        )
            .sprintf('Memory verse: "%s" (%s). ', $lesson->memory_text, $lesson->memory_text_reference)
            .'The image should be warm, peaceful, and suitable for spiritual reflection and Bible study. '
            .'Use soft, natural lighting and biblical themes. '
            .'Do not include any text or words in the image.';
    }
}
