import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { AlertCircle, FileText, GitBranch, Users, Calendar, Clock, X, Plus } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface Documento {
    id: number;
    nombre: string;
    codigo: string;
    expediente?: {
        numero: string;
        titulo: string;
    };
}

interface Usuario {
    id: number;
    name: string;
    email: string;
    cargo: string;
}

interface Props {
    documento?: Documento;
    usuarios_disponibles?: Usuario[];
}

interface FormData {
    documento_id: string;
    aprobadores: number[];
    descripcion: string;
    prioridad: number;
    requiere_unanime: boolean;
    dias_vencimiento: number;
}

export default function WorkflowCreate({ documento, usuarios_disponibles = [] }: Props) {
    const usuariosDisponibles = usuarios_disponibles; // Alias para usar en el código
    const [aprobadoresSeleccionados, setAprobadoresSeleccionados] = useState<Usuario[]>([]);
    const [mostrarBuscarDocumento, setMostrarBuscarDocumento] = useState(!documento);
    
    const { data, setData, post, processing, errors } = useForm<FormData>({
        documento_id: documento?.id.toString() || '',
        aprobadores: [],
        descripcion: '',
        prioridad: 2,
        requiere_unanime: false,
        dias_vencimiento: 7
    });

    const prioridades = [
        { valor: 4, etiqueta: 'Crítica', color: 'text-red-600' },
        { valor: 3, etiqueta: 'Alta', color: 'text-orange-600' },
        { valor: 2, etiqueta: 'Media', color: 'text-blue-600' },
        { valor: 1, etiqueta: 'Baja', color: 'text-gray-600' }
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (aprobadoresSeleccionados.length === 0) {
            return;
        }

        post('/admin/workflow', {
            ...data,
            aprobadores: aprobadoresSeleccionados.map(u => u.id)
        });
    };

    const agregarAprobador = (usuario: Usuario) => {
        if (!aprobadoresSeleccionados.find(u => u.id === usuario.id)) {
            const nuevosAprobadores = [...aprobadoresSeleccionados, usuario];
            setAprobadoresSeleccionados(nuevosAprobadores);
            setData('aprobadores', nuevosAprobadores.map(u => u.id));
        }
    };

    const removerAprobador = (usuarioId: number) => {
        const nuevosAprobadores = aprobadoresSeleccionados.filter(u => u.id !== usuarioId);
        setAprobadoresSeleccionados(nuevosAprobadores);
        setData('aprobadores', nuevosAprobadores.map(u => u.id));
    };

    const prioridadSeleccionada = prioridades.find(p => p.valor === data.prioridad);

    return (
        <AppLayout>
            <Head title="Nuevo Workflow de Aprobación" />
            
            <div className="container mx-auto py-6">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <GitBranch className="w-8 h-8 text-blue-600" />
                            Nuevo Workflow de Aprobación
                        </h1>
                        <p className="text-gray-600 mt-1">
                            Inicia un proceso de aprobación para un documento
                        </p>
                    </div>
                    <Link href="/admin/workflow">
                        <Button variant="outline">
                            Volver a Workflows
                        </Button>
                    </Link>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Formulario Principal */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Documento */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <FileText className="w-5 h-5" />
                                        Documento a Aprobar
                                    </CardTitle>
                                    <CardDescription>
                                        Selecciona el documento que necesita aprobación
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {documento ? (
                                        <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                            <div className="flex justify-between items-start">
                                                <div>
                                                    <h4 className="font-medium text-blue-900">{documento.nombre}</h4>
                                                    <p className="text-sm text-blue-700">{documento.codigo}</p>
                                                    {documento.expediente && (
                                                        <p className="text-sm text-blue-600 mt-1">
                                                            Expediente: {documento.expediente.numero} - {documento.expediente.titulo}
                                                        </p>
                                                    )}
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => setMostrarBuscarDocumento(true)}
                                                >
                                                    Cambiar
                                                </Button>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            <Label htmlFor="documento_id">Documento *</Label>
                                            <Input
                                                id="documento_id"
                                                placeholder="Buscar documento por nombre o código..."
                                                className={errors.documento_id ? 'border-red-500' : ''}
                                            />
                                            {errors.documento_id && (
                                                <p className="text-sm text-red-600">{errors.documento_id}</p>
                                            )}
                                            <p className="text-sm text-gray-500">
                                                Nota: La búsqueda de documentos se implementará próximamente
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Aprobadores */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Users className="w-5 h-5" />
                                        Aprobadores
                                    </CardTitle>
                                    <CardDescription>
                                        Selecciona los usuarios que deben aprobar este documento
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {/* Lista de aprobadores seleccionados */}
                                    {aprobadoresSeleccionados.length > 0 && (
                                        <div>
                                            <Label className="text-sm font-medium">Aprobadores Seleccionados ({aprobadoresSeleccionados.length})</Label>
                                            <div className="space-y-2 mt-2">
                                                {aprobadoresSeleccionados.map((usuario, index) => (
                                                    <div key={usuario.id} className="flex items-center justify-between p-3 bg-gray-50 border rounded-lg">
                                                        <div className="flex items-center gap-3">
                                                            <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-sm font-medium text-blue-600">
                                                                {index + 1}
                                                            </div>
                                                            <div>
                                                                <p className="font-medium">{usuario.name}</p>
                                                                <p className="text-sm text-gray-600">{usuario.cargo}</p>
                                                            </div>
                                                        </div>
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => removerAprobador(usuario.id)}
                                                        >
                                                            <X className="w-4 h-4" />
                                                        </Button>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* Selector de aprobadores */}
                                    <div>
                                        <Label htmlFor="nuevo_aprobador">Agregar Aprobador</Label>
                                        <Select onValueChange={(value) => {
                                            const usuario = usuariosDisponibles.find(u => u.id.toString() === value);
                                            if (usuario) agregarAprobador(usuario);
                                        }}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar usuario..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {usuariosDisponibles
                                                    .filter(u => !aprobadoresSeleccionados.find(a => a.id === u.id))
                                                    .map((usuario) => (
                                                    <SelectItem key={usuario.id} value={usuario.id.toString()}>
                                                        <div>
                                                            <p className="font-medium">{usuario.name}</p>
                                                            <p className="text-sm text-gray-500">{usuario.cargo} - {usuario.email}</p>
                                                        </div>
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {aprobadoresSeleccionados.length === 0 && (
                                        <Alert>
                                            <AlertCircle className="h-4 w-4" />
                                            <AlertDescription>
                                                Debes seleccionar al menos un aprobador para continuar.
                                            </AlertDescription>
                                        </Alert>
                                    )}

                                    {/* Opción de aprobación unánime */}
                                    {aprobadoresSeleccionados.length > 1 && (
                                        <div className="flex items-center space-x-2">
                                            <Checkbox
                                                id="requiere_unanime"
                                                checked={data.requiere_unanime}
                                                onCheckedChange={(checked) => setData('requiere_unanime', !!checked)}
                                            />
                                            <Label htmlFor="requiere_unanime" className="text-sm">
                                                Requiere aprobación unánime (todos deben aprobar)
                                            </Label>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Descripción */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Descripción de la Solicitud</CardTitle>
                                    <CardDescription>
                                        Explica por qué este documento necesita aprobación
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Textarea
                                        placeholder="Describe el motivo de la aprobación, contexto adicional, o instrucciones específicas..."
                                        value={data.descripcion}
                                        onChange={(e) => setData('descripcion', e.target.value)}
                                        rows={4}
                                        className={errors.descripcion ? 'border-red-500' : ''}
                                    />
                                    {errors.descripcion && (
                                        <p className="text-sm text-red-600 mt-1">{errors.descripcion}</p>
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        {/* Panel Lateral */}
                        <div className="space-y-6">
                            {/* Configuración */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Clock className="w-5 h-5" />
                                        Configuración
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {/* Prioridad */}
                                    <div>
                                        <Label>Prioridad *</Label>
                                        <Select 
                                            value={data.prioridad.toString()} 
                                            onValueChange={(value) => setData('prioridad', parseInt(value))}
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {prioridades.map((prioridad) => (
                                                    <SelectItem key={prioridad.valor} value={prioridad.valor.toString()}>
                                                        <span className={prioridad.color}>{prioridad.etiqueta}</span>
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.prioridad && (
                                            <p className="text-sm text-red-600 mt-1">{errors.prioridad}</p>
                                        )}
                                    </div>

                                    {/* Días de vencimiento */}
                                    <div>
                                        <Label htmlFor="dias_vencimiento">Días para vencer *</Label>
                                        <Input
                                            id="dias_vencimiento"
                                            type="number"
                                            min="1"
                                            max="30"
                                            value={data.dias_vencimiento}
                                            onChange={(e) => setData('dias_vencimiento', parseInt(e.target.value) || 7)}
                                            className={errors.dias_vencimiento ? 'border-red-500' : ''}
                                        />
                                        {errors.dias_vencimiento && (
                                            <p className="text-sm text-red-600 mt-1">{errors.dias_vencimiento}</p>
                                        )}
                                        <p className="text-xs text-gray-500 mt-1">
                                            Fecha de vencimiento: {new Date(Date.now() + (data.dias_vencimiento * 24 * 60 * 60 * 1000)).toLocaleDateString()}
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Resumen */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Resumen</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-sm text-gray-600">Documento:</span>
                                        <span className="text-sm font-medium">{documento ? '✓' : '✗'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-sm text-gray-600">Aprobadores:</span>
                                        <span className="text-sm font-medium">{aprobadoresSeleccionados.length}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-sm text-gray-600">Prioridad:</span>
                                        <Badge variant="outline" className={prioridadSeleccionada?.color}>
                                            {prioridadSeleccionada?.etiqueta}
                                        </Badge>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-sm text-gray-600">Vencimiento:</span>
                                        <span className="text-sm font-medium">{data.dias_vencimiento} días</span>
                                    </div>
                                    {data.requiere_unanime && (
                                        <div className="flex justify-between">
                                            <span className="text-sm text-gray-600">Tipo:</span>
                                            <Badge variant="outline" className="text-orange-600">Unánime</Badge>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Acciones */}
                            <div className="space-y-3">
                                <Button 
                                    type="submit" 
                                    className="w-full" 
                                    disabled={processing || !documento || aprobadoresSeleccionados.length === 0}
                                >
                                    {processing ? 'Creando...' : 'Iniciar Workflow'}
                                </Button>
                                <Link href="/admin/workflow">
                                    <Button variant="outline" className="w-full">
                                        Cancelar
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
