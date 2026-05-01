<section class="w-full bg-white dark:bg-slate-950 border-t border-gray-300 dark:border-gray-700 mt-37.5 p-6 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <!-- Texto principal -->
        <div class="space-y-3 max-w-3xl">
            <h2 class="text-lg md:text-xl font-semibold text-slate-900 dark:text-white">
                <img alt="Dorsi ERP logo" src="{{ filament()->getDefaultPanel()->getBrandLogo() }}" class="fi-logo h-8 mb-2 dark:filter-[brightness(1.5)_contrast(1.25)]" style="height: {{ filament()->getDefaultPanel()->getBrandLogoHeight() }};">
            </h2>
            <p class="text-sm md:text-base text-slate-600 dark:text-slate-300 leading-relaxed">{{ __('ERP long description') }}</p>

            <!-- CTA -->
            <div class="pt-2">
                <a
                    href="{{ filament()->getDefaultPanel()->getUrl() }}"
                    class="inline-flex items-center gap-2 rounded-full bg-primary-600 px-5 py-2.5
                           text-sm font-medium text-white shadow-sm
                           hover:bg-primary-700 dark:hover:bg-primary-800 focus:outline-none focus:ring-2
                           focus:ring-primary-500 dark:focus:ring-primary-400 focus:ring-offset-2 dark:focus:ring-offset-slate-950 transition"
                >
                    {{ __('Go to the control panel') }}
                </a>
            </div>
        </div>

        <!-- Branding / Footer info -->
        <div class="flex items-center justify-end self-end gap-3 text-slate-500 dark:text-slate-400 text-sm">
            <div class="flex flex-col items-end leading-tight">
                <span class="font-medium text-slate-700 dark:text-slate-300">{{ config('app.name') }}</span>
                <span class="text-slate-600 dark:text-slate-400">© 2025 – {{ date('Y') }}</span>
                <span class="text-slate-600 dark:text-slate-400">{{ __('All rights reserved') }}</span>
            </div>

            <!-- Logo placeholder -->
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-600 dark:bg-primary-700 text-white font-bold text-lg uppercase">{{ str(config('app.name'))->substr(0, 1) }}</div>
        </div>
    </div>
</section>
