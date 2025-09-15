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
    Power,
    PowerOff,
    FolderTree
} from 'lucide-react';
import { toast } from 'sonner';

interface CCD {
    id: number;
    codigo: string;
    nombre: string;
    descripcion?: string;
    nivel: number;
    padre_id?: number;
    orden: number;
    estado: 'borrador' | 'activo' | 'inactivo' | 'historico';
    activo: boolean;
    observaciones?: string;
    vocabulario_controlado?: any;
    metadatos?: any;
    usuario_creador_id?: number;
    usuario_modificador_id?: number;
    created_at: string;
    updated_at: string;
    padre?: CCD;
    hijos?: CCD[];
    usuario_creador?: {
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
    const [showViewModal, setShowViewModal] = useState(false);
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
        nivel: 1,
        padre_id: '',
        orden: 0,
        estado: 'borrador',
        activo: true,
        observaciones: '',
        vocabulario_controlado: {},
        metadatos: {}
    });

    const [editForm, setEditForm] = useState({
        codigo: '',
        nombre: '',
        descripcion: '',
        nivel: 1,
        padre_id: '',
        orden: 0,
        estado: 'borrador',
        activo: true,
        observaciones: '',
        vocabulario_controlado: {},
        metadatos: {}
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
                    nivel: 1,
                    padre_id: '',
                    orden: 0,
                    estado: 'borrador',
                    activo: true,
                    observaciones: '',
                    vocabulario_controlado: {},
                    metadatos: {}
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
        setSelectedCCD(ccd);
        setShowViewModal(true);
    };

    const handleEdit = (ccd: CCD) => {
        setSelectedCCD(ccd);
        setEditForm({
            codigo: ccd.codigo,
            nombre: ccd.nombre,
            descripcion: ccd.descripcion || '',
            nivel: ccd.nivel,
            padre_id: ccd.padre_id?.toString() || '',
            orden: ccd.orden,
            estado: ccd.estado,
            activo: ccd.activo,
            observaciones: ccd.observaciones || '',
            vocabulario_controlado: ccd.vocabulario_controlado || {},
            metadatos: ccd.metadatos || {}
        });
        setShowEditModal(true);
    };

    const handleDelete = (ccd: CCD) => {
        if (!confirm('¿Está seguro de eliminar este CCD? Esta acción no se puede deshacer.')) {
            return;
        }

        router.delete(`/admin/ccd/${ccd.id}`, {
            onSuccess: () => toast.success('CCD eliminado exitosamente'),
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
            'borrador': 'bg-gray-500',
            'activo': 'bg-green-500',
            'inactivo': 'bg-red-500',
            'historico': 'bg-yellow-500'
        };
        
        const labels = {
            'borrador': 'Borrador',
            'activo': 'Activo',
            'inactivo': 'Inactivo',
            'historico': 'Histórico'
        };
        
        return (
            <Badge variant="default" className={colors[estado as keyof typeof colors] || 'bg-gray-500'}>
                {labels[estado as keyof typeof labels] || estado}
            </Badge>
        );
    };

    const getNivelBadge = (nivel: number) => {
        const niveles = {
            1: { label: 'Fondo', color: 'bg-blue-500' },
            2: { label: 'Sección', color: 'bg-purple-500' },
            3: { label: 'Subsección', color: 'bg-indigo-500' },
            4: { label: 'Serie', color: 'bg-teal-500' },
            5: { label: 'Subserie', color: 'bg-cyan-500' }
        };
        
        const nivelData = niveles[nivel as keyof typeof niveles] || { label: `Nivel ${nivel}`, color: 'bg-gray-500' };
        
        return (
            <Badge variant="default" className={nivelData.color}>
                {nivelData.label}
            </Badge>
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
                                        <Label htmlFor="orden">Orden</Label>
                                        <Input
                                            id="orden"
                                            type="number"
                                            value={createForm.orden}
                                            onChange={(e) => setCreateForm({...createForm, orden: parseInt(e.target.value) || 0})}
                                            min="0"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <Label htmlFor="observaciones">Observaciones</Label>
                                    <Textarea
                                        id="observaciones"
                                        value={createForm.observaciones}
                                        onChange={(e) => setCreateForm({...createForm, observaciones: e.target.value})}
                                        placeholder="Observaciones adicionales"
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

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-semibold text-gray-600">Total CCDs</p>
                                <p className="text-2xl font-semibold text-gray-900">{estadisticas.total}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <FolderTree className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-semibold text-gray-600">CCDs Activos</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas.activos}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <CheckCircle className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-semibold text-gray-600">Borradores</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas.borradores}</p>
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

                {/* CCDs Table */}
                <div className="bg-white rounded-lg border overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b">
                                <tr>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Código</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Nombre</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Nivel</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Estado</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Activo</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Creado</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                    {data.data.length > 0 ? (
                                        data.data.map((ccd) => (
                                            <tr key={ccd.id} className="border-b hover:bg-gray-50">
                                                <td className="p-4">
                                                    <div className="font-mono text-sm">{ccd.codigo}</div>
                                                </td>
                                                <td className="p-4">
                                                    <div className="font-medium">{ccd.nombre}</div>
                                                    {ccd.descripcion && (
                                                        <div className="text-sm text-gray-500 truncate max-w-xs">
                                                            {ccd.descripcion}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="p-4">
                                                    {getNivelBadge(ccd.nivel)}
                                                </td>
                                                <td className="p-4">
                                                    {getEstadoBadge(ccd.estado)}
                                                </td>
                                                <td className="p-4">
                                                    <Badge variant={ccd.activo ? "default" : "secondary"} className={ccd.activo ? "bg-green-500" : ""}>
                                                        {ccd.activo ? 'Sí' : 'No'}
                                                    </Badge>
                                                </td>
                                                <td className="p-4">
                                                    <div className="text-sm">
                                                        {new Date(ccd.created_at).toLocaleDateString('es-ES')}
                                                    </div>
                                                    {ccd.usuario_creador && (
                                                        <div className="text-xs text-gray-500">
                                                            por {ccd.usuario_creador.name}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="p-4">
                                                    <div className="flex items-center gap-1 justify-end">
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() => handleView(ccd)}
                                                            className="h-8 w-8 p-0"
                                                        >
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() => handleEdit(ccd)}
                                                            className="h-8 w-8 p-0"
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() => handleDuplicate(ccd)}
                                                            className="h-8 w-8 p-0"
                                                        >
                                                            <Copy className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() => handleToggleActive(ccd)}
                                                            className="h-8 w-8 p-0"
                                                        >
                                                            {ccd.activo ? (
                                                                <PowerOff className="h-4 w-4 text-red-500" />
                                                            ) : (
                                                                <Power className="h-4 w-4 text-green-500" />
                                                            )}
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() => handleDelete(ccd)}
                                                            className="h-8 w-8 p-0 text-red-500 hover:text-red-700"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={7} className="p-8 text-center text-gray-500">
                                                No se encontraron cuadros de clasificación documental.
                                            </td>
                                        </tr>
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
            <Dialog open={showEditModal} onOpenChange={setShowEditModal}>
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
                                    <Label htmlFor="edit-orden">Orden</Label>
                                    <Input
                                        id="edit-orden"
                                        type="number"
                                        value={editForm.orden}
                                        onChange={(e) => setEditForm({...editForm, orden: parseInt(e.target.value) || 0})}
                                        min="0"
                                    />
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="edit-observaciones">Observaciones</Label>
                                <Textarea
                                    id="edit-observaciones"
                                    value={editForm.observaciones}
                                    onChange={(e) => setEditForm({...editForm, observaciones: e.target.value})}
                                    placeholder="Observaciones adicionales"
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

            {/* Modal de Ver Detalles */}
            <Dialog open={showViewModal} onOpenChange={setShowViewModal}>
                <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-scroll [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                    <DialogHeader>
                        <DialogTitle className="text-xl font-semibold text-gray-900">Detalles del CCD</DialogTitle>
                        <DialogDescription className="text-sm text-gray-600">
                            Información completa del Cuadro de Clasificación Documental.
                        </DialogDescription>
                    </DialogHeader>
                    {selectedCCD && (
                        <div className="space-y-6">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Código</Label>
                                    <div className="mt-1 p-2 bg-gray-50 rounded-md font-mono text-sm">
                                        {selectedCCD?.codigo}
                                    </div>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Nivel</Label>
                                    <div className="mt-1 p-2 bg-gray-50 rounded-md">
                                         {getNivelBadge(selectedCCD?.nivel || 1)}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <Label className="text-sm font-medium text-gray-700">Nombre</Label>
                                <div className="mt-1 p-2 bg-gray-50 rounded-md">
                                     {selectedCCD?.nombre}
                                </div>
                            </div>

                            {selectedCCD?.descripcion && (
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Descripción</Label>
                                    <div className="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                         {selectedCCD?.descripcion}
                                    </div>
                                </div>
                            )}

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Estado</Label>
                                    <div className="mt-1 p-2 bg-gray-50 rounded-md">
                                         {getEstadoBadge(selectedCCD?.estado || 'borrador')}
                                    </div>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Activo</Label>
                                    <div className="mt-1 p-2 bg-gray-50 rounded-md">
                                         <Badge variant={selectedCCD?.activo ? "default" : "secondary"} className={selectedCCD?.activo ? "bg-green-500" : ""}>
                                            {selectedCCD?.activo ? 'Sí' : 'No'}
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Orden</Label>
                                    <div className="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                         {selectedCCD?.orden}
                                    </div>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Fecha de Creación</Label>
                                    <div className="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                         {selectedCCD?.created_at ? new Date(selectedCCD.created_at).toLocaleDateString('es-ES') : 'N/A'}
                                    </div>
                                </div>
                            </div>

                             {selectedCCD?.observaciones && (
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Observaciones</Label>
                                    <div className="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                         {selectedCCD?.observaciones}
                                    </div>
                                </div>
                            )}

                             {selectedCCD?.padre && (
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Elemento Padre</Label>
                                    <div className="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                         <span className="font-mono">{selectedCCD?.padre?.codigo}</span> - {selectedCCD?.padre?.nombre}
                                    </div>
                                </div>
                            )}

                             {selectedCCD?.usuario_creador && (
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Creado por</Label>
                                    <div className="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                         {selectedCCD?.usuario_creador?.name}
                                    </div>
                                </div>
                            )}

                            <div className="flex justify-end pt-4">
                                <Button 
                                    variant="outline" 
                                    onClick={() => setShowViewModal(false)}
                                >
                                    Cerrar
                                </Button>
                            </div>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
