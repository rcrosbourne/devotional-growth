<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContentStatus;
use App\Models\DevotionalEntry;
use Illuminate\Support\Facades\DB;

final readonly class UnpublishDevotionalEntry
{
    public function handle(DevotionalEntry $entry): DevotionalEntry
    {
        return DB::transaction(function () use ($entry): DevotionalEntry {
            $entry->update([
                'status' => ContentStatus::Draft,
            ]);

            $theme = $entry->theme;

            if ($theme !== null && $theme->status === ContentStatus::Published) {
                $remainingPublished = $theme->entries()
                    ->where('status', ContentStatus::Published)
                    ->count();

                if ($remainingPublished === 0) {
                    $theme->update([
                        'status' => ContentStatus::Draft,
                    ]);
                }
            }

            return $entry->refresh();
        });
    }
}
