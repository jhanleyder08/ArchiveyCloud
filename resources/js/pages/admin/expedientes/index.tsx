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
    filtros: {
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
    const [searchQuery, setSearchQuery] = useState(filtros.search || '');
    const [showFilters, setShowFilters] = useState(false);
    
    const [currentFilters, setCurrentFilters] = useState({
        search: filtros.search || '',
        estado: filtros.estado || 'all',
        tipo_expediente: filtros.tipo_expediente || 'all',
        serie_id: filtros.serie_id || 'all',
        area_responsable: filtros.area_responsable || 'all',
        proximidad_vencimiento: filtros.proximidad_vencimiento || 'all',
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
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <FolderOpen className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Gestión de Expedientes
                        </h1>
                    </div>
                    <Link href="/admin/expedientes/create">
                        <Button className="bg-[#2a3d83] hover:bg-[#1e2b5f] flex items-center gap-2">
                            <Plus className="h-4 w-4" />
                            Crear Expediente
                        </Button>
                    </Link>
                </div>

                {/* Estadísticas */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-blue-100 rounded-lg">
                                    <Archive className="h-4 w-4 text-blue-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.total}</p>
                                    <p className="text-xs font-medium text-gray-500">Total</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-green-100 rounded-lg">
                                    <CheckCircle className="h-4 w-4 text-green-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.abiertos}</p>
                                    <p className="text-xs font-medium text-gray-500">Abiertos</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-gray-100 rounded-lg">
                                    <Archive className="h-4 w-4 text-gray-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.cerrados}</p>
                                    <p className="text-xs font-medium text-gray-500">Cerrados</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-indigo-100 rounded-lg">
                                    <FileText className="h-4 w-4 text-indigo-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.electronicos}</p>
                                    <p className="text-xs font-medium text-gray-500">Electrónicos</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-gray-100 rounded-lg">
                                    <Archive className="h-4 w-4 text-gray-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.fisicos}</p>
                                    <p className="text-xs font-medium text-gray-500">Físicos</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-yellow-100 rounded-lg">
                                    <Archive className="h-4 w-4 text-yellow-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.hibridos}</p>
                                    <p className="text-xs font-medium text-gray-500">Híbridos</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-orange-100 rounded-lg">
                                    <Calendar className="h-4 w-4 text-orange-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.proximos_vencer}</p>
                                    <p className="text-xs font-medium text-gray-500">Próx. Vencer</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-red-100 rounded-lg">
                                    <AlertTriangle className="h-4 w-4 text-red-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.vencidos}</p>
                                    <p className="text-xs font-medium text-gray-500">Vencidos</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filtros */}
                <Card>
                    <CardHeader className="pb-3">
                        <div className="flex items-center justify-between">
                            <CardTitle>Filtros de Búsqueda</CardTitle>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setShowFilters(!showFilters)}
                                className="flex items-center gap-2"
                            >
                                <Filter className="h-4 w-4" />
                                Filtros Avanzados
                                <ChevronDown className={`h-4 w-4 transition-transform ${showFilters ? 'rotate-180' : ''}`} />
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {/* Búsqueda básica */}
                        <div className="flex gap-4">
                            <div className="flex-1">
                                <Input
                                    placeholder="Buscar por código, nombre o descripción..."
                                    value={currentFilters.search}
                                    onChange={(e) => setCurrentFilters(prev => ({ ...prev, search: e.target.value }))}
                                    className="w-full"
                                />
                            </div>
                            <Select 
                                value={currentFilters.estado} 
                                onValueChange={(value) => setCurrentFilters(prev => ({ ...prev, estado: value }))}
                            >
                                <SelectTrigger className="w-[200px]">
                                    <SelectValue placeholder="Estado" />
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
                            <div className="flex gap-2">
                                <Button onClick={aplicarFiltros} className="flex items-center gap-2">
                                    <Search className="h-4 w-4" />
                                    Buscar
                                </Button>
                                <Button variant="outline" onClick={limpiarFiltros}>
                                    Limpiar
                                </Button>
                            </div>
                        </div>

                        {/* Filtros avanzados */}
                        {showFilters && (
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 pt-4 border-t">
                                <Select 
                                    value={currentFilters.tipo_expediente} 
                                    onValueChange={(value) => setCurrentFilters(prev => ({ ...prev, tipo_expediente: value }))}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los tipos</SelectItem>
                                        {opciones.tipos.map((tipo) => (
                                            <SelectItem key={tipo.value} value={tipo.value}>
                                                {tipo.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <Select 
                                    value={currentFilters.serie_id} 
                                    onValueChange={(value) => setCurrentFilters(prev => ({ ...prev, serie_id: value }))}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Serie" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todas las series</SelectItem>
                                        {opciones.series_disponibles.map((serie) => (
                                            <SelectItem key={serie.id} value={serie.id.toString()}>
                                                {serie.codigo} - {serie.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <Select 
                                    value={currentFilters.area_responsable} 
                                    onValueChange={(value) => setCurrentFilters(prev => ({ ...prev, area_responsable: value }))}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Área" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todas las áreas</SelectItem>
                                        {opciones.areas_disponibles.map((area) => (
                                            <SelectItem key={area.value} value={area.value}>
                                                {area.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <Select 
                                    value={currentFilters.proximidad_vencimiento} 
                                    onValueChange={(value) => setCurrentFilters(prev => ({ ...prev, proximidad_vencimiento: value }))}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Vencimiento" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Sin filtro de vencimiento</SelectItem>
                                        {opciones.proximidad_vencimiento.map((proximidad) => (
                                            <SelectItem key={proximidad.value} value={proximidad.value}>
                                                {proximidad.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Tabla de Expedientes */}
                <div className="bg-white rounded-lg border overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Expediente
                                    </th>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Serie/Subserie
                                    </th>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Estado/Tipo
                                    </th>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Responsable
                                    </th>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Volumen/Folios
                                    </th>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Fechas
                                    </th>
                                    <th className="text-right p-4 font-medium text-sm text-gray-900">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {expedientes.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="p-8 text-center text-gray-500">
                                            No hay expedientes disponibles
                                        </td>
                                    </tr>
                                ) : (
                                    expedientes.data.map((expediente) => (
                                        <tr key={expediente.id} className="border-t hover:bg-gray-50">
                                            <td className="p-4">
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
                                            <td className="p-4">
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
                                            <td className="p-4">
                                                <div className="space-y-2">
                                                    {getEstadoBadge(expediente.estado)}
                                                    {getTipoBadge(expediente.tipo_expediente)}
                                                </div>
                                            </td>
                                            <td className="p-4">
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
                                            <td className="p-4">
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
                                            <td className="p-4">
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
                                            <td className="p-4">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link href={`/admin/expedientes/${expediente.id}`}>
                                                        <Button variant="ghost" size="sm" title="Ver expediente">
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Link href={`/admin/expedientes/${expediente.id}/edit`}>
                                                        <Button variant="ghost" size="sm" title="Editar expediente">
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Button 
                                                        variant="ghost" 
                                                        size="sm" 
                                                        title="Exportar directorio"
                                                        onClick={() => {
                                                            window.open(`/admin/expedientes/${expediente.id}/exportar-directorio?formato=json`, '_blank');
                                                        }}
                                                    >
                                                        <Download className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Paginación */}
                    {expedientes.last_page > 1 && (
                        <div className="px-4 py-3 border-t bg-gray-50">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-gray-500">
                                    Mostrando {expedientes.from} a {expedientes.to} de {expedientes.total} expedientes
                                </div>
                                <div className="flex space-x-1">
                                    {expedientes.links.map((link, index) => (
                                        <Button
                                            key={index}
                                            variant={link.active ? "default" : "outline"}
                                            size="sm"
                                            onClick={() => link.url && router.get(link.url)}
                                            disabled={!link.url}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
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
