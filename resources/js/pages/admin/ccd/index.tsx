import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogDescription } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { 
    FileText, 
    Plus, 
    Search, 
    Eye, 
    Edit, 
    Trash2, 
    Copy, 
    Filter,
    TrendingUp,
    Users,
    CheckCircle,
    AlertCircle,
    ToggleLeft,
    ToggleRight,
    FolderTree
} from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { toast } from 'sonner';

interface CCD {
    id: number;
    codigo: string;
    nombre: string;
    descripcion?: string;
    entidad: string;  // Campo requerido agregado
    dependencia?: string;  // Campo agregado
    nivel: number;
    padre_id?: number;
    orden_jerarquico: number;  // Nombre corregido
    estado: 'borrador' | 'activo' | 'inactivo' | 'historico';
    activo: boolean;
    vocabularios_controlados?: any;  // Nombre corregido
    notas?: string;  // Campo agregado
    alcance?: string;  // Campo agregado
    razon_reubicacion?: string;  // Campo agregado
    fecha_reubicacion?: string;  // Campo agregado
    reubicado_por?: string;  // Campo agregado
    created_by?: number;  // Nombre corregido
    updated_by?: number;  // Nombre corregido
    created_at: string;
    updated_at: string;
    deleted_at?: string;  // Campo agregado
    padre?: CCD;
    hijos?: CCD[];
    creador?: {  // Corregido: usar nombre real de la relación del backend
        id: number;
        name: string;
    };
}

interface CCDIndexProps {
    data: {
        data: CCD[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: any[];
    };
    estadisticas: {
        total: number;
        activos: number;
        borradores: number;
        vigentes: number;
    };
    opciones: {
        estados: { value: string; label: string; }[];
        niveles: { value: string; label: string; }[];
    };
    filtros: {
        search?: string;
        estado?: string;
        nivel?: string;
        activo?: string;
    };
}

export default function CCDIndex({ data, estadisticas, opciones, filtros }: CCDIndexProps) {
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showViewModal, setShowViewModal] = useState<CCD | null>(null);
    const [showDeleteModal, setShowDeleteModal] = useState<CCD | null>(null);
    const [selectedCCD, setSelectedCCD] = useState<CCD | null>(null);
    const [loading, setLoading] = useState(false);

    const [searchTerm, setSearchTerm] = useState(filtros.search || '');
    const [filterEstado, setFilterEstado] = useState(filtros.estado || 'all');
    const [filterNivel, setFilterNivel] = useState(filtros.nivel || 'all');
    const [filterActivo, setFilterActivo] = useState(filtros.activo || 'all');

    const breadcrumbItems = [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Cuadros de Clasificación Documental', href: '/admin/ccd' }
    ];

    const [createForm, setCreateForm] = useState({
        codigo: '',
        nombre: '',
        descripcion: '',
        entidad: '',  // Campo requerido agregado
        dependencia: '',  // Campo agregado
        nivel: 1,
        padre_id: '',
        orden_jerarquico: 0,  // Nombre corregido
        estado: 'borrador',
        activo: true,
        vocabularios_controlados: [],  // Nombre corregido
        notas: '',  // Campo agregado
        alcance: '',  // Campo agregado
        razon_reubicacion: '',  // Campo agregado
        fecha_reubicacion: '',  // Campo agregado
        reubicado_por: ''  // Campo agregado
    });

    const [editForm, setEditForm] = useState({
        codigo: '',
        nombre: '',
        descripcion: '',
        entidad: '',  // Campo requerido agregado
        dependencia: '',  // Campo agregado
        nivel: 1,
        padre_id: '',
        orden_jerarquico: 0,  // Nombre corregido
        estado: 'borrador',
        activo: true,
        vocabularios_controlados: [],  // Nombre corregido
        notas: '',  // Campo agregado
        alcance: '',  // Campo agregado
        razon_reubicacion: '',  // Campo agregado
        fecha_reubicacion: '',  // Campo agregado
        reubicado_por: ''  // Campo agregado
    });

