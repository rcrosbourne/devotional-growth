<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Theme;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;
use Laravel\Ai\Responses\ImageResponse;

final readonly class GenerateThemeImage
{
    public function handle(Theme $theme, bool $replace = false): Theme
    {
        if (! $replace && $theme->image_path !== null) {
            return $theme;
        }

        $prompt = $this->buildPrompt($theme);

        $response = $this->generateImage($prompt);

        /** @var string $path */
        $path = $response->store('images/themes', 'public');

        $this->stripExtendedAttributes($path);

        if ($replace && $theme->image_path !== null) {
            Storage::disk('public')->delete($theme->image_path);
        }

        $theme->update(['image_path' => $path]);

        return $theme->refresh();
    }

    private function buildPrompt(Theme $theme): string
    {
        $description = $theme->description !== null
            ? mb_substr($theme->description, 0, 300)
            : $theme->name;

        return sprintf('Create a beautiful, evocative cover image for a Christian devotional theme titled "%s". ', $theme->name)
            .sprintf('Theme description: %s. ', $description)
            .'The image should be atmospheric, warm, and contemplative — suitable as a cover for a spiritual journal or devotional series. '
            .'Use rich tones, natural imagery, and a sense of peaceful reverence. '
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
