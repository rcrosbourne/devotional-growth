<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateDevotionalContent;
use App\Actions\GenerateDevotionalImage;
use App\Actions\SaveAiContentAsDevotion;
use App\Http\Requests\GenerateContentRequest;
use App\Http\Requests\SaveAiContentRequest;
use App\Models\AiGenerationLog;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final readonly class AiContentController
{
    public function create(): Response
    {
        return Inertia::render('admin/ai-content/generate', [
            'themes' => Theme::query()->orderBy('name')->get(['id', 'name']),
        ]);
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

    public function save(
        SaveAiContentRequest $request,
        #[CurrentUser] User $user,
        SaveAiContentAsDevotion $saveAction,
        GenerateDevotionalImage $imageAction,
    ): JsonResponse {
        set_time_limit(180);

        $log = AiGenerationLog::query()->findOrFail($request->integer('ai_generation_log_id'));

        /** @var array{theme_id?: int, new_theme_name?: string, new_theme_description?: string} $themeData */
        $themeData = $request->only([
            'theme_id',
            'new_theme_name',
            'new_theme_description',
        ]);

        $entry = $saveAction->handle($user, $log, $themeData);

        try {
            $imageAction->handle($entry);
        } catch (Throwable) {
            // Image generation is non-critical; the entry is already saved.
        }

        $entry->load(['theme', 'scriptureReferences', 'generatedImage']);

        return response()->json([
            'entry' => $entry,
        ]);
    }
}
