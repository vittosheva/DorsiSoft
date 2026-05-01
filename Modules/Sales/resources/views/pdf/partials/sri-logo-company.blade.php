@if($document->company->logo_url)
    @php
        $logo = 'data:image/png;base64,' . base64_encode(file_get_contents(\Illuminate\Support\Facades\Storage::disk('public')->path($document->company->logo_url)));
    @endphp
    <img src="{{ $logo }}" alt="{{ $document->company->legal_name }}">
@endif
