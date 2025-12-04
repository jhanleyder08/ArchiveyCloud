import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { 
    GitBranch, 
    Clock, 
    CheckCircle, 
    FileText,
    TrendingUp,
    Users,
    Calendar,
    Plus
} from 'lucide-react';

const breadcrumbItems = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Workflow de Aprobaciones', href: '/admin/workflow' },
];

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
            'en_proceso': <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">En Proceso</span>,
            'pendiente': <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pendiente</span>,
            'completado': <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Completado</span>,
            'cancelado': <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Cancelado</span>,
            'pausado': <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Pausado</span>,
        };
        return badges[estado] || <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{estado}</span>;
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
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title="Workflow de Aprobaciones" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-2">
                        <GitBranch className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Workflow de Aprobaciones
                        </h1>
                    </div>
                    <Link href="/admin/workflow/create">
                        <Button className="flex items-center gap-2 px-4 py-2 bg-[#2a3d83] text-white rounded-lg hover:bg-[#1e2b5f] transition-colors">
                            <Plus className="h-4 w-4" />
                            Nuevo Workflow
                        </Button>
                    </Link>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Tareas Pendientes</p>
                                <p className="text-2xl font-semibold text-gray-900">{stats.tareas_pendientes}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <Clock className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Instancias Activas</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{stats.instancias_activas}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <TrendingUp className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Completadas este Mes</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{stats.completadas_mes}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <CheckCircle className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Promedio Duración</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{stats.promedio_duracion}h</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <Clock className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Tareas Pendientes */}
                    <div className="bg-white rounded-lg border overflow-hidden">
                        <div className="border-b bg-gray-50 px-6 py-4">
                            <h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <Clock className="w-5 h-5 text-[#2a3d83]" />
                                Tareas Pendientes
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Tareas que requieren tu atención
                            </p>
                        </div>
                        <div className="p-6">
                            {tareas_pendientes && tareas_pendientes.length > 0 ? (
                                <div className="space-y-4">
                                    {tareas_pendientes.map((tarea) => (
                                        <div key={tarea.id} className="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                            <div className="flex items-start justify-between mb-3">
                                                <div>
                                                    <h4 className="font-medium text-gray-900">
                                                        {tarea.nombre}
                                                    </h4>
                                                    {tarea.descripcion && (
                                                        <p className="text-sm text-gray-600 mt-1">{tarea.descripcion}</p>
                                                    )}
                                                </div>
                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    {tarea.estado}
                                                </span>
                                            </div>

                                            {tarea.fecha_vencimiento && (
                                                <div className="text-sm text-gray-600 mb-2">
                                                    <Calendar className="w-4 h-4 inline mr-1" />
                                                    Vence: {formatearFecha(tarea.fecha_vencimiento)}
                                                </div>
                                            )}

                                            <div className="mt-3 flex gap-2">
                                                <Link href={`/admin/workflow/${tarea.instancia_id}`}>
                                                    <Button variant="outline" size="sm" className="text-[#2a3d83] hover:text-[#1e2b5f]">
                                                        Ver Detalles
                                                    </Button>
                                                </Link>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8 text-gray-500">
                                    <CheckCircle className="w-12 h-12 mx-auto mb-4 text-[#2a3d83]" />
                                    <p>No hay tareas pendientes</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Instancias de Workflow */}
                    <div className="bg-white rounded-lg border overflow-hidden">
                        <div className="border-b bg-gray-50 px-6 py-4">
                            <h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <FileText className="w-5 h-5 text-[#2a3d83]" />
                                Mis Instancias
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Workflows que has iniciado
                            </p>
                        </div>
                        <div className="p-6">
                            {instancias_usuario && instancias_usuario.length > 0 ? (
                                <div className="space-y-4">
                                    {instancias_usuario.map((instancia) => (
                                        <div key={instancia.id} className="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                            <div className="flex items-start justify-between mb-3">
                                                <div>
                                                    <Link href={`/admin/workflow/${instancia.id}`}>
                                                        <h4 className="font-medium text-[#2a3d83] hover:text-[#1e2b5f] hover:underline">
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
                                                    <Button variant="outline" size="sm" className="text-[#2a3d83] hover:text-[#1e2b5f]">
                                                        Ver Estado
                                                    </Button>
                                                </Link>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8 text-gray-500">
                                    <FileText className="w-12 h-12 mx-auto mb-4 text-[#2a3d83]" />
                                    <p>No has iniciado workflows</p>
                                    <Link href="/admin/workflow/create" className="mt-4 inline-block">
                                        <Button className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                                            <Plus className="w-4 h-4 mr-2" />
                                            Nuevo Workflow
                                        </Button>
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
