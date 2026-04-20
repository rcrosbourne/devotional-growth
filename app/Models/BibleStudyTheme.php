<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BibleStudyThemeStatus;
use Carbon\CarbonInterface;
use Database\Factories\BibleStudyThemeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read string $slug
 * @property-read string $title
 * @property-read string $short_description
 * @property-read string $long_intro
 * @property-read BibleStudyThemeStatus $status
 * @property-read int $requested_count
 * @property-read CarbonInterface|null $approved_at
 * @property-read int|null $approved_by_user_id
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudyTheme extends Model
{
    /** @use HasFactory<BibleStudyThemeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * @return HasMany<BibleStudyThemePassage, $this>
     */
    public function passages(): HasMany
    {
        return $this->hasMany(BibleStudyThemePassage::class, 'bible_study_theme_id')->orderBy('position');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'slug' => 'string',
            'title' => 'string',
            'short_description' => 'string',
            'long_intro' => 'string',
            'status' => BibleStudyThemeStatus::class,
            'requested_count' => 'integer',
            'approved_at' => 'datetime',
            'approved_by_user_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
