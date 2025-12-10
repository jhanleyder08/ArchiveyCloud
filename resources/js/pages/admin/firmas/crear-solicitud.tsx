import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { 
    Users, 
    FileText,
    Plus,
    Trash2,
    Calendar,
    Clock,
    AlertCircle,
    User,
    ArrowLeft,
    Save,
    Search,
    UserPlus
} from 'lucide-react';

interface Usuario {
    id: number;
    name: string;
    email: string;
    cargo?: string;
    departamento?: string;
}

interface Documento {
    id: number;
    titulo: string;
    tipo_documento?: string;
    expediente?: {
        id: number;
        codigo: string;
        titulo?: string;
    };
}

interface Firmante {
    usuario_id: number;
    orden: number;
    es_obligatorio: boolean;
    rol: 'aprobador' | 'revisor' | 'testigo' | 'autoridad' | 'validador';
    usuario?: Usuario;
}

interface Props {
    documentos: Documento[];
    usuarios: Usuario[];
}

export default function CrearSolicitudFirma({ documentos, usuarios }: Props) {
    const [firmantes, setFirmantes] = useState<Firmante[]>([]);
    const [mostrarSelectorUsuarios, setMostrarSelectorUsuarios] = useState(false);
    const [busquedaUsuario, setBusquedaUsuario] = useState('');
    const [documentoSeleccionado, setDocumentoSeleccionado] = useState<Documento | null>(null);
    
    const { data, setData, post, processing, errors, reset } = useForm({
        documento_id: '',
        titulo: '',
        descripcion: '',
        tipo_flujo: 'secuencial',
        prioridad: 'normal',
        fecha_limite: '',
        requiere_todos: false,
        configuracion: {
            permitir_comentarios: true,
            notificar_cambios: true,
            recordatorio_dias: 3
        },
        firmantes: [] as Array<{
            usuario_id: number;
            orden: number;
            es_obligatorio: boolean;
            rol: 'aprobador' | 'revisor' | 'testigo' | 'autoridad' | 'validador';
        }>
    });

    const usuariosFiltrados = usuarios.filter(usuario =>
        usuario.name.toLowerCase().includes(busquedaUsuario.toLowerCase()) ||
        usuario.email.toLowerCase().includes(busquedaUsuario.toLowerCase()) ||
        (usuario.cargo && usuario.cargo.toLowerCase().includes(busquedaUsuario.toLowerCase()))
    );

    const agregarFirmante = (usuario: Usuario) => {
        if (firmantes.some(f => f.usuario_id === usuario.id)) {
            return; // Ya está agregado
        }

        const nuevoFirmante: Firmante = {
            usuario_id: usuario.id,
            orden: firmantes.length + 1,
            es_obligatorio: true,
            rol: 'aprobador',
            usuario: usuario
        };

        const nuevosFirmantes = [...firmantes, nuevoFirmante];
        setFirmantes(nuevosFirmantes);
        
        // Actualizar form data
        setData('firmantes', nuevosFirmantes.map(f => ({
            usuario_id: f.usuario_id,
            orden: f.orden,
            es_obligatorio: f.es_obligatorio,
            rol: f.rol
        })));

        setMostrarSelectorUsuarios(false);
        setBusquedaUsuario('');
    };

    const removerFirmante = (usuarioId: number) => {
        const nuevosFirmantes = firmantes
            .filter(f => f.usuario_id !== usuarioId)
            .map((f, index) => ({ ...f, orden: index + 1 }));
        
        setFirmantes(nuevosFirmantes);
        setData('firmantes', nuevosFirmantes.map(f => ({
            usuario_id: f.usuario_id,
            orden: f.orden,
            es_obligatorio: f.es_obligatorio,
            rol: f.rol
        })));
    };

    const cambiarOrdenFirmante = (usuarioId: number, nuevoOrden: number) => {
        if (nuevoOrden < 1 || nuevoOrden > firmantes.length) return;

        const firmantesOrdenados = [...firmantes];
        const firmanteIndex = firmantesOrdenados.findIndex(f => f.usuario_id === usuarioId);
        const firmante = firmantesOrdenados[firmanteIndex];
        
        firmantesOrdenados.splice(firmanteIndex, 1);
        firmantesOrdenados.splice(nuevoOrden - 1, 0, firmante);
        
        // Reordenar todos
        const firmantesReordenados = firmantesOrdenados.map((f, index) => ({
            ...f,
            orden: index + 1
        }));
        
        setFirmantes(firmantesReordenados);
        setData('firmantes', firmantesReordenados.map(f => ({
            usuario_id: f.usuario_id,
            orden: f.orden,
            es_obligatorio: f.es_obligatorio,
            rol: f.rol
        })));
    };

    const toggleEsObligatorio = (usuarioId: number) => {
        const nuevosFirmantes = firmantes.map(f => 
            f.usuario_id === usuarioId ? { ...f, es_obligatorio: !f.es_obligatorio } : f
        );
        
        setFirmantes(nuevosFirmantes);
        setData('firmantes', nuevosFirmantes.map(f => ({
            usuario_id: f.usuario_id,
            orden: f.orden,
            es_obligatorio: f.es_obligatorio,
            rol: f.rol
        })));
    };

    const cambiarRolFirmante = (usuarioId: number, nuevoRol: 'aprobador' | 'revisor' | 'testigo' | 'autoridad' | 'validador') => {
        const nuevosFirmantes = firmantes.map(f => 
            f.usuario_id === usuarioId ? { ...f, rol: nuevoRol } : f
        );
        
        setFirmantes(nuevosFirmantes);
        setData('firmantes', nuevosFirmantes.map(f => ({
            usuario_id: f.usuario_id,
            orden: f.orden,
            es_obligatorio: f.es_obligatorio,
            rol: f.rol
        })));
    };

    const handleDocumentoChange = (documentoId: string) => {
        const documento = documentos.find(d => d.id === parseInt(documentoId));
        setDocumentoSeleccionado(documento || null);
        setData('documento_id', documentoId);
        
        // Auto-generar título si está vacío
        if (!data.titulo && documento) {
            setData('titulo', `Solicitud de firma: ${documento.titulo}`);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (firmantes.length === 0) {
            alert('Debe agregar al menos un firmante');
            return;
        }
        
        post(route('admin.firmas.solicitudes.store'), {
            onSuccess: () => {
                // Redirect will be handled by Inertia
            },
            onError: (errors) => {
                console.error('Errores de validación:', errors);
            }
        });
    };

    const getBadgePrioridad = (prioridad: string) => {
        const colores = {
            'urgente': 'bg-red-500 text-white',
            'alta': 'bg-orange-500 text-white',
            'normal': 'bg-blue-500 text-white',
            'baja': 'bg-gray-500 text-white'
        };
        return colores[prioridad as keyof typeof colores] || 'bg-gray-500 text-white';
    };

    // Fecha mínima debe ser al menos mañana (after:now)
    const fechaMinima = new Date(Date.now() + 86400000).toISOString().split('T')[0];

    return (
        <AppLayout>
            <Head title="Crear Solicitud de Firma" />
            
            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center space-x-3 mb-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => router.get(route('admin.firmas.solicitudes'))}
                            >
                                <ArrowLeft className="w-4 h-4 mr-2" />
                                Volver
                            </Button>
                        </div>
                        <h1 className="text-2xl font-bold text-gray-900">
                            Nueva Solicitud de Firma Digital
                        </h1>
                        <p className="text-gray-600 mt-1">
                            Configura un flujo de firma digital para múltiples usuarios
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Información básica */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <FileText className="w-5 h-5 mr-2" />
                                    Información de la Solicitud
                                </CardTitle>
                                <CardDescription>
                                    Datos básicos de la solicitud de firma
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="documento_id">Documento a Firmar *</Label>
                                        <Select value={data.documento_id} onValueChange={handleDocumentoChange}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona un documento" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {documentos.map((documento) => (
                                                    <SelectItem key={documento.id} value={documento.id.toString()}>
                                                        <div>
                                                            <div className="font-medium">{documento.titulo}</div>
                                                            <div className="text-xs text-gray-500">
                                                                {documento.tipo_documento || 'Sin tipo'}
                                                                {documento.expediente && (
                                                                    <span> • Exp: {documento.expediente.codigo}</span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.documento_id && (
                                            <p className="text-sm text-red-600 mt-1">{errors.documento_id}</p>
                                        )}
                                    </div>

                                    <div>
                                        <Label htmlFor="prioridad">Prioridad</Label>
                                        <Select value={data.prioridad} onValueChange={(valor) => setData('prioridad', valor)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="baja">
                                                    <div className="flex items-center space-x-2">
                                                        <Badge className="bg-gray-500 text-white">Baja</Badge>
                                                        <span>Sin urgencia</span>
                                                    </div>
                                                </SelectItem>
                                                <SelectItem value="normal">
                                                    <div className="flex items-center space-x-2">
                                                        <Badge className="bg-blue-500 text-white">Normal</Badge>
                                                        <span>Prioridad estándar</span>
                                                    </div>
                                                </SelectItem>
                                                <SelectItem value="alta">
                                                    <div className="flex items-center space-x-2">
                                                        <Badge className="bg-orange-500 text-white">Alta</Badge>
                                                        <span>Requiere atención</span>
                                                    </div>
                                                </SelectItem>
                                                <SelectItem value="urgente">
                                                    <div className="flex items-center space-x-2">
                                                        <Badge className="bg-red-500 text-white">Urgente</Badge>
                                                        <span>Crítica</span>
                                                    </div>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div>
                                    <Label htmlFor="titulo">Título de la Solicitud *</Label>
                                    <Input
                                        id="titulo"
                                        value={data.titulo}
                                        onChange={(e) => setData('titulo', e.target.value)}
                                        placeholder="Ej: Solicitud de firma para contrato de servicios"
                                        required
                                    />
                                    {errors.titulo && (
                                        <p className="text-sm text-red-600 mt-1">{errors.titulo}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="descripcion">Descripción</Label>
                                    <Textarea
                                        id="descripcion"
                                        value={data.descripcion}
                                        onChange={(e) => setData('descripcion', e.target.value)}
                                        placeholder="Descripción detallada de lo que se necesita firmar y el propósito..."
                                        rows={3}
                                    />
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="tipo_flujo">Tipo de Flujo</Label>
                                        <Select value={data.tipo_flujo} onValueChange={(valor) => setData('tipo_flujo', valor)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="secuencial">
                                                    <div>
                                                        <div className="font-medium">Secuencial</div>
                                                        <div className="text-xs text-gray-500">Firma en orden específico</div>
                                                    </div>
                                                </SelectItem>
                                                <SelectItem value="paralelo">
                                                    <div>
                                                        <div className="font-medium">Paralelo</div>
                                                        <div className="text-xs text-gray-500">Cualquier orden</div>
                                                    </div>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div>
                                        <Label htmlFor="fecha_limite">Fecha Límite</Label>
                                        <Input
                                            id="fecha_limite"
                                            type="date"
                                            min={fechaMinima}
                                            value={data.fecha_limite}
                                            onChange={(e) => setData('fecha_limite', e.target.value)}
                                        />
                                    </div>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="requiere_todos"
                                        checked={data.requiere_todos}
                                        onCheckedChange={(checked) => setData('requiere_todos', checked as boolean)}
                                    />
                                    <Label htmlFor="requiere_todos" className="text-sm">
                                        Requiere que todos los firmantes obligatorios firmen
                                    </Label>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Documento seleccionado */}
                        {documentoSeleccionado && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Documento Seleccionado</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <div className="flex items-start space-x-3">
                                            <FileText className="w-5 h-5 text-blue-600 mt-0.5" />
                                            <div className="flex-1">
                                                <h4 className="font-medium text-blue-900">{documentoSeleccionado.titulo}</h4>
                                                <p className="text-sm text-blue-700 mt-1">
                                                    Tipo: {documentoSeleccionado.tipo_documento || 'Sin tipo'}
                                                </p>
                                                {documentoSeleccionado.expediente && (
                                                    <p className="text-sm text-blue-700">
                                                        Expediente: {documentoSeleccionado.expediente.codigo}{documentoSeleccionado.expediente.titulo ? ` - ${documentoSeleccionado.expediente.titulo}` : ''}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Firmantes */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center justify-between">
                                    <div className="flex items-center">
                                        <Users className="w-5 h-5 mr-2" />
                                        Firmantes ({firmantes.length})
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setMostrarSelectorUsuarios(true)}
                                    >
                                        <UserPlus className="w-4 h-4 mr-2" />
                                        Agregar Firmante
                                    </Button>
                                </CardTitle>
                                <CardDescription>
                                    {data.tipo_flujo === 'secuencial' 
                                        ? 'Los firmantes deben firmar en el orden especificado'
                                        : 'Los firmantes pueden firmar en cualquier orden'
                                    }
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {firmantes.length === 0 ? (
                                    <div className="text-center py-8 text-gray-500">
                                        <Users className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                                        <p>No se han agregado firmantes</p>
                                        <p className="text-sm">Haz clic en "Agregar Firmante" para comenzar</p>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {/* Tabla de firmantes */}
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    {data.tipo_flujo === 'secuencial' && <TableHead>Orden</TableHead>}
                                                    <TableHead>Firmante</TableHead>
                                                    <TableHead>Cargo</TableHead>
                                                    <TableHead>Rol</TableHead>
                                                    <TableHead>Obligatorio</TableHead>
                                                    <TableHead>Acciones</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {firmantes
                                                    .sort((a, b) => a.orden - b.orden)
                                                    .map((firmante) => (
                                                    <TableRow key={firmante.usuario_id}>
                                                        {data.tipo_flujo === 'secuencial' && (
                                                            <TableCell>
                                                                <div className="flex items-center space-x-2">
                                                                    <span className="text-sm font-medium">#{firmante.orden}</span>
                                                                    <div className="flex flex-col space-y-1">
                                                                        <Button
                                                                            type="button"
                                                                            variant="outline"
                                                                            size="sm"
                                                                            className="h-6 w-6 p-0"
                                                                            onClick={() => cambiarOrdenFirmante(firmante.usuario_id, firmante.orden - 1)}
                                                                            disabled={firmante.orden === 1}
                                                                        >
                                                                            ↑
                                                                        </Button>
                                                                        <Button
                                                                            type="button"
                                                                            variant="outline"
                                                                            size="sm"
                                                                            className="h-6 w-6 p-0"
                                                                            onClick={() => cambiarOrdenFirmante(firmante.usuario_id, firmante.orden + 1)}
                                                                            disabled={firmante.orden === firmantes.length}
                                                                        >
                                                                            ↓
                                                                        </Button>
                                                                    </div>
                                                                </div>
                                                            </TableCell>
                                                        )}
                                                        <TableCell>
                                                            <div className="flex items-center space-x-3">
                                                                <User className="w-4 h-4 text-gray-400" />
                                                                <div>
                                                                    <div className="font-medium">{firmante.usuario?.name}</div>
                                                                    <div className="text-sm text-gray-500">{firmante.usuario?.email}</div>
                                                                </div>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            <span className="text-sm text-gray-600">
                                                                {firmante.usuario?.cargo || 'No especificado'}
                                                            </span>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Select
                                                                value={firmante.rol}
                                                                onValueChange={(value) => cambiarRolFirmante(firmante.usuario_id, value as any)}
                                                            >
                                                                <SelectTrigger className="w-[140px]">
                                                                    <SelectValue />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    <SelectItem value="aprobador">Aprobador</SelectItem>
                                                                    <SelectItem value="revisor">Revisor</SelectItem>
                                                                    <SelectItem value="testigo">Testigo</SelectItem>
                                                                    <SelectItem value="autoridad">Autoridad</SelectItem>
                                                                    <SelectItem value="validador">Validador</SelectItem>
                                                                </SelectContent>
                                                            </Select>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Checkbox
                                                                checked={firmante.es_obligatorio}
                                                                onCheckedChange={() => toggleEsObligatorio(firmante.usuario_id)}
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <Button
                                                                type="button"
                                                                variant="destructive"
                                                                size="sm"
                                                                onClick={() => removerFirmante(firmante.usuario_id)}
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </Button>
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                )}
                                
                                {errors.firmantes && (
                                    <Alert className="mt-4">
                                        <AlertCircle className="h-4 w-4" />
                                        <AlertDescription className="text-red-600">
                                            {errors.firmantes}
                                        </AlertDescription>
                                    </Alert>
                                )}
                            </CardContent>
                        </Card>

                        {/* Configuración avanzada */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Configuración Avanzada</CardTitle>
                                <CardDescription>
                                    Opciones adicionales para la solicitud
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="permitir_comentarios"
                                            checked={data.configuracion.permitir_comentarios}
                                            onCheckedChange={(checked) => setData('configuracion', {
                                                ...data.configuracion,
                                                permitir_comentarios: checked as boolean
                                            })}
                                        />
                                        <Label htmlFor="permitir_comentarios" className="text-sm">
                                            Permitir comentarios
                                        </Label>
                                    </div>

                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="notificar_cambios"
                                            checked={data.configuracion.notificar_cambios}
                                            onCheckedChange={(checked) => setData('configuracion', {
                                                ...data.configuracion,
                                                notificar_cambios: checked as boolean
                                            })}
                                        />
                                        <Label htmlFor="notificar_cambios" className="text-sm">
                                            Notificar cambios por email
                                        </Label>
                                    </div>

                                    <div>
                                        <Label htmlFor="recordatorio_dias" className="text-sm">
                                            Recordatorio (días)
                                        </Label>
                                        <Input
                                            id="recordatorio_dias"
                                            type="number"
                                            min="1"
                                            max="30"
                                            value={data.configuracion.recordatorio_dias}
                                            onChange={(e) => setData('configuracion', {
                                                ...data.configuracion,
                                                recordatorio_dias: parseInt(e.target.value) || 3
                                            })}
                                            className="mt-1"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Botones de acción */}
                        <div className="flex items-center justify-end space-x-4">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => router.get(route('admin.firmas.solicitudes'))}
                            >
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing || firmantes.length === 0}>
                                {processing ? (
                                    <>
                                        <Clock className="w-4 h-4 mr-2 animate-spin" />
                                        Creando...
                                    </>
                                ) : (
                                    <>
                                        <Save className="w-4 h-4 mr-2" />
                                        Crear Solicitud
                                    </>
                                )}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>

            {/* Modal selector de usuarios */}
            <Dialog open={mostrarSelectorUsuarios} onOpenChange={setMostrarSelectorUsuarios}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Seleccionar Firmantes</DialogTitle>
                        <DialogDescription>
                            Busca y selecciona los usuarios que deben firmar el documento
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div className="space-y-4">
                        <div className="relative">
                            <Search className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                            <Input
                                placeholder="Buscar por nombre, email o cargo..."
                                value={busquedaUsuario}
                                onChange={(e) => setBusquedaUsuario(e.target.value)}
                                className="pl-10"
                            />
                        </div>

                        <div className="max-h-96 overflow-y-auto">
                            {usuariosFiltrados.length === 0 ? (
                                <div className="text-center py-8 text-gray-500">
                                    <User className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                                    <p>No se encontraron usuarios</p>
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    {usuariosFiltrados.map((usuario) => {
                                        const yaAgregado = firmantes.some(f => f.usuario_id === usuario.id);
                                        return (
                                            <div
                                                key={usuario.id}
                                                className={`flex items-center justify-between p-3 border rounded-lg ${
                                                    yaAgregado 
                                                        ? 'bg-gray-50 border-gray-200' 
                                                        : 'hover:bg-gray-50 cursor-pointer'
                                                }`}
                                                onClick={() => !yaAgregado && agregarFirmante(usuario)}
                                            >
                                                <div className="flex items-center space-x-3">
                                                    <User className="w-5 h-5 text-gray-400" />
                                                    <div>
                                                        <div className="font-medium">{usuario.name}</div>
                                                        <div className="text-sm text-gray-500">{usuario.email}</div>
                                                        {usuario.cargo && (
                                                            <div className="text-xs text-gray-400">{usuario.cargo}</div>
                                                        )}
                                                    </div>
                                                </div>
                                                {yaAgregado ? (
                                                    <Badge variant="secondary">Ya agregado</Badge>
                                                ) : (
                                                    <Button size="sm" onClick={() => agregarFirmante(usuario)}>
                                                        <Plus className="w-4 h-4" />
                                                    </Button>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setMostrarSelectorUsuarios(false)}>
                            Cerrar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
