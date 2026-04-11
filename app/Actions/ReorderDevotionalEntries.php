<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DevotionalEntry;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;

final readonly class ReorderDevotionalEntries
{
    /**
     * @param  array<int>  $orderedIds
     */
    public function handle(Theme $theme, array $orderedIds): void
    {
        DB::transaction(function () use ($theme, $orderedIds): void {
            foreach ($orderedIds as $position => $id) {
                DevotionalEntry::query()
                    ->where('id', $id)
                    ->where('theme_id', $theme->id)
                    ->update(['display_order' => $position]);
            }
        });
    }
}
