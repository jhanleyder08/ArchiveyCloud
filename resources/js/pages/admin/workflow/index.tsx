import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { 
    GitBranch, 
    Clock, 
    CheckCircle, 
    XCircle,
    AlertTriangle,
    FileText,
    TrendingUp,
    Users,
    Calendar,
    Plus
} from 'lucide-react';

interface WorkflowPendiente {
    id: number;
    documento: {
        id: number;
        nombre: string;
        codigo: string;
    };
    solicitante: string;
    prioridad: string;
    fecha_solicitud: string;
    fecha_vencimiento: string;
    esta_vencido: boolean;
    progreso: number;
    descripcion: string;
}

interface WorkflowSolicitado {
    id: number;
    documento: {
        id: number;
        nombre: string;
        codigo: string;
    };
    estado: string;
    prioridad: string;
    revisor_actual: string;
    fecha_solicitud: string;
    progreso: number;
    esta_vencido: boolean;
}

interface Estadisticas {
    pendientes_aprobacion: number;
    solicitados_activos: number;
    aprobados_mes: number;
    rechazados_mes: number;
}

interface Props {
    workflowsPendientes: WorkflowPendiente[];
    workflowsSolicitados: WorkflowSolicitado[];
    estadisticas: Estadisticas;
}

export default function WorkflowIndex({ workflowsPendientes, workflowsSolicitados, estadisticas }: Props) {
    const getEstadoBadge = (estado: string) => {
        const badges = {
            'borrador': <Badge variant="outline" className="text-gray-600">Borrador</Badge>,
            'pendiente': <Badge variant="outline" className="text-yellow-600">Pendiente</Badge>,
            'en_revision': <Badge className="bg-blue-100 text-blue-800">En Revisión</Badge>,
            'aprobado': <Badge className="bg-green-100 text-green-800">Aprobado</Badge>,
            'rechazado': <Badge variant="destructive">Rechazado</Badge>
        };
        return badges[estado as keyof typeof badges] || <Badge variant="outline">{estado}</Badge>;
    };

    const getPrioridadBadge = (prioridad: string) => {
        const badges = {
            'Crítica': <Badge variant="destructive">Crítica</Badge>,
            'Alta': <Badge className="bg-orange-100 text-orange-800">Alta</Badge>,
            'Media': <Badge variant="outline" className="text-blue-600">Media</Badge>,
            'Baja': <Badge variant="outline" className="text-gray-600">Baja</Badge>
        };
        return badges[prioridad as keyof typeof badges] || <Badge variant="outline">{prioridad}</Badge>;
    };

    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleDateString();
    };

    return (
        <AppLayout>
            <Head title="Workflow de Aprobaciones" />
            
            <div className="container mx-auto py-6">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <GitBranch className="w-8 h-8 text-blue-600" />
                            Workflow de Aprobaciones
                        </h1>
                        <p className="text-gray-600 mt-1">
                            Gestiona los procesos de aprobación de documentos
                        </p>
                    </div>
                    <Link href="/admin/workflow/create">
                        <Button>
                            <Plus className="w-4 h-4 mr-2" />
                            Nuevo Workflow
                        </Button>
                    </Link>
                </div>

                {/* Estadísticas */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Pendientes de Aprobación</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-orange-600">{estadisticas.pendientes_aprobacion}</div>
                            <p className="text-xs text-muted-foreground">Requieren tu atención</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Solicitados Activos</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{estadisticas.solicitados_activos}</div>
                            <p className="text-xs text-muted-foreground">En proceso de aprobación</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Aprobados este Mes</CardTitle>
                            <CheckCircle className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{estadisticas.aprobados_mes}</div>
                            <p className="text-xs text-muted-foreground">Completados exitosamente</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Rechazados este Mes</CardTitle>
                            <XCircle className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">{estadisticas.rechazados_mes}</div>
                            <p className="text-xs text-muted-foreground">No aprobados</p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Workflows Pendientes de Aprobación */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock className="w-5 h-5 text-orange-600" />
                                Pendientes de Aprobación
                            </CardTitle>
                            <CardDescription>
                                Documentos que requieren tu aprobación
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {workflowsPendientes.length > 0 ? (
                                <div className="space-y-4">
                                    {workflowsPendientes.map((workflow) => (
                                        <div key={workflow.id} className="p-4 border rounded-lg hover:bg-gray-50">
                                            <div className="flex items-start justify-between mb-3">
                                                <div>
                                                    <Link href={`/admin/workflow/${workflow.id}/aprobar`}>
                                                        <h4 className="font-medium text-blue-600 hover:underline">
                                                            {workflow.documento.nombre}
                                                        </h4>
                                                    </Link>
                                                    <p className="text-sm text-gray-600">{workflow.documento.codigo}</p>
                                                </div>
                                                <div className="text-right">
                                                    {getPrioridadBadge(workflow.prioridad)}
                                                    {workflow.esta_vencido && (
                                                        <div className="mt-1">
                                                            <Badge variant="destructive" className="text-xs">
                                                                <AlertTriangle className="w-3 h-3 mr-1" />
                                                                Vencido
                                                            </Badge>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="mb-3">
                                                <div className="flex justify-between items-center mb-1">
                                                    <span className="text-sm font-medium">Progreso</span>
                                                    <span className="text-sm text-gray-600">{workflow.progreso}%</span>
                                                </div>
                                                <Progress value={workflow.progreso} className="h-2" />
                                            </div>

                                            <div className="grid grid-cols-2 gap-4 text-sm">
                                                <div>
                                                    <div className="flex items-center gap-1 text-gray-600">
                                                        <Users className="w-4 h-4" />
                                                        Solicitante
                                                    </div>
                                                    <div className="font-medium">{workflow.solicitante}</div>
                                                </div>
                                                <div>
                                                    <div className="flex items-center gap-1 text-gray-600">
                                                        <Calendar className="w-4 h-4" />
                                                        Vencimiento
                                                    </div>
                                                    <div className="font-medium">{formatearFecha(workflow.fecha_vencimiento)}</div>
                                                </div>
                                            </div>

                                            {workflow.descripcion && (
                                                <div className="mt-3 p-2 bg-gray-50 rounded text-sm">
                                                    {workflow.descripcion}
                                                </div>
                                            )}

                                            <div className="mt-3 flex gap-2">
                                                <Link href={`/admin/workflow/${workflow.id}/aprobar`}>
                                                    <Button size="sm" className="bg-green-600 hover:bg-green-700">
                                                        <CheckCircle className="w-4 h-4 mr-1" />
                                                        Revisar
                                                    </Button>
                                                </Link>
                                                <Link href={`/admin/workflow/${workflow.id}`}>
                                                    <Button variant="outline" size="sm">
                                                        Ver Detalles
                                                    </Button>
                                                </Link>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8">
                                    <CheckCircle className="w-16 h-16 mx-auto text-gray-300 mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">
                                        No hay aprobaciones pendientes
                                    </h3>
                                    <p className="text-gray-600">
                                        Excelente! No tienes documentos pendientes de aprobar.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Workflows Solicitados */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="w-5 h-5 text-blue-600" />
                                Mis Solicitudes
                            </CardTitle>
                            <CardDescription>
                                Workflows que has solicitado
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {workflowsSolicitados.length > 0 ? (
                                <div className="space-y-4">
                                    {workflowsSolicitados.map((workflow) => (
                                        <div key={workflow.id} className="p-4 border rounded-lg hover:bg-gray-50">
                                            <div className="flex items-start justify-between mb-3">
                                                <div>
                                                    <Link href={`/admin/workflow/${workflow.id}`}>
                                                        <h4 className="font-medium text-blue-600 hover:underline">
                                                            {workflow.documento.nombre}
                                                        </h4>
                                                    </Link>
                                                    <p className="text-sm text-gray-600">{workflow.documento.codigo}</p>
                                                </div>
                                                <div className="text-right">
                                                    {getEstadoBadge(workflow.estado)}
                                                    {workflow.esta_vencido && (
                                                        <div className="mt-1">
                                                            <Badge variant="destructive" className="text-xs">
                                                                <AlertTriangle className="w-3 h-3 mr-1" />
                                                                Vencido
                                                            </Badge>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="mb-3">
                                                <div className="flex justify-between items-center mb-1">
                                                    <span className="text-sm font-medium">Progreso</span>
                                                    <span className="text-sm text-gray-600">{workflow.progreso}%</span>
                                                </div>
                                                <Progress value={workflow.progreso} className="h-2" />
                                            </div>

                                            <div className="grid grid-cols-2 gap-4 text-sm">
                                                <div>
                                                    <div className="flex items-center gap-1 text-gray-600">
                                                        <Users className="w-4 h-4" />
                                                        Revisor Actual
                                                    </div>
                                                    <div className="font-medium">{workflow.revisor_actual || 'Completado'}</div>
                                                </div>
                                                <div>
                                                    <div className="flex items-center gap-1 text-gray-600">
                                                        <Calendar className="w-4 h-4" />
                                                        Solicitud
                                                    </div>
                                                    <div className="font-medium">{formatearFecha(workflow.fecha_solicitud)}</div>
                                                </div>
                                            </div>

                                            <div className="mt-3">
                                                <Link href={`/admin/workflow/${workflow.id}`}>
                                                    <Button variant="outline" size="sm">
                                                        Ver Estado
                                                    </Button>
                                                </Link>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8">
                                    <FileText className="w-16 h-16 mx-auto text-gray-300 mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">
                                        No has solicitado workflows
                                    </h3>
                                    <p className="text-gray-600 mb-6">
                                        Inicia tu primer proceso de aprobación de documentos.
                                    </p>
                                    <Link href="/admin/workflow/create">
                                        <Button>
                                            <Plus className="w-4 h-4 mr-2" />
                                            Nuevo Workflow
                                        </Button>
                                    </Link>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
