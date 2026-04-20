<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read bool $completion_notifications
 * @property-read bool $observation_notifications
 * @property-read bool $new_theme_notifications
 * @property-read bool $reminder_notifications
 * @property-read bool $bible_study_partner_share_notifications
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class NotificationPreference extends Model
{
    /** @use HasFactory<NotificationPreferenceFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'completion_notifications' => 'boolean',
            'observation_notifications' => 'boolean',
            'new_theme_notifications' => 'boolean',
            'reminder_notifications' => 'boolean',
            'bible_study_partner_share_notifications' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
