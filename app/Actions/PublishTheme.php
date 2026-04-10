<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContentStatus;
use App\Models\Theme;

final readonly class PublishTheme
{
    public function handle(Theme $theme): Theme
    {
        $theme->update([
            'status' => ContentStatus::Published,
        ]);

        return $theme->refresh();
    }
}
