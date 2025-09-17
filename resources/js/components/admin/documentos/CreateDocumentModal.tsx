import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { CloudArrowUpIcon, DocumentTextIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    expedientes: Array<{ id: number; numero_expediente: string; titulo: string; }>;
    tipologias: Array<{ id: number; nombre: string; categoria: string; }>;
    formatosDisponibles: string[];
    tiposSoporte: Record<string, string>;
    nivelesConfidencialidad: Record<string, string>;
}

interface FormData {
    nombre: string;
    descripcion: string;
    expediente_id: string;
    tipologia_id: string;
    tipo_soporte: string;
    confidencialidad: string;
    palabras_clave: string[];
    ubicacion_fisica: string;
    observaciones: string;
    archivo: File | null;
}

const CreateDocumentModal = ({
    open,
    onOpenChange,
    expedientes,
    tipologias,
    formatosDisponibles,
    tiposSoporte,
    nivelesConfidencialidad
}: Props) => {
    const [dragActive, setDragActive] = useState(false);
    const [previewFile, setPreviewFile] = useState<File | null>(null);
    const [palabrasClaveInput, setPalabrasClaveInput] = useState('');

    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        nombre: '',
        descripcion: '',
        expediente_id: '',
        tipologia_id: '',
        tipo_soporte: 'electronico',
        confidencialidad: 'publica',
        palabras_clave: [],
        ubicacion_fisica: '',
        observaciones: '',
        archivo: null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        const formData = new FormData();
        Object.keys(data).forEach(key => {
            const value = data[key as keyof FormData];
            if (key === 'palabras_clave') {
                formData.append(key, JSON.stringify(value));
            } else if (key === 'archivo' && value instanceof File) {
                formData.append(key, value);
            } else if (value !== null && value !== '') {
                formData.append(key, value as string);
            }
        });

        post('/admin/documentos', {
            onSuccess: () => {
                reset();
                setPreviewFile(null);
                setPalabrasClaveInput('');
                onOpenChange(false);
            },
        });
    };

    const handleFileSelect = (file: File) => {
        const extension = file.name.split('.').pop()?.toLowerCase() || '';
        
        // REQ-CP-007: Validación de formatos
        if (!formatosDisponibles.includes(extension)) {
            alert(`Formato de archivo no permitido. Formatos válidos: ${formatosDisponibles.join(', ')}`);
            return;
        }

        setData('archivo', file);
        setPreviewFile(file);
        
        // Auto-llenar nombre si está vacío
        if (!data.nombre) {
            setData('nombre', file.name.replace(/\.[^/.]+$/, ''));
        }
    };

    const handleDrag = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.type === 'dragenter' || e.type === 'dragover') {
            setDragActive(true);
        } else if (e.type === 'dragleave') {
            setDragActive(false);
        }
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);
        
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            handleFileSelect(e.dataTransfer.files[0]);
        }
    };

    const handleFileInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            handleFileSelect(e.target.files[0]);
        }
    };

    const addPalabraClave = () => {
        if (palabrasClaveInput.trim() && !data.palabras_clave.includes(palabrasClaveInput.trim())) {
            setData('palabras_clave', [...data.palabras_clave, palabrasClaveInput.trim()]);
            setPalabrasClaveInput('');
        }
    };

    const removePalabraClave = (palabra: string) => {
        setData('palabras_clave', data.palabras_clave.filter(p => p !== palabra));
    };

    const formatFileSize = (bytes: number) => {
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    };

    const getFileIcon = (file: File) => {
        const extension = file.name.split('.').pop()?.toLowerCase();
        // Retornar icono apropiado según extensión
        return <DocumentTextIcon className="h-8 w-8 text-blue-500" />;
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[800px] max-h-[80vh] overflow-y-scroll scrollbar-none">
                <DialogHeader>
                    <DialogTitle className="flex items-center space-x-2">
                        <CloudArrowUpIcon className="h-5 w-5 text-[#2a3d83]" />
                        <span>Nuevo Documento</span>
                    </DialogTitle>
                    <DialogDescription>
                        Carga y registra un nuevo documento en el sistema SGDEA
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Sección de carga de archivo */}
                    <div className="space-y-4">
                        <Label>Archivo del Documento</Label>
                        
                        {!previewFile ? (
                            <div
                                className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
                                    dragActive 
                                        ? 'border-[#2a3d83] bg-blue-50' 
                                        : 'border-gray-300 hover:border-gray-400'
                                }`}
                                onDragEnter={handleDrag}
                                onDragLeave={handleDrag}
                                onDragOver={handleDrag}
                                onDrop={handleDrop}
                            >
                                <CloudArrowUpIcon className="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <div className="text-lg font-medium text-gray-900 mb-2">
                                    Arrastra tu archivo aquí o haz clic para seleccionar
                                </div>
                                <div className="text-sm text-gray-500 mb-4">
                                    Formatos soportados: {formatosDisponibles.slice(0, 5).join(', ')}
                                    {formatosDisponibles.length > 5 && ` y ${formatosDisponibles.length - 5} más`}
                                </div>
                                <input
                                    type="file"
                                    id="archivo"
                                    className="hidden"
                                    onChange={handleFileInputChange}
                                    accept={formatosDisponibles.map(f => `.${f}`).join(',')}
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => document.getElementById('archivo')?.click()}
                                >
                                    Seleccionar archivo
                                </Button>
                            </div>
                        ) : (
                            <div className="border rounded-lg p-4 bg-gray-50">
                                <div className="flex items-center space-x-3">
                                    {getFileIcon(previewFile)}
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-900 truncate">
                                            {previewFile.name}
                                        </p>
                                        <p className="text-sm text-gray-500">
                                            {formatFileSize(previewFile.size)}
                                        </p>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => {
                                            setPreviewFile(null);
                                            setData('archivo', null);
                                        }}
                                    >
                                        Cambiar
                                    </Button>
                                </div>
                            </div>
                        )}

                        {errors.archivo && (
                            <Alert variant="destructive">
                                <ExclamationTriangleIcon className="h-4 w-4" />
                                <AlertDescription>{errors.archivo}</AlertDescription>
                            </Alert>
                        )}
                    </div>

                    {/* Información básica */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="nombre">Nombre del Documento *</Label>
                            <Input
                                id="nombre"
                                value={data.nombre}
                                onChange={(e) => setData('nombre', e.target.value)}
                                placeholder="Ingrese el nombre del documento"
                                className={errors.nombre ? 'border-red-500' : ''}
                            />
                            {errors.nombre && <p className="text-sm text-red-500">{errors.nombre}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="expediente_id">Expediente *</Label>
                            <Select value={data.expediente_id} onValueChange={(value) => setData('expediente_id', value)}>
                                <SelectTrigger className={errors.expediente_id ? 'border-red-500' : ''}>
                                    <SelectValue placeholder="Seleccionar expediente" />
                                </SelectTrigger>
                                <SelectContent>
                                    {expedientes.map((expediente) => (
                                        <SelectItem key={expediente.id} value={expediente.id.toString()}>
                                            {expediente.numero_expediente} - {expediente.titulo}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.expediente_id && <p className="text-sm text-red-500">{errors.expediente_id}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="tipologia_id">Tipología Documental *</Label>
                            <Select value={data.tipologia_id} onValueChange={(value) => setData('tipologia_id', value)}>
                                <SelectTrigger className={errors.tipologia_id ? 'border-red-500' : ''}>
                                    <SelectValue placeholder="Seleccionar tipología" />
                                </SelectTrigger>
                                <SelectContent>
                                    {tipologias.map((tipologia) => (
                                        <SelectItem key={tipologia.id} value={tipologia.id.toString()}>
                                            {tipologia.nombre} ({tipologia.categoria})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.tipologia_id && <p className="text-sm text-red-500">{errors.tipologia_id}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="tipo_soporte">Tipo de Soporte *</Label>
                            <Select value={data.tipo_soporte} onValueChange={(value) => setData('tipo_soporte', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(tiposSoporte).map(([key, label]) => (
                                        <SelectItem key={key} value={key}>{label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.tipo_soporte && <p className="text-sm text-red-500">{errors.tipo_soporte}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="confidencialidad">Nivel de Confidencialidad *</Label>
                            <Select value={data.confidencialidad} onValueChange={(value) => setData('confidencialidad', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(nivelesConfidencialidad).map(([key, label]) => (
                                        <SelectItem key={key} value={key}>{label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.confidencialidad && <p className="text-sm text-red-500">{errors.confidencialidad}</p>}
                        </div>
                    </div>

                    {/* Descripción */}
                    <div className="space-y-2">
                        <Label htmlFor="descripcion">Descripción</Label>
                        <Textarea
                            id="descripcion"
                            value={data.descripcion}
                            onChange={(e) => setData('descripcion', e.target.value)}
                            placeholder="Descripción detallada del documento"
                            rows={3}
                        />
                        {errors.descripcion && <p className="text-sm text-red-500">{errors.descripcion}</p>}
                    </div>

                    {/* Palabras clave */}
                    <div className="space-y-2">
                        <Label>Palabras Clave</Label>
                        <div className="flex space-x-2">
                            <Input
                                value={palabrasClaveInput}
                                onChange={(e) => setPalabrasClaveInput(e.target.value)}
                                placeholder="Agregar palabra clave"
                                onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), addPalabraClave())}
                            />
                            <Button type="button" variant="outline" onClick={addPalabraClave}>
                                Agregar
                            </Button>
                        </div>
                        {data.palabras_clave.length > 0 && (
                            <div className="flex flex-wrap gap-2 mt-2">
                                {data.palabras_clave.map((palabra, index) => (
                                    <span
                                        key={index}
                                        className="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800"
                                    >
                                        {palabra}
                                        <button
                                            type="button"
                                            onClick={() => removePalabraClave(palabra)}
                                            className="ml-1 text-blue-600 hover:text-blue-800"
                                        >
                                            ×
                                        </button>
                                    </span>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Ubicación física y observaciones */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="ubicacion_fisica">Ubicación Física</Label>
                            <Input
                                id="ubicacion_fisica"
                                value={data.ubicacion_fisica}
                                onChange={(e) => setData('ubicacion_fisica', e.target.value)}
                                placeholder="Ej: Archivo Central, Estante A1, Caja 15"
                            />
                            {errors.ubicacion_fisica && <p className="text-sm text-red-500">{errors.ubicacion_fisica}</p>}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="observaciones">Observaciones</Label>
                        <Textarea
                            id="observaciones"
                            value={data.observaciones}
                            onChange={(e) => setData('observaciones', e.target.value)}
                            placeholder="Observaciones adicionales sobre el documento"
                            rows={2}
                        />
                        {errors.observaciones && <p className="text-sm text-red-500">{errors.observaciones}</p>}
                    </div>

                    {/* Botones de acción */}
                    <div className="flex justify-end space-x-3 pt-4 border-t">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={processing}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-[#2a3d83] hover:bg-[#1e2a5c]"
                        >
                            {processing ? 'Guardando...' : 'Crear Documento'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
};

export default CreateDocumentModal;
