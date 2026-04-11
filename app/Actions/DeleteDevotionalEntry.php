<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DevotionalEntry;

final readonly class DeleteDevotionalEntry
{
    public function handle(DevotionalEntry $entry): void
    {
        $entry->delete();
    }
}
