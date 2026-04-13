<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\LessonDayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property-read int $id
 * @property-read int $lesson_id
 * @property-read int $day_position
 * @property-read string $day_name
 * @property-read string $title
 * @property-read string $body
 * @property-read array<int, string>|null $discussion_questions
 * @property-read bool $has_parse_warning
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class LessonDay extends Model
{
    /** @use HasFactory<LessonDayFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return HasMany<LessonDayScriptureReference, $this>
     */
    public function scriptureReferences(): HasMany
    {
        return $this->hasMany(LessonDayScriptureReference::class);
    }

    /**
     * @return HasMany<LessonDayCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(LessonDayCompletion::class);
    }

    /**
     * @return HasMany<LessonDayObservation, $this>
     */
    public function observations(): HasMany
    {
        return $this->hasMany(LessonDayObservation::class);
    }

    /**
     * @return MorphMany<Bookmark, $this>
     */
    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'lesson_id' => 'integer',
            'day_position' => 'integer',
            'day_name' => 'string',
            'title' => 'string',
            'body' => 'string',
            'discussion_questions' => 'array',
            'has_parse_warning' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
