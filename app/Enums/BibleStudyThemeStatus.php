<?php

declare(strict_types=1);

namespace App\Enums;

enum BibleStudyThemeStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Archived = 'archived';
}
