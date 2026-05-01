@php
    /** @var \Illuminate\Support\Collection<int, \Modules\Core\Models\CompanySubscription>|array<int, \Modules\Core\Models\CompanySubscription> $subscriptions */
    $subscriptions = collect($subscriptions ?? []);
@endphp

<div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10 text-sm">
        <thead class="bg-gray-50 dark:bg-white/5">
            <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-200">{{ __('Plan') }}</th>
                <th class="px-3 py-2 text-center font-medium text-gray-700 dark:text-gray-200">{{ __('Billing cycle') }}</th>
                <th class="px-3 py-2 text-center font-medium text-gray-700 dark:text-gray-200">{{ __('Starts at') }}</th>
                <th class="px-3 py-2 text-center font-medium text-gray-700 dark:text-gray-200">{{ __('Ends at') }}</th>
                <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-200">{{ __('Status') }}</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100 dark:divide-white/10 bg-white dark:bg-transparent">
            @forelse ($subscriptions as $subscription)
                @if (is_object($subscription))
                    <tr>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-100">
                            @php
                                $planCode = $subscription->plan_code?->value ?? $subscription->plan_code;
                                $planCodeDisplay = is_string($planCode) ? strtoupper($planCode) : strtoupper((string) $planCode);
                            @endphp
                            {{ __($planCodeDisplay) }}
                        </td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200 text-center">
                            {{ __($subscription->billing_cycle ?? '—') }}
                        </td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200 text-center">
                            {{ $subscription->starts_at?->format('Y-m-d H:i') ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200 text-center">
                            {{ $subscription->ends_at?->format('Y-m-d H:i') ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                            {{ __(ucfirst((string) $subscription->status)) }}
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                        {{ __('No subscriptions found for this company.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
