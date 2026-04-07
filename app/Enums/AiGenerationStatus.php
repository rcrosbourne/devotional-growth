<?php

declare(strict_types=1);

namespace App\Enums;

enum AiGenerationStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
