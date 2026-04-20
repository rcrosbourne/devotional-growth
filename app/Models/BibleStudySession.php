<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BibleStudySessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read int|null $bible_study_theme_id
 * @property-read string $current_book
 * @property-read int $current_chapter
 * @property-read int $current_verse_start
 * @property-read int|null $current_verse_end
 * @property-read CarbonInterface $started_at
 * @property-read CarbonInterface $last_accessed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudySession extends Model
{
    /** @use HasFactory<BibleStudySessionFactory> */
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
            'current_book' => 'string',
            'current_chapter' => 'integer',
            'current_verse_start' => 'integer',
            'current_verse_end' => 'integer',
            'started_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
