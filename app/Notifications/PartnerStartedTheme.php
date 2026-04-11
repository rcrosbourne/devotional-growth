<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Theme;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class PartnerStartedTheme extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $partner,
        public Theme $theme,
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
            'theme_id' => $this->theme->id,
            'theme_name' => $this->theme->name,
            'message' => sprintf('%s started the theme "%s"', $this->partner->name, $this->theme->name),
        ];
    }
}
