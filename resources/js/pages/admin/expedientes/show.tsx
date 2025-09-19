import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
    Dialog, 
    DialogContent, 
    DialogDescription, 
    DialogHeader, 
    DialogTitle, 
    DialogTrigger,
    DialogFooter
} from '@/components/ui/dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    FileText, 
    Calendar, 
    MapPin, 
    User, 
    Tag, 
    Settings, 
    Download, 
    Shield, 
    Eye,
    Edit,
    Archive,
    Trash2,
    Clock,
    CheckCircle,
    XCircle,
    AlertTriangle,
    Info
} from 'lucide-react';

interface Expediente {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string;
    estado: string;
    fecha_apertura: string;
    fecha_cierre?: string;
    ubicacion_fisica: string;
    ubicacion_digital: string;
    responsable: string;
    departamento: string;
    clasificacion_serie: string;
    clasificacion_subserie: string;
    nivel_acceso: string;
    palabras_clave: string[];
    observaciones?: string;
    configuracion: {
        permite_documentos_electronicos: boolean;
        requiere_firma_digital: boolean;
        control_versiones: boolean;
        notificaciones_automaticas: boolean;
    };
    estadisticas: {
        total_documentos: number;
        documentos_por_tipo: Record<string, number>;
        tamaño_total: string;
        ultima_actividad: string;
    };
    documentos?: Array<{
        id: number;
        nombre: string;
        tipo: string;
        tamaño: string;
        fecha_creacion: string;
        version: number;
    }>;
    actividades?: Array<{
        id: number;
        accion: string;
        usuario: string;
        fecha: string;
        detalles: string;
    }>;
    created_at: string;
    updated_at: string;
}

interface Props {
    expediente: Expediente;
    can: {
        edit: boolean;
        delete: boolean;
        cambiar_estado: boolean;
        exportar: boolean;
        verificar_integridad: boolean;
    };
}

const estadoColors: Record<string, string> = {
    abierto: 'bg-green-100 text-green-800',
    tramite: 'bg-blue-100 text-blue-800',
    revision: 'bg-yellow-100 text-yellow-800',
    cerrado: 'bg-gray-100 text-gray-800',
    archivado: 'bg-purple-100 text-purple-800',
    eliminado: 'bg-red-100 text-red-800',
};

const estadoIcons: Record<string, React.ReactNode> = {
    abierto: <CheckCircle className="h-4 w-4" />,
    tramite: <Clock className="h-4 w-4" />,
    revision: <AlertTriangle className="h-4 w-4" />,
    cerrado: <Archive className="h-4 w-4" />,
    archivado: <Archive className="h-4 w-4" />,
    eliminado: <XCircle className="h-4 w-4" />,
};

const nivelAccesoColors: Record<string, string> = {
    publico: 'bg-green-100 text-green-800',
    restringido: 'bg-yellow-100 text-yellow-800',
    confidencial: 'bg-red-100 text-red-800',
    secreto: 'bg-red-200 text-red-900',
};

