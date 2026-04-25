<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BibleStudyHistoricalContextFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $bible_study_theme_passage_id
 * @property-read string $setting
 * @property-read string $author
 * @property-read string $date_range
 * @property-read string $audience
 * @property-read string $historical_events
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudyHistoricalContext extends Model
{
    /** @use HasFactory<BibleStudyHistoricalContextFactory> */
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
            'setting' => 'string',
            'author' => 'string',
            'date_range' => 'string',
            'audience' => 'string',
            'historical_events' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
