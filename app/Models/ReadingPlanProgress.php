<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ReadingPlanProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read int $reading_plan_id
 * @property-read int $reading_plan_day_id
 * @property-read CarbonInterface $started_at
 * @property-read CarbonInterface|null $completed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class ReadingPlanProgress extends Model
{
    /** @use HasFactory<ReadingPlanProgressFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ReadingPlan, $this>
     */
    public function readingPlan(): BelongsTo
    {
        return $this->belongsTo(ReadingPlan::class);
    }

    /**
     * @return BelongsTo<ReadingPlanDay, $this>
     */
    public function readingPlanDay(): BelongsTo
    {
        return $this->belongsTo(ReadingPlanDay::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'reading_plan_id' => 'integer',
            'reading_plan_day_id' => 'integer',
            'started_at' => 'date',
            'completed_at' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
