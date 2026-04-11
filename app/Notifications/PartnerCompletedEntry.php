<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\DevotionalEntry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class PartnerCompletedEntry extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $partner,
        public DevotionalEntry $entry,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'partner_id' => $this->partner->id,
            'partner_name' => $this->partner->name,
            'entry_id' => $this->entry->id,
            'entry_title' => $this->entry->title,
            'theme_id' => $this->entry->theme_id,
            'message' => sprintf('%s completed "%s"', $this->partner->name, $this->entry->title),
        ];
    }
}
