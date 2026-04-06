<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ReadingPlanDayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read int $reading_plan_id
 * @property-read int $day_number
 * @property-read array<int, string> $passages
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class ReadingPlanDay extends Model
{
    /** @use HasFactory<ReadingPlanDayFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ReadingPlan, $this>
     */
    public function readingPlan(): BelongsTo
    {
        return $this->belongsTo(ReadingPlan::class);
    }

    /**
     * @return HasMany<ReadingPlanProgress, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(ReadingPlanProgress::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'reading_plan_id' => 'integer',
            'day_number' => 'integer',
            'passages' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
