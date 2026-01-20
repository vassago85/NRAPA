<?php

namespace App\Livewire\Components;

use App\Models\Calibre;
use App\Models\CalibreRequest;
use Livewire\Component;
use Livewire\Attributes\Modelable;

class CalibreSelector extends Component
{
    #[Modelable]
    public ?int $calibreId = null;

    public ?string $categoryFilter = null;
    public ?string $ignitionFilter = null;

    public string $search = '';
    public bool $showDropdown = false;
    public bool $showRequestModal = false;

    // Request form fields
    public string $requestName = '';
    public string $requestCategory = 'rifle';
    public string $requestIgnition = 'centerfire';
    public string $requestReason = '';

    protected $listeners = ['calibreApproved' => '$refresh'];

    public function updatedSearch(): void
    {
        $this->showDropdown = strlen($this->search) >= 1;
    }

    public function selectCalibre(int $id): void
    {
        $calibre = Calibre::find($id);
        if ($calibre) {
            $this->calibreId = $calibre->id;
            $this->search = $calibre->name;
            $this->showDropdown = false;
            $this->dispatch('calibre-selected', calibreId: $calibre->id);
        }
    }

    public function clearSelection(): void
    {
        $this->calibreId = null;
        $this->search = '';
        $this->dispatch('calibre-selected', calibreId: null);
    }

    public function openRequestModal(): void
    {
        $this->requestName = $this->search;
        $this->requestCategory = $this->categoryFilter ?? 'rifle';
        $this->requestIgnition = $this->ignitionFilter ?? 'centerfire';
        $this->requestReason = '';
        $this->showDropdown = false;
        $this->showRequestModal = true;
    }

    public function submitRequest(): void
    {
        $this->validate([
            'requestName' => ['required', 'string', 'max:255'],
            'requestCategory' => ['required', 'in:handgun,rifle,shotgun,other'],
            'requestIgnition' => ['required', 'in:rimfire,centerfire'],
            'requestReason' => ['nullable', 'string', 'max:1000'],
        ]);

        // Check if this calibre already exists (case-insensitive)
        $existingCalibre = Calibre::whereRaw('LOWER(name) = ?', [strtolower($this->requestName)])->first();
        if ($existingCalibre) {
            $this->addError('requestName', 'This calibre already exists. Please search for "' . $existingCalibre->name . '".');
            return;
        }

        // Check if there's already a pending request for this calibre
        $existingRequest = CalibreRequest::where('status', 'pending')
            ->whereRaw('LOWER(name) = ?', [strtolower($this->requestName)])
            ->first();
        
        if ($existingRequest) {
            $this->addError('requestName', 'A request for this calibre is already pending review.');
            return;
        }

        CalibreRequest::create([
            'user_id' => auth()->id(),
            'name' => $this->requestName,
            'category' => $this->requestCategory,
            'ignition_type' => $this->requestIgnition,
            'reason' => $this->requestReason ?: null,
        ]);

        $this->showRequestModal = false;
        $this->search = '';
        
        session()->flash('calibre-request-success', 'Your calibre request has been submitted for admin approval.');
    }

    public function getFilteredCalibresProperty()
    {
        return Calibre::query()
            ->active()
            ->when($this->categoryFilter, fn($q) => $q->forCategory($this->categoryFilter))
            ->when($this->ignitionFilter, fn($q) => $q->forIgnitionType($this->ignitionFilter))
            ->when($this->search, fn($q) => $q->search($this->search))
            ->ordered()
            ->limit(20)
            ->get();
    }

    public function getSelectedCalibreProperty()
    {
        return $this->calibreId ? Calibre::find($this->calibreId) : null;
    }

    public function mount(): void
    {
        if ($this->calibreId) {
            $calibre = Calibre::find($this->calibreId);
            if ($calibre) {
                $this->search = $calibre->name;
            }
        }
    }

    public function render()
    {
        return view('livewire.components.calibre-selector', [
            'calibres' => $this->filteredCalibres,
            'selectedCalibre' => $this->selectedCalibre,
            'categoryOptions' => Calibre::getCategoryOptions(),
            'ignitionOptions' => Calibre::getIgnitionTypeOptions(),
        ]);
    }
}
