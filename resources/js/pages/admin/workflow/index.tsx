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

interface TareaPendiente {
    id: number;
    nombre: string;
    descripcion: string;
    estado: string;
    fecha_vencimiento?: string;
    instancia_id: number;
    entidad?: {
        tipo: string;
        id: number;
    };
}

interface InstanciaUsuario {
    id: number;
    codigo_seguimiento: number;
    workflow_nombre: string;
    entidad_tipo: string;
    entidad_id: number;
    estado: string;
    progreso: number;
    fecha_inicio?: string;
    fecha_limite?: string;
    tarea_actual?: {
        nombre: string;
        asignado_a: string;
    };
}

interface Estadisticas {
    tareas_pendientes: number;
    instancias_activas: number;
    completadas_mes: number;
    promedio_duracion: number;
}

interface Props {
    tareas_pendientes: TareaPendiente[];
    instancias_usuario: InstanciaUsuario[];
    estadisticas: Estadisticas;
    workflows_disponibles: Array<{
        id: number;
        nombre: string;
        descripcion: string;
        tipo_entidad: string;
    }>;
}

export default function WorkflowIndex({ tareas_pendientes = [], instancias_usuario = [], estadisticas, workflows_disponibles = [] }: Props) {
    const getEstadoBadge = (estado: string) => {
        const badges: Record<string, JSX.Element> = {
            'en_proceso': <Badge className="bg-blue-100 text-blue-800">En Proceso</Badge>,
            'pendiente': <Badge variant="outline" className="text-yellow-600">Pendiente</Badge>,
            'completado': <Badge className="bg-green-100 text-green-800">Completado</Badge>,
            'cancelado': <Badge variant="destructive">Cancelado</Badge>,
            'pausado': <Badge variant="outline" className="text-gray-600">Pausado</Badge>,
        };
        return badges[estado] || <Badge variant="outline">{estado}</Badge>;
    };

    const formatearFecha = (fecha: string) => {
        if (!fecha) return 'N/A';
        try {
            return new Date(fecha).toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        } catch {
            return fecha;
        }
    };

    // Valores por defecto para estadísticas
    const stats = estadisticas || {
        tareas_pendientes: 0,
        instancias_activas: 0,
        completadas_mes: 0,
        promedio_duracion: 0
    };

    return (
        <AppLayout>
            <Head title="Workflow de Aprobaciones" />
            
            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-2">
                        <GitBranch className="h-6 w-6 text-[#2a3d83]" />
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900">
                                Workflow de Aprobaciones
                            </h1>
                            <p className="text-sm text-gray-600 mt-1">
                                Gestiona los procesos de aprobación de documentos
                            </p>
                        </div>
                    </div>
                    <Link href="/admin/workflow/create">
                        <Button className="bg-[#2a3d83] hover:bg-[#1e2b5f] flex items-center gap-2">
                            <Plus className="h-4 w-4" />
                            Nuevo Workflow
                        </Button>
                    </Link>
                </div>

                {/* Estadísticas */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card className="border border-gray-200 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">Tareas Pendientes</CardTitle>
                            <Clock className="h-4 w-4 text-[#2a3d83]" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-gray-900">{stats.tareas_pendientes}</div>
                            <p className="text-xs text-gray-500 mt-1">Requieren tu atención</p>
                        </CardContent>
                    </Card>

                    <Card className="border border-gray-200 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">Instancias Activas</CardTitle>
                            <TrendingUp className="h-4 w-4 text-[#2a3d83]" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-gray-900">{stats.instancias_activas}</div>
                            <p className="text-xs text-gray-500 mt-1">En proceso</p>
                        </CardContent>
                    </Card>

                    <Card className="border border-gray-200 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">Completadas este Mes</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-gray-900">{stats.completadas_mes}</div>
                            <p className="text-xs text-gray-500 mt-1">Finalizadas exitosamente</p>
                        </CardContent>
                    </Card>

                    <Card className="border border-gray-200 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">Promedio Duración</CardTitle>
                            <Clock className="h-4 w-4 text-[#2a3d83]" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-gray-900">{stats.promedio_duracion}h</div>
                            <p className="text-xs text-gray-500 mt-1">Tiempo promedio</p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Tareas Pendientes */}
                    <Card className="border border-gray-200 shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <Clock className="w-5 h-5 text-orange-600" />
                                Tareas Pendientes
                            </CardTitle>
                            <CardDescription className="text-gray-600">
                                Tareas que requieren tu atención
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {tareas_pendientes && tareas_pendientes.length > 0 ? (
                                <div className="space-y-4">
                                    {tareas_pendientes.map((tarea) => (
                                        <div key={tarea.id} className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                            <div className="flex items-start justify-between mb-3">
                                                <div>
                                                    <h4 className="font-medium text-gray-900">
                                                        {tarea.nombre}
                                                    </h4>
                                                    {tarea.descripcion && (
                                                        <p className="text-sm text-gray-600 mt-1">{tarea.descripcion}</p>
                                                    )}
                                                </div>
                                                <Badge variant="outline" className="text-yellow-600">
                                                    {tarea.estado}
                                                </Badge>
                                            </div>

                                            {tarea.fecha_vencimiento && (
                                                <div className="text-sm text-gray-600 mb-2">
                                                    <Calendar className="w-4 h-4 inline mr-1" />
                                                    Vence: {formatearFecha(tarea.fecha_vencimiento)}
                                                </div>
                                            )}

                                            <div className="mt-3 flex gap-2">
                                                <Link href={`/admin/workflow/${tarea.instancia_id}`}>
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
                                        No hay tareas pendientes
                                    </h3>
                                    <p className="text-gray-600">
                                        Excelente! No tienes tareas pendientes.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Instancias de Workflow */}
                    <Card className="border border-gray-200 shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <FileText className="w-5 h-5 text-blue-600" />
                                Mis Instancias
                            </CardTitle>
                            <CardDescription className="text-gray-600">
                                Workflows que has iniciado
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {instancias_usuario && instancias_usuario.length > 0 ? (
                                <div className="space-y-4">
                                    {instancias_usuario.map((instancia) => (
                                        <div key={instancia.id} className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                            <div className="flex items-start justify-between mb-3">
                                                <div>
                                                    <Link href={`/admin/workflow/${instancia.id}`}>
                                                        <h4 className="font-medium text-blue-600 hover:underline">
                                                            {instancia.workflow_nombre}
                                                        </h4>
                                                    </Link>
                                                    <p className="text-sm text-gray-600">{instancia.entidad_tipo} #{instancia.entidad_id}</p>
                                                </div>
                                                {getEstadoBadge(instancia.estado)}
                                            </div>

                                            <div className="mb-3">
                                                <div className="flex justify-between items-center mb-1">
                                                    <span className="text-sm font-medium text-gray-900">Progreso</span>
                                                    <span className="text-sm text-gray-600">{instancia.progreso}%</span>
                                                </div>
                                                <Progress value={instancia.progreso} className="h-2" />
                                            </div>

                                            {instancia.tarea_actual && (
                                                <div className="text-sm text-gray-600 mb-2">
                                                    <Users className="w-4 h-4 inline mr-1" />
                                                    Tarea actual: {instancia.tarea_actual.nombre} - {instancia.tarea_actual.asignado_a}
                                                </div>
                                            )}

                                            {instancia.fecha_inicio && (
                                                <div className="text-sm text-gray-600 mb-2">
                                                    <Calendar className="w-4 h-4 inline mr-1" />
                                                    Iniciado: {formatearFecha(instancia.fecha_inicio)}
                                                </div>
                                            )}

                                            <div className="mt-3">
                                                <Link href={`/admin/workflow/${instancia.id}`}>
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
                                        No has iniciado workflows
                                    </h3>
                                    <p className="text-gray-600 mb-6">
                                        Inicia tu primer proceso de workflow.
                                    </p>
                                    <Link href="/admin/workflow/create">
                                        <Button className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
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
