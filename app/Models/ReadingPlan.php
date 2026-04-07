<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ReadingPlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read int $total_days
 * @property-read bool $is_default
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class ReadingPlan extends Model
{
    /** @use HasFactory<ReadingPlanFactory> */
    use HasFactory;

    /**
     * @return HasMany<ReadingPlanDay, $this>
     */
    public function days(): HasMany
    {
        return $this->hasMany(ReadingPlanDay::class);
    }

    /**
     * @return HasMany<ReadingPlanProgress, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(ReadingPlanProgress::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'name' => 'string',
            'description' => 'string',
            'total_days' => 'integer',
            'is_default' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
