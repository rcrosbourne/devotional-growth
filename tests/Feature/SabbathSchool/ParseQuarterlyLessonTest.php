<?php

declare(strict_types=1);

use App\Actions\SabbathSchool\ParseQuarterlyLesson;

beforeEach(function (): void {
    $this->html = file_get_contents(base_path('tests/fixtures/ssnet_lesson_03.html'));
    $this->parser = new ParseQuarterlyLesson();
});

it('extracts the lesson title', function (): void {
    $result = $this->parser->handle($this->html, 3);

    expect($result['title'])->toBe('Pride Versus Humility');
});

it('extracts the lesson number', function (): void {
    $result = $this->parser->handle($this->html, 3);

    expect($result['lesson_number'])->toBe(3);
});

it('extracts the date range', function (): void {
    $result = $this->parser->handle($this->html, 3);

    expect($result['date_start'])->toBe('April 11');
    expect($result['date_end'])->toBe('17');
});

it('extracts the memory text and reference', function (): void {
    $result = $this->parser->handle($this->html, 3);

    expect($result['memory_text'])->toContain('whoever exalts himself will be humbled');
    expect($result['memory_text_reference'])->toContain('Luke 14:11');
});

it('extracts all 7 day sections', function (): void {
    $result = $this->parser->handle($this->html, 3);

    expect($result['days'])->toHaveCount(7);
});

