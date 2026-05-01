<x-core::layouts.master moduleTitle="Accounting Module" :description="$description ?? ''" :keywords="$keywords ?? ''" :author="$author ?? ''">
    {{ $slot }}
</x-core::layouts.master>
