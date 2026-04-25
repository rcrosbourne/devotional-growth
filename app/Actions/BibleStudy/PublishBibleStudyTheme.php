<?php

declare(strict_types=1);

namespace App\Actions\BibleStudy;

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use App\Models\User;
use DomainException;

final readonly class PublishBibleStudyTheme
{
    public function handle(User $admin, BibleStudyTheme $theme): BibleStudyTheme
    {
        throw_if($theme->status !== BibleStudyThemeStatus::Draft, DomainException::class, 'Only draft themes can be published.');

        $theme->update([
            'status' => BibleStudyThemeStatus::Approved,
            'approved_at' => now(),
            'approved_by_user_id' => $admin->id,
        ]);

        return $theme->refresh();
    }
}
