@props([
    'options',          // Collection/array of objects with id + name
    'wireModel',        // Livewire model property name
    'placeholder' => 'Search...',
    'disabled' => false,
    'live' => false,    // Whether to use wire:model.live
])

@php
    $items = collect($options)->map(fn ($o) => [
        'id'   => $o->id ?? $o['id'],
        'name' => $o->name ?? $o['name'],
    ])->values()->toArray();

    $wireAttr = $live ? 'wire:model.live' : 'wire:model';
@endphp

<div
    x-data="{
        open: false,
        search: '',
        highlightIndex: -1,
        items: @js($items),
        disabled: @js((bool) $disabled),
        get filtered() {
            if (!this.search) return this.items;
            const s = this.search.toLowerCase();
            return this.items.filter(i => i.name.toLowerCase().includes(s));
        },
        get selectedId() {
            return this.$refs.hidden?.value || null;
        },
        get selectedLabel() {
            if (!this.selectedId) return '';
            const found = this.items.find(i => String(i.id) === String(this.selectedId));
            return found ? found.name : '';
        },
        select(item) {
            this.$refs.hidden.value = item.id;
            this.$refs.hidden.dispatchEvent(new Event('input', { bubbles: true }));
            this.search = '';
            this.open = false;
            this.highlightIndex = -1;
        },
        clear() {
            this.$refs.hidden.value = '';
            this.$refs.hidden.dispatchEvent(new Event('input', { bubbles: true }));
            this.search = '';
            this.$nextTick(() => this.$refs.input.focus());
        },
        onInputFocus() {
            if (this.disabled) return;
            this.open = true;
            this.highlightIndex = -1;
        },
        onKeydown(e) {
            if (!this.open) { this.open = true; return; }
            const list = this.filtered;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.highlightIndex = Math.min(this.highlightIndex + 1, list.length - 1);
                this.scrollToHighlighted();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.highlightIndex = Math.max(this.highlightIndex - 1, 0);
                this.scrollToHighlighted();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (this.highlightIndex >= 0 && list[this.highlightIndex]) {
                    this.select(list[this.highlightIndex]);
                }
            } else if (e.key === 'Escape') {
                this.open = false;
                this.search = '';
                this.$refs.input.blur();
            } else if (e.key === 'Tab') {
                this.open = false;
                this.search = '';
            }
        },
        scrollToHighlighted() {
            this.$nextTick(() => {
                const el = this.$refs.listbox?.querySelector('[data-highlighted]');
                if (el) el.scrollIntoView({ block: 'nearest' });
            });
        }
    }"
    x-on:click.outside="open = false; search = ''"
    class="relative"
>
    {{-- Hidden input carries the actual wire:model binding --}}
    <input type="hidden" x-ref="hidden" {{ $wireAttr }}="{{ $wireModel }}" />

    {{-- Visible search input --}}
    <div class="relative">
        <input
            x-ref="input"
            type="text"
            :disabled="disabled"
            x-model="search"
            x-on:focus="onInputFocus()"
            x-on:keydown="onKeydown($event)"
            autocomplete="off"
            :placeholder="selectedId ? '' : '{{ $placeholder }}'"
            :class="disabled
                ? 'bg-zinc-100 dark:bg-zinc-800 cursor-not-allowed opacity-60'
                : 'bg-white dark:bg-zinc-700'"
            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 pr-16 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:border-nrapa-blue focus:ring-1 focus:ring-nrapa-blue"
        />

        {{-- Show selected value over input when not searching --}}
        <div
            x-show="selectedId && !open"
            x-on:click="$refs.input.focus()"
            class="absolute inset-0 flex items-center px-4 cursor-text pointer-events-auto"
        >
            <span class="text-sm text-zinc-900 dark:text-white truncate pr-16" x-text="selectedLabel"></span>
        </div>

        {{-- Right-side icons --}}
        <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-2">
            <button
                type="button"
                x-show="selectedId"
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
        class="absolute z-50 mt-1 w-full max-h-60 overflow-auto rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 shadow-lg"
    >
        <div x-show="filtered.length === 0" class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
            No results found
        </div>

        <template x-for="(item, idx) in filtered" :key="item.id">
            <button
                type="button"
                x-on:click="select(item)"
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
    </div>
</div>
