import { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { toast } from 'sonner';
import { Search, Eye, Edit, Trash2, Download, FileText, Filter, CloudUpload, Plus, Copy, ToggleLeft, ToggleRight, CheckCircle, Upload, FolderOpen, Archive, FileCheck } from 'lucide-react';
import CreateDocumentModal from '@/components/admin/documentos/CreateDocumentModal';

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
    estado: string;
    tipo_soporte: string;
    formato: string;
    tamaño?: number;
    fecha_creacion: string;
    fecha_modificacion?: string;
    activo: boolean;
    expediente?: {
        numero_expediente: string;
        titulo: string;
    };
    tipologia?: {
        id: number;
        nombre: string;
        categoria: string;
    };
    usuario_creador?: {
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
    expedientes: Array<{ id: number; numero_expediente: string; titulo: string; }>;
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
        estado: 'all',
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
                estado: estadoFilter === 'all' ? undefined : estadoFilter,
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
        if (key === 'estado') {
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
            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-2">
                        <FileText className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Gestión de Documentos
                        </h1>
                    </div>
                    <Button 
                        onClick={() => setShowCreateModal(true)}
                        className="flex items-center gap-2 px-4 py-2 bg-[#2a3d83] text-white rounded-lg hover:bg-[#1e2b5f] transition-colors"
                    >
                        <Plus className="h-4 w-4" />
                        Nuevo Documento
                    </Button>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total Documentos</p>
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
                                <p className="text-sm text-gray-600">Documentos Activos</p>
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
                                <p className="text-sm text-gray-600">Borradores</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{stats.borradores}</p>
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
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="activo">Activo</SelectItem>
                                    <SelectItem value="borrador">Borrador</SelectItem>
                                    <SelectItem value="archivado">Archivado</SelectItem>
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
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Documento
                                    </th>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Expediente
                                    </th>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Estado
                                    </th>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Formato
                                    </th>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Tamaño
                                    </th>
                                    <th className="text-left p-4 font-medium text-sm text-gray-900">
                                        Fecha
                                    </th>
                                    <th className="text-right p-4 font-medium text-sm text-gray-900">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {documentos.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="p-8 text-center text-gray-500">
                                            No hay documentos disponibles
                                        </td>
                                    </tr>
                                ) : (
                                    documentos.data.map((documento) => (
                                        <tr key={documento.id} className="border-b hover:bg-gray-50/50">
                                            <td className="p-4">
                                                <div className="flex items-center">
                                                    <div className="flex-shrink-0 h-10 w-10">
                                                        <div className="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                            <FileText className="h-5 w-5 text-[#2a3d83]" />
                                                        </div>
                                                    </div>
                                                    <div className="ml-4">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {documento.titulo}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            {documento.codigo_documento}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="p-4">
                                                {documento.expediente ? (
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {documento.expediente.titulo}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            {documento.expediente.numero_expediente}
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <span className="text-sm text-gray-400">Sin expediente</span>
                                                )}
                                            </td>
                                            <td className="p-4">
                                                <Badge className={getEstadoBadgeColor(documento.estado)}>
                                                    {estados[documento.estado] || documento.estado}
                                                </Badge>
                                            </td>
                                            <td className="p-4">
                                                <div className="flex items-center space-x-2">
                                                    <Badge className={getSoporteBadgeColor(documento.tipo_soporte)}>
                                                        {tiposSoporte[documento.tipo_soporte] || documento.tipo_soporte}
                                                    </Badge>
                                                    <span className="text-xs text-gray-500 uppercase">
                                                        {documento.formato}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="p-4 text-sm text-gray-900">
                                                {formatFileSize(documento.tamaño)}
                                            </td>
                                            <td className="p-4 text-sm text-gray-500">
                                                {new Date(documento.created_at).toLocaleDateString('es-ES')}
                                            </td>
                                            <td className="p-4 text-right">
                                                <div className="flex justify-end gap-2">
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    onClick={() => window.location.href = `/admin/documentos/${documento.id}`}
                                                                    className="h-8 w-8"
                                                                >
                                                                    <Eye className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Ver detalle</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    onClick={() => setShowEditModal(documento)}
                                                                    className="h-8 w-8"
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Editar</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    onClick={() => setShowDeleteModal(documento)}
                                                                    className="h-8 w-8 text-red-600 hover:text-red-700"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Eliminar</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {documentos.last_page > 1 && (
                        <div className="mt-6 flex items-center justify-between px-6 pb-6">
                            <p className="text-sm text-gray-600">
                                Mostrando <span className="font-medium">{documentos.from || 0}</span> a{' '}
                                <span className="font-medium">{documentos.to || 0}</span> de{' '}
                                <span className="font-medium">{documentos.total}</span> documentos
                            </p>
                            <div className="flex gap-2">
                                {documentos.links.map((link) => (
                                    <Button
                                        key={link.label}
                                        variant={link.active ? "default" : "outline"}
                                        size="sm"
                                        onClick={() => link.url && (window.location.href = link.url)}
                                        disabled={!link.url}
                                    >
                                        <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                    </Button>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal de creación de documentos */}
            <CreateDocumentModal
                open={showCreateModal}
                onOpenChange={setShowCreateModal}
                expedientes={expedientes}
                tipologias={tipologias}
                formatosDisponibles={Object.keys(formatosDisponibles)}
                tiposSoporte={tiposSoporte}
                nivelesConfidencialidad={nivelesConfidencialidad}
            />
        </AppLayout>
    );
}
