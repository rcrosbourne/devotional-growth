<?php

declare(strict_types=1);

use App\Actions\SaveAiContentAsDevotion;
use App\Models\AiGenerationLog;
use App\Models\User;

it('throws when neither theme_id nor new_theme_name is provided', function (): void {
    $admin = User::factory()->admin()->create();
    $log = AiGenerationLog::factory()->completed()->create(['admin_id' => $admin->id]);

    resolve(SaveAiContentAsDevotion::class)->handle($admin, $log, []);
})->throws(InvalidArgumentException::class, 'Either theme_id or new_theme_name is required.');
