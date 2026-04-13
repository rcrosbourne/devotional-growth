<?php

declare(strict_types=1);

use App\Models\Lesson;
use App\Models\LessonDay;
use App\Models\LessonDayScriptureReference;
use App\Models\Quarterly;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

// Index page

it('renders the sabbath school index page', function (): void {
    $this->actingAs($this->user)
        ->get(route('sabbath-school.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('sabbath-school/index'));
});

it('shows the active quarterly on the index page', function (): void {
    $quarterly = Quarterly::factory()->active()->create();

    $this->actingAs($this->user)
        ->get(route('sabbath-school.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sabbath-school/index')
            ->where('activeQuarterly.id', $quarterly->id)
        );
});

it('shows past quarterlies on the index page', function (): void {
    Quarterly::factory()->active()->create(['quarter_code' => '26b']);
    Quarterly::factory()->create(['quarter_code' => '26a']);

    $this->actingAs($this->user)
        ->get(route('sabbath-school.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sabbath-school/index')
            ->has('pastQuarterlies', 1)
        );
});

it('shows empty state when no quarterlies exist', function (): void {
    $this->actingAs($this->user)
        ->get(route('sabbath-school.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sabbath-school/index')
            ->where('activeQuarterly', null)
            ->has('pastQuarterlies', 0)
        );
});

it('requires authentication for sabbath school index', function (): void {
    $this->get(route('sabbath-school.index'))
        ->assertRedirect();
});

// Show page

it('renders the quarterly show page with lessons', function (): void {
    $quarterly = Quarterly::factory()->active()->create();
    Lesson::factory()->count(3)->create(['quarterly_id' => $quarterly->id]);

    $this->actingAs($this->user)
        ->get(route('sabbath-school.show', $quarterly))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sabbath-school/show')
            ->where('quarterly.id', $quarterly->id)
            ->has('quarterly.lessons', 3)
        );
});

it('orders lessons by lesson number', function (): void {
    $quarterly = Quarterly::factory()->active()->create();
    Lesson::factory()->create(['quarterly_id' => $quarterly->id, 'lesson_number' => 3]);
    Lesson::factory()->create(['quarterly_id' => $quarterly->id, 'lesson_number' => 1]);
    Lesson::factory()->create(['quarterly_id' => $quarterly->id, 'lesson_number' => 2]);

    $this->actingAs($this->user)
        ->get(route('sabbath-school.show', $quarterly))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sabbath-school/show')
            ->where('quarterly.lessons.0.lesson_number', 1)
            ->where('quarterly.lessons.1.lesson_number', 2)
            ->where('quarterly.lessons.2.lesson_number', 3)
        );
});

it('shows an empty state for a quarterly with no lessons', function (): void {
    $quarterly = Quarterly::factory()->active()->create();

    $this->actingAs($this->user)
        ->get(route('sabbath-school.show', $quarterly))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sabbath-school/show')
            ->has('quarterly.lessons', 0)
        );
});

it('requires authentication for quarterly show', function (): void {
    $quarterly = Quarterly::factory()->create();

    $this->get(route('sabbath-school.show', $quarterly))
        ->assertRedirect();
});

// Lesson view

