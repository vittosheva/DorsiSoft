@if($document->logo_pdf_url)
    @php
        $logo = 'data:image/png;base64,' . base64_encode(file_get_contents(\Illuminate\Support\Facades\Storage::disk(Modules\Core\Services\FileStoragePathService::getDisk(Modules\Core\Enums\FileTypeEnum::CompanyLogos))->path($document->logo_pdf_url)));
    @endphp
    <img src="{{ $logo }}" alt="{{ $document->legal_name }}">
@endif
