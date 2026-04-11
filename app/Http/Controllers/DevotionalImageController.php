<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateDevotionalImage;
use App\Models\DevotionalEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final readonly class DevotionalImageController
{
    public function store(
        Request $request,
        DevotionalEntry $entry,
        GenerateDevotionalImage $action,
    ): JsonResponse {
        $replace = $request->boolean('replace');

        try {
            $image = $action->handle($entry, $replace);

            return response()->json([
                'image' => [
                    'id' => $image->id,
                    'path' => $image->path,
                    'url' => asset('storage/'.$image->path),
                ],
            ]);
        } catch (Throwable) {
            return response()->json([
                'message' => 'Image generation is currently unavailable. Please try again later.',
            ], 503);
        }
    }
}
