<div class="space-y-4">
    @if($qrUrls['uuid'] || $qrUrls['legacy'])
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- QR UUID -->
            @if($qrUrls['uuid'])
                <div class="text-center p-4 border rounded-lg bg-gray-50 dark:bg-gray-800">
                    <h4 class="font-semibold text-sm mb-2 text-gray-700 dark:text-gray-300">QR UUID</h4>
                    <div class="mb-3">
                        <img src="{{ $qrUrls['uuid'] }}" alt="QR UUID" class="mx-auto w-32 h-32 border rounded">
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3 font-mono break-all">{{ $uuid }}</p>
                    <a href="{{ $qrUrls['uuid'] }}"
                       download="QR_UUID_{{ $legacyId }}.svg"
                       class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Descargar
                    </a>
                </div>
            @endif

            <!-- QR Legacy -->
            @if($qrUrls['legacy'])
                <div class="text-center p-4 border rounded-lg bg-gray-50 dark:bg-gray-800">
                    <h4 class="font-semibold text-sm mb-2 text-gray-700 dark:text-gray-300">QR Legacy ID</h4>
                    <div class="mb-3">
                        <img src="{{ $qrUrls['legacy'] }}" alt="QR Legacy" class="mx-auto w-32 h-32 border rounded">
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3 font-mono">{{ $legacyId }}</p>
                    <a href="{{ $qrUrls['legacy'] }}"
                       download="QR_Legacy_{{ $legacyId }}.svg"
                       class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Descargar
                    </a>
                </div>
            @endif
        </div>
    @else
        <div class="text-center text-gray-500 dark:text-gray-400 py-4">
            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-sm">Los códigos QR se generarán después de guardar</p>
        </div>
    @endif
</div>