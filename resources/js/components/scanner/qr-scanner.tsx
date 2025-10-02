import { useCallback, useEffect, useRef, useState } from 'react';
import { Html5Qrcode, Html5QrcodeCameraScanConfig, Html5QrcodeResult } from 'html5-qrcode';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { CameraIcon, SearchIcon, AlertCircleIcon, InfoIcon } from 'lucide-react';

interface QRScannerProps {
    onScan: (result: string) => void;
    onError?: (error: string) => void;
    isActive: boolean;
}

export function QRScanner({ onScan, onError, isActive }: QRScannerProps) {
    const [isScanning, setIsScanning] = useState(false);
    const [manualSearch, setManualSearch] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [hasCamera, setHasCamera] = useState<boolean | null>(null);
    const html5QrCodeRef = useRef<Html5Qrcode | null>(null);
    const qrReaderRef = useRef<HTMLDivElement>(null);
    const isInitializedRef = useRef(false);

    // Check camera availability
    useEffect(() => {
        const checkCameraAvailability = async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                stream.getTracks().forEach(track => track.stop());
                setHasCamera(true);
            } catch (err) {
                setHasCamera(false);
                console.error('Camera not available:', err);
            }
        };

        checkCameraAvailability();
    }, []);

    // Cleanup on unmount or when inactive
    useEffect(() => {
        return () => {
            stopScanning();
        };
    }, []);

    useEffect(() => {
        if (!isActive) {
            stopScanning();
            return;
        }
    }, [isActive]);

    const stopScanning = useCallback(async () => {
        try {
            if (html5QrCodeRef.current) {
                if (html5QrCodeRef.current.isScanning) {
                    await html5QrCodeRef.current.stop();
                }
                await html5QrCodeRef.current.clear();
                html5QrCodeRef.current = null;
            }
            setIsScanning(false);
            isInitializedRef.current = false;
        } catch (err) {
            console.error('Error stopping scanner:', err);
            setIsScanning(false);
            isInitializedRef.current = false;
        }
    }, []);

    const startScanning = async () => {
        if (isInitializedRef.current) {
            await stopScanning();
        }

        setError(null);

        try {
            // Check if cameras are available
            if (hasCamera === false) {
                throw new Error('CAMERA_NOT_AVAILABLE');
            }

            // Create new instance
            const elementId = 'qr-reader';
            const element = document.getElementById(elementId);
            if (!element) {
                throw new Error('QR reader element not found');
            }

            html5QrCodeRef.current = new Html5Qrcode(elementId);
            isInitializedRef.current = true;

            const qrCodeSuccessCallback = (decodedText: string, result: Html5QrcodeResult) => {
                console.log('QR scanned successfully:', decodedText);
                stopScanning();
                onScan(decodedText);
            };

            const config: Html5QrcodeCameraScanConfig = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
                disableFlip: false,
            };

            // Try different camera configurations
            const cameraConfigs = [
                { facingMode: 'environment' }, // Back camera (preferred)
                { facingMode: 'user' },        // Front camera
                'environment',                  // String format back camera
                'user'                         // String format front camera
            ];

            let cameraStarted = false;
            for (const cameraConfig of cameraConfigs) {
                try {
                    await html5QrCodeRef.current.start(
                        cameraConfig,
                        config,
                        qrCodeSuccessCallback,
                        undefined
                    );
                    cameraStarted = true;
                    break;
                } catch (cameraErr) {
                    console.warn('Failed to start camera with config:', cameraConfig, cameraErr);
                    continue;
                }
            }

            if (!cameraStarted) {
                throw new Error('CAMERA_START_FAILED');
            }

            setIsScanning(true);
        } catch (err: any) {
            console.error('Error starting camera:', err);

            let errorMsg = 'Error desconocido al acceder a la cámara.';

            if (err.message === 'CAMERA_NOT_AVAILABLE') {
                errorMsg = 'No se detectó ninguna cámara en este dispositivo.';
            } else if (err.message === 'CAMERA_START_FAILED') {
                errorMsg = 'No se pudo iniciar la cámara. Verifique que esté disponible y no esté siendo usada por otra aplicación.';
            } else if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                errorMsg = 'Permisos de cámara denegados. Por favor permita el acceso a la cámara en su navegador.';
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                errorMsg = 'No se encontró ninguna cámara disponible en este dispositivo.';
            } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                errorMsg = 'La cámara está siendo usada por otra aplicación. Por favor cierre otras aplicaciones que usen la cámara.';
            } else if (err.name === 'OverconstrainedError' || err.name === 'ConstraintNotSatisfiedError') {
                errorMsg = 'La configuración de cámara solicitada no es compatible con este dispositivo.';
            } else if (err.name === 'NotSupportedError') {
                errorMsg = 'Este navegador no soporta el acceso a la cámara. Pruebe con Chrome, Firefox o Safari.';
            } else if (err.name === 'AbortError') {
                errorMsg = 'El acceso a la cámara fue interrumpido.';
            }

            setError(errorMsg);
            if (onError) onError(errorMsg);
            await stopScanning();
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
                                    {hasCamera === null && 'Verificando disponibilidad de cámara...'}
                                    {hasCamera === true && 'Presione el botón para activar la cámara'}
                                    {hasCamera === false && 'No se detectó cámara disponible'}
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Camera status indicator */}
                    {hasCamera !== null && (
                        <Alert variant={hasCamera ? "default" : "destructive"} className="mb-4">
                            <InfoIcon />
                            <AlertDescription>
                                {hasCamera
                                    ? 'Cámara detectada. Puede activar el escáner.'
                                    : 'No se detectó cámara. Use la búsqueda manual a continuación.'
                                }
                            </AlertDescription>
                        </Alert>
                    )}

                    <div className="flex gap-2">
                        {!isScanning ? (
                            <Button
                                onClick={startScanning}
                                size="lg"
                                className="w-full"
                                disabled={hasCamera === false}
                            >
                                <CameraIcon className="mr-2" />
                                {hasCamera === false ? 'Cámara No Disponible' : 'Activar Cámara'}
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