it('renders the lesson view page with days', function (): void {
    $quarterly = Quarterly::factory()->create();
    $lesson = Lesson::factory()->create(['quarterly_id' => $quarterly->id]);
    LessonDay::factory()->count(7)->sequence(
        ['day_position' => 0, 'day_name' => 'Sabbath'],
        ['day_position' => 1, 'day_name' => 'Sunday'],
        ['day_position' => 2, 'day_name' => 'Monday'],
        ['day_position' => 3, 'day_name' => 'Tuesday'],
        ['day_position' => 4, 'day_name' => 'Wednesday'],
        ['day_position' => 5, 'day_name' => 'Thursday'],
        ['day_position' => 6, 'day_name' => 'Friday'],
    )->create(['lesson_id' => $lesson->id]);

    $this->actingAs($this->user)
        ->get(route('sabbath-school.lessons.show', [$quarterly, $lesson]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sabbath-school/lesson')
            ->where('lesson.id', $lesson->id)
            ->has('lesson.days', 7)
            ->where('quarterly.id', $quarterly->id)
        );
});

it('provides previous and next lesson navigation', function (): void {
    $quarterly = Quarterly::factory()->create();
    $lesson1 = Lesson::factory()->create(['quarterly_id' => $quarterly->id, 'lesson_number' => 1]);
    $lesson2 = Lesson::factory()->create(['quarterly_id' => $quarterly->id, 'lesson_number' => 2]);
    $lesson3 = Lesson::factory()->create(['quarterly_id' => $quarterly->id, 'lesson_number' => 3]);

    $this->actingAs($this->user)
        ->get(route('sabbath-school.lessons.show', [$quarterly, $lesson2]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('previousLesson.id', $lesson1->id)
            ->where('nextLesson.id', $lesson3->id)
        );
});

it('has no previous lesson for lesson 1', function (): void {
    $quarterly = Quarterly::factory()->create();
    $lesson = Lesson::factory()->create(['quarterly_id' => $quarterly->id, 'lesson_number' => 1]);

    $this->actingAs($this->user)
        ->get(route('sabbath-school.lessons.show', [$quarterly, $lesson]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('previousLesson', null)
        );
});

it('requires authentication for lesson view', function (): void {
    $quarterly = Quarterly::factory()->create();
    $lesson = Lesson::factory()->create(['quarterly_id' => $quarterly->id]);

    $this->get(route('sabbath-school.lessons.show', [$quarterly, $lesson]))
        ->assertRedirect();
});

// Day view

it('renders the day view page with content and scripture references', function (): void {
    $quarterly = Quarterly::factory()->create();
    $lesson = Lesson::factory()->create(['quarterly_id' => $quarterly->id]);
    $day = LessonDay::factory()->forDay(1)->create([
        'lesson_id' => $lesson->id,
        'title' => 'Sunday Study',
        'body' => '<p>Study content here</p>',
    ]);
    LessonDayScriptureReference::factory()->count(2)->create(['lesson_day_id' => $day->id]);

    $this->actingAs($this->user)
        ->get(route('sabbath-school.lessons.days.show', [$quarterly, $lesson, $day]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sabbath-school/day')
            ->where('lessonDay.id', $day->id)
            ->has('lessonDay.scripture_references', 2)
            ->where('lesson.id', $lesson->id)
            ->where('quarterly.id', $quarterly->id)
        );
});

it('provides previous and next day navigation within a lesson', function (): void {
    $quarterly = Quarterly::factory()->create();
    $lesson = Lesson::factory()->create(['quarterly_id' => $quarterly->id]);
    LessonDay::factory()->forDay(0)->create(['lesson_id' => $lesson->id]);
    $monday = LessonDay::factory()->forDay(2)->create(['lesson_id' => $lesson->id]);
    $sunday = LessonDay::factory()->forDay(1)->create(['lesson_id' => $lesson->id]);

    $this->actingAs($this->user)
        ->get(route('sabbath-school.lessons.days.show', [$quarterly, $lesson, $sunday]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('previousDay.day_name', 'Sabbath')
            ->where('nextDay.day_name', 'Monday')
        );
});

it('navigates across lesson boundaries', function (): void {
    $quarterly = Quarterly::factory()->create();
    $lesson1 = Lesson::factory()->create(['quarterly_id' => $quarterly->id, 'lesson_number' => 1]);
    $lesson2 = Lesson::factory()->create(['quarterly_id' => $quarterly->id, 'lesson_number' => 2]);

    $friday = LessonDay::factory()->forDay(6)->create(['lesson_id' => $lesson1->id]);
    $sabbath2 = LessonDay::factory()->forDay(0)->create(['lesson_id' => $lesson2->id]);

    $this->actingAs($this->user)
        ->get(route('sabbath-school.lessons.days.show', [$quarterly, $lesson1, $friday]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('nextDay.lesson_day_id', $sabbath2->id)
        );
});

it('navigates backwards across lesson boundaries', function (): void {
    $quarterly = Quarterly::factory()->create();
    $lesson1 = Lesson::factory()->create(['quarterly_id' => $quarterly->id, 'lesson_number' => 1]);
    $lesson2 = Lesson::factory()->create(['quarterly_id' => $quarterly->id, 'lesson_number' => 2]);

    $friday1 = LessonDay::factory()->forDay(6)->create(['lesson_id' => $lesson1->id]);
    $sabbath2 = LessonDay::factory()->forDay(0)->create(['lesson_id' => $lesson2->id]);

    $this->actingAs($this->user)
        ->get(route('sabbath-school.lessons.days.show', [$quarterly, $lesson2, $sabbath2]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('previousDay.lesson_day_id', $friday1->id)
            ->where('previousDay.day_name', 'Friday')
        );
});

it('has no previous day for first day of first lesson', function (): void {
    $quarterly = Quarterly::factory()->create();
    $lesson = Lesson::factory()->create(['quarterly_id' => $quarterly->id, 'lesson_number' => 1]);
    $sabbath = LessonDay::factory()->forDay(0)->create(['lesson_id' => $lesson->id]);

    $this->actingAs($this->user)
        ->get(route('sabbath-school.lessons.days.show', [$quarterly, $lesson, $sabbath]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('previousDay', null)
        );
});

it('has no next day for last day of last lesson', function (): void {
    $quarterly = Quarterly::factory()->create();
    $lesson = Lesson::factory()->create(['quarterly_id' => $quarterly->id, 'lesson_number' => 13]);
    $friday = LessonDay::factory()->forDay(6)->create(['lesson_id' => $lesson->id]);

    $this->actingAs($this->user)
        ->get(route('sabbath-school.lessons.days.show', [$quarterly, $lesson, $friday]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('nextDay', null)
        );
});

it('shows discussion questions on Friday', function (): void {
    $quarterly = Quarterly::factory()->create();
    $lesson = Lesson::factory()->create(['quarterly_id' => $quarterly->id]);
    $friday = LessonDay::factory()->friday()->create([
        'lesson_id' => $lesson->id,
        'discussion_questions' => ['Question 1', 'Question 2'],
    ]);

    $this->actingAs($this->user)
        ->get(route('sabbath-school.lessons.days.show', [$quarterly, $lesson, $friday]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lessonDay.discussion_questions', ['Question 1', 'Question 2'])
        );
});

it('shows completed together when both partners have completed', function (): void {
    $partner = User::factory()->create();
    $userWithPartner = User::factory()->withPartner($partner)->create();
    $quarterly = Quarterly::factory()->create();
    $lesson = Lesson::factory()->create(['quarterly_id' => $quarterly->id]);
    $day = LessonDay::factory()->forDay(1)->create(['lesson_id' => $lesson->id]);

    App\Models\LessonDayCompletion::factory()->create(['user_id' => $userWithPartner->id, 'lesson_day_id' => $day->id]);
    App\Models\LessonDayCompletion::factory()->create(['user_id' => $partner->id, 'lesson_day_id' => $day->id]);

    $this->actingAs($userWithPartner)
        ->get(route('sabbath-school.lessons.days.show', [$quarterly, $lesson, $day]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('isCompleted', true)
            ->where('isPartnerCompleted', true)
            ->where('hasPartner', true)
        );
});

it('requires authentication for day view', function (): void {
    $quarterly = Quarterly::factory()->create();
    $lesson = Lesson::factory()->create(['quarterly_id' => $quarterly->id]);
    $day = LessonDay::factory()->create(['lesson_id' => $lesson->id]);

    $this->get(route('sabbath-school.lessons.days.show', [$quarterly, $lesson, $day]))
        ->assertRedirect();
});
