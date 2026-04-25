<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\BibleStudy\DraftBibleStudyTheme;
use App\Enums\AiGenerationStatus;
use App\Models\BibleStudyTheme;
use App\Models\User;
use App\Notifications\BibleStudyDraftReady;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

#[\Illuminate\Queue\Attributes\Timeout(300)]
#[\Illuminate\Queue\Attributes\Tries(2)]
final class DraftBibleStudyThemeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $admin,
        public string $themeTitle,
    ) {}

    public function handle(DraftBibleStudyTheme $action): void
    {
        $log = $action->handle($this->admin, $this->themeTitle);

        $theme = $log->status === AiGenerationStatus::Completed
            ? BibleStudyTheme::query()
                ->where('slug', data_get($log->generated_content, 'slug'))
                ->latest('id')
                ->first()
            : null;

        $this->admin->notify(new BibleStudyDraftReady($log, $this->themeTitle, $theme));
    }
}
