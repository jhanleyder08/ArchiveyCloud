import { useState, useRef } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { toast } from '@/components/ui/toast';
import { FileText, Plus, Search, Eye, Edit, Copy, ToggleLeft, ToggleRight, Trash2, CheckCircle, Upload, Download, FileSpreadsheet } from 'lucide-react';
import { useEffect } from 'react';

interface CCD {
    id: number;
    codigo: string;
    nombre: string;
    version: string;
}

interface TRD {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string;
    entidad: string;
    dependencia?: string;
    version: number;
    fecha_aprobacion: string;
    fecha_vigencia_inicio: string;
    fecha_vigencia_fin?: string;
    estado: string;
    vigente: boolean;
    aprobado_por?: number;
    observaciones_generales?: string;
    metadatos_adicionales?: any;
    identificador_unico?: string;
    formato_archivo?: string;
    metadatos_asociados?: any;
    usuario_creador_id?: number;
    usuario_modificador_id?: number;
    created_at: string;
    updated_at: string;
    series_count?: number;
    ccd_id?: number;
    cuadro_clasificacion?: CCD;
}

interface PaginatedTRDs {
    data: TRD[];
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
    vigentes: number;
    borradores: number;
    aprobadas: number;
}

interface Props {
    trds?: PaginatedTRDs;
    stats?: Stats;
    ccds?: CCD[];
    flash?: {
        success?: string;
        error?: string;
    };
}

