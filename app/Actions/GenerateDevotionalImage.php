<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DevotionalEntry;
use App\Models\GeneratedImage;
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

        $path = $response->store('images/devotionals', 'public');

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

        return "Create a serene, inspirational image for a Christian devotional titled \"{$entry->title}\". "
            ."Scripture references: {$scriptureRefs}. "
            ."Theme of the devotional: {$bodyExcerpt}. "
            .'The image should be warm, peaceful, and suitable for spiritual reflection. '
            .'Do not include any text or words in the image.';
    }

    private function generateImage(string $prompt): ImageResponse
    {
        return Image::of($prompt)
            ->square()
            ->quality('medium')
            ->timeout(60)
            ->generate();
    }
}
