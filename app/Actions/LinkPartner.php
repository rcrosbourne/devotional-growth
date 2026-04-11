<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class LinkPartner
{
    public function handle(User $user, User $partner): void
    {
        DB::transaction(function () use ($user, $partner): void {
            $user->update(['partner_id' => $partner->id]);
            $partner->update(['partner_id' => $user->id]);
        });
    }
}
