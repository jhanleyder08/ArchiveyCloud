import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from 'sonner';
import { 
    Search, 
    Eye, 
    Edit, 
    Trash2, 
    Download, 
    Filter,
    Plus,
    FolderOpen,
    Calendar,
    User,
    Archive,
    AlertTriangle,
    CheckCircle,
    MoreHorizontal,
    ChevronDown,
    FileText,
    Shield
} from 'lucide-react';

const breadcrumbItems = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Expedientes', href: '/admin/expedientes' },
];

interface Expediente {
    id: number;
    codigo: string;
    nombre: string;
    descripcion?: string;
    estado: string;
    tipo_expediente: string;
    confidencialidad: string;
    fecha_apertura: string;
    fecha_cierre?: string;
    fecha_vencimiento_disposicion?: string;
    volumen_actual: number;
    volumen_maximo: number;
    numero_folios: number;
    area_responsable: string;
    usuario_responsable?: {
        name: string;
        email: string;
    };
    serie?: {
        codigo: string;
        nombre: string;
    };
    subserie?: {
        codigo: string;
        nombre: string;
    };
    created_at: string;
    updated_at: string;
}

interface PaginatedExpedientes {
    data: Expediente[];
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

interface Estadisticas {
    total: number;
    abiertos: number;
    cerrados: number;
    electronicos: number;
    fisicos: number;
    hibridos: number;
    proximos_vencer: number;
    vencidos: number;
}

interface Opciones {
    estados: Array<{ value: string; label: string; }>;
    tipos: Array<{ value: string; label: string; }>;
    proximidad_vencimiento: Array<{ value: string; label: string; }>;
    series_disponibles: Array<{ id: number; codigo: string; nombre: string; }>;
    areas_disponibles: Array<{ value: string; label: string; }>;
}

interface Props {
    expedientes: PaginatedExpedientes;
    estadisticas: Estadisticas;
    opciones: Opciones;
    filtros?: {
        search?: string;
        estado?: string;
        tipo_expediente?: string;
        serie_id?: string;
        area_responsable?: string;
        proximidad_vencimiento?: string;
    };
    flash?: {
        success?: string;
        error?: string;
    };
}

export default function Index({ expedientes, estadisticas, opciones, filtros }: Props) {
    const { flash: pageFlash } = usePage<{flash: {success?: string, error?: string}}>().props;
    const [searchQuery, setSearchQuery] = useState(filtros?.search || '');
    const [showFilters, setShowFilters] = useState(false);
    
    const [currentFilters, setCurrentFilters] = useState({
        search: filtros?.search || '',
        estado: filtros?.estado || 'all',
        tipo_expediente: filtros?.tipo_expediente || 'all',
        serie_id: filtros?.serie_id || 'all',
        area_responsable: filtros?.area_responsable || 'all',
        proximidad_vencimiento: filtros?.proximidad_vencimiento || 'all',
    });

    // Aplicar filtros
    const aplicarFiltros = () => {
        const params = new URLSearchParams();
        
        Object.entries(currentFilters).forEach(([key, value]) => {
            if (value && value !== 'all') {
                params.append(key, value);
            }
        });

        router.get('/admin/expedientes', Object.fromEntries(params));
    };

    // Limpiar filtros
    const limpiarFiltros = () => {
        setCurrentFilters({
            search: '',
            estado: 'all',
            tipo_expediente: 'all',
            serie_id: 'all',
            area_responsable: 'all',
            proximidad_vencimiento: 'all',
        });
        router.get('/admin/expedientes');
    };

    // Obtener badge de estado
    const getEstadoBadge = (estado: string) => {
        const badges = {
            'abierto': <Badge variant="default" className="bg-green-100 text-green-800">Abierto</Badge>,
            'cerrado': <Badge variant="secondary">Cerrado</Badge>,
            'transferido': <Badge variant="default" className="bg-blue-100 text-blue-800">Transferido</Badge>,
            'archivado': <Badge variant="default" className="bg-purple-100 text-purple-800">Archivado</Badge>,
            'en_disposicion': <Badge variant="destructive">En Disposición</Badge>,
        };
        return badges[estado] || <Badge variant="outline">{estado}</Badge>;
    };

    // Obtener badge de tipo
    const getTipoBadge = (tipo: string) => {
        const badges = {
            'electronico': <Badge variant="default" className="bg-indigo-100 text-indigo-800">Electrónico</Badge>,
            'fisico': <Badge variant="default" className="bg-gray-100 text-gray-800">Físico</Badge>,
            'hibrido': <Badge variant="default" className="bg-yellow-100 text-yellow-800">Híbrido</Badge>,
        };
        return badges[tipo] || <Badge variant="outline">{tipo}</Badge>;
    };

    // Obtener badge de confidencialidad
    const getConfidencialidadBadge = (confidencialidad: string) => {
        const badges = {
            'publica': <Badge variant="outline" className="text-green-600 border-green-600">Pública</Badge>,
            'interna': <Badge variant="outline" className="text-blue-600 border-blue-600">Interna</Badge>,
            'confidencial': <Badge variant="outline" className="text-orange-600 border-orange-600">Confidencial</Badge>,
            'reservada': <Badge variant="outline" className="text-red-600 border-red-600">Reservada</Badge>,
            'clasificada': <Badge variant="destructive">Clasificada</Badge>,
        };
        return badges[confidencialidad] || <Badge variant="outline">{confidencialidad}</Badge>;
    };

    // Formatear fecha
    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleDateString('es-CO', {
            year: 'numeric',
            month: 'short',
            day: '2-digit'
        });
    };

