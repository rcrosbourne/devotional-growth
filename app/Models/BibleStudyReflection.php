<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BibleStudyReflectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read int|null $bible_study_theme_id
 * @property-read string $book
 * @property-read int $chapter
 * @property-read int $verse_start
 * @property-read int|null $verse_end
 * @property-read int|null $verse_number
 * @property-read string $body
 * @property-read bool $is_shared_with_partner
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudyReflection extends Model
{
    /** @use HasFactory<BibleStudyReflectionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<BibleStudyTheme, $this>
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(BibleStudyTheme::class, 'bible_study_theme_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'bible_study_theme_id' => 'integer',
            'book' => 'string',
            'chapter' => 'integer',
            'verse_start' => 'integer',
            'verse_end' => 'integer',
            'verse_number' => 'integer',
            'body' => 'string',
            'is_shared_with_partner' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
