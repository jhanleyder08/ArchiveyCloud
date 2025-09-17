import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { toast } from 'sonner';
import { 
    Search, Eye, Clock, AlertTriangle, Archive, Send, Trash2, 
    PauseCircle, PlayCircle, Lock, Unlock, AlertCircle, CheckCircle,
    FileText, Calendar, Bell, TrendingUp, Filter, RefreshCw
} from 'lucide-react';

interface ProcesoRetencion {
    id: number;
    codigo_proceso: string;
    tipo_entidad: string;
    documento?: {
        id: number;
        codigo_documento: string;
        titulo: string;
        estado: string;
    };
    expediente?: {
        id: number;
        numero_expediente: string;
        titulo: string;
        estado: string;
    };
    trd: {
        id: number;
        codigo: string;
        nombre: string;
        version: number;
    };
    serie_documental?: {
        id: number;
        nombre: string;
        codigo: string;
    };
    fecha_creacion_documento: string;
    fecha_vencimiento_gestion: string;
    fecha_vencimiento_central: string;
    estado: string;
    accion_disposicion?: string;
    aplazado: boolean;
    alertas_activas: boolean;
    bloqueado_eliminacion: boolean;
    dias_hasta_vencimiento: number;
    es_critico: boolean;
    created_at: string;
    updated_at: string;
}

