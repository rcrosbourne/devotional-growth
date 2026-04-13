<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use Carbon\CarbonInterface;
use Database\Factories\ThemeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read int $created_by
 * @property-read string $name
 * @property-read string|null $description
 * @property-read string|null $image_path
 * @property-read ContentStatus $status
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read int $entries_count
 * @property-read int $completed_entries_count
 */
final class Theme extends Model
{
    /** @use HasFactory<ThemeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<DevotionalEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(DevotionalEntry::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'created_by' => 'integer',
            'name' => 'string',
            'description' => 'string',
            'image_path' => 'string',
            'status' => ContentStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
