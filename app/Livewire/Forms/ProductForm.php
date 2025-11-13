<?php

namespace App\Livewire\Forms;

use App\Models\Product;
use App\Rules\MaxProductImages;
use App\Rules\Voltage;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ProductForm extends Form
{
    public ?Product $product = null;

    #[Validate]
    public mixed $thumbnail = null;

    public mixed $newThumbnail = null;

    public string $name = '';

    public string $subcategoryId = '';

    public string $mainSku = '';

    public string $costPrice = '';

    public string $description = '';

    public bool $isActive = true;

    public mixed $images = null;

    public mixed $newImages = null;

    public string $warranty = '';

    public string $material = '';

    public string $length = '';

    public string $width = '';

    public string $height = '';

    public string $weight = '';

    public string $package = '';

    public ?string $power = null;

    public ?string $voltage = null;

    public ?string $price = null;

    public ?string $priceDiscount = null;

    public ?string $stock = null;

    public array $variation = [
        'name' => '',
        'variants' => [
            [
                'name' => '',
                'price' => '',
                'priceDiscount' => '',
                'stock' => '',
                'variantSku' => '',
                'isVariantActive' => true,
            ],
        ],
    ];

    protected function prepareForValidation($attributes)
    {
        $attributes['costPrice'] = (int) str_replace('.', '', $attributes['costPrice']);
        $attributes['length'] = (int) str_replace('.', '', $attributes['length']);
        $attributes['width'] = (int) str_replace('.', '', $attributes['width']);
        $attributes['height'] = (int) str_replace('.', '', $attributes['height']);
        $attributes['weight'] = (int) str_replace('.', '', $attributes['weight']);
        $attributes['power'] = ($attributes['power'] !== null && $attributes['power'] !== '') ? (int) str_replace('.', '', $attributes['power']) : null;

        if ($this->variation['name'] !== '') {
            foreach ($attributes['variation']['variants'] as $key => $variant) {
                if ($variant['price'] !== '') {
                    $attributes['variation']['variants'][$key]['price'] = (int) str_replace('.', '', $variant['price']);
                }

                if ($variant['priceDiscount'] !== '') {
                    $attributes['variation']['variants'][$key]['priceDiscount'] = (int) str_replace('.', '', $variant['priceDiscount']);
                }

                if ($variant['stock'] !== '') {
                    $attributes['variation']['variants'][$key]['stock'] = (int) str_replace('.', '', $variant['stock']);
                }
            }
        } else {
            $attributes['price'] = (int) str_replace('.', '', $attributes['price']);
            $attributes['priceDiscount'] = ($attributes['priceDiscount'] !== null && $attributes['priceDiscount'] !== '') ? (int) str_replace('.', '', $attributes['priceDiscount']) : null;
            $attributes['stock'] = (int) str_replace('.', '', $attributes['stock']);
        }

        return $attributes;
    }

    protected function rules()
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'min:5',
                'max:255',
                is_null($this->product) ? 'unique:products,name' : 'unique:products,name,'.$this->product->id,
            ],
            'subcategoryId' => [
                'required',
                'uuid',
                'exists:subcategories,id',
            ],
            'mainSku' => [
                'required',
                'string',
                'min:5',
                'max:255',
                is_null($this->product) ? 'unique:products,main_sku' : 'unique:products,main_sku,'.$this->product->id,
            ],
            'costPrice' => [
                'required',
                'numeric',
                'gt:0',
                'lt:99999999',
            ],
            'description' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
            'isActive' => [
                'required',
                'boolean',
            ],
            'warranty' => [
                'required',
                'min:5',
                'max:100',
            ],
            'material' => [
                'required',
                'min:3',
                'max:100',
            ],
            'length' => [
                'required',
                'numeric',
                'gt:0',
                'lt:1000',
            ],
            'width' => [
                'required',
                'numeric',
                'gt:0',
                'lt:1000',
            ],
            'height' => [
                'required',
                'numeric',
                'gt:0',
                'lt:1000',
            ],
            'weight' => [
                'required',
                'numeric',
                'gt:0',
                'lt:30000',
            ],
            'package' => [
                'required',
                'string',
                'min:5',
                'max:100',
            ],
            'power' => [
                'nullable',
                'numeric',
                'gt:0',
                'lt:10000',
            ],
            'voltage' => [
                'nullable',
                'string',
                new Voltage,
            ],
            'price' => [
                'required_if:variation.name,""',
                'nullable',
                'numeric',
                'gt:0',
                'lt:99999999',
            ],
            'priceDiscount' => [
                'nullable',
                'numeric',
                'gt:0',
                'lt:price',
                'lt:99999999',
            ],
            'stock' => [
                'required_if:variation.name,""',
                'nullable',
                'numeric',
                'gt:0',
                'lt:1000',
            ],
            'variation' => [
                'array',
                'size:2',
            ],
            'variation.name' => [
                'required_if:price,""',
                'required_if:stock,""',
                'string',
                'min:3',
                'max:50',
            ],
            'variation.variants' => [
                'required_if:price,""',
                'required_if:stock,""',
                'array',
                'min:1',
                'max:9',
            ],
            'variation.variants.*' => [
                'required_if:price,""',
                'required_if:stock,""',
                'array',
                'size:6',
            ],
            'variation.variants.*.name' => [
                'required_with_all:variation.variants.*.price,variation.variants.*.stock,variation.variants.*.variantSku,variation.variants.*.isVariantActive',
                'string',
                'min:3',
                'max:50',
            ],
            'variation.variants.*.price' => [
                'required_with_all:variation.variants.*.price,variation.variants.*.stock,variation.variants.*.variantSku,variation.variants.*.isVariantActive',
                'numeric',
                'gt:0',
                'lt:99999999',
            ],
            'variation.variants.*.priceDiscount' => [
                'nullable',
                'numeric',
                'gt:0',
                'lt:variation.variants.*.price',
                'max:99999999',
            ],
            'variation.variants.*.stock' => [
                'required_with_all:variation.variants.*.price,variation.variants.*.stock,variation.variants.*.variantSku,variation.variants.*.isVariantActive',
                'numeric',
                'gt:0',
                'lt:1000',
            ],
            'variation.variants.*.variantSku' => [
                'required_with_all:variation.variants.*.price,variation.variants.*.stock,variation.variants.*.variantSku,variation.variants.*.isVariantActive',
                'string',
                'min:1',
                'max:255',
            ],
            'variation.variants.*.isVariantActive' => [
                'required_with_all:variation.variants.*.price,variation.variants.*.stock,variation.variants.*.variantSku,variation.variants.*.isVariantActive',
                'boolean',
            ],
        ];

        if (is_null($this->product)) {
            $rules['thumbnail'] = [
                'required',
                'image',
                'mimes:jpeg,jpg,png',
                'max:1024',
            ];

            $rules['images'] = [
                'nullable',
                'array',
                new MaxProductImages($this->product),
            ];

            $rules['images.*'] = [
                'image',
                'mimes:jpeg,jpg,png',
                'max:1024',
            ];
        } else {
            $rules['newThumbnail'] = [
                'required_if:thumbnail,null',
                'nullable',
                'image',
                'mimes:jpeg,jpg,png',
                'max:1024',
            ];

            $rules['newImages'] = [
                'nullable',
                'array',
                new MaxProductImages($this->product),
            ];

            $rules['newImages.*'] = [
                'image',
                'mimes:jpeg,jpg,png',
                'max:1024',
            ];
        }

        return $rules;
    }

    protected function validationAttributes()
    {
        return [
            'thumbnail' => 'Gambar utama produk',
            'newThumbnail' => 'Gambar utama produk baru',
            'name' => 'Nama produk',
            'subcategoryId' => 'Subkategori produk',
            'mainSku' => 'SKU utama produk',
            'costPrice' => 'Harga modal produk',
            'description' => 'Deskripsi produk',
            'isActive' => 'Status produk',
            'images' => 'Gambar produk',
            'images.*' => 'Gambar produk :position',
            'newImages' => 'Gambar produk baru',
            'newImages.*' => 'Gambar produk baru :position',
            'warranty' => 'Garansi produk',
            'material' => 'Bahan material produk',
            'length' => 'Panjang paket',
            'width' => 'Lebar paket',
            'height' => 'Tinggi paket',
            'weight' => 'Berat produk',
            'package' => 'Apa yang ada di dalam box',
            'power' => 'Daya listrik produk',
            'voltage' => 'Tegangan listrik produk',
            'price' => 'Harga produk',
            'priceDiscount' => 'Harga diskon produk',
            'stock' => 'Stok produk',
            'variation' => 'Variasi',
            'variation.name' => 'Nama variasi produk',
            'variation.variants' => 'Varian produk',
            'variation.variants.*' => 'Varian array produk',
            'variation.variants.*.name' => 'Nama varian produk :position',
            'variation.variants.*.price' => 'Harga varian produk :position',
            'variation.variants.*.priceDiscount' => 'Harga diskon varian produk :position',
            'variation.variants.*.stock' => 'Stok varian produk :position',
            'variation.variants.*.variantSku' => 'SKU varian produk :position',
        ];
    }

    public function setProduct($product)
    {
        $this->product = $product;
        $this->name = $product->name;
        $this->subcategoryId = $product->subcategory ? $product->subcategory_id : '';
        $this->mainSku = $product->main_sku;
        $this->costPrice = number_format($product->cost_price, 0, ',', '');
        $this->description = $product->description;
        $this->isActive = $product->is_active;
        $this->warranty = $product->warranty;
        $this->material = $product->material;
        $this->weight = $product->weight;
        $this->package = $product->package;
        $this->power = $product->power ? $product->power : null;
        $this->voltage = $product->voltage ? $product->voltage : null;

        $this->setImages($product->images);
        $this->setDimension($product->dimension);
        $this->setVariation($product->variation);
    }

    private function setImages(array $images)
    {
        foreach ($images as $image) {
            if ($image->is_thumbnail) {
                $this->thumbnail = $image;
            } else {
                $this->images[] = $image;
            }
        }
    }

    private function setDimension(string $dimension)
    {
        [$length, $width, $height] = explode('x', $dimension);

        $this->length = $length;
        $this->width = $width;
        $this->height = $height;
    }

    private function setVariation(?object $variation)
    {
        if ($variation) {
            $this->variation = [
                'name' => $variation->name,
                'variants' => array_map(function ($variant) {
                    return [
                        'name' => $variant->name,
                        'price' => number_format($variant->price, 0, '.', ''),
                        'priceDiscount' => $variant->price_discount ? number_format($variant->price_discount, 0, '.', '') : null,
                        'stock' => (int) $variant->stock,
                        'variantSku' => $variant->sku,
                        'isVariantActive' => (bool) $variant->is_active,
                    ];
                }, $variation->variants),
            ];
        } else {
            $this->price = number_format($this->product->base_price, 0, ',', '');
            $this->priceDiscount = $this->product->base_price_discount ? number_format($this->product->base_price_discount, 0, ',', '') : null;
            $this->stock = $this->product->total_stock;
        }
    }
}
