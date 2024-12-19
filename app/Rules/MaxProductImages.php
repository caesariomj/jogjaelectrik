<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxProductImages implements ValidationRule
{
    protected $product;

    protected $maxImages;

    public function __construct($product, $maxImages = 9)
    {
        $this->product = $product;
        $this->maxImages = $maxImages;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $existingImagesCount = $this->product ? $this->product->images->count() : 0;

        $remainingSlots = $this->maxImages - $existingImagesCount;

        $newImagesCount = count($value);

        if ($newImagesCount > $remainingSlots) {
            $fail("Anda hanya dapat mengunggah {$remainingSlots} gambar lagi. Maksimal gambar yang diperbolehkan adalah {$this->maxImages} / produk.");
        }
    }
}
