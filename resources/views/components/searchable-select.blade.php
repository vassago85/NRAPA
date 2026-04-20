@props([
    'options',              // Collection/array of objects/arrays with id + name
    'wireModel',            // Livewire model property name for the selected ID (nullable int)
    'wireModelCustom' => null, // Optional Livewire property for the free-text fallback value
    'placeholder' => 'Search...',
    'disabled' => false,
    'live' => false,        // When true, selecting an option triggers a Livewire re-render
    'allowCustom' => null,  // If null, auto-enabled when wireModelCustom is provided
])

@php
    $items = collect($options)->map(fn ($o) => [
        'id'   => is_array($o) ? $o['id']   : $o->id,
        'name' => is_array($o) ? $o['name'] : $o->name,
    ])->values()->toArray();

    $allowCustom = $allowCustom ?? ($wireModelCustom !== null);

    $componentId = 'sel_' . substr(md5($wireModel . '|' . ($wireModelCustom ?? '')), 0, 8);
@endphp

{{-- Items JSON lives OUTSIDE wire:ignore so Livewire can update it when the source collection changes --}}
<div>
    <script type="application/json" data-searchable-items="{{ $componentId }}">@json($items)</script>

<div wire:ignore
    x-data='searchableSelect(@json([
        "componentId" => $componentId,
        "allowCustom" => (bool) $allowCustom,
        "disabled" => (bool) $disabled,
        "hasCustom" => $wireModelCustom !== null,
        "wireModel" => $wireModel,
        "wireModelCustom" => $wireModelCustom,
        "liveUpdate" => (bool) $live,
        "initialItems" => $items,
    ]))'
    x-on:click.outside="handleClickOutside()"
    class="relative"
    id="{{ $componentId }}"
>
    <div class="relative">
        <input
            x-ref="input"
            type="text"
            x-model="search"
            :disabled="disabled"
            x-on:focus="onFocus()"
            x-on:input="onInput()"
            x-on:keydown="onKeydown($event)"
            autocomplete="off"
            :placeholder="'{{ $placeholder }}'"
            :class="disabled
                ? 'bg-zinc-100 dark:bg-zinc-800 cursor-not-allowed opacity-60'
                : 'bg-white dark:bg-zinc-700'"
            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 pr-16 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:border-nrapa-blue focus:ring-1 focus:ring-nrapa-blue"
        />

        {{-- Right-side icons --}}
        <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-2">
            <button
                type="button"
                x-show="hasValue && !disabled"
                x-on:click.stop="clear()"
                class="p-0.5 rounded hover:bg-zinc-200 dark:hover:bg-zinc-600"
                title="Clear"
            >
                <svg class="h-4 w-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <svg class="h-4 w-4 text-zinc-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </div>

    {{-- Dropdown list --}}
    <div
        x-show="open"
        x-transition.opacity.duration.150ms
        x-ref="listbox"
        x-cloak
        class="absolute z-50 mt-1 w-full max-h-60 overflow-auto rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 shadow-lg"
    >
        <template x-for="(item, idx) in filtered" :key="'item-' + item.id">
            <button
                type="button"
                x-on:click="selectItem(item)"
                x-on:mousedown.prevent
                x-on:mouseenter="highlightIndex = idx"
                :data-highlighted="highlightIndex === idx ? '' : undefined"
                :class="{
                    'bg-nrapa-blue/10 dark:bg-nrapa-blue/20': highlightIndex === idx,
                    'bg-nrapa-blue/5': String(item.id) === String(selectedId) && highlightIndex !== idx
                }"
                class="w-full text-left px-4 py-2 text-sm text-zinc-800 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center justify-between"
            >
                <span x-text="item.name"></span>
                <svg
                    x-show="String(item.id) === String(selectedId)"
                    class="h-4 w-4 text-nrapa-blue shrink-0"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
        </template>

        {{-- Custom value option --}}
        <template x-if="allowCustom && showCustomOption">
            <button
                type="button"
                x-on:click="commitCustom()"
                x-on:mousedown.prevent
                :class="{ 'bg-nrapa-blue/10 dark:bg-nrapa-blue/20': highlightIndex === filtered.length }"
                x-on:mouseenter="highlightIndex = filtered.length"
                class="w-full text-left px-4 py-2 text-sm text-zinc-800 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center gap-2 border-t border-zinc-100 dark:border-zinc-700"
            >
                <svg class="h-4 w-4 text-nrapa-blue shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Use "<span x-text="search"></span>" as custom value</span>
            </button>
        </template>

        {{-- No results fallback (only when custom is disallowed) --}}
        <div x-show="!allowCustom && filtered.length === 0" class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
            No results found
        </div>
    </div>
