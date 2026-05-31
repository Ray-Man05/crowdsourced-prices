<?php

namespace App\Livewire\Admin;

use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\WithPagination;

abstract class AdminManager extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $search = '';

    /**
     * The Eloquent model class this manager operates on.
     * Used for direct delete without fetching the model.
     * e.g. return Category::class;
     */
    abstract protected function modelClass(): string;

    /**
     * Reset the form to its empty default state.
     */
    abstract protected function resetForm(): void;

    /**
     * Populate the form fields from the given model ID.
     */
    abstract protected function fillForm(int $id): void;

    /**
     * Create or update the model from the current form state.
     */
    abstract protected function persist(): void;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->editingId = $id;
        $this->fillForm($id);
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();
        $this->persist();
        $this->showModal = false;
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        // Direct DELETE query — no model fetch, no memory allocation
        ($this->modelClass())::whereKey($id)->delete();
        $this->resetPage();
    }
}
