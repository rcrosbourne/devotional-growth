<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WordStudy;
use App\Models\WordStudyPassage;
use Illuminate\Database\Seeder;

final class WordStudySeeder extends Seeder
{
    public function run(): void
    {
        if (WordStudy::query()->exists()) {
            return;
        }

        $entries = $this->getWordStudyEntries();

        $now = now();

        foreach ($entries as $entry) {
            $wordStudy = WordStudy::query()->create([
                'original_word' => $entry['original_word'],
                'transliteration' => $entry['transliteration'],
                'language' => $entry['language'],
                'definition' => $entry['definition'],
                'strongs_number' => $entry['strongs_number'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $passageRecords = [];
            foreach ($entry['passages'] as $passage) {
                $passageRecords[] = [
                    'word_study_id' => $wordStudy->id,
                    'book' => $passage['book'],
                    'chapter' => $passage['chapter'],
                    'verse' => $passage['verse'],
                    'english_word' => $passage['english_word'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            WordStudyPassage::query()->insert($passageRecords);
        }
    }

    /**
     * @return list<array{original_word: string, transliteration: string, language: string, definition: string, strongs_number: string, passages: list<array{book: string, chapter: int, verse: int, english_word: string}>}>
     */
    private function getWordStudyEntries(): array
    {
        return [
            [
                'original_word' => 'ἀγάπη',
                'transliteration' => 'agape',
                'language' => 'greek',
                'definition' => "Love, affection, benevolence; used in the New Testament to describe God's unconditional, self-sacrificing love for humanity and the love believers are called to show one another.",
                'strongs_number' => 'G26',
                'passages' => [
                    ['book' => 'John', 'chapter' => 3, 'verse' => 16, 'english_word' => 'love'],
                    ['book' => '1 Corinthians', 'chapter' => 13, 'verse' => 4, 'english_word' => 'charity'],
                    ['book' => '1 Corinthians', 'chapter' => 13, 'verse' => 13, 'english_word' => 'charity'],
                    ['book' => '1 John', 'chapter' => 4, 'verse' => 8, 'english_word' => 'love'],
                    ['book' => '1 John', 'chapter' => 4, 'verse' => 16, 'english_word' => 'love'],
                    ['book' => 'Romans', 'chapter' => 5, 'verse' => 8, 'english_word' => 'love'],
                    ['book' => 'Romans', 'chapter' => 8, 'verse' => 39, 'english_word' => 'love'],
                    ['book' => 'Galatians', 'chapter' => 5, 'verse' => 22, 'english_word' => 'love'],
                ],
            ],
            [
                'original_word' => 'πίστις',
                'transliteration' => 'pistis',
                'language' => 'greek',
                'definition' => 'Faith, belief, trust, confidence; firm persuasion and conviction based upon hearing. In the New Testament, refers to trust in God and in Christ for salvation.',
                'strongs_number' => 'G4102',
                'passages' => [
                    ['book' => 'Hebrews', 'chapter' => 11, 'verse' => 1, 'english_word' => 'faith'],
                    ['book' => 'Hebrews', 'chapter' => 11, 'verse' => 6, 'english_word' => 'faith'],
                    ['book' => 'Romans', 'chapter' => 1, 'verse' => 17, 'english_word' => 'faith'],
                    ['book' => 'Romans', 'chapter' => 10, 'verse' => 17, 'english_word' => 'faith'],
                    ['book' => 'Galatians', 'chapter' => 2, 'verse' => 20, 'english_word' => 'faith'],
                    ['book' => 'Ephesians', 'chapter' => 2, 'verse' => 8, 'english_word' => 'faith'],
                    ['book' => 'James', 'chapter' => 2, 'verse' => 17, 'english_word' => 'faith'],
                ],
            ],
            [
                'original_word' => 'χάρις',
                'transliteration' => 'charis',
                'language' => 'greek',
                'definition' => 'Grace, favor, kindness; the unmerited favor of God toward sinful humanity, enabling salvation and spiritual growth. Also refers to thankfulness and gratitude.',
                'strongs_number' => 'G5485',
                'passages' => [
                    ['book' => 'Ephesians', 'chapter' => 2, 'verse' => 8, 'english_word' => 'grace'],
                    ['book' => 'Romans', 'chapter' => 3, 'verse' => 24, 'english_word' => 'grace'],
                    ['book' => 'Romans', 'chapter' => 6, 'verse' => 14, 'english_word' => 'grace'],
                    ['book' => '2 Corinthians', 'chapter' => 12, 'verse' => 9, 'english_word' => 'grace'],
                    ['book' => 'John', 'chapter' => 1, 'verse' => 14, 'english_word' => 'grace'],
                    ['book' => 'Titus', 'chapter' => 2, 'verse' => 11, 'english_word' => 'grace'],
                ],
            ],
            [
                'original_word' => 'εἰρήνη',
                'transliteration' => 'eirene',
                'language' => 'greek',
                'definition' => 'Peace, tranquility, harmony; a state of rest and wholeness. In the New Testament, denotes the peace that comes from reconciliation with God through Christ.',
                'strongs_number' => 'G1515',
                'passages' => [
                    ['book' => 'John', 'chapter' => 14, 'verse' => 27, 'english_word' => 'peace'],
                    ['book' => 'Philippians', 'chapter' => 4, 'verse' => 7, 'english_word' => 'peace'],
                    ['book' => 'Romans', 'chapter' => 5, 'verse' => 1, 'english_word' => 'peace'],
                    ['book' => 'Galatians', 'chapter' => 5, 'verse' => 22, 'english_word' => 'peace'],
                    ['book' => 'Colossians', 'chapter' => 3, 'verse' => 15, 'english_word' => 'peace'],
                ],
            ],
            [
                'original_word' => 'ἐλπίς',
                'transliteration' => 'elpis',
                'language' => 'greek',
                'definition' => "Hope, expectation; a confident expectation of what God has promised. In the New Testament, refers to the believer's assured hope in God's faithfulness and the return of Christ.",
                'strongs_number' => 'G1680',
                'passages' => [
                    ['book' => 'Romans', 'chapter' => 8, 'verse' => 24, 'english_word' => 'hope'],
                    ['book' => 'Romans', 'chapter' => 15, 'verse' => 13, 'english_word' => 'hope'],
                    ['book' => '1 Corinthians', 'chapter' => 13, 'verse' => 13, 'english_word' => 'hope'],
                    ['book' => 'Hebrews', 'chapter' => 6, 'verse' => 19, 'english_word' => 'hope'],
                    ['book' => '1 Peter', 'chapter' => 1, 'verse' => 3, 'english_word' => 'hope'],
                    ['book' => 'Titus', 'chapter' => 2, 'verse' => 13, 'english_word' => 'hope'],
                ],
            ],
            [
                'original_word' => 'δικαιοσύνη',
                'transliteration' => 'dikaiosyne',
                'language' => 'greek',
                'definition' => 'Righteousness, justice; the quality of being right or just before God. In the New Testament, refers to the righteousness imputed to believers through faith in Christ.',
                'strongs_number' => 'G1343',
                'passages' => [
                    ['book' => 'Romans', 'chapter' => 3, 'verse' => 22, 'english_word' => 'righteousness'],
                    ['book' => 'Romans', 'chapter' => 1, 'verse' => 17, 'english_word' => 'righteousness'],
                    ['book' => 'Matthew', 'chapter' => 5, 'verse' => 6, 'english_word' => 'righteousness'],
                    ['book' => 'Matthew', 'chapter' => 6, 'verse' => 33, 'english_word' => 'righteousness'],
                    ['book' => '2 Corinthians', 'chapter' => 5, 'verse' => 21, 'english_word' => 'righteousness'],
                    ['book' => 'Philippians', 'chapter' => 3, 'verse' => 9, 'english_word' => 'righteousness'],
                ],
            ],
            [
                'original_word' => 'σωτηρία',
                'transliteration' => 'soteria',
                'language' => 'greek',
                'definition' => 'Salvation, deliverance, preservation; the act of being saved from sin and its consequences. In the New Testament, refers to the deliverance from sin and spiritual death through Christ.',
                'strongs_number' => 'G4991',
                'passages' => [
                    ['book' => 'Acts', 'chapter' => 4, 'verse' => 12, 'english_word' => 'salvation'],
                    ['book' => 'Ephesians', 'chapter' => 2, 'verse' => 8, 'english_word' => 'saved'],
                    ['book' => 'Romans', 'chapter' => 1, 'verse' => 16, 'english_word' => 'salvation'],
                    ['book' => 'Philippians', 'chapter' => 2, 'verse' => 12, 'english_word' => 'salvation'],
                    ['book' => 'Hebrews', 'chapter' => 2, 'verse' => 3, 'english_word' => 'salvation'],
                ],
            ],
            [
                'original_word' => 'μετάνοια',
                'transliteration' => 'metanoia',
                'language' => 'greek',
                'definition' => 'Repentance, a change of mind; a turning away from sin and toward God. Involves a fundamental shift in thinking and attitude that leads to changed behavior.',
                'strongs_number' => 'G3341',
                'passages' => [
                    ['book' => 'Acts', 'chapter' => 2, 'verse' => 38, 'english_word' => 'repentance'],
                    ['book' => 'Acts', 'chapter' => 3, 'verse' => 19, 'english_word' => 'repent'],
                    ['book' => 'Luke', 'chapter' => 15, 'verse' => 7, 'english_word' => 'repentance'],
                    ['book' => '2 Peter', 'chapter' => 3, 'verse' => 9, 'english_word' => 'repentance'],
                    ['book' => 'Romans', 'chapter' => 2, 'verse' => 4, 'english_word' => 'repentance'],
                ],
            ],
            [
                'original_word' => 'ἁμαρτία',
                'transliteration' => 'hamartia',
                'language' => 'greek',
                'definition' => "Sin, missing the mark; an offense against God's law, a wandering from the path of righteousness. In the New Testament, refers to both the act of sinning and the inherent sinful nature.",
                'strongs_number' => 'G266',
                'passages' => [
                    ['book' => 'Romans', 'chapter' => 3, 'verse' => 23, 'english_word' => 'sin'],
                    ['book' => 'Romans', 'chapter' => 6, 'verse' => 23, 'english_word' => 'sin'],
                    ['book' => '1 John', 'chapter' => 1, 'verse' => 9, 'english_word' => 'sins'],
                    ['book' => 'Hebrews', 'chapter' => 12, 'verse' => 1, 'english_word' => 'sin'],
                    ['book' => 'John', 'chapter' => 1, 'verse' => 29, 'english_word' => 'sin'],
                ],
            ],
            [
                'original_word' => 'לֵב',
                'transliteration' => 'leb',
                'language' => 'hebrew',
                'definition' => 'Heart, mind, inner person; the center of human thought, will, and emotion. In the Old Testament, the seat of intellect, feeling, and moral decision-making.',
                'strongs_number' => 'H3820',
                'passages' => [
                    ['book' => 'Proverbs', 'chapter' => 4, 'verse' => 23, 'english_word' => 'heart'],
                    ['book' => 'Psalm', 'chapter' => 51, 'verse' => 10, 'english_word' => 'heart'],
                    ['book' => 'Jeremiah', 'chapter' => 17, 'verse' => 9, 'english_word' => 'heart'],
                    ['book' => 'Deuteronomy', 'chapter' => 6, 'verse' => 5, 'english_word' => 'heart'],
                    ['book' => 'Proverbs', 'chapter' => 3, 'verse' => 5, 'english_word' => 'heart'],
                ],
            ],
            [
                'original_word' => 'שָׁלוֹם',
                'transliteration' => 'shalom',
                'language' => 'hebrew',
                'definition' => 'Peace, completeness, welfare, safety; a state of wholeness and harmony. In the Old Testament, describes total well-being in relationship with God and others.',
                'strongs_number' => 'H7965',
                'passages' => [
                    ['book' => 'Numbers', 'chapter' => 6, 'verse' => 26, 'english_word' => 'peace'],
                    ['book' => 'Isaiah', 'chapter' => 26, 'verse' => 3, 'english_word' => 'peace'],
                    ['book' => 'Psalm', 'chapter' => 29, 'verse' => 11, 'english_word' => 'peace'],
                    ['book' => 'Psalm', 'chapter' => 122, 'verse' => 6, 'english_word' => 'peace'],
                    ['book' => 'Jeremiah', 'chapter' => 29, 'verse' => 11, 'english_word' => 'peace'],
                ],
            ],
            [
                'original_word' => 'אֱמוּנָה',
                'transliteration' => 'emunah',
                'language' => 'hebrew',
                'definition' => "Faithfulness, firmness, steadfastness; the quality of being reliable and trustworthy. In the Old Testament, describes both human faithfulness and God's unwavering reliability.",
                'strongs_number' => 'H530',
                'passages' => [
                    ['book' => 'Habakkuk', 'chapter' => 2, 'verse' => 4, 'english_word' => 'faith'],
                    ['book' => 'Psalm', 'chapter' => 119, 'verse' => 90, 'english_word' => 'faithfulness'],
                    ['book' => 'Lamentations', 'chapter' => 3, 'verse' => 23, 'english_word' => 'faithfulness'],
                    ['book' => 'Deuteronomy', 'chapter' => 32, 'verse' => 4, 'english_word' => 'truth'],
                ],
            ],
            [
                'original_word' => 'חֶסֶד',
                'transliteration' => 'chesed',
                'language' => 'hebrew',
                'definition' => "Loving-kindness, mercy, steadfast love, covenant loyalty; God's faithful, enduring love within covenant relationship. One of the most theologically rich words in the Old Testament.",
                'strongs_number' => 'H2617',
                'passages' => [
                    ['book' => 'Psalm', 'chapter' => 136, 'verse' => 1, 'english_word' => 'mercy'],
                    ['book' => 'Psalm', 'chapter' => 23, 'verse' => 6, 'english_word' => 'mercy'],
                    ['book' => 'Psalm', 'chapter' => 103, 'verse' => 8, 'english_word' => 'mercy'],
                    ['book' => 'Micah', 'chapter' => 6, 'verse' => 8, 'english_word' => 'mercy'],
                    ['book' => 'Lamentations', 'chapter' => 3, 'verse' => 22, 'english_word' => 'mercies'],
                    ['book' => 'Hosea', 'chapter' => 6, 'verse' => 6, 'english_word' => 'mercy'],
                ],
            ],
            [
                'original_word' => 'λόγος',
                'transliteration' => 'logos',
                'language' => 'greek',
                'definition' => 'Word, speech, reason, account; the expression of thought. In John\'s Gospel, "the Word" refers to the eternal Son of God, the second person of the Trinity, who became flesh.',
                'strongs_number' => 'G3056',
                'passages' => [
                    ['book' => 'John', 'chapter' => 1, 'verse' => 1, 'english_word' => 'Word'],
                    ['book' => 'John', 'chapter' => 1, 'verse' => 14, 'english_word' => 'Word'],
                    ['book' => 'Hebrews', 'chapter' => 4, 'verse' => 12, 'english_word' => 'word'],
                    ['book' => 'Psalm', 'chapter' => 119, 'verse' => 105, 'english_word' => 'word'],
                    ['book' => '1 Peter', 'chapter' => 1, 'verse' => 23, 'english_word' => 'word'],
                ],
            ],
            [
                'original_word' => 'πνεῦμα',
                'transliteration' => 'pneuma',
                'language' => 'greek',
                'definition' => 'Spirit, breath, wind; the immaterial part of a being. In the New Testament, refers to the Holy Spirit (the third person of the Trinity), the human spirit, or spiritual beings.',
                'strongs_number' => 'G4151',
                'passages' => [
                    ['book' => 'John', 'chapter' => 3, 'verse' => 8, 'english_word' => 'Spirit'],
                    ['book' => 'Romans', 'chapter' => 8, 'verse' => 16, 'english_word' => 'Spirit'],
                    ['book' => 'Galatians', 'chapter' => 5, 'verse' => 22, 'english_word' => 'Spirit'],
                    ['book' => 'Acts', 'chapter' => 2, 'verse' => 4, 'english_word' => 'Spirit'],
                    ['book' => 'John', 'chapter' => 14, 'verse' => 26, 'english_word' => 'Spirit'],
                    ['book' => '1 Corinthians', 'chapter' => 2, 'verse' => 14, 'english_word' => 'Spirit'],
                ],
            ],
        ];
    }
}
