<?php

namespace App\Livewire\Admin;

use App\Models\Category;

class CategoryManager extends AdminManager
{
    public array $form = ['name_en' => '', 'name_fr' => '', 'color' => '#6366f1'];

    protected function modelClass(): string
    {
        return Category::class;
    }

    public function rules(): array
    {
        return [
            'form.name_en' => ['required', 'string', 'max:100'],
            'form.name_fr' => ['required', 'string', 'max:100'],
            'form.color'   => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    protected function resetForm(): void
    {
        $this->form = ['name_en' => '', 'name_fr' => '', 'color' => '#6366f1'];
    }

    protected function fillForm(int $id): void
    {
        $category   = Category::findOrFail($id);
        $this->form = [
            'name_en' => $category->getRawTranslations('name')['en'] ?? '',
            'name_fr' => $category->getRawTranslations('name')['fr'] ?? '',
            'color'   => $category->color,
        ];
    }

    protected function persist(): void
    {
        $data = [
            'name'  => ['en' => $this->form['name_en'], 'fr' => $this->form['name_fr']],
            'color' => $this->form['color'],
        ];

        $this->editingId
            ? Category::whereKey($this->editingId)->update($data)
            : Category::create($data);
    }

    public function render()
    {
        $categories = Category::query()
            ->withCount('products')
            ->when($this->search, fn($q) =>
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$this->search}%"])
            )
            ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))")
            ->paginate(15);

        return view('livewire.admin.category-manager', compact('categories'))
            ->layout('layouts.admin');
    }
}