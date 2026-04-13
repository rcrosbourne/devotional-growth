<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\GenerateDevotionalImageJob;
use App\Models\DevotionalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class DevotionalImageController
{
    public function store(
        Request $request,
        DevotionalEntry $entry,
    ): RedirectResponse {
        $replace = $request->boolean('replace');

        if (! $replace && $entry->generatedImage !== null) {
            return back();
        }

        dispatch(new GenerateDevotionalImageJob($entry, $replace));

        return back();
    }
}
