<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateBookmark;
use App\Actions\DeleteBookmark;
use App\Http\Requests\CreateBookmarkRequest;
use App\Models\Bookmark;
use App\Models\DevotionalEntry;
use App\Models\Lesson;
use App\Models\LessonDay;
use App\Models\ScriptureReference;
use App\Models\User;
use App\Models\WordStudy;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class BookmarkController
{
    public function index(#[CurrentUser] User $user): Response
    {
        $bookmarks = $user->bookmarks()
            ->with('bookmarkable')
            ->latest()
            ->get();

        return Inertia::render('bookmarks/index', [
            'devotionalEntries' => $bookmarks->filter(
                fn (Bookmark $bookmark): bool => $bookmark->bookmarkable_type === DevotionalEntry::class,
            )->values(),
            'scriptureReferences' => $bookmarks->filter(
                fn (Bookmark $bookmark): bool => $bookmark->bookmarkable_type === ScriptureReference::class,
            )->values(),
            'wordStudies' => $bookmarks->filter(
                fn (Bookmark $bookmark): bool => $bookmark->bookmarkable_type === WordStudy::class,
            )->values(),
            'lessons' => $bookmarks->filter(
                fn (Bookmark $bookmark): bool => $bookmark->bookmarkable_type === Lesson::class,
            )->values(),
            'lessonDays' => $bookmarks->filter(
                fn (Bookmark $bookmark): bool => $bookmark->bookmarkable_type === LessonDay::class,
            )->values(),
        ]);
    }

    public function store(CreateBookmarkRequest $request, #[CurrentUser] User $user, CreateBookmark $action): RedirectResponse
    {
        $action->handle(
            $user,
            $request->string('bookmarkable_type')->toString(),
            $request->integer('bookmarkable_id'),
        );

        return back();
    }

    public function destroy(Bookmark $bookmark, #[CurrentUser] User $user, DeleteBookmark $action): RedirectResponse
    {
        abort_unless($bookmark->user_id === $user->id, 403);

        $action->handle($bookmark);

        return back();
    }
}
