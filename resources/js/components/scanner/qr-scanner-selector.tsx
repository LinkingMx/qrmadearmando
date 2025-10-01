import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { QRScanner } from './qr-scanner';
import { QRScannerImproved } from './qr-scanner-improved';
import { QRScannerNative } from './qr-scanner-native';
import { SettingsIcon, CameraIcon, VideoIcon, SearchIcon } from 'lucide-react';

interface QRScannerSelectorProps {
    onScan: (result: string) => void;
    onError?: (error: string) => void;
    isActive: boolean;
}

type ScannerType = 'original' | 'improved' | 'native' | 'manual-only';

export function QRScannerSelector({ onScan, onError, isActive }: QRScannerSelectorProps) {
    const [selectedScanner, setSelectedScanner] = useState<ScannerType>('native');
    const [manualInput, setManualInput] = useState('');

    const handleManualScan = (e: React.FormEvent) => {
        e.preventDefault();
        if (manualInput.trim()) {
            onScan(manualInput.trim());
            setManualInput('');
        }
    };

    const scannerOptions = [
        {
            type: 'improved' as ScannerType,
            name: 'Scanner Mejorado',
            description: 'Con diagnóstico y múltiples métodos',
            icon: <CameraIcon className="size-4" />
        },
        {
            type: 'native' as ScannerType,
            name: 'Scanner Nativo',
            description: 'API directa del navegador',
            icon: <VideoIcon className="size-4" />
        },
        {
            type: 'original' as ScannerType,
            name: 'Scanner Original',
            description: 'Versión original html5-qrcode',
            icon: <SettingsIcon className="size-4" />
        },
        {
            type: 'manual-only' as ScannerType,
            name: 'Solo Manual',
            description: 'Sin cámara, búsqueda directa',
            icon: <SearchIcon className="size-4" />
        }
    ];

    return (
        <div className="space-y-6">
            {/* Scanner Type Selector - Hidden for now, only native works */}
            {/*
            <Card>
                <CardHeader>
                    <CardTitle>Método de Escaneo</CardTitle>
                    <CardDescription>
                        Seleccione el método que funcione mejor en su dispositivo
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-2">
                        {scannerOptions.map((option) => (
                            <Button
                                key={option.type}
                                variant={selectedScanner === option.type ? "default" : "outline"}
                                size="sm"
                                onClick={() => setSelectedScanner(option.type)}
                                className="flex flex-col h-auto p-3 text-center"
                            >
                                {option.icon}
                                <span className="text-xs font-medium mt-1">{option.name}</span>
                                <span className="text-xs opacity-70">{option.description}</span>
                            </Button>
                        ))}
                    </div>
                </CardContent>
            </Card>
            */}

            {/* Native Scanner - Only active scanner */}
            <QRScannerNative
                onScan={onScan}
                onError={onError}
                isActive={isActive}
            />

            {/* Other scanners temporarily hidden */}
            {/*
            {selectedScanner === 'original' && (
                <QRScanner
                    onScan={onScan}
                    onError={onError}
                    isActive={isActive}
                />
            )}

            {selectedScanner === 'improved' && (
                <QRScannerImproved
                    onScan={onScan}
                    onError={onError}
                    isActive={isActive}
                />
            )}

            {selectedScanner === 'manual-only' && (
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <SearchIcon className="size-5" />
                            Búsqueda Manual
                        </CardTitle>
                        <CardDescription>
                            Ingrese el ID o UUID del código QR directamente
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleManualScan} className="space-y-4">
                            <div className="space-y-2">
                                <label htmlFor="manual-input" className="text-sm font-medium">
                                    ID o UUID del QR
                                </label>
                                <input
                                    id="manual-input"
                                    type="text"
                                    placeholder="Ej: EMCAD20005 o 0199a054-e508-72d7-ab39-7bd9360fed36"
                                    value={manualInput}
                                    onChange={(e) => setManualInput(e.target.value)}
                                    className="w-full h-12 px-3 text-base border rounded-md"
                                />
                            </div>
                            <Button
                                type="submit"
                                size="lg"
                                className="w-full"
                                disabled={!manualInput.trim()}
                            >
                                <SearchIcon className="mr-2" />
                                Buscar QR
                            </Button>
                        </form>

                        <Alert className="mt-4">
                            <AlertDescription>
                                <strong>Ejemplos de códigos válidos:</strong>
                                <br />• ID legacy: EMCAD20005, EMCAD20001, etc.
                                <br />• UUID: 0199a054-e508-72d7-ab39-7bd9360fed36
                            </AlertDescription>
                        </Alert>
                    </CardContent>
                </Card>
            )}
            */}

            {/* Instructions - Hidden for now */}
            {/*
            <Alert>
                <AlertDescription>
                    <strong>Problemas con la cámara?</strong>
                    <br />1. Pruebe diferentes métodos de escaneo arriba
                    <br />2. Verifique permisos de cámara en su navegador
                    <br />3. Use "Solo Manual" como alternativa confiable
                    <br />4. En móviles, pruebe rotar la pantalla
                </AlertDescription>
            </Alert>
            */}
        </div>
    );
}