import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { FileText, ArrowLeft, Edit, Copy, ToggleLeft, ToggleRight, FileDown, Trash2, Users, FolderOpen, FileBarChart, Calendar, User, Clock, Check, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useState } from 'react';

const breadcrumbItems = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Tablas de Retención Documental', href: '/admin/trd' },
    { title: 'Detalle TRD', href: '#' },
];

interface TRD {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string;
    justificacion: string;
    version: string;
    estado: string;
    vigente: boolean;
    identificador_unico: string;
    formato_archivo: string;
    metadatos_asociados: any;
    fecha_actualizacion: string;
    fecha_aprobacion: string | null;
    created_at: string;
    creador?: {
        id: number;
        name: string;
    };
    modificador?: {
        id: number;
        name: string;
    };
    series?: Array<{
        id: number;
        codigo: string;
        nombre: string;
        subseries?: Array<{
            id: number;
            codigo: string;
            nombre: string;
        }>;
        tipologias?: Array<{
            id: number;
            codigo: string;
            nombre: string;
        }>;
    }>;
}

interface Version {
    id: number;
    version: string;
    estado: string;
    vigente: boolean;
    fecha_actualizacion: string;
    created_at: string;
}

interface Stats {
    series_count: number;
    subseries_count: number;
    expedientes_count: number;
    documentos_count: number;
}

interface Props {
    trd: TRD;
    versiones: Version[];
    estadisticas: Stats;
    tieneDocumentosAsociados: boolean;
}

