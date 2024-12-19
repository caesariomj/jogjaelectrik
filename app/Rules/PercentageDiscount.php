<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PercentageDiscount implements ValidationRule
{
    protected $maxValue;

    public function __construct(int $maxValue = 100)
    {
        $this->maxValue = $maxValue;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || $value < 0 || $value >= $this->maxValue) {
            $fail("Nilai potongan diskon tidak boleh lebih dari {$this->maxValue}% jika jenis diskon adalah persentase.");
        }
    }
}
