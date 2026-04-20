<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BibleStudyThemePassageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read int $id
 * @property-read int $bible_study_theme_id
 * @property-read int $position
 * @property-read bool $is_guided_path
 * @property-read string $book
 * @property-read int $chapter
 * @property-read int $verse_start
 * @property-read int|null $verse_end
 * @property-read string|null $passage_intro
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudyThemePassage extends Model
{
    /** @use HasFactory<BibleStudyThemePassageFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<BibleStudyTheme, $this>
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(BibleStudyTheme::class, 'bible_study_theme_id');
    }

    /**
     * @return HasOne<BibleStudyInsight, $this>
     */
    public function insight(): HasOne
    {
        return $this->hasOne(BibleStudyInsight::class, 'bible_study_theme_passage_id');
    }

    /**
     * @return HasOne<BibleStudyHistoricalContext, $this>
     */
    public function historicalContext(): HasOne
    {
        return $this->hasOne(BibleStudyHistoricalContext::class, 'bible_study_theme_passage_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'bible_study_theme_id' => 'integer',
            'position' => 'integer',
            'is_guided_path' => 'boolean',
            'book' => 'string',
            'chapter' => 'integer',
            'verse_start' => 'integer',
            'verse_end' => 'integer',
            'passage_intro' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
