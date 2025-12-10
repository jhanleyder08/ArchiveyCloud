import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import { FileText, Plus, Search, Eye, Edit, Copy, Trash2, ToggleRight, ToggleLeft, Download } from 'lucide-react';
import { toast } from 'sonner';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

// Tipos TypeScript
interface TRD {
    id: number;
    codigo: string;
    nombre: string;
}

interface Serie {
    id: number;
    codigo: string;
    nombre: string;
    trd?: TRD;
}

interface SubserieDocumental {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string;
    serie_documental_id: number;  // Campo real del backend
    serie?: Serie;
    tiempo_archivo_gestion: number;
    tiempo_archivo_central: number;
    disposicion_final: string;
    area_responsable?: string;  // Es nullable
    observaciones?: string;
    activa: boolean;
    expedientes_count?: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    data?: {
        data: SubserieDocumental[];
        current_page: number;
        first_page_url: string;
        from: number;
        last_page: number;
        last_page_url: string;
        next_page_url: string | null;
        prev_page_url: string | null;
        to: number;
        total: number;
    };
    stats?: {
        total: number;
        activas: number;
        inactivas: number;
        con_expedientes: number;
    };
    series?: Serie[];
    areas?: string[];
    flash?: {
        message?: string;
        error?: string;
    };
    filters?: {
        search?: string;
        serie_id?: string;
        estado?: string;
        area?: string;
    };
}

