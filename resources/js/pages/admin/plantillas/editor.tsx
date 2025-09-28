import React, { useState, useEffect } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, Eye, Code, Download, Upload, FileText, Settings, Plus, Trash2, Edit, Check, X } from 'lucide-react';
import AppLayout from '../../../layouts/app/app-sidebar-layout';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Input } from '../../../components/ui/input';
import { Label } from '../../../components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { Textarea } from '../../../components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Badge } from '../../../components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '../../../components/ui/dialog';
import { Switch } from '../../../components/ui/switch';

interface PlantillaDocumental {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string;
    categoria: string;
    contenido_html: string;
    contenido_json: any;
    campos_variables: CampoVariable[];
    metadatos_predefinidos: Record<string, any>;
    configuracion_formato: Record<string, any>;
    estado: string;
    version: number;
    tags: string[];
}

interface CampoVariable {
    nombre: string;
    marcador: string;
    tipo: string;
    requerido: boolean;
    valor_default: string;
}

interface SerieDocumental {
    id: number;
    codigo: string;
    nombre: string;
}

interface EstadisticasUso {
    documentos_generados: number;
    uso_ultimo_mes: number;
    usuarios_distintos: number;
    fecha_ultimo_uso?: string;
    promedio_uso_mensual: number;
}

interface Props {
    plantilla?: PlantillaDocumental;
    categorias: Array<{value: string, label: string}>;
    series: SerieDocumental[];
    tipos_campo: Array<{value: string, label: string}>;
    estadisticas_uso?: EstadisticasUso;
}

