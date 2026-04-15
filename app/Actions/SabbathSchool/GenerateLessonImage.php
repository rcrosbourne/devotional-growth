<?php

declare(strict_types=1);

namespace App\Actions\SabbathSchool;

use App\Models\Lesson;
use App\Services\ImagePromptBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;
use RuntimeException;
use Throwable;

final readonly class GenerateLessonImage
{
    public function __construct(private ImagePromptBuilder $promptBuilder) {}

    public function handle(Lesson $lesson, bool $replace = false): void
    {
        if (! $replace && $lesson->image_path !== null) {
            return;
        }

        $prompt = $this->buildPrompt($lesson);

        $lesson->loadMissing('quarterly');

        if ($lesson->quarterly === null) {
            Log::warning('GenerateLessonImage: Lesson has no quarterly', [
                'lesson_id' => $lesson->id,
                'lesson_title' => $lesson->title,
            ]);

            return;
        }

        try {
            // @codeCoverageIgnoreStart
            $response = Image::of($prompt)
                ->square()
                ->quality('medium')
                ->timeout(180)
                ->generate();

            throw_if($response->count() === 0, RuntimeException::class, 'Image provider returned no images — prompt may have been flagged by content moderation.');

            $directory = sprintf('images/sabbath-school/%s', $lesson->quarterly->quarter_code);

            /** @var string $path */
            $path = $response->store($directory, 'public');

            $fullPath = Storage::disk('public')->path($path);
            Process::run(['xattr', '-c', $fullPath]);

            if ($replace && $lesson->image_path !== null) {
                Storage::disk('public')->delete($lesson->image_path);
            }

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
        $context = sprintf('For a Sabbath School Bible study lesson titled "%s". ', $lesson->title)
            .sprintf('Memory verse: "%s" (%s). ', $lesson->memory_text, $lesson->memory_text_reference);

        return $this->promptBuilder->build($context, $lesson->memory_text);
    }
}
