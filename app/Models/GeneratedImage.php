<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\GeneratedImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $devotional_entry_id
 * @property-read string $path
 * @property-read string $prompt
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class GeneratedImage extends Model
{
    /** @use HasFactory<GeneratedImageFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<DevotionalEntry, $this>
     */
    public function devotionalEntry(): BelongsTo
    {
        return $this->belongsTo(DevotionalEntry::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'devotional_entry_id' => 'integer',
            'path' => 'string',
            'prompt' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
