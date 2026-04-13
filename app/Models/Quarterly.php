<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\QuarterlyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read string $title
 * @property-read string $quarter_code
 * @property-read int $year
 * @property-read int $quarter_number
 * @property-read bool $is_active
 * @property-read string|null $description
 * @property-read string $source_url
 * @property-read CarbonInterface|null $last_synced_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Quarterly extends Model
{
    /** @use HasFactory<QuarterlyFactory> */
    use HasFactory;

    /**
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'title' => 'string',
            'quarter_code' => 'string',
            'year' => 'integer',
            'quarter_number' => 'integer',
            'is_active' => 'boolean',
            'description' => 'string',
            'source_url' => 'string',
            'last_synced_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
