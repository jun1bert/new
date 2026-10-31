<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DrawnSignature implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $strokes = is_string($value) ? json_decode($value, true) : null;
        if (! is_array($strokes) || ! array_is_list($strokes) || count($strokes) < 1 || count($strokes) > 80) {
            $fail('Please draw your signature in the signature pad.');
            return;
        }
        $count = 0;
        $length = 0;
        foreach ($strokes as $stroke) {
            if (! is_array($stroke) || ! array_is_list($stroke) || count($stroke) < 2 || count($stroke) > 600) {
                $fail('Please clear the pad and draw your signature again.');
                return;
            }
            $previous = null;
            foreach ($stroke as $point) {
                if (! is_array($point) || ! array_is_list($point) || count($point) !== 2
                    || ! is_numeric($point[0]) || ! is_numeric($point[1])
                    || ! is_finite((float) $point[0]) || ! is_finite((float) $point[1])
                    || $point[0] < 0 || $point[0] > 800 || $point[1] < 0 || $point[1] > 400 || ++$count > 5000) {
                    $fail('Please clear the pad and draw your signature again.');
                    return;
                }
                if ($previous) $length += hypot($point[0] - $previous[0], $point[1] - $previous[1]);
                $previous = $point;
            }
        }
        if ($length < 12) $fail('Please draw your signature rather than tapping the pad.');
    }
}
