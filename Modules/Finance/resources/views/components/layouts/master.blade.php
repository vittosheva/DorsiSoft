<x-core::layouts.master moduleTitle="Finance Module" :description="$description ?? ''" :keywords="$keywords ?? ''" :author="$author ?? ''">
    {{ $slot }}
</x-core::layouts.master>
