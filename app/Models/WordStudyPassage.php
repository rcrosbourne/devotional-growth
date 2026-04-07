<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\WordStudyPassageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $word_study_id
 * @property-read string $book
 * @property-read int $chapter
 * @property-read int $verse
 * @property-read string $english_word
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class WordStudyPassage extends Model
{
    /** @use HasFactory<WordStudyPassageFactory> */
    use HasFactory;

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
            'word_study_id' => 'integer',
            'book' => 'string',
            'chapter' => 'integer',
            'verse' => 'integer',
            'english_word' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
