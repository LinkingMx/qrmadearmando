<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Storage;

class QrCodeService
{
    public function generateQrCodes(string $uuid, string $legacyId): string
    {
        // Crear directorio si no existe
        $this->ensureDirectoryExists();

        // Generar QR para UUID
        $this->generateQrCode($uuid, "{$uuid}_uuid.svg");

        // Generar QR para legacy_id
        $this->generateQrCode($legacyId, "{$uuid}_legacy.svg");

        // Retornar la ruta base para almacenar en BD
        return "qr-codes/{$uuid}";
    }

    private function generateQrCode(string $data, string $filename): void
    {
        // Crear QR code con tamaño 400x400 y margen 10
        $qrCode = new QrCode(
            data: $data,
            size: 400,
            margin: 10
        );

        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        Storage::disk('public')->put("qr-codes/{$filename}", $result->getString());
    }

    private function ensureDirectoryExists(): void
    {
        if (!Storage::disk('public')->exists('qr-codes')) {
            Storage::disk('public')->makeDirectory('qr-codes');
        }
    }

    public function deleteQrCodes(?string $qrImagePath): void
    {
        if (!$qrImagePath) {
            return;
        }

        // Extraer UUID del path
        $uuid = basename($qrImagePath);

        // Eliminar ambos archivos QR
        Storage::disk('public')->delete([
            "qr-codes/{$uuid}_uuid.svg",
            "qr-codes/{$uuid}_legacy.svg"
        ]);
    }

    public function getQrCodeUrls(?string $qrImagePath): array
    {
        if (!$qrImagePath) {
            return ['uuid' => null, 'legacy' => null];
        }

        $uuid = basename($qrImagePath);

        return [
            'uuid' => Storage::disk('public')->url("qr-codes/{$uuid}_uuid.svg"),
            'legacy' => Storage::disk('public')->url("qr-codes/{$uuid}_legacy.svg")
        ];
    }
}