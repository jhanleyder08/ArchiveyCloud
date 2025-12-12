import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
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
    tabla_retencion?: TRD; // Relación del modelo (snake_case desde Inertia)
    // Campos directos de retención y disposición
    soporte_fisico?: boolean;
    soporte_electronico?: boolean;
    retencion_gestion?: number;
    retencion_central?: number;
    disposicion_ct?: boolean;
    disposicion_e?: boolean;
    disposicion_d?: boolean;
    disposicion_s?: boolean;
    procedimiento?: string;
    dependencia?: string;
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
    };
    stats: {
        total: number;
        activas: number;
        inactivas: number;
        con_subseries: number;
        con_expedientes: number;
    };
    trds: TRD[];
    areas: string[];
    flash?: {
        success?: string;
        message?: string;
        error?: string;
    };
}

export default function AdminSeriesIndex({ data, stats, trds, areas, flash }: Props) {
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
        if (flash?.success) {
            toast.success(flash.success);
        }
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
        dependencia: '',
        orden: 0,
        soporte_fisico: false,
        soporte_electronico: true,
        retencion_gestion: 0,
        retencion_central: 0,
        disposicion_ct: false,
        disposicion_e: false,
        disposicion_d: false,
        disposicion_s: false,
        procedimiento: '',
        activa: true
    });

    const [editForm, setEditForm] = useState({
        codigo: '',
        nombre: '',
        descripcion: '',
        trd_id: '',
        dependencia: '',
        soporte_fisico: false,
        soporte_electronico: true,
        retencion_gestion: 0,
        retencion_central: 0,
        disposicion_ct: false,
        disposicion_e: false,
        disposicion_d: false,
        disposicion_s: false,
        procedimiento: '',
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
                dependencia: showEditModal.dependencia || '',
                soporte_fisico: showEditModal.soporte_fisico ?? false,
                soporte_electronico: showEditModal.soporte_electronico ?? true,
                retencion_gestion: showEditModal.retencion_gestion ?? 0,
                retencion_central: showEditModal.retencion_central ?? 0,
                disposicion_ct: showEditModal.disposicion_ct ?? false,
                disposicion_e: showEditModal.disposicion_e ?? false,
                disposicion_d: showEditModal.disposicion_d ?? false,
                disposicion_s: showEditModal.disposicion_s ?? false,
                procedimiento: showEditModal.procedimiento || '',
                activa: showEditModal.activa ?? true
            });
        }
    }, [showEditModal]);

    const breadcrumbItems = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Administración", href: "#" },
        { title: "Series Documentales", href: "/admin/series" }
    ];

    const disposicionesFinales = {
        'conservacion_total': 'Conservación Total',
        'eliminacion': 'Eliminación',
        'seleccion': 'Selección',
        'transferencia_historica': 'Transferencia Histórica',
        'digitalizacion_eliminacion_fisica': 'Digitalización y Eliminación Física'
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
            preserveScroll: true,
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
            preserveScroll: true,
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
            preserveScroll: true,
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
            'conservacion_total': 'bg-blue-100 text-[#2a3d83]',
            'eliminacion': 'bg-red-100 text-red-800',
            'seleccion': 'bg-yellow-100 text-yellow-800',
            'transferencia_historica': 'bg-green-100 text-green-800',
            'digitalizacion_eliminacion_fisica': 'bg-orange-100 text-orange-800'
        };
        
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colors[disposicion as keyof typeof colors] || 'bg-gray-100 text-gray-800'}`}>
                {disposicionesFinales[disposicion as keyof typeof disposicionesFinales] || disposicion}
            </span>
        );
    };

    // Función para obtener disposición final desde campos booleanos
    const getDisposicionFromFlags = (serie: SerieDocumental) => {
        const disposiciones: { key: string; label: string; color: string }[] = [];
        
        if (serie.disposicion_ct) disposiciones.push({ key: 'CT', label: 'Conservación Total', color: 'bg-blue-100 text-blue-800' });
        if (serie.disposicion_e) disposiciones.push({ key: 'E', label: 'Eliminación', color: 'bg-red-100 text-red-800' });
        if (serie.disposicion_d) disposiciones.push({ key: 'D', label: 'Digitalización', color: 'bg-orange-100 text-orange-800' });
        if (serie.disposicion_s) disposiciones.push({ key: 'S', label: 'Selección', color: 'bg-yellow-100 text-yellow-800' });
        
        if (disposiciones.length === 0) {
            return <span className="text-sm text-gray-400">No configurada</span>;
        }
        
        return (
            <div className="flex flex-wrap gap-1">
                {disposiciones.map(d => (
                    <span 
                        key={d.key} 
                        className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${d.color}`}
                        title={d.label}
                    >
                        {d.key}
                    </span>
                ))}
            </div>
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
                                
                                // Preparar datos asegurando que trd_id sea un número
                                const formData = {
                                    codigo: createForm.codigo.trim() || null,
                                    nombre: createForm.nombre.trim(),
                                    descripcion: createForm.descripcion.trim(),
                                    trd_id: createForm.trd_id ? parseInt(createForm.trd_id) : null,
                                    dependencia: createForm.dependencia?.trim() || null,
                                    orden: createForm.orden || null,
                                    observaciones: createForm.observaciones?.trim() || null,
                                    activa: createForm.activa !== undefined ? createForm.activa : true,
                                    // Datos de retención
                                    retencion_archivo_gestion: createForm.tiempo_archivo_gestion,
                                    retencion_archivo_central: createForm.tiempo_archivo_central,
                                    disposicion_final: createForm.disposicion_final
                                };
                                
                                router.post('/admin/series', formData, {
                                     preserveScroll: true,
                                     onSuccess: () => {
                                        setShowCreateModal(false);
                                        setCreateForm({
                                            codigo: '',
                                            nombre: '',
                                            descripcion: '',
                                            trd_id: '',
                                            dependencia: '',
                                            orden: 0,
                                            tiempo_archivo_gestion: 0,
                                            tiempo_archivo_central: 0,
                                            disposicion_final: 'conservacion_total',
                                            area_responsable: '',
                                            observaciones: '',
                                            activa: true
                                        });
                                        // No mostrar toast aquí, el backend redirige con mensaje flash
                                    },
                                    onError: (errors) => {
                                        console.error('Error al crear serie:', errors);
                                        // Mostrar errores de validación
                                        if (errors) {
                                            Object.keys(errors).forEach(field => {
                                                const message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                                                toast.error(`Error en ${field}: ${message}`);
                                            });
                                        } else {
                                            toast.error('Error al crear la serie documental');
                                        }
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
                                        <Label htmlFor="create-tiempo-gestion">Retención Archivo Gestión (años) *</Label>
                                        <Input
                                            id="create-tiempo-gestion"
                                            type="number"
                                            min="0"
                                            value={createForm.retencion_gestion}
                                            onChange={(e) => setCreateForm({...createForm, retencion_gestion: parseInt(e.target.value) || 0})}
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="create-tiempo-central">Retención Archivo Central (años) *</Label>
                                        <Input
                                            id="create-tiempo-central"
                                            type="number"
                                            min="0"
                                            value={createForm.retencion_central}
                                            onChange={(e) => setCreateForm({...createForm, retencion_central: parseInt(e.target.value) || 0})}
                                            required
                                        />
                                    </div>
                                </div>

                                {/* Soporte Documental */}
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium">Soporte Documental</Label>
                                    <div className="flex items-center gap-4">
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={createForm.soporte_fisico}
                                                onChange={(e) => setCreateForm({...createForm, soporte_fisico: e.target.checked})}
                                                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span className="text-sm">Físico (F)</span>
                                        </label>
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={createForm.soporte_electronico}
                                                onChange={(e) => setCreateForm({...createForm, soporte_electronico: e.target.checked})}
                                                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span className="text-sm">Electrónico (E)</span>
                                        </label>
                                    </div>
                                </div>

                                {/* Disposición Final */}
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium">Disposición Final *</Label>
                                    <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                                        <label className="flex items-center gap-2 cursor-pointer p-2 border rounded hover:bg-gray-50">
                                            <input
                                                type="checkbox"
                                                checked={createForm.disposicion_ct}
                                                onChange={(e) => setCreateForm({...createForm, disposicion_ct: e.target.checked})}
                                                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span className="text-sm">CT - Conservación Total</span>
                                        </label>
                                        <label className="flex items-center gap-2 cursor-pointer p-2 border rounded hover:bg-gray-50">
                                            <input
                                                type="checkbox"
                                                checked={createForm.disposicion_e}
                                                onChange={(e) => setCreateForm({...createForm, disposicion_e: e.target.checked})}
                                                className="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                            />
                                            <span className="text-sm">E - Eliminación</span>
                                        </label>
                                        <label className="flex items-center gap-2 cursor-pointer p-2 border rounded hover:bg-gray-50">
                                            <input
                                                type="checkbox"
                                                checked={createForm.disposicion_d}
                                                onChange={(e) => setCreateForm({...createForm, disposicion_d: e.target.checked})}
                                                className="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                                            />
                                            <span className="text-sm">D - Digitalización</span>
                                        </label>
                                        <label className="flex items-center gap-2 cursor-pointer p-2 border rounded hover:bg-gray-50">
                                            <input
                                                type="checkbox"
                                                checked={createForm.disposicion_s}
                                                onChange={(e) => setCreateForm({...createForm, disposicion_s: e.target.checked})}
                                                className="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500"
                                            />
                                            <span className="text-sm">S - Selección</span>
                                        </label>
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create-procedimiento">Procedimiento</Label>
                                    <Textarea
                                        id="create-procedimiento"
                                        value={createForm.procedimiento}
                                        onChange={(e) => setCreateForm({...createForm, procedimiento: e.target.value})}
                                        placeholder="Procedimiento para la disposición final"
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
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total Series</p>
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
                                <p className="text-sm text-gray-600">Activas</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{stats.activas}</p>
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
                                <p className="text-2xl font-semibold text-[#2a3d83]">{stats.inactivas}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <ToggleLeft className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Con Subseries</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{stats.con_subseries}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <FileText className="h-6 w-6 text-[#2a3d83]" />
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
                                    placeholder="Buscar series..."
                                    className="pl-10"
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Select value={trdFilter || "all"} onValueChange={(value) => setTrdFilter(value === "all" ? "" : value)}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Todas las TRDs" />
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
                                    {areas.map((area) => (
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
                                        if (trdFilter && trdFilter !== 'all') params.append('tablaRetencion', trdFilter);
                                        if (estadoFilter && estadoFilter !== 'all') params.append('estado', estadoFilter);
                                        params.append('formato', formato);
                                        
                                        // Descargar archivo
                                        window.location.href = `/admin/series/export?${params.toString()}`;
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
                                    <SelectItem value="excel">Excel (JSON)</SelectItem>
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
                                                {serie.tabla_retencion ? (
                                                    <Link 
                                                        href={`/admin/trd/${serie.trd_id}`}
                                                        className="block hover:bg-blue-50 rounded p-1 -m-1 transition-colors"
                                                    >
                                                        <div className="text-sm text-[#2a3d83] font-medium hover:underline">
                                                            {serie.tabla_retencion.codigo}
                                                        </div>
                                                        <div className="text-xs text-gray-500 line-clamp-1">
                                                            {serie.tabla_retencion.nombre}
                                                        </div>
                                                    </Link>
                                                ) : (
                                                    <span className="text-sm text-gray-400">Sin TRD asociada</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                {(serie.retencion_gestion !== null && serie.retencion_gestion !== undefined) || 
                                                 (serie.retencion_central !== null && serie.retencion_central !== undefined) ? (
                                                    <div className="text-sm">
                                                        <div className="text-gray-900 font-medium">
                                                            AG: {serie.retencion_gestion ?? 0} años
                                                        </div>
                                                        <div className="text-gray-600 text-xs">
                                                            AC: {serie.retencion_central ?? 0} años
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <span className="text-sm text-gray-400">No configurada</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                {getDisposicionFromFlags(serie)}
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
                    {data.data.length > 0 && (
                        <div className="flex items-center justify-between bg-white border rounded-lg px-6 py-3">
                            <div className="text-sm text-gray-600">
                                Mostrando <span className="font-medium">{data.from || 0}</span> a{' '}
                                <span className="font-medium">{data.to || 0}</span> de{' '}
                                <span className="font-medium">{data.total || 0}</span> series
                            </div>
                            <div className="flex items-center gap-2">
                                {data.prev_page_url && (
                                    <Link
                                        href={data.prev_page_url}
                                        preserveState
                                        className="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Anterior
                                    </Link>
                                )}
                                {data.next_page_url && (
                                    <Link
                                        href={data.next_page_url}
                                        preserveState
                                        className="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Siguiente
                                    </Link>
                                )}
                            </div>
                        </div>
                    )}
                </div>

                {/* Edit Modal */}
                {showEditModal && (
                    <Dialog open={!!showEditModal} onOpenChange={() => setShowEditModal(null)}>
                        <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-scroll [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                            <DialogHeader>
                                <DialogTitle className="text-xl font-semibold text-gray-900">Editar Serie Documental</DialogTitle>
                                <DialogDescription className="text-sm text-gray-600">
                                    Modifique los datos de la serie documental según sea necesario.
                                </DialogDescription>
                            </DialogHeader>
                            <form onSubmit={(e) => {
                                e.preventDefault();
                                router.patch(`/admin/series/${showEditModal.id}`, editForm, {
                                    preserveScroll: true,
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
                                        <Label htmlFor="edit-tiempo-gestion">Retención Archivo Gestión (años) *</Label>
                                        <Input
                                            id="edit-tiempo-gestion"
                                            type="number"
                                            min="0"
                                            value={editForm.retencion_gestion}
                                            onChange={(e) => setEditForm({...editForm, retencion_gestion: parseInt(e.target.value) || 0})}
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="edit-tiempo-central">Retención Archivo Central (años) *</Label>
                                        <Input
                                            id="edit-tiempo-central"
                                            type="number"
                                            min="0"
                                            value={editForm.retencion_central}
                                            onChange={(e) => setEditForm({...editForm, retencion_central: parseInt(e.target.value) || 0})}
                                            required
                                        />
                                    </div>
                                </div>
                                
                                {/* Soporte Documental */}
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium">Soporte Documental</Label>
                                    <div className="flex items-center gap-4">
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={editForm.soporte_fisico}
                                                onChange={(e) => setEditForm({...editForm, soporte_fisico: e.target.checked})}
                                                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span className="text-sm">Físico (F)</span>
                                        </label>
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={editForm.soporte_electronico}
                                                onChange={(e) => setEditForm({...editForm, soporte_electronico: e.target.checked})}
                                                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span className="text-sm">Electrónico (E)</span>
                                        </label>
                                    </div>
                                </div>
                                
                                {/* Disposición Final */}
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium">Disposición Final *</Label>
                                    <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                                        <label className="flex items-center gap-2 cursor-pointer p-2 border rounded hover:bg-gray-50">
                                            <input
                                                type="checkbox"
                                                checked={editForm.disposicion_ct}
                                                onChange={(e) => setEditForm({...editForm, disposicion_ct: e.target.checked})}
                                                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span className="text-sm">CT - Conservación Total</span>
                                        </label>
                                        <label className="flex items-center gap-2 cursor-pointer p-2 border rounded hover:bg-gray-50">
                                            <input
                                                type="checkbox"
                                                checked={editForm.disposicion_e}
                                                onChange={(e) => setEditForm({...editForm, disposicion_e: e.target.checked})}
                                                className="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                            />
                                            <span className="text-sm">E - Eliminación</span>
                                        </label>
                                        <label className="flex items-center gap-2 cursor-pointer p-2 border rounded hover:bg-gray-50">
                                            <input
                                                type="checkbox"
                                                checked={editForm.disposicion_d}
                                                onChange={(e) => setEditForm({...editForm, disposicion_d: e.target.checked})}
                                                className="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                                            />
                                            <span className="text-sm">D - Digitalización</span>
                                        </label>
                                        <label className="flex items-center gap-2 cursor-pointer p-2 border rounded hover:bg-gray-50">
                                            <input
                                                type="checkbox"
                                                checked={editForm.disposicion_s}
                                                onChange={(e) => setEditForm({...editForm, disposicion_s: e.target.checked})}
                                                className="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500"
                                            />
                                            <span className="text-sm">S - Selección</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div className="space-y-2">
                                    <Label htmlFor="edit-procedimiento">Procedimiento</Label>
                                    <Textarea
                                        id="edit-procedimiento"
                                        value={editForm.procedimiento}
                                        onChange={(e) => setEditForm({...editForm, procedimiento: e.target.value})}
                                        placeholder="Procedimiento para la disposición final"
                                        rows={2}
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
                                <DialogTitle className="text-xl font-semibold text-gray-900">Detalles de la Serie Documental</DialogTitle>
                                <DialogDescription className="text-sm text-gray-600">
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
                                    <p className="text-sm text-gray-900">{showViewModal.tabla_retencion?.codigo} - {showViewModal.tabla_retencion?.nombre}</p>
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Retención Archivo Gestión</Label>
                                        <p className="text-sm text-gray-900">{showViewModal.retencion_gestion ?? 'N/A'} años</p>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Retención Archivo Central</Label>
                                        <p className="text-sm text-gray-900">{showViewModal.retencion_central ?? 'N/A'} años</p>
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Soporte</Label>
                                        <div className="flex gap-2 mt-1">
                                            {showViewModal.soporte_fisico && <span className="px-2 py-0.5 bg-gray-100 text-gray-800 rounded text-xs">Físico</span>}
                                            {showViewModal.soporte_electronico && <span className="px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs">Electrónico</span>}
                                        </div>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Disposición Final</Label>
                                        <div className="mt-1">{getDisposicionFromFlags(showViewModal)}</div>
                                    </div>
                                </div>
                                {showViewModal.procedimiento && (
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Procedimiento</Label>
                                        <p className="text-sm text-gray-900">{showViewModal.procedimiento}</p>
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
                        <DialogContent className="sm:max-w-[425px]">
                            <DialogHeader>
                                <DialogTitle className="text-xl font-semibold text-gray-900">Eliminar Serie Documental</DialogTitle>
                                <DialogDescription className="text-sm text-gray-600">
                                    Esta acción no se puede deshacer. La serie será eliminada permanentemente del sistema.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="py-4">
                                <p className="text-gray-700">
                                    ¿Estás seguro de que deseas eliminar la serie <strong>{showDeleteModal.nombre}</strong>?
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
                                    Eliminar Serie
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}
            </div>
        </AppLayout>
    );
};
