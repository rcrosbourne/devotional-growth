<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SabbathSchool;

use App\Actions\SabbathSchool\ImportQuarter;
use App\Http\Requests\ImportQuarterRequest;
use App\Models\Quarterly;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class QuarterlyController
{
    public function index(): Response
    {
        $quarterlies = Quarterly::query()
            ->withCount(['lessons', 'lessons as lessons_with_images_count' => function (Builder $query): void {
                $query->whereNotNull('image_path');
            }])
            ->latest()
            ->get();

        return Inertia::render('admin/sabbath-school/index', [
            'quarterlies' => $quarterlies,
        ]);
    }

    public function import(ImportQuarterRequest $request, ImportQuarter $action): RedirectResponse
    {
        $quarterCode = $request->string('quarter_code')->value() ?: null;

        $result = $action->handle($quarterCode);

        $message = sprintf(
            'Imported "%s": %d lessons imported, %d failed.',
            $result['quarterly']->title,
            $result['lessons_imported'],
            $result['lessons_failed'],
        );

        return to_route('admin.sabbath-school.index')->with('success', $message);
    }

    public function sync(Quarterly $quarterly, ImportQuarter $action): RedirectResponse
    {
        $result = $action->handle($quarterly->quarter_code);

        $message = sprintf(
            'Re-synced "%s": %d lessons updated, %d failed.',
            $result['quarterly']->title,
            $result['lessons_imported'],
            $result['lessons_failed'],
        );

        return to_route('admin.sabbath-school.index')->with('success', $message);
    }

    public function activate(Quarterly $quarterly): RedirectResponse
    {
        Quarterly::query()->update(['is_active' => false]);
        $quarterly->update(['is_active' => true]);

        return to_route('admin.sabbath-school.index');
    }
}
