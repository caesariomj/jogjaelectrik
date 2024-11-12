<?php

namespace App\Livewire\Forms;

use App\Models\Subcategory;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SubcategoryForm extends Form
{
    public ?Subcategory $subcategory = null;

    #[Validate]
    public string $categoryId = '';

    #[Validate]
    public string $name = '';

    public function rules()
    {
        return [
            'categoryId' => [
                'required',
                'exists:categories,id',
            ],
            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                is_null($this->subcategory) ? 'unique:subcategories,name' : 'unique:subcategories,name,'.$this->subcategory->id,
            ],
        ];
    }

    public function validationAttributes()
    {
        return [
            'categoryId' => 'Kategori',
            'name' => 'Nama subkategori',
        ];
    }

    public function setSubcategory($subcategory)
    {
        $this->subcategory = $subcategory;
        $this->categoryId = $subcategory->category_id;
        $this->name = $subcategory->name;
    }
}
