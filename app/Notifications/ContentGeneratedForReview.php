<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Theme;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ContentGeneratedForReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Theme $theme,
        public int $entryCount,
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
            'theme_id' => $this->theme->id,
            'theme_name' => $this->theme->name,
            'entry_count' => $this->entryCount,
            'message' => sprintf(
                'New theme "%s" with %d entries has been generated and is ready for review.',
                $this->theme->name,
                $this->entryCount,
            ),
        ];
    }
}