    const handleSearch = () => {
        const params = new URLSearchParams();
        if (searchTerm) params.append('search', searchTerm);
        if (filterEstado !== 'all') params.append('estado', filterEstado);
        if (filterNivel !== 'all') params.append('nivel', filterNivel);
        if (filterActivo !== 'all') params.append('activo', filterActivo);
        
        router.get(`/admin/ccd?${params.toString()}`);
    };

    const resetFilters = () => {
        setSearchTerm('');
        setFilterEstado('all');
        setFilterNivel('all');
        setFilterActivo('all');
        router.get('/admin/ccd');
    };

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);

        router.post('/admin/ccd', createForm, {
            onSuccess: () => {
                setShowCreateModal(false);
                setCreateForm({
                    codigo: '',
                    nombre: '',
                    descripcion: '',
                    entidad: '',  // Campo requerido agregado
                    dependencia: '',  // Campo agregado
                    nivel: 1,
                    padre_id: '',
                    orden_jerarquico: 0,  // Nombre corregido
                    estado: 'borrador',
                    activo: true,
                    vocabularios_controlados: [],  // Nombre corregido
                    notas: '',  // Campo agregado
                    alcance: '',  // Campo agregado
                    razon_reubicacion: '',  // Campo agregado
                    fecha_reubicacion: '',  // Campo agregado
                    reubicado_por: ''  // Campo agregado
                });
                toast.success('CCD creado exitosamente');
            },
            onError: (errors) => {
                console.error('Error creating CCD:', errors);
                toast.error('Error al crear el CCD');
            },
            onFinish: () => setLoading(false)
        });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedCCD) return;

        setLoading(true);
        router.put(`/admin/ccd/${selectedCCD.id}`, editForm, {
            onSuccess: () => {
                setShowEditModal(false);
                setSelectedCCD(null);
                toast.success('CCD actualizado exitosamente');
            },
            onError: (errors) => {
                console.error('Error updating CCD:', errors);
                toast.error('Error al actualizar el CCD');
            },
            onFinish: () => setLoading(false)
        });
    };

    const handleView = (ccd: CCD) => {
        setShowViewModal(ccd);
    };

    const handleEdit = (ccd: CCD) => {
        setSelectedCCD(ccd);
        setEditForm({
            codigo: ccd.codigo,
            nombre: ccd.nombre,
            descripcion: ccd.descripcion || '',
            entidad: ccd.entidad || '',  // Campo agregado
            dependencia: ccd.dependencia || '',  // Campo agregado
            nivel: ccd.nivel,
            padre_id: ccd.padre_id?.toString() || '',
            orden_jerarquico: ccd.orden_jerarquico || 0,  // Nombre corregido
            estado: ccd.estado,
            activo: ccd.activo,
            vocabularios_controlados: ccd.vocabularios_controlados || [],  // Nombre corregido
            notas: ccd.notas || '',  // Campo agregado
            alcance: ccd.alcance || '',  // Campo agregado
            razon_reubicacion: ccd.razon_reubicacion || '',  // Campo agregado
            fecha_reubicacion: ccd.fecha_reubicacion || '',  // Campo agregado
            reubicado_por: ccd.reubicado_por || ''  // Campo agregado
        });
        setShowEditModal(true);
    };

    const handleDelete = () => {
        if (!showDeleteModal) return;

        router.delete(`/admin/ccd/${showDeleteModal.id}`, {
            onSuccess: () => {
                toast.success('CCD eliminado exitosamente');
                setShowDeleteModal(null);
            },
            onError: () => toast.error('Error al eliminar el CCD')
        });
    };

    const handleDuplicate = (ccd: CCD) => {
        router.post(`/admin/ccd/${ccd.id}/duplicate`, {}, {
            onSuccess: () => toast.success('CCD duplicado exitosamente'),
            onError: () => toast.error('Error al duplicar el CCD')
        });
    };

    const handleToggleActive = (ccd: CCD) => {
        router.patch(`/admin/ccd/${ccd.id}/toggle-active`, {}, {
            onSuccess: () => {
                const action = ccd.activo ? 'desactivado' : 'activado';
                toast.success(`CCD ${action} exitosamente`);
            },
            onError: () => toast.error('Error al cambiar el estado del CCD')
        });
    };

    const getEstadoBadge = (estado: string) => {
        const colors = {
            'borrador': 'bg-gray-100 text-gray-800',
            'activo': 'bg-green-100 text-green-800',
            'inactivo': 'bg-red-100 text-red-800',
            'historico': 'bg-yellow-100 text-yellow-800'
        };
        
        const labels = {
            'borrador': 'Borrador',
            'activo': 'Activo',
            'inactivo': 'Inactivo',
            'historico': 'Histórico'
        };
        
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colors[estado as keyof typeof colors] || 'bg-gray-100 text-gray-800'}`}>
                {labels[estado as keyof typeof labels] || estado}
            </span>
        );
    };

    const getNivelBadge = (nivel: number) => {
        const niveles = {
            1: { label: 'Fondo', color: 'bg-blue-100 text-[#2a3d83]' },
            2: { label: 'Sección', color: 'bg-purple-100 text-purple-800' },
            3: { label: 'Subsección', color: 'bg-indigo-100 text-indigo-800' },
            4: { label: 'Serie', color: 'bg-teal-100 text-teal-800' },
            5: { label: 'Subserie', color: 'bg-cyan-100 text-cyan-800' }
        };
        
        const nivelData = niveles[nivel as keyof typeof niveles] || { label: `Nivel ${nivel}`, color: 'bg-gray-100 text-gray-800' };
        
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${nivelData.color}`}>
                {nivelData.label}
            </span>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title="Cuadros de Clasificación Documental" />
            
            <div className="space-y-6">
                {/* Header with title and create button */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-3">
                        <FolderTree className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Cuadros de Clasificación Documental
                        </h1>
                    </div>
                    <Dialog open={showCreateModal} onOpenChange={setShowCreateModal}>
                        <DialogTrigger asChild>
                            <Button className="flex items-center gap-2 px-4 py-2 bg-[#2a3d83] text-white rounded-lg hover:bg-[#1e2b5f] transition-colors">
                                <Plus className="h-4 w-4" />
                                Nuevo CCD
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-scroll [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                            <DialogHeader>
                                <DialogTitle className="text-xl font-semibold text-gray-900">Crear Nuevo CCD</DialogTitle>
                                <DialogDescription className="text-sm text-gray-600">
                                    Complete los siguientes datos para crear un nuevo Cuadro de Clasificación Documental.
                                </DialogDescription>
                            </DialogHeader>
                            <form onSubmit={handleCreateSubmit} className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="codigo">Código *</Label>
                                        <Input
                                            id="codigo"
                                            value={createForm.codigo}
                                            onChange={(e) => setCreateForm({...createForm, codigo: e.target.value})}
                                            placeholder="Ej: F.01, S.01.01"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="nivel">Nivel *</Label>
                                        <Select 
                                            value={createForm.nivel.toString()} 
                                            onValueChange={(value) => setCreateForm({...createForm, nivel: parseInt(value)})}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar nivel" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {opciones.niveles.map((nivel) => (
                                                    <SelectItem key={nivel.value} value={nivel.value}>
                                                        {nivel.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                                
                                <div>
                                    <Label htmlFor="nombre">Nombre *</Label>
                                    <Input
                                        id="nombre"
                                        value={createForm.nombre}
                                        onChange={(e) => setCreateForm({...createForm, nombre: e.target.value})}
                                        placeholder="Nombre del CCD"
                                        required
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="descripcion">Descripción</Label>
                                    <Textarea
                                        id="descripcion"
                                        value={createForm.descripcion}
                                        onChange={(e) => setCreateForm({...createForm, descripcion: e.target.value})}
                                        placeholder="Descripción del CCD"
                                        rows={3}
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="entidad">Entidad *</Label>
                                        <Input
                                            id="entidad"
                                            value={createForm.entidad}
                                            onChange={(e) => setCreateForm({...createForm, entidad: e.target.value})}
                                            placeholder="Nombre de la entidad"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="dependencia">Dependencia</Label>
                                        <Input
                                            id="dependencia"
                                            value={createForm.dependencia}
                                            onChange={(e) => setCreateForm({...createForm, dependencia: e.target.value})}
                                            placeholder="Dependencia responsable"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="estado">Estado *</Label>
                                        <Select 
                                            value={createForm.estado} 
                                            onValueChange={(value) => setCreateForm({...createForm, estado: value as any})}
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {opciones.estados.map((estado) => (
                                                    <SelectItem key={estado.value} value={estado.value}>
                                                        {estado.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label htmlFor="orden_jerarquico">Orden Jerárquico</Label>
                                        <Input
                                            id="orden_jerarquico"
                                            type="number"
                                            value={createForm.orden_jerarquico}
                                            onChange={(e) => setCreateForm({...createForm, orden_jerarquico: parseInt(e.target.value) || 0})}
                                            min="0"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <Label htmlFor="notas">Notas</Label>
                                    <Textarea
                                        id="notas"
                                        value={createForm.notas}
                                        onChange={(e) => setCreateForm({...createForm, notas: e.target.value})}
                                        placeholder="Notas adicionales"
                                        rows={3}
                                    />
                                </div>

                                <div className="flex justify-end gap-2 pt-4">
                                    <Button 
                                        type="button" 
                                        variant="outline" 
                                        onClick={() => setShowCreateModal(false)}
                                    >
                                        Cancelar
                                    </Button>
                                    <Button 
                                        type="submit" 
                                        disabled={loading}
                                        className="bg-[#2a3d83] hover:bg-[#1e2b5f]"
                                    >
                                        {loading ? 'Creando...' : 'Crear CCD'}
                                    </Button>
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Total CCDs</p>
                                <p className="text-2xl font-bold text-gray-900">{estadisticas.total}</p>
                            </div>
                            <FolderTree className="h-8 w-8 text-[#2a3d83]" />
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Activos</p>
                                <p className="text-2xl font-bold text-[#2a3d83]">{estadisticas.activos}</p>
                            </div>
                            <CheckCircle className="h-8 w-8 text-[#2a3d83]" />
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Borradores</p>
                                <p className="text-2xl font-bold text-[#2a3d83]">{estadisticas.borradores}</p>
                            </div>
                            <FileText className="h-8 w-8 text-[#2a3d83]" />
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
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder="Buscar CCDs..."
                                    className="pl-10"
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Select value={filterEstado || "all"} onValueChange={(value) => setFilterEstado(value === "all" ? "" : value)}>
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
                                        CCD
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Entidad
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nivel
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Activo
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
                                        <td colSpan={7} className="px-6 py-8 text-center text-gray-500">
                                            <div className="flex flex-col items-center gap-2">
                                                <FolderTree className="h-8 w-8 text-gray-300" />
                                                <p>No se encontraron cuadros de clasificación documental</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    data.data.map((ccd) => (
                                        <tr key={ccd.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4">
                                                <div className="flex flex-col">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {ccd.codigo}
                                                    </div>
                                                    <div className="text-sm text-gray-600 line-clamp-2">
                                                        {ccd.nombre}
                                                    </div>
                                                    {ccd.descripcion && (
                                                        <div className="text-xs text-gray-500 mt-1 line-clamp-1">
                                                            {ccd.descripcion}
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="text-sm text-gray-900">
                                                    {ccd.entidad}
                                                </div>
                                                {ccd.dependencia && (
                                                    <div className="text-xs text-gray-500 line-clamp-1">
                                                        {ccd.dependencia}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                {getNivelBadge(ccd.nivel)}
                                            </td>
                                            <td className="px-6 py-4">
                                                {getEstadoBadge(ccd.estado)}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                    ccd.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                }`}>
                                    {ccd.activo ? 'Sí' : 'No'}
                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="text-sm text-gray-900">
                                                    {new Date(ccd.created_at).toLocaleDateString('es-ES')}
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
                                                                    onClick={() => handleView(ccd)}
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
                                                                    onClick={() => handleEdit(ccd)}
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
                                                                    onClick={() => handleDuplicate(ccd)}
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
                                                                    onClick={() => handleToggleActive(ccd)}
                                                                    className={`${
                                                                        ccd.activo 
                                                                            ? 'text-orange-600 hover:text-orange-800 hover:bg-orange-50' 
                                                                            : 'text-green-600 hover:text-green-800 hover:bg-green-50'
                                                                    }`}
                                                                >
                                                                    {ccd.activo ? (
                                                                        <ToggleLeft className="h-4 w-4" />
                                                                    ) : (
                                                                        <ToggleRight className="h-4 w-4" />
                                                                    )}
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                {ccd.activo ? 'Desactivar' : 'Activar'}
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => setShowDeleteModal(ccd)}
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
                        
                        {/* Pagination would go here if needed */}
                        {data.last_page > 1 && (
                            <div className="flex items-center justify-between px-4 py-3 border-t">
                                <div className="text-sm text-gray-700">
                                    Mostrando {((data.current_page - 1) * data.per_page) + 1} a{' '}
                                    {Math.min(data.current_page * data.per_page, data.total)} de {data.total} resultados
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Modal de Edición */}
            <Dialog open={showEditModal} onOpenChange={(open) => { if (!open) { setShowEditModal(false); setSelectedCCD(null); } }}>
                <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-scroll [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                    <DialogHeader>
                        <DialogTitle className="text-xl font-semibold text-gray-900">Editar CCD</DialogTitle>
                        <DialogDescription className="text-sm text-gray-600">
                            Modifique los datos del Cuadro de Clasificación Documental.
                        </DialogDescription>
                    </DialogHeader>
                    {selectedCCD && (
                        <form onSubmit={handleEditSubmit} className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="edit-codigo">Código *</Label>
                                    <Input
                                        id="edit-codigo"
                                        value={editForm.codigo}
                                        onChange={(e) => setEditForm({...editForm, codigo: e.target.value})}
                                        placeholder="Ej: F.01, S.01.01"
                                        required
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="edit-nivel">Nivel *</Label>
                                    <Select 
                                        value={editForm.nivel.toString()} 
                                        onValueChange={(value) => setEditForm({...editForm, nivel: parseInt(value)})}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar nivel" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {opciones.niveles.map((nivel) => (
                                                <SelectItem key={nivel.value} value={nivel.value}>
                                                    {nivel.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            
                            <div>
                                <Label htmlFor="edit-nombre">Nombre *</Label>
                                <Input
                                    id="edit-nombre"
                                    value={editForm.nombre}
                                    onChange={(e) => setEditForm({...editForm, nombre: e.target.value})}
                                    placeholder="Nombre del CCD"
                                    required
                                />
                            </div>

                            <div>
                                <Label htmlFor="edit-descripcion">Descripción</Label>
                                <Textarea
                                    id="edit-descripcion"
                                    value={editForm.descripcion}
                                    onChange={(e) => setEditForm({...editForm, descripcion: e.target.value})}
                                    placeholder="Descripción del CCD"
                                    rows={3}
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="edit-estado">Estado *</Label>
                                    <Select 
                                        value={editForm.estado} 
                                        onValueChange={(value) => setEditForm({...editForm, estado: value as any})}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {opciones.estados.map((estado) => (
                                                <SelectItem key={estado.value} value={estado.value}>
                                                    {estado.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label htmlFor="edit-orden_jerarquico">Orden Jerárquico</Label>
                                    <Input
                                        id="edit-orden_jerarquico"
                                        type="number"
                                        value={editForm.orden_jerarquico}
                                        onChange={(e) => setEditForm({...editForm, orden_jerarquico: parseInt(e.target.value) || 0})}
                                        min="0"
                                    />
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="edit-notas">Notas</Label>
                                <Textarea
                                    id="edit-notas"
                                    value={editForm.notas}
                                    onChange={(e) => setEditForm({...editForm, notas: e.target.value})}
                                    placeholder="Notas adicionales"
                                    rows={3}
                                />
                            </div>

                            <div className="flex justify-end gap-2 pt-4">
                                <Button 
                                    type="button" 
                                    variant="outline" 
                                    onClick={() => setShowEditModal(false)}
                                >
                                    Cancelar
                                </Button>
                                <Button 
                                    type="submit" 
                                    disabled={loading}
                                    className="bg-[#2a3d83] hover:bg-[#1e2b5f]"
                                >
                                    {loading ? 'Actualizando...' : 'Actualizar CCD'}
                                </Button>
                            </div>
                        </form>
                    )}
                </DialogContent>
            </Dialog>

            {/* View Modal */}
            {showViewModal && (
                <Dialog open={!!showViewModal} onOpenChange={() => setShowViewModal(null)}>
                    <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-scroll [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                        <DialogHeader>
                            <DialogTitle>Detalles del CCD</DialogTitle>
                            <DialogDescription>
                                Información completa del Cuadro de Clasificación Documental.
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
                                    <div className="mt-1">{getEstadoBadge(showViewModal.estado)}</div>
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
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Entidad</Label>
                                    <p className="text-sm text-gray-900">{showViewModal.entidad}</p>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Dependencia</Label>
                                    <p className="text-sm text-gray-900">{showViewModal.dependencia || 'No especificada'}</p>
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Nivel</Label>
                                    <div className="mt-1">{getNivelBadge(showViewModal.nivel)}</div>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Activo</Label>
                                    <div className="mt-1">
                                        <Badge variant={showViewModal.activo ? "default" : "secondary"} className={showViewModal.activo ? "bg-green-500" : ""}>
                                            {showViewModal.activo ? 'Sí' : 'No'}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                            {showViewModal.notas && (
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Notas</Label>
                                    <p className="text-sm text-gray-900">{showViewModal.notas}</p>
                                </div>
                            )}
                            <div>
                                <Label className="text-sm font-medium text-gray-500">Fecha de Creación</Label>
                                <p className="text-sm text-gray-900">{new Date(showViewModal.created_at).toLocaleDateString('es-ES', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric'
                                })}</p>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>
            )}

            {/* Delete Modal */}
            {showDeleteModal && (
                <Dialog open={!!showDeleteModal} onOpenChange={() => setShowDeleteModal(null)}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Confirmar Eliminación</DialogTitle>
                            <DialogDescription>
                                ¿Está seguro de que desea eliminar el CCD "{showDeleteModal.nombre}"? Esta acción no se puede deshacer.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="flex items-center space-x-2">
                            <div className="grid flex-1 gap-2">
                                <p className="text-sm text-gray-600">
                                    <strong>Código:</strong> {showDeleteModal.codigo}
                                </p>
                            </div>
                        </div>
                        <div className="flex justify-end gap-3">
                            <Button
                                variant="outline"
                                onClick={() => setShowDeleteModal(null)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                variant="destructive"
                                onClick={handleDelete}
                                disabled={loading}
                            >
                                Eliminar
                            </Button>
                        </div>
                    </DialogContent>
                </Dialog>
            )}
        </AppLayout>
    );
}
