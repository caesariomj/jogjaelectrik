<?php

namespace App\Livewire\Forms;

use App\Models\Discount;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DiscountForm extends Form
{
    public ?Discount $discount = null;

    #[Validate]
    public string $name = '';

    #[Validate]
    public string $description = '';

    #[Validate]
    public bool $isActive = true;

    #[Validate]
    public string $code = '';

    #[Validate]
    public string $type = 'fixed';

    #[Validate]
    public string $value = '';

    #[Validate]
    public string $usageLimit = '';

    #[Validate]
    public string $minimumPurchase = '';

    #[Validate]
    public string $startDate = '';

    #[Validate]
    public string $endDate = '';

    public function rules()
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
                'max:99999999.99',
                function ($attribute, $value, $fail) {
                    if ($this->type === 'percentage' && $value >= 100) {
                        $fail(':attribute tidak boleh lebih dari 100 jika jenis diskon adalah persentase.');
                    }
                },
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
                'max:99999999.99',
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

    public function validationAttributes()
    {
        return [
            'name' => 'Nama diskon',
            'description' => 'Deskripsi diskon',
            'isActive' => 'Status diskon',
            'code' => 'Kode diskon',
            'type' => 'Tipe diskon',
            'value' => 'Nilai potongan',
            'usageLimit' => 'Pemakaian diskon',
            'minimumPurchase' => 'Minimum pembelian',
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
        $this->usageLimit = $discount->usage_limit ?? '';
        $this->minimumPurchase = number_format($discount->minimum_purchase, 0, '.', '');
        $this->startDate = ! is_null($discount->start_date) ? date('d-m-Y', strtotime($discount->start_date)) : '';
        $this->endDate = ! is_null($discount->end_date) ? date('d-m-Y', strtotime($discount->end_date)) : '';
    }
}
