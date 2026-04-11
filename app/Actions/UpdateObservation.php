<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Observation;

final readonly class UpdateObservation
{
    public function handle(Observation $observation, string $body): Observation
    {
        $observation->update([
            'body' => $body,
            'edited_at' => now(),
        ]);

        return $observation;
    }
}
