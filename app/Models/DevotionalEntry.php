<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\DevotionalEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read int $id
 * @property-read int $theme_id
 * @property-read string $title
 * @property-read string $body
 * @property-read string|null $reflection_prompts
 * @property-read string|null $adventist_insights
 * @property-read int $display_order
 * @property-read string $status
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class DevotionalEntry extends Model
{
    /** @use HasFactory<DevotionalEntryFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Theme, $this>
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * @return HasMany<ScriptureReference, $this>
     */
    public function scriptureReferences(): HasMany
    {
        return $this->hasMany(ScriptureReference::class);
    }

    /**
     * @return HasMany<DevotionalCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(DevotionalCompletion::class);
    }

    /**
     * @return HasMany<Observation, $this>
     */
    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }

    /**
     * @return HasOne<GeneratedImage, $this>
     */
    public function generatedImage(): HasOne
    {
        return $this->hasOne(GeneratedImage::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'theme_id' => 'integer',
            'title' => 'string',
            'body' => 'string',
            'reflection_prompts' => 'string',
            'adventist_insights' => 'string',
            'display_order' => 'integer',
            'status' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
