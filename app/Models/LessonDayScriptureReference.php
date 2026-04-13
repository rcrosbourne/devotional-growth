<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\LessonDayScriptureReferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $lesson_day_id
 * @property-read string $book
 * @property-read int $chapter
 * @property-read int $verse_start
 * @property-read int|null $verse_end
 * @property-read string $raw_reference
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class LessonDayScriptureReference extends Model
{
    /** @use HasFactory<LessonDayScriptureReferenceFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<LessonDay, $this>
     */
    public function lessonDay(): BelongsTo
    {
        return $this->belongsTo(LessonDay::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'lesson_day_id' => 'integer',
            'book' => 'string',
            'chapter' => 'integer',
            'verse_start' => 'integer',
            'verse_end' => 'integer',
            'raw_reference' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
