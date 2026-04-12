<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContentStatus;
use App\Models\Theme;
use Illuminate\Validation\ValidationException;

final readonly class PublishTheme
{
    public function handle(Theme $theme): Theme
    {
        $publishedEntryCount = $theme->entries()
            ->where('status', ContentStatus::Published)
            ->count();

        if ($publishedEntryCount === 0) {
            throw ValidationException::withMessages([
                'theme' => 'A theme cannot be published without at least one published entry.',
            ]);
        }

        $theme->update([
            'status' => ContentStatus::Published,
        ]);

        return $theme->refresh();
    }
}
