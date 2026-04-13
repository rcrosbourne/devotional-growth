<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Theme;
use App\Services\ImagePromptBuilder;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;
use Laravel\Ai\Responses\ImageResponse;

final readonly class GenerateThemeImage
{
    public function __construct(private ImagePromptBuilder $promptBuilder) {}

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

        $context = sprintf('For a Christian devotional theme cover titled "%s". ', $theme->name)
            .sprintf('Theme description: %s. ', $description)
            .'The image should be atmospheric and contemplative — suitable as a cover for a spiritual journal or devotional series. ';

        return $this->promptBuilder->build($context);
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