export default function AdminTRDIndex({ trds, stats, ccds = [], flash }: Props) {
    // Valores por defecto para evitar errores
    const safeStats = stats || {
        total: 0,
        vigentes: 0,
        borradores: 0,
        aprobadas: 0,
    };
    
    const safeTrds = trds || {
        data: [],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
        from: 0,
        to: 0,
        links: [],
    };
    const [searchQuery, setSearchQuery] = useState('');
    const [estadoFilter, setEstadoFilter] = useState('');
    const [vigenciaFilter, setVigenciaFilter] = useState('');
    const [showDeleteModal, setShowDeleteModal] = useState<TRD | null>(null);
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState<TRD | null>(null);
    const [showViewModal, setShowViewModal] = useState<TRD | null>(null);
    const [showImportModal, setShowImportModal] = useState<TRD | null>(null);
    const [importFile, setImportFile] = useState<File | null>(null);
    const [isImporting, setIsImporting] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

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
        codigo: '',
        ccd_id: '',
        nombre: '',
        descripcion: '',
        // Campos del formato oficial FOR-GDI-GDO-002
        codigo_unidad_administrativa: '',
        nombre_unidad_administrativa: '',
        codigo_dependencia: '',
        nombre_dependencia: '',
        fecha_aprobacion: '',
        fecha_vigencia_inicio: '',
        fecha_vigencia_fin: '',
        observaciones_generales: '',
        version: 1,
        estado: 'borrador'
    });

    const [editForm, setEditForm] = useState({
        codigo: '',
        nombre: '',
        descripcion: '',
        codigo_unidad_administrativa: '',
        nombre_unidad_administrativa: '',
        codigo_dependencia: '',
        nombre_dependencia: '',
        fecha_aprobacion: '',
        fecha_vigencia_inicio: '',
        fecha_vigencia_fin: '',
        observaciones_generales: '',
        version: 1,
        estado: 'borrador'
    });

    // Cargar datos en el formulario de edición cuando se abre el modal
    useEffect(() => {
        if (showEditModal) {
            setEditForm({
                codigo: showEditModal.codigo || '',
                nombre: showEditModal.nombre || '',
                descripcion: showEditModal.descripcion || '',
                codigo_unidad_administrativa: (showEditModal as any).codigo_unidad_administrativa || '',
                nombre_unidad_administrativa: (showEditModal as any).nombre_unidad_administrativa || '',
                codigo_dependencia: (showEditModal as any).codigo_dependencia || '',
                nombre_dependencia: (showEditModal as any).nombre_dependencia || '',
                fecha_aprobacion: showEditModal.fecha_aprobacion ? new Date(showEditModal.fecha_aprobacion).toISOString().split('T')[0] : '',
                fecha_vigencia_inicio: showEditModal.fecha_vigencia_inicio ? new Date(showEditModal.fecha_vigencia_inicio).toISOString().split('T')[0] : '',
                fecha_vigencia_fin: showEditModal.fecha_vigencia_fin ? new Date(showEditModal.fecha_vigencia_fin).toISOString().split('T')[0] : '',
                observaciones_generales: showEditModal.observaciones_generales || '',
                version: showEditModal.version || 1,
                estado: showEditModal.estado || 'borrador'
            });
        }
    }, [showEditModal]);

    const breadcrumbItems = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Administración", href: "#" },
        { title: "Tablas de Retención Documental", href: "/admin/trd" }
    ];

    const estados = {
        'borrador': 'Borrador',
        'revision': 'En Revisión',
        'aprobada': 'Aprobada',
        'vigente': 'Vigente',
        'historica': 'Histórica'
    };

    // Auto-search functionality
    useEffect(() => {
        const delayedSearch = setTimeout(() => {
            if (searchQuery !== '' || estadoFilter !== '' || vigenciaFilter !== '') {
                const params = new URLSearchParams();
                if (searchQuery) params.append('search', searchQuery);
                if (estadoFilter) params.append('estado', estadoFilter);
                if (vigenciaFilter) params.append('vigencia', vigenciaFilter);
                
                router.visit(`/admin/trd?${params.toString()}`, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true
                });
            }
        }, 500);

        return () => clearTimeout(delayedSearch);
    }, [searchQuery, estadoFilter, vigenciaFilter]);

    const handleDelete = (trd: TRD) => {
        router.delete(`/admin/trd/${trd.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setShowDeleteModal(null);
                toast.success('TRD eliminada exitosamente');
            },
            onError: (errors) => {
                const errorMessage = Object.values(errors)[0] as string;
                toast.error(errorMessage || 'Error al eliminar la TRD');
                setShowDeleteModal(null);
            },
            onFinish: () => setShowDeleteModal(null)
        });
    };

    const handleToggleVigencia = (trd: TRD) => {
        // Validar que solo TRDs aprobadas puedan cambiar su vigencia
        if (trd.estado === 'borrador') {
            toast.error('No se puede activar la vigencia de una TRD en estado Borrador. Debe estar Aprobada primero.');
            return;
        }
        if (trd.estado === 'revision') {
            toast.error('No se puede activar la vigencia de una TRD en Revisión. Debe estar Aprobada primero.');
            return;
        }
        
        router.patch(`/admin/trd/${trd.id}/vigencia`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(trd.estado === 'vigente' ? 'Vigencia desactivada exitosamente' : 'Vigencia activada exitosamente');
            },
            onError: (errors) => {
                const errorMessage = Object.values(errors)[0] as string;
                toast.error(errorMessage || 'Error al cambiar el estado de vigencia');
            }
        });
    };

    const handleDuplicate = (trd: TRD) => {
        router.post(`/admin/trd/${trd.id}/duplicate`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('TRD duplicada exitosamente');
            },
            onError: (errors) => {
                const errorMessage = Object.values(errors)[0] as string;
                toast.error(errorMessage || 'Error al duplicar la TRD');
            }
        });
    };

    // Función para manejar la importación de series desde Excel/CSV
    const handleImportSeries = (trd: TRD) => {
        if (!importFile) {
            toast.error('Seleccione un archivo Excel o CSV');
            return;
        }

        setIsImporting(true);
        const formData = new FormData();
        formData.append('archivo', importFile);

        router.post(`/admin/trd/${trd.id}/importar`, formData, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Series importadas exitosamente');
                setShowImportModal(null);
                setImportFile(null);
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            },
            onError: (errors) => {
                const errorMessage = Object.values(errors)[0] as string;
                toast.error(errorMessage || 'Error al importar series');
            },
            onFinish: () => {
                setIsImporting(false);
            }
        });
    };

    // Función para descargar plantilla de importación
    const handleDownloadTemplate = () => {
        window.location.href = '/admin/trd/plantilla';
    };

    // Función para exportar TRD a PDF
    const handleExportPDF = (trd: TRD) => {
        window.location.href = `/admin/trd/${trd.id}/export-pdf`;
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const getEstadoBadge = (estado: string) => {
        // Solo mostrar estados del proceso, no "vigente"
        const estadoProceso = estado === 'vigente' ? 'aprobada' : estado;
        
        const colors = {
            'borrador': 'bg-gray-100 text-gray-800',
            'revision': 'bg-yellow-100 text-yellow-800',
            'aprobada': 'bg-green-100 text-green-800',
            'rechazada': 'bg-red-100 text-red-800',
            'obsoleta': 'bg-red-100 text-red-800'
        };
        
        const text = estados[estadoProceso as keyof typeof estados] || estadoProceso;
        const colorClass = colors[estadoProceso as keyof typeof colors] || 'bg-gray-100 text-gray-800';
        
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorClass}`}>
                {text}
            </span>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title="Gestión de Tablas de Retención Documental" />
            
            <div className="space-y-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        {flash.error}
                    </div>
                )}

                {/* Header */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-2">
                        <FileText className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Gestión de Tablas de Retención Documental
                        </h1>
                    </div>
                    <div className="flex items-center gap-2">
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="outline"
                                        onClick={handleDownloadTemplate}
                                        className="flex items-center gap-2 px-3 py-2 text-emerald-700 border-emerald-300 hover:bg-emerald-50"
                                    >
                                        <Download className="h-4 w-4" />
                                        Plantilla Excel
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Descargar plantilla para importar series</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                        <Dialog open={showCreateModal} onOpenChange={setShowCreateModal}>
                            <DialogTrigger asChild>
                                <Button className="flex items-center gap-2 px-4 py-2 bg-[#2a3d83] text-white rounded-lg hover:bg-[#1e2b5f] transition-colors">
                                    <Plus className="h-4 w-4" />
                                    Nueva TRD
                                </Button>
                            </DialogTrigger>
                        <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-scroll [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                            <DialogHeader>
                                <DialogTitle className="text-xl font-semibold text-gray-900">Crear Nueva TRD</DialogTitle>
                                <DialogDescription className="text-sm text-gray-600">
                                    Complete los siguientes datos para crear una nueva Tabla de Retención Documental.
                                </DialogDescription>
                            </DialogHeader>
                            <form onSubmit={(e) => {
                                e.preventDefault();
                                
                                // Validación básica del frontend
                                if (!createForm.ccd_id) {
                                    toast.error('Debe seleccionar un CCD');
                                    return;
                                }
                                if (!createForm.codigo || !createForm.nombre || !createForm.codigo_unidad_administrativa || !createForm.nombre_unidad_administrativa) {
                                    toast.error('Por favor complete todos los campos requeridos');
                                    return;
                                }
                                
                                // Preparar datos para enviar según formato oficial FOR-GDI-GDO-002
                                const formData = {
                                    codigo: createForm.codigo.trim(),
                                    ccd_id: parseInt(createForm.ccd_id),
                                    nombre: createForm.nombre.trim(),
                                    descripcion: createForm.descripcion?.trim() || '',
                                    // Campos del formato oficial
                                    codigo_unidad_administrativa: createForm.codigo_unidad_administrativa.trim(),
                                    nombre_unidad_administrativa: createForm.nombre_unidad_administrativa.trim(),
                                    codigo_dependencia: createForm.codigo_dependencia?.trim() || '',
                                    nombre_dependencia: createForm.nombre_dependencia?.trim() || '',
                                    fecha_aprobacion: createForm.fecha_aprobacion || null,
                                    fecha_vigencia_inicio: createForm.fecha_vigencia_inicio || null,
                                    fecha_vigencia_fin: createForm.fecha_vigencia_fin || null,
                                    observaciones_generales: createForm.observaciones_generales?.trim() || null,
                                    version: String(createForm.version || 1),
                                    estado: createForm.estado,
                                };
                                
                                console.log('Enviando datos:', formData);
                                
                                router.post('/admin/trd', formData, {
                                     preserveScroll: true,
                                     onSuccess: () => {
                                        setShowCreateModal(false);
                                        setCreateForm({
                                            codigo: '',
                                            ccd_id: '',
                                            nombre: '',
                                            descripcion: '',
                                            codigo_unidad_administrativa: '',
                                            nombre_unidad_administrativa: '',
                                            codigo_dependencia: '',
                                            nombre_dependencia: '',
                                            fecha_aprobacion: '',
                                            fecha_vigencia_inicio: '',
                                            fecha_vigencia_fin: '',
                                            observaciones_generales: '',
                                            version: 1,
                                            estado: 'borrador'
                                        });
                                    },
                                    onError: (errors) => {
                                        console.error('Error al crear TRD:', errors);
                                        // Mostrar errores de validación al usuario
                                        if (errors) {
                                            Object.keys(errors).forEach(field => {
                                                const message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                                                toast.error(`Error en ${field}: ${message}`);
                                            });
                                        }
                                    }
                                });
                            }} className="space-y-4">
                                {/* Selector de CCD - OBLIGATORIO */}
                                <div className="space-y-2 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <Label htmlFor="create-ccd" className="text-[#2a3d83] font-semibold">
                                        Cuadro de Clasificación Documental (CCD) *
                                    </Label>
                                    <Select 
                                        value={createForm.ccd_id} 
                                        onValueChange={(value) => setCreateForm({...createForm, ccd_id: value})}
                                    >
                                        <SelectTrigger className="bg-white">
                                            <SelectValue placeholder="Seleccione un CCD..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {ccds.length === 0 ? (
                                                <SelectItem value="none" disabled>
                                                    No hay CCDs disponibles
                                                </SelectItem>
                                            ) : (
                                                ccds.map((ccd) => (
                                                    <SelectItem key={ccd.id} value={ccd.id.toString()}>
                                                        {ccd.codigo} - {ccd.nombre}
                                                    </SelectItem>
                                                ))
                                            )}
                                        </SelectContent>
                                    </Select>
                                    {ccds.length === 0 && (
                                        <p className="text-xs text-amber-600">
                                            ⚠️ Debe crear un CCD primero
                                        </p>
                                    )}
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="create-codigo">Código TRD *</Label>
                                        <Input
                                            id="create-codigo"
                                            type="text"
                                            value={createForm.codigo}
                                            onChange={(e) => setCreateForm({...createForm, codigo: e.target.value})}
                                            placeholder="Ej: FOR-GDI-GDO-002"
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="create-version">Versión *</Label>
                                        <Input
                                            id="create-version"
                                            type="number"
                                            min="1"
                                            value={createForm.version}
                                            onChange={(e) => setCreateForm({...createForm, version: parseInt(e.target.value) || 1})}
                                            placeholder="01"
                                            required
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create-nombre">Nombre de la TRD *</Label>
                                    <Input
                                        id="create-nombre"
                                        type="text"
                                        value={createForm.nombre}
                                        onChange={(e) => setCreateForm({...createForm, nombre: e.target.value})}
                                        placeholder="Ej: TABLAS DE RETENCIÓN DOCUMENTAL"
                                        required
                                    />
                                </div>

                                {/* UNIDAD ADMINISTRATIVA - Según formato oficial */}
                                <div className="p-3 bg-gray-50 rounded-lg border space-y-3">
                                    <p className="text-sm font-semibold text-gray-700">📁 Unidad Administrativa</p>
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="create-codigo-unidad">Código *</Label>
                                            <Input
                                                id="create-codigo-unidad"
                                                type="text"
                                                value={createForm.codigo_unidad_administrativa}
                                                onChange={(e) => setCreateForm({...createForm, codigo_unidad_administrativa: e.target.value})}
                                                placeholder="Ej: 110"
                                                required
                                            />
                                        </div>
                                        <div className="space-y-2 md:col-span-3">
                                            <Label htmlFor="create-nombre-unidad">Nombre *</Label>
                                            <Input
                                                id="create-nombre-unidad"
                                                type="text"
                                                value={createForm.nombre_unidad_administrativa}
                                                onChange={(e) => setCreateForm({...createForm, nombre_unidad_administrativa: e.target.value})}
                                                placeholder="Ej: SUBGERENCIA DE SERVICIOS DE SALUD"
                                                required
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* DEPENDENCIA PRODUCTORA - Según formato oficial */}
                                <div className="p-3 bg-gray-50 rounded-lg border space-y-3">
                                    <p className="text-sm font-semibold text-gray-700">🏢 Dependencia Productora</p>
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="create-codigo-dependencia">Código</Label>
                                            <Input
                                                id="create-codigo-dependencia"
                                                type="text"
                                                value={createForm.codigo_dependencia}
                                                onChange={(e) => setCreateForm({...createForm, codigo_dependencia: e.target.value})}
                                                placeholder="Ej: 111"
                                            />
                                        </div>
                                        <div className="space-y-2 md:col-span-3">
                                            <Label htmlFor="create-nombre-dependencia">Nombre</Label>
                                            <Input
                                                id="create-nombre-dependencia"
                                                type="text"
                                                value={createForm.nombre_dependencia}
                                                onChange={(e) => setCreateForm({...createForm, nombre_dependencia: e.target.value})}
                                                placeholder="Ej: OFICINA COORDINADORA DE URGENCIAS Y EMERGENCIAS"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create-descripcion">Descripción</Label>
                                    <Textarea
                                        id="create-descripcion"
                                        value={createForm.descripcion}
                                        onChange={(e) => setCreateForm({...createForm, descripcion: e.target.value})}
                                        placeholder="Descripción de la TRD"
                                        rows={2}
                                    />
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="create-fecha-aprobacion">Fecha de Emisión</Label>
                                        <Input
                                            id="create-fecha-aprobacion"
                                            type="date"
                                            value={createForm.fecha_aprobacion}
                                            onChange={(e) => setCreateForm({...createForm, fecha_aprobacion: e.target.value})}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="create-fecha-vigencia-inicio">Fecha Vigencia Inicio</Label>
                                        <Input
                                            id="create-fecha-vigencia-inicio"
                                            type="date"
                                            value={createForm.fecha_vigencia_inicio}
                                            onChange={(e) => setCreateForm({...createForm, fecha_vigencia_inicio: e.target.value})}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="create-fecha-vigencia-fin">Fecha Vigencia Fin</Label>
                                        <Input
                                            id="create-fecha-vigencia-fin"
                                            type="date"
                                            value={createForm.fecha_vigencia_fin}
                                            onChange={(e) => setCreateForm({...createForm, fecha_vigencia_fin: e.target.value})}
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create-observaciones">Observaciones Generales</Label>
                                    <Textarea
                                        id="create-observaciones"
                                        value={createForm.observaciones_generales}
                                        onChange={(e) => setCreateForm({...createForm, observaciones_generales: e.target.value})}
                                        placeholder="Observaciones adicionales"
                                        rows={2}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create-estado">Estado Inicial</Label>
                                    <Select value={createForm.estado} onValueChange={(value) => setCreateForm({...createForm, estado: value})}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar estado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(estados)
                                                .filter(([key]) => key !== 'vigente')
                                                .map(([key, label]) => (
                                                    <SelectItem key={key} value={key}>{label}</SelectItem>
                                                ))}
                                        </SelectContent>
                                    </Select>
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
                                        Crear TRD
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total TRDs</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{safeStats.total}</p>
                                <p className="text-xs text-gray-400">Tablas registradas</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <FileText className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Vigentes</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{safeStats.vigentes}</p>
                                <p className="text-xs text-gray-400">En funcionamiento</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <ToggleRight className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Borradores</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{safeStats.borradores}</p>
                                <p className="text-xs text-gray-400">En elaboración</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <Edit className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Aprobadas</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{safeStats.aprobadas}</p>
                                <p className="text-xs text-gray-400">Listas para uso</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <CheckCircle className="h-6 w-6 text-[#2a3d83]" />
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
                                    placeholder="Buscar TRDs..."
                                    className="pl-10"
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Select value={estadoFilter || "all"} onValueChange={(value) => setEstadoFilter(value === "all" ? "" : value)}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    {Object.entries(estados).map(([key, value]) => (
                                        <SelectItem key={key} value={key}>
                                            {value}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={vigenciaFilter || "all"} onValueChange={(value) => setVigenciaFilter(value === "all" ? "" : value)}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Todas las vigencias" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas las vigencias</SelectItem>
                                    <SelectItem value="true">Vigentes</SelectItem>
                                    <SelectItem value="false">No vigentes</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                {/* TRDs Table */}
                <div className="bg-white rounded-lg border overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b">
                                <tr>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">TRD</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">CCD Asociado</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Estado</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Vigencia</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Series</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Actualización</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {safeTrds.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="py-8 px-6 text-center text-gray-500">
                                            No se encontraron TRDs.
                                        </td>
                                    </tr>
                                ) : (
                                    safeTrds.data.map((trd) => (
                                        <tr key={trd.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="py-4 px-6">
                                                <div>
                                                    <div className="font-medium text-gray-900">{trd.nombre}</div>
                                                    <div className="text-sm text-gray-500">
                                                        {trd.codigo} • v{trd.version}
                                                    </div>
                                                    <div className="text-xs text-gray-400">
                                                        {trd.identificador_unico}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="py-4 px-6">
                                                {trd.cuadro_clasificacion ? (
                                                    <Link 
                                                        href={`/admin/ccd/${trd.cuadro_clasificacion.id}`}
                                                        className="block hover:bg-blue-50 rounded p-1 -m-1 transition-colors"
                                                    >
                                                        <div className="text-sm text-[#2a3d83] font-medium hover:underline">
                                                            {trd.cuadro_clasificacion.codigo}
                                                        </div>
                                                        <div className="text-xs text-gray-500 line-clamp-1">
                                                            {trd.cuadro_clasificacion.nombre}
                                                        </div>
                                                    </Link>
                                                ) : (
                                                    <span className="text-sm text-gray-400 italic">Sin CCD</span>
                                                )}
                                            </td>
                                            <td className="py-4 px-6">
                                                {getEstadoBadge(trd.estado)}
                                            </td>
                                            <td className="py-4 px-6">
                                                {trd.vigente ? (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Vigente
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Inactiva
                                                    </span>
                                                )}
                                            </td>
                                            <td className="py-4 px-6 text-gray-600">
                                                {trd.series_count || 0}
                                            </td>
                                            <td className="py-4 px-6 text-gray-600">
                                                {formatDate(trd.updated_at)}
                                            </td>
                                            <td className="py-4 px-6">
                                                <TooltipProvider>
                                                    <div className="flex items-center gap-2">
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Link
                                                                    href={`/admin/trd/${trd.id}`}
                                                                    className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors inline-flex"
                                                                >
                                                                    <Eye className="h-4 w-4" />
                                                                </Link>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Ver estructura y tiempos de retención</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <button
                                                                    onClick={() => setShowEditModal(trd)}
                                                                    className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors"
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Editar TRD</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <button
                                                                    onClick={() => setShowImportModal(trd)}
                                                                    className="p-2 rounded-md text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 transition-colors"
                                                                >
                                                                    <Upload className="h-4 w-4" />
                                                                </button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Importar Series (Excel/CSV)</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <button
                                                                    onClick={() => handleExportPDF(trd)}
                                                                    className="p-2 rounded-md text-red-500 hover:text-red-700 hover:bg-red-50 transition-colors"
                                                                >
                                                                    <FileText className="h-4 w-4" />
                                                                </button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Exportar PDF (Formato Oficial)</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <button
                                                                    onClick={() => handleDuplicate(trd)}
                                                                    className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors"
                                                                >
                                                                    <Copy className="h-4 w-4" />
                                                                </button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Duplicar TRD</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <button
                                                                    onClick={() => handleToggleVigencia(trd)}
                                                                    className={`p-2 rounded-md transition-colors ${
                                                                        trd.estado === 'vigente'
                                                                            ? 'text-orange-600 hover:text-orange-800 hover:bg-orange-50' 
                                                                            : 'text-green-600 hover:text-green-800 hover:bg-green-50'
                                                                    }`}
                                                                >
                                                                    {trd.estado === 'vigente' ? (
                                                                        <ToggleLeft className="h-4 w-4" />
                                                                    ) : (
                                                                        <ToggleRight className="h-4 w-4" />
                                                                    )}
                                                                </button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{trd.estado === 'vigente' ? 'Desactivar vigencia' : 'Activar vigencia'}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <button
                                                                    onClick={() => setShowDeleteModal(trd)}
                                                                    className="p-2 rounded-md text-red-600 hover:text-red-800 hover:bg-red-50 transition-colors"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Eliminar TRD</p>
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
                {safeTrds.data.length > 0 && (
                    <div className="flex items-center justify-between bg-white border rounded-lg px-6 py-3">
                        <div className="text-sm text-gray-600">
                            Mostrando <span className="font-medium">{safeTrds.from || 0}</span> a{' '}
                            <span className="font-medium">{safeTrds.to || 0}</span> de{' '}
                            <span className="font-medium">{safeTrds.total || 0}</span> TRDs
                        </div>
                        <div className="flex items-center gap-2">
                            {safeTrds.links.map((link, index) => {
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

                {/* Delete Confirmation Modal */}
                <Dialog open={!!showDeleteModal} onOpenChange={(open) => {
                    if (!open) setShowDeleteModal(null);
                }}>
                    <DialogContent className="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle className="text-xl font-semibold text-gray-900">Eliminar TRD</DialogTitle>
                            <DialogDescription className="text-sm text-gray-600">
                                Esta acción no se puede deshacer. La TRD será eliminada permanentemente del sistema.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="py-4">
                            <p className="text-gray-700">
                                ¿Estás seguro de que deseas eliminar la TRD <strong>{showDeleteModal?.nombre}</strong>?
                            </p>
                            <p className="text-gray-600 mt-2">
                                Código: <strong>{showDeleteModal?.codigo}</strong>
                            </p>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShowDeleteModal(null)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                onClick={() => showDeleteModal && handleDelete(showDeleteModal)}
                            >
                                Eliminar TRD
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Edit TRD Modal */}
                <Dialog open={!!showEditModal} onOpenChange={(open) => {
                    if (!open) setShowEditModal(null);
                }}>
                    <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-scroll [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                        <DialogHeader>
                            <DialogTitle className="text-xl font-semibold text-gray-900">Editar TRD</DialogTitle>
                            <DialogDescription className="text-sm text-gray-600">
                                Modifique los datos de la Tabla de Retención Documental según sea necesario.
                            </DialogDescription>
                        </DialogHeader>
                        
                        <form onSubmit={(e) => {
                            e.preventDefault();
                            if (showEditModal) {
                                router.put(`/admin/trd/${showEditModal.id}`, editForm, {
                                    onSuccess: () => {
                                        setShowEditModal(null);
                                        toast.success('TRD actualizada exitosamente');
                                    },
                                    onError: (errors) => {
                                        Object.keys(errors).forEach(field => {
                                            const message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                                            toast.error(`Error en ${field}: ${message}`);
                                        });
                                    }
                                });
                            }
                        }} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="edit-codigo">Código *</Label>
                                    <Input
                                        id="edit-codigo"
                                        type="text"
                                        value={editForm.codigo}
                                        onChange={(e) => setEditForm({...editForm, codigo: e.target.value})}
                                        placeholder="Ej: TRD-001"
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="edit-entidad">Entidad *</Label>
                                    <Input
                                        id="edit-entidad"
                                        type="text"
                                        value={editForm.entidad}
                                        onChange={(e) => setEditForm({...editForm, entidad: e.target.value})}
                                        placeholder="Nombre de la entidad"
                                        required
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="edit-nombre">Nombre *</Label>
                                <Input
                                    id="edit-nombre"
                                    type="text"
                                    value={editForm.nombre}
                                    onChange={(e) => setEditForm({...editForm, nombre: e.target.value})}
                                    placeholder="Nombre de la TRD"
                                    required
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="edit-descripcion">Descripción *</Label>
                                <Textarea
                                    id="edit-descripcion"
                                    value={editForm.descripcion}
                                    onChange={(e) => setEditForm({...editForm, descripcion: e.target.value})}
                                    placeholder="Descripción de la TRD"
                                    rows={3}
                                    required
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="edit-dependencia">Dependencia</Label>
                                <Input
                                    id="edit-dependencia"
                                    type="text"
                                    value={editForm.dependencia}
                                    onChange={(e) => setEditForm({...editForm, dependencia: e.target.value})}
                                    placeholder="Dependencia responsable"
                                />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="edit-fecha-aprobacion">Fecha de Aprobación *</Label>
                                    <Input
                                        id="edit-fecha-aprobacion"
                                        type="date"
                                        value={editForm.fecha_aprobacion}
                                        onChange={(e) => setEditForm({...editForm, fecha_aprobacion: e.target.value})}
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="edit-fecha-vigencia-inicio">Fecha Vigencia Inicio *</Label>
                                    <Input
                                        id="edit-fecha-vigencia-inicio"
                                        type="date"
                                        value={editForm.fecha_vigencia_inicio}
                                        onChange={(e) => setEditForm({...editForm, fecha_vigencia_inicio: e.target.value})}
                                        required
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="edit-fecha-vigencia-fin">Fecha Vigencia Fin</Label>
                                    <Input
                                        id="edit-fecha-vigencia-fin"
                                        type="date"
                                        value={editForm.fecha_vigencia_fin}
                                        onChange={(e) => setEditForm({...editForm, fecha_vigencia_fin: e.target.value})}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="edit-estado">Estado *</Label>
                                    <Select value={editForm.estado} onValueChange={(value) => setEditForm({...editForm, estado: value})}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar estado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(estados)
                                                .filter(([key]) => key !== 'vigente') // Excluir "vigente" del select
                                                .map(([key, label]) => (
                                                    <SelectItem key={key} value={key}>{label}</SelectItem>
                                                ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="edit-observaciones">Observaciones Generales</Label>
                                <Textarea
                                    id="edit-observaciones"
                                    value={editForm.observaciones_generales}
                                    onChange={(e) => setEditForm({...editForm, observaciones_generales: e.target.value})}
                                    placeholder="Observaciones adicionales"
                                    rows={2}
                                />
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setShowEditModal(null)}
                                >
                                    Cancelar
                                </Button>
                                <Button
                                    type="submit"
                                    className="bg-[#2a3d83] hover:bg-[#1e2b5f]"
                                >
                                    Actualizar TRD
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Modal Ver Detalles TRD */}
                <Dialog open={!!showViewModal} onOpenChange={() => setShowViewModal(null)}>
                    <DialogContent className="sm:max-w-[800px] max-h-[80vh] overflow-y-scroll [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                        <DialogHeader>
                            <DialogTitle className="text-xl font-semibold text-gray-900">Detalles de TRD</DialogTitle>
                            <DialogDescription className="text-sm text-gray-600">
                                Información completa de la Tabla de Retención Documental.
                            </DialogDescription>
                        </DialogHeader>
                        
                        {showViewModal && (
                            <div className="space-y-6">
                                {/* Información Básica */}
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <h3 className="text-lg font-semibold text-[#2a3d83] mb-3">Información Básica</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <Label className="font-medium text-gray-700">Código</Label>
                                            <p className="text-gray-900 bg-white p-2 rounded border">{showViewModal.codigo}</p>
                                        </div>
                                        <div>
                                            <Label className="font-medium text-gray-700">Estado</Label>
                                            <div className="mt-1">
                                                {getEstadoBadge(showViewModal.estado)}
                                            </div>
                                        </div>
                                        <div className="md:col-span-2">
                                            <Label className="font-medium text-gray-700">Nombre</Label>
                                            <p className="text-gray-900 bg-white p-2 rounded border">{showViewModal.nombre}</p>
                                        </div>
                                        <div className="md:col-span-2">
                                            <Label className="font-medium text-gray-700">Descripción</Label>
                                            <p className="text-gray-900 bg-white p-3 rounded border min-h-[60px]">{showViewModal.descripcion}</p>
                                        </div>
                                    </div>
                                </div>

                                {/* Información Organizacional */}
                                <div className="bg-blue-50 p-4 rounded-lg">
                                    <h3 className="text-lg font-semibold text-[#2a3d83] mb-3">Información Organizacional</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <Label className="font-medium text-gray-700">Entidad</Label>
                                            <p className="text-gray-900 bg-white p-2 rounded border">{showViewModal.entidad}</p>
                                        </div>
                                        <div>
                                            <Label className="font-medium text-gray-700">Dependencia</Label>
                                            <p className="text-gray-900 bg-white p-2 rounded border">{showViewModal.dependencia || 'No especificada'}</p>
                                        </div>
                                    </div>
                                </div>

                                {/* Información de Versión y Vigencia */}
                                <div className="bg-green-50 p-4 rounded-lg">
                                    <h3 className="text-lg font-semibold text-[#2a3d83] mb-3">Versión y Vigencia</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <Label className="font-medium text-gray-700">Versión</Label>
                                            <p className="text-gray-900 bg-white p-2 rounded border font-semibold">{showViewModal.version}</p>
                                        </div>
                                        <div>
                                            <Label className="font-medium text-gray-700">Fecha Aprobación</Label>
                                            <p className="text-gray-900 bg-white p-2 rounded border">{formatDate(showViewModal.fecha_aprobacion)}</p>
                                        </div>
                                        <div>
                                            <Label className="font-medium text-gray-700">Estado Vigencia</Label>
                                            <div className="mt-1">
                                                {showViewModal.vigente ? (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Vigente
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        No Vigente
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        <div>
                                            <Label className="font-medium text-gray-700">Fecha Vigencia Inicio</Label>
                                            <p className="text-gray-900 bg-white p-2 rounded border">{formatDate(showViewModal.fecha_vigencia_inicio)}</p>
                                        </div>
                                        <div>
                                            <Label className="font-medium text-gray-700">Fecha Vigencia Fin</Label>
                                            <p className="text-gray-900 bg-white p-2 rounded border">{showViewModal.fecha_vigencia_fin ? formatDate(showViewModal.fecha_vigencia_fin) : 'No definida'}</p>
                                        </div>
                                        <div>
                                            <Label className="font-medium text-gray-700">Series Documentales</Label>
                                            <p className="text-gray-900 bg-white p-2 rounded border font-semibold">{showViewModal.series_count || 0}</p>
                                        </div>
                                    </div>
                                </div>

                                {/* Observaciones */}
                                {showViewModal.observaciones_generales && (
                                    <div className="bg-yellow-50 p-4 rounded-lg">
                                        <h3 className="text-lg font-semibold text-[#2a3d83] mb-3">Observaciones Generales</h3>
                                        <p className="text-gray-900 bg-white p-3 rounded border whitespace-pre-wrap">{showViewModal.observaciones_generales}</p>
                                    </div>
                                )}

                                {/* Información de Sistema */}
                                <div className="bg-gray-100 p-4 rounded-lg">
                                    <h3 className="text-lg font-semibold text-[#2a3d83] mb-3">Información de Sistema</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <Label className="font-medium text-gray-700">Identificador Único</Label>
                                            <p className="text-gray-900 bg-white p-2 rounded border font-mono text-sm">{showViewModal.identificador_unico || 'No asignado'}</p>
                                        </div>
                                        <div>
                                            <Label className="font-medium text-gray-700">Formato de Archivo</Label>
                                            <p className="text-gray-900 bg-white p-2 rounded border">{showViewModal.formato_archivo || 'No especificado'}</p>
                                        </div>
                                        <div>
                                            <Label className="font-medium text-gray-700">Fecha de Creación</Label>
                                            <p className="text-gray-900 bg-white p-2 rounded border">{formatDate(showViewModal.created_at)}</p>
                                        </div>
                                        <div>
                                            <Label className="font-medium text-gray-700">Última Modificación</Label>
                                            <p className="text-gray-900 bg-white p-2 rounded border">{formatDate(showViewModal.updated_at)}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShowViewModal(null)}
                            >
                                Cerrar
                            </Button>
                            <Button
                                type="button"
                                onClick={() => setShowEditModal(showViewModal)}
                                className="bg-[#2a3d83] hover:bg-[#1e2b5f]"
                            >
                                Editar TRD
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Modal de Importación de Series */}
                <Dialog open={showImportModal !== null} onOpenChange={(open) => !open && setShowImportModal(null)}>
                    <DialogContent className="sm:max-w-[500px]">
                        <DialogHeader>
                            <DialogTitle className="text-xl font-semibold text-gray-900 flex items-center gap-2">
                                <FileSpreadsheet className="h-5 w-5 text-emerald-600" />
                                Importar Series Documentales
                            </DialogTitle>
                            <DialogDescription className="text-sm text-gray-600">
                                Importe series documentales desde un archivo Excel (.xlsx, .xls) o CSV para la TRD: <strong>{showImportModal?.nombre}</strong>
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4 py-4">
                            {/* Zona de carga de archivo */}
                            <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-emerald-400 transition-colors">
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
                                    onChange={(e) => {
                                        const file = e.target.files?.[0];
                                        if (file) {
                                            // Validar tamaño (max 10MB)
                                            if (file.size > 10 * 1024 * 1024) {
                                                toast.error('El archivo no debe superar 10MB');
                                                e.target.value = '';
                                                return;
                                            }
                                            setImportFile(file);
                                        }
                                    }}
                                    className="hidden"
                                    id="import-file-input"
                                />
                                <label
                                    htmlFor="import-file-input"
                                    className="cursor-pointer"
                                >
                                    <Upload className="mx-auto h-12 w-12 text-gray-400 mb-3" />
                                    <p className="text-sm text-gray-600 mb-2">
                                        <span className="font-semibold text-emerald-600">Haga clic para seleccionar</span> o arrastre el archivo aquí
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        Formatos soportados: Excel (.xlsx, .xls) o CSV (máx. 10MB)
                                    </p>
                                </label>
                            </div>

                            {/* Archivo seleccionado */}
                            {importFile && (
                                <div className="bg-emerald-50 border border-emerald-200 rounded-lg p-3 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <FileSpreadsheet className="h-5 w-5 text-emerald-600" />
                                        <div>
                                            <p className="text-sm font-medium text-emerald-900">{importFile.name}</p>
                                            <p className="text-xs text-emerald-600">
                                                {(importFile.size / 1024).toFixed(2)} KB
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        onClick={() => {
                                            setImportFile(null);
                                            if (fileInputRef.current) {
                                                fileInputRef.current.value = '';
                                            }
                                        }}
                                        className="text-emerald-600 hover:text-emerald-800"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </div>
                            )}

                            {/* Descargar plantilla */}
                            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div className="flex items-start gap-3">
                                    <Download className="h-5 w-5 text-blue-600 mt-0.5" />
                                    <div>
                                        <p className="text-sm font-medium text-blue-900 mb-1">¿Necesita la plantilla?</p>
                                        <p className="text-xs text-blue-700 mb-2">
                                            Descargue la plantilla Excel con el formato correcto para importar series documentales.
                                        </p>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={handleDownloadTemplate}
                                            className="text-blue-700 border-blue-300 hover:bg-blue-100"
                                        >
                                            <Download className="h-4 w-4 mr-1" />
                                            Descargar Plantilla
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            {/* Información sobre columnas */}
                            <div className="text-xs text-gray-500 bg-gray-50 p-3 rounded-lg">
                                <p className="font-semibold mb-1">Columnas esperadas en el archivo:</p>
                                <ul className="list-disc list-inside space-y-0.5">
                                    <li>Código Serie, Nombre Serie (requeridos)</li>
                                    <li>Código Subserie, Nombre Subserie (opcionales)</li>
                                    <li>Soporte Físico (F), Soporte Electrónico (E)</li>
                                    <li>Retención Gestión, Retención Central (años)</li>
                                    <li>Disposición Final (CT, E, D, S)</li>
                                    <li>Procedimiento</li>
                                </ul>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    setShowImportModal(null);
                                    setImportFile(null);
                                    if (fileInputRef.current) {
                                        fileInputRef.current.value = '';
                                    }
                                }}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="button"
                                onClick={() => showImportModal && handleImportSeries(showImportModal)}
                                disabled={!importFile || isImporting}
                                className="bg-emerald-600 hover:bg-emerald-700"
                            >
                                {isImporting ? (
                                    <>
                                        <span className="animate-spin mr-2">⏳</span>
                                        Importando...
                                    </>
                                ) : (
                                    <>
                                        <Upload className="h-4 w-4 mr-1" />
                                        Importar Series
                                    </>
                                )}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
