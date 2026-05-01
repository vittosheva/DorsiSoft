<x-core::layouts.master moduleTitle="Sri Module" :description="$description ?? ''" :keywords="$keywords ?? ''" :author="$author ?? ''">
    {{ $slot }}
</x-core::layouts.master>
