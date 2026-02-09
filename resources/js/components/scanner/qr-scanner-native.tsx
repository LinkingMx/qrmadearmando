import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import jsQR from 'jsqr';
import {
    AlertCircleIcon,
    CameraIcon,
    SearchIcon,
    VideoIcon,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

interface QRScannerNativeProps {
    onScan: (result: string) => void;
    onError?: (error: string) => void;
    isActive: boolean;
}

export function QRScannerNative({
    onScan,
    onError,
    isActive,
}: QRScannerNativeProps) {
    const [isScanning, setIsScanning] = useState(false);
    const [manualSearch, setManualSearch] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [status, setStatus] = useState<string>('Esperando...');
    const [scanCount, setScanCount] = useState(0);
    const [lastScanAttempt, setLastScanAttempt] = useState<string>('');

    const videoRef = useRef<HTMLVideoElement>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const scanIntervalRef = useRef<NodeJS.Timeout | null>(null);

    // QR Code detection using jsQR library
    const detectQRCode = useCallback(() => {
        if (!videoRef.current || !canvasRef.current) return;

        const video = videoRef.current;
        const canvas = canvasRef.current;
        const context = canvas.getContext('2d');

        if (!context || video.videoWidth === 0 || video.videoHeight === 0)
            return;

        // Set canvas size to match video
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        // Draw current video frame to canvas
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        try {
            // Get image data for QR detection
            const imageData = context.getImageData(
                0,
                0,
                canvas.width,
                canvas.height,
            );

            // Increment scan count
            setScanCount((prev) => prev + 1);
            setLastScanAttempt(new Date().toLocaleTimeString());

            // Use jsQR to detect QR codes
            const qrCode = jsQR(
                imageData.data,
                imageData.width,
                imageData.height,
                {
                    inversionAttempts: 'dontInvert',
                },
            );

            if (qrCode) {
                setStatus(`✅ QR detectado: ${qrCode.data}`);
                stopScanning();
                onScan(qrCode.data);
                return;
            }

            // Update status with scan info
            setStatus(
                `🔍 Escaneando... (${scanCount} intentos) - ${lastScanAttempt}`,
            );
        } catch (err) {
            setStatus(`❌ Error en detección: ${err}`);
        }
    }, [onScan, scanCount, lastScanAttempt]);

    const startScanning = async () => {
        setError(null);
        setStatus('Iniciando cámara...');

        try {
            // Stop any existing stream
            if (streamRef.current) {
                streamRef.current.getTracks().forEach((track) => track.stop());
            }

            setStatus('Solicitando permisos de cámara...');

            const constraints = {
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280, min: 640 },
                    height: { ideal: 720, min: 480 },
                    frameRate: { ideal: 30, min: 10 },
                },
            };

            const stream =
                await navigator.mediaDevices.getUserMedia(constraints);
            streamRef.current = stream;

            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                videoRef.current.play();

                videoRef.current.addEventListener('loadedmetadata', () => {
                    setStatus(
                        '🎥 Cámara activa - Coloque QR frente a la cámara',
                    );
                    setIsScanning(true);
                    setScanCount(0);

                    // Start scanning every 300ms for faster detection
                    scanIntervalRef.current = setInterval(detectQRCode, 300);
                });
            }
        } catch (err: any) {
            let errorMsg = 'Error desconocido';

            if (err.name === 'NotAllowedError') {
                errorMsg = 'Permisos de cámara denegados';
            } else if (err.name === 'NotFoundError') {
                errorMsg = 'No se encontró cámara';
            } else if (err.name === 'NotReadableError') {
                errorMsg = 'Cámara en uso por otra aplicación';
            } else {
                errorMsg = `Error: ${err.message}`;
            }

            setError(errorMsg);
            setStatus('Error de cámara');
            if (onError) onError(errorMsg);
        }
    };

    const stopScanning = useCallback(() => {
        setStatus('Deteniendo...');

        if (scanIntervalRef.current) {
            clearInterval(scanIntervalRef.current);
            scanIntervalRef.current = null;
        }

        if (streamRef.current) {
            streamRef.current.getTracks().forEach((track) => track.stop());
            streamRef.current = null;
        }

        if (videoRef.current) {
            videoRef.current.srcObject = null;
        }

        setIsScanning(false);
        setStatus('Cámara detenida');
    }, []);

    useEffect(() => {
        if (!isActive && isScanning) {
            stopScanning();
        }
    }, [isActive, isScanning, stopScanning]);

    useEffect(() => {
        return () => {
            stopScanning();
        };
    }, [stopScanning]);

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
                    <VideoIcon className="size-5" />
                    Scanner QR Nativo
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
                {/* Error Display */}
                {error && (
                    <Alert variant="destructive">
                        <AlertCircleIcon />
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}

                {/* Video Container */}
                <div className="space-y-4">
                    <div className="relative flex min-h-[300px] w-full items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-muted-foreground/25 bg-black">
                        <video
                            ref={videoRef}
                            className="h-full w-full object-cover"
                            style={{ display: isScanning ? 'block' : 'none' }}
                            playsInline
                            muted
                        />
                        <canvas ref={canvasRef} className="hidden" />
                        {!isScanning && (
                            <div className="p-8 text-center">
                                <CameraIcon className="mx-auto mb-4 size-16 text-white/50" />
                                <p className="text-sm text-white/70">
                                    Presione "Activar Cámara" para comenzar
                                </p>
                            </div>
                        )}
                        {isScanning && (
                            <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                                <div className="relative">
                                    <div className="h-64 w-64 animate-pulse rounded-lg border-2 border-dashed border-green-500"></div>
                                    <div className="absolute top-2 left-2 rounded bg-black/50 px-2 py-1 font-mono text-xs text-green-400">
                                        Zona de Objetivo QR
                                    </div>
                                    <div className="absolute right-2 bottom-2 rounded bg-black/50 px-2 py-1 font-mono text-xs text-green-400">
                                        {scanCount} escaneos
                                    </div>
                                </div>
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
                                Detener Cámara
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
                            Búsqueda Manual
                        </span>
                    </div>
                </div>

                <form onSubmit={handleManualSearch} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="manual-search">ID o UUID del QR</Label>
                        <Input
                            id="manual-search"
                            type="text"
                            placeholder="Ej: EMCAD20005 o UUID"
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
