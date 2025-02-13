<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Voltage implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen($value) < 3) {
            $fail('Tegangan listrik produk harus valid. (contoh: 220 atau 220-240).');
        } elseif (strlen($value) > 3) {
            if (! preg_match('/^\d{3}-\d{3}$/', $value)) {
                $fail('Tegangan listrik produk harus valid. (contoh: 220 atau 220-240).');
            }
        } elseif (strlen($value) === 3) {
            if (! ctype_digit($value)) {
                $fail('Tegangan listrik produk harus berupa angka 3 digit angka. (contoh: 220).');
            }
        }
    }
}
