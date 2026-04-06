<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ScriptureReferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $devotional_entry_id
 * @property-read string $book
 * @property-read int $chapter
 * @property-read int $verse_start
 * @property-read int|null $verse_end
 * @property-read string $raw_reference
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class ScriptureReference extends Model
{
    /** @use HasFactory<ScriptureReferenceFactory> */
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
            'book' => 'string',
            'chapter' => 'integer',
            'verse_start' => 'integer',
            'verse_end' => 'integer',
            'raw_reference' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
