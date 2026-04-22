<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AiGenerationLog;
use App\Models\BibleStudyTheme;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class BibleStudyDraftReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AiGenerationLog $log,
        public string $themeTitle,
        public ?BibleStudyTheme $theme,
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
        $failed = ! $this->theme instanceof BibleStudyTheme;

        return [
            'log_id' => $this->log->id,
            'theme_id' => $this->theme?->id,
            'theme_slug' => $this->theme?->slug,
            'theme_title' => $this->themeTitle,
            'status' => $failed ? 'failed' : 'ready',
            'message' => $failed
                ? sprintf('AI draft for "%s" failed. Check log #%d.', $this->themeTitle, $this->log->id)
                : sprintf('AI draft for "%s" is ready to review.', $this->themeTitle),
            'error_excerpt' => $failed ? mb_substr((string) $this->log->error_message, 0, 180) : null,
        ];
    }
}
