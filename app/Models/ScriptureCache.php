<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ScriptureCacheFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property-read string $book
 * @property-read int $chapter
 * @property-read int $verse_start
 * @property-read int|null $verse_end
 * @property-read string $bible_version
 * @property-read string $text
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class ScriptureCache extends Model
{
    /** @use HasFactory<ScriptureCacheFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'book' => 'string',
            'chapter' => 'integer',
            'verse_start' => 'integer',
            'verse_end' => 'integer',
            'bible_version' => 'string',
            'text' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
