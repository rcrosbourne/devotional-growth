<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Theme;

final readonly class DeleteTheme
{
    public function handle(Theme $theme): void
    {
        $theme->delete();
    }
}
