@php
    $qrUrls = $getRecord()->getQrCodeUrls();
@endphp

<div class="flex items-center space-x-6">
    @if($qrUrls['uuid'])
        <div>
            <img src="{{ $qrUrls['uuid'] }}" alt="QR UUID" class="w-8 h-8 border rounded">
        </div>
    @endif

    @if($qrUrls['legacy'])
        <div>
            <img src="{{ $qrUrls['legacy'] }}" alt="QR Legacy" class="w-8 h-8 border rounded">
        </div>
    @endif

    @if(!$qrUrls['uuid'] && !$qrUrls['legacy'])
        <span class="text-xs text-gray-400">Sin QR</span>
    @endif
</div>