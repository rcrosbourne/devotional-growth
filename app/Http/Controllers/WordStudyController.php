<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WordStudy;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class WordStudyController
{
    public function show(WordStudy $wordStudy): Response
    {
        $wordStudy->load('passages');

        return Inertia::render('bible-study/word-study', [
            'wordStudy' => $wordStudy,
        ]);
    }

    public function search(Request $request): Response
    {
        $query = $request->string('q')->trim()->toString();

        $results = [];

        if ($query !== '') {
            $results = WordStudy::query()
                ->where('strongs_number', $query)
                ->orWhere('original_word', 'like', sprintf('%%%s%%', $query))
                ->orWhere('transliteration', 'like', sprintf('%%%s%%', $query))
                ->orWhere('definition', 'like', sprintf('%%%s%%', $query))
                ->orWhereHas('passages', function (Builder $passageQuery) use ($query): void {
                    $passageQuery->where('english_word', 'like', sprintf('%%%s%%', $query));
                })
                ->with('passages')
                ->limit(50)
                ->get();
        }

        return Inertia::render('bible-study/word-study-search', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}
