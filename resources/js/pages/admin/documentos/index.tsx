import { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { toast } from 'sonner';
import { Search, Eye, Edit, Trash2, Download, FileText, Filter, CloudUpload, Plus, Copy, ToggleLeft, ToggleRight, CheckCircle, Upload } from 'lucide-react';
import CreateDocumentModal from '@/components/admin/documentos/CreateDocumentModal';

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
    const [searchQuery, setSearchQuery] = useState('');
    const [estadoFilter, setEstadoFilter] = useState('');
    const [formatoFilter, setFormatoFilter] = useState('');
    const [showDeleteModal, setShowDeleteModal] = useState<Documento | null>(null);
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState<Documento | null>(null);
    const [showViewModal, setShowViewModal] = useState<Documento | null>(null);
    const [showFilters, setShowFilters] = useState(false);
    const [filters, setFilters] = useState({
        search: '',
        estado: '',
        tipo_soporte: '',
        expediente_id: ''
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
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);

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
        const newFilters = { [key]: value };
        router.get('/admin/documentos', newFilters, {
            preserveState: true,
            replace: true,
        });
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
        <AppLayout>
            <Head title="Gestión de Documentos" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="md:flex md:items-center md:justify-between mb-6">
                        <div className="min-w-0 flex-1">
                            <h1 className="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                                Gestión de Documentos
                            </h1>
                            <p className="mt-1 text-sm text-gray-500">
                                Administra la captura e ingreso de documentos electrónicos del SGDEA
                            </p>
                        </div>
                        <div className="mt-4 flex md:ml-4 md:mt-0 space-x-3">
                            <Button
                                variant="outline"
                                onClick={() => setShowFilters(!showFilters)}
                                className="flex items-center space-x-2"
                            >
                                <Filter className="h-4 w-4" />
                                <span>Filtros</span>
                            </Button>
                            <Button 
                                className="flex items-center space-x-2 bg-[#2a3d83] hover:bg-[#1e2a5c]"
                                onClick={() => setShowCreateModal(true)}
                            >
                                <CloudUpload className="h-4 w-4" />
                                <span>Nuevo Documento</span>
                            </Button>
                        </div>
                    </div>

                    {/* Estadísticas */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        <div className="bg-white rounded-lg border p-6">
                            <div className="flex items-center">
                                <div className="p-3 bg-blue-100 rounded-full">
                                    <FileText className="h-6 w-6 text-[#2a3d83]" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-500">Total Documentos</p>
                                    <p className="text-2xl font-semibold text-gray-900">{stats?.total || 0}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div className="bg-white rounded-lg border p-6">
                            <div className="flex items-center">
                                <div className="p-3 bg-green-100 rounded-full">
                                    <FileText className="h-6 w-6 text-green-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-500">Activos</p>
                                    <p className="text-2xl font-semibold text-gray-900">{stats?.activos || 0}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg border p-6">
                            <div className="flex items-center">
                                <div className="p-3 bg-yellow-100 rounded-full">
                                    <FileText className="h-6 w-6 text-yellow-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-500">Borradores</p>
                                    <p className="text-2xl font-semibold text-gray-900">{stats?.borradores || 0}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg border p-6">
                            <div className="flex items-center">
                                <div className="p-3 bg-purple-100 rounded-full">
                                    <FileText className="h-6 w-6 text-purple-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-500">Archivados</p>
                                    <p className="text-2xl font-semibold text-gray-900">{stats?.archivados || 0}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filtros */}
                    {showFilters && (
                        <div className="bg-white rounded-lg border p-6 mb-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Buscar
                                    </label>
                                    <Input
                                        type="text"
                                        placeholder="Buscar por nombre, código o descripción..."
                                        value={filters.search}
                                        onChange={(e) => handleFilterChange('search', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Estado
                                    </label>
                                    <Select value={filters.estado} onValueChange={(value) => handleFilterChange('estado', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Todos los estados" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Todos los estados</SelectItem>
                                            {Object.entries(estados).map(([key, label]) => (
                                                <SelectItem key={key} value={key}>{label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Tipo de Soporte
                                    </label>
                                    <Select value={filters.tipo_soporte} onValueChange={(value) => handleFilterChange('tipo_soporte', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Todos los soportes" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Todos los soportes</SelectItem>
                                            {Object.entries(tiposSoporte).map(([key, label]) => (
                                                <SelectItem key={key} value={key}>{label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Tabla de documentos */}
                    <div className="bg-white rounded-lg border overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Documento
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Expediente
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Estado
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Formato
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Tamaño
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Fecha
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {documentos.data.map((documento) => (
                                        <tr key={documento.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <div className="p-2 bg-blue-100 rounded-lg">
                                                        <FileText className="h-5 w-5" />
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
                                            <td className="px-6 py-4 whitespace-nowrap">
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
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <Badge className={getEstadoBadgeColor(documento.estado)}>
                                                    {estados[documento.estado] || documento.estado}
                                                </Badge>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center space-x-2">
                                                    <Badge className={getSoporteBadgeColor(documento.tipo_soporte)}>
                                                        {tiposSoporte[documento.tipo_soporte] || documento.tipo_soporte}
                                                    </Badge>
                                                    <span className="text-xs text-gray-500 uppercase">
                                                        {documento.formato}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {formatFileSize(documento.tamaño)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {new Date(documento.created_at).toLocaleDateString('es-ES')}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => window.location.href = `/admin/documentos/${documento.id}/download`}
                                                >
                                                    Ver detalle
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Paginación */}
                    {documentos.last_page > 1 && (
                        <div className="mt-6 flex items-center justify-between">
                            <div className="text-sm text-gray-700">
                                Mostrando {((documentos.current_page - 1) * documentos.per_page) + 1} a{' '}
                                {Math.min(documentos.current_page * documentos.per_page, documentos.total)} de{' '}
                                {documentos.total} resultados
                            </div>
                            <div className="flex space-x-2">
                                {documentos.links.map((link, index) => (
                                    link.url ? (
                                        <Button
                                            key={index}
                                            variant={link.active ? "default" : "outline"}
                                            size="sm"
                                            onClick={() => link.url && (window.location.href = link.url)}
                                            disabled={!link.url}
                                        >
                                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                        </Button>
                                    ) : null
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
};
