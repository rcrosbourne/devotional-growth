<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AiGenerationLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $admin_id
 * @property-read string $prompt
 * @property-read array<string, mixed>|null $generated_content
 * @property-read string $status
 * @property-read string|null $error_message
 * @property-read int|null $devotional_entry_id
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class AiGenerationLog extends Model
{
    /** @use HasFactory<AiGenerationLogFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

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
            'admin_id' => 'integer',
            'prompt' => 'string',
            'generated_content' => 'array',
            'status' => 'string',
            'error_message' => 'string',
            'devotional_entry_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
