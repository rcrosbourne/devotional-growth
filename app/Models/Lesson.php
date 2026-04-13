<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property-read int $id
 * @property-read int $quarterly_id
 * @property-read int $lesson_number
 * @property-read string $title
 * @property-read CarbonInterface $date_start
 * @property-read CarbonInterface $date_end
 * @property-read string $memory_text
 * @property-read string $memory_text_reference
 * @property-read string|null $image_path
 * @property-read string|null $image_prompt
 * @property-read bool $has_parse_warnings
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Quarterly, $this>
     */
    public function quarterly(): BelongsTo
    {
        return $this->belongsTo(Quarterly::class);
    }

    /**
     * @return HasMany<LessonDay, $this>
     */
    public function days(): HasMany
    {
        return $this->hasMany(LessonDay::class);
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
            'quarterly_id' => 'integer',
            'lesson_number' => 'integer',
            'title' => 'string',
            'date_start' => 'date',
            'date_end' => 'date',
            'memory_text' => 'string',
            'memory_text_reference' => 'string',
            'image_path' => 'string',
            'image_prompt' => 'string',
            'has_parse_warnings' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
