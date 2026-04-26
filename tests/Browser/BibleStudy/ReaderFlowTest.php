<?php

declare(strict_types=1);

use App\Models\BibleStudyReflection;
use App\Models\User;
use Database\Seeders\BibleStudyThemeSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::fake([
        'bible-api.com/*' => Http::response([
            'verses' => [
                ['verse' => 13, 'text' => 'And there was a day when his sons and his daughters were eating...'],
                ['verse' => 22, 'text' => 'In all this Job sinned not, nor charged God foolishly.'],
            ],
        ]),
    ]);
});

it('user can open the Resilience theme, read Job 1:13–22, and save a reflection', function (): void {
    (new BibleStudyThemeSeeder)->run();
    $user = User::factory()->create();

    $this->actingAs($user);

    $page = visit('/bible-study');

    $page->assertSee('Bible Study')
        ->assertSee('Resilience')
        ->click('Resilience')
        ->assertSee('Job 1:13');

    $passagePage = visit('/bible-study/passage?theme=resilience&book=Job&chapter=1&verse_start=13&verse_end=22');

    $passagePage->assertSee('Historical Context')
        ->assertSee('Insights')
        ->type('[placeholder="Reflect on this passage..."]', 'Worship before understanding.')
        ->click('Save reflection')
        ->assertSee('Worship before understanding.');

    expect(BibleStudyReflection::query()->where('user_id', $user->id)->count())->toBe(1);
});
