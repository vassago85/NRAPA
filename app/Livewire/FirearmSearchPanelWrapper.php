<?php

namespace App\Livewire;

use Livewire\Component;

class FirearmSearchPanelWrapper extends Component
{
    public ?array $initialData = null;

    public string $wireModelPrefix = 'firearmPanelData';

    public function mount(?array $initialData = null, string $wireModelPrefix = 'firearmPanelData'): void
    {
        $this->initialData = $initialData;
        $this->wireModelPrefix = $wireModelPrefix;
    }

    /**
     * Get data from the nested FirearmSearchPanel component.
     */
    public function getFirearmData(): array
    {
        // This will be called by parent component
        $panel = \Livewire\Livewire::find('firearm-search-panel-'.$this->wireModelPrefix);
        if ($panel) {
            return $panel->getData();
        }

        return [];
    }

    public function render()
    {
        return view('livewire.firearm-search-panel-wrapper');
    }
}
