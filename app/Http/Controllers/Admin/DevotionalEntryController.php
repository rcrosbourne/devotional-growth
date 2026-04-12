<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\CreateDevotionalEntry;
use App\Actions\DeleteDevotionalEntry;
use App\Actions\PublishDevotionalEntry;
use App\Actions\ReorderDevotionalEntries;
use App\Actions\UnpublishDevotionalEntry;
use App\Actions\UpdateDevotionalEntry;
use App\Http\Requests\CreateDevotionalEntryRequest;
use App\Http\Requests\ReorderDevotionalEntriesRequest;
use App\Http\Requests\UpdateDevotionalEntryRequest;
use App\Models\DevotionalEntry;
use App\Models\Theme;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DevotionalEntryController
{
    public function index(Theme $theme): Response
    {
        $entries = $theme->entries()
            ->with('scriptureReferences')
            ->orderBy('display_order')
            ->get();

        return Inertia::render('admin/devotional-entries/index', [
            'theme' => $theme,
            'entries' => $entries,
        ]);
    }

    public function create(Theme $theme): Response
    {
        return Inertia::render('admin/devotional-entries/create', [
            'theme' => $theme,
        ]);
    }

    public function store(CreateDevotionalEntryRequest $request, Theme $theme, CreateDevotionalEntry $action): RedirectResponse
    {
        /** @var array<string> $scriptureReferences */
        $scriptureReferences = $request->input('scripture_references');

        $action->handle($theme, [
            'title' => $request->string('title')->value(),
            'body' => $request->string('body')->value(),
            'reflection_prompts' => $request->string('reflection_prompts')->value() ?: null,
            'adventist_insights' => $request->string('adventist_insights')->value() ?: null,
            'scripture_references' => $scriptureReferences,
        ]);

        return to_route('admin.themes.entries.index', $theme);
    }

    public function edit(Theme $theme, DevotionalEntry $entry): Response
    {
        return Inertia::render('admin/devotional-entries/edit', [
            'theme' => $theme,
            'entry' => $entry->load(['scriptureReferences', 'generatedImage']),
        ]);
    }

    public function update(UpdateDevotionalEntryRequest $request, Theme $theme, DevotionalEntry $entry, UpdateDevotionalEntry $action): RedirectResponse
    {
        /** @var array<string> $scriptureReferences */
        $scriptureReferences = $request->input('scripture_references');

        $action->handle($entry, [
            'title' => $request->string('title')->value(),
            'body' => $request->string('body')->value(),
            'reflection_prompts' => $request->string('reflection_prompts')->value() ?: null,
            'adventist_insights' => $request->string('adventist_insights')->value() ?: null,
            'scripture_references' => $scriptureReferences,
        ]);

        return to_route('admin.themes.entries.index', $theme);
    }

    public function destroy(Theme $theme, DevotionalEntry $entry, DeleteDevotionalEntry $action): RedirectResponse
    {
        $action->handle($entry);

        return to_route('admin.themes.entries.index', $theme);
    }

    public function publish(Theme $theme, DevotionalEntry $entry, PublishDevotionalEntry $action): RedirectResponse
    {
        $action->handle($entry);

        return to_route('admin.themes.entries.index', $theme);
    }

    public function unpublish(Theme $theme, DevotionalEntry $entry, UnpublishDevotionalEntry $action): RedirectResponse
    {
        $action->handle($entry);

        return to_route('admin.themes.entries.index', $theme);
    }

    public function reorder(ReorderDevotionalEntriesRequest $request, Theme $theme, ReorderDevotionalEntries $action): RedirectResponse
    {
        /** @var array<int> $orderedIds */
        $orderedIds = $request->input('ordered_ids');

        $action->handle($theme, $orderedIds);

        return to_route('admin.themes.entries.index', $theme);
    }
}
