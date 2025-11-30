import React, { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { toast } from 'sonner';
import { 
    Search, 
    Eye, 
    Edit, 
    Trash2, 
    Download, 
    FileText, 
    Filter, 
    Plus, 
    FolderOpen, 
    Archive 
} from 'lucide-react';

const breadcrumbItems = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Documentos', href: '/admin/documentos' },
];

interface Documento {
    id: number;
    codigo_documento: string;
    titulo: string;
    descripcion?: string;
    activo: boolean;
    tipo_soporte: string;
    formato: string;
    tamano_bytes?: number;
    fecha_creacion: string;
    fecha_modificacion?: string;
    expediente?: {
        id: number;
        codigo: string;
        titulo: string;
    };
    tipologia?: {
        id: number;
        nombre: string;
        categoria: string;
    };
    usuario_creador?: {
        id: number;
        name: string;
    };
    observaciones?: string;
    identificador_unico?: string;
    usuario_creador_id?: number;
    usuario_modificador_id?: number;
    created_at: string;
    updated_at: string;
}

interface PaginatedDocumentos {
    data: Documento[];
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
    borradores: number;
    archivados: number;
}

interface Props {
    documentos: PaginatedDocumentos;
    stats: Stats;
    flash?: {
        success?: string;
        error?: string;
    };
    expedientes: Array<{ id: number; codigo: string; titulo: string; }>;
    tipologias: Array<{ id: number; nombre: string; categoria: string; }>;
}

