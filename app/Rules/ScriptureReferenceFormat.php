<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\ScriptureReferenceParser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use InvalidArgumentException;

final readonly class ScriptureReferenceFormat implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        assert(is_string($value));

        try {
            resolve(ScriptureReferenceParser::class)->parse($value);
        } catch (InvalidArgumentException) {
            $fail('The :attribute must be a valid scripture reference (e.g., "John 3:16", "Psalm 23:1-6").');
        }
    }
}
