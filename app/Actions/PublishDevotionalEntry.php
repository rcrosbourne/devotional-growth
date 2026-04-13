<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContentStatus;
use App\Models\DevotionalEntry;
use Illuminate\Support\Facades\DB;

final readonly class PublishDevotionalEntry
{
    public function handle(DevotionalEntry $entry): DevotionalEntry
    {
        return DB::transaction(function () use ($entry): DevotionalEntry {
            $entry->update([
                'status' => ContentStatus::Published,
            ]);

            $theme = $entry->theme;

            if ($theme !== null && $theme->status !== ContentStatus::Published) {
                $theme->update([
                    'status' => ContentStatus::Published,
                ]);
            }

            return $entry->refresh();
        });
    }
}
