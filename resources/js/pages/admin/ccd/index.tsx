import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { 
    FileText, 
    Plus, 
    Search, 
    Eye, 
    Edit, 
    Trash2, 
    TrendingUp,
    Users,
    CheckCircle,
    AlertCircle,
    FolderTree,
    Save
} from 'lucide-react';
import { toast } from 'sonner';
import { useInertiaActions } from '@/hooks/useInertiaActions';

interface CCDVersion {
    id: number;
    version_anterior: string;
    version_nueva: string;
    cambios: string;
    fecha_cambio: string;
}

interface CCD {
    id: number;
    codigo: string;
    nombre: string;
    descripcion?: string;
    version: string;
    estado: 'borrador' | 'activo' | 'inactivo' | 'historico';
    fecha_aprobacion?: string;
    created_at: string;
    updated_at: string;
    creador?: {
        id: number;
        name: string;
    };
    niveles_count?: number;
    versiones_count?: number;
    versiones?: CCDVersion[];
}

interface CCDOption {
    id: number;
    codigo: string;
    nombre: string;
    nivel: number;
}

interface CCDIndexProps {
    ccds: {
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
    filters: {
        search?: string;
        estado?: string;
    };
    opciones?: {
        estados: { value: string; label: string; }[];
        niveles: { value: string; label: string; }[];
        padres_disponibles: CCDOption[];
    };
}

export default function CCDIndex({ ccds, estadisticas, filters, opciones }: CCDIndexProps) {
    // Hook para acciones sin recarga de página
    const actions = useInertiaActions({
        only: ['ccds', 'estadisticas'], // Solo recarga estos datos
    });
    
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [filterEstado, setFilterEstado] = useState(filters?.estado || 'all');
    const [showCreateModal, setShowCreateModal] = useState(false);

    const { data: createForm, setData: setCreateForm, post, processing, errors, reset } = useForm({
        codigo: '',
        nombre: '',
        descripcion: '',
        version: '',
        fecha_vigencia_inicio: '',
        fecha_vigencia_fin: '',
        estado: 'borrador',
    });

    const defaultOpciones = {
        estados: [
            { value: 'borrador', label: 'Borrador' },
            { value: 'activo', label: 'Activo' },
            { value: 'inactivo', label: 'Inactivo' },
            { value: 'historico', label: 'Histórico' },
        ],
        niveles: [
            { value: '1', label: 'Nivel 1 - Fondo' },
            { value: '2', label: 'Nivel 2 - Sección' },
            { value: '3', label: 'Nivel 3 - Subsección' },
            { value: '4', label: 'Nivel 4 - Serie' },
            { value: '5', label: 'Nivel 5 - Subserie' },
        ],
        padres_disponibles: [],
    };

    const formOpciones = opciones || defaultOpciones;

    const handleSearch = () => {
        const params: any = {};
        
        if (searchTerm.trim()) {
            params.search = searchTerm.trim();
        }
        
        if (filterEstado && filterEstado !== 'all') {
            params.estado = filterEstado;
        }
        
        router.get('/admin/ccd', params, {
            preserveState: false,
            preserveScroll: false,
            replace: true,
        });
    };

    const handleEstadoChange = (value: string) => {
        setFilterEstado(value);
        // Ejecutar búsqueda automáticamente cuando cambia el estado
        const params: any = {};
        
        if (searchTerm.trim()) {
            params.search = searchTerm.trim();
        }
        
        if (value && value !== 'all') {
            params.estado = value;
        }
        
        router.get('/admin/ccd', params, {
            preserveState: false,
            preserveScroll: false,
            replace: true,
        });
    };

    const getEstadoBadge = (estado: string) => {
        const badges = {
            'borrador': <Badge variant="secondary" className="bg-gray-100 text-gray-800">Borrador</Badge>,
            'activo': <Badge variant="default" className="bg-green-100 text-green-800">Activo</Badge>,
            'inactivo': <Badge variant="destructive" className="bg-red-100 text-red-800">Inactivo</Badge>,
            'historico': <Badge variant="outline" className="bg-blue-100 text-blue-800">Histórico</Badge>,
        };
        return badges[estado as keyof typeof badges] || <Badge>{estado}</Badge>;
    };

    return (
        <AppLayout breadcrumbs={[
            { title: "Dashboard", href: "/dashboard" },
            { title: "Administración", href: "#" },
            { title: "Cuadros de Clasificación Documental", href: "/admin/ccd" }
        ]}>
            <Head title="Cuadros de Clasificación Documental" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-2">
                        <FolderTree className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Gestión de Cuadros de Clasificación Documental
                        </h1>
                    </div>
                    <Dialog open={showCreateModal} onOpenChange={setShowCreateModal}>
                        <DialogTrigger asChild>
                            <Button className="flex items-center gap-2 px-4 py-2 bg-[#2a3d83] text-white rounded-lg hover:bg-[#1e2b5f] transition-colors">
                                <Plus className="h-4 w-4" />
                                Nuevo CCD
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle className="text-xl font-semibold text-gray-900">
                                    Crear Nuevo Cuadro de Clasificación Documental
                                </DialogTitle>
                                <DialogDescription className="text-sm text-gray-600">
                                    Complete los siguientes datos para crear un nuevo CCD.
                                </DialogDescription>
                            </DialogHeader>
                            <form onSubmit={(e) => {
                                e.preventDefault();
                                
                                if (!createForm.codigo || !createForm.nombre) {
                                    toast.error('Por favor complete los campos requeridos');
                                    return;
                                }

                                actions.create('/admin/ccd', createForm, {
                                    successMessage: 'CCD creado exitosamente',
                                    onSuccess: () => {
                                        setShowCreateModal(false);
                                        reset();
                                    },
                                    onError: (errors) => {
                                        console.error('Error al crear CCD:', errors);
                                        if (errors) {
                                            Object.keys(errors).forEach(field => {
                                                const message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                                                toast.error(`Error en ${field}: ${message}`);
                                            });
                                        }
                                    }
                                });
                            }} className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="create-codigo">Código *</Label>
                                        <Input
                                            id="create-codigo"
                                            type="text"
                                            value={createForm.codigo}
                                            onChange={(e) => setCreateForm('codigo', e.target.value)}
                                            placeholder="Ej: CCD-001"
                                            required
                                            className={errors.codigo ? 'border-red-500' : ''}
                                        />
                                        {errors.codigo && (
                                            <p className="text-sm text-red-600">{errors.codigo}</p>
                                        )}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="create-version">Versión</Label>
                                        <Input
                                            id="create-version"
                                            type="text"
                                            value={createForm.version}
                                            onChange={(e) => setCreateForm('version', e.target.value)}
                                            placeholder="Ej: 1.0"
                                            className={errors.version ? 'border-red-500' : ''}
                                        />
                                        {errors.version && (
                                            <p className="text-sm text-red-600">{errors.version}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create-nombre">Nombre *</Label>
                                    <Input
                                        id="create-nombre"
                                        type="text"
                                        value={createForm.nombre}
                                        onChange={(e) => setCreateForm('nombre', e.target.value)}
                                        placeholder="Nombre del CCD"
                                        required
                                        className={errors.nombre ? 'border-red-500' : ''}
                                    />
                                    {errors.nombre && (
                                        <p className="text-sm text-red-600">{errors.nombre}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create-descripcion">Descripción</Label>
                                    <Textarea
                                        id="create-descripcion"
                                        value={createForm.descripcion}
                                        onChange={(e) => setCreateForm('descripcion', e.target.value)}
                                        placeholder="Descripción del CCD"
                                        rows={3}
                                        className={errors.descripcion ? 'border-red-500' : ''}
                                    />
                                    {errors.descripcion && (
                                        <p className="text-sm text-red-600">{errors.descripcion}</p>
                                    )}
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="create-fecha-vigencia-inicio">Fecha Vigencia Inicio</Label>
                                        <Input
                                            id="create-fecha-vigencia-inicio"
                                            type="date"
                                            value={createForm.fecha_vigencia_inicio}
                                            onChange={(e) => setCreateForm('fecha_vigencia_inicio', e.target.value)}
                                            className={errors.fecha_vigencia_inicio ? 'border-red-500' : ''}
                                        />
                                        {errors.fecha_vigencia_inicio && (
                                            <p className="text-sm text-red-600">{errors.fecha_vigencia_inicio}</p>
                                        )}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="create-fecha-vigencia-fin">Fecha Vigencia Fin</Label>
                                        <Input
                                            id="create-fecha-vigencia-fin"
                                            type="date"
                                            value={createForm.fecha_vigencia_fin}
                                            onChange={(e) => setCreateForm('fecha_vigencia_fin', e.target.value)}
                                            className={errors.fecha_vigencia_fin ? 'border-red-500' : ''}
                                        />
                                        {errors.fecha_vigencia_fin && (
                                            <p className="text-sm text-red-600">{errors.fecha_vigencia_fin}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create-estado">Estado *</Label>
                                    <Select 
                                        value={createForm.estado} 
                                        onValueChange={(value) => setCreateForm('estado', value)}
                                    >
                                        <SelectTrigger className={errors.estado ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Seleccionar estado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {formOpciones.estados.map((estado) => (
                                                <SelectItem key={estado.value} value={estado.value}>
                                                    {estado.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.estado && (
                                        <p className="text-sm text-red-600">{errors.estado}</p>
                                    )}
                                </div>

                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setShowCreateModal(false);
                                            reset();
                                        }}
                                    >
                                        Cancelar
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-[#2a3d83] hover:bg-[#1e2b5f] flex items-center gap-2"
                                    >
                                        <Save className="h-4 w-4" />
                                        {processing ? 'Guardando...' : 'Crear CCD'}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Estadísticas */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total CCDs</p>
                                <p className="text-2xl font-semibold text-gray-900">{estadisticas?.total || 0}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <FolderTree className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Activos</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas?.activos || 0}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <CheckCircle className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Borradores</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas?.borradores || 0}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <AlertCircle className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Vigentes</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas?.vigentes || 0}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <TrendingUp className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filtros */}
                <div className="bg-white rounded-lg border p-6">
                    <div className="flex flex-col sm:flex-row gap-4 items-center justify-between">
                        <div className="flex items-center gap-4 w-full sm:w-auto">
                            <div className="relative flex-1 sm:w-80">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    type="text"
                                    placeholder="Buscar CCDs..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    onKeyPress={(e) => {
                                        if (e.key === 'Enter') {
                                            e.preventDefault();
                                            handleSearch();
                                        }
                                    }}
                                    className="pl-10"
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Select
                                value={filterEstado}
                                onValueChange={handleEstadoChange}
                            >
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="borrador">Borrador</SelectItem>
                                    <SelectItem value="activo">Activo</SelectItem>
                                    <SelectItem value="inactivo">Inactivo</SelectItem>
                                    <SelectItem value="historico">Histórico</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button onClick={handleSearch} type="button" className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                                <Search className="mr-2 h-4 w-4" />
                                Buscar
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Lista de CCDs */}
                <div className="bg-white rounded-lg border overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b">
                                <tr>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Código</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Nombre</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Versión</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Estado</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Niveles</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Creado</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {ccds.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="py-8 px-6 text-center text-gray-500">
                                            No se encontraron CCDs.
                                        </td>
                                    </tr>
                                ) : (
                                    ccds.data.map((ccd) => (
                                        <tr key={ccd.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="py-4 px-6 font-medium text-gray-900">{ccd.codigo}</td>
                                            <td className="py-4 px-6">
                                                <div>
                                                    <div className="font-medium text-gray-900">{ccd.nombre}</div>
                                                    {ccd.descripcion && (
                                                        <div className="text-sm text-gray-500 truncate max-w-xs">{ccd.descripcion}</div>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="py-4 px-6">
                                                <div className="flex flex-col gap-1">
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                        v{ccd.version || '1.0'}
                                                    </span>
                                                    {(ccd.versiones_count || 0) > 0 && (
                                                        <span className="text-xs text-gray-500">
                                                            {ccd.versiones_count} cambios
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="py-4 px-6">
                                                <div className="flex flex-col gap-1">
                                                    {getEstadoBadge(ccd.estado)}
                                                    {ccd.estado === 'borrador' && (
                                                        <span className="text-xs text-orange-600">
                                                            Pendiente aprobación
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="py-4 px-6">
                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-[#2a3d83]">
                                                    {ccd.niveles_count || 0} niveles
                                                </span>
                                            </td>
                                            <td className="py-4 px-6 text-gray-600">{new Date(ccd.created_at).toLocaleDateString('es-ES')}</td>
                                            <td className="py-4 px-6">
                                                <div className="flex items-center gap-2">
                                                    <Link href={`/admin/ccd/${ccd.id}`}>
                                                        <button className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors">
                                                            <Eye className="h-4 w-4" />
                                                        </button>
                                                    </Link>
                                                    <Link href={`/admin/ccd/${ccd.id}/edit`}>
                                                        <button className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors">
                                                            <Edit className="h-4 w-4" />
                                                        </button>
                                                    </Link>
                                                    <button 
                                                        onClick={() => {
                                                            const nivelesCount = ccd.niveles_count || 0;
                                                            const nivelesInfo = nivelesCount > 0 
                                                                ? `\n\n⚠️ ADVERTENCIA: Este CCD tiene ${nivelesCount} nivel(es) asociado(s) que también serán eliminados.` 
                                                                : '';
                                                            
                                                            const estadoWarning = ccd.estado !== 'borrador'
                                                                ? `\n\n⚠️ Este CCD está en estado "${ccd.estado}". Se recomienda solo eliminar CCDs en borrador.`
                                                                : '';
                                                            
                                                            actions.destroy(`/admin/ccd/${ccd.id}`, {
                                                                confirmMessage: `¿Está seguro de eliminar el CCD "${ccd.nombre}"?${nivelesInfo}${estadoWarning}\n\nEsta acción NO se puede deshacer.`,
                                                                successMessage: nivelesCount > 0 
                                                                    ? `CCD y ${nivelesCount} nivel(es) eliminados exitosamente`
                                                                    : 'CCD eliminado exitosamente',
                                                                errorMessage: 'Error al eliminar el CCD',
                                                            });
                                                        }}
                                                        className="p-2 rounded-md text-red-600 hover:text-red-700 hover:bg-red-50 transition-colors"
                                                        title="Eliminar CCD"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Paginación */}
                {ccds.data.length > 0 && ccds.last_page > 1 && (
                    <div className="flex items-center justify-between bg-white border rounded-lg px-6 py-3">
                        <div className="text-sm text-gray-600">
                            Mostrando <span className="font-medium">{((ccds.current_page - 1) * ccds.per_page) + 1}</span> a{' '}
                            <span className="font-medium">{Math.min(ccds.current_page * ccds.per_page, ccds.total)}</span> de{' '}
                            <span className="font-medium">{ccds.total}</span> resultados
                        </div>
                        <div className="flex items-center gap-2">
                            {ccds.current_page > 1 && (
                                <Link
                                    href={`/admin/ccd?page=${ccds.current_page - 1}${searchTerm ? `&search=${searchTerm}` : ''}${filterEstado !== 'all' ? `&estado=${filterEstado}` : ''}`}
                                    preserveState
                                    className="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Anterior
                                </Link>
                            )}
                            {ccds.current_page < ccds.last_page && (
                                <Link
                                    href={`/admin/ccd?page=${ccds.current_page + 1}${searchTerm ? `&search=${searchTerm}` : ''}${filterEstado !== 'all' ? `&estado=${filterEstado}` : ''}`}
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
        </AppLayout>
    );
}
