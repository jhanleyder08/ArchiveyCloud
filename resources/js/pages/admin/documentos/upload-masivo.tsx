import React, { useState, useRef } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Progress } from "@/components/ui/progress";
import { Badge } from "@/components/ui/badge";
import { 
    ArrowLeft, 
    Upload,
    AlertTriangle,
    Info,
    Check,
    X,
    FileText,
    Image,
    Archive,
    Video,
    Headphones,
    Monitor,
    CloudUpload,
    Trash2
} from 'lucide-react';
import { toast } from 'sonner';

interface Expediente {
    id: number;
    codigo: string;
    nombre: string;
}

interface UploadMasivoProps {
    expedientes: Expediente[];
}

interface ArchivoSubida {
    file: File;
    id: string;
    status: 'pending' | 'uploading' | 'success' | 'error';
    progress: number;
    error?: string;
    codigo?: string;
}

interface ConfiguracionMasiva {
    expediente_id: string;
    estado_default: string;
    confidencialidad_default: string;
    tipo_soporte_default: string;
}

export default function UploadMasivo({ expedientes }: UploadMasivoProps) {
    const [archivos, setArchivos] = useState<ArchivoSubida[]>([]);
    const [dragActive, setDragActive] = useState(false);
    const [isProcessing, setIsProcessing] = useState(false);
    const [overallProgress, setOverallProgress] = useState(0);
    const [resultados, setResultados] = useState<any>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);
    
    const { data, setData } = useForm<ConfiguracionMasiva>({
        expediente_id: '',
        estado_default: 'borrador',
        confidencialidad_default: 'interna',
        tipo_soporte_default: 'electronico',
    });

    const breadcrumbItems = [
        { title: 'Inicio', href: '/dashboard' },
        { title: 'Administración', href: '/admin' },
        { title: 'Documentos', href: '/admin/documentos' },
        { title: 'Subida Masiva', href: '/admin/documentos/upload/masivo' },
    ];

    // Generar ID único para archivos
    const generateId = () => Math.random().toString(36).substr(2, 9);

    // Manejo de drag & drop
    const handleDrag = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.type === "dragenter" || e.type === "dragover") {
            setDragActive(true);
        } else if (e.type === "dragleave") {
            setDragActive(false);
        }
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);

        const files = Array.from(e.dataTransfer.files);
        handleMultipleFiles(files);
    };

    const handleFileInput = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            const files = Array.from(e.target.files);
            handleMultipleFiles(files);
        }
    };

    const handleMultipleFiles = (files: File[]) => {
        const nuevosArchivos = files.map(file => ({
            file,
            id: generateId(),
            status: 'pending' as const,
            progress: 0,
        }));
        
        setArchivos(prev => [...prev, ...nuevosArchivos]);
    };

    // Eliminar archivo individual
    const eliminarArchivo = (id: string) => {
        setArchivos(prev => prev.filter(archivo => archivo.id !== id));
    };

    // Limpiar todos los archivos
    const limpiarArchivos = () => {
        setArchivos([]);
        setResultados(null);
        setOverallProgress(0);
    };

    // Procesar subida masiva
    const procesarSubidaMasiva = async () => {
        if (archivos.length === 0) {
            toast.error('No hay archivos seleccionados');
            return;
        }

        if (!data.expediente_id) {
            toast.error('Selecciona un expediente');
            return;
        }

        setIsProcessing(true);
        setOverallProgress(0);

        const formData = new FormData();
        
        // Agregar archivos
        archivos.forEach((archivo, index) => {
            formData.append(`archivos[${index}]`, archivo.file);
        });

        // Agregar configuración
        formData.append('expediente_id', data.expediente_id);
        formData.append('configuracion[estado_default]', data.estado_default);
        formData.append('configuracion[confidencialidad_default]', data.confidencialidad_default);
        formData.append('configuracion[tipo_soporte_default]', data.tipo_soporte_default);

        try {
            // Simular progreso
            const progressInterval = setInterval(() => {
                setOverallProgress(prev => {
                    if (prev >= 90) {
                        clearInterval(progressInterval);
                        return 90;
                    }
                    return prev + 10;
                });
            }, 300);

            // Realizar petición
            const response = await fetch('/admin/documentos/upload/masivo', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const result = await response.json();
            
            clearInterval(progressInterval);
            setOverallProgress(100);

            if (result.success) {
                setResultados(result.resultados);
                toast.success(result.mensaje);
                
                // Actualizar estados de archivos
                setArchivos(prev => prev.map(archivo => {
                    const detalle = result.resultados.detalles.find(
                        (d: any) => d.archivo === archivo.file.name
                    );
                    
                    if (detalle) {
                        return {
                            ...archivo,
                            status: detalle.estado === 'exitoso' ? 'success' : 'error',
                            progress: 100,
                            error: detalle.mensaje,
                            codigo: detalle.codigo,
                        };
                    }
                    
                    return archivo;
                }));
            } else {
                toast.error(result.mensaje);
            }

        } catch (error) {
            setOverallProgress(0);
            toast.error('Error en la subida masiva');
        } finally {
            setIsProcessing(false);
        }
    };

    // Obtener icono según formato
    const getFormatIcon = (fileName: string) => {
        const extension = fileName.split('.').pop()?.toLowerCase();
        
        if (!extension) return <FileText className="h-5 w-5" />;
        
        if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'].includes(extension)) {
            return <Image className="h-5 w-5 text-blue-500" />;
        }
        if (['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'].includes(extension)) {
            return <Video className="h-5 w-5 text-purple-500" />;
        }
        if (['mp3', 'wav', 'ogg', 'flac', 'm4a'].includes(extension)) {
            return <Headphones className="h-5 w-5 text-green-500" />;
        }
        if (['zip', 'rar', '7z', 'tar', 'gz'].includes(extension)) {
            return <Archive className="h-5 w-5 text-orange-500" />;
        }
        if (['xls', 'xlsx', 'ppt', 'pptx'].includes(extension)) {
            return <Monitor className="h-5 w-5 text-indigo-500" />;
        }
        
        return <FileText className="h-5 w-5 text-gray-500" />;
    };

    // Formatear tamaño de archivo
    const formatFileSize = (bytes: number) => {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    // Obtener badge de estado
    const getStatusBadge = (status: ArchivoSubida['status'], error?: string) => {
        switch (status) {
            case 'pending':
                return <Badge variant="secondary">Pendiente</Badge>;
            case 'uploading':
                return <Badge variant="default">Subiendo...</Badge>;
            case 'success':
                return <Badge variant="default" className="bg-green-100 text-green-800">Exitoso</Badge>;
            case 'error':
                return (
                    <Badge 
                        variant="destructive" 
                        title={error}
                        className="cursor-help"
                    >
                        Error
                    </Badge>
                );
            default:
                return null;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title="Subida Masiva de Documentos" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <CloudUpload className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Subida Masiva de Documentos
                        </h1>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => router.visit('/admin/documentos')}
                        className="flex items-center gap-2"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Volver
                    </Button>
                </div>

                {/* Progress bar durante procesamiento */}
                {isProcessing && (
                    <Card>
                        <CardContent className="pt-6">
                            <div className="space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span>Procesando documentos...</span>
                                    <span>{overallProgress}%</span>
                                </div>
                                <Progress value={overallProgress} className="w-full" />
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Configuración */}
                <Card>
                    <CardHeader>
                        <CardTitle>Configuración de Subida</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Alert>
                            <Info className="h-4 w-4" />
                            <AlertDescription>
                                Configura las opciones predeterminadas que se aplicarán a todos los documentos 
                                que subas de forma masiva.
                            </AlertDescription>
                        </Alert>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <Label htmlFor="expediente_id">Expediente *</Label>
                                <Select 
                                    value={data.expediente_id} 
                                    onValueChange={(value) => setData('expediente_id', value)}
                                >
                                    <SelectTrigger id="expediente_id">
                                        <SelectValue placeholder="Seleccionar expediente" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {expedientes.map((expediente) => (
                                            <SelectItem key={expediente.id} value={expediente.id.toString()}>
                                                {expediente.codigo} - {expediente.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label htmlFor="estado_default">Estado por Defecto</Label>
                                <Select 
                                    value={data.estado_default} 
                                    onValueChange={(value) => setData('estado_default', value)}
                                >
                                    <SelectTrigger id="estado_default">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="borrador">Borrador</SelectItem>
                                        <SelectItem value="pendiente">Pendiente</SelectItem>
                                        <SelectItem value="activo">Activo</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label htmlFor="confidencialidad_default">Confidencialidad</Label>
                                <Select 
                                    value={data.confidencialidad_default} 
                                    onValueChange={(value) => setData('confidencialidad_default', value)}
                                >
                                    <SelectTrigger id="confidencialidad_default">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="publica">Pública</SelectItem>
                                        <SelectItem value="interna">Interna</SelectItem>
                                        <SelectItem value="confidencial">Confidencial</SelectItem>
                                        <SelectItem value="reservada">Reservada</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label htmlFor="tipo_soporte_default">Tipo de Soporte</Label>
                                <Select 
                                    value={data.tipo_soporte_default} 
                                    onValueChange={(value) => setData('tipo_soporte_default', value)}
                                >
                                    <SelectTrigger id="tipo_soporte_default">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="electronico">Electrónico</SelectItem>
                                        <SelectItem value="fisico">Físico</SelectItem>
                                        <SelectItem value="hibrido">Híbrido</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Área de subida */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            Archivos ({archivos.length})
                            {archivos.length > 0 && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={limpiarArchivos}
                                    disabled={isProcessing}
                                >
                                    <Trash2 className="h-4 w-4 mr-2" />
                                    Limpiar Todo
                                </Button>
                            )}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {/* Drop zone */}
                        <div
                            className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors mb-6 ${
                                dragActive 
                                    ? 'border-blue-400 bg-blue-50' 
                                    : 'border-gray-300 hover:border-gray-400'
                            }`}
                            onDragEnter={handleDrag}
                            onDragLeave={handleDrag}
                            onDragOver={handleDrag}
                            onDrop={handleDrop}
                        >
                            <CloudUpload className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                            <p className="text-lg font-medium text-gray-900 mb-2">
                                Arrastra múltiples archivos aquí o haz clic para seleccionar
                            </p>
                            <p className="text-sm text-gray-500 mb-4">
                                Máximo 50 archivos. Formatos soportados: PDF, DOC, DOCX, JPG, PNG, MP4, MP3 y más
                            </p>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => fileInputRef.current?.click()}
                                disabled={isProcessing}
                            >
                                Seleccionar Archivos
                            </Button>
                            
                            <input
                                ref={fileInputRef}
                                type="file"
                                className="hidden"
                                onChange={handleFileInput}
                                multiple
                                accept="*/*"
                            />
                        </div>

                        {/* Lista de archivos */}
                        {archivos.length > 0 && (
                            <div className="space-y-2 mb-6">
                                {archivos.map((archivo) => (
                                    <div 
                                        key={archivo.id}
                                        className="flex items-center gap-3 p-3 border rounded-lg"
                                    >
                                        <div className="flex-shrink-0">
                                            {getFormatIcon(archivo.file.name)}
                                        </div>
                                        
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-medium text-gray-900 truncate">
                                                {archivo.file.name}
                                            </p>
                                            <p className="text-xs text-gray-500">
                                                {formatFileSize(archivo.file.size)}
                                                {archivo.codigo && (
                                                    <span className="ml-2 text-green-600">
                                                        → {archivo.codigo}
                                                    </span>
                                                )}
                                            </p>
                                            
                                            {archivo.status === 'uploading' && (
                                                <div className="mt-1">
                                                    <Progress value={archivo.progress} className="h-1" />
                                                </div>
                                            )}
                                        </div>
                                        
                                        <div className="flex-shrink-0 flex items-center gap-2">
                                            {getStatusBadge(archivo.status, archivo.error)}
                                            
                                            {archivo.status === 'pending' && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => eliminarArchivo(archivo.id)}
                                                    disabled={isProcessing}
                                                >
                                                    <X className="h-4 w-4" />
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Botón de procesamiento */}
                        {archivos.length > 0 && (
                            <div className="flex justify-center">
                                <Button
                                    onClick={procesarSubidaMasiva}
                                    disabled={isProcessing || !data.expediente_id}
                                    className="bg-[#2a3d83] hover:bg-[#1e2b5f] flex items-center gap-2"
                                >
                                    <Upload className="h-4 w-4" />
                                    {isProcessing ? 'Procesando...' : `Procesar ${archivos.length} Archivos`}
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Resultados */}
                {resultados && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Check className="h-5 w-5 text-green-500" />
                                Resultados del Procesamiento
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div className="text-center p-4 bg-green-50 rounded-lg">
                                    <p className="text-2xl font-bold text-green-600">{resultados.exitosos}</p>
                                    <p className="text-sm text-green-700">Documentos Creados</p>
                                </div>
                                <div className="text-center p-4 bg-red-50 rounded-lg">
                                    <p className="text-2xl font-bold text-red-600">{resultados.errores}</p>
                                    <p className="text-sm text-red-700">Errores</p>
                                </div>
                                <div className="text-center p-4 bg-gray-50 rounded-lg">
                                    <p className="text-2xl font-bold text-gray-600">{archivos.length}</p>
                                    <p className="text-sm text-gray-700">Total Procesados</p>
                                </div>
                            </div>
                            
                            <div className="flex justify-center">
                                <Button
                                    onClick={() => router.visit('/admin/documentos')}
                                    className="flex items-center gap-2"
                                >
                                    Ver Documentos Creados
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
