@php
    /** @var string $body */
    /** @var string $documentCode */
@endphp

<p>{{ __('Hello,') }}</p>

{!! nl2br(e($body)) !!}

<p>{{ __('Document code: :code', ['code' => $documentCode]) }}</p>

<p>{{ __('Regards,') }}</p>
