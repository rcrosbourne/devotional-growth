<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\LinkPartner;
use App\Http\Requests\LinkPartnerRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final readonly class PartnerController
{
    public function store(LinkPartnerRequest $request, #[CurrentUser] User $user, LinkPartner $action): RedirectResponse
    {
        /** @var User $partner */
        $partner = User::query()->where('email', $request->validated('email'))->firstOrFail();

        $action->handle($user, $partner);

        return back();
    }

    public function destroy(#[CurrentUser] User $user): RedirectResponse
    {
        if ($user->hasPartner()) {
            DB::transaction(function () use ($user): void {
                /** @var User $partner */
                $partner = $user->partner;
                $user->update(['partner_id' => null]);
                $partner->update(['partner_id' => null]);
            });
        }

        return back();
    }
}
