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

interface SerieDocumental {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string;
    trd_id: number;
    trd?: TRD;
    tiempo_archivo_gestion: number;
    tiempo_archivo_central: number;
    disposicion_final: string;
    area_responsable: string;
    observaciones?: string;
    activa: boolean;
    subseries_count?: number;
    expedientes_count?: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    data: {
        data: SerieDocumental[];
        current_page: number;
        first_page_url: string;
        from: number;
        last_page: number;
        last_page_url: string;
        next_page_url: string | null;
        prev_page_url: string | null;
        to: number;
        total: number;
        stats?: {
            activas: number;
            inactivas: number;
            total_subseries: number;
        };
    };
    trds: TRD[];
    areas: string[];
    flash?: {
        message?: string;
        error?: string;
    };
}

export default function AdminSeriesIndex({ data, trds, areas, flash }: Props) {
    const [searchQuery, setSearchQuery] = useState('');
    const [trdFilter, setTrdFilter] = useState('all');
    const [estadoFilter, setEstadoFilter] = useState('all');
    const [areaFilter, setAreaFilter] = useState('all');
    const [showDeleteModal, setShowDeleteModal] = useState<SerieDocumental | null>(null);
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState<SerieDocumental | null>(null);
    const [showViewModal, setShowViewModal] = useState<SerieDocumental | null>(null);

    // Interceptar flash messages y mostrarlos como toasts
    useEffect(() => {
        if (flash?.message) {
            toast.success(flash.message);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    const [createForm, setCreateForm] = useState({
        codigo: '',
        nombre: '',
        descripcion: '',
        trd_id: '',
        tiempo_archivo_gestion: 0,
        tiempo_archivo_central: 0,
        disposicion_final: 'conservacion_permanente',
        area_responsable: '',
        observaciones: '',
        activa: true
    });

    const [editForm, setEditForm] = useState({
        codigo: '',
        nombre: '',
        descripcion: '',
        trd_id: '',
        tiempo_archivo_gestion: 0,
        tiempo_archivo_central: 0,
        disposicion_final: 'conservacion_permanente',
        area_responsable: '',
        observaciones: '',
        activa: true
    });

    // Cargar datos en el formulario de edición cuando se abre el modal
    useEffect(() => {
        if (showEditModal) {
            setEditForm({
                codigo: showEditModal.codigo || '',
                nombre: showEditModal.nombre || '',
                descripcion: showEditModal.descripcion || '',
                trd_id: showEditModal.trd_id?.toString() || '',
                tiempo_archivo_gestion: showEditModal.tiempo_archivo_gestion || 0,
                tiempo_archivo_central: showEditModal.tiempo_archivo_central || 0,
                disposicion_final: showEditModal.disposicion_final || 'conservacion_permanente',
                area_responsable: showEditModal.area_responsable || '',
                observaciones: showEditModal.observaciones || '',
                activa: showEditModal.activa ?? true
            });
        }
    }, [showEditModal]);

    const breadcrumbItems = [
        { title: "Dashboard", href: "/admin" },
        { title: "Series Documentales", href: "/admin/series" }
    ];

    const disposicionesFinales = {
        'conservacion_permanente': 'Conservación Permanente',
        'eliminacion': 'Eliminación',
        'seleccion': 'Selección',
        'microfilmacion': 'Microfilmación'
    };

    // Auto-search functionality
    useEffect(() => {
        const delayedSearch = setTimeout(() => {
            if (searchQuery !== '' || trdFilter !== '' || estadoFilter !== '' || areaFilter !== '') {
                const params = new URLSearchParams();
                if (searchQuery) params.append('search', searchQuery);
                if (trdFilter) params.append('trd', trdFilter);
                if (estadoFilter) params.append('estado', estadoFilter);
                if (areaFilter) params.append('area', areaFilter);
                
                router.visit(`/admin/series?${params.toString()}`, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true
                });
            }
        }, 500);

        return () => clearTimeout(delayedSearch);
    }, [searchQuery, trdFilter, estadoFilter, areaFilter]);

    const handleDelete = (serie: SerieDocumental) => {
        router.delete(`/admin/series/${serie.id}`, {
            onSuccess: () => {
                setShowDeleteModal(null);
                toast.success('Serie documental eliminada exitosamente');
            },
            onError: (errors) => {
                const message = typeof errors === 'object' ? Object.values(errors)[0] : 'Error al eliminar la serie';
                toast.error(message as string);
            }
        });
    };

    const handleDuplicate = (serie: SerieDocumental) => {
        router.post(`/admin/series/${serie.id}/duplicate`, {}, {
            onSuccess: () => {
                toast.success('Serie documental duplicada exitosamente');
            },
            onError: (errors) => {
                const message = typeof errors === 'object' ? Object.values(errors)[0] : 'Error al duplicar la serie';
                toast.error(message as string);
            }
        });
    };

    const handleToggleActive = (serie: SerieDocumental) => {
        router.patch(`/admin/series/${serie.id}/toggle-active`, {}, {
            onSuccess: () => {
                const estado = !serie.activa ? 'activada' : 'desactivada';
                toast.success(`Serie documental ${estado} exitosamente`);
            },
            onError: (errors) => {
                const message = typeof errors === 'object' ? Object.values(errors)[0] : 'Error al cambiar el estado';
                toast.error(message as string);
            }
        });
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-CO', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
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
        const colors = {
            'conservacion_permanente': 'bg-blue-100 text-[#2a3d83]',
            'eliminacion': 'bg-red-100 text-red-800',
            'seleccion': 'bg-yellow-100 text-yellow-800',
            'microfilmacion': 'bg-purple-100 text-purple-800'
        };
        
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colors[disposicion as keyof typeof colors] || 'bg-gray-100 text-gray-800'}`}>
                {disposicionesFinales[disposicion as keyof typeof disposicionesFinales] || disposicion}
            </span>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title="Series Documentales" />
            
            <div className="space-y-6">
                {/* Header with title and create button */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-3">
                        <FileText className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Gestión de Series Documentales
                        </h1>
                    </div>
                    <Dialog open={showCreateModal} onOpenChange={setShowCreateModal}>
                        <DialogTrigger asChild>
                            <Button className="flex items-center gap-2 px-4 py-2 bg-[#2a3d83] text-white rounded-lg hover:bg-[#1e2b5f] transition-colors">
                                <Plus className="h-4 w-4" />
                                Nueva Serie Documental
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-scroll [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                            <DialogHeader>
                                <DialogTitle className="text-xl font-semibold text-gray-900">Crear Nueva Serie Documental</DialogTitle>
                                <DialogDescription className="text-sm text-gray-600">
                                    Complete los siguientes datos para crear una nueva Serie Documental.
                                </DialogDescription>
                            </DialogHeader>
                            <form onSubmit={(e) => {
                                e.preventDefault();
                                
                                // Validación del lado del cliente
                                if (!createForm.trd_id) {
                                    toast.error('Debe seleccionar una TRD asociada');
                                    return;
                                }
                                if (!createForm.nombre.trim()) {
                                    toast.error('El nombre es requerido');
                                    return;
                                }
                                if (!createForm.descripcion.trim()) {
                                    toast.error('La descripción es requerida');
                                    return;
                                }
                                
                                router.post('/admin/series', createForm, {
                                     onSuccess: () => {
                                        setShowCreateModal(false);
                                        setCreateForm({
                                            codigo: '',
                                            nombre: '',
                                            descripcion: '',
                                            trd_id: '',
                                            tiempo_archivo_gestion: 0,
                                            tiempo_archivo_central: 0,
                                            disposicion_final: 'conservacion_permanente',
                                            area_responsable: '',
                                            observaciones: '',
                                            activa: true
                                        });
                                        toast.success('Serie documental creada exitosamente');
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
                                            placeholder="Ej: SER-001 (opcional, se genera automático)"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="create-trd">TRD Asociada *</Label>
                                        <Select value={createForm.trd_id} onValueChange={(value) => setCreateForm({...createForm, trd_id: value})}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar TRD" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {trds.map((trd) => (
                                                    <SelectItem key={trd.id} value={trd.id.toString()}>
                                                        {trd.codigo} - {trd.nombre}
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
                                        placeholder="Nombre de la serie documental"
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create-descripcion">Descripción *</Label>
                                    <Textarea
                                        id="create-descripcion"
                                        value={createForm.descripcion}
                                        onChange={(e) => setCreateForm({...createForm, descripcion: e.target.value})}
                                        placeholder="Descripción de la serie documental"
                                        rows={3}
                                        required
                                    />
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="create-tiempo-gestion">Tiempo Archivo Gestión (años) *</Label>
                                        <Input
                                            id="create-tiempo-gestion"
                                            type="number"
                                            min="0"
                                            value={createForm.tiempo_archivo_gestion}
                                            onChange={(e) => setCreateForm({...createForm, tiempo_archivo_gestion: parseInt(e.target.value) || 0})}
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="create-tiempo-central">Tiempo Archivo Central (años) *</Label>
                                        <Input
                                            id="create-tiempo-central"
                                            type="number"
                                            min="0"
                                            value={createForm.tiempo_archivo_central}
                                            onChange={(e) => setCreateForm({...createForm, tiempo_archivo_central: parseInt(e.target.value) || 0})}
                                            required
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="create-disposicion">Disposición Final *</Label>
                                        <Select value={createForm.disposicion_final} onValueChange={(value) => setCreateForm({...createForm, disposicion_final: value})}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar disposición" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {Object.entries(disposicionesFinales).map(([key, label]) => (
                                                    <SelectItem key={key} value={key}>{label}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="create-area">Área Responsable</Label>
                                        <Input
                                            id="create-area"
                                            type="text"
                                            value={createForm.area_responsable}
                                            onChange={(e) => setCreateForm({...createForm, area_responsable: e.target.value})}
                                            placeholder="Área responsable de la serie"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create-observaciones">Observaciones</Label>
                                    <Textarea
                                        id="create-observaciones"
                                        value={createForm.observaciones}
                                        onChange={(e) => setCreateForm({...createForm, observaciones: e.target.value})}
                                        placeholder="Observaciones adicionales"
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
                                        Crear Serie Documental
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Total Series</p>
                                <p className="text-2xl font-bold text-[#2a3d83]">{data.total}</p>
                            </div>
                            <FileText className="h-8 w-8 text-[#2a3d83]" />
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Activas</p>
                                <p className="text-2xl font-bold text-[#2a3d83]">{data.stats?.activas || 0}</p>
                            </div>
                            <ToggleRight className="h-8 w-8 text-[#2a3d83]" />
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Inactivas</p>
                                <p className="text-2xl font-bold text-[#2a3d83]">{data.stats?.inactivas || 0}</p>
                            </div>
                            <ToggleLeft className="h-8 w-8 text-[#2a3d83]" />
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Subseries Totales</p>
                                <p className="text-2xl font-bold text-[#2a3d83]">{data.stats?.total_subseries || 0}</p>
                            </div>
                            <FileText className="h-8 w-8 text-[#2a3d83]" />
                        </div>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white rounded-lg border p-6">
                    <div className="flex flex-col lg:flex-row gap-4">
                        <div className="flex-1">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                <Input
                                    type="text"
                                    placeholder="Buscar por código, nombre o descripción..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                        </div>
                        <div className="flex flex-col sm:flex-row gap-3">
                            <Select value={trdFilter} onValueChange={setTrdFilter}>
                                <SelectTrigger className="w-full sm:w-48">
                                    <SelectValue placeholder="Filtrar por TRD" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas las TRDs</SelectItem>
                                    {trds.map((trd) => (
                                        <SelectItem key={trd.id} value={trd.id.toString()}>
                                            {trd.codigo} - {trd.nombre}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={estadoFilter} onValueChange={setEstadoFilter}>
                                <SelectTrigger className="w-full sm:w-40">
                                    <SelectValue placeholder="Estado" />
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
                                    {areas.map((area) => (
                                        <SelectItem key={area} value={area}>
                                            {area}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button variant="outline" size="sm" className="flex items-center gap-2">
                                <Download className="h-4 w-4" />
                                Exportar
                            </Button>
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
                                        Serie Documental
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        TRD Asociada
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Retención
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Disposición Final
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Subseries
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha Creación
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {data.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="px-6 py-8 text-center text-gray-500">
                                            <div className="flex flex-col items-center gap-2">
                                                <FileText className="h-8 w-8 text-gray-300" />
                                                <p>No se encontraron series documentales</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    data.data.map((serie) => (
                                        <tr key={serie.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4">
                                                <div className="flex flex-col">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {serie.codigo}
                                                    </div>
                                                    <div className="text-sm text-gray-600 line-clamp-2">
                                                        {serie.nombre}
                                                    </div>
                                                    {serie.descripcion && (
                                                        <div className="text-xs text-gray-500 mt-1 line-clamp-1">
                                                            {serie.descripcion}
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="text-sm text-gray-900">
                                                    {serie.trd?.codigo}
                                                </div>
                                                <div className="text-xs text-gray-500 line-clamp-1">
                                                    {serie.trd?.nombre}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="text-sm text-gray-900">
                                                    AG: {serie.tiempo_archivo_gestion} años
                                                </div>
                                                <div className="text-sm text-gray-600">
                                                    AC: {serie.tiempo_archivo_central} años
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                {getDisposicionBadge(serie.disposicion_final)}
                                            </td>
                                            <td className="px-6 py-4">
                                                {getEstadoBadge(serie.activa)}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="text-sm text-gray-900">
                                                    {serie.subseries_count || 0} subseries
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="text-sm text-gray-900">
                                                    {formatDate(serie.created_at)}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => setShowViewModal(serie)}
                                                                    className="text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50"
                                                                >
                                                                    <Eye className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>Ver detalles</TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => setShowEditModal(serie)}
                                                                    className="text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50"
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>Editar</TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => handleDuplicate(serie)}
                                                                    className="text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50"
                                                                >
                                                                    <Copy className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>Duplicar</TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => handleToggleActive(serie)}
                                                                    className={`${
                                                                        serie.activa 
                                                                            ? 'text-orange-600 hover:text-orange-800 hover:bg-orange-50' 
                                                                            : 'text-green-600 hover:text-green-800 hover:bg-green-50'
                                                                    }`}
                                                                >
                                                                    {serie.activa ? (
                                                                        <ToggleLeft className="h-4 w-4" />
                                                                    ) : (
                                                                        <ToggleRight className="h-4 w-4" />
                                                                    )}
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                {serie.activa ? 'Desactivar' : 'Activar'}
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => setShowDeleteModal(serie)}
                                                                    className="text-red-600 hover:text-red-700"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>Eliminar</TooltipContent>
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
                    {data.last_page > 1 && (
                        <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                            <div className="flex items-center justify-between">
                                <div className="flex justify-between flex-1 sm:hidden">
                                    <Button
                                        variant="outline"
                                        disabled={!data.prev_page_url}
                                        onClick={() => data.prev_page_url && router.visit(data.prev_page_url)}
                                    >
                                        Anterior
                                    </Button>
                                    <Button
                                        variant="outline"
                                        disabled={!data.next_page_url}
                                        onClick={() => data.next_page_url && router.visit(data.next_page_url)}
                                    >
                                        Siguiente
                                    </Button>
                                </div>
                                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm text-gray-700">
                                            Mostrando{' '}
                                            <span className="font-medium">{data.from}</span> a{' '}
                                            <span className="font-medium">{data.to}</span> de{' '}
                                            <span className="font-medium">{data.total}</span> resultados
                                        </p>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={data.current_page === 1}
                                            onClick={() => router.visit(data.first_page_url)}
                                        >
                                            Primera
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={!data.prev_page_url}
                                            onClick={() => data.prev_page_url && router.visit(data.prev_page_url)}
                                        >
                                            Anterior
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={!data.next_page_url}
                                            onClick={() => data.next_page_url && router.visit(data.next_page_url)}
                                        >
                                            Siguiente
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={data.current_page === data.last_page}
                                            onClick={() => router.visit(data.last_page_url)}
                                        >
                                            Última
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Edit Modal */}
                {showEditModal && (
                    <Dialog open={!!showEditModal} onOpenChange={() => setShowEditModal(null)}>
                        <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-scroll [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                            <DialogHeader>
                                <DialogTitle>Editar Serie Documental</DialogTitle>
                                <DialogDescription>
                                    Modifique los datos de la serie documental.
                                </DialogDescription>
                            </DialogHeader>
                            <form onSubmit={(e) => {
                                e.preventDefault();
                                router.patch(`/admin/series/${showEditModal.id}`, editForm, {
                                    onSuccess: () => {
                                        setShowEditModal(null);
                                        toast.success('Serie documental actualizada exitosamente');
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
                                        <Label htmlFor="edit-codigo">Código</Label>
                                        <Input
                                            id="edit-codigo"
                                            type="text"
                                            value={editForm.codigo}
                                            onChange={(e) => setEditForm({...editForm, codigo: e.target.value})}
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="edit-trd">TRD Asociada *</Label>
                                        <Select value={editForm.trd_id} onValueChange={(value) => setEditForm({...editForm, trd_id: value})}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar TRD" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {trds.map((trd) => (
                                                    <SelectItem key={trd.id} value={trd.id.toString()}>
                                                        {trd.codigo} - {trd.nombre}
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
                                
                                {/* Campos de Retención */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="edit-tiempo-gestion">Tiempo Archivo Gestión (años) *</Label>
                                        <Input
                                            id="edit-tiempo-gestion"
                                            type="number"
                                            min="0"
                                            value={editForm.tiempo_archivo_gestion}
                                            onChange={(e) => setEditForm({...editForm, tiempo_archivo_gestion: parseInt(e.target.value) || 0})}
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="edit-tiempo-central">Tiempo Archivo Central (años) *</Label>
                                        <Input
                                            id="edit-tiempo-central"
                                            type="number"
                                            min="0"
                                            value={editForm.tiempo_archivo_central}
                                            onChange={(e) => setEditForm({...editForm, tiempo_archivo_central: parseInt(e.target.value) || 0})}
                                            required
                                        />
                                    </div>
                                </div>
                                
                                <div className="space-y-2">
                                    <Label htmlFor="edit-disposicion">Disposición Final *</Label>
                                    <Select value={editForm.disposicion_final} onValueChange={(value) => setEditForm({...editForm, disposicion_final: value})}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar disposición final" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(disposicionesFinales).map(([key, label]) => (
                                                <SelectItem key={key} value={key}>
                                                    {label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                
                                <div className="space-y-2">
                                    <Label htmlFor="edit-area-responsable">Área Responsable</Label>
                                    <Input
                                        id="edit-area-responsable"
                                        type="text"
                                        value={editForm.area_responsable}
                                        onChange={(e) => setEditForm({...editForm, area_responsable: e.target.value})}
                                    />
                                </div>
                                
                                <div className="space-y-2">
                                    <Label htmlFor="edit-observaciones">Observaciones</Label>
                                    <Textarea
                                        id="edit-observaciones"
                                        value={editForm.observaciones}
                                        onChange={(e) => setEditForm({...editForm, observaciones: e.target.value})}
                                        rows={3}
                                    />
                                </div>
                                
                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={() => setShowEditModal(null)}>
                                        Cancelar
                                    </Button>
                                    <Button type="submit" className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                                        Actualizar Serie
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
                                <DialogTitle>Detalles de la Serie Documental</DialogTitle>
                                <DialogDescription>
                                    Información completa de la serie documental.
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
                                    <Label className="text-sm font-medium text-gray-500">TRD Asociada</Label>
                                    <p className="text-sm text-gray-900">{showViewModal.trd?.codigo} - {showViewModal.trd?.nombre}</p>
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
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Subseries</Label>
                                        <p className="text-sm text-gray-900">{showViewModal.subseries_count || 0} subseries</p>
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
                        <DialogContent className="sm:max-w-[400px]">
                            <DialogHeader>
                                <DialogTitle className="text-red-600">Confirmar Eliminación</DialogTitle>
                                <DialogDescription>
                                    ¿Está seguro de que desea eliminar la serie documental "{showDeleteModal.nombre}"? Esta acción no se puede deshacer.
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <Button variant="outline" onClick={() => setShowDeleteModal(null)}>
                                    Cancelar
                                </Button>
                                <Button 
                                    variant="destructive" 
                                    onClick={() => handleDelete(showDeleteModal)}
                                >
                                    Eliminar
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}
            </div>
        </AppLayout>
    );
};
