import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { 
    ArrowLeft, 
    Upload, 
    FileText, 
    AlertTriangle, 
    CheckCircle, 
    Settings,
    Info,
    Plus,
    X,
    HelpCircle
} from 'lucide-react';
import AppLayout from '../../../layouts/app/app-sidebar-layout';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Input } from '../../../components/ui/input';
import { Label } from '../../../components/ui/label';
import { Textarea } from '../../../components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { Checkbox } from '../../../components/ui/checkbox';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Badge } from '../../../components/ui/badge';

interface Props {
    tipos: { [key: string]: string };
    formatosPermitidos: { [key: string]: string };
}

interface ConfiguracionMapeo {
    [campo: string]: string;
}

interface ConfiguracionFormData {
    mapeo: ConfiguracionMapeo;
    actualizar_existentes: boolean;
    validaciones_personalizadas: string[];
}

const tiposIcons = {
    expedientes: '📁',
    documentos: '📄',
    series: '📚',
    subseries: '📖',
    usuarios: '👥',
    trd: '📋',
    certificados: '🔐',
    mixto: '🔄'
};

const formatosInfo = {
    csv: {
        icon: '📊',
        descripcion: 'Archivos separados por comas',
        ejemplos: ['datos.csv', 'expedientes.csv'],
        tamanoMax: '10 MB'
    },
    excel: {
        icon: '📈',
        descripcion: 'Hojas de cálculo Excel',
        ejemplos: ['datos.xlsx', 'documentos.xls'],
        tamanoMax: '20 MB'
    },
    json: {
        icon: '⚙️',
        descripcion: 'Archivos de intercambio JSON',
        ejemplos: ['datos.json', 'usuarios.json'],
        tamanoMax: '15 MB'
    },
    xml: {
        icon: '🏷️',
        descripcion: 'Documentos XML estructurados',
        ejemplos: ['datos.xml', 'series.xml'],
        tamanoMax: '15 MB'
    }
};

const camposPorTipo = {
    expedientes: [
        { campo: 'codigo', nombre: 'Código', requerido: true },
        { campo: 'nombre', nombre: 'Nombre', requerido: true },
        { campo: 'descripcion', nombre: 'Descripción', requerido: false },
        { campo: 'fecha_inicio', nombre: 'Fecha Inicio', requerido: false },
        { campo: 'fecha_fin', nombre: 'Fecha Fin', requerido: false },
        { campo: 'estado', nombre: 'Estado', requerido: false },
        { campo: 'nivel_acceso', nombre: 'Nivel de Acceso', requerido: false }
    ],
    documentos: [
        { campo: 'nombre', nombre: 'Nombre', requerido: true },
        { campo: 'descripcion', nombre: 'Descripción', requerido: false },
        { campo: 'ruta_archivo', nombre: 'Ruta del Archivo', requerido: false },
        { campo: 'tipo_archivo', nombre: 'Tipo de Archivo', requerido: false },
        { campo: 'tamaño_archivo', nombre: 'Tamaño', requerido: false }
    ],
    series: [
        { campo: 'codigo', nombre: 'Código', requerido: true },
        { campo: 'nombre', nombre: 'Nombre', requerido: true },
        { campo: 'descripcion', nombre: 'Descripción', requerido: false },
        { campo: 'tiempo_archivo_central', nombre: 'Tiempo Archivo Central', requerido: false },
        { campo: 'tiempo_archivo_gestion', nombre: 'Tiempo Archivo Gestión', requerido: false },
        { campo: 'disposicion_final', nombre: 'Disposición Final', requerido: false }
    ],
    usuarios: [
        { campo: 'name', nombre: 'Nombre', requerido: true },
        { campo: 'email', nombre: 'Email', requerido: true },
        { campo: 'password', nombre: 'Contraseña', requerido: false }
    ],
    certificados: [
        { campo: 'nombre_certificado', nombre: 'Nombre', requerido: true },
        { campo: 'numero_serie', nombre: 'Número de Serie', requerido: true },
        { campo: 'fecha_vencimiento', nombre: 'Fecha Vencimiento', requerido: false },
        { campo: 'tipo_certificado', nombre: 'Tipo', requerido: false }
    ]
};

