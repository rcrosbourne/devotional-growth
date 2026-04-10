<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContentStatus;
use App\Models\Theme;
use App\Models\User;

final readonly class CreateTheme
{
    public function handle(User $admin, string $name, ?string $description = null): Theme
    {
        return Theme::query()->create([
            'created_by' => $admin->id,
            'name' => $name,
            'description' => $description,
            'status' => ContentStatus::Draft,
        ]);
    }
}
