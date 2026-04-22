<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\BibleStudy\DraftBibleStudyTheme;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class DraftBibleStudyThemeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        public User $admin,
        public string $themeTitle,
    ) {}

    public function handle(DraftBibleStudyTheme $action): void
    {
        $action->handle($this->admin, $this->themeTitle);
    }
}
