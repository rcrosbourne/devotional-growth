<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateObservation;
use App\Actions\DeleteObservation;
use App\Actions\UpdateObservation;
use App\Http\Requests\CreateObservationRequest;
use App\Http\Requests\UpdateObservationRequest;
use App\Models\DevotionalEntry;
use App\Models\Observation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class ObservationController
{
    public function store(
        CreateObservationRequest $request,
        DevotionalEntry $entry,
        #[CurrentUser] User $user,
        CreateObservation $action,
    ): RedirectResponse {
        $action->handle($user, $entry, $request->string('body')->value());

        return back();
    }

    public function update(
        UpdateObservationRequest $request,
        Observation $observation,
        #[CurrentUser] User $user,
        UpdateObservation $action,
    ): RedirectResponse {
        abort_unless($observation->user_id === $user->id, 403);

        $action->handle($observation, $request->string('body')->value());

        return back();
    }

    public function destroy(
        Observation $observation,
        #[CurrentUser] User $user,
        DeleteObservation $action,
    ): RedirectResponse {
        abort_unless($observation->user_id === $user->id, 403);

        $action->handle($observation);

        return back();
    }
}
