<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\SabbathSchool\GenerateLessonImage;
use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

#[\Illuminate\Queue\Attributes\Timeout(180)]
#[\Illuminate\Queue\Attributes\Tries(2)]
final class GenerateLessonImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Lesson $lesson, public bool $replace = false) {}

    public function handle(GenerateLessonImage $action): void
    {
        $action->handle($this->lesson, $this->replace);
    }
}
