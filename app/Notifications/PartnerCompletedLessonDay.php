<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LessonDay;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class PartnerCompletedLessonDay extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $partner,
        public LessonDay $lessonDay,
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
        $lesson = $this->lessonDay->lesson ?? $this->lessonDay->loadMissing('lesson')->lesson;

        /** @var \App\Models\Lesson $lesson */
        $lessonTitle = $lesson->title;

        return [
            'partner_id' => $this->partner->id,
            'partner_name' => $this->partner->name,
            'lesson_day_id' => $this->lessonDay->id,
            'day_name' => $this->lessonDay->day_name,
            'lesson_title' => $lessonTitle,
            'message' => sprintf(
                '%s completed %s\'s "%s" study',
                $this->partner->name,
                $this->lessonDay->day_name,
                $lessonTitle,
            ),
        ];
    }
}
