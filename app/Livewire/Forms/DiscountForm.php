<?php

namespace App\Livewire\Forms;

use App\Models\Discount;
use App\Rules\PercentageDiscount;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DiscountForm extends Form
{
    public ?Discount $discount = null;

    #[Validate]
    public string $name = '';

    public string $description = '';

    public bool $isActive = true;

    public string $code = '';

    public string $type = 'fixed';

    public string $value = '';

    public string $maxDiscountAmount = '';

    public string $usageLimit = '';

    public string $minimumPurchase = '';

    public string $startDate = '';

    public string $endDate = '';

    protected function prepareForValidation($attributes)
    {
        if ($attributes['type'] === 'fixed') {
            $attributes['value'] = (int) str_replace('.', '', $attributes['value']);
        } elseif ($attributes['type'] === 'percentage') {
            $attributes['value'] = (int) preg_replace('/[^0-9]/', '', $attributes['value']);
            $attributes['maxDiscountAmount'] = (int) str_replace('.', '', $attributes['maxDiscountAmount']);
        }

        $attributes['minimumPurchase'] = (int) str_replace('.', '', $attributes['minimumPurchase']);

        return $attributes;
    }

    protected function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                is_null($this->discount) ? 'unique:discounts,name' : 'unique:discounts,name,'.$this->discount->id,
            ],
            'description' => [
                'nullable',
                'string',
                'min:5',
                'max:1000',
            ],
            'isActive' => [
                'required',
                'boolean',
            ],
            'code' => [
                'required',
                'alpha_dash:ascii',
                'string',
                'min:3',
                'max:50',
                is_null($this->discount) ? 'unique:discounts,code' : 'unique:discounts,code,'.$this->discount->id,
            ],
            'type' => [
                'required',
                'string',
                'in:fixed,percentage',
            ],
            'value' => [
                'required',
                'numeric',
                'gt:0',
                'lt:99999999',
                Rule::when($this->type === 'percentage', [new PercentageDiscount]),
            ],
            'maxDiscountAmount' => [
                'nullable',
                'required_if:type,percentage',
                'numeric',
                'gt:0',
                'lt:99999999',
            ],
            'usageLimit' => [
                'nullable',
                'numeric',
                'gt:0',
                'max:255',
            ],
            'minimumPurchase' => [
                'required',
                'numeric',
                'gt:0',
                'lt:99999999',
            ],
            'startDate' => [
                'nullable',
                'date',
                'before_or_equal:endDate',
            ],
            'endDate' => [
                'nullable',
                'date',
                'after_or_equal:startDate',
            ],
        ];
    }

    protected function validationAttributes()
    {
        return [
            'name' => 'Nama diskon',
            'description' => 'Deskripsi diskon',
            'isActive' => 'Status diskon',
            'code' => 'Kode diskon',
            'type' => 'Tipe diskon',
            'value' => 'Nilai potongan',
            'maxDiscountAmount' => 'Maksimal potongan harga',
            'usageLimit' => 'Pemakaian diskon',
            'minimumPurchase' => 'Minimum harga pembelian',
            'startDate' => 'Tanggal mulai diskon',
            'endDate' => 'Tanggal berakhir diskon',
        ];
    }

    public function setDiscount($discount)
    {
        $this->discount = $discount;
        $this->name = $discount->name;
        $this->description = $discount->description ?? '';
        $this->isActive = $discount->is_active;
        $this->code = $discount->code;
        $this->type = $discount->type;
        $this->value = number_format($discount->value, 0, '.', '');
        $this->maxDiscountAmount = $discount->max_discount_amount ? number_format($discount->max_discount_amount, 0, '.', '') : '';
        $this->usageLimit = $discount->usage_limit ?? '';
        $this->minimumPurchase = number_format($discount->minimum_purchase, 0, '.', '');
        $this->startDate = ! is_null($discount->start_date) ? date('d-m-Y', strtotime($discount->start_date)) : '';
        $this->endDate = ! is_null($discount->end_date) ? date('d-m-Y', strtotime($discount->end_date)) : '';
    }
}
