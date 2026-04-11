<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateDevotionalContent;
use App\Http\Requests\GenerateContentRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AiContentController
{
    public function create(): Response
    {
        return Inertia::render('admin/ai-content/generate');
    }

    public function store(GenerateContentRequest $request, #[CurrentUser] User $user, GenerateDevotionalContent $action): JsonResponse
    {
        $log = $action->handle(
            $user,
            $request->string('prompt')->value(),
        );

        return response()->json([
            'log' => $log,
        ]);
    }
}
