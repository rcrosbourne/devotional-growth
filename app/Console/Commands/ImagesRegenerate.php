<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\GenerateDevotionalImage;
use App\Actions\GenerateThemeImage;
use App\Actions\SabbathSchool\GenerateLessonImage;
use App\Models\DevotionalEntry;
use App\Models\Lesson;
use App\Models\Theme;
use Illuminate\Console\Command;
use Throwable;

final class ImagesRegenerate extends Command
{
    private const string TYPE_ALL = 'all';

    private const string TYPE_DEVOTIONALS = 'devotionals';

    private const string TYPE_THEMES = 'themes';

    private const string TYPE_LESSONS = 'lessons';

    /**
     * @var string
     */
    protected $signature = 'images:regenerate
                            {--type=all : What to regenerate (all|devotionals|themes|lessons)}
                            {--limit=0 : Maximum number of images to regenerate per type (0 = no limit)}
                            {--dry-run : Show counts without regenerating}';

    /**
     * @var string
     */
    protected $description = 'Regenerate AI-generated images for devotional entries, themes, and Sabbath School lessons using the current prompt builder.';

    public function handle(
        GenerateDevotionalImage $devotionalAction,
        GenerateThemeImage $themeAction,
        GenerateLessonImage $lessonAction,
    ): int {
        $type = (string) $this->option('type');
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        if (! in_array($type, [self::TYPE_ALL, self::TYPE_DEVOTIONALS, self::TYPE_THEMES, self::TYPE_LESSONS], true)) {
            $this->error(sprintf('Invalid --type "%s". Use one of: all, devotionals, themes, lessons.', $type));

            return self::FAILURE;
        }

        $processed = 0;

        if ($type === self::TYPE_ALL || $type === self::TYPE_DEVOTIONALS) {
            $processed += $this->regenerateDevotionals($devotionalAction, $limit, $dryRun);
        }

        if ($type === self::TYPE_ALL || $type === self::TYPE_THEMES) {
            $processed += $this->regenerateThemes($themeAction, $limit, $dryRun);
        }

        if ($type === self::TYPE_ALL || $type === self::TYPE_LESSONS) {
            $processed += $this->regenerateLessons($lessonAction, $limit, $dryRun);
        }

        $this->info(sprintf('%s %d image(s).', $dryRun ? 'Would regenerate' : 'Processed', $processed));

        return self::SUCCESS;
    }

    private function regenerateDevotionals(GenerateDevotionalImage $action, int $limit, bool $dryRun): int
    {
        $query = DevotionalEntry::query()->whereHas('generatedImage');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $entries = $query->get();
        $this->info(sprintf('Devotionals with images: %d', $entries->count()));

        if ($dryRun) {
            return $entries->count();
        }

        $count = 0;

        foreach ($entries as $entry) {
            $this->line(sprintf('  [%d/%d] Devotional #%d: %s', $count + 1, $entries->count(), $entry->id, $entry->title));

            try {
                $action->handle($entry, replace: true);
                $count++;
            } catch (Throwable $e) {
                $this->warn(sprintf('  Devotional %d failed: %s', $entry->id, $e->getMessage()));
            }
        }

        return $count;
    }

    private function regenerateThemes(GenerateThemeImage $action, int $limit, bool $dryRun): int
    {
        $query = Theme::query()->whereNotNull('image_path');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $themes = $query->get();
        $this->info(sprintf('Themes with images: %d', $themes->count()));

        if ($dryRun) {
            return $themes->count();
        }

        $count = 0;

        foreach ($themes as $theme) {
            $this->line(sprintf('  [%d/%d] Theme #%d: %s', $count + 1, $themes->count(), $theme->id, $theme->name));

            try {
                $action->handle($theme, replace: true);
                $count++;
            } catch (Throwable $e) {
                $this->warn(sprintf('  Theme %d failed: %s', $theme->id, $e->getMessage()));
            }
        }

        return $count;
    }

    private function regenerateLessons(GenerateLessonImage $action, int $limit, bool $dryRun): int
    {
        $query = Lesson::query()->whereNotNull('image_path');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $lessons = $query->get();
        $this->info(sprintf('Lessons with images: %d', $lessons->count()));

        if ($dryRun) {
            return $lessons->count();
        }

        $index = 0;

        foreach ($lessons as $lesson) {
            $index++;
            $this->line(sprintf('  [%d/%d] Lesson #%d: %s', $index, $lessons->count(), $lesson->id, $lesson->title));
            $action->handle($lesson, replace: true);
        }

        return $lessons->count();
    }
}
