<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\WordStudyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read string $original_word
 * @property-read string $transliteration
 * @property-read string $language
 * @property-read string $definition
 * @property-read string $strongs_number
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class WordStudy extends Model
{
    /** @use HasFactory<WordStudyFactory> */
    use HasFactory;

    /**
     * @return HasMany<WordStudyPassage, $this>
     */
    public function passages(): HasMany
    {
        return $this->hasMany(WordStudyPassage::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'original_word' => 'string',
            'transliteration' => 'string',
            'language' => 'string',
            'definition' => 'string',
            'strongs_number' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
