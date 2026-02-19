<?php

use App\Models\Bullet;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add Bullet - Admin')] class extends Component {
    public string $manufacturer = '';
    public string $brand_line = '';
    public string $bullet_label = '';
    public string $caliber_label = '';
    public ?int $weight_gr = null;

    public ?float $diameter_in = null;
    public ?float $diameter_mm = null;
    public ?float $length_in = null;
    public ?float $length_mm = null;

    public ?float $bc_g1 = null;
    public ?float $bc_g7 = null;
    public ?string $bc_reference = '';

    public string $construction = 'cup_and_core';
    public string $intended_use = 'match';

    public ?string $twist_note = '';
    public ?string $sku_or_part_no = '';
    public string $source_url = '';
    public string $status = 'active';
    public ?string $last_verified_at = null;

    public function mount(): void
    {
        $this->last_verified_at = now()->format('Y-m-d\TH:i');
    }

    public function updatedDiameterIn($value): void
    {
        if ($value && is_numeric($value)) {
            $this->diameter_mm = Bullet::inToMm((float) $value);
        }
    }

    public function updatedDiameterMm($value): void
    {
        if ($value && is_numeric($value)) {
            $this->diameter_in = Bullet::mmToIn((float) $value);
        }
    }

    public function updatedLengthIn($value): void
    {
        if ($value && is_numeric($value)) {
            $this->length_mm = Bullet::inToMm((float) $value);
        } else {
            $this->length_mm = null;
        }
    }

    public function updatedLengthMm($value): void
    {
        if ($value && is_numeric($value)) {
            $this->length_in = Bullet::mmToIn((float) $value);
        } else {
            $this->length_in = null;
        }
    }

    public function updatedCaliberLabel($value): void
    {
        $diameters = Bullet::diameterForCaliber($value);
        if ($diameters) {
            $this->diameter_in = $diameters['in'];
            $this->diameter_mm = $diameters['mm'];
        }
    }

    public function save(): void
    {
        $this->validate(Bullet::validationRules());

        Bullet::create([
            'manufacturer' => $this->manufacturer,
            'brand_line' => $this->brand_line,
            'bullet_label' => $this->bullet_label,
            'caliber_label' => $this->caliber_label,
            'weight_gr' => $this->weight_gr,
            'diameter_in' => $this->diameter_in,
            'diameter_mm' => $this->diameter_mm,
            'length_in' => $this->length_in ?: null,
            'length_mm' => $this->length_mm ?: null,
            'bc_g1' => $this->bc_g1 ?: null,
            'bc_g7' => $this->bc_g7 ?: null,
            'bc_reference' => $this->bc_reference ?: null,
            'construction' => $this->construction,
            'intended_use' => $this->intended_use,
            'twist_note' => $this->twist_note ?: null,
            'sku_or_part_no' => $this->sku_or_part_no ?: null,
            'source_url' => $this->source_url,
            'status' => $this->status,
            'last_verified_at' => $this->last_verified_at,
        ]);

        session()->flash('success', 'Bullet added to database.');
        $this->redirect(route('admin.bullet-database.index'), navigate: true);
    }

    public function with(): array
    {
        $manufacturers = Bullet::select('manufacturer')->distinct()->orderBy('manufacturer')->pluck('manufacturer');
        $brandLines = Bullet::select('brand_line')->distinct()->orderBy('brand_line')->pluck('brand_line');

        return [
            'manufacturers' => $manufacturers,
            'brandLines' => $brandLines,
            'constructionTypes' => Bullet::constructionTypes(),
            'intendedUses' => Bullet::intendedUses(),
            'statuses' => Bullet::statuses(),
            'caliberDiameters' => Bullet::caliberDiameters(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 p-6">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Add Bullet</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Add a new bullet to the database</p>
    </x-slot>
    
    <div>
        <a href="{{ route('admin.bullet-database.index') }}" wire:navigate class="text-zinc-400 hover:text-zinc-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
    </div>

    <form wire:submit="save" class="max-w-4xl space-y-6">
        @include('pages.admin.bullet-database._form')

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-nrapa-blue px-6 py-2.5 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                Add Bullet
            </button>
            <a href="{{ route('admin.bullet-database.index') }}" wire:navigate
               class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-6 py-2.5 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                Cancel
            </a>
        </div>
    </form>
</div>
