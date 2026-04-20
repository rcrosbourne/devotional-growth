<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BibleStudyWordHighlightFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $bible_study_theme_passage_id
 * @property-read int $word_study_id
 * @property-read int $verse_number
 * @property-read int $word_index_in_verse
 * @property-read string $display_word
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudyWordHighlight extends Model
{
    /** @use HasFactory<BibleStudyWordHighlightFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<BibleStudyThemePassage, $this>
     */
    public function passage(): BelongsTo
    {
        return $this->belongsTo(BibleStudyThemePassage::class, 'bible_study_theme_passage_id');
    }

    /**
     * @return BelongsTo<WordStudy, $this>
     */
    public function wordStudy(): BelongsTo
    {
        return $this->belongsTo(WordStudy::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'bible_study_theme_passage_id' => 'integer',
            'word_study_id' => 'integer',
            'verse_number' => 'integer',
            'word_index_in_verse' => 'integer',
            'display_word' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