export default function CrearImportacion({ tipos, formatosPermitidos }: Props) {
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [previewData, setPreviewData] = useState<any>(null);
    const [configuracion, setConfiguracion] = useState<ConfiguracionFormData>({
        mapeo: {},
        actualizar_existentes: false,
        validaciones_personalizadas: []
    });

    const { data, setData, post, processing, errors } = useForm({
        nombre: '',
        descripcion: '',
        tipo: '',
        archivo: null as File | null,
        configuracion: configuracion
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setSelectedFile(file);
            setData('archivo', file);
            
            // Aquí podrías agregar lógica para preview del archivo
            // Por ejemplo, leer las primeras líneas de un CSV
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('nombre', data.nombre);
        formData.append('descripcion', data.descripcion);
        formData.append('tipo', data.tipo);
        if (data.archivo) {
            formData.append('archivo', data.archivo);
        }
        formData.append('configuracion', JSON.stringify(configuracion));

        post('/admin/importaciones', {
            data: formData,
            forceFormData: true
        });
    };

    const actualizarMapeo = (campo: string, valor: string) => {
        setConfiguracion(prev => ({
            ...prev,
            mapeo: {
                ...prev.mapeo,
                [campo]: valor
            }
        }));
    };

    const camposDisponibles = data.tipo ? camposPorTipo[data.tipo as keyof typeof camposPorTipo] || [] : [];

    return (
        <AppLayout>
            <Head title="Nueva Importación" />
            
            <div className="container mx-auto p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button variant="outline" asChild>
                        <Link href="/admin/importaciones">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Volver
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Nueva Importación</h1>
                        <p className="text-muted-foreground">
                            Configura una nueva importación de datos al sistema
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Tabs defaultValue="basicos" className="space-y-6">
                        <TabsList className="grid w-full grid-cols-4">
                            <TabsTrigger value="basicos">Datos Básicos</TabsTrigger>
                            <TabsTrigger value="archivo" disabled={!data.nombre || !data.tipo}>Archivo</TabsTrigger>
                            <TabsTrigger value="mapeo" disabled={!selectedFile}>Mapeo</TabsTrigger>
                            <TabsTrigger value="configuracion" disabled={!selectedFile}>Configuración</TabsTrigger>
                        </TabsList>

                        {/* Tab 1: Datos Básicos */}
                        <TabsContent value="basicos">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Info className="h-5 w-5" />
                                        Información Básica
                                    </CardTitle>
                                    <CardDescription>
                                        Define la información general de la importación
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div className="space-y-2">
                                            <Label htmlFor="nombre">Nombre de la Importación *</Label>
                                            <Input
                                                id="nombre"
                                                value={data.nombre}
                                                onChange={(e) => setData('nombre', e.target.value)}
                                                placeholder="Ej: Importación expedientes 2024"
                                                className={errors.nombre ? 'border-red-500' : ''}
                                            />
                                            {errors.nombre && (
                                                <p className="text-sm text-red-600">{errors.nombre}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="tipo">Tipo de Datos *</Label>
                                            <Select value={data.tipo} onValueChange={(value) => setData('tipo', value)}>
                                                <SelectTrigger className={errors.tipo ? 'border-red-500' : ''}>
                                                    <SelectValue placeholder="Selecciona el tipo de datos" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {Object.entries(tipos).map(([key, label]) => (
                                                        <SelectItem key={key} value={key}>
                                                            <div className="flex items-center gap-2">
                                                                <span>{tiposIcons[key as keyof typeof tiposIcons]}</span>
                                                                <span>{label}</span>
                                                            </div>
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors.tipo && (
                                                <p className="text-sm text-red-600">{errors.tipo}</p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="descripcion">Descripción</Label>
                                        <Textarea
                                            id="descripcion"
                                            value={data.descripcion}
                                            onChange={(e) => setData('descripcion', e.target.value)}
                                            placeholder="Describe el propósito y contenido de esta importación..."
                                            rows={3}
                                        />
                                    </div>

                                    {data.tipo && (
                                        <Alert>
                                            <Info className="h-4 w-4" />
                                            <AlertDescription>
                                                <strong>Tipo seleccionado:</strong> {tipos[data.tipo]}
                                                <br />
                                                Este tipo de importación procesará datos relacionados con {tipos[data.tipo].toLowerCase()}.
                                                {camposDisponibles.length > 0 && (
                                                    <>
                                                        <br />
                                                        <strong>Campos principales:</strong> {camposDisponibles.filter(c => c.requerido).map(c => c.nombre).join(', ')}
                                                    </>
                                                )}
                                            </AlertDescription>
                                        </Alert>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Tab 2: Archivo */}
                        <TabsContent value="archivo">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Upload className="h-5 w-5" />
                                        Selección de Archivo
                                    </CardTitle>
                                    <CardDescription>
                                        Sube el archivo que contiene los datos a importar
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    {/* Información de formatos */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        {Object.entries(formatosPermitidos).map(([formato, label]) => {
                                            const info = formatosInfo[formato as keyof typeof formatosInfo];
                                            return (
                                                <Card key={formato} className="p-4">
                                                    <div className="text-center space-y-2">
                                                        <div className="text-2xl">{info?.icon}</div>
                                                        <div className="font-medium">{label}</div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {info?.descripcion}
                                                        </div>
                                                        <div className="text-xs">
                                                            <Badge variant="outline">
                                                                Max: {info?.tamanoMax}
                                                            </Badge>
                                                        </div>
                                                    </div>
                                                </Card>
                                            );
                                        })}
                                    </div>

                                    {/* Upload area */}
                                    <div className="border-2 border-dashed border-gray-300 rounded-lg p-8">
                                        <div className="text-center space-y-4">
                                            <Upload className="h-12 w-12 mx-auto text-gray-400" />
                                            <div>
                                                <Label htmlFor="archivo" className="cursor-pointer">
                                                    <span className="text-lg font-medium text-blue-600 hover:text-blue-500">
                                                        Seleccionar archivo
                                                    </span>
                                                    <Input
                                                        id="archivo"
                                                        type="file"
                                                        className="hidden"
                                                        onChange={handleFileChange}
                                                        accept=".csv,.xlsx,.xls,.json,.xml"
                                                    />
                                                </Label>
                                                <p className="text-sm text-muted-foreground">
                                                    o arrastra y suelta tu archivo aquí
                                                </p>
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                Formatos soportados: CSV, Excel, JSON, XML (máximo 50MB)
                                            </p>
                                        </div>
                                    </div>

                                    {selectedFile && (
                                        <Alert>
                                            <CheckCircle className="h-4 w-4" />
                                            <AlertDescription>
                                                <strong>Archivo seleccionado:</strong> {selectedFile.name}
                                                <br />
                                                <strong>Tamaño:</strong> {(selectedFile.size / 1024 / 1024).toFixed(2)} MB
                                                <br />
                                                <strong>Tipo:</strong> {selectedFile.type}
                                            </AlertDescription>
                                        </Alert>
                                    )}

                                    {errors.archivo && (
                                        <Alert variant="destructive">
                                            <AlertTriangle className="h-4 w-4" />
                                            <AlertDescription>{errors.archivo}</AlertDescription>
                                        </Alert>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Tab 3: Mapeo */}
                        <TabsContent value="mapeo">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Settings className="h-5 w-5" />
                                        Mapeo de Campos
                                    </CardTitle>
                                    <CardDescription>
                                        Define cómo se corresponden los campos del archivo con los campos del sistema
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    {camposDisponibles.length > 0 ? (
                                        <div className="space-y-4">
                                            <Alert>
                                                <Info className="h-4 w-4" />
                                                <AlertDescription>
                                                    Configura cómo los campos de tu archivo se mapean a los campos del sistema.
                                                    Los campos marcados con * son obligatorios.
                                                </AlertDescription>
                                            </Alert>

                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                {camposDisponibles.map((campo) => (
                                                    <div key={campo.campo} className="space-y-2">
                                                        <Label htmlFor={`mapeo_${campo.campo}`}>
                                                            {campo.nombre}
                                                            {campo.requerido && <span className="text-red-500 ml-1">*</span>}
                                                        </Label>
                                                        <Input
                                                            id={`mapeo_${campo.campo}`}
                                                            placeholder={`Nombre de columna para ${campo.nombre.toLowerCase()}`}
                                                            value={configuracion.mapeo[campo.campo] || ''}
                                                            onChange={(e) => actualizarMapeo(campo.campo, e.target.value)}
                                                        />
                                                        <p className="text-xs text-muted-foreground">
                                                            Campo del sistema: <code className="bg-gray-100 px-1 rounded">{campo.campo}</code>
                                                        </p>
                                                    </div>
                                                ))}
                                            </div>

                                            <Alert>
                                                <HelpCircle className="h-4 w-4" />
                                                <AlertDescription>
                                                    <strong>Ejemplo:</strong> Si tu archivo CSV tiene una columna llamada "Código Expediente", 
                                                    debes escribir "Código Expediente" en el campo de mapeo correspondiente a "Código".
                                                </AlertDescription>
                                            </Alert>
                                        </div>
                                    ) : (
                                        <Alert>
                                            <AlertTriangle className="h-4 w-4" />
                                            <AlertDescription>
                                                Primero debes seleccionar un tipo de datos en la pestaña "Datos Básicos".
                                            </AlertDescription>
                                        </Alert>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Tab 4: Configuración */}
                        <TabsContent value="configuracion">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Settings className="h-5 w-5" />
                                        Configuración Avanzada
                                    </CardTitle>
                                    <CardDescription>
                                        Opciones adicionales para el procesamiento de la importación
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div className="space-y-4">
                                        <div className="flex items-center space-x-2">
                                            <Checkbox
                                                id="actualizar_existentes"
                                                checked={configuracion.actualizar_existentes}
                                                onCheckedChange={(checked) => 
                                                    setConfiguracion(prev => ({
                                                        ...prev,
                                                        actualizar_existentes: checked as boolean
                                                    }))
                                                }
                                            />
                                            <Label htmlFor="actualizar_existentes" className="text-sm font-medium">
                                                Actualizar registros existentes
                                            </Label>
                                        </div>
                                        <p className="text-sm text-muted-foreground ml-6">
                                            Si está marcado, los registros que ya existan en el sistema serán actualizados 
                                            con los nuevos datos. Si no, se omitirán los duplicados.
                                        </p>
                                    </div>

                                    <Alert>
                                        <Info className="h-4 w-4" />
                                        <AlertDescription>
                                            <strong>Recomendación:</strong> Ejecuta primero una importación de prueba con pocos registros 
                                            para verificar que el mapeo y la configuración sean correctos.
                                        </AlertDescription>
                                    </Alert>

                                    <div className="border-t pt-6">
                                        <h4 className="font-medium mb-4">Resumen de Configuración</h4>
                                        <div className="space-y-2 text-sm">
                                            <div className="flex justify-between">
                                                <span>Tipo de datos:</span>
                                                <Badge variant="outline">{data.tipo ? tipos[data.tipo] : 'No seleccionado'}</Badge>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Archivo:</span>
                                                <span>{selectedFile?.name || 'No seleccionado'}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Campos mapeados:</span>
                                                <span>{Object.keys(configuracion.mapeo).length}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Actualizar existentes:</span>
                                                <span>{configuracion.actualizar_existentes ? 'Sí' : 'No'}</span>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>

                    {/* Botones de acción */}
                    <div className="flex items-center justify-between pt-6 border-t">
                        <Button variant="outline" asChild>
                            <Link href="/admin/importaciones">
                                Cancelar
                            </Link>
                        </Button>

                        <div className="flex items-center gap-3">
                            <Button
                                type="submit"
                                disabled={processing || !data.nombre || !data.tipo || !selectedFile}
                                className="min-w-[150px]"
                            >
                                {processing ? (
                                    <>
                                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                        Creando...
                                    </>
                                ) : (
                                    <>
                                        <Plus className="h-4 w-4 mr-2" />
                                        Crear Importación
                                    </>
                                )}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
