<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContentStatus;
use App\Models\DevotionalEntry;

final readonly class PublishDevotionalEntry
{
    public function handle(DevotionalEntry $entry): DevotionalEntry
    {
        $entry->update([
            'status' => ContentStatus::Published,
        ]);

        return $entry->refresh();
    }
}