export default function PlantillaEditor({ plantilla, categorias, series, tipos_campo, estadisticas_uso }: Props) {
    const [modoEditor, setModoEditor] = useState<'visual' | 'codigo'>('visual');
    const [vistaPrevia, setVistaPrevia] = useState('');
    const [validacion, setValidacion] = useState<any>(null);
    const [editandoCampo, setEditandoCampo] = useState<number | null>(null);
    const [nuevoCampo, setNuevoCampo] = useState<CampoVariable>({
        nombre: '',
        marcador: '',
        tipo: 'text',
        requerido: true,
        valor_default: ''
    });

    const { data, setData, post, put, processing, errors, reset } = useForm({
        nombre: plantilla?.nombre || '',
        descripcion: plantilla?.descripcion || '',
        categoria: plantilla?.categoria || '',
        serie_documental_id: '',
        contenido_html: plantilla?.contenido_html || '',
        campos_variables: plantilla?.campos_variables || [],
        metadatos_predefinidos: plantilla?.metadatos_predefinidos || {},
        configuracion_formato: plantilla?.configuracion_formato || {},
        tags: plantilla?.tags || [],
        es_publica: false
    });

    const handleGuardar = () => {
        if (plantilla) {
            put(route('admin.plantillas.update', plantilla.id));
        } else {
            post(route('admin.plantillas.store'));
        }
    };

    const handleValidarEstructura = async () => {
        try {
            const response = await fetch(route('admin.plantillas.validar-estructura'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ contenido_html: data.contenido_html })
            });
            
            const result = await response.json();
            setValidacion(result);
        } catch (error) {
            console.error('Error al validar estructura:', error);
        }
    };

    const handleGenerarVistaPrevia = () => {
        let contenido = data.contenido_html;
        
        // Reemplazar campos variables con valores de ejemplo
        data.campos_variables.forEach((campo) => {
            const valorEjemplo = obtenerValorEjemplo(campo);
            contenido = contenido.replace(new RegExp(escapeRegExp(campo.marcador), 'g'), valorEjemplo);
        });
        
        setVistaPrevia(contenido);
    };

    const obtenerValorEjemplo = (campo: CampoVariable): string => {
        switch (campo.tipo) {
            case 'date':
                return new Date().toLocaleDateString();
            case 'number':
                return '123';
            case 'email':
                return 'ejemplo@correo.com';
            case 'tel':
                return '+57 300 123 4567';
            default:
                return campo.valor_default || `[${campo.nombre}]`;
        }
    };

    const escapeRegExp = (string: string): string => {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    };

    const agregarCampoVariable = () => {
        if (!nuevoCampo.nombre) return;
        
        const marcador = nuevoCampo.marcador || `{${nuevoCampo.nombre}}`;
        const campo = { ...nuevoCampo, marcador };
        
        setData('campos_variables', [...data.campos_variables, campo]);
        setNuevoCampo({
            nombre: '',
            marcador: '',
            tipo: 'text',
            requerido: true,
            valor_default: ''
        });
    };

    const eliminarCampoVariable = (index: number) => {
        const nuevoCampos = data.campos_variables.filter((_, i) => i !== index);
        setData('campos_variables', nuevoCampos);
    };

    const actualizarCampoVariable = (index: number, campo: CampoVariable) => {
        const nuevoCampos = [...data.campos_variables];
        nuevoCampos[index] = campo;
        setData('campos_variables', nuevoCampos);
        setEditandoCampo(null);
    };

    const insertarMarcador = (marcador: string) => {
        const textarea = document.getElementById('contenido_html') as HTMLTextAreaElement;
        if (textarea) {
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const contenido = data.contenido_html;
            const nuevoContenido = contenido.substring(0, start) + marcador + contenido.substring(end);
            setData('contenido_html', nuevoContenido);
            
            // Mantener el cursor después del marcador insertado
            setTimeout(() => {
                textarea.focus();
                textarea.setSelectionRange(start + marcador.length, start + marcador.length);
            }, 0);
        }
    };

    useEffect(() => {
        if (data.contenido_html) {
            handleValidarEstructura();
        }
    }, [data.contenido_html]);

    return (
        <AppLayout>
            <Head title={`Editor - ${plantilla?.nombre || 'Nueva Plantilla'}`} />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href={route('admin.plantillas.index')}>
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                {plantilla ? `Editar: ${plantilla.nombre}` : 'Nueva Plantilla'}
                            </h1>
                            <p className="text-muted-foreground">
                                Editor avanzado de plantillas documentales
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-3">
                        <Button variant="outline" onClick={handleValidarEstructura}>
                            <Check className="h-4 w-4 mr-2" />
                            Validar
                        </Button>
                        <Button variant="outline" onClick={handleGenerarVistaPrevia}>
                            <Eye className="h-4 w-4 mr-2" />
                            Vista Previa
                        </Button>
                        <Button onClick={handleGuardar} disabled={processing}>
                            <Save className="h-4 w-4 mr-2" />
                            {processing ? 'Guardando...' : 'Guardar'}
                        </Button>
                    </div>
                </div>

                {/* Estadísticas de uso (si existe) */}
                {estadisticas_uso && (
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <p className="text-2xl font-bold">{estadisticas_uso.documentos_generados}</p>
                                    <p className="text-sm text-gray-600">Documentos Generados</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <p className="text-2xl font-bold">{estadisticas_uso.uso_ultimo_mes}</p>
                                    <p className="text-sm text-gray-600">Uso Último Mes</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <p className="text-2xl font-bold">{estadisticas_uso.usuarios_distintos}</p>
                                    <p className="text-sm text-gray-600">Usuarios Únicos</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <p className="text-2xl font-bold">{estadisticas_uso.promedio_uso_mensual}</p>
                                    <p className="text-sm text-gray-600">Promedio Mensual</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Panel Principal - Editor */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Información Básica */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Información Básica</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="nombre">Nombre de la Plantilla</Label>
                                        <Input
                                            id="nombre"
                                            value={data.nombre}
                                            onChange={(e) => setData('nombre', e.target.value)}
                                            placeholder="Ej: Memorando Interno"
                                        />
                                        {errors.nombre && <p className="text-sm text-red-600">{errors.nombre}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="categoria">Categoría</Label>
                                        <Select value={data.categoria} onValueChange={(value) => setData('categoria', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar categoría" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {categorias.map((cat) => (
                                                    <SelectItem key={cat.value} value={cat.value}>
                                                        {cat.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="descripcion">Descripción</Label>
                                    <Textarea
                                        id="descripcion"
                                        value={data.descripcion}
                                        onChange={(e) => setData('descripcion', e.target.value)}
                                        placeholder="Describe el propósito y uso de esta plantilla..."
                                        rows={3}
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Editor de Contenido */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <CardTitle>Contenido de la Plantilla</CardTitle>
                                    <div className="flex items-center space-x-2">
                                        <Button
                                            variant={modoEditor === 'visual' ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => setModoEditor('visual')}
                                        >
                                            <Eye className="h-4 w-4 mr-2" />
                                            Visual
                                        </Button>
                                        <Button
                                            variant={modoEditor === 'codigo' ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => setModoEditor('codigo')}
                                        >
                                            <Code className="h-4 w-4 mr-2" />
                                            Código
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {modoEditor === 'codigo' ? (
                                    <div className="space-y-4">
                                        <Textarea
                                            id="contenido_html"
                                            value={data.contenido_html}
                                            onChange={(e) => setData('contenido_html', e.target.value)}
                                            placeholder="Escribe el contenido HTML de tu plantilla aquí..."
                                            rows={20}
                                            className="font-mono text-sm"
                                        />
                                        {errors.contenido_html && (
                                            <p className="text-sm text-red-600">{errors.contenido_html}</p>
                                        )}
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        <Alert>
                                            <FileText className="h-4 w-4" />
                                            <AlertDescription>
                                                El editor visual estará disponible en la próxima actualización. 
                                                Por ahora, usa el modo código para editar el contenido HTML.
                                            </AlertDescription>
                                        </Alert>
                                        <Button onClick={() => setModoEditor('codigo')} className="w-full">
                                            Cambiar a Modo Código
                                        </Button>
                                    </div>
                                )}

                                {/* Validación */}
                                {validacion && (
                                    <div className="mt-4 space-y-2">
                                        {!validacion.valida && validacion.errores.length > 0 && (
                                            <Alert variant="destructive">
                                                <X className="h-4 w-4" />
                                                <AlertDescription>
                                                    <strong>Errores encontrados:</strong>
                                                    <ul className="list-disc list-inside mt-1">
                                                        {validacion.errores.map((error: string, index: number) => (
                                                            <li key={index}>{error}</li>
                                                        ))}
                                                    </ul>
                                                </AlertDescription>
                                            </Alert>
                                        )}
                                        {validacion.advertencias.length > 0 && (
                                            <Alert>
                                                <AlertDescription>
                                                    <strong>Advertencias:</strong>
                                                    <ul className="list-disc list-inside mt-1">
                                                        {validacion.advertencias.map((adv: string, index: number) => (
                                                            <li key={index}>{adv}</li>
                                                        ))}
                                                    </ul>
                                                </AlertDescription>
                                            </Alert>
                                        )}
                                        {validacion.estadisticas && (
                                            <div className="text-sm text-gray-600">
                                                Tamaño: {validacion.estadisticas.tamaño_bytes} bytes | 
                                                Campos variables: {validacion.estadisticas.campos_variables} | 
                                                Elementos HTML: {validacion.estadisticas.elementos_html}
                                            </div>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Vista Previa */}
                        {vistaPrevia && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Vista Previa</CardTitle>
                                    <CardDescription>
                                        Visualización de la plantilla con valores de ejemplo
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div 
                                        className="border p-4 bg-white min-h-[300px]"
                                        dangerouslySetInnerHTML={{ __html: vistaPrevia }}
                                    />
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Panel Lateral - Campos Variables */}
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Campos Variables</CardTitle>
                                <CardDescription>
                                    Gestiona los campos que pueden ser reemplazados dinámicamente
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {/* Lista de campos existentes */}
                                {data.campos_variables.length > 0 && (
                                    <div className="space-y-2">
                                        {data.campos_variables.map((campo, index) => (
                                            <div key={index} className="flex items-center justify-between p-2 border rounded">
                                                <div className="flex-1">
                                                    <div className="font-medium">{campo.nombre}</div>
                                                    <div className="text-sm text-gray-500">
                                                        {campo.marcador} • {campo.tipo}
                                                        {campo.requerido && <Badge variant="outline" className="ml-1">Requerido</Badge>}
                                                    </div>
                                                </div>
                                                <div className="flex space-x-1">
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => insertarMarcador(campo.marcador)}
                                                    >
                                                        <Plus className="h-3 w-3" />
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => setEditandoCampo(index)}
                                                    >
                                                        <Edit className="h-3 w-3" />
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => eliminarCampoVariable(index)}
                                                    >
                                                        <Trash2 className="h-3 w-3" />
                                                    </Button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                {/* Formulario para nuevo campo */}
                                <div className="space-y-3 p-3 border rounded bg-gray-50">
                                    <h4 className="font-medium">Agregar Campo</h4>
                                    <div className="space-y-2">
                                        <Input
                                            placeholder="Nombre del campo"
                                            value={nuevoCampo.nombre}
                                            onChange={(e) => setNuevoCampo({...nuevoCampo, nombre: e.target.value})}
                                        />
                                        <Input
                                            placeholder="Marcador (ej: {nombre})"
                                            value={nuevoCampo.marcador}
                                            onChange={(e) => setNuevoCampo({...nuevoCampo, marcador: e.target.value})}
                                        />
                                        <Select 
                                            value={nuevoCampo.tipo} 
                                            onValueChange={(value) => setNuevoCampo({...nuevoCampo, tipo: value})}
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {tipos_campo.map((tipo) => (
                                                    <SelectItem key={tipo.value} value={tipo.value}>
                                                        {tipo.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <Input
                                            placeholder="Valor por defecto"
                                            value={nuevoCampo.valor_default}
                                            onChange={(e) => setNuevoCampo({...nuevoCampo, valor_default: e.target.value})}
                                        />
                                        <div className="flex items-center space-x-2">
                                            <Switch
                                                checked={nuevoCampo.requerido}
                                                onCheckedChange={(checked) => setNuevoCampo({...nuevoCampo, requerido: checked})}
                                            />
                                            <Label>Campo requerido</Label>
                                        </div>
                                        <Button onClick={agregarCampoVariable} className="w-full" size="sm">
                                            <Plus className="h-4 w-4 mr-2" />
                                            Agregar Campo
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Acciones Rápidas */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Acciones</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {plantilla && (
                                    <>
                                        <Button variant="outline" className="w-full">
                                            <Download className="h-4 w-4 mr-2" />
                                            Exportar JSON
                                        </Button>
                                        <Button variant="outline" className="w-full">
                                            <Download className="h-4 w-4 mr-2" />
                                            Exportar HTML
                                        </Button>
                                        <Button variant="outline" className="w-full">
                                            <FileText className="h-4 w-4 mr-2" />
                                            Nueva Versión
                                        </Button>
                                    </>
                                )}
                                <Button variant="outline" className="w-full">
                                    <Upload className="h-4 w-4 mr-2" />
                                    Importar Plantilla
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
