<?php

namespace App\Livewire\Forms;

use App\Models\Product;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ProductForm extends Form
{
    public ?Product $product = null;

    #[Validate]
    public mixed $thumbnail = null;

    #[Validate]
    public mixed $newThumbnail = null;

    #[Validate]
    public string $name = '';

    #[Validate]
    public string $subcategoryId = '';

    #[Validate]
    public string $mainSku = '';

    #[Validate]
    public string $description = '';

    #[Validate]
    public bool $isActive = true;

    #[Validate]
    public mixed $images = null;

    #[Validate]
    public mixed $newImages = null;

    #[Validate]
    public string $warranty = '';

    #[Validate]
    public string $material = '';

    #[Validate]
    public string $length = '';

    #[Validate]
    public string $width = '';

    #[Validate]
    public string $height = '';

    #[Validate]
    public string $weight = '';

    #[Validate]
    public string $package = '';

    #[Validate]
    public ?string $power = null;

    #[Validate]
    public ?string $voltage = null;

    #[Validate]
    public ?string $price = null;

    #[Validate]
    public ?string $priceDiscount = null;

    #[Validate]
    public ?string $stock = null;

    #[Validate]
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

    public function rules()
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'min:5',
                'max:255',
                is_null($this->product) ? 'unique:categories,name' : 'unique:categories,name,'.$this->product->id,
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
                'min:1',
                'max:50',
            ],
            'price' => [
                'required_if:variation.name,""',
                'nullable',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],
            'priceDiscount' => [
                'nullable',
                'numeric',
                'gt:0',
                'lt:price',
                'max:99999999.99',
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
                'max:99999999.99',
            ],
            'variation.variants.*.priceDiscount' => [
                'nullable',
                'numeric',
                'gt:0',
                'lt:variation.variants.*.price',
                'max:99999999.99',
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
                'required',
                'array',
                'min:1',
                'max:9',
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
                'required_if:images,null',
                'nullable',
                'array',
                'min:1',
                'max:'. 9 - count($this->images),
            ];

            $rules['newImages.*'] = [
                'image',
                'mimes:jpeg,jpg,png',
                'max:1024',
            ];
        }

        return $rules;
    }

    public function validationAttributes()
    {
        return [
            'thumbnail' => 'Gambar utama produk',
            'newThumbnail' => 'Gambar utama produk baru',
            'name' => 'Nama produk',
            'subcategoryId' => 'Subkategori produk',
            'mainSku' => 'SKU utama produk',
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
        $this->subcategoryId = $product->subcategory_id;
        $this->mainSku = $product->main_sku;
        $this->description = $product->description;
        $this->isActive = $product->is_active;
        $this->warranty = $product->warranty;
        $this->material = $product->material;
        $this->weight = $product->weight;
        $this->package = $product->package;
        $this->power = $product->power ? $product->power : null;
        $this->voltage = $product->voltage ? $product->voltage : null;

        $this->setImages($product);
        $this->setDimension($product);
        $this->setVariation($product);
    }

    private function setImages($product)
    {
        $this->thumbnail = $product->images()->thumbnail()->first();
        $this->images = $product->images()->nonThumbnail()->get();
    }

    private function setDimension($product)
    {
        $dimension = $product->dimension;
        $cleanDimension = str_replace(['cm', ' '], '', $dimension);
        [$length, $width, $height] = explode('x', $cleanDimension);
        $this->length = $length;
        $this->width = $width;
        $this->height = $height;
    }

    private function setVariation($product)
    {
        if ($product->variants->count() === 1) {
            $baseVariant = $product->variants->first();
            $this->price = number_format($baseVariant->price, 0, '.', '');
            $this->priceDiscount = $baseVariant->price_discount ? number_format($baseVariant->price_discount, 0, '.', '') : null;
            $this->stock = $baseVariant->stock;
        } else {
            $this->variation = [
                'name' => $this->product->variants->first()->combinations->first()->variationVariant->variation->name,
                'variants' => $this->product->variants->map(function ($variant) {
                    return [
                        'name' => $variant->combinations->first()->variationVariant->name,
                        'price' => number_format($variant->price, 0, '.', ''),
                        'priceDiscount' => $variant->price_discount ? number_format($variant->price_discount ?? 0, 0, '.', '') : null,
                        'stock' => (int) $variant->stock,
                        'variantSku' => $variant->variant_sku,
                        'isVariantActive' => (bool) $variant->is_active,
                    ];
                })->toArray(),
            ];
        }
    }
}
