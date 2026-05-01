<div class="fi-simple-page">
    <div class="fi-simple-page-content">
        <x-filament-panels::header.simple
            :heading="__('Select company')"
            :subheading="__('Choose the company you want to work with')"
        />

        <form wire:submit.prevent="submit">
            {{ $this->form }}

            <x-filament::button type="submit" class="mt-4 w-full" size="lg">
                {{ __('Enter') }}
            </x-filament::button>
        </form>

        <p class="mt-4 text-center text-sm">
            <a
                href="{{ filament()->getTenantRegistrationUrl() }}"
                class="text-primary-600 hover:underline"
            >
                {{ __('Register a new company') }}
            </a>
        </p>
    </div>

    <x-filament-actions::modals />
</div>
