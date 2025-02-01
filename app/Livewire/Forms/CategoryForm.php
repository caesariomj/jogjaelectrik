<?php

namespace App\Livewire\Forms;

use App\Models\Category;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CategoryForm extends Form
{
    public ?Category $category = null;

    #[Validate]
    public string $name = '';

    public bool $isPrimary = false;

    protected function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                is_null($this->category) ? 'unique:categories,name' : 'unique:categories,name,'.$this->category->id,
            ],
            'isPrimary' => [
                'boolean',
            ],
        ];
    }

    protected function validationAttributes()
    {
        return [
            'name' => 'Nama kategori',
            'isPrimary' => 'Kategori utama',
        ];
    }

    public function setCategory($category)
    {
        $this->category = $category;
        $this->name = $category->name;
        $this->isPrimary = $category->is_primary;
    }
}
