<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Observation;

final readonly class DeleteObservation
{
    public function handle(Observation $observation): void
    {
        $observation->delete();
    }
}