</div>
</div>

@once
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('searchableSelect', (config) => ({
        componentId: config.componentId,
        items: Array.isArray(config.initialItems) ? config.initialItems : [],
        allowCustom: !!config.allowCustom,
        disabled: !!config.disabled,
        hasCustom: !!config.hasCustom,
        wireModel: config.wireModel,
        wireModelCustom: config.wireModelCustom,
        liveUpdate: !!config.liveUpdate,

        search: '',
        open: false,
        highlightIndex: -1,
        selectedId: null,
        customValue: '',

        init() {
            // Try to refresh items from the sibling <script> tag (may be updated by Livewire morphs)
            this.reloadItems();

            // Find the Livewire component that owns us
            this._wire = this.findWire();

            // Hydrate initial state from Livewire
            this.syncFromWire();

            // React to Livewire state changes (e.g. server-side updates, resets)
            if (this._wire) {
                try {
                    this._wire.$watch(this.wireModel, () => this.syncFromWire());
                    if (this.hasCustom && this.wireModelCustom) {
                        this._wire.$watch(this.wireModelCustom, () => this.syncFromWire());
                    }
                } catch (e) {}

                // Livewire fires `livewire:morphed` after a server render; refresh items
                // in case the source collection changed (e.g. calibres filtered by type).
                document.addEventListener('livewire:morphed', () => {
                    this.reloadItems();
                    this.syncFromWire();
                });
            }
        },

        reloadItems() {
            const node = document.querySelector('script[data-searchable-items="' + this.componentId + '"]');
            if (!node) return;
            try {
                const parsed = JSON.parse(node.textContent || '[]');
                if (Array.isArray(parsed)) this.items = parsed;
            } catch (e) {}
        },

        findWire() {
            let el = this.$root;
            while (el) {
                if (el.__livewire) return el.__livewire;
                if (el.getAttribute && el.getAttribute('wire:id')) {
                    return window.Livewire?.find(el.getAttribute('wire:id'));
                }
                el = el.parentElement;
            }
            return null;
        },

        getWireValue(prop) {
            if (!this._wire || !prop) return null;
            try {
                return this._wire.get(prop);
            } catch (e) {
                return null;
            }
        },

        setWireValue(prop, value, forceLive = null) {
            if (!this._wire || !prop) return;
            try {
                // Pass `true` to trigger a Livewire re-render (used when this field
                // drives dependent dropdowns like Make → Model). Otherwise update
                // silently so other Alpine/Livewire state isn't disturbed.
                const live = forceLive === null ? this.liveUpdate : forceLive;
                this._wire.set(prop, value, live ? true : false);
            } catch (e) {}
        },

        syncFromWire() {
            const id = this.getWireValue(this.wireModel);
            const newSelected = id ? String(id) : null;
            const changed = newSelected !== this.selectedId;
            this.selectedId = newSelected;

            if (this.hasCustom) {
                this.customValue = this.getWireValue(this.wireModelCustom) || '';
            }

            // Always rewrite the visible text to match committed state, unless the user is
            // actively typing (dropdown open AND we just changed due to our own selectItem)
            if (changed || !this.open) {
                this.search = this.currentLabel();
            }
        },

        get hasValue() {
            return !!this.selectedId || (this.allowCustom && !!this.customValue);
        },

        get filtered() {
            const s = (this.search || '').trim().toLowerCase();
            // If the search string matches the current selection label exactly, show everything
            // (so the user can re-open and see the full list)
            if (!s || s === (this.currentLabel() || '').toLowerCase()) return this.items;
            return this.items.filter(i => (i.name || '').toLowerCase().includes(s));
        },

        get showCustomOption() {
            const s = (this.search || '').trim();
            if (!s) return false;
            return !this.items.some(i => (i.name || '').toLowerCase() === s.toLowerCase());
        },

        currentLabel() {
            if (this.selectedId) {
                const match = this.items.find(i => String(i.id) === String(this.selectedId));
                if (match) return match.name;
            }
            if (this.allowCustom && this.customValue) return this.customValue;
            return '';
        },

        onFocus() {
            if (this.disabled) return;
            this.open = true;
            this.highlightIndex = -1;
            this.$nextTick(() => this.$refs.input.select?.());
        },

        onInput() {
            if (this.disabled) return;
            // Typing means the user is searching; clear the committed id so old label doesn't stick
            if (this.selectedId) {
                this.selectedId = null;
                this.setWireValue(this.wireModel, null);
            }
            if (this.hasCustom && this.customValue) {
                this.customValue = '';
                this.setWireValue(this.wireModelCustom, '');
            }
            this.open = true;
            this.highlightIndex = -1;
        },

        onKeydown(e) {
            if (this.disabled) return;
            if (!this.open && ['ArrowDown', 'Enter'].includes(e.key)) {
                this.open = true;
                return;
            }
            const list = this.filtered;
            const customSlot = this.allowCustom && this.showCustomOption ? 1 : 0;
            const lastIndex = list.length + customSlot - 1;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.highlightIndex = Math.min(this.highlightIndex + 1, lastIndex);
                this.scrollToHighlighted();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.highlightIndex = Math.max(this.highlightIndex - 1, 0);
                this.scrollToHighlighted();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (this.highlightIndex >= 0 && this.highlightIndex < list.length) {
                    this.selectItem(list[this.highlightIndex]);
                } else if (customSlot && this.highlightIndex === list.length) {
                    this.commitCustom();
                } else if (list.length === 1) {
                    this.selectItem(list[0]);
                } else if (this.allowCustom && this.search.trim()) {
                    this.commitCustom();
                }
            } else if (e.key === 'Escape') {
                this.open = false;
                this.search = this.currentLabel();
            } else if (e.key === 'Tab') {
                if (this.allowCustom && this.search.trim() && !this.selectedId) {
                    this.commitCustom();
                }
                this.open = false;
            }
        },

        handleClickOutside() {
            if (!this.open) return;
            this.open = false;

            const typed = (this.search || '').trim();
            if (!typed) {
                this.search = this.currentLabel();
                return;
            }

            // Exact match? Auto-select it
            const exact = this.items.find(i => (i.name || '').toLowerCase() === typed.toLowerCase());
            if (exact) {
                this.selectItem(exact);
                return;
            }

            if (this.allowCustom) {
                this.commitCustom();
                return;
            }

            this.search = this.currentLabel();
        },

        selectItem(item) {
            this.selectedId = String(item.id);
            this.setWireValue(this.wireModel, item.id);
            if (this.allowCustom) {
                this.customValue = '';
                this.setWireValue(this.wireModelCustom, '');
            }
            this.search = item.name;
            this.open = false;
            this.highlightIndex = -1;
            this.$nextTick(() => this.$refs.input.blur?.());
        },

        commitCustom() {
            const typed = (this.search || '').trim();
            if (!typed || !this.allowCustom) return;
            this.selectedId = null;
            this.setWireValue(this.wireModel, null);
            this.customValue = typed;
            this.setWireValue(this.wireModelCustom, typed);
            this.search = typed;
            this.open = false;
            this.highlightIndex = -1;
            this.$nextTick(() => this.$refs.input.blur?.());
        },

        clear() {
            this.selectedId = null;
            this.setWireValue(this.wireModel, null);
            if (this.allowCustom) {
                this.customValue = '';
                this.setWireValue(this.wireModelCustom, '');
            }
            this.search = '';
            this.$nextTick(() => this.$refs.input.focus?.());
        },

        scrollToHighlighted() {
            this.$nextTick(() => {
                const el = this.$refs.listbox?.querySelector('[data-highlighted]');
                if (el) el.scrollIntoView({ block: 'nearest' });
            });
        },
    }));
});
</script>
@endonce
