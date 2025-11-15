<?php

namespace App\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SaleForm extends Form
{
    #[Validate]
    public array $items = [
        [
            'id' => '',
            'name' => '',
            'variants' => [
                [
                    'id' => '',
                    'name' => '',
                    'price' => 0,
                    'stock' => 0,
                    'quantity' => 0,
                ],
            ],
        ],
    ];

    public string $transactionTime = '';

    public string $source = '';

    public string $totalPrice = '';

    protected function prepareForValidation($attributes)
    {
        $attributes['totalPrice'] = (int) str_replace('.', '', $attributes['totalPrice']);

        foreach ($attributes['items'] as $itemIndex => $item) {
            foreach ($item['variants'] as $variantIndex => $variant) {
                $attributes['items'][$itemIndex]['variants'][$variantIndex]['quantity'] = (int) str_replace('.', '', $variant['quantity']);
            }
        }

        return $attributes;
    }

    protected function rules()
    {
        return [
            'items' => [
                'required',
                'array',
            ],
            'items.*' => [
                'required',
                'array',
                'size:3',
            ],
            'items.*.id' => [
                'required',
                'uuid',
                'exists:products,id',
            ],
            'items.*.name' => [
                'required',
                'string',
            ],
            'items.*.variants' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.variants.*' => [
                'required',
                'array',
                'size:5',
            ],
            'items.*.variants.*.id' => [
                'required',
                'uuid',
                'exists:product_variants,id',
            ],
            'items.*.variants.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
                'lte:items.*.variants.*.stock',
            ],
            'transactionTime' => [
                'required',
                'date',
            ],
            'source' => [
                'required',
                'string',
                Rule::in(['offline']),
            ],
            'totalPrice' => [
                'required',
                'numeric',
                'gt:0',
                'lt:99999999',
            ],
        ];
    }

    protected function validationAttributes()
    {
        return [
            'items' => 'Item produk',
            'items.*.id' => 'ID produk :position',
            'items.*.name' => 'Nama produk :position',
            'items.*.variants' => 'Varian item produk',
            'items.*.variants.*.id' => 'ID varian produk :position',
            'items.*.variants.*.quantity' => 'Kuantitas varian produk :position',
        ];
    }

    public function setSale()
    {
        $this->items = [];
        $this->transactionTime = now()->format('d-m-Y H:i');
        $this->source = 'offline';
        $this->totalPrice = '0';
    }
}
