<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\GenerateThemeWithEntries;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Override;
use Throwable;

final class GenerateDevotionalThemeCommand extends Command
{
    /**
     * @var string
     */
    #[Override]
    protected $signature = 'devotional:generate
                            {prompt : The topic or prompt for the theme}
                            {--count=1 : Number of themes to generate}';

    /**
     * @var string
     */
    #[Override]
    protected $description = 'Generate devotional themes with entries using AI and notify admins for review';

    public function handle(GenerateThemeWithEntries $generateThemeWithEntries): int
    {
        $prompt = $this->argument('prompt');
        $count = (int) $this->option('count');

        $admin = User::query()->where('is_admin', true)->oldest('id')->first();

        if (! $admin) {
            $this->error('No admin user found. Please create an admin user first.');

            return self::FAILURE;
        }

        $this->info(sprintf('Generating %d theme(s) for prompt: "%s"', $count, $prompt));

        $generated = 0;

        for ($i = 0; $i < $count; $i++) {
            $themePrompt = $count > 1
                ? $prompt.' (variation '.($i + 1).sprintf(' of %d)', $count)
                : $prompt;

            $this->info('Generating theme '.($i + 1).sprintf(' of %d...', $count));

            try {
                $result = $generateThemeWithEntries->handle($admin, $themePrompt);

                $this->info(sprintf('Created theme "%s" with %d entries.', $result['theme']->name, $result['entry_count']));
                $generated++;
            } catch (Throwable $e) {
                Log::error('Failed to generate devotional theme', [
                    'prompt' => $themePrompt,
                    'exception' => $e,
                ]);

                $this->error('Failed to generate theme '.($i + 1).(': '.$e->getMessage()));
            }
        }

        if ($generated > 0) {
            $this->info(sprintf('Done! Generated %d theme(s). Admin users have been notified.', $generated));
        }

        return $generated > 0 ? self::SUCCESS : self::FAILURE;
    }
}
