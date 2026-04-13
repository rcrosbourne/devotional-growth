<?php

declare(strict_types=1);

use App\Models\Bookmark;
use App\Models\Lesson;
use App\Models\LessonDay;
use App\Models\Quarterly;
use App\Models\User;

// Model relationships

it('lesson has bookmarks morphMany relationship', function (): void {
    $user = User::factory()->create();
    $lesson = Lesson::factory()->create();
    Bookmark::factory()->create([
        'user_id' => $user->id,
        'bookmarkable_type' => Lesson::class,
        'bookmarkable_id' => $lesson->id,
    ]);

    expect($lesson->bookmarks)->toHaveCount(1);
});

it('lesson day has bookmarks morphMany relationship', function (): void {
    $user = User::factory()->create();
    $day = LessonDay::factory()->create();
    Bookmark::factory()->create([
        'user_id' => $user->id,
        'bookmarkable_type' => LessonDay::class,
        'bookmarkable_id' => $day->id,
    ]);

    expect($day->bookmarks)->toHaveCount(1);
});

// Bookmark CRUD via existing endpoints

it('can bookmark a lesson day', function (): void {
    $user = User::factory()->create();
    $day = LessonDay::factory()->create();

    $this->actingAs($user)
        ->post('/bookmarks', [
            'bookmarkable_type' => LessonDay::class,
            'bookmarkable_id' => $day->id,
        ])
        ->assertRedirect();

    expect(Bookmark::query()->where('user_id', $user->id)->where('bookmarkable_type', LessonDay::class)->count())->toBe(1);
});

it('can bookmark a lesson', function (): void {
    $user = User::factory()->create();
    $lesson = Lesson::factory()->create();

    $this->actingAs($user)
        ->post('/bookmarks', [
            'bookmarkable_type' => Lesson::class,
            'bookmarkable_id' => $lesson->id,
        ])
        ->assertRedirect();

    expect(Bookmark::query()->where('user_id', $user->id)->where('bookmarkable_type', Lesson::class)->count())->toBe(1);
});

it('can delete a lesson day bookmark', function (): void {
    $user = User::factory()->create();
    $day = LessonDay::factory()->create();
    $bookmark = Bookmark::factory()->create([
        'user_id' => $user->id,
        'bookmarkable_type' => LessonDay::class,
        'bookmarkable_id' => $day->id,
    ]);

    $this->actingAs($user)
        ->delete('/bookmarks/'.$bookmark->id)
        ->assertRedirect();

    expect(Bookmark::query()->count())->toBe(0);
});

// Day view passes bookmark data

it('day view passes bookmark state', function (): void {
    $user = User::factory()->create();
    $quarterly = Quarterly::factory()->create();
    $lesson = Lesson::factory()->create(['quarterly_id' => $quarterly->id]);
    $day = LessonDay::factory()->forDay(1)->create(['lesson_id' => $lesson->id]);

    $this->actingAs($user)
        ->get(route('sabbath-school.lessons.days.show', [$quarterly, $lesson, $day]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('isBookmarked', false)
            ->where('bookmarkId', null)
        );
});

it('day view shows bookmarked state when bookmarked', function (): void {
    $user = User::factory()->create();
    $quarterly = Quarterly::factory()->create();
    $lesson = Lesson::factory()->create(['quarterly_id' => $quarterly->id]);
    $day = LessonDay::factory()->forDay(1)->create(['lesson_id' => $lesson->id]);
    $bookmark = Bookmark::factory()->create([
        'user_id' => $user->id,
        'bookmarkable_type' => LessonDay::class,
        'bookmarkable_id' => $day->id,
    ]);

    $this->actingAs($user)
        ->get(route('sabbath-school.lessons.days.show', [$quarterly, $lesson, $day]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('isBookmarked', true)
            ->where('bookmarkId', $bookmark->id)
        );
});

// Bookmarks index includes sabbath school bookmarks

it('bookmarks index includes lesson and lesson day bookmarks', function (): void {
    $user = User::factory()->create();
    $lesson = Lesson::factory()->create();
    $day = LessonDay::factory()->create();

    Bookmark::factory()->create([
        'user_id' => $user->id,
        'bookmarkable_type' => Lesson::class,
        'bookmarkable_id' => $lesson->id,
    ]);
    Bookmark::factory()->create([
        'user_id' => $user->id,
        'bookmarkable_type' => LessonDay::class,
        'bookmarkable_id' => $day->id,
    ]);

    $this->actingAs($user)
        ->get('/bookmarks')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('lessons', 1)
            ->has('lessonDays', 1)
        );
});
