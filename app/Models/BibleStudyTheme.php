<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BibleStudyThemeStatus;
use Carbon\CarbonInterface;
use Database\Factories\BibleStudyThemeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property string $slug
 * @property string $title
 * @property string $short_description
 * @property string $long_intro
 * @property BibleStudyThemeStatus $status
 * @property int $requested_count
 * @property CarbonInterface|null $approved_at
 * @property int|null $approved_by_user_id
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class BibleStudyTheme extends Model
{
    /** @use HasFactory<BibleStudyThemeFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'status' => BibleStudyThemeStatus::class,
            'requested_count' => 'integer',
            'approved_at' => 'datetime',
            'approved_by_user_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
