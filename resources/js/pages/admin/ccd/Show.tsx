import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { 
    FolderTree, 
    Plus, 
    Edit, 
    Trash2, 
    ChevronDown, 
    ChevronRight,
    FileText,
    Folder,
    FolderOpen,
    CheckCircle,
    AlertCircle,
    ArrowLeft,
    Download,
    Upload,
    GitBranch
} from 'lucide-react';
import { toast } from 'sonner';
import { useInertiaActions } from '@/hooks/useInertiaActions';

interface CCDNivel {
    id: number;
    ccd_id: number;
    parent_id?: number;
    codigo: string;
    nombre: string;
    descripcion?: string;
    nivel: number;
    tipo_nivel: string;
    orden: number;
    activo: boolean;
    ruta?: string;
    palabras_clave?: string[];
    hijos?: CCDNivel[];
}

interface CCD {
    id: number;
    codigo: string;
    nombre: string;
    descripcion?: string;
    version: string;
    estado: string;
    fecha_aprobacion?: string;
    fecha_vigencia_inicio?: string;
    fecha_vigencia_fin?: string;
    vigente: boolean;
    created_at: string;
    updated_at: string;
    creador?: {
        id: number;
        name: string;
    };
    aprobador?: {
        id: number;
        name: string;
    };
}

interface Props {
    ccd: CCD;
    estructura: CCDNivel[];
    estadisticas: {
        total_niveles: number;
        niveles_activos: number;
        por_tipo: Record<string, number>;
        niveles_por_profundidad: Record<string, number>;
        total_vocabularios: number;
        profundidad_maxima: number;
    };
    errores_validacion: string[];
}

// Componente para renderizar un nodo del árbol
const TreeNode = ({ 
    node, 
    onEdit, 
    onDelete, 
    onAddChild 
}: { 
    node: CCDNivel; 
    onEdit: (node: CCDNivel) => void; 
    onDelete: (node: CCDNivel) => void;
    onAddChild: (parent: CCDNivel) => void;
}) => {
    const [isExpanded, setIsExpanded] = useState(true);
    const hasChildren = node.hijos && node.hijos.length > 0;

    const getTipoIcon = (tipo: string) => {
        switch (tipo) {
            case 'fondo':
                return <FolderOpen className="h-4 w-4 text-blue-600" />;
            case 'seccion':
                return <Folder className="h-4 w-4 text-green-600" />;
            case 'subseccion':
                return <Folder className="h-4 w-4 text-yellow-600" />;
            case 'serie':
                return <FileText className="h-4 w-4 text-purple-600" />;
            case 'subserie':
                return <FileText className="h-4 w-4 text-pink-600" />;
            default:
                return <FileText className="h-4 w-4 text-gray-600" />;
        }
    };

    const getTipoColor = (tipo: string) => {
        const colors = {
            'fondo': 'bg-blue-100 text-blue-800',
            'seccion': 'bg-green-100 text-green-800',
            'subseccion': 'bg-yellow-100 text-yellow-800',
            'serie': 'bg-purple-100 text-purple-800',
            'subserie': 'bg-pink-100 text-pink-800',
        };
        return colors[tipo as keyof typeof colors] || 'bg-gray-100 text-gray-800';
    };

    return (
        <div className="ml-4">
            <div className="flex items-center gap-2 py-2 group hover:bg-gray-50 rounded-md px-2">
                {/* Expand/Collapse Button */}
                <button
                    onClick={() => setIsExpanded(!isExpanded)}
                    className="p-0.5 hover:bg-gray-200 rounded"
                    disabled={!hasChildren}
                >
                    {hasChildren ? (
                        isExpanded ? (
                            <ChevronDown className="h-4 w-4" />
                        ) : (
                            <ChevronRight className="h-4 w-4" />
                        )
                    ) : (
                        <div className="w-4 h-4" />
                    )}
                </button>

                {/* Icon */}
                {getTipoIcon(node.tipo_nivel)}

                {/* Node Info */}
                <div className="flex-1 flex items-center gap-2">
                    <span className="font-medium text-sm">{node.nombre}</span>
                    <span className="text-xs text-gray-500">({node.codigo})</span>
                    <Badge variant="outline" className={`text-xs ${getTipoColor(node.tipo_nivel)}`}>
                        {node.tipo_nivel}
                    </Badge>
                    {!node.activo && (
                        <Badge variant="outline" className="text-xs bg-red-100 text-red-800">
                            Inactivo
                        </Badge>
                    )}
                </div>

                {/* Actions (visible on hover) */}
                <div className="hidden group-hover:flex items-center gap-1">
                    <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => onAddChild(node)}
                        className="h-7 w-7 p-0"
                    >
                        <Plus className="h-3 w-3" />
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => onEdit(node)}
                        className="h-7 w-7 p-0"
                    >
                        <Edit className="h-3 w-3" />
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => onDelete(node)}
                        className="h-7 w-7 p-0 hover:bg-red-100 hover:text-red-600"
                    >
                        <Trash2 className="h-3 w-3" />
                    </Button>
                </div>
            </div>

            {/* Children */}
            {isExpanded && hasChildren && (
                <div className="border-l-2 border-gray-200 ml-2">
                    {node.hijos!.map((child) => (
                        <TreeNode
                            key={child.id}
                            node={child}
                            onEdit={onEdit}
                            onDelete={onDelete}
                            onAddChild={onAddChild}
                        />
                    ))}
                </div>
            )}
        </div>
    );
};