export default function AdminDocumentosIndex({ documentos, stats, flash, expedientes, tipologias }: Props) {
    const { flash: pageFlash } = usePage<{flash: {success?: string, error?: string}}>().props;
    const [searchQuery, setSearchQuery] = useState('');
    const [estadoFilter, setEstadoFilter] = useState('all');
    const [formatoFilter, setFormatoFilter] = useState('all');
    const [showDeleteModal, setShowDeleteModal] = useState<Documento | null>(null);
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState<Documento | null>(null);
    const [showViewModal, setShowViewModal] = useState<Documento | null>(null);
    const [showFilters, setShowFilters] = useState(false);
    const [filters, setFilters] = useState({
        search: '',
        activo: 'all',
        tipo_soporte: 'all',
        expediente_id: 'all'
    });

    // Estados estáticos para filtros
    const estados: Record<string, string> = {
        'borrador': 'Borrador',
        'activo': 'Activo',
        'archivado': 'Archivado'
    };

    const tiposSoporte: Record<string, string> = {
        'fisico': 'Físico',
        'electronico': 'Electrónico',
        'hibrido': 'Híbrido'
    };

    // Props adicionales para modales
    const formatosDisponibles = {
        'PDF': 'PDF',
        'DOC': 'DOC', 
        'DOCX': 'DOCX',
        'XLS': 'XLS',
        'XLSX': 'XLSX',
        'JPG': 'JPG',
        'PNG': 'PNG'
    };
    const nivelesConfidencialidad = {
        'publico': 'Público',
        'clasificado': 'Clasificado', 
        'reservado': 'Reservado',
        'confidencial': 'Confidencial'
    };

    // Interceptar flash messages y mostrarlos como toasts
    useEffect(() => {
        if (pageFlash?.success) {
            toast.success(pageFlash.success);
        }
        if (pageFlash?.error) {
            toast.error(pageFlash.error);
        }
    }, [pageFlash]);

    // Reactive search with debounce
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            router.get('/admin/documentos', {
                search: searchQuery || undefined,
                activo: estadoFilter === 'all' ? undefined : estadoFilter,
                formato: formatoFilter === 'all' ? undefined : formatoFilter
            }, {
                preserveState: true,
                replace: true,
            });
        }, 500); // 500ms debounce

        return () => clearTimeout(timeoutId);
    }, [searchQuery, estadoFilter, formatoFilter]);

    const [createForm, setCreateForm] = useState({
        codigo_documento: '',
        titulo: '',
        descripcion: '',
        expediente_id: '',
        tipologia_id: '',
        estado: 'borrador'
    });

    const [editForm, setEditForm] = useState({
        codigo_documento: '',
        titulo: '',
        descripcion: '',
        expediente_id: '',
        tipologia_id: '',
        estado: 'borrador'
    });

    const handleFilterChange = (key: string, value: string) => {
        setFilters(prev => ({
            ...prev,
            [key]: value
        }));
        // Aplicar filtros, tratando 'all' como sin filtro
        if (key === 'activo') {
            setEstadoFilter(value);
        } else if (key === 'tipo_soporte') {
            // Aquí puedes manejar otros filtros si es necesario
        }
    };

    // Funciones auxiliares
    const getEstadoBadgeColor = (estado: string) => {
        const colors: Record<string, string> = {
            'borrador': 'bg-gray-100 text-gray-800',
            'pendiente': 'bg-yellow-100 text-yellow-800',
            'aprobado': 'bg-blue-100 text-blue-800',
            'activo': 'bg-green-100 text-green-800',
            'archivado': 'bg-purple-100 text-purple-800',
            'obsoleto': 'bg-red-100 text-red-800',
        };
        return colors[estado] || 'bg-gray-100 text-gray-800';
    };

    const getSoporteBadgeColor = (soporte: string) => {
        const colors: Record<string, string> = {
            'electronico': 'bg-blue-100 text-blue-800',
            'fisico': 'bg-orange-100 text-orange-800',
            'hibrido': 'bg-purple-100 text-purple-800',
        };
        return colors[soporte] || 'bg-gray-100 text-gray-800';
    };

    const formatFileSize = (bytes?: number) => {
        if (!bytes) return 'N/A';
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    };

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title="Gestión de Documentos" />
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-2">
                        <FileText className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Gestión de Documentos
                        </h1>
                    </div>
                    <Link href="/admin/documentos/create">
                        <Button className="flex items-center gap-2 px-4 py-2 bg-[#2a3d83] text-white rounded-lg hover:bg-[#1e2b5f] transition-colors">
                            <Plus className="h-4 w-4" />
                            Nuevo Documento
                        </Button>
                    </Link>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total Documentos</p>
                                <p className="text-2xl font-semibold text-gray-900">{stats?.total || 0}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <FileText className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Documentos Activos</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{stats?.activos || 0}</p>
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
                                <p className="text-sm text-gray-600">Borradores</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{stats?.borradores || 0}</p>
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
                                    placeholder="Buscar documentos..."
                                    className="pl-10"
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Select value={estadoFilter} onValueChange={setEstadoFilter}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Filtro por estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    <SelectItem value="true">Activos</SelectItem>
                                    <SelectItem value="false">Inactivos</SelectItem>
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
                                <Select value={filters.tipo_soporte} onValueChange={(value) => handleFilterChange('tipo_soporte', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Tipo de Soporte" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los soportes</SelectItem>
                                        {Object.entries(tiposSoporte).map(([key, label]) => (
                                            <SelectItem key={key} value={key}>{label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select value={formatoFilter} onValueChange={setFormatoFilter}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Formato" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los formatos</SelectItem>
                                        {Object.entries(formatosDisponibles).map(([key, label]) => (
                                            <SelectItem key={key} value={key}>{label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select value={filters.expediente_id} onValueChange={(value) => handleFilterChange('expediente_id', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Expediente" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los expedientes</SelectItem>
                                        {expedientes.map((exp) => (
                                            <SelectItem key={exp.id} value={exp.id.toString()}>
                                                {exp.titulo}
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
                            <thead className="bg-gray-50 border-b">
                                <tr>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Documento</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Expediente</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Estado</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Formato</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Tamaño</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Fecha</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {!documentos?.data || documentos.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="py-8 px-6 text-center text-gray-500">
                                            No se encontraron documentos.
                                        </td>
                                    </tr>
                                ) : (
                                    documentos?.data?.map((documento) => (
                                        <tr key={documento.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="py-4 px-6">
                                                <div className="flex items-center gap-3">
                                                    <div className="h-8 w-8 bg-[#2a3d83] rounded-full flex items-center justify-center text-white text-sm font-medium">
                                                        {documento.titulo.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {documento.titulo}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            {documento.codigo_documento}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="py-4 px-6">
                                                {documento.expediente ? (
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {documento.expediente.titulo}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            {documento.expediente.codigo}
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <span className="text-sm text-gray-400">Sin expediente</span>
                                                )}
                                            </td>
                                            <td className="py-4 px-6">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                    documento.activo 
                                                        ? 'bg-green-100 text-green-800' 
                                                        : 'bg-gray-100 text-gray-800'
                                                }`}>
                                                    {documento.activo ? 'Activo' : 'Inactivo'}
                                                </span>
                                            </td>
                                            <td className="py-4 px-6">
                                                <div className="flex items-center space-x-2">
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getSoporteBadgeColor(documento.tipo_soporte)}`}>
                                                        {tiposSoporte[documento.tipo_soporte] || documento.tipo_soporte}
                                                    </span>
                                                    <span className="text-xs text-gray-500 uppercase">
                                                        {documento.formato}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="py-4 px-6 text-sm text-gray-900">
                                                {formatFileSize(documento.tamano_bytes)}
                                            </td>
                                            <td className="py-4 px-6 text-sm text-gray-600">
                                                {new Date(documento.created_at).toLocaleDateString('es-ES')}
                                            </td>
                                            <td className="py-4 px-6">
                                                <TooltipProvider>
                                                    <div className="flex items-center gap-2">
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <button
                                                                    onClick={() => window.location.href = `/admin/documentos/${documento.id}`}
                                                                    className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors"
                                                                >
                                                                    <Eye className="h-4 w-4" />
                                                                </button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Ver detalle</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <button
                                                                    onClick={() => setShowEditModal(documento)}
                                                                    className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors"
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Editar documento</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <button
                                                                    onClick={() => setShowDeleteModal(documento)}
                                                                    className="p-2 rounded-md text-red-600 hover:text-red-800 hover:bg-red-50 transition-colors"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Eliminar documento</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </div>
                                                </TooltipProvider>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {documentos?.data && documentos.data.length > 0 && (
                        <div className="flex items-center justify-between bg-white border rounded-lg px-6 py-3">
                            <div className="text-sm text-gray-600">
                                Mostrando <span className="font-medium">{documentos?.from || 0}</span> a{' '}
                                <span className="font-medium">{documentos?.to || 0}</span> de{' '}
                                <span className="font-medium">{documentos?.total || 0}</span> documentos
                            </div>
                            <div className="flex items-center gap-2">
                                {documentos?.links?.map((link, index) => {
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

            {/* TODO: Implementar modal de creación de documentos */}
        </AppLayout>
    );
}