export default function AdminTRDShow({ trd, versiones, estadisticas, tieneDocumentosAsociados }: Props) {
    const [showDeleteModal, setShowDeleteModal] = useState(false);

    const handleDelete = () => {
        router.delete(`/admin/trd/${trd.id}`, {
            onFinish: () => setShowDeleteModal(false)
        });
    };

    const handleToggleVigencia = () => {
        router.patch(`/admin/trd/${trd.id}/vigencia`, {}, {
            preserveScroll: true
        });
    };

    const handleDuplicate = () => {
        router.post(`/admin/trd/${trd.id}/duplicate`);
    };

    const handleExport = (formato: string) => {
        window.open(`/admin/trd/${trd.id}/export?formato=${formato}`, '_blank');
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const getEstadoBadge = (estado: string) => {
        const config = {
            'borrador': { color: 'bg-gray-100 text-gray-800', icon: Edit },
            'revision': { color: 'bg-yellow-100 text-yellow-800', icon: Clock },
            'aprobada': { color: 'bg-green-100 text-green-800', icon: Check },
            'vigente': { color: 'bg-blue-100 text-blue-800', icon: Check },
            'historica': { color: 'bg-purple-100 text-purple-800', icon: FileBarChart },
        }[estado] || { color: 'bg-gray-100 text-gray-800', icon: AlertCircle };

        const Icon = config.icon;

        return (
            <Badge className={`${config.color} font-medium flex items-center gap-1`}>
                <Icon className="h-3 w-3" />
                {estado.charAt(0).toUpperCase() + estado.slice(1)}
            </Badge>
        );
    };

    return (
        <AppLayout breadcrumbItems={breadcrumbItems}>
            <Head title={`${trd.nombre} - Detalle TRD`} />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/admin/trd">
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Button>
                        </Link>
                        <div>
                            <div className="flex items-center gap-3 mb-2">
                                <h1 className="text-2xl font-bold text-gray-900">{trd.nombre}</h1>
                                {getEstadoBadge(trd.estado)}
                                {trd.vigente && (
                                    <Badge className="bg-blue-100 text-blue-800 font-medium flex items-center gap-1">
                                        <ToggleRight className="h-3 w-3" />
                                        Vigente
                                    </Badge>
                                )}
                            </div>
                            <p className="text-gray-600">
                                {trd.codigo} • Versión {trd.version} • {trd.identificador_unico}
                            </p>
                        </div>
                    </div>
                    
                    <div className="flex items-center gap-2">
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button 
                                        variant="outline" 
                                        size="sm"
                                        onClick={() => handleExport('json')}
                                    >
                                        <FileDown className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Exportar TRD</p>
                                </TooltipContent>
                            </Tooltip>

                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button 
                                        variant="outline" 
                                        size="sm"
                                        onClick={handleDuplicate}
                                    >
                                        <Copy className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Duplicar TRD</p>
                                </TooltipContent>
                            </Tooltip>

                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button 
                                        variant="outline" 
                                        size="sm"
                                        onClick={handleToggleVigencia}
                                        disabled={trd.estado !== 'aprobada' && !trd.vigente}
                                    >
                                        {trd.vigente ? (
                                            <ToggleRight className="h-4 w-4 text-green-600" />
                                        ) : (
                                            <ToggleLeft className="h-4 w-4" />
                                        )}
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{trd.vigente ? 'Desmarcar vigente' : 'Marcar vigente'}</p>
                                </TooltipContent>
                            </Tooltip>

                            <Link href={`/admin/trd/${trd.id}/edit`}>
                                <Button variant="outline" size="sm">
                                    <Edit className="h-4 w-4" />
                                </Button>
                            </Link>

                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button 
                                        variant="outline" 
                                        size="sm"
                                        onClick={() => setShowDeleteModal(true)}
                                        className="text-red-600 hover:text-red-700"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Eliminar TRD</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>

                {/* Alertas */}
                {tieneDocumentosAsociados && (
                    <Alert className="border-yellow-200 bg-yellow-50">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription className="text-yellow-800">
                            <strong>Esta TRD tiene documentos asociados.</strong> Cualquier modificación debe 
                            considerar el impacto en los documentos existentes.
                        </AlertDescription>
                    </Alert>
                )}

                {/* Estadísticas */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center">
                                <div className="bg-blue-100 rounded-full p-2">
                                    <FolderOpen className="h-6 w-6 text-blue-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Series</p>
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.series_count}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center">
                                <div className="bg-green-100 rounded-full p-2">
                                    <FolderOpen className="h-6 w-6 text-green-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Subseries</p>
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.subseries_count}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center">
                                <div className="bg-purple-100 rounded-full p-2">
                                    <FileBarChart className="h-6 w-6 text-purple-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Expedientes</p>
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.expedientes_count}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center">
                                <div className="bg-orange-100 rounded-full p-2">
                                    <FileText className="h-6 w-6 text-orange-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Documentos</p>
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.documentos_count}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Información Principal */}
                    <div className="lg:col-span-2 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <FileText className="h-5 w-5 text-[#2a3d83]" />
                                    Información de la TRD
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {trd.descripcion && (
                                    <div>
                                        <h4 className="font-medium text-gray-900 mb-2">Descripción</h4>
                                        <p className="text-gray-600 leading-relaxed">{trd.descripcion}</p>
                                    </div>
                                )}
                                
                                {trd.justificacion && (
                                    <div>
                                        <h4 className="font-medium text-gray-900 mb-2">Justificación</h4>
                                        <p className="text-gray-600 leading-relaxed">{trd.justificacion}</p>
                                    </div>
                                )}

                                <Separator />

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <h4 className="font-medium text-gray-900 mb-1">Formato de Archivo</h4>
                                        <p className="text-gray-600">{trd.formato_archivo || 'No especificado'}</p>
                                    </div>
                                    <div>
                                        <h4 className="font-medium text-gray-900 mb-1">Estado Actual</h4>
                                        <div className="flex items-center gap-2">
                                            {getEstadoBadge(trd.estado)}
                                        </div>
                                    </div>
                                </div>

                                {trd.metadatos_asociados && (
                                    <div>
                                        <h4 className="font-medium text-gray-900 mb-2">Metadatos Asociados</h4>
                                        <pre className="bg-gray-50 p-3 rounded-md text-sm font-mono overflow-x-auto">
                                            {JSON.stringify(trd.metadatos_asociados, null, 2)}
                                        </pre>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Series Documentales */}
                        {trd.series && trd.series.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Series Documentales</CardTitle>
                                    <CardDescription>
                                        Series asociadas a esta TRD ({trd.series.length})
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {trd.series.map((serie) => (
                                            <div key={serie.id} className="border rounded-lg p-4">
                                                <div className="flex items-center justify-between mb-2">
                                                    <h4 className="font-medium text-gray-900">{serie.nombre}</h4>
                                                    <Badge variant="outline">{serie.codigo}</Badge>
                                                </div>
                                                {serie.subseries && serie.subseries.length > 0 && (
                                                    <div className="ml-4 mt-2">
                                                        <p className="text-sm text-gray-600 mb-1">
                                                            Subseries ({serie.subseries.length}):
                                                        </p>
                                                        <div className="flex flex-wrap gap-1">
                                                            {serie.subseries.map((subserie) => (
                                                                <Badge key={subserie.id} variant="secondary" className="text-xs">
                                                                    {subserie.codigo}
                                                                </Badge>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Panel Lateral */}
                    <div className="space-y-6">
                        {/* Información del Sistema */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">Información del Sistema</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center gap-3">
                                    <Calendar className="h-4 w-4 text-gray-500" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">Creado</p>
                                        <p className="text-sm text-gray-600">{formatDate(trd.created_at)}</p>
                                        {trd.creador && (
                                            <p className="text-xs text-gray-500">por {trd.creador.name}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="flex items-center gap-3">
                                    <Clock className="h-4 w-4 text-gray-500" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">Última Actualización</p>
                                        <p className="text-sm text-gray-600">{formatDate(trd.fecha_actualizacion)}</p>
                                        {trd.modificador && (
                                            <p className="text-xs text-gray-500">por {trd.modificador.name}</p>
                                        )}
                                    </div>
                                </div>

                                {trd.fecha_aprobacion && (
                                    <div className="flex items-center gap-3">
                                        <Check className="h-4 w-4 text-green-500" />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">Aprobado</p>
                                            <p className="text-sm text-gray-600">{formatDate(trd.fecha_aprobacion)}</p>
                                        </div>
                                    </div>
                                )}

                                <Separator />

                                <div>
                                    <p className="text-sm font-medium text-gray-900 mb-1">ID Único</p>
                                    <p className="text-xs font-mono text-gray-600 bg-gray-50 px-2 py-1 rounded">
                                        {trd.identificador_unico}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Historial de Versiones */}
                        {versiones.length > 1 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Historial de Versiones</CardTitle>
                                    <CardDescription>
                                        {versiones.length} versiones disponibles
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {versiones.slice(0, 5).map((version) => (
                                            <div key={version.id} className="flex items-center justify-between p-2 rounded border">
                                                <div>
                                                    <p className="text-sm font-medium">v{version.version}</p>
                                                    <p className="text-xs text-gray-500">
                                                        {formatDate(version.created_at)}
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    {getEstadoBadge(version.estado)}
                                                    {version.vigente && (
                                                        <Badge variant="secondary" className="text-xs">
                                                            Vigente
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                        {versiones.length > 5 && (
                                            <p className="text-xs text-gray-500 text-center">
                                                +{versiones.length - 5} versiones más
                                            </p>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>

                {/* Delete Confirmation Modal */}
                <Dialog open={showDeleteModal} onOpenChange={setShowDeleteModal}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Confirmar Eliminación</DialogTitle>
                            <DialogDescription>
                                ¿Estás seguro de que deseas eliminar la TRD "{trd.nombre}"?
                                Esta acción no se puede deshacer.
                                {tieneDocumentosAsociados && (
                                    <div className="mt-2 p-2 bg-red-50 border border-red-200 rounded">
                                        <strong className="text-red-800">Advertencia:</strong> Esta TRD tiene documentos asociados.
                                    </div>
                                )}
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShowDeleteModal(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                onClick={handleDelete}
                            >
                                Eliminar
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
