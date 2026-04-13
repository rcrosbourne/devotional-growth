<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DevotionalEntry;
use App\Models\GeneratedImage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;
use Laravel\Ai\Responses\ImageResponse;

final readonly class GenerateDevotionalImage
{
    /**
     * @param  bool  $replace  Whether to replace an existing image
     */
    public function handle(DevotionalEntry $entry, bool $replace = false): GeneratedImage
    {
        $entry->loadMissing('scriptureReferences');

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
        $scriptureRefs = $entry->scriptureReferences
            ->pluck('raw_reference')
            ->implode(', ');

        $bodyExcerpt = mb_substr(strip_tags($entry->body), 0, 300);

        return sprintf('Create a serene, inspirational image for a Christian devotional titled "%s". ', $entry->title)
            .sprintf('Scripture references: %s. ', $scriptureRefs)
            .sprintf('Theme of the devotional: %s. ', $bodyExcerpt)
            .'The image should be warm, peaceful, and suitable for spiritual reflection. '
            .'When depicting people, primarily feature Black individuals of Caribbean descent, '
            .'though occasionally vary to include other races and ethnicities. '
            .'Incorporate Caribbean-relatable scenery such as tropical landscapes, coastal settings, '
            .'lush greenery, warm sunlight, and island life where appropriate. '
            .'Do not include any text or words in the image.';
    }

    private function stripExtendedAttributes(string $path): void
    {
        $fullPath = Storage::disk('public')->path($path);

        Process::run(['xattr', '-c', $fullPath]);
    }

    private function generateImage(string $prompt): ImageResponse
    {
        return Image::of($prompt)
            ->square()
            ->quality('medium')
            ->timeout(120)
            ->generate();
    }
}
