<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContentStatus;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;

final readonly class UnpublishTheme
{
    public function handle(Theme $theme): Theme
    {
        return DB::transaction(function () use ($theme): Theme {
            $theme->update([
                'status' => ContentStatus::Draft,
            ]);

            $theme->entries()->update([
                'status' => ContentStatus::Draft,
            ]);

            return $theme->refresh();
        });
    }
}
