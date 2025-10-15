import React, { useState, useRef } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Progress } from "@/components/ui/progress";
import { Switch } from "@/components/ui/switch";
import { 
    ArrowLeft, 
    Save, 
    FileUp,
    AlertTriangle,
    Info,
    Check,
    Upload,
    X,
    FileText,
    Image,
    Archive,
    Video,
    Headphones,
    Monitor
} from 'lucide-react';
import { toast } from 'sonner';

interface Expediente {
    id: number;
    codigo: string;
    nombre: string;
    serie_id?: number;
    subserie_id?: number;
}

interface Tipologia {
    id: number;
    nombre: string;
    categoria: string;
    formato_archivo?: string[];
}

interface CreateDocumentProps {
    opciones: {
        expedientes: Expediente[];
        tipologias: Tipologia[];
        estados: { value: string; label: string; }[];
        tipos_soporte: { value: string; label: string; }[];
        confidencialidad: { value: string; label: string; }[];
        formatos_permitidos: Record<string, string[]>;
        tamaños_maximos: Record<string, number>;
        configuracion_multimedia?: {
            audio: any;
            video: any;
            imagen: any;
        };
    };
}

interface FormData {
    nombre: string;
    descripcion: string;
    expediente_id: string;
    tipologia_id: string;
    tipo_documental: string;
    tipo_soporte: string;
    estado: string;
    confidencialidad: string;
    numero_folios: number | null;
    palabras_clave: string[];
    ubicacion_fisica: string;
    observaciones: string;
    archivo: File | null;
    // Opciones de procesamiento avanzado
    procesamiento: {
        ocr: boolean;
        convertir: boolean;
        generar_miniatura: boolean;
    };
}

