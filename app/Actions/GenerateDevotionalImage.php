<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DevotionalEntry;
use App\Models\GeneratedImage;
use App\Services\ImagePromptBuilder;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;
use Laravel\Ai\Responses\ImageResponse;
use RuntimeException;

final readonly class GenerateDevotionalImage
{
    public function __construct(private ImagePromptBuilder $promptBuilder) {}

    /**
     * @param  bool  $replace  Whether to replace an existing image
     */
    public function handle(DevotionalEntry $entry, bool $replace = false): GeneratedImage
    {
        if (! $replace && $entry->generatedImage !== null) {
            return $entry->generatedImage;
        }

        $prompt = $this->buildPrompt($entry);

        $response = $this->generateImage($prompt);

        /** @var string $path */
        $path = $response->store('images/devotionals', 'public');

        $this->stripExtendedAttributes($path);

        if ($replace && $entry->generatedImage !== null) {
            Storage::disk('public')->delete($entry->generatedImage->path);
            $entry->generatedImage->delete();
        }

        return GeneratedImage::query()->create([
            'devotional_entry_id' => $entry->id,
            'path' => $path,
            'prompt' => $prompt,
        ]);
    }

    private function buildPrompt(DevotionalEntry $entry): string
    {
        $thesis = $this->promptBuilder->firstSentence($entry->body);

        $context = sprintf('For a Christian devotional titled "%s". ', $entry->title)
            .sprintf('Theme of the devotional: %s. ', $thesis);

        return $this->promptBuilder->build($context, $entry->title);
    }

    private function stripExtendedAttributes(string $path): void
    {
        $fullPath = Storage::disk('public')->path($path);

        Process::run(['xattr', '-c', $fullPath]);
    }

    private function generateImage(string $prompt): ImageResponse
    {
        $response = Image::of($prompt)
            ->square()
            ->quality('medium')
            ->timeout(120)
            ->generate();

        throw_if($response->count() === 0, RuntimeException::class, 'Image provider returned no images — prompt may have been flagged by content moderation.');

        return $response;
    }
}
