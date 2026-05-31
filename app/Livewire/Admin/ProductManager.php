<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;

class ProductManager extends AdminManager
{
    public array $form = [
        'name_en' => '',
        'name_fr' => '',
        'desc_en' => '',
        'desc_fr' => '',
        'category_id' => '',
        'unit_id' => '',
    ];

    public string $filterCategory = '';

    public string $filterUnit = '';

    public string $sortBy = 'name';

    public string $sortDir = 'asc';

    protected function modelClass(): string
    {
        return Product::class;
    }

    public function rules(): array
    {
        return [
            'form.name_en' => ['required', 'string', 'max:200'],
            'form.name_fr' => ['required', 'string', 'max:200'],
            'form.desc_en' => ['nullable', 'string', 'max:1000'],
            'form.desc_fr' => ['nullable', 'string', 'max:1000'],
            'form.category_id' => ['required', 'exists:categories,id'],
            'form.unit_id' => ['nullable', 'exists:units,id'],
        ];
    }

    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatedFilterUnit(): void
    {
        $this->resetPage();
    }

    public function changeSort(string $column): void
    {
        $this->sortDir = ($this->sortBy === $column && $this->sortDir === 'asc') ? 'desc' : 'asc';
        $this->sortBy = $column;
        $this->resetPage();
    }

    protected function resetForm(): void
    {
        $this->form = [
            'name_en' => '',
            'name_fr' => '',
            'desc_en' => '',
            'desc_fr' => '',
            'category_id' => '',
            'unit_id' => '',
        ];
    }

    protected function fillForm(int $id): void
    {
        $product = Product::findOrFail($id);
        $this->form = [
            'name_en' => $product->getRawTranslations('name')['en'] ?? '',
            'name_fr' => $product->getRawTranslations('name')['fr'] ?? '',
            'desc_en' => $product->getRawTranslations('description')['en'] ?? '',
            'desc_fr' => $product->getRawTranslations('description')['fr'] ?? '',
            'category_id' => $product->category_id,
            'unit_id' => $product->unit_id ?? '',
        ];
    }

    protected function persist(): void
    {
        $data = [
            'name' => ['en' => $this->form['name_en'], 'fr' => $this->form['name_fr']],
            'description' => ['en' => $this->form['desc_en'], 'fr' => $this->form['desc_fr']],
            'category_id' => $this->form['category_id'],
            'unit_id' => $this->form['unit_id'] ?: null,
        ];

        $this->editingId
            ? Product::whereKey($this->editingId)->update($data)
            : Product::create($data);
    }

    public function render()
    {
        $query = Product::query()
            ->with(['category', 'unit'])
            ->withCount('priceEstimates');

        if ($this->search) {
            $query->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?",
                ["%{$this->search}%"]
            );
        }

        if ($this->filterCategory) {
            $query->where('category_id', $this->filterCategory);
        }

        if ($this->filterUnit) {
            $this->filterUnit === 'none'
                ? $query->whereNull('unit_id')
                : $query->where('unit_id', $this->filterUnit);
        }

        match ($this->sortBy) {
            'category' => $query->orderBy(
                Category::select(\DB::raw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))"))
                    ->whereColumn('categories.id', 'products.category_id')
                    ->limit(1),
                $this->sortDir
            ),
            'unit' => $query->orderBy(
                Unit::select('symbol')
                    ->whereColumn('units.id', 'products.unit_id')
                    ->limit(1),
                $this->sortDir
            ),
            'estimates' => $query->orderBy('price_estimates_count', $this->sortDir),
            default => $query->orderByRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) {$this->sortDir}"
            ),
        };

        return view('livewire.admin.product-manager', [
            'products' => $query->paginate(20),
            'categories' => Category::orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))")->get(),
            'units' => Unit::orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))")->get(),
        ])->layout('layouts.admin');
    }
}