export default function CreateDocument({ opciones }: CreateDocumentProps) {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [uploadProgress, setUploadProgress] = useState(0);
    const [dragActive, setDragActive] = useState(false);
    const [palabrasClave, setPalabrasClave] = useState<string[]>([]);
    const [nuevaPalabra, setNuevaPalabra] = useState('');
    const fileInputRef = useRef<HTMLInputElement>(null);
    
    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        nombre: '',
        descripcion: '',
        expediente_id: '',
        tipologia_id: '',
        tipo_documental: '',
        tipo_soporte: 'electronico',
        estado: 'borrador',
        confidencialidad: 'interna',
        numero_folios: null,
        palabras_clave: [],
        ubicacion_fisica: '',
        observaciones: '',
        archivo: null,
        procesamiento: {
            ocr: true,
            convertir: true,
            generar_miniatura: true
        }
    });

    const breadcrumbItems = [
        { title: 'Inicio', href: '/dashboard' },
        { title: 'Administración', href: '/admin' },
        { title: 'Documentos', href: '/admin/documentos' },
        { title: 'Crear Documento', href: '/admin/documentos/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        // Crear FormData para subida de archivos
        const formData = new FormData();
        Object.entries(data).forEach(([key, value]) => {
            if (key === 'palabras_clave') {
                palabrasClave.forEach((palabra, index) => {
                    formData.append(`palabras_clave[${index}]`, palabra);
                });
            } else if (value !== null && value !== undefined) {
                formData.append(key, value);
            }
        });

        // Simular progreso de subida
        const interval = setInterval(() => {
            setUploadProgress(prev => {
                if (prev >= 90) {
                    clearInterval(interval);
                    return 90;
                }
                return prev + 10;
            });
        }, 200);

        router.post('/admin/documentos', formData, {
            onSuccess: () => {
                setUploadProgress(100);
                toast.success('Documento creado exitosamente');
                setTimeout(() => {
                    clearInterval(interval);
                    router.visit('/admin/documentos');
                }, 1000);
            },
            onError: (errors) => {
                clearInterval(interval);
                setUploadProgress(0);
                toast.error('Error al crear el documento. Revisa los campos marcados.');
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };

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

        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            handleFileChange(e.dataTransfer.files[0]);
        }
    };

    const handleFileChange = (file: File) => {
        setData('archivo', file);
        if (!data.nombre) {
            setData('nombre', file.name);
        }
    };

    const handleFileInput = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            handleFileChange(e.target.files[0]);
        }
    };

    // Agregar palabra clave
    const agregarPalabraClave = () => {
        if (nuevaPalabra.trim() && !palabrasClave.includes(nuevaPalabra.trim())) {
            const nuevas = [...palabrasClave, nuevaPalabra.trim()];
            setPalabrasClave(nuevas);
            setData('palabras_clave', nuevas);
            setNuevaPalabra('');
        }
    };

    // Eliminar palabra clave
    const eliminarPalabraClave = (palabra: string) => {
        const nuevas = palabrasClave.filter(p => p !== palabra);
        setPalabrasClave(nuevas);
        setData('palabras_clave', nuevas);
    };

    // Obtener icono según formato
    const getFormatIcon = (fileName: string) => {
        const extension = fileName.split('.').pop()?.toLowerCase();
        
        if (!extension) return <FileText className="h-8 w-8" />;
        
        if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'].includes(extension)) {
            return <Image className="h-8 w-8 text-blue-500" />;
        }
        if (['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'].includes(extension)) {
            return <Video className="h-8 w-8 text-purple-500" />;
        }
        if (['mp3', 'wav', 'ogg', 'flac', 'm4a'].includes(extension)) {
            return <Headphones className="h-8 w-8 text-green-500" />;
        }
        if (['zip', 'rar', '7z', 'tar', 'gz'].includes(extension)) {
            return <Archive className="h-8 w-8 text-orange-500" />;
        }
        if (['xls', 'xlsx', 'ppt', 'pptx'].includes(extension)) {
            return <Monitor className="h-8 w-8 text-indigo-500" />;
        }
        
        return <FileText className="h-8 w-8 text-gray-500" />;
    };

    // Formatear tamaño de archivo
    const formatFileSize = (bytes: number) => {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    // Obtener opciones de procesamiento disponibles según el tipo de archivo
    const getAvailableProcessing = (fileName: string) => {
        const extension = fileName.split('.').pop()?.toLowerCase();
        const features = [];
        
        if (!extension) return 'No se puede determinar el tipo de archivo';
        
        // OCR disponible para imágenes y PDFs
        if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'pdf'].includes(extension)) {
            features.push('✓ OCR (Reconocimiento de texto)');
        }
        
        // Miniatura para imágenes y videos
        if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'mp4', 'avi', 'mov', 'wmv', 'pdf'].includes(extension)) {
            features.push('✓ Generación de miniatura');
        }
        
        // Conversión para la mayoría de formatos
        if (['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'mp4', 'mp3'].includes(extension)) {
            features.push('✓ Conversión automática');
        }
        
        // Análisis de metadatos
        features.push('✓ Extracción de metadatos');
        features.push('✓ Verificación de integridad');
        
        return features.length > 0 ? features.join(', ') : 'Procesamiento básico disponible';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title="Crear Documento" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <FileUp className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Crear Documento
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

                {/* Progress bar during upload */}
                {isSubmitting && (
                    <Card>
                        <CardContent className="pt-6">
                            <div className="space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span>Subiendo documento...</span>
                                    <span>{uploadProgress}%</span>
                                </div>
                                <Progress value={uploadProgress} className="w-full" />
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Info Alert */}
                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        Sube documentos electrónicos al sistema siguiendo las políticas de formatos y 
                        tamaños establecidas para cada tipo de contenido.
                    </AlertDescription>
                </Alert>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Área de subida de archivos */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Archivo</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
                                    dragActive 
                                        ? 'border-blue-400 bg-blue-50' 
                                        : 'border-gray-300 hover:border-gray-400'
                                }`}
                                onDragEnter={handleDrag}
                                onDragLeave={handleDrag}
                                onDragOver={handleDrag}
                                onDrop={handleDrop}
                            >
                                {data.archivo ? (
                                    <div className="space-y-4">
                                        <div className="flex items-center justify-center">
                                            {getFormatIcon(data.archivo.name)}
                                        </div>
                                        <div>
                                            <p className="font-medium">{data.archivo.name}</p>
                                            <p className="text-sm text-gray-500">
                                                {formatFileSize(data.archivo.size)}
                                            </p>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setData('archivo', null)}
                                        >
                                            <X className="h-4 w-4 mr-2" />
                                            Remover
                                        </Button>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        <Upload className="h-12 w-12 text-gray-400 mx-auto" />
                                        <div>
                                            <p className="text-lg font-medium text-gray-900 mb-2">
                                                Arrastra tu archivo aquí o haz clic para seleccionar
                                            </p>
                                            <p className="text-sm text-gray-500">
                                                Formatos soportados: PDF, DOC, DOCX, JPG, PNG, MP4, MP3 y más
                                            </p>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => fileInputRef.current?.click()}
                                        >
                                            Seleccionar archivo
                                        </Button>
                                    </div>
                                )}
                                
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    className="hidden"
                                    onChange={handleFileInput}
                                    accept="*/*"
                                />
                            </div>
                            
                            {errors.archivo && (
                                <p className="text-sm text-red-600 mt-2">{errors.archivo}</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Información básica */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Información Básica</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="nombre">Nombre del Documento *</Label>
                                    <Input
                                        id="nombre"
                                        type="text"
                                        value={data.nombre}
                                        onChange={(e) => setData('nombre', e.target.value)}
                                        className={errors.nombre ? 'border-red-500' : ''}
                                        required
                                    />
                                    {errors.nombre && (
                                        <p className="text-sm text-red-600 mt-1">{errors.nombre}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="expediente_id">Expediente *</Label>
                                    <Select 
                                        value={data.expediente_id} 
                                        onValueChange={(value) => setData('expediente_id', value)}
                                    >
                                        <SelectTrigger id="expediente_id" className={errors.expediente_id ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Seleccionar expediente" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {opciones.expedientes.map((expediente) => (
                                                <SelectItem key={expediente.id} value={expediente.id.toString()}>
                                                    {expediente.codigo} - {expediente.nombre}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.expediente_id && (
                                        <p className="text-sm text-red-600 mt-1">{errors.expediente_id}</p>
                                    )}
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="descripcion">Descripción</Label>
                                <Textarea
                                    id="descripcion"
                                    value={data.descripcion}
                                    onChange={(e) => setData('descripcion', e.target.value)}
                                    placeholder="Descripción detallada del documento"
                                    rows={3}
                                    className={errors.descripcion ? 'border-red-500' : ''}
                                />
                                {errors.descripcion && (
                                    <p className="text-sm text-red-600 mt-1">{errors.descripcion}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Clasificación */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Clasificación</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="tipologia_id">Tipología Documental</Label>
                                    <Select 
                                        value={data.tipologia_id} 
                                        onValueChange={(value) => setData('tipologia_id', value)}
                                    >
                                        <SelectTrigger id="tipologia_id">
                                            <SelectValue placeholder="Seleccionar tipología" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="null">Sin tipología</SelectItem>
                                            {opciones.tipologias.map((tipologia) => (
                                                <SelectItem key={tipologia.id} value={tipologia.id.toString()}>
                                                    {tipologia.nombre} ({tipologia.categoria})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label htmlFor="tipo_documental">Tipo Documental</Label>
                                    <Input
                                        id="tipo_documental"
                                        type="text"
                                        value={data.tipo_documental}
                                        onChange={(e) => setData('tipo_documental', e.target.value)}
                                        placeholder="Ej: Contrato, Factura, Informe"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="tipo_soporte">Tipo de Soporte *</Label>
                                    <Select 
                                        value={data.tipo_soporte} 
                                        onValueChange={(value) => setData('tipo_soporte', value)}
                                    >
                                        <SelectTrigger id="tipo_soporte">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {opciones.tipos_soporte.map((tipo) => (
                                                <SelectItem key={tipo.value} value={tipo.value}>
                                                    {tipo.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label htmlFor="numero_folios">Número de Folios</Label>
                                    <Input
                                        id="numero_folios"
                                        type="number"
                                        min="1"
                                        value={data.numero_folios || ''}
                                        onChange={(e) => setData('numero_folios', parseInt(e.target.value) || null)}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Estado y Confidencialidad */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Estado y Confidencialidad</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="estado">Estado *</Label>
                                    <Select 
                                        value={data.estado} 
                                        onValueChange={(value) => setData('estado', value)}
                                    >
                                        <SelectTrigger id="estado">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {opciones.estados.map((estado) => (
                                                <SelectItem key={estado.value} value={estado.value}>
                                                    {estado.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label htmlFor="confidencialidad">Confidencialidad *</Label>
                                    <Select 
                                        value={data.confidencialidad} 
                                        onValueChange={(value) => setData('confidencialidad', value)}
                                    >
                                        <SelectTrigger id="confidencialidad">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {opciones.confidencialidad.map((conf) => (
                                                <SelectItem key={conf.value} value={conf.value}>
                                                    {conf.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Palabras clave */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Palabras Clave</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex gap-2">
                                <Input
                                    value={nuevaPalabra}
                                    onChange={(e) => setNuevaPalabra(e.target.value)}
                                    placeholder="Agregar palabra clave"
                                    onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), agregarPalabraClave())}
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={agregarPalabraClave}
                                >
                                    Agregar
                                </Button>
                            </div>
                            
                            {palabrasClave.length > 0 && (
                                <div className="flex flex-wrap gap-2">
                                    {palabrasClave.map((palabra) => (
                                        <span
                                            key={palabra}
                                            className="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800"
                                        >
                                            {palabra}
                                            <button
                                                type="button"
                                                onClick={() => eliminarPalabraClave(palabra)}
                                                className="hover:text-blue-600"
                                            >
                                                <X className="h-3 w-3" />
                                            </button>
                                        </span>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Información adicional */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Información Adicional</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label htmlFor="ubicacion_fisica">Ubicación Física</Label>
                                <Input
                                    id="ubicacion_fisica"
                                    type="text"
                                    value={data.ubicacion_fisica}
                                    onChange={(e) => setData('ubicacion_fisica', e.target.value)}
                                    placeholder="Ej: Archivo Central, Estante A-3, Caja 15"
                                />
                            </div>

                            <div>
                                <Label htmlFor="observaciones">Observaciones</Label>
                                <Textarea
                                    id="observaciones"
                                    value={data.observaciones}
                                    onChange={(e) => setData('observaciones', e.target.value)}
                                    placeholder="Observaciones adicionales"
                                    rows={3}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Opciones de Procesamiento Avanzado */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Monitor className="h-5 w-5" />
                                Procesamiento Avanzado
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <Alert>
                                <Info className="h-4 w-4" />
                                <AlertDescription>
                                    Configura las opciones de procesamiento automático que se aplicarán al documento.
                                </AlertDescription>
                            </Alert>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                {/* OCR para imágenes */}
                                <div className="flex items-center justify-between space-x-2 p-4 border rounded-lg">
                                    <div className="space-y-1">
                                        <Label htmlFor="ocr">Reconocimiento OCR</Label>
                                        <p className="text-sm text-gray-500">
                                            Extraer texto de imágenes y documentos escaneados
                                        </p>
                                    </div>
                                    <Switch
                                        id="ocr"
                                        checked={data.procesamiento.ocr}
                                        onCheckedChange={(checked) => 
                                            setData('procesamiento', { 
                                                ...data.procesamiento, 
                                                ocr: checked 
                                            })
                                        }
                                    />
                                </div>

                                {/* Conversión automática */}
                                <div className="flex items-center justify-between space-x-2 p-4 border rounded-lg">
                                    <div className="space-y-1">
                                        <Label htmlFor="convertir">Conversión Automática</Label>
                                        <p className="text-sm text-gray-500">
                                            Convertir a formatos optimizados para archivo
                                        </p>
                                    </div>
                                    <Switch
                                        id="convertir"
                                        checked={data.procesamiento.convertir}
                                        onCheckedChange={(checked) => 
                                            setData('procesamiento', { 
                                                ...data.procesamiento, 
                                                convertir: checked 
                                            })
                                        }
                                    />
                                </div>

                                {/* Generar miniatura */}
                                <div className="flex items-center justify-between space-x-2 p-4 border rounded-lg">
                                    <div className="space-y-1">
                                        <Label htmlFor="miniatura">Generar Miniatura</Label>
                                        <p className="text-sm text-gray-500">
                                            Crear vista previa para imágenes y videos
                                        </p>
                                    </div>
                                    <Switch
                                        id="miniatura"
                                        checked={data.procesamiento.generar_miniatura}
                                        onCheckedChange={(checked) => 
                                            setData('procesamiento', { 
                                                ...data.procesamiento, 
                                                generar_miniatura: checked 
                                            })
                                        }
                                    />
                                </div>
                            </div>

                            {/* Información de formatos soportados */}
                            {data.archivo && (
                                <div className="mt-4 p-4 bg-blue-50 rounded-lg">
                                    <h4 className="font-medium text-blue-900 mb-2">
                                        Procesamiento disponible para este archivo:
                                    </h4>
                                    <div className="text-sm text-blue-700">
                                        {getAvailableProcessing(data.archivo.name)}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Botones de acción */}
                    <div className="flex justify-end gap-3 pt-6">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit('/admin/documentos')}
                            disabled={processing || isSubmitting}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing || isSubmitting}
                            className="bg-[#2a3d83] hover:bg-[#1e2b5f] flex items-center gap-2"
                        >
                            <Save className="h-4 w-4" />
                            {processing || isSubmitting ? 'Guardando...' : 'Crear Documento'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
