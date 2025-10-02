import { useEffect, useRef, useState } from 'react';
import { Html5Qrcode, Html5QrcodeScannerState } from 'html5-qrcode';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { CameraIcon, SearchIcon, AlertCircleIcon, InfoIcon, CheckCircleIcon } from 'lucide-react';

interface QRScannerImprovedProps {
    onScan: (result: string) => void;
    onError?: (error: string) => void;
    isActive: boolean;
}

export function QRScannerImproved({ onScan, onError, isActive }: QRScannerImprovedProps) {
    const [isScanning, setIsScanning] = useState(false);
    const [manualSearch, setManualSearch] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [debugInfo, setDebugInfo] = useState<string[]>([]);
    const [camerasAvailable, setCamerasAvailable] = useState<MediaDeviceInfo[]>([]);
    const [selectedCameraId, setSelectedCameraId] = useState<string>('');

    const html5QrCodeRef = useRef<Html5Qrcode | null>(null);
    const elementId = 'qr-reader-improved';

    // Add debug log
    const addDebugLog = (message: string) => {
        console.log(`[QR Scanner] ${message}`);
        setDebugInfo(prev => [...prev.slice(-4), `${new Date().toLocaleTimeString()}: ${message}`]);
    };

    // Get available cameras
    useEffect(() => {
        const getCameras = async () => {
            try {
                addDebugLog('Solicitando permisos de cámara...');

                // Request camera permission first
                await navigator.mediaDevices.getUserMedia({ video: true })
                    .then(stream => {
                        stream.getTracks().forEach(track => track.stop());
                        addDebugLog('Permisos de cámara concedidos');
                    });

                addDebugLog('Enumerando dispositivos...');
                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoDevices = devices.filter(device => device.kind === 'videoinput');

                addDebugLog(`Encontradas ${videoDevices.length} cámaras`);
                videoDevices.forEach((device, index) => {
                    addDebugLog(`Cámara ${index + 1}: ${device.label || 'Camera ' + (index + 1)}`);
                });

                setCamerasAvailable(videoDevices);
                if (videoDevices.length > 0) {
                    // Prefer back camera (usually contains 'back' or 'environment')
                    const backCamera = videoDevices.find(device =>
                        device.label.toLowerCase().includes('back') ||
                        device.label.toLowerCase().includes('environment') ||
                        device.label.toLowerCase().includes('rear')
                    );
                    setSelectedCameraId(backCamera?.deviceId || videoDevices[0].deviceId);
                }
            } catch (err: any) {
                addDebugLog(`Error obteniendo cámaras: ${err.name} - ${err.message}`);
                setError(`Error de cámara: ${err.message}`);
            }
        };

        getCameras();
    }, []);

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            stopScanning();
        };
    }, []);

    // Stop when inactive
    useEffect(() => {
        if (!isActive && isScanning) {
            stopScanning();
        }
    }, [isActive, isScanning]);

    const stopScanning = async () => {
        addDebugLog('Deteniendo escáner...');
        try {
            if (html5QrCodeRef.current) {
                const state = html5QrCodeRef.current.getState();
                addDebugLog(`Estado actual del escáner: ${state}`);

                if (state === Html5QrcodeScannerState.SCANNING) {
                    await html5QrCodeRef.current.stop();
                    addDebugLog('Escáner detenido exitosamente');
                }

                html5QrCodeRef.current.clear();
                html5QrCodeRef.current = null;
            }
            setIsScanning(false);
        } catch (err: any) {
            addDebugLog(`Error deteniendo escáner: ${err.message}`);
            setIsScanning(false);
        }
    };

    const startScanning = async () => {
        setError(null);
        addDebugLog('Iniciando escáner...');

        if (camerasAvailable.length === 0) {
            const errorMsg = 'No hay cámaras disponibles';
            setError(errorMsg);
            addDebugLog(errorMsg);
            return;
        }

        try {
            // Stop any existing scanner
            await stopScanning();

            // Create new scanner instance
            addDebugLog('Creando nueva instancia de Html5Qrcode...');
            html5QrCodeRef.current = new Html5Qrcode(elementId);

            const qrCodeSuccessCallback = (decodedText: string) => {
                addDebugLog(`QR escaneado exitosamente: ${decodedText}`);
                stopScanning();
                onScan(decodedText);
            };

            const qrCodeErrorCallback = (errorMessage: string) => {
                // This is called frequently during scanning, so we don't log it
                // console.log('QR scan error (normal):', errorMessage);
            };

            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
            };

            addDebugLog(`Intentando iniciar con cámara: ${selectedCameraId}`);

            // Try with selected camera ID
            await html5QrCodeRef.current.start(
                selectedCameraId,
                config,
                qrCodeSuccessCallback,
                qrCodeErrorCallback
            );

            addDebugLog('Cámara iniciada exitosamente');
            setIsScanning(true);

        } catch (err: any) {
            addDebugLog(`Error iniciando cámara: ${err.name} - ${err.message}`);

            // Try with constraint-based approach as fallback
            try {
                addDebugLog('Intentando método de respaldo...');

                await html5QrCodeRef.current!.start(
                    { facingMode: "environment" },
                    config,
                    qrCodeSuccessCallback,
                    qrCodeErrorCallback
                );

                addDebugLog('Método de respaldo exitoso');
                setIsScanning(true);

            } catch (fallbackErr: any) {
                addDebugLog(`Error en método de respaldo: ${fallbackErr.name} - ${fallbackErr.message}`);

                let errorMsg = 'Error desconocido';

                if (fallbackErr.name === 'NotAllowedError') {
                    errorMsg = 'Permisos de cámara denegados. Permita el acceso en su navegador.';
                } else if (fallbackErr.name === 'NotFoundError') {
                    errorMsg = 'No se encontró cámara en el dispositivo.';
                } else if (fallbackErr.name === 'NotReadableError') {
                    errorMsg = 'Cámara en uso por otra aplicación.';
                } else if (fallbackErr.name === 'OverconstrainedError') {
                    errorMsg = 'Configuración de cámara no compatible.';
                } else {
                    errorMsg = `Error: ${fallbackErr.message}`;
                }

                setError(errorMsg);
                if (onError) onError(errorMsg);
                await stopScanning();
            }
        }
    };

    const handleManualSearch = (e: React.FormEvent) => {
        e.preventDefault();
        if (manualSearch.trim()) {
            addDebugLog(`Búsqueda manual: ${manualSearch.trim()}`);
            onScan(manualSearch.trim());
            setManualSearch('');
        }
    };

    return (
        <Card className="w-full">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <CameraIcon className="size-5" />
                    Escáner QR Mejorado
                </CardTitle>
                <CardDescription>
                    Escáner QR con diagnóstico avanzado y múltiples métodos de captura
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {/* Error Display */}
                {error && (
                    <Alert variant="destructive">
                        <AlertCircleIcon />
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}

                {/* Camera Status */}
                {camerasAvailable.length > 0 && (
                    <Alert>
                        <CheckCircleIcon />
                        <AlertDescription>
                            {camerasAvailable.length} cámara(s) detectada(s)
                        </AlertDescription>
                    </Alert>
                )}

                {/* Camera Selection */}
                {camerasAvailable.length > 1 && (
                    <div className="space-y-2">
                        <Label>Seleccionar Cámara:</Label>
                        <select
                            value={selectedCameraId}
                            onChange={(e) => setSelectedCameraId(e.target.value)}
                            className="w-full p-2 border rounded"
                        >
                            {camerasAvailable.map((camera, index) => (
                                <option key={camera.deviceId} value={camera.deviceId}>
                                    {camera.label || `Cámara ${index + 1}`}
                                </option>
                            ))}
                        </select>
                    </div>
                )}

                {/* QR Scanner Container */}
                <div className="space-y-4">
                    <div
                        id={elementId}
                        className="w-full rounded-lg overflow-hidden border-2 border-dashed border-muted-foreground/25 min-h-[300px] flex items-center justify-center bg-muted/20"
                    >
                        {!isScanning && (
                            <div className="text-center p-8">
                                <CameraIcon className="size-16 mx-auto mb-4 text-muted-foreground/50" />
                                <p className="text-muted-foreground text-sm">
                                    {camerasAvailable.length === 0
                                        ? 'Detectando cámaras...'
                                        : 'Presione "Activar Cámara" para comenzar'}
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
                                disabled={camerasAvailable.length === 0}
                            >
                                <CameraIcon className="mr-2" />
                                {camerasAvailable.length === 0 ? 'Sin Cámaras' : 'Activar Cámara'}
                            </Button>
                        ) : (
                            <Button
                                onClick={stopScanning}
                                variant="destructive"
                                size="lg"
                                className="w-full"
                            >
                                Detener Escáner
                            </Button>
                        )}
                    </div>
                </div>

                {/* Debug Information */}
                {debugInfo.length > 0 && (
                    <Alert>
                        <InfoIcon />
                        <AlertDescription>
                            <div className="font-medium mb-2">Información de Diagnóstico:</div>
                            <div className="text-xs space-y-1 max-h-24 overflow-y-auto">
                                {debugInfo.map((log, index) => (
                                    <div key={index} className="font-mono">{log}</div>
                                ))}
                            </div>
                        </AlertDescription>
                    </Alert>
                )}

                {/* Manual Search */}
                <div className="relative">
                    <div className="absolute inset-0 flex items-center">
                        <span className="w-full border-t" />
                    </div>
                    <div className="relative flex justify-center text-xs uppercase">
                        <span className="bg-background px-2 text-muted-foreground">
                            O buscar manualmente
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