<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateDevotionalImage;
use App\Models\DevotionalEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class GenerateDevotionalImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        public DevotionalEntry $entry,
        public bool $replace = false,
    ) {}

    public function handle(GenerateDevotionalImage $action): void
    {
        $action->handle($this->entry, $this->replace);
    }
}