    // Formatear tamaño
    const formatearTamaño = (bytes: number) => {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    // Calcular porcentaje de ocupación
    const calcularPorcentajeOcupacion = (actual: number, maximo: number) => {
        return Math.round((actual / maximo) * 100);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title="Gestión de Expedientes" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-2">
                        <FolderOpen className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Gestión de Expedientes
                        </h1>
                    </div>
                    <Link href="/admin/expedientes/create">
                        <Button className="flex items-center gap-2 px-4 py-2 bg-[#2a3d83] text-white rounded-lg hover:bg-[#1e2b5f] transition-colors">
                            <Plus className="h-4 w-4" />
                            Crear Expediente
                        </Button>
                    </Link>
                </div>

                {/* Estadísticas */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total Expedientes</p>
                                <p className="text-2xl font-semibold text-gray-900">{estadisticas.total}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <Archive className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Abiertos</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas.abiertos}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <CheckCircle className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Cerrados</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas.cerrados}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <Archive className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Próx. Vencer</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas.proximos_vencer}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <Calendar className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filtros */}
                <div className="bg-white rounded-lg border p-6">
                    <div className="flex flex-col sm:flex-row gap-4 items-center justify-between">
                        <div className="flex items-center gap-4 w-full sm:w-auto">
                            <div className="relative flex-1 sm:w-80">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    placeholder="Buscar expedientes..."
                                    value={currentFilters.search}
                                    onChange={(e) => setCurrentFilters(prev => ({ ...prev, search: e.target.value }))}
                                    className="pl-10"
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Select 
                                value={currentFilters.estado} 
                                onValueChange={(value) => setCurrentFilters(prev => ({ ...prev, estado: value }))}
                            >
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    {opciones.estados.map((estado) => (
                                        <SelectItem key={estado.value} value={estado.value}>
                                            {estado.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button onClick={aplicarFiltros} className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                                <Search className="h-4 w-4 mr-2" />
                                Buscar
                            </Button>
                            <Button variant="outline" onClick={limpiarFiltros}>
                                Limpiar
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Tabla de Expedientes */}
                <div className="bg-white rounded-lg border overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b">
                                <tr>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">
                                        Expediente
                                    </th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">
                                        Serie/Subserie
                                    </th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">
                                        Estado/Tipo
                                    </th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">
                                        Responsable
                                    </th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">
                                        Volumen/Folios
                                    </th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">
                                        Fechas
                                    </th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {expedientes.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="py-8 px-6 text-center text-gray-500">
                                            No se encontraron expedientes.
                                        </td>
                                    </tr>
                                ) : (
                                    expedientes.data.map((expediente) => (
                                        <tr key={expediente.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="py-4 px-6">
                                                <div className="space-y-1">
                                                    <div className="flex items-center gap-2">
                                                        <p className="font-medium text-gray-900">
                                                            {expediente.codigo}
                                                        </p>
                                                        {getConfidencialidadBadge(expediente.confidencialidad)}
                                                    </div>
                                                    <p className="text-sm text-gray-600">
                                                        {expediente.nombre}
                                                    </p>
                                                    {expediente.descripcion && (
                                                        <p className="text-xs text-gray-500 line-clamp-2">
                                                            {expediente.descripcion}
                                                        </p>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="py-4 px-6">
                                                <div className="space-y-1">
                                                    {expediente.serie && (
                                                        <p className="text-sm font-medium text-gray-900">
                                                            {expediente.serie.codigo} - {expediente.serie.nombre}
                                                        </p>
                                                    )}
                                                    {expediente.subserie && (
                                                        <p className="text-xs text-gray-600">
                                                            {expediente.subserie.codigo} - {expediente.subserie.nombre}
                                                        </p>
                                                    )}
                                                    <p className="text-xs text-gray-500">
                                                        {expediente.area_responsable}
                                                    </p>
                                                </div>
                                            </td>
                                            <td className="py-4 px-6">
                                                <div className="space-y-2">
                                                    {getEstadoBadge(expediente.estado)}
                                                    {getTipoBadge(expediente.tipo_expediente)}
                                                </div>
                                            </td>
                                            <td className="py-4 px-6">
                                                <div className="space-y-1">
                                                    {expediente.usuario_responsable && (
                                                        <>
                                                            <p className="text-sm font-medium text-gray-900">
                                                                {expediente.usuario_responsable.name}
                                                            </p>
                                                            <p className="text-xs text-gray-500">
                                                                {expediente.usuario_responsable.email}
                                                            </p>
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="py-4 px-6">
                                                <div className="space-y-1">
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-16 bg-gray-200 rounded-full h-2">
                                                            <div 
                                                                className="bg-blue-600 h-2 rounded-full"
                                                                style={{ 
                                                                    width: `${calcularPorcentajeOcupacion(expediente.volumen_actual, expediente.volumen_maximo)}%` 
                                                                }}
                                                            />
                                                        </div>
                                                        <span className="text-xs text-gray-500">
                                                            {calcularPorcentajeOcupacion(expediente.volumen_actual, expediente.volumen_maximo)}%
                                                        </span>
                                                    </div>
                                                    <p className="text-xs text-gray-600">
                                                        {formatearTamaño(expediente.volumen_actual)} / {formatearTamaño(expediente.volumen_maximo)}
                                                    </p>
                                                    <p className="text-xs text-gray-500">
                                                        {expediente.numero_folios} folios
                                                    </p>
                                                </div>
                                            </td>
                                            <td className="py-4 px-6">
                                                <div className="space-y-1">
                                                    <p className="text-xs text-gray-600">
                                                        <strong>Apertura:</strong><br />
                                                        {formatearFecha(expediente.fecha_apertura)}
                                                    </p>
                                                    {expediente.fecha_cierre && (
                                                        <p className="text-xs text-gray-600">
                                                            <strong>Cierre:</strong><br />
                                                            {formatearFecha(expediente.fecha_cierre)}
                                                        </p>
                                                    )}
                                                    {expediente.fecha_vencimiento_disposicion && (
                                                        <p className="text-xs text-orange-600">
                                                            <strong>Vence:</strong><br />
                                                            {formatearFecha(expediente.fecha_vencimiento_disposicion)}
                                                        </p>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="py-4 px-6">
                                                <div className="flex items-center gap-2">
                                                    <Link href={`/admin/expedientes/${expediente.id}`}>
                                                        <button className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors">
                                                            <Eye className="h-4 w-4" />
                                                        </button>
                                                    </Link>
                                                    <Link href={`/admin/expedientes/${expediente.id}/edit`}>
                                                        <button className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors">
                                                            <Edit className="h-4 w-4" />
                                                        </button>
                                                    </Link>
                                                    <button 
                                                        className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors"
                                                        onClick={() => {
                                                            window.open(`/admin/expedientes/${expediente.id}/exportar-directorio?formato=json`, '_blank');
                                                        }}
                                                    >
                                                        <Download className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Paginación */}
                    {expedientes.data.length > 0 && (
                        <div className="flex items-center justify-between bg-white border rounded-lg px-6 py-3">
                            <div className="text-sm text-gray-600">
                                Mostrando <span className="font-medium">{expedientes.from || 0}</span> a{' '}
                                <span className="font-medium">{expedientes.to || 0}</span> de{' '}
                                <span className="font-medium">{expedientes.total || 0}</span> expedientes
                            </div>
                            <div className="flex items-center gap-2">
                                {expedientes.links.map((link, index) => {
                                    if (link.label.includes('Previous')) {
                                        return (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                preserveState
                                                className={`px-3 py-2 border border-gray-300 rounded-md text-sm font-medium ${
                                                    link.url 
                                                        ? 'text-gray-700 hover:bg-gray-50' 
                                                        : 'text-gray-300 cursor-not-allowed'
                                                }`}
                                            >
                                                Anterior
                                            </Link>
                                        );
                                    }
                                    
                                    if (link.label.includes('Next')) {
                                        return (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                preserveState
                                                className={`px-3 py-2 border border-gray-300 rounded-md text-sm font-medium ${
                                                    link.url 
                                                        ? 'text-gray-700 hover:bg-gray-50' 
                                                        : 'text-gray-300 cursor-not-allowed'
                                                }`}
                                            >
                                                Siguiente
                                            </Link>
                                        );
                                    }

                                    // Number pages
                                    if (!isNaN(Number(link.label))) {
                                        return (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                preserveState
                                                className={`px-3 py-2 rounded-md text-sm font-medium ${
                                                    link.active
                                                        ? 'bg-[#2a3d83] text-white'
                                                        : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                                                }`}
                                            >
                                                {link.label}
                                            </Link>
                                        );
                                    }

                                    return null;
                                })}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
