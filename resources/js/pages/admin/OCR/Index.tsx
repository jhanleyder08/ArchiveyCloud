import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { FileText, Scan, CheckCircle2, Clock, XCircle, Upload } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface Documento {
    id: number;
    nombre: string;
    codigo: string;
    formato: string;
    tamanio: number;
    ocr_processed_at: string | null;
    ocr_confidence: number | null;
    contenido_ocr: string | null;
}

interface Props {
    documentos: {
        data: Documento[];
        total: number;
        per_page: number;
        current_page: number;
    };
}

export default function OCRIndex({ documentos }: Props) {
    const [selectedDocuments, setSelectedDocuments] = useState<number[]>([]);
    const [processing, setProcessing] = useState<Record<number, boolean>>({});
    const [engine, setEngine] = useState('tesseract');
    const [alert, setAlert] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

    const handleProcessSingle = async (documentId: number) => {
        setProcessing(prev => ({ ...prev, [documentId]: true }));
        setAlert(null);

        try {
            const response = await axios.post(`/ocr/process/${documentId}`, {
                engine,
                async: true,
            });

            setAlert({
                type: 'success',
                message: response.data.message || 'OCR iniciado correctamente',
            });

            setTimeout(() => router.reload(), 2000);
        } catch (error: any) {
            setAlert({
                type: 'error',
                message: error.response?.data?.message || 'Error al procesar OCR',
            });
        } finally {
            setProcessing(prev => ({ ...prev, [documentId]: false }));
        }
    };

    const handleProcessBatch = async () => {
        if (selectedDocuments.length === 0) {
            setAlert({ type: 'error', message: 'Selecciona al menos un documento' });
            return;
        }

        setAlert(null);

        try {
            const response = await axios.post('/ocr/batch', {
                documento_ids: selectedDocuments,
            });

            setAlert({
                type: 'success',
                message: `Procesando ${selectedDocuments.length} documentos en lote`,
            });

            setSelectedDocuments([]);
            setTimeout(() => router.reload(), 2000);
        } catch (error: any) {
            setAlert({
                type: 'error',
                message: error.response?.data?.message || 'Error en procesamiento por lote',
            });
        }
    };

    const toggleSelection = (id: number) => {
        setSelectedDocuments(prev =>
            prev.includes(id) ? prev.filter(docId => docId !== id) : [...prev, id]
        );
    };

    const getStatusBadge = (doc: Documento) => {
        if (doc.ocr_processed_at) {
            return (
                <Badge variant="default" className="gap-1">
                    <CheckCircle2 className="h-3 w-3" />
                    Procesado
                </Badge>
            );
        }
        return (
            <Badge variant="secondary" className="gap-1">
                <Clock className="h-3 w-3" />
                Pendiente
            </Badge>
        );
    };

    const formatFileSize = (bytes: number) => {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    };

    const getSupportedFormats = () => ['pdf', 'png', 'jpg', 'jpeg', 'tiff', 'bmp', 'gif'];

    return (
        <AppLayout>
            <Head title="Procesamiento OCR" />

            <div className="container mx-auto py-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Procesamiento OCR</h1>
                        <p className="text-muted-foreground mt-1">
                            Reconocimiento óptico de caracteres para digitalización
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Scan className="h-5 w-5 text-primary" />
                    </div>
                </div>

                {alert && (
                    <Alert variant={alert.type === 'error' ? 'destructive' : 'default'}>
                        <AlertDescription>{alert.message}</AlertDescription>
                    </Alert>
                )}

                {/* Controles */}
                <Card>
                    <CardHeader>
                        <CardTitle>Configuración de OCR</CardTitle>
                        <CardDescription>
                            Selecciona el motor de OCR y los documentos a procesar
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <Label>Motor OCR</Label>
                                <Select value={engine} onValueChange={setEngine}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="tesseract">Tesseract (Local)</SelectItem>
                                        <SelectItem value="cloud_vision">Google Cloud Vision</SelectItem>
                                        <SelectItem value="azure_vision">Azure Computer Vision</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-end">
                                <Button
                                    onClick={handleProcessBatch}
                                    disabled={selectedDocuments.length === 0}
                                    className="w-full"
                                >
                                    <Scan className="h-4 w-4 mr-2" />
                                    Procesar Seleccionados ({selectedDocuments.length})
                                </Button>
                            </div>

                            <div className="flex items-end">
                                <div className="text-sm text-muted-foreground">
                                    <strong>Formatos soportados:</strong><br />
                                    {getSupportedFormats().join(', ').toUpperCase()}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Lista de documentos */}
                <Card>
                    <CardHeader>
                        <CardTitle>Documentos ({documentos.total})</CardTitle>
                        <CardDescription>
                            Documentos disponibles para procesamiento OCR
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {documentos.data.map((doc) => (
                                <div
                                    key={doc.id}
                                    className="flex items-center gap-4 p-4 border rounded-lg hover:bg-accent/50 transition-colors"
                                >
                                    <input
                                        type="checkbox"
                                        checked={selectedDocuments.includes(doc.id)}
                                        onChange={() => toggleSelection(doc.id)}
                                        className="h-4 w-4"
                                        disabled={!!doc.ocr_processed_at}
                                    />

                                    <div className="p-2 bg-primary/10 rounded">
                                        <FileText className="h-5 w-5 text-primary" />
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <h4 className="font-semibold truncate">{doc.nombre}</h4>
                                        <p className="text-sm text-muted-foreground">
                                            {doc.codigo} • {doc.formato.toUpperCase()} • {formatFileSize(doc.tamanio)}
                                        </p>
                                        {doc.ocr_confidence && (
                                            <p className="text-xs text-muted-foreground mt-1">
                                                Confianza: {doc.ocr_confidence.toFixed(1)}%
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex items-center gap-2">
                                        {getStatusBadge(doc)}
                                        
                                        {!doc.ocr_processed_at && (
                                            <Button
                                                onClick={() => handleProcessSingle(doc.id)}
                                                disabled={processing[doc.id]}
                                                size="sm"
                                            >
                                                {processing[doc.id] ? (
                                                    <>
                                                        <Clock className="h-4 w-4 mr-2 animate-spin" />
                                                        Procesando...
                                                    </>
                                                ) : (
                                                    <>
                                                        <Scan className="h-4 w-4 mr-2" />
                                                        Procesar
                                                    </>
                                                )}
                                            </Button>
                                        )}

                                        {doc.ocr_processed_at && doc.contenido_ocr && (
                                            <Button
                                                onClick={() => {
                                                    // TODO: Mostrar modal con texto extraído
                                                }}
                                                size="sm"
                                                variant="outline"
                                            >
                                                Ver Texto
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>

                        {documentos.data.length === 0 && (
                            <div className="text-center py-12 text-muted-foreground">
                                <FileText className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                <p>No hay documentos disponibles para OCR</p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
