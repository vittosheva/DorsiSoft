<x-core::layouts.master moduleTitle="Sales Module" :description="$description ?? ''" :keywords="$keywords ?? ''" :author="$author ?? ''">
    {{ $slot }}
</x-core::layouts.master>
