@props([
    'options',              // Collection/array of objects/arrays with id + name
    'wireModel',            // Livewire model property name for the selected ID (nullable int)
    'wireModelCustom' => null, // Optional Livewire property for the free-text fallback value
    'placeholder' => 'Search...',
    'disabled' => false,
    'live' => false,        // Whether to use wire:model.live on the hidden id input
    'allowCustom' => null,  // If null, auto-enabled when wireModelCustom is provided
])

@php
    $items = collect($options)->map(fn ($o) => [
        'id'   => is_array($o) ? $o['id']   : $o->id,
        'name' => is_array($o) ? $o['name'] : $o->name,
    ])->values()->toArray();

    $idAttr = $live ? 'wire:model.live' : 'wire:model';
    $allowCustom = $allowCustom ?? ($wireModelCustom !== null);

    $componentId = 'sel_' . substr(md5($wireModel . '|' . ($wireModelCustom ?? '')), 0, 8);
@endphp

<div
    x-data="searchableSelect({
        items: @js($items),
        hasCustom: {{ $wireModelCustom ? 'true' : 'false' }},
        allowCustom: @js((bool) $allowCustom),
        disabled: @js((bool) $disabled),
    })"
    x-on:click.outside="handleClickOutside()"
    class="relative"
    id="{{ $componentId }}"
>
    {{-- Hidden inputs carry the Livewire bindings --}}
    <input type="hidden" x-ref="hiddenId" {{ $idAttr }}="{{ $wireModel }}" />
    @if($wireModelCustom)
        <input type="hidden" x-ref="hiddenCustom" wire:model="{{ $wireModelCustom }}" />
    @endif

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
                x-on:mousedown.prevent="selectItem(item)"
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
                x-on:mousedown.prevent="commitCustom()"
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

@once
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('searchableSelect', (config) => ({
        items: config.items || [],
        allowCustom: config.allowCustom,
        disabled: config.disabled,
        hasCustom: !!config.hasCustom,

        search: '',
        open: false,
        highlightIndex: -1,
        selectedId: null,
        customValue: '',

        get idRef()     { return this.$refs.hiddenId || null; },
        get customRef() { return this.hasCustom ? (this.$refs.hiddenCustom || null) : null; },

        init() {
            // Hydrate from the hidden inputs (Livewire source of truth)
            this.selectedId = this.idRef?.value ? String(this.idRef.value) : null;
            if (this.customRef) {
                this.customValue = this.customRef.value ?? '';
            }
            this.search = this.currentLabel();

            // Watch the hidden id input for server-side changes (Livewire rerenders)
            if (this.idRef) {
                this._idObserver = new MutationObserver(() => {
                    const v = this.idRef.value ? String(this.idRef.value) : null;
                    if (v !== this.selectedId) {
                        this.selectedId = v;
                        if (!this.open) this.search = this.currentLabel();
                    }
                });
                this._idObserver.observe(this.idRef, { attributes: true, attributeFilter: ['value'] });
            }
        },

        get hasValue() {
            return !!this.selectedId || (this.allowCustom && !!this.customValue);
        },

        get filtered() {
            const s = (this.search || '').trim().toLowerCase();
            if (!s) return this.items;
            return this.items.filter(i => (i.name || '').toLowerCase().includes(s));
        },

        get showCustomOption() {
            const s = (this.search || '').trim();
            if (!s) return false;
            // Only show if the search term isn't already an exact match
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
            // Select all so typing replaces
            this.$nextTick(() => this.$refs.input.select?.());
        },

        onInput() {
            if (this.disabled) return;
            // Typing means the user is searching; clear the committed id so old label doesn't stick
            if (this.selectedId) {
                this.selectedId = null;
                this.writeId(null);
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

            const typed = this.search.trim();
            if (!typed) {
                // Nothing typed — restore label from current selection
                this.search = this.currentLabel();
                return;
            }

            // Exact match? Auto-select it.
            const exact = this.items.find(i => (i.name || '').toLowerCase() === typed.toLowerCase());
            if (exact) {
                this.selectItem(exact);
                return;
            }

            if (this.allowCustom) {
                this.commitCustom();
                return;
            }

            // Otherwise restore to current label and throw away the typed text
            this.search = this.currentLabel();
        },

        selectItem(item) {
            this.selectedId = String(item.id);
            this.writeId(item.id);
            if (this.allowCustom) {
                this.customValue = '';
                this.writeCustom('');
            }
            this.search = item.name;
            this.open = false;
            this.highlightIndex = -1;
            this.$nextTick(() => this.$refs.input.blur?.());
        },

        commitCustom() {
            const typed = this.search.trim();
            if (!typed || !this.allowCustom) return;
            this.selectedId = null;
            this.writeId(null);
            this.customValue = typed;
            this.writeCustom(typed);
            this.search = typed;
            this.open = false;
            this.highlightIndex = -1;
            this.$nextTick(() => this.$refs.input.blur?.());
        },

        clear() {
            this.selectedId = null;
            this.writeId(null);
            if (this.allowCustom) {
                this.customValue = '';
                this.writeCustom('');
            }
            this.search = '';
            this.$nextTick(() => this.$refs.input.focus?.());
        },

        writeId(value) {
            if (!this.idRef) return;
            this.idRef.value = value == null ? '' : String(value);
            this.idRef.dispatchEvent(new Event('input', { bubbles: true }));
            this.idRef.dispatchEvent(new Event('change', { bubbles: true }));
        },

        writeCustom(value) {
            if (!this.customRef) return;
            this.customRef.value = value ?? '';
            this.customRef.dispatchEvent(new Event('input', { bubbles: true }));
            this.customRef.dispatchEvent(new Event('change', { bubbles: true }));
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
