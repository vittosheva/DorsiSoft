<x-core::layouts.master moduleTitle="People Module" :description="$description ?? ''" :keywords="$keywords ?? ''" :author="$author ?? ''">
    {{ $slot }}
</x-core::layouts.master>
