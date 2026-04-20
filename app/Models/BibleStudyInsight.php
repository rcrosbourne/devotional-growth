<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BibleStudyInsightFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int $bible_study_theme_passage_id
 * @property string $interpretation
 * @property string $application
 * @property array<int, array{book: string, chapter: int, verse_start: int, verse_end?: int, note?: string}> $cross_references
 * @property string $literary_context
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudyInsight extends Model
{
    /** @use HasFactory<BibleStudyInsightFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<BibleStudyThemePassage, $this>
     */
    public function passage(): BelongsTo
    {
        return $this->belongsTo(BibleStudyThemePassage::class, 'bible_study_theme_passage_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'bible_study_theme_passage_id' => 'integer',
            'interpretation' => 'string',
            'application' => 'string',
            'cross_references' => 'array',
            'literary_context' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
