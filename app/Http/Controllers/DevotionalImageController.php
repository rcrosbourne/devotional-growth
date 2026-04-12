<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateDevotionalImage;
use App\Models\DevotionalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class DevotionalImageController
{
    public function store(
        Request $request,
        DevotionalEntry $entry,
        GenerateDevotionalImage $action,
    ): RedirectResponse {
        set_time_limit(120);

        $replace = $request->boolean('replace');

        try {
            $action->handle($entry, $replace);
        } catch (Throwable $throwable) {
            Log::error('Image generation failed', ['error' => $throwable->getMessage(), 'entry_id' => $entry->id]);

            return back()->with('error', 'Image generation is currently unavailable. Please try again later.');
        }

        return back();
    }
}
