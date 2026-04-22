<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyInsight;
use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use Illuminate\Database\Seeder;

final class BibleStudyThemeSeeder extends Seeder
{
    public function run(): void
    {
        $theme = BibleStudyTheme::query()->updateOrCreate(
            ['slug' => 'resilience'],
            [
                'title' => 'Resilience',
                'short_description' => 'Faith under loss, waiting, and affliction.',
                'long_intro' => "Resilience in scripture is not stoic endurance. It is faith that clings through pain.\n\nAcross the canon, God meets afflicted people in their waiting—Job in ashes, Israel in exile, Paul in chains. The shape of biblical resilience is trust that keeps speaking, even through tears.",
                'status' => BibleStudyThemeStatus::Approved,
                'approved_at' => now(),
            ],
        );

        $passage = BibleStudyThemePassage::query()->updateOrCreate(
            ['bible_study_theme_id' => $theme->id, 'book' => 'Job', 'chapter' => 1, 'verse_start' => 13, 'verse_end' => 22],
            [
                'position' => 1,
                'is_guided_path' => true,
                'passage_intro' => 'Job loses his children and his livelihood in a single day. His response—grief expressed physically, trust expressed in worship—presents lament and faith as companions, not opposites.',
            ],
        );

        BibleStudyInsight::query()->updateOrCreate(
            ['bible_study_theme_passage_id' => $passage->id],
            [
                'interpretation' => "Job's tearing his robe and shaving his head are ritual acts of deep mourning. Falling to the ground in worship reframes the grief: he refuses to interpret his loss as evidence that God is unworthy.",
                'application' => 'Space to grieve does not require suspending faith. Worship here is not dismissal of pain; it is the refusal to let pain have the final word.',
                'cross_references' => [
                    ['book' => 'Lamentations', 'chapter' => 3, 'verse_start' => 19, 'verse_end' => 24, 'note' => 'Grief holds hope.'],
                    ['book' => '1 Peter', 'chapter' => 1, 'verse_start' => 6, 'verse_end' => 7, 'note' => 'Trials refine faith.'],
                ],
                'literary_context' => "Part of the prose prologue framing the poetic dialogues that follow. Verse 22 is the narrator's verdict: Job did not sin.",
            ],
        );

        BibleStudyHistoricalContext::query()->updateOrCreate(
            ['bible_study_theme_passage_id' => $passage->id],
            [
                'setting' => 'The land of Uz, likely east of Canaan in a patriarchal-era setting.',
                'author' => 'Unknown',
                'date_range' => 'Uncertain; possibly pre-exilic',
                'audience' => 'Israelite wisdom readers wrestling with undeserved suffering.',
                'historical_events' => "The narrative reports a sequence of raids (Sabean, Chaldean) and a natural disaster that destroy Job's household in a day.",
            ],
        );
    }
}