interface PaginatedProcesos {
    data: ProcesoRetencion[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{
        url?: string;
        label: string;
        active: boolean;
    }>;
}

interface Stats {
    total: number;
    activos: number;
    vencidos: number;
    en_alerta: number;
    transferidos: number;
    eliminados: number;
    conservados: number;
    criticos: number;
}

interface TRDOption {
    id: number;
    codigo: string;
    nombre: string;
}

interface Props {
    procesos: PaginatedProcesos;
    stats: Stats;
    filtros: {
        search?: string;
        estado?: string;
        tipo_entidad?: string;
        prioridad?: string;
        trd_id?: string;
    };
    trdOptions: TRDOption[];
    flash?: {
        success?: string;
        error?: string;
    };
}

export default function AdminRetencionDisposicionIndex({ procesos, stats, filtros, trdOptions, flash }: Props) {
    const [searchQuery, setSearchQuery] = useState(filtros.search || '');
    const [estadoFilter, setEstadoFilter] = useState(filtros.estado || '');
    const [tipoEntidadFilter, setTipoEntidadFilter] = useState(filtros.tipo_entidad || '');
    const [prioridadFilter, setPrioridadFilter] = useState(filtros.prioridad || '');
    const [trdFilter, setTrdFilter] = useState(filtros.trd_id || '');
    const [procesando, setProcesando] = useState(false);

    // Manejar filtros
    const handleFilterChange = (key: string, value: string) => {
        const params = {
            ...filtros,
            [key]: value || undefined,
            page: 1
        };
        router.get('/admin/retencion-disposicion', params, { preserveState: true });
    };

    // Funciones auxiliares para badges y colores
    const getEstadoBadgeColor = (estado: string) => {
        switch (estado) {
            case 'activo': return 'bg-green-100 text-green-800';
            case 'alerta_previa': return 'bg-yellow-100 text-yellow-800';
            case 'vencido': return 'bg-red-100 text-red-800';
            case 'en_disposicion': return 'bg-blue-100 text-blue-800';
            case 'transferido': return 'bg-purple-100 text-purple-800';
            case 'eliminado': return 'bg-gray-100 text-gray-800';
            case 'conservado': return 'bg-emerald-100 text-emerald-800';
            case 'aplazado': return 'bg-orange-100 text-orange-800';
            case 'suspendido': return 'bg-gray-100 text-gray-600';
            default: return 'bg-gray-100 text-gray-600';
        }
    };

    const getEstadoIcon = (estado: string) => {
        switch (estado) {
            case 'activo': return <CheckCircle className="h-4 w-4" />;
            case 'alerta_previa': return <AlertCircle className="h-4 w-4" />;
            case 'vencido': return <AlertTriangle className="h-4 w-4" />;
            case 'en_disposicion': return <Clock className="h-4 w-4" />;
            case 'transferido': return <Send className="h-4 w-4" />;
            case 'eliminado': return <Trash2 className="h-4 w-4" />;
            case 'conservado': return <Archive className="h-4 w-4" />;
            case 'aplazado': return <PauseCircle className="h-4 w-4" />;
            case 'suspendido': return <Lock className="h-4 w-4" />;
            default: return null;
        }
    };

    const formatEstado = (estado: string) => {
        const estados: Record<string, string> = {
            'activo': 'Activo',
            'alerta_previa': 'Alerta Previa',
            'vencido': 'Vencido',
            'en_disposicion': 'En Disposición',
            'transferido': 'Transferido',
            'eliminado': 'Eliminado',
            'conservado': 'Conservado',
            'aplazado': 'Aplazado',
            'suspendido': 'Suspendido'
        };
        return estados[estado] || estado;
    };

    const getDiasVencimientoColor = (dias: number) => {
        if (dias <= 0) return 'text-red-600 font-bold';
        if (dias <= 7) return 'text-orange-600 font-semibold';
        if (dias <= 30) return 'text-yellow-600';
        return 'text-gray-600';
    };

    // Procesar actualizaciones masivas
    const procesarActualizacionesMasivas = async () => {
        setProcesando(true);
        try {
            await router.post('/admin/retencion-disposicion/procesar-masivo', {}, {
                onSuccess: (page: any) => {
                    toast.success('Actualizaciones procesadas correctamente');
                },
                onError: (errors: any) => {
                    toast.error('Error al procesar actualizaciones');
                }
            });
        } finally {
            setProcesando(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Retención y Disposición" />

            <div className="p-6">
                {/* Header */}
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">Gestión de Retención y Disposición</h1>
                    <p className="mt-1 text-sm text-gray-600">
                        Control automatizado de períodos de retención y disposición documental
                    </p>
                </div>

                {/* Estadísticas */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-blue-100 rounded-full">
                                <FileText className="h-6 w-6 text-blue-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-500">Total Procesos</p>
                                <p className="text-2xl font-semibold text-gray-900">{stats.total}</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-red-100 rounded-full">
                                <AlertTriangle className="h-6 w-6 text-red-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-500">Críticos</p>
                                <p className="text-2xl font-semibold text-gray-900">{stats.criticos}</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-yellow-100 rounded-full">
                                <Bell className="h-6 w-6 text-yellow-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-500">En Alerta</p>
                                <p className="text-2xl font-semibold text-gray-900">{stats.en_alerta}</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-green-100 rounded-full">
                                <Archive className="h-6 w-6 text-green-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-500">Conservados</p>
                                <p className="text-2xl font-semibold text-gray-900">{stats.conservados}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filtros y acciones */}
                <div className="bg-white rounded-lg border p-4 mb-6">
                    <div className="flex flex-col lg:flex-row gap-4">
                        <div className="flex-1">
                            <Input
                                placeholder="Buscar por código, documento, expediente..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                onKeyPress={(e) => e.key === 'Enter' && handleFilterChange('search', searchQuery)}
                                className="w-full"
                                icon={<Search className="h-4 w-4 text-gray-400" />}
                            />
                        </div>
                        
                        <Select value={estadoFilter} onValueChange={(value) => handleFilterChange('estado', value)}>
                            <SelectTrigger className="w-full lg:w-[180px]">
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="activo">Activo</SelectItem>
                                <SelectItem value="alerta_previa">Alerta Previa</SelectItem>
                                <SelectItem value="vencido">Vencido</SelectItem>
                                <SelectItem value="en_disposicion">En Disposición</SelectItem>
                                <SelectItem value="transferido">Transferido</SelectItem>
                                <SelectItem value="eliminado">Eliminado</SelectItem>
                                <SelectItem value="conservado">Conservado</SelectItem>
                                <SelectItem value="aplazado">Aplazado</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select value={tipoEntidadFilter} onValueChange={(value) => handleFilterChange('tipo_entidad', value)}>
                            <SelectTrigger className="w-full lg:w-[180px]">
                                <SelectValue placeholder="Tipo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="documento">Documentos</SelectItem>
                                <SelectItem value="expediente">Expedientes</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select value={prioridadFilter} onValueChange={(value) => handleFilterChange('prioridad', value)}>
                            <SelectTrigger className="w-full lg:w-[180px]">
                                <SelectValue placeholder="Prioridad" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todas</SelectItem>
                                <SelectItem value="criticos">Críticos</SelectItem>
                            </SelectContent>
                        </Select>

                        <div className="flex gap-2">
                            <Link href="/admin/retencion-disposicion/alertas">
                                <Button variant="outline">
                                    <Bell className="h-4 w-4 mr-2" />
                                    Alertas
                                </Button>
                            </Link>
                            
                            <Link href="/admin/retencion-disposicion/reportes">
                                <Button variant="outline">
                                    <TrendingUp className="h-4 w-4 mr-2" />
                                    Reportes
                                </Button>
                            </Link>
                            
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Button 
                                            variant="outline"
                                            onClick={procesarActualizacionesMasivas}
                                            disabled={procesando}
                                        >
                                            <RefreshCw className={`h-4 w-4 ${procesando ? 'animate-spin' : ''}`} />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>Procesar actualizaciones masivas</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    </div>
                </div>

                {/* Tabla de procesos */}
                <div className="bg-white rounded-lg border overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Código / Entidad
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        TRD / Serie
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Vencimiento
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Días Restantes
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Indicadores
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {procesos.data.map((proceso) => (
                                    <tr key={proceso.id} className={proceso.es_critico ? 'bg-red-50' : ''}>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div className="text-sm font-medium text-gray-900">
                                                    {proceso.codigo_proceso}
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    {proceso.tipo_entidad === 'documento' ? (
                                                        <>
                                                            <FileText className="inline h-3 w-3 mr-1" />
                                                            {proceso.documento?.titulo || 'Sin título'}
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Archive className="inline h-3 w-3 mr-1" />
                                                            {proceso.expediente?.titulo || 'Sin título'}
                                                        </>
                                                    )}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div className="text-sm font-medium text-gray-900">
                                                    {proceso.trd.nombre}
                                                </div>
                                                {proceso.serie_documental && (
                                                    <div className="text-xs text-gray-500">
                                                        {proceso.serie_documental.nombre}
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <Badge className={getEstadoBadgeColor(proceso.estado)}>
                                                <span className="flex items-center gap-1">
                                                    {getEstadoIcon(proceso.estado)}
                                                    {formatEstado(proceso.estado)}
                                                </span>
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div className="text-sm text-gray-900">
                                                    {new Date(proceso.fecha_vencimiento_gestion).toLocaleDateString('es-ES')}
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    Archivo Gestión
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={getDiasVencimientoColor(proceso.dias_hasta_vencimiento)}>
                                                {proceso.dias_hasta_vencimiento > 0 
                                                    ? `${proceso.dias_hasta_vencimiento} días`
                                                    : proceso.dias_hasta_vencimiento === 0 
                                                    ? 'Vence hoy'
                                                    : `Vencido hace ${Math.abs(proceso.dias_hasta_vencimiento)} días`
                                                }
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex gap-1">
                                                {proceso.aplazado && (
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger>
                                                                <PauseCircle className="h-4 w-4 text-orange-500" />
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Aplazado</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                )}
                                                {proceso.bloqueado_eliminacion && (
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger>
                                                                <Lock className="h-4 w-4 text-red-500" />
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Eliminación bloqueada</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                )}
                                                {proceso.alertas_activas && (
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger>
                                                                <Bell className="h-4 w-4 text-yellow-500" />
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Alertas activas</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link href={`/admin/retencion-disposicion/${proceso.id}`}>
                                                <Button variant="ghost" size="sm">
                                                    <Eye className="h-4 w-4 mr-1" />
                                                    Ver detalle
                                                </Button>
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Paginación */}
                    {procesos.last_page > 1 && (
                        <div className="px-6 py-4 border-t border-gray-200">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-gray-700">
                                    Mostrando {procesos.from} a {procesos.to} de {procesos.total} resultados
                                </div>
                                <div className="flex gap-2">
                                    {procesos.links.map((link, index) => (
                                        link.url ? (
                                            <Button
                                                key={index}
                                                variant={link.active ? "default" : "outline"}
                                                size="sm"
                                                onClick={() => router.get(link.url!)}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ) : (
                                            <Button
                                                key={index}
                                                variant="outline"
                                                size="sm"
                                                disabled
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        )
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
