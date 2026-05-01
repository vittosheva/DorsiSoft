<div id="sidebar-search" class="w-full m-0 p-4 bg-white"
    @if ($isSidebarCollapsibleOnDesktop)
        x-bind:class="$store.sidebar.isOpen ? 'block' : 'hidden'"
    @endif
>
    {{-- Search Input --}}
    <x-filament::input.wrapper class="relative search-menu">
        <span class="absolute left-1 top-1/2 transform -translate-y-1/2 text-gray-600 dark:text-gray-400 dark:border-gray-600 text-xs px-2 flex items-center justify-center gap-1 py-1 rounded">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </span>
        
        <div x-data="sidebarSearch()" class="relative z-0">
            <x-filament::input
                id="search-menu-items"
                type="text"
                placeholder="{{ __('Search menu...') }}"
                x-ref="search"
                x-on:input.debounce.300ms="filterItems($event.target.value)"
                x-on:keydown.escape="clearSearch"
                x-on:keydown.meta.j.prevent.document="$refs.search.focus()"
                inline-suffix=""
                class="pr-14! pl-10!"
            />
            
            <button 
                x-show="hasSearchText"
                x-on:click="clearSearch"
                type="button" 
                class="absolute right-10 top-1/2 transform -translate-y-1/2 mr-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                aria-label="{{ __('Clear search') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide-x">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="absolute right-0 top-1/2 mr-1 transform -translate-y-1/2 text-gray-600 dark:text-gray-50 text-xs px-2 flex items-center justify-center gap-1 py-1 rounded">
            <span class="fi-input-wrp-label whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"> ⌘+J </span>
        </div>
    </x-filament::input.wrapper>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('sidebarSearch', () => ({
                hasSearchText: false,
                
                init() {
                    this.$refs.search.value = '';
                    this.hasSearchText = false;
                },
                
                filterItems(searchTerm) {
                    this.hasSearchText = searchTerm.length > 0;
                    
                    const groups = document.querySelectorAll('.fi-sidebar-nav-groups .fi-sidebar-group');
                    searchTerm = searchTerm.toLowerCase();

                    groups.forEach(group => {
                        const groupButton = group.querySelector('.fi-sidebar-group-button');
                        const groupText = groupButton?.textContent.toLowerCase() || '';
                        const items = group.querySelectorAll('.fi-sidebar-item');
                        let hasVisibleItems = false;

                        const groupMatches = groupText.includes(searchTerm);

                        items.forEach(item => {
                            const itemText = item.textContent.toLowerCase();
                            const isVisible = itemText.includes(searchTerm) || groupMatches;

                            item.style.display = isVisible ? '' : 'none';
                            if (isVisible) hasVisibleItems = true;
                        })

                        group.style.display = (hasVisibleItems || groupMatches) ? '' : 'none';
                    });
                },
                
                clearSearch() {
                    this.$refs.search.value = '';
                    this.hasSearchText = false;
                    this.filterItems('');
                }
            }));
        });
    </script>
</div>
