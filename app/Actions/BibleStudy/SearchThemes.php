<?php

declare(strict_types=1);

namespace App\Actions\BibleStudy;

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class SearchThemes
{
    /**
     * @return Collection<int, BibleStudyTheme>
     */
    public function handle(string $query): Collection
    {
        $normalized = mb_strtolower(mb_trim($query));

        if ($normalized === '') {
            return new Collection;
        }

        return BibleStudyTheme::query()
            ->where('status', BibleStudyThemeStatus::Approved)
            ->where(function (Builder $q) use ($normalized): void {
                $q->whereRaw('LOWER(slug) = ?', [$normalized])
                    ->orWhereRaw('LOWER(title) = ?', [$normalized]);
            })
            ->get();
    }
}
