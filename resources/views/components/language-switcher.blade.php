@php
    $current = app()->getLocale();
    $locales = config('app.available_locales', []);
    $currentMeta = $locales[$current] ?? ['name' => 'English', 'native' => 'English', 'flag' => '🌐'];
@endphp

<div class="fi-language-switcher relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <button
        type="button"
        @click="open = ! open"
        class="fi-dropdown-trigger inline-flex items-center justify-center gap-1 fi-icon-btn h-9 w-9 rounded-full hover:bg-gray-100 dark:hover:bg-white/5 transition-colors outline-none focus:outline-none"
        aria-label="Language"
    >
        <span class="text-lg leading-none" aria-hidden="true">{{ $currentMeta['flag'] }}</span>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-75"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 z-50 mt-2 min-w-[11rem] origin-top-right rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 shadow-lg ring-1 ring-black/5 dark:ring-white/10 focus:outline-none"
        role="menu"
        tabindex="-1"
        style="display: none;"
    >
        <div class="py-1">
            @foreach ($locales as $code => $meta)
                @php
                    $isActive = $code === $current;
                @endphp
                <a
                    href="{{ route('locale.switch', ['locale' => $code]) }}"
                    role="menuitem"
                    class="fi-dropdown-list-item flex items-center gap-2 w-full px-3 py-2 text-sm transition-colors
                        {{ $isActive
                            ? 'bg-gray-50 dark:bg-white/5 text-primary-600 dark:text-primary-400 font-medium'
                            : 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5'
                        }}"
                >
                    <span class="text-base leading-none" aria-hidden="true">{{ $meta['flag'] }}</span>
                    <span class="flex-1 truncate">{{ $meta['native'] }}</span>
                    @if ($isActive)
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>