export default function CCDShow({ ccd, estructura, estadisticas, errores_validacion }: Props) {
    // Hook para acciones sin recarga de página
    const actions = useInertiaActions({
        only: ['ccd', 'estructura', 'estadisticas', 'errores_validacion'],
    });
    
    const [showAddNivelModal, setShowAddNivelModal] = useState(false);
    const [showEditNivelModal, setShowEditNivelModal] = useState(false);
    const [selectedParent, setSelectedParent] = useState<CCDNivel | null>(null);
    const [selectedNivel, setSelectedNivel] = useState<CCDNivel | null>(null);

    const [nivelForm, setNivelForm] = useState({
        codigo: '',
        nombre: '',
        descripcion: '',
        tipo_nivel: 'fondo',
        orden: 0,
        activo: true,
    });

    const breadcrumbItems = [
        { title: "Dashboard", href: "/admin" },
        { title: "CCD", href: "/admin/ccd" },
        { title: ccd.nombre, href: `/admin/ccd/${ccd.id}` }
    ];

    const getEstadoBadge = (estado: string) => {
        const colors = {
            'borrador': 'bg-gray-100 text-gray-800',
            'activo': 'bg-green-100 text-green-800',
            'inactivo': 'bg-red-100 text-red-800',
            'archivado': 'bg-yellow-100 text-yellow-800',
        };
        return colors[estado as keyof typeof colors] || 'bg-gray-100 text-gray-800';
    };

    const handleAddNivel = (parent: CCDNivel | null = null) => {
        setSelectedParent(parent);
        setNivelForm({
            codigo: '',
            nombre: '',
            descripcion: '',
            tipo_nivel: parent ? 'seccion' : 'fondo',
            orden: 0,
            activo: true,
        });
        setShowAddNivelModal(true);
    };

    const handleEditNivel = (nivel: CCDNivel) => {
        setSelectedNivel(nivel);
        setNivelForm({
            codigo: nivel.codigo,
            nombre: nivel.nombre,
            descripcion: nivel.descripcion || '',
            tipo_nivel: nivel.tipo_nivel,
            orden: nivel.orden,
            activo: nivel.activo,
        });
        setShowEditNivelModal(true);
    };

    const handleDeleteNivel = (nivel: CCDNivel) => {
        if (nivel.hijos && nivel.hijos.length > 0) {
            toast.error('No se puede eliminar un nivel que tiene hijos');
            return;
        }

        actions.destroy(`/admin/ccd/nivel/${nivel.id}`, {
            confirmMessage: `¿Está seguro de eliminar el nivel "${nivel.nombre}"?`,
            successMessage: 'Nivel eliminado exitosamente',
            errorMessage: 'Error al eliminar el nivel',
        });
    };

    const submitAddNivel = () => {
        actions.create(`/admin/ccd/${ccd.id}/nivel`, {
            ...nivelForm,
            parent_id: selectedParent?.id || null,
        }, {
            successMessage: 'Nivel agregado exitosamente',
            errorMessage: 'Error al agregar nivel',
            onSuccess: () => {
                setShowAddNivelModal(false);
                setNivelForm({
                    codigo: '',
                    nombre: '',
                    descripcion: '',
                    tipo_nivel: 'fondo',
                    orden: 0,
                    activo: true,
                });
            }
        });
    };

    const submitEditNivel = () => {
        if (!selectedNivel) return;

        actions.update(`/admin/ccd/nivel/${selectedNivel.id}`, nivelForm, {
            successMessage: 'Nivel actualizado exitosamente',
            errorMessage: 'Error al actualizar nivel',
            onSuccess: () => {
                setShowEditNivelModal(false);
            }
        });
    };

    const handleAprobar = () => {
        if (errores_validacion.length > 0) {
            toast.error('No se puede aprobar: ' + errores_validacion.join(', '));
            return;
        }

        actions.create(`/admin/ccd/${ccd.id}/aprobar`, {}, {
            successMessage: 'CCD aprobado exitosamente',
            errorMessage: 'Error al aprobar el CCD',
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title={`CCD - ${ccd.nombre}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="outline"
                            onClick={() => router.visit('/admin/ccd')}
                        >
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Volver
                        </Button>
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900">{ccd.nombre}</h1>
                            <p className="text-sm text-gray-500">{ccd.codigo} • Versión {ccd.version}</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Badge className={getEstadoBadge(ccd.estado)}>
                            {ccd.estado}
                        </Badge>
                        {ccd.vigente && (
                            <Badge className="bg-green-100 text-green-800">
                                <CheckCircle className="h-3 w-3 mr-1" />
                                Vigente
                            </Badge>
                        )}
                    </div>
                </div>

                {/* Errores de validación */}
                {errores_validacion.length > 0 && (
                    <Card className="border-red-200 bg-red-50">
                        <CardHeader>
                            <CardTitle className="text-red-800 flex items-center gap-2">
                                <AlertCircle className="h-5 w-5" />
                                Errores de Validación
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul className="list-disc list-inside space-y-1">
                                {errores_validacion.map((error, idx) => (
                                    <li key={idx} className="text-sm text-red-700">{error}</li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}

                {/* Estadísticas */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">
                                Total Niveles
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-[#2a3d83]">
                                {estadisticas.total_niveles}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">
                                Niveles Activos
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">
                                {estadisticas.niveles_activos}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">
                                Profundidad Máxima
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-600">
                                {estadisticas.profundidad_maxima}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">
                                Vocabularios
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-orange-600">
                                {estadisticas.total_vocabularios}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Acciones */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle>Acciones</CardTitle>
                            <div className="flex items-center gap-2">
                                {ccd.estado === 'borrador' && errores_validacion.length === 0 && (
                                    <Button onClick={handleAprobar} className="bg-green-600 hover:bg-green-700">
                                        <CheckCircle className="h-4 w-4 mr-2" />
                                        Aprobar CCD
                                    </Button>
                                )}
                                <Button variant="outline">
                                    <Download className="h-4 w-4 mr-2" />
                                    Exportar
                                </Button>
                                <Button variant="outline">
                                    <GitBranch className="h-4 w-4 mr-2" />
                                    Nueva Versión
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                {/* Estructura Jerárquica */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <FolderTree className="h-5 w-5" />
                                    Estructura Jerárquica
                                </CardTitle>
                                <CardDescription>
                                    Gestione la estructura del cuadro de clasificación documental
                                </CardDescription>
                            </div>
                            <Button onClick={() => handleAddNivel(null)} className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                                <Plus className="h-4 w-4 mr-2" />
                                Agregar Nivel Raíz
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {estructura.length === 0 ? (
                            <div className="text-center py-12 text-gray-500">
                                <FolderTree className="h-12 w-12 mx-auto mb-4 text-gray-300" />
                                <p>No hay niveles configurados aún</p>
                                <p className="text-sm">Comience agregando un nivel raíz (Fondo)</p>
                            </div>
                        ) : (
                            <div className="space-y-1">
                                {estructura.map((nodo) => (
                                    <TreeNode
                                        key={nodo.id}
                                        node={nodo}
                                        onEdit={handleEditNivel}
                                        onDelete={handleDeleteNivel}
                                        onAddChild={handleAddNivel}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Modal Agregar Nivel */}
                <Dialog open={showAddNivelModal} onOpenChange={setShowAddNivelModal}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Agregar Nuevo Nivel</DialogTitle>
                            <DialogDescription>
                                {selectedParent 
                                    ? `Agregar nivel hijo a: ${selectedParent.nombre}` 
                                    : 'Agregar nivel raíz al CCD'}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Código*</Label>
                                    <Input
                                        value={nivelForm.codigo}
                                        onChange={(e) => setNivelForm({...nivelForm, codigo: e.target.value})}
                                        placeholder="Ej: FOND-01"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>Tipo*</Label>
                                    <Select 
                                        value={nivelForm.tipo_nivel} 
                                        onValueChange={(value) => setNivelForm({...nivelForm, tipo_nivel: value})}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="fondo">Fondo</SelectItem>
                                            <SelectItem value="seccion">Sección</SelectItem>
                                            <SelectItem value="subseccion">Subsección</SelectItem>
                                            <SelectItem value="serie">Serie</SelectItem>
                                            <SelectItem value="subserie">Subserie</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label>Nombre*</Label>
                                <Input
                                    value={nivelForm.nombre}
                                    onChange={(e) => setNivelForm({...nivelForm, nombre: e.target.value})}
                                    placeholder="Nombre del nivel"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Descripción</Label>
                                <Textarea
                                    value={nivelForm.descripcion}
                                    onChange={(e) => setNivelForm({...nivelForm, descripcion: e.target.value})}
                                    placeholder="Descripción opcional"
                                    rows={3}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Orden</Label>
                                <Input
                                    type="number"
                                    value={nivelForm.orden}
                                    onChange={(e) => setNivelForm({...nivelForm, orden: parseInt(e.target.value) || 0})}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setShowAddNivelModal(false)}>
                                Cancelar
                            </Button>
                            <Button onClick={submitAddNivel} className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                                Agregar Nivel
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Modal Editar Nivel */}
                <Dialog open={showEditNivelModal} onOpenChange={setShowEditNivelModal}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Editar Nivel</DialogTitle>
                            <DialogDescription>
                                Modificar información del nivel {selectedNivel?.nombre}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Código*</Label>
                                    <Input
                                        value={nivelForm.codigo}
                                        onChange={(e) => setNivelForm({...nivelForm, codigo: e.target.value})}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>Tipo*</Label>
                                    <Select 
                                        value={nivelForm.tipo_nivel} 
                                        onValueChange={(value) => setNivelForm({...nivelForm, tipo_nivel: value})}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="fondo">Fondo</SelectItem>
                                            <SelectItem value="seccion">Sección</SelectItem>
                                            <SelectItem value="subseccion">Subsección</SelectItem>
                                            <SelectItem value="serie">Serie</SelectItem>
                                            <SelectItem value="subserie">Subserie</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label>Nombre*</Label>
                                <Input
                                    value={nivelForm.nombre}
                                    onChange={(e) => setNivelForm({...nivelForm, nombre: e.target.value})}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Descripción</Label>
                                <Textarea
                                    value={nivelForm.descripcion}
                                    onChange={(e) => setNivelForm({...nivelForm, descripcion: e.target.value})}
                                    rows={3}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setShowEditNivelModal(false)}>
                                Cancelar
                            </Button>
                            <Button onClick={submitEditNivel} className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                                Actualizar Nivel
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
