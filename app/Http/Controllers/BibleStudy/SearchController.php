<?php

declare(strict_types=1);

namespace App\Http\Controllers\BibleStudy;

use App\Actions\BibleStudy\SearchThemes;
use App\Http\Requests\BibleStudy\SearchQueryRequest;
use App\Models\BibleStudyTheme;
use Illuminate\Http\JsonResponse;

final readonly class SearchController
{
    public function show(SearchQueryRequest $request, SearchThemes $search): JsonResponse
    {
        $themes = $search->handle($request->string('q')->value());

        return response()->json([
            'themes' => $themes->map(fn (BibleStudyTheme $t): array => [
                'id' => $t->id,
                'slug' => $t->slug,
                'title' => $t->title,
                'short_description' => $t->short_description,
            ])->all(),
        ]);
    }
}
