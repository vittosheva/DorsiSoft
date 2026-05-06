@if(method_exists($document, 'getSriSequentialCode') && $document->getSriSequentialCode())
    <tr>
        <td class="detail-key">{{ __('Sequential') }}</td>
        <td class="detail-val">{{ $document->getSriSequentialCode() }}</td>
    </tr>
@endif
@if(!empty($document->access_key))
    <tr>
        <td class="detail-key">{{ __('Authorization code') }}</td>
        <td class="detail-val" style="font-size:6.5pt; word-break:break-all;">{{ $document->access_key }}</td>
    </tr>
@endif
