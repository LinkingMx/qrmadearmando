@php
    $qrUrls = $getRecord()->getQrCodeUrls();
@endphp

<div class="flex items-center space-x-2">
    @if($qrUrls['uuid'])
        <div class="text-center">
            <img src="{{ $qrUrls['uuid'] }}" alt="QR UUID" class="w-8 h-8 border rounded">
            <span class="text-xs text-gray-500">UUID</span>
        </div>
    @endif

    @if($qrUrls['legacy'])
        <div class="text-center">
            <img src="{{ $qrUrls['legacy'] }}" alt="QR Legacy" class="w-8 h-8 border rounded">
            <span class="text-xs text-gray-500">Legacy</span>
        </div>
    @endif

    @if(!$qrUrls['uuid'] && !$qrUrls['legacy'])
        <span class="text-xs text-gray-400">Sin QR</span>
    @endif
</div>