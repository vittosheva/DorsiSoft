@if($document->access_key)
    @php
        $barcodePng = (new \Picqer\Barcode\BarcodeGeneratorPNG())->getBarcode($document->access_key, \Picqer\Barcode\BarcodeGeneratorPNG::TYPE_CODE_128, 2, 60);
        $barcodeUri = 'data:image/png;base64,' . base64_encode($barcodePng);
    @endphp
    <div style="width:92%; border:1px solid #e2e8f0; padding:6px 10px; margin: 6px 0; text-align:center;">
        <div style="font-size:6.5px; font-weight:bold; text-transform:uppercase; letter-spacing:1px; color:#94a3b8; margin-bottom:4px;">
            {{ __('Authorization number') }}
        </div>
        <img src="{{ $barcodeUri }}" style="width:100%; height:30px;" alt="{{ $document->access_key }}">
        <div style="font-family:monospace; font-size:9px; letter-spacing:1px; color:#333333; word-break:break-all; margin-top:3px;">
            {{ $document->access_key }}
        </div>
    </div>
@endif
