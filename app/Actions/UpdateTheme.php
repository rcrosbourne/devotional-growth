<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Theme;

final readonly class UpdateTheme
{
    public function handle(Theme $theme, string $name, ?string $description = null): Theme
    {
        $theme->update([
            'name' => $name,
            'description' => $description,
        ]);

        return $theme->refresh();
    }
}
