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
    FileText, Calendar, Bell, TrendingUp, Filter, RefreshCw, Plus
} from 'lucide-react';

const breadcrumbItems = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Retención y Disposición', href: '/admin/retencion-disposicion' },
];

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
    const [estadoFilter, setEstadoFilter] = useState(filtros.estado || 'all');
    const [tipoEntidadFilter, setTipoEntidadFilter] = useState(filtros.tipo_entidad || 'all');
    const [prioridadFilter, setPrioridadFilter] = useState(filtros.prioridad || 'all');
    const [trdFilter, setTrdFilter] = useState(filtros.trd_id || 'all');
    const [procesando, setProcesando] = useState(false);
    const [showFilters, setShowFilters] = useState(false);

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
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title="Retención y Disposición" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-2">
                        <Clock className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Retención y Disposición
                        </h1>
                    </div>
                    <Button 
                        onClick={procesarActualizacionesMasivas}
                        className="flex items-center gap-2 px-4 py-2 bg-[#2a3d83] text-white rounded-lg hover:bg-[#1e2b5f] transition-colors"
                        disabled={procesando}
                    >
                        <RefreshCw className={`h-4 w-4 ${procesando ? 'animate-spin' : ''}`} />
                        {procesando ? 'Procesando...' : 'Actualizar Procesos'}
                    </Button>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total Procesos</p>
                                <p className="text-2xl font-semibold text-gray-900">{stats.total}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <FileText className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Procesos Activos</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{stats.activos}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <div className="h-6 w-6 bg-[#2a3d83] rounded-full flex items-center justify-center">
                                    <div className="h-2 w-2 bg-white rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Vencidos</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{stats.vencidos}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <div className="h-6 w-6 bg-[#2a3d83] rounded-full flex items-center justify-center">
                                    <div className="h-2 w-2 bg-white rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Search and Filters */}
                <div className="bg-white rounded-lg border p-6">
                    <div className="flex flex-col sm:flex-row gap-4 items-center justify-between">
                        <div className="flex items-center gap-4 w-full sm:w-auto">
                            <div className="relative flex-1 sm:w-80">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleFilterChange('search', searchQuery)}
                                    placeholder="Buscar procesos..."
                                    className="pl-10"
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Select value={estadoFilter} onValueChange={(value) => handleFilterChange('estado', value)}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Filtro por estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="activo">Activo</SelectItem>
                                    <SelectItem value="alerta_previa">Alerta Previa</SelectItem>
                                    <SelectItem value="vencido">Vencido</SelectItem>
                                    <SelectItem value="en_disposicion">En Disposición</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button
                                variant="outline"
                                size="icon"
                                onClick={() => setShowFilters(!showFilters)}
                            >
                                <Filter className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    {showFilters && (
                        <div className="mt-4 pt-4 border-t">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <Select value={tipoEntidadFilter} onValueChange={(value) => handleFilterChange('tipo_entidad', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Tipo de Entidad" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los tipos</SelectItem>
                                        <SelectItem value="documento">Documento</SelectItem>
                                        <SelectItem value="expediente">Expediente</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select value={prioridadFilter} onValueChange={(value) => handleFilterChange('prioridad', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Prioridad" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todas las prioridades</SelectItem>
                                        <SelectItem value="critico">Crítico</SelectItem>
                                        <SelectItem value="alto">Alto</SelectItem>
                                        <SelectItem value="medio">Medio</SelectItem>
                                        <SelectItem value="bajo">Bajo</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select value={trdFilter} onValueChange={(value) => handleFilterChange('trd_id', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="TRD" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todas las TRD</SelectItem>
                                        {trdOptions.map(trd => (
                                            <SelectItem key={trd.id} value={trd.id.toString()}>
                                                {trd.codigo} - {trd.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    )}
                </div>

                {/* Table */}
                <div className="bg-white rounded-lg border overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Código / Entidad
                                    </th>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        TRD / Serie
                                    </th>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Estado
                                    </th>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Vencimiento
                                    </th>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Días Restantes
                                    </th>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Indicadores
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {procesos.data.map(proceso => (
                                    <tr key={proceso.id} className="border-b hover:bg-gray-50/50">
                                        <td className="p-4">
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
                                        <td className="p-4">
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
                                        <td className="p-4">
                                            <Badge className={getEstadoBadgeColor(proceso.estado)}>
                                                <span className="flex items-center gap-1">
                                                    {getEstadoIcon(proceso.estado)}
                                                    {formatEstado(proceso.estado)}
                                                </span>
                                            </Badge>
                                        </td>
                                        <td className="p-4">
                                            <div>
                                                <div className="text-sm text-gray-900">
                                                    {new Date(proceso.fecha_vencimiento_gestion).toLocaleDateString('es-ES')}
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    Archivo Gestión
                                                </div>
                                            </div>
                                        </td>
                                        <td className="p-4">
                                            <span className={getDiasVencimientoColor(proceso.dias_hasta_vencimiento)}>
                                                {proceso.dias_hasta_vencimiento > 0 
                                                    ? `${proceso.dias_hasta_vencimiento} días`
                                                    : proceso.dias_hasta_vencimiento === 0 
                                                    ? 'Vence hoy'
                                                    : `Vencido hace ${Math.abs(proceso.dias_hasta_vencimiento)} días`
                                                }
                                            </span>
                                        </td>
                                        <td className="p-4">
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

                    {/* Pagination */}
                    {procesos.last_page > 1 && (
                        <div className="mt-6 flex items-center justify-between px-6 pb-6">
                            <p className="text-sm text-gray-600">
                                Mostrando <span className="font-medium">{procesos.from || 0}</span> a{' '}
                                <span className="font-medium">{procesos.to || 0}</span> de{' '}
                                <span className="font-medium">{procesos.total}</span> procesos
                            </p>
                            <div className="flex gap-2">
                                {procesos.links.map((link) => (
                                    <Button
                                        key={link.label}
                                        variant={link.active ? "default" : "outline"}
                                        size="sm"
                                        onClick={() => link.url && router.get(link.url)}
                                        disabled={!link.url}
                                        className={link.active ? "bg-[#2a3d83] hover:bg-[#1e2b5f]" : ""}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