export default function AdminSubseriesIndex({ data, stats, series, areas, flash, filters }: Props) {
    // Valores por defecto para evitar errores
    const safeData = data || {
        data: [],
        current_page: 1,
        first_page_url: '',
        from: 0,
        last_page: 1,
        last_page_url: '',
        next_page_url: null,
        prev_page_url: null,
        to: 0,
        total: 0,
    };
    
    const safeStats = stats || {
        total: 0,
        activas: 0,
        inactivas: 0,
        con_expedientes: 0,
    };
    
    const safeSeries = series || [];
    const safeAreas = areas || [];
    const [searchQuery, setSearchQuery] = useState('');
    const [serieFilter, setSerieFilter] = useState('all');
    const [estadoFilter, setEstadoFilter] = useState('all');
    const [areaFilter, setAreaFilter] = useState('all');
    const [showDeleteModal, setShowDeleteModal] = useState<SubserieDocumental | null>(null);
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState<SubserieDocumental | null>(null);
    const [showViewModal, setShowViewModal] = useState<SubserieDocumental | null>(null);

    // Formularios
    const [createForm, setCreateForm] = useState({
        codigo: '',
        nombre: '',
        descripcion: '',
        serie_documental_id: '',
        tiempo_archivo_gestion: 0,
        tiempo_archivo_central: 0,
        disposicion_final: 'conservacion_permanente',
        procedimiento: '',
        metadatos_especificos: null,
        tipologias_documentales: null,
        observaciones: '',
        activa: true
    });

    const [editForm, setEditForm] = useState({
        codigo: '',
        nombre: '',
        descripcion: '',
        serie_documental_id: '',
        tiempo_archivo_gestion: 0,
        tiempo_archivo_central: 0,
        disposicion_final: 'conservacion_permanente',
        procedimiento: '',
        metadatos_especificos: null,
        tipologias_documentales: null,
        observaciones: '',
        activa: true
    });

    // Flash messages
    useEffect(() => {
        if (flash?.message) {
            toast.success(flash.message);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    // Cargar datos en formulario de edición
    useEffect(() => {
        if (showEditModal) {
            setEditForm({
                codigo: showEditModal.codigo || '',
                nombre: showEditModal.nombre || '',
                descripcion: showEditModal.descripcion || '',
                serie_documental_id: showEditModal.serie_documental_id?.toString() || '',
                tiempo_archivo_gestion: showEditModal.tiempo_archivo_gestion || 0,
                tiempo_archivo_central: showEditModal.tiempo_archivo_central || 0,
                disposicion_final: showEditModal.disposicion_final || 'conservacion_permanente',
                procedimiento: '',  // Agregado para TypeScript
                metadatos_especificos: null,  // Agregado para TypeScript
                tipologias_documentales: null,  // Agregado para TypeScript
                observaciones: showEditModal.observaciones || '',
                activa: showEditModal.activa ?? true
            });
        }
    }, [showEditModal]);

    const breadcrumbItems = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Administración", href: "#" },
        { title: "Subseries Documentales", href: "/admin/subseries" }
    ];

    const disposicionesFinales = {
        'conservacion_permanente': 'Conservación Permanente',
        'eliminacion': 'Eliminación',
        'seleccion': 'Selección',
        'microfilmacion': 'Microfilmación'
    };

    const handleDelete = (subserie: SubserieDocumental) => {
        router.delete(`/admin/subseries/${subserie.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setShowDeleteModal(null);
                toast.success('Subserie documental eliminada exitosamente');
            },
            onError: (errors) => {
                const message = typeof errors === 'object' ? Object.values(errors)[0] : 'Error al eliminar la subserie';
                toast.error(message as string);
            }
        });
    };

    const handleDuplicate = (subserie: SubserieDocumental) => {
        router.post(`/admin/subseries/${subserie.id}/duplicate`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Subserie documental duplicada exitosamente');
            },
            onError: (errors) => {
                const message = typeof errors === 'object' ? Object.values(errors)[0] : 'Error al duplicar la subserie';
                toast.error(message as string);
            }
        });
    };

    const handleToggleActive = (subserie: SubserieDocumental) => {
        router.patch(`/admin/subseries/${subserie.id}/toggle-active`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                const estado = !subserie.activa ? 'activada' : 'desactivada';
                toast.success(`Subserie documental ${estado} exitosamente`);
            },
            onError: (errors) => {
                const message = typeof errors === 'object' ? Object.values(errors)[0] : 'Error al cambiar el estado';
                toast.error(message as string);
            }
        });
    };

    const getEstadoBadge = (activa: boolean) => {
        if (activa) {
            return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activa</span>;
        } else {
            return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactiva</span>;
        }
    };

    const getDisposicionBadge = (disposicion: string) => {
        const colors: { [key: string]: string } = {
            'conservacion_permanente': 'bg-green-100 text-green-800',
            'eliminacion': 'bg-red-100 text-red-800',
            'seleccion': 'bg-yellow-100 text-yellow-800',
            'microfilmacion': 'bg-blue-100 text-blue-800'
        };
        const labels: { [key: string]: string } = {
            'conservacion_permanente': 'CONSERVACIÓN PERMANENTE',
            'eliminacion': 'ELIMINACIÓN',
            'seleccion': 'SELECCIÓN',
            'microfilmacion': 'MICROFILMACIÓN'
        };
        return (
            <Badge className={colors[disposicion] || 'bg-gray-100 text-gray-800'}>
                {labels[disposicion] || disposicion.replace('_', ' ').toUpperCase()}
            </Badge>
        );
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-CO', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title="Subseries Documentales" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-3">
                        <FileText className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Gestión de Subseries Documentales
                        </h1>
                    </div>
                    <Dialog open={showCreateModal} onOpenChange={setShowCreateModal}>
                        <DialogTrigger asChild>
                            <Button className="flex items-center gap-2 px-4 py-2 bg-[#2a3d83] text-white rounded-lg hover:bg-[#1e2b5f] transition-colors">
                                <Plus className="h-4 w-4" />
                                Nueva Subserie Documental
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-scroll [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                            <DialogHeader>
                                <DialogTitle className="text-xl font-semibold text-gray-900">Crear Nueva Subserie Documental</DialogTitle>
                                <DialogDescription className="text-sm text-gray-600">
                                    Complete los siguientes datos para crear una nueva Subserie Documental.
                                </DialogDescription>
                            </DialogHeader>
                            <form onSubmit={(e) => {
                                e.preventDefault();
                                router.post('/admin/subseries', createForm, {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        setShowCreateModal(false);
                                        setCreateForm({
                                            codigo: '',
                                            nombre: '',
                                            descripcion: '',
                                            serie_documental_id: '',
                                            tiempo_archivo_gestion: 0,
                                            tiempo_archivo_central: 0,
                                            disposicion_final: 'conservacion_permanente',
                                            procedimiento: '',
                                            metadatos_especificos: null,
                                            tipologias_documentales: null,
                                            observaciones: '',
                                            activa: true
                                        });
                                        toast.success('Subserie documental creada exitosamente');
                                    },
                                    onError: (errors) => {
                                        Object.keys(errors).forEach(field => {
                                            const message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                                            toast.error(`Error en ${field}: ${message}`);
                                        });
                                    }
                                });
                            }} className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="create-codigo">Código</Label>
                                        <Input
                                            id="create-codigo"
                                            type="text"
                                            value={createForm.codigo}
                                            onChange={(e) => setCreateForm({...createForm, codigo: e.target.value})}
                                            placeholder="Ej: SER-001-001 (opcional, se genera automático)"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="create-serie">Serie Documental Asociada *</Label>
                                        <Select value={createForm.serie_documental_id} onValueChange={(value) => setCreateForm({...createForm, serie_documental_id: value})}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar Serie" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {safeSeries.map((serie) => (
                                                    <SelectItem key={serie.id} value={serie.id.toString()}>
                                                        {serie.codigo} - {serie.nombre}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create-nombre">Nombre *</Label>
                                    <Input
                                        id="create-nombre"
                                        type="text"
                                        value={createForm.nombre}
                                        onChange={(e) => setCreateForm({...createForm, nombre: e.target.value})}
                                        placeholder="Nombre de la subserie documental"
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create-descripcion">Descripción *</Label>
                                    <Textarea
                                        id="create-descripcion"
                                        value={createForm.descripcion}
                                        onChange={(e) => setCreateForm({...createForm, descripcion: e.target.value})}
                                        placeholder="Descripción de la subserie documental"
                                        rows={3}
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create-observaciones">Observaciones</Label>
                                    <Textarea
                                        id="create-observaciones"
                                        value={createForm.observaciones}
                                        onChange={(e) => setCreateForm({...createForm, observaciones: e.target.value})}
                                        placeholder="Observaciones adicionales (opcional)"
                                        rows={2}
                                    />
                                </div>

                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setShowCreateModal(false)}
                                    >
                                        Cancelar
                                    </Button>
                                    <Button
                                        type="submit"
                                        className="bg-[#2a3d83] hover:bg-[#1e2b5f]"
                                    >
                                        Crear Subserie Documental
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Statistics */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total Subseries</p>
                                <p className="text-2xl font-semibold text-gray-900">{safeStats.total}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <FileText className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Activas</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{safeStats.activas}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <ToggleRight className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Inactivas</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{safeStats.inactivas}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <ToggleLeft className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Con Expedientes</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{safeStats.con_expedientes}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <FileText className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white rounded-lg border p-6">
                    <div className="flex flex-col sm:flex-row gap-4 items-center justify-between">
                        <div className="flex items-center gap-4 w-full sm:w-auto">
                            <div className="relative flex-1 sm:w-80">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    placeholder="Buscar subseries..."
                                    className="pl-10"
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Select value={serieFilter || "all"} onValueChange={(value) => setSerieFilter(value === "all" ? "" : value)}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Todas las Series" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas las Series</SelectItem>
                                    {safeSeries.map((serie) => (
                                        <SelectItem key={serie.id} value={serie.id.toString()}>
                                            {serie.codigo} - {serie.nombre}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={estadoFilter || "all"} onValueChange={(value) => setEstadoFilter(value === "all" ? "" : value)}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="activa">Activa</SelectItem>
                                    <SelectItem value="inactiva">Inactiva</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select value={areaFilter} onValueChange={setAreaFilter}>
                                <SelectTrigger className="w-full sm:w-48">
                                    <SelectValue placeholder="Área Responsable" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas las áreas</SelectItem>
                                    {safeAreas.map((area) => (
                                        <SelectItem key={area} value={area}>
                                            {area}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select 
                                value="" 
                                onValueChange={(formato) => {
                                    if (formato) {
                                        // Construir URL con filtros actuales
                                        const params = new URLSearchParams();
                                        if (searchQuery) params.append('search', searchQuery);
                                        if (serieFilter && serieFilter !== 'all') params.append('serie_id', serieFilter);
                                        if (estadoFilter && estadoFilter !== 'all') params.append('estado', estadoFilter);
                                        if (areaFilter && areaFilter !== 'all') params.append('area', areaFilter);
                                        params.append('format', formato);
                                        
                                        // Descargar archivo
                                        window.location.href = `/admin/subseries/export?${params.toString()}`;
                                        
                                        // Notificar al usuario
                                        toast.success(`Exportando subseries en formato ${formato.toUpperCase()}...`);
                                    }
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-32">
                                    <div className="flex items-center gap-2">
                                        <Download className="h-4 w-4" />
                                        <SelectValue placeholder="Exportar" />
                                    </div>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="json">Excel (JSON)</SelectItem>
                                    <SelectItem value="csv">CSV</SelectItem>
                                    <SelectItem value="xml">XML</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                {/* Main Table */}
                <div className="bg-white rounded-lg border overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Subserie Documental
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Serie Asociada
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Expedientes
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {safeData.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-6 py-8 text-center text-gray-500">
                                            <div className="flex flex-col items-center gap-2">
                                                <FileText className="h-8 w-8 text-gray-300" />
                                                <p>No se encontraron subseries documentales</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    safeData.data.map((subserie) => (
                                        <tr key={subserie.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4">
                                                <div className="flex flex-col">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {subserie.codigo}
                                                    </div>
                                                    <div className="text-sm text-gray-600 line-clamp-2">
                                                        {subserie.nombre}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="text-sm text-gray-900">
                                                    {subserie.serie?.codigo}
                                                </div>
                                                <div className="text-xs text-gray-500 line-clamp-1">
                                                    {subserie.serie?.nombre}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                {getEstadoBadge(subserie.activa)}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="text-sm text-gray-900">
                                                    {subserie.expedientes_count || 0} expedientes
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <TooltipProvider>
                                                    <div className="flex items-center gap-2">
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <button
                                                                    onClick={() => setShowViewModal(subserie)}
                                                                    className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors"
                                                                >
                                                                    <Eye className="h-4 w-4" />
                                                                </button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Ver detalles</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <button
                                                                    onClick={() => setShowEditModal(subserie)}
                                                                    className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors"
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Editar subserie</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <button
                                                                    onClick={() => handleDuplicate(subserie)}
                                                                    className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors"
                                                                >
                                                                    <Copy className="h-4 w-4" />
                                                                </button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Duplicar subserie</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <button
                                                                    onClick={() => handleToggleActive(subserie)}
                                                                    className={`p-2 rounded-md transition-colors ${
                                                                        subserie.activa 
                                                                            ? 'text-orange-600 hover:text-orange-800 hover:bg-orange-50' 
                                                                            : 'text-green-600 hover:text-green-800 hover:bg-green-50'
                                                                    }`}
                                                                >
                                                                    {subserie.activa ? (
                                                                        <ToggleLeft className="h-4 w-4" />
                                                                    ) : (
                                                                        <ToggleRight className="h-4 w-4" />
                                                                    )}
                                                                </button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{subserie.activa ? 'Desactivar subserie' : 'Activar subserie'}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <button
                                                                    onClick={() => setShowDeleteModal(subserie)}
                                                                    className="p-2 rounded-md text-red-600 hover:text-red-800 hover:bg-red-50 transition-colors"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Eliminar subserie</p>
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
                </div>

                {/* Edit Modal */}
                {showEditModal && (
                    <Dialog open={!!showEditModal} onOpenChange={() => setShowEditModal(null)}>
                        <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-scroll [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                            <DialogHeader>
                                <DialogTitle className="text-xl font-semibold text-gray-900">Editar Subserie Documental</DialogTitle>
                                <DialogDescription className="text-sm text-gray-600">
                                    Modifique los datos de la subserie documental según sea necesario.
                                </DialogDescription>
                            </DialogHeader>
                            <form onSubmit={(e) => {
                                e.preventDefault();
                                router.patch(`/admin/subseries/${showEditModal.id}`, editForm, {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        setShowEditModal(null);
                                        toast.success('Subserie documental actualizada exitosamente');
                                    },
                                    onError: (errors) => {
                                        Object.keys(errors).forEach(field => {
                                            const message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                                            toast.error(`Error en ${field}: ${message}`);
                                        });
                                    }
                                });
                            }} className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="edit-codigo">Código *</Label>
                                        <Input
                                            id="edit-codigo"
                                            type="text"
                                            value={editForm.codigo}
                                            onChange={(e) => setEditForm({...editForm, codigo: e.target.value})}
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="edit-serie">Serie Documental Asociada *</Label>
                                        <Select value={editForm.serie_documental_id} onValueChange={(value) => setEditForm({...editForm, serie_documental_id: value})}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar Serie" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {safeSeries.map((serie) => (
                                                    <SelectItem key={serie.id} value={serie.id.toString()}>
                                                        {serie.codigo} - {serie.nombre}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="edit-nombre">Nombre *</Label>
                                    <Input
                                        id="edit-nombre"
                                        type="text"
                                        value={editForm.nombre}
                                        onChange={(e) => setEditForm({...editForm, nombre: e.target.value})}
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="edit-descripcion">Descripción *</Label>
                                    <Textarea
                                        id="edit-descripcion"
                                        value={editForm.descripcion}
                                        onChange={(e) => setEditForm({...editForm, descripcion: e.target.value})}
                                        rows={3}
                                        required
                                    />
                                </div>
                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={() => setShowEditModal(null)}>
                                        Cancelar
                                    </Button>
                                    <Button type="submit" className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                                        Actualizar Subserie
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                )}

                {/* View Modal */}
                {showViewModal && (
                    <Dialog open={!!showViewModal} onOpenChange={() => setShowViewModal(null)}>
                        <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-scroll [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                            <DialogHeader>
                                <DialogTitle className="text-xl font-semibold text-gray-900">Detalles de la Subserie Documental</DialogTitle>
                                <DialogDescription className="text-sm text-gray-600">
                                    Información completa de la subserie documental.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Código</Label>
                                        <p className="text-sm text-gray-900">{showViewModal.codigo}</p>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Estado</Label>
                                        <div className="mt-1">{getEstadoBadge(showViewModal.activa)}</div>
                                    </div>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Nombre</Label>
                                    <p className="text-sm text-gray-900">{showViewModal.nombre}</p>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Descripción</Label>
                                    <p className="text-sm text-gray-900">{showViewModal.descripcion}</p>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Serie Documental Asociada</Label>
                                    <p className="text-sm text-gray-900">
                                        {showViewModal.serie?.codigo} - {showViewModal.serie?.nombre}
                                    </p>
                                    {showViewModal.serie?.trd && (
                                        <p className="text-xs text-gray-500">
                                            TRD: {showViewModal.serie.trd.codigo} - {showViewModal.serie.trd.nombre}
                                        </p>
                                    )}
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Tiempo Archivo Gestión</Label>
                                        <p className="text-sm text-gray-900">{showViewModal.tiempo_archivo_gestion} años</p>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Tiempo Archivo Central</Label>
                                        <p className="text-sm text-gray-900">{showViewModal.tiempo_archivo_central} años</p>
                                    </div>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Disposición Final</Label>
                                    <div className="mt-1">{getDisposicionBadge(showViewModal.disposicion_final)}</div>
                                </div>
                                {showViewModal.area_responsable && (
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Área Responsable</Label>
                                        <p className="text-sm text-gray-900">{showViewModal.area_responsable}</p>
                                    </div>
                                )}
                                {showViewModal.observaciones && (
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Observaciones</Label>
                                        <p className="text-sm text-gray-900">{showViewModal.observaciones}</p>
                                    </div>
                                )}
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Expedientes</Label>
                                        <p className="text-sm text-gray-900">{showViewModal.expedientes_count || 0} expedientes</p>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Fecha de Creación</Label>
                                        <p className="text-sm text-gray-900">{formatDate(showViewModal.created_at)}</p>
                                    </div>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button onClick={() => setShowViewModal(null)}>Cerrar</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}

                {/* Delete Confirmation Modal */}
                {showDeleteModal && (
                    <Dialog open={!!showDeleteModal} onOpenChange={() => setShowDeleteModal(null)}>
                        <DialogContent className="sm:max-w-[425px]">
                            <DialogHeader>
                                <DialogTitle className="text-xl font-semibold text-gray-900">Eliminar Subserie Documental</DialogTitle>
                                <DialogDescription className="text-sm text-gray-600">
                                    Esta acción no se puede deshacer. La subserie será eliminada permanentemente del sistema.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="py-4">
                                <p className="text-gray-700">
                                    ¿Estás seguro de que deseas eliminar la subserie <strong>{showDeleteModal.nombre}</strong>?
                                </p>
                                <p className="text-gray-600 mt-2">
                                    Código: <strong>{showDeleteModal.codigo}</strong>
                                </p>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" onClick={() => setShowDeleteModal(null)}>
                                    Cancelar
                                </Button>
                                <Button 
                                    variant="destructive" 
                                    onClick={() => handleDelete(showDeleteModal)}
                                >
                                    Eliminar Subserie
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}
            </div>
        </AppLayout>
    );
};