export default function Show({ expediente, can }: Props) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [showCambiarEstadoDialog, setShowCambiarEstadoDialog] = useState(false);
    const [nuevoEstado, setNuevoEstado] = useState('');

    const { delete: destroy, processing: deleting } = useForm();
    const { post, processing: cambiandoEstado } = useForm();

    const handleDelete = () => {
        destroy(route('admin.expedientes.destroy', expediente.id), {
            onSuccess: () => setShowDeleteDialog(false),
        });
    };

    const handleCambiarEstado = () => {
        post(route('admin.expedientes.cambiar-estado', expediente.id), {
            data: { estado: nuevoEstado },
            onSuccess: () => {
                setShowCambiarEstadoDialog(false);
                setNuevoEstado('');
            },
        });
    };

    const exportarDirectorio = () => {
        window.open(route('admin.expedientes.exportar-directorio', expediente.id));
    };

    const verificarIntegridad = () => {
        window.open(route('admin.expedientes.verificar-integridad', expediente.id));
    };

    return (
        <AppLayout>
            <Head title={`Expediente: ${expediente.nombre}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center space-x-2">
                            <Link 
                                href={route('admin.expedientes.index')}
                                className="text-sm text-muted-foreground hover:text-foreground"
                            >
                                Expedientes
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">{expediente.codigo}</span>
                        </div>
                        <h1 className="text-2xl font-bold mt-1">{expediente.nombre}</h1>
                        <p className="text-muted-foreground">{expediente.descripcion}</p>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <Badge 
                            variant="secondary" 
                            className={`${estadoColors[expediente.estado]} flex items-center space-x-1`}
                        >
                            {estadoIcons[expediente.estado]}
                            <span className="capitalize">{expediente.estado}</span>
                        </Badge>
                        
                        <Badge 
                            variant="outline"
                            className={`${nivelAccesoColors[expediente.nivel_acceso]} flex items-center space-x-1`}
                        >
                            <Shield className="h-3 w-3" />
                            <span className="capitalize">{expediente.nivel_acceso}</span>
                        </Badge>
                    </div>
                </div>

                {/* Actions */}
                <div className="flex flex-wrap gap-2">
                    {can.edit && (
                        <Button asChild>
                            <Link href={route('admin.expedientes.edit', expediente.id)}>
                                <Edit className="h-4 w-4 mr-2" />
                                Editar
                            </Link>
                        </Button>
                    )}
                    
                    {can.cambiar_estado && (
                        <Dialog open={showCambiarEstadoDialog} onOpenChange={setShowCambiarEstadoDialog}>
                            <DialogTrigger asChild>
                                <Button variant="outline">
                                    <Settings className="h-4 w-4 mr-2" />
                                    Cambiar Estado
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Cambiar Estado del Expediente</DialogTitle>
                                    <DialogDescription>
                                        Selecciona el nuevo estado para el expediente "{expediente.nombre}".
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="space-y-4">
                                    <div className="grid grid-cols-2 gap-2">
                                        {['abierto', 'tramite', 'revision', 'cerrado', 'archivado'].map((estado) => (
                                            <Button
                                                key={estado}
                                                variant={nuevoEstado === estado ? "default" : "outline"}
                                                onClick={() => setNuevoEstado(estado)}
                                                className="justify-start"
                                            >
                                                {estadoIcons[estado]}
                                                <span className="ml-2 capitalize">{estado}</span>
                                            </Button>
                                        ))}
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button 
                                        variant="outline" 
                                        onClick={() => setShowCambiarEstadoDialog(false)}
                                    >
                                        Cancelar
                                    </Button>
                                    <Button 
                                        onClick={handleCambiarEstado}
                                        disabled={!nuevoEstado || cambiandoEstado}
                                    >
                                        {cambiandoEstado ? 'Cambiando...' : 'Cambiar Estado'}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    )}
                    
                    {can.exportar && (
                        <Button variant="outline" onClick={exportarDirectorio}>
                            <Download className="h-4 w-4 mr-2" />
                            Exportar Directorio
                        </Button>
                    )}
                    
                    {can.verificar_integridad && (
                        <Button variant="outline" onClick={verificarIntegridad}>
                            <Shield className="h-4 w-4 mr-2" />
                            Verificar Integridad
                        </Button>
                    )}
                    
                    {can.delete && (
                        <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                            <DialogTrigger asChild>
                                <Button variant="destructive">
                                    <Trash2 className="h-4 w-4 mr-2" />
                                    Eliminar
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>¿Eliminar expediente?</DialogTitle>
                                    <DialogDescription>
                                        Esta acción no se puede deshacer. Se eliminará permanentemente el 
                                        expediente "{expediente.nombre}" y todos sus documentos asociados.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <Button 
                                        variant="outline" 
                                        onClick={() => setShowDeleteDialog(false)}
                                    >
                                        Cancelar
                                    </Button>
                                    <Button 
                                        variant="destructive" 
                                        onClick={handleDelete}
                                        disabled={deleting}
                                    >
                                        {deleting ? 'Eliminando...' : 'Eliminar'}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    )}
                </div>

                {/* Content */}
                <Tabs defaultValue="informacion" className="space-y-4">
                    <TabsList className="grid w-full grid-cols-5">
                        <TabsTrigger value="informacion">Información</TabsTrigger>
                        <TabsTrigger value="documentos">
                            Documentos ({expediente.estadisticas.total_documentos})
                        </TabsTrigger>
                        <TabsTrigger value="configuracion">Configuración</TabsTrigger>
                        <TabsTrigger value="actividad">Actividad</TabsTrigger>
                        <TabsTrigger value="estadisticas">Estadísticas</TabsTrigger>
                    </TabsList>

                    {/* Información General */}
                    <TabsContent value="informacion" className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Información Básica */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <Info className="h-5 w-5" />
                                        <span>Información Básica</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Código</label>
                                        <p className="font-mono">{expediente.codigo}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Nombre</label>
                                        <p>{expediente.nombre}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Descripción</label>
                                        <p>{expediente.descripcion}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Estado</label>
                                        <Badge className={`${estadoColors[expediente.estado]} mt-1`}>
                                            {expediente.estado}
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Fechas */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <Calendar className="h-5 w-5" />
                                        <span>Fechas</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Fecha de Apertura</label>
                                        <p>{new Date(expediente.fecha_apertura).toLocaleDateString()}</p>
                                    </div>
                                    {expediente.fecha_cierre && (
                                        <div>
                                            <label className="text-sm font-medium text-muted-foreground">Fecha de Cierre</label>
                                            <p>{new Date(expediente.fecha_cierre).toLocaleDateString()}</p>
                                        </div>
                                    )}
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Creado</label>
                                        <p>{new Date(expediente.created_at).toLocaleDateString()}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Actualizado</label>
                                        <p>{new Date(expediente.updated_at).toLocaleDateString()}</p>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Ubicación */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <MapPin className="h-5 w-5" />
                                        <span>Ubicación</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Ubicación Física</label>
                                        <p>{expediente.ubicacion_fisica}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Ubicación Digital</label>
                                        <p className="font-mono text-sm break-all">{expediente.ubicacion_digital}</p>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Responsabilidad */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <User className="h-5 w-5" />
                                        <span>Responsabilidad</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Responsable</label>
                                        <p>{expediente.responsable}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Departamento</label>
                                        <p>{expediente.departamento}</p>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Clasificación */}
                            <Card className="md:col-span-2">
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <FileText className="h-5 w-5" />
                                        <span>Clasificación Archivística</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label className="text-sm font-medium text-muted-foreground">Serie</label>
                                            <p>{expediente.clasificacion_serie}</p>
                                        </div>
                                        <div>
                                            <label className="text-sm font-medium text-muted-foreground">Subserie</label>
                                            <p>{expediente.clasificacion_subserie}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Palabras Clave */}
                            <Card className="md:col-span-2">
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <Tag className="h-5 w-5" />
                                        <span>Palabras Clave</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex flex-wrap gap-2">
                                        {expediente.palabras_clave.map((palabra, index) => (
                                            <Badge key={index} variant="secondary">
                                                {palabra}
                                            </Badge>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Observaciones */}
                            {expediente.observaciones && (
                                <Card className="md:col-span-2">
                                    <CardHeader>
                                        <CardTitle>Observaciones</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-sm">{expediente.observaciones}</p>
                                    </CardContent>
                                </Card>
                            )}
                        </div>
                    </TabsContent>

                    {/* Documentos */}
                    <TabsContent value="documentos" className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-medium">
                                Documentos ({expediente.estadisticas.total_documentos})
                            </h3>
                            <Button asChild>
                                <Link href={route('admin.documentos.create', { expediente: expediente.id })}>
                                    Agregar Documento
                                </Link>
                            </Button>
                        </div>

                        {expediente.documentos && expediente.documentos.length > 0 ? (
                            <div className="grid gap-4">
                                {expediente.documentos.map((documento) => (
                                    <Card key={documento.id}>
                                        <CardContent className="p-4">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center space-x-3">
                                                    <FileText className="h-8 w-8 text-muted-foreground" />
                                                    <div>
                                                        <h4 className="font-medium">{documento.nombre}</h4>
                                                        <div className="flex items-center space-x-4 text-sm text-muted-foreground">
                                                            <span>Tipo: {documento.tipo}</span>
                                                            <span>Tamaño: {documento.tamaño}</span>
                                                            <span>Versión: {documento.version}</span>
                                                            <span>{new Date(documento.fecha_creacion).toLocaleDateString()}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center space-x-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={route('admin.documentos.show', documento.id)}>
                                                            <Eye className="h-4 w-4 mr-1" />
                                                            Ver
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        ) : (
                            <Alert>
                                <Info className="h-4 w-4" />
                                <AlertDescription>
                                    No hay documentos asociados a este expediente.
                                </AlertDescription>
                            </Alert>
                        )}
                    </TabsContent>

                    {/* Configuración */}
                    <TabsContent value="configuracion" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <Settings className="h-5 w-5" />
                                    <span>Configuración del Expediente</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="flex items-center justify-between p-3 border rounded-lg">
                                        <div>
                                            <h4 className="font-medium">Documentos Electrónicos</h4>
                                            <p className="text-sm text-muted-foreground">
                                                Permite agregar documentos digitales
                                            </p>
                                        </div>
                                        <Badge variant={expediente.configuracion.permite_documentos_electronicos ? "default" : "secondary"}>
                                            {expediente.configuracion.permite_documentos_electronicos ? "Habilitado" : "Deshabilitado"}
                                        </Badge>
                                    </div>
                                    
                                    <div className="flex items-center justify-between p-3 border rounded-lg">
                                        <div>
                                            <h4 className="font-medium">Firma Digital</h4>
                                            <p className="text-sm text-muted-foreground">
                                                Requiere firma digital para documentos
                                            </p>
                                        </div>
                                        <Badge variant={expediente.configuracion.requiere_firma_digital ? "default" : "secondary"}>
                                            {expediente.configuracion.requiere_firma_digital ? "Requerido" : "Opcional"}
                                        </Badge>
                                    </div>
                                    
                                    <div className="flex items-center justify-between p-3 border rounded-lg">
                                        <div>
                                            <h4 className="font-medium">Control de Versiones</h4>
                                            <p className="text-sm text-muted-foreground">
                                                Mantiene historial de versiones
                                            </p>
                                        </div>
                                        <Badge variant={expediente.configuracion.control_versiones ? "default" : "secondary"}>
                                            {expediente.configuracion.control_versiones ? "Activo" : "Inactivo"}
                                        </Badge>
                                    </div>
                                    
                                    <div className="flex items-center justify-between p-3 border rounded-lg">
                                        <div>
                                            <h4 className="font-medium">Notificaciones</h4>
                                            <p className="text-sm text-muted-foreground">
                                                Envía notificaciones automáticas
                                            </p>
                                        </div>
                                        <Badge variant={expediente.configuracion.notificaciones_automaticas ? "default" : "secondary"}>
                                            {expediente.configuracion.notificaciones_automaticas ? "Activas" : "Inactivas"}
                                        </Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Actividad */}
                    <TabsContent value="actividad" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <Clock className="h-5 w-5" />
                                    <span>Historial de Actividad</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {expediente.actividades && expediente.actividades.length > 0 ? (
                                    <div className="space-y-4">
                                        {expediente.actividades.map((actividad) => (
                                            <div key={actividad.id} className="flex items-start space-x-3 pb-3 border-b last:border-b-0">
                                                <div className="flex-shrink-0 w-2 h-2 bg-primary rounded-full mt-2"></div>
                                                <div className="flex-grow">
                                                    <div className="flex items-center justify-between">
                                                        <h4 className="font-medium">{actividad.accion}</h4>
                                                        <span className="text-sm text-muted-foreground">
                                                            {new Date(actividad.fecha).toLocaleDateString()}
                                                        </span>
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">Por: {actividad.usuario}</p>
                                                    {actividad.detalles && (
                                                        <p className="text-sm mt-1">{actividad.detalles}</p>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <Alert>
                                        <Info className="h-4 w-4" />
                                        <AlertDescription>
                                            No hay actividad registrada para este expediente.
                                        </AlertDescription>
                                    </Alert>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Estadísticas */}
                    <TabsContent value="estadisticas" className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium">Total Documentos</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">{expediente.estadisticas.total_documentos}</div>
                                    <p className="text-xs text-muted-foreground">documentos en el expediente</p>
                                </CardContent>
                            </Card>
                            
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium">Tamaño Total</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">{expediente.estadisticas.tamaño_total}</div>
                                    <p className="text-xs text-muted-foreground">espacio utilizado</p>
                                </CardContent>
                            </Card>
                            
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium">Última Actividad</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-sm font-bold">
                                        {new Date(expediente.estadisticas.ultima_actividad).toLocaleDateString()}
                                    </div>
                                    <p className="text-xs text-muted-foreground">última modificación</p>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Documentos por Tipo */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Documentos por Tipo</CardTitle>
                                <CardDescription>Distribución de documentos según su tipo</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {Object.entries(expediente.estadisticas.documentos_por_tipo).map(([tipo, cantidad]) => (
                                        <div key={tipo} className="flex items-center justify-between">
                                            <span className="capitalize">{tipo}</span>
                                            <Badge variant="secondary">{cantidad}</Badge>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