it('assigns correct day positions and names', function (): void {
    $result = $this->parser->handle($this->html, 3);

    $expectedNames = ['Sabbath', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    foreach ($result['days'] as $index => $day) {
        expect($day['day_position'])->toBe($index);
        expect($day['day_name'])->toBe($expectedNames[$index]);
    }
});

it('extracts day section titles', function (): void {
    $result = $this->parser->handle($this->html, 3);

    // Sunday should have a title from the h4
    $sunday = $result['days'][1];
    expect($sunday['title'])->toBe('The Tight Fingers of Pride');
});

it('extracts body content for each day', function (): void {
    $result = $this->parser->handle($this->html, 3);

    // Sunday body should contain text about pride
    $sunday = $result['days'][1];
    expect($sunday['body'])->not->toBeEmpty();
    expect($sunday['body'])->toContain('pride');
});

it('extracts discussion questions from Friday', function (): void {
    $result = $this->parser->handle($this->html, 3);

    $friday = $result['days'][6];
    expect($friday['discussion_questions'])->not->toBeNull();
    expect($friday['discussion_questions'])->toBeArray();
    expect(count($friday['discussion_questions']))->toBeGreaterThanOrEqual(1);
});

it('extracts scripture references from day sections', function (): void {
    $result = $this->parser->handle($this->html, 3);

    // Sunday has "Read 1 John 2:15-17"
    $sunday = $result['days'][1];
    expect($sunday['scripture_references'])->toBeArray();
    expect($sunday['scripture_references'])->not->toBeEmpty();
    expect($sunday['scripture_references'][0])->toContain('1 John 2:15-17');
});

it('extracts multiple scripture references from a single day', function (): void {
    $result = $this->parser->handle($this->html, 3);

    // Thursday has "Read Luke 22:27" and "Read Philippians 2:3-8"
    $thursday = $result['days'][5];
    expect(count($thursday['scripture_references']))->toBeGreaterThanOrEqual(2);
});

it('returns empty scripture references for days without them', function (): void {
    $result = $this->parser->handle($this->html, 3);

    // Each day should have a scripture_references key
    foreach ($result['days'] as $day) {
        expect($day['scripture_references'])->toBeArray();
    }
});

it('does not have discussion questions on non-Friday days', function (): void {
    $result = $this->parser->handle($this->html, 3);

    foreach ($result['days'] as $day) {
        if ($day['day_name'] !== 'Friday') {
            expect($day['discussion_questions'])->toBeNull();
        }
    }
});

it('formats EGW quotes as blockquotes in body', function (): void {
    $result = $this->parser->handle($this->html, 3);

    // Friday usually has EGW quotes in the body
    $friday = $result['days'][6];
    expect($friday['body'])->toContain('blockquote');
    expect($friday['body'])->toContain('egw-quote');
});

it('formats scripture prompts with emphasis', function (): void {
    $result = $this->parser->handle($this->html, 3);

    // Sunday should have a scripture prompt (aques class)
    $sunday = $result['days'][1];
    expect($sunday['body'])->toContain('scripture-prompt');
});

it('excludes discussion link elements from body', function (): void {
    $result = $this->parser->handle($this->html, 3);

    foreach ($result['days'] as $day) {
        expect($day['body'])->not->toContain('Discuss on the Daily Blog');
    }
});

it('excludes inside story content', function (): void {
    $result = $this->parser->handle($this->html, 3);

    foreach ($result['days'] as $day) {
        expect($day['body'])->not->toContain('Inside Story');
    }
});

it('sets has_parse_warnings to false for well-formed content', function (): void {
    $result = $this->parser->handle($this->html, 3);

    expect($result['has_parse_warnings'])->toBeFalse();
});

it('handles missing day section gracefully', function (): void {
    // Strip out the Monday section marker
    $html = str_replace('id="mon"', 'id="mon_removed"', $this->html);

    $result = $this->parser->handle($html, 3);

    $monday = $result['days'][2];
    expect($monday['has_parse_warning'])->toBeTrue();
    expect($monday['body'])->toBe('');
    expect($result['has_parse_warnings'])->toBeTrue();
});

it('handles missing lesson title gracefully', function (): void {
    // Remove all h2 tags
    $html = preg_replace('/<h2[^>]*>.*?<\/h2>/s', '', $this->html);

    $result = $this->parser->handle($html, 5);

    expect($result['title'])->toBe('Lesson 5');
    expect($result['has_parse_warnings'])->toBeTrue();
});

it('handles missing memory text gracefully', function (): void {
    $html = str_replace('class="memory_text"', 'class="removed_memory"', $this->html);

    $result = $this->parser->handle($html, 3);

    expect($result['memory_text'])->toBe('');
    expect($result['has_parse_warnings'])->toBeTrue();
});

it('includes reflection and study note formatting in body', function (): void {
    $result = $this->parser->handle($this->html, 3);

    // Check that summary/reflection classes are present
    $hasReflection = false;
    foreach ($result['days'] as $day) {
        if (str_contains((string) $day['body'], 'reflection')) {
            $hasReflection = true;
            break;
        }
    }

    expect($hasReflection)->toBeTrue();
});

it('handles sabbath section without title heading', function (): void {
    $result = $this->parser->handle($this->html, 3);

    // Sabbath section typically uses "Sabbath Afternoon" as the day name, no h4 title
    $sabbath = $result['days'][0];
    expect($sabbath['day_name'])->toBe('Sabbath');
    expect($sabbath['body'])->not->toBeEmpty();
});

it('returns empty string for body of empty paragraphs', function (): void {
    $result = $this->parser->handle($this->html, 3);

    // Body should not contain completely empty <p></p> tags
    foreach ($result['days'] as $day) {
        expect($day['body'])->not->toContain('<p></p>');
    }
});

it('handles missing date range gracefully', function (): void {
    $html = str_replace('class="week_dates"', 'class="removed_dates"', $this->html);

    $result = $this->parser->handle($html, 3);

    expect($result['date_start'])->toBe('');
    expect($result['date_end'])->toBe('');
});

it('handles date range without dash separator', function (): void {
    // Replace the date with a single date (no range)
    $html = str_replace('April 11-17', 'April 11', $this->html);

    $result = $this->parser->handle($html, 3);

    // Should return the raw text for both start and end
    expect($result['date_start'])->toBe('April 11');
});

it('handles memory text without standard quote formatting', function (): void {
    // Replace the memory text with plain text (no quotes)
    $html = str_replace('&ldquo;For whoever exalts himself will be humbled, and he who humbles himself will be exalted&rdquo; (Luke 14:11, NKJV).', 'Some plain text without proper formatting.', $this->html);

    $result = $this->parser->handle($html, 3);

    // Should return the text as-is with empty reference
    expect($result['memory_text'])->toContain('Some plain text');
    expect($result['memory_text_reference'])->toBe('');
});

it('handles hr response elements by skipping them', function (): void {
    $result = $this->parser->handle($this->html, 3);

    // Body should not contain raw hr tags
    foreach ($result['days'] as $day) {
        expect($day['body'])->not->toContain('<hr class="response">');
    }
});

it('skips heading elements from body content', function (): void {
    $result = $this->parser->handle($this->html, 3);

    // h4 day titles should not appear in body
    $sunday = $result['days'][1];
    expect($sunday['body'])->not->toContain('<h4>');
});

it('handles nested elements containing day ids', function (): void {
    // Create HTML where a day id is nested inside a div (not a direct sibling)
    $html = '<html><body>';
    $html .= '<p class="flush-left"><span class="lesson_number">Lesson 1</span> <span class="week_dates">Jan 1-7</span></p>';
    $html .= '<h2>Test</h2>';
    $html .= '<p class="memory_text">Memory Text: "Test" (John 3:16).</p>';
    $html .= '<p id="sab">Sabbath</p><p>Content</p>';
    $html .= '<div><p id="sun" class="day">Sunday</p></div><p>Sunday body</p>';
    $html .= '<div><p id="mon" class="day">Monday</p></div><p>Monday body</p>';
    $html .= '<div><p id="tue" class="day">Tuesday</p></div><p>Tue body</p>';
    $html .= '<div><p id="wed" class="day">Wednesday</p></div><p>Wed body</p>';
    $html .= '<div><p id="thu" class="day">Thursday</p></div><p>Thu body</p>';
    $html .= '<div><p id="fri" class="day">Friday</p></div><p>Fri body</p>';
    $html .= '<div id="inside_story"></div>';
    $html .= '</body></html>';

    $result = $this->parser->handle($html, 1);

    expect($result['days'])->toHaveCount(7);
});

it('skips navigation link elements in body', function (): void {
    $html = '<html><body>';
    $html .= '<p class="flush-left"><span class="lesson_number">Lesson 1</span> <span class="week_dates">Jan 1-7</span></p>';
    $html .= '<h2>Test</h2>';
    $html .= '<p class="memory_text">Memory Text: "Test" (John 3:16).</p>';
    $html .= '<p id="sab">Sabbath</p>';
    $html .= '<div class="day-jump-links">Nav links</div>';
    $html .= '<div class="week-jump-links">Week links</div>';
    $html .= '<div class="study-jump-links">Study links</div>';
    $html .= '<p>Real content</p>';
    $html .= '<p id="sun" class="day">Sunday</p><p>Sun content</p>';
    $html .= '<p id="mon" class="day">Monday</p><p>Mon content</p>';
    $html .= '<p id="tue" class="day">Tuesday</p><p>Tue content</p>';
    $html .= '<p id="wed" class="day">Wednesday</p><p>Wed content</p>';
    $html .= '<p id="thu" class="day">Thursday</p><p>Thu content</p>';
    $html .= '<p id="fri" class="day">Friday</p><p>Fri content</p>';
    $html .= '<div id="inside_story"></div>';
    $html .= '</body></html>';

    $result = $this->parser->handle($html, 1);
    $sabbath = $result['days'][0];

    expect($sabbath['body'])->not->toContain('Nav links');
    expect($sabbath['body'])->not->toContain('Week links');
    expect($sabbath['body'])->not->toContain('Study links');
    expect($sabbath['body'])->toContain('Real content');
});

it('returns null discussion questions when no discussion div exists', function (): void {
    $html = '<html><body>';
    $html .= '<p class="flush-left"><span class="lesson_number">Lesson 1</span> <span class="week_dates">Jan 1-7</span></p>';
    $html .= '<h2>Test</h2>';
    $html .= '<p class="memory_text">Memory Text: "Test" (John 3:16).</p>';
    $html .= '<p id="sab">Sabbath</p><p>Sab</p>';
    $html .= '<p id="sun" class="day">Sunday</p><p>Sun</p>';
    $html .= '<p id="mon" class="day">Monday</p><p>Mon</p>';
    $html .= '<p id="tue" class="day">Tuesday</p><p>Tue</p>';
    $html .= '<p id="wed" class="day">Wednesday</p><p>Wed</p>';
    $html .= '<p id="thu" class="day">Thursday</p><p>Thu</p>';
    $html .= '<p id="fri" class="day">Friday</p><p>Just text, no discussion div</p>';
    $html .= '<div id="inside_story"></div>';
    $html .= '</body></html>';

    $result = $this->parser->handle($html, 1);

    expect($result['days'][6]['discussion_questions'])->toBeNull();
});

it('handles empty text elements by skipping them', function (): void {
    $html = '<html><body>';
    $html .= '<p class="flush-left"><span class="lesson_number">Lesson 1</span> <span class="week_dates">Jan 1-7</span></p>';
    $html .= '<h2>Test</h2>';
    $html .= '<p class="memory_text">Memory Text: "Test" (John 3:16).</p>';
    $html .= '<p id="sab">Sabbath</p><p></p><p>   </p><p>Real content</p>';
    $html .= '<p id="sun" class="day">Sunday</p><p>Sun</p>';
    $html .= '<p id="mon" class="day">Monday</p><p>Mon</p>';
    $html .= '<p id="tue" class="day">Tuesday</p><p>Tue</p>';
    $html .= '<p id="wed" class="day">Wednesday</p><p>Wed</p>';
    $html .= '<p id="thu" class="day">Thursday</p><p>Thu</p>';
    $html .= '<p id="fri" class="day">Friday</p><p>Fri</p>';
    $html .= '<div id="inside_story"></div>';
    $html .= '</body></html>';

    $result = $this->parser->handle($html, 1);
    $sabbath = $result['days'][0];

    expect($sabbath['body'])->toContain('Real content');
    expect($sabbath['body'])->not->toContain('<p></p>');
});
