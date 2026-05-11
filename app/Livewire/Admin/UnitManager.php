<?php

namespace App\Livewire\Admin;

use App\Models\Unit;

class UnitManager extends AdminManager
{
    public array $form = ['name_en' => '', 'name_fr' => '', 'symbol' => ''];

    protected function modelClass(): string
    {
        return Unit::class;
    }

    public function rules(): array
    {
        return [
            'form.name_en' => ['required', 'string', 'max:100'],
            'form.name_fr' => ['required', 'string', 'max:100'],
            'form.symbol'  => ['required', 'string', 'max:20'],
        ];
    }

    protected function resetForm(): void
    {
        $this->form = ['name_en' => '', 'name_fr' => '', 'symbol' => ''];
    }

    protected function fillForm(int $id): void
    {
        $unit       = Unit::findOrFail($id);
        $this->form = [
            'name_en' => $unit->getRawTranslations('name')['en'] ?? '',
            'name_fr' => $unit->getRawTranslations('name')['fr'] ?? '',
            'symbol'  => $unit->symbol,
        ];
    }

    protected function persist(): void
    {
        $data = [
            'name'   => ['en' => $this->form['name_en'], 'fr' => $this->form['name_fr']],
            'symbol' => $this->form['symbol'],
        ];

        $this->editingId
            ? Unit::whereKey($this->editingId)->update($data)
            : Unit::create($data);
    }

    public function render()
    {
        $units = Unit::query()
            ->withCount('products')
            ->when($this->search, fn($q) =>
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$this->search}%"])
            )
            ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))")
            ->paginate(15);

        return view('livewire.admin.unit-manager', compact('units'))
            ->layout('layouts.admin');
    }
}