import { useEffect, useRef, useState } from 'react';
import { Html5Qrcode } from 'html5-qrcode';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { CameraIcon, SearchIcon, AlertCircleIcon } from 'lucide-react';

interface QRScannerProps {
    onScan: (result: string) => void;
    onError?: (error: string) => void;
    isActive: boolean;
}

export function QRScanner({ onScan, onError, isActive }: QRScannerProps) {
    const [isScanning, setIsScanning] = useState(false);
    const [manualSearch, setManualSearch] = useState('');
    const [error, setError] = useState<string | null>(null);
    const html5QrCodeRef = useRef<Html5Qrcode | null>(null);
    const qrReaderRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!isActive) {
            stopScanning();
            return;
        }
    }, [isActive]);

    const startScanning = async () => {
        setError(null);

        try {
            if (!html5QrCodeRef.current) {
                html5QrCodeRef.current = new Html5Qrcode('qr-reader');
            }

            const qrCodeSuccessCallback = (decodedText: string) => {
                stopScanning();
                onScan(decodedText);
            };

            await html5QrCodeRef.current.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                },
                qrCodeSuccessCallback,
                undefined
            );

            setIsScanning(true);
        } catch (err: any) {
            const errorMsg = 'No se pudo acceder a la cámara. Verifique los permisos.';
            setError(errorMsg);
            if (onError) onError(errorMsg);
        }
    };

    const stopScanning = async () => {
        try {
            if (html5QrCodeRef.current && html5QrCodeRef.current.isScanning) {
                await html5QrCodeRef.current.stop();
            }
            setIsScanning(false);
        } catch (err) {
            // Silently fail
        }
    };

    const handleManualSearch = (e: React.FormEvent) => {
        e.preventDefault();
        if (manualSearch.trim()) {
            onScan(manualSearch.trim());
            setManualSearch('');
        }
    };

    return (
        <Card className="w-full">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <CameraIcon className="size-5" />
                    Escanear QR Empleado
                </CardTitle>
                <CardDescription>
                    Active la cámara para escanear el código QR o busque manualmente
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {error && (
                    <Alert variant="destructive">
                        <AlertCircleIcon />
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}

                {/* QR Scanner Container */}
                <div className="space-y-4">
                    <div
                        id="qr-reader"
                        ref={qrReaderRef}
                        className="w-full rounded-lg overflow-hidden border-2 border-dashed border-muted-foreground/25 min-h-[300px] flex items-center justify-center bg-muted/20"
                    >
                        {!isScanning && (
                            <div className="text-center p-8">
                                <CameraIcon className="size-16 mx-auto mb-4 text-muted-foreground/50" />
                                <p className="text-muted-foreground text-sm">
                                    Presione el botón para activar la cámara
                                </p>
                            </div>
                        )}
                    </div>

                    <div className="flex gap-2">
                        {!isScanning ? (
                            <Button
                                onClick={startScanning}
                                size="lg"
                                className="w-full"
                            >
                                <CameraIcon className="mr-2" />
                                Activar Cámara
                            </Button>
                        ) : (
                            <Button
                                onClick={stopScanning}
                                variant="destructive"
                                size="lg"
                                className="w-full"
                            >
                                Detener Escaneo
                            </Button>
                        )}
                    </div>
                </div>

                {/* Manual Search */}
                <div className="relative">
                    <div className="absolute inset-0 flex items-center">
                        <span className="w-full border-t" />
                    </div>
                    <div className="relative flex justify-center text-xs uppercase">
                        <span className="bg-background px-2 text-muted-foreground">
                            O bien
                        </span>
                    </div>
                </div>

                <form onSubmit={handleManualSearch} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="manual-search">Buscar por ID o UUID</Label>
                        <Input
                            id="manual-search"
                            type="text"
                            placeholder="Ej: EMCAD20005 o UUID completo"
                            value={manualSearch}
                            onChange={(e) => setManualSearch(e.target.value)}
                            className="h-12 text-base"
                        />
                    </div>
                    <Button
                        type="submit"
                        variant="outline"
                        size="lg"
                        className="w-full"
                        disabled={!manualSearch.trim()}
                    >
                        <SearchIcon className="mr-2" />
                        Buscar QR
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}
