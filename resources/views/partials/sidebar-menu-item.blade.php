@props(['item', 'level' => 0])

@php
    $user = auth()->user();
    $hasAccess = \App\Helpers\SidebarMenu::userCanAccess($item);
    
    $route = $item['route'] ?? null;
    // Handle callable routes (for dynamic route selection)
    if (is_callable($route)) {
        $route = $route();
    }
    $routeParams = $item['route_params'] ?? null;
    $isActive = $hasAccess && $route ? \App\Helpers\SidebarMenu::isRouteActive($route, $routeParams) : false;
    $hasChildren = !empty($item['children'] ?? []);
    $isCollapsible = $item['collapsible'] ?? false;
    $icon = $item['icon'] ?? null;
    $iconPath = $icon ? \App\Helpers\SidebarMenu::getIcon($icon) : '';
    
    // Get pending count from item data or calculate
    $pendingCount = $item['pending_count'] ?? 0;
    
    // Collapsible state from localStorage
    $collapseKey = 'sidebar_' . md5($route ?? 'menu') . '_' . ($level ?? 0);
    
    // Check if any child route is active
    $childActive = false;
    if ($hasChildren && $hasAccess) {
        foreach ($item['children'] ?? [] as $child) {
            $childRoute = $child['route'] ?? null;
            $childParams = $child['route_params'] ?? null;
            if ($childRoute && \App\Helpers\SidebarMenu::isRouteActive($childRoute, $childParams)) {
                $childActive = true;
                break;
            }
        }
    }
    
    $defaultOpen = $isActive || $childActive;
@endphp

@if($hasAccess)
@if($hasChildren && $isCollapsible)
    {{-- Collapsible Group --}}
    <div x-data="{ 
        open: {{ $defaultOpen ? 'true' : 'false' }},
        init() {
            const stored = localStorage.getItem('{{ $collapseKey }}');
            if (stored !== null) {
                this.open = stored === 'true';
            }
            this.$watch('open', value => {
                localStorage.setItem('{{ $collapseKey }}', value);
            });
        }
    }">
        <button 
            @click="open = !open"
            type="button"
            :aria-expanded="open"
            aria-controls="collapse-{{ md5($route ?? 'menu') }}"
            class="w-full flex items-center justify-between gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ $isActive || $childActive ? 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
        >
            <span class="flex items-center gap-3">
                @if($iconPath)
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {!! $iconPath !!}
                </svg>
                @endif
                {{ $item['label'] }}
                @if($pendingCount > 0)
                <span class="px-1.5 py-0.5 text-xs bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded">{{ $pendingCount }}</span>
                @endif
            </span>
            <svg class="w-4 h-4 transition-transform flex-shrink-0" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div 
            x-show="open" 
            x-collapse
            id="collapse-{{ md5($route ?? 'menu') }}"
            class="ml-4 mt-1 space-y-1"
        >
            @foreach($item['children'] as $child)
                @php
                    $childRoute = $child['route'] ?? null;
                    // Handle callable routes (for dynamic route selection)
                    if (is_callable($childRoute)) {
                        $childRoute = $childRoute();
                    }
                    $childParams = $child['route_params'] ?? null;
                    $childActive = $childRoute ? \App\Helpers\SidebarMenu::isRouteActive($childRoute, $childParams) : false;
                    $childIcon = $child['icon'] ?? null;
                    $childIconPath = $childIcon ? \App\Helpers\SidebarMenu::getIcon($childIcon) : '';
                    
                    // Get pending count for child
                    $childPending = $child['pending_count'] ?? 0;
                @endphp
                <a 
                    href="{{ route($childRoute, $childParams ?? []) }}" 
                    wire:navigate 
                    @click="sidebarOpen = false"
                    class="flex items-center justify-between gap-3 px-3 py-2 text-sm rounded-lg transition-colors {{ $childActive ? 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
                >
                    <span>{{ $child['label'] }}</span>
                    @if($childPending > 0)
                    <span class="px-1.5 py-0.5 text-xs bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded">{{ $childPending }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
@else
    {{-- Regular Menu Item --}}
    <a 
        href="{{ $route ? route($route, $routeParams ?? []) : '#' }}" 
        wire:navigate 
        @click="sidebarOpen = false"
        class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ $isActive ? 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
    >
        @if($iconPath)
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            {!! $iconPath !!}
        </svg>
        @endif
        {{ $item['label'] }}
        @if($pendingCount > 0)
        <span class="ml-auto px-1.5 py-0.5 text-xs bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded">{{ $pendingCount }}</span>
        @endif
    </a>
@endif
@endif
