<?php

declare(strict_types=1);

namespace App\Actions\SabbathSchool;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Log;

final readonly class ParseQuarterlyLesson
{
    /** @var array<int, string> */
    private const array DAY_IDS = ['sab', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'];

    /** @var array<int, string> */
    private const array DAY_NAMES = ['Sabbath', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    /**
     * Parse a lesson HTML page from ssnet.org into structured data.
     *
     * @return array{
     *     lesson_number: int,
     *     title: string,
     *     date_start: string,
     *     date_end: string,
     *     memory_text: string,
     *     memory_text_reference: string,
     *     has_parse_warnings: bool,
     *     days: array<int, array{
     *         day_position: int,
     *         day_name: string,
     *         title: string,
     *         body: string,
     *         discussion_questions: array<int, string>|null,
     *         scripture_references: array<int, string>,
     *         has_parse_warning: bool,
     *     }>
     * }
     */
    public function handle(string $html, int $lessonNumber): array
    {
        $doc = $this->loadHtml($html);
        $xpath = new DOMXPath($doc);

        $hasParseWarnings = false;
        $title = $this->extractLessonTitle($xpath);
        [$dateStart, $dateEnd] = $this->extractDateRange($xpath);
        [$memoryText, $memoryTextReference] = $this->extractMemoryText($xpath);

        if ($title === '' || $dateStart === '' || $memoryText === '') {
            $hasParseWarnings = true;
            Log::warning('ParseQuarterlyLesson: Missing header data', [
                'lesson_number' => $lessonNumber,
                'title' => $title,
                'date_start' => $dateStart,
                'memory_text' => mb_substr($memoryText, 0, 50),
            ]);
        }

        $days = $this->extractDays($doc);

        foreach ($days as $day) {
            if ($day['has_parse_warning']) {
                $hasParseWarnings = true;
            }
        }

        return [
            'lesson_number' => $lessonNumber,
            'title' => $title ?: 'Lesson '.$lessonNumber,
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'memory_text' => $memoryText,
            'memory_text_reference' => $memoryTextReference,
            'has_parse_warnings' => $hasParseWarnings,
            'days' => $days,
        ];
    }

    private function loadHtml(string $html): DOMDocument
    {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        return $doc;
    }

    private function extractLessonTitle(DOMXPath $xpath): string
    {
        $h2Nodes = $xpath->query('//h2');

        if ($h2Nodes === false || $h2Nodes->length === 0) {
            return '';
        }

        /** @var DOMElement $node */
        $node = $h2Nodes->item(0);

        return mb_trim($node->textContent);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDateRange(DOMXPath $xpath): array
    {
        $weekDatesNodes = $xpath->query("//*[contains(@class, 'week_dates')]");

        if ($weekDatesNodes === false || $weekDatesNodes->length === 0) {
            return ['', ''];
        }

        /** @var DOMElement $node */
        $node = $weekDatesNodes->item(0);
        $raw = mb_trim($node->textContent);
        $parts = preg_split('/[-–]/', $raw, 2);

        if ($parts === false || count($parts) < 2) {
            return [$raw, $raw];
        }

        return [mb_trim($parts[0]), mb_trim($parts[1])];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractMemoryText(DOMXPath $xpath): array
    {
        $memNodes = $xpath->query("//*[contains(@class, 'memory_text')]");

        if ($memNodes === false || $memNodes->length === 0) {
            return ['', ''];
        }

        /** @var DOMElement $node */
        $node = $memNodes->item(0);
        $fullText = mb_trim($node->textContent);
        $fullText = (string) preg_replace('/^Memory Text:\s*/i', '', $fullText);

        if (preg_match('/^["\x{201C}](.+?)["\x{201D}]\s*\((.+?)\)\s*\.?\s*$/us', $fullText, $matches)) {
            return [mb_trim($matches[1]), mb_trim($matches[2])];
        }

        return [$fullText, ''];
    }

    /**
     * @return array<int, array{
     *     day_position: int,
     *     day_name: string,
     *     title: string,
     *     body: string,
     *     discussion_questions: array<int, string>|null,
     *     scripture_references: array<int, string>,
     *     has_parse_warning: bool,
     * }>
     */
    private function extractDays(DOMDocument $doc): array
    {
        $days = [];

        foreach (self::DAY_IDS as $position => $dayId) {
            $dayElement = $doc->getElementById($dayId);

            if (! $dayElement) {
                Log::warning('ParseQuarterlyLesson: Day section not found', ['day_id' => $dayId]);
                $days[] = [
                    'day_position' => $position,
                    'day_name' => self::DAY_NAMES[$position],
                    'title' => self::DAY_NAMES[$position],
                    'body' => '',
                    'discussion_questions' => null,
                    'scripture_references' => [],
                    'has_parse_warning' => true,
                ];

                continue;
            }

            $nextDayId = $position < 6 ? self::DAY_IDS[$position + 1] : null;
            $contentElements = $this->collectElementsBetween($dayElement, $nextDayId);

            $title = $this->extractDayTitle($contentElements);
            $body = $this->buildDayBody($contentElements, $doc);
            $discussionQuestions = $position === 6 ? $this->extractDiscussionQuestions($contentElements) : null;
            $scriptureRefs = $this->extractScriptureReferences($contentElements);

            $hasParseProblem = $body === '' && $title === self::DAY_NAMES[$position];

            $days[] = [
                'day_position' => $position,
                'day_name' => self::DAY_NAMES[$position],
                'title' => $title ?: self::DAY_NAMES[$position],
                'body' => $body,
                'discussion_questions' => $discussionQuestions,
                'scripture_references' => $scriptureRefs,
                'has_parse_warning' => $hasParseProblem,
            ];
        }

        return $days;
    }

    /**
     * Collect all sibling elements between a day marker and the next day marker (or inside_story).
     *
     * @return array<int, DOMElement>
     */
    private function collectElementsBetween(DOMElement $startElement, ?string $nextDayId): array
    {
        $elements = [];
        $stopId = $nextDayId ?? 'inside_story';

        $current = $startElement->nextSibling;

        while ($current !== null) {
            if ($current instanceof DOMElement) {
                if ($current->getAttribute('id') === $stopId) {
                    break;
                }

                if ($this->findElementById($current, $stopId) instanceof DOMElement) {
                    break;
                }

                $elements[] = $current;
            }

            $current = $current->nextSibling;
        }

        return $elements;
    }

    private function findElementById(DOMElement $element, string $id): ?DOMElement
    {
        if ($element->getAttribute('id') === $id) {
            return $element;
        }

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $found = $this->findElementById($child, $id);
                if ($found instanceof DOMElement) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, DOMElement>  $elements
     */
    private function extractDayTitle(array $elements): string
    {
        foreach ($elements as $el) {
            if (in_array($el->tagName, ['h2', 'h3', 'h4', 'h5'], true)) {
                return mb_trim($el->textContent);
            }
        }

        return '';
    }

    /**
     * Build the day's body content as cleaned HTML.
     *
     * @param  array<int, DOMElement>  $elements
     */
    private function buildDayBody(array $elements, DOMDocument $doc): string
    {
        $parts = [];

        foreach ($elements as $el) {
            if ($this->shouldSkipElement($el)) {
                continue;
            }

            $html = $this->cleanElementHtml($el, $doc);

            if ($html !== '') {
                $parts[] = $html;
            }
        }

        return implode("\n", $parts);
    }

    private function shouldSkipElement(DOMElement $el): bool
    {
        $class = $el->getAttribute('class');

        if (str_contains($class, 'discussion-link')) {
            return true;
        }

        if (str_contains($class, 'day-jump-links') || str_contains($class, 'week-jump-links') || str_contains($class, 'study-jump-links')) {
            return true;
        }

        if ($el->getAttribute('id') === 'inside_story') {
            return true;
        }

        return str_contains($class, 'discussion');
    }

    private function cleanElementHtml(DOMElement $el, DOMDocument $doc): string
    {
        $class = $el->getAttribute('class');
        $tag = $el->tagName;
        $text = mb_trim($el->textContent);

        if ($text === '' && $tag !== 'hr') {
            return '';
        }

        if ($tag === 'hr' && str_contains($class, 'response')) {
            return '';
        }

        if (str_contains($class, 'aques')) {
            return '<p class="scripture-prompt"><strong>'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</strong></p>';
        }

        if (str_contains($class, 'summary')) {
            return '<div class="reflection"><p><em>'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</em></p></div>';
        }

        if (str_contains($class, 'study')) {
            return '<p class="study-note"><em>'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</em></p>';
        }

        if (in_array($tag, ['h2', 'h3', 'h4', 'h5'], true)) {
            return '';
        }

        if ($this->containsEgwQuote($el)) {
            return $this->formatAsBlockquote($el);
        }

        $innerHTML = $this->getInnerHtml($el, $doc);

        return '<p>'.$this->sanitizeHtml($innerHTML).'</p>';
    }

    private function containsEgwQuote(DOMElement $el): bool
    {
        $text = $el->textContent;

        return (bool) preg_match('/--.*Ellen\s+G\.\s+White|--.*E\.\s+G\.\s+White|--.*Steps to Christ|--.*Christ.s Object Lessons|--.*The Desire of Ages|--.*Testimonies/i', $text);
    }

    private function formatAsBlockquote(DOMElement $el): string
    {
        $text = mb_trim($el->textContent);

        if (preg_match('/^(.+?)(--\s*.+)$/s', $text, $matches)) {
            $quote = mb_trim($matches[1]);
            $attribution = mb_trim($matches[2]);
            $attribution = mb_ltrim($attribution, '-');

            return '<blockquote class="egw-quote"><p>'.htmlspecialchars($quote, ENT_QUOTES, 'UTF-8').'</p><footer>'.htmlspecialchars(mb_trim($attribution), ENT_QUOTES, 'UTF-8').'</footer></blockquote>';
        }

        return '<blockquote class="egw-quote"><p>'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</p></blockquote>'; // @codeCoverageIgnore
    }

    private function getInnerHtml(DOMElement $el, DOMDocument $doc): string
    {
        $html = '';

        foreach ($el->childNodes as $child) {
            $html .= $doc->saveHTML($child);
        }

        return $html;
    }

    private function sanitizeHtml(string $html): string
    {
        $html = preg_replace('/<a[^>]*class="[^"]*DayBarHRef[^"]*"[^>]*>.*?<\/a>/s', '', $html) ?? $html;

        $html = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $html) ?? $html;
        $html = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $html) ?? $html;
        $html = preg_replace('/\s(on\w+)="[^"]*"/i', '', $html) ?? $html;

        return mb_trim($html);
    }

    /**
     * Extract scripture references from "Read [reference]" patterns in aques elements.
     *
     * @param  array<int, DOMElement>  $elements
     * @return array<int, string>
     */
    private function extractScriptureReferences(array $elements): array
    {
        $references = [];

        foreach ($elements as $el) {
            $class = $el->getAttribute('class');
            $text = mb_trim($el->textContent);

            if (str_contains($class, 'aques') || str_contains($class, 'read_para')) {
                preg_match_all('/(?:Read\s+)?(\d?\s*[A-Za-z]+(?:\.\s*)?(?:\s+[A-Za-z]+)*\s+\d+:\d+(?:\s*[-–]\s*\d+)?)/i', $text, $matches);

                foreach ($matches[1] as $ref) {
                    $ref = mb_trim($ref);
                    if ($ref !== '' && ! in_array($ref, $references, true)) {
                        $references[] = $ref;
                    }
                }
            }
        }

        return $references;
    }

    /**
     * @param  array<int, DOMElement>  $elements
     * @return array<int, string>|null
     */
    private function extractDiscussionQuestions(array $elements): ?array
    {
        foreach ($elements as $el) {
            if (str_contains($el->getAttribute('class'), 'discussion')) {
                $questions = [];
                $listItems = $el->getElementsByTagName('li');

                foreach ($listItems as $li) {
                    $text = mb_trim($li->textContent);
                    if ($text !== '') {
                        $questions[] = $text;
                    }
                }

                return $questions !== [] ? $questions : null;
            }
        }

        return null;
    }
}
