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
    GitBranch,
    FileJson,
    FileSpreadsheet,
    File,
    RotateCcw,
    History
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

interface CCDVersion {
    id: number;
    version_anterior: string;
    version_nueva: string;
    cambios: string;
    fecha_cambio: string;
    created_at: string;
    modificador?: {
        id: number;
        name: string;
    };
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
    versiones?: CCDVersion[];
}

interface TRDRelacionada {
    id: number;
    codigo: string;
    nombre: string;
    version: string;
    estado: string;
    series_count: number;
}

interface SerieRelacionada {
    id: number;
    codigo: string;
    nombre: string;
    trd_nombre: string;
    subseries_count: number;
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
    trds_relacionadas?: TRDRelacionada[];
    series_relacionadas?: SerieRelacionada[];
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

export default function CCDShow({ ccd, estructura, estadisticas, errores_validacion, trds_relacionadas = [], series_relacionadas = [] }: Props) {
    // Hook para acciones sin recarga de página
    const actions = useInertiaActions({
        only: ['ccd', 'estructura', 'estadisticas', 'errores_validacion', 'trds_relacionadas', 'series_relacionadas'],
    });
    
    const [showAddNivelModal, setShowAddNivelModal] = useState(false);
    const [showEditNivelModal, setShowEditNivelModal] = useState(false);
    const [showExportModal, setShowExportModal] = useState(false);
    const [showVersionModal, setShowVersionModal] = useState(false);
    const [exportLoading, setExportLoading] = useState(false);
    const [selectedParent, setSelectedParent] = useState<CCDNivel | null>(null);
    const [selectedNivel, setSelectedNivel] = useState<CCDNivel | null>(null);
    
    // Estados para diálogos de confirmación
    const [showConfirmDelete, setShowConfirmDelete] = useState(false);
    const [showConfirmRevert, setShowConfirmRevert] = useState(false);
    const [versionToAction, setVersionToAction] = useState<{id: number, version: string} | null>(null);

    const [nivelForm, setNivelForm] = useState({
        codigo: '',
        nombre: '',
        descripcion: '',
        tipo_nivel: 'fondo',
        orden: 0,
        activo: true,
    });

    const [versionForm, setVersionForm] = useState({
        version: '',
        cambios: '',
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

    const handleExport = async (formato: string) => {
        setExportLoading(true);
        try {
            const url = `/admin/ccd/${ccd.id}/exportar?formato=${formato}`;
            
            // Para JSON, CSV y Excel, usar descarga directa
            const link = document.createElement('a');
            link.href = url;
            link.download = `CCD_${ccd.codigo}_v${ccd.version}.${formato === 'excel' ? 'xlsx' : formato}`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            toast.success(`Exportación en formato ${formato.toUpperCase()} iniciada`);
            setShowExportModal(false);
        } catch (error) {
            console.error('Error al exportar:', error);
            toast.error('Error al exportar el CCD');
        } finally {
            setExportLoading(false);
        }
    };

    const exportFormats = [
        { 
            id: 'json', 
            name: 'JSON', 
            description: 'Formato de datos estructurado, ideal para integración con otros sistemas',
            icon: FileJson,
            color: 'text-yellow-600 bg-yellow-50'
        },
        { 
            id: 'csv', 
            name: 'CSV', 
            description: 'Valores separados por comas, compatible con Excel y hojas de cálculo',
            icon: FileText,
            color: 'text-green-600 bg-green-50'
        },
        { 
            id: 'excel', 
            name: 'Excel (XLSX)', 
            description: 'Hoja de cálculo con formato y colores por tipo de nivel',
            icon: FileSpreadsheet,
            color: 'text-emerald-600 bg-emerald-50'
        },
        { 
            id: 'pdf', 
            name: 'PDF', 
            description: 'Documento portable para impresión y archivo',
            icon: File,
            color: 'text-red-600 bg-red-50'
        },
    ];

    const openVersionModal = () => {
        // Sugerir nueva versión basada en la actual
        const currentVersion = ccd.version || '1.0';
        const parts = currentVersion.split('.');
        const minor = parseInt(parts[1] || '0') + 1;
        const suggestedVersion = `${parts[0]}.${minor}`;
        
        setVersionForm({
            version: suggestedVersion,
            cambios: '',
        });
        setShowVersionModal(true);
    };

    const submitNewVersion = () => {
        if (!versionForm.version.trim()) {
            toast.error('La versión es requerida');
            return;
        }
        if (!versionForm.cambios.trim()) {
            toast.error('Debe describir los cambios realizados');
            return;
        }

        router.post(`/admin/ccd/${ccd.id}/version`, versionForm, {
            onSuccess: () => {
                toast.success('Nueva versión creada exitosamente. El CCD requiere nueva aprobación.');
                setShowVersionModal(false);
                setVersionForm({ version: '', cambios: '' });
            },
            onError: (errors) => {
                console.error('Errores:', errors);
                toast.error('Error al crear la nueva versión');
            },
        });
    };

    const confirmDeleteVersion = (versionId: number, versionNueva: string) => {
        setVersionToAction({ id: versionId, version: versionNueva });
        setShowConfirmDelete(true);
    };

    const handleDeleteVersion = () => {
        if (!versionToAction) return;

        router.delete(`/admin/ccd/version/${versionToAction.id}`, {
            onSuccess: () => {
                toast.success(`Versión ${versionToAction.version} eliminada exitosamente`);
                setShowConfirmDelete(false);
                setVersionToAction(null);
            },
            onError: () => {
                toast.error('Error al eliminar la versión');
            },
        });
    };

    const confirmRevertVersion = (versionId: number, versionAnterior: string) => {
        setVersionToAction({ id: versionId, version: versionAnterior });
        setShowConfirmRevert(true);
    };

    const handleRevertVersion = () => {
        if (!versionToAction) return;

        router.post(`/admin/ccd/${ccd.id}/revertir/${versionToAction.id}`, {}, {
            onSuccess: () => {
                toast.success(`CCD revertido a versión ${versionToAction.version}`);
                setShowConfirmRevert(false);
                setVersionToAction(null);
            },
            onError: () => {
                toast.error('Error al revertir la versión');
            },
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

                {/* Estado de Versión e Historial */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Versión Actual */}
                    <Card className={ccd.estado === 'borrador' ? 'border-orange-300 bg-orange-50' : 'border-green-300 bg-green-50'}>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                {ccd.estado === 'borrador' ? (
                                    <>
                                        <AlertCircle className="h-5 w-5 text-orange-600" />
                                        <span className="text-orange-800">Versión en Borrador</span>
                                    </>
                                ) : (
                                    <>
                                        <CheckCircle className="h-5 w-5 text-green-600" />
                                        <span className="text-green-800">Versión Aprobada</span>
                                    </>
                                )}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium">Versión:</span>
                                    <Badge className="bg-purple-100 text-purple-800 text-lg px-3">
                                        v{ccd.version}
                                    </Badge>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium">Estado:</span>
                                    <Badge className={getEstadoBadge(ccd.estado)}>
                                        {ccd.estado === 'borrador' ? 'Pendiente de Aprobación' : ccd.estado}
                                    </Badge>
                                </div>
                                {ccd.fecha_aprobacion && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium">Fecha Aprobación:</span>
                                        <span className="text-sm">{new Date(ccd.fecha_aprobacion).toLocaleDateString('es-ES')}</span>
                                    </div>
                                )}
                                {ccd.aprobador && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium">Aprobado por:</span>
                                        <span className="text-sm">{ccd.aprobador.name}</span>
                                    </div>
                                )}
                                {ccd.estado === 'borrador' && (
                                    <div className="mt-4 p-3 bg-orange-100 rounded-lg">
                                        <p className="text-sm text-orange-800">
                                            <strong>⚠️ Esta versión requiere aprobación.</strong> Una vez aprobada, 
                                            estará disponible para su uso en el sistema.
                                        </p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Historial de Versiones */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <GitBranch className="h-5 w-5 text-[#2a3d83]" />
                                Historial de Versiones
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {ccd.versiones && ccd.versiones.length > 0 ? (
                                <div className="space-y-3 max-h-80 overflow-y-auto">
                                    {ccd.versiones.map((version, idx) => (
                                        <div key={version.id} className={`flex items-start gap-3 p-3 rounded-lg ${
                                            version.version_nueva === ccd.version 
                                                ? 'bg-orange-50 border border-orange-200' 
                                                : 'bg-gray-50'
                                        }`}>
                                            <div className={`flex-shrink-0 w-8 h-8 text-white rounded-full flex items-center justify-center text-xs font-bold ${
                                                version.version_nueva === ccd.version 
                                                    ? 'bg-orange-500' 
                                                    : 'bg-[#2a3d83]'
                                            }`}>
                                                {idx + 1}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center gap-2">
                                                        <Badge variant="outline" className="text-xs">
                                                            v{version.version_anterior} → v{version.version_nueva}
                                                        </Badge>
                                                        {version.version_nueva === ccd.version && (
                                                            <Badge className="bg-orange-100 text-orange-800 text-xs">
                                                                Actual
                                                            </Badge>
                                                        )}
                                                        <span className="text-xs text-gray-500">
                                                            {new Date(version.created_at).toLocaleDateString('es-ES')}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="h-7 w-7 p-0 text-blue-600 hover:text-blue-800 hover:bg-blue-50"
                                                            onClick={() => confirmRevertVersion(version.id, version.version_anterior)}
                                                            title={`Revertir a v${version.version_anterior}`}
                                                        >
                                                            <RotateCcw className="h-3.5 w-3.5" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="h-7 w-7 p-0 text-red-600 hover:text-red-800 hover:bg-red-50"
                                                            onClick={() => confirmDeleteVersion(version.id, version.version_nueva)}
                                                            title="Eliminar versión"
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </Button>
                                                    </div>
                                                </div>
                                                <p className="text-sm text-gray-700 mt-1">
                                                    {version.cambios}
                                                </p>
                                                {version.modificador && (
                                                    <p className="text-xs text-gray-500 mt-1">
                                                        Por: {version.modificador.name}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-6 text-gray-500">
                                    <GitBranch className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                    <p className="text-sm">No hay historial de versiones</p>
                                    <p className="text-xs">Esta es la versión inicial del CCD</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Relaciones con TRD y Series */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* TRDs Relacionadas */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <FileText className="h-5 w-5 text-[#2a3d83]" />
                                    TRDs Relacionadas
                                </CardTitle>
                                <CardDescription>
                                    Tablas de Retención vinculadas a este CCD ({trds_relacionadas.length})
                                </CardDescription>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {trds_relacionadas.length > 0 ? (
                                <div className="space-y-2">
                                    {trds_relacionadas.map((trd) => (
                                        <a 
                                            key={trd.id}
                                            href={`/admin/trd/${trd.id}`}
                                            className="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 hover:border-[#2a3d83] transition-all"
                                        >
                                            <div>
                                                <div className="font-medium text-gray-900">{trd.nombre}</div>
                                                <div className="text-sm text-gray-500">{trd.codigo} • v{trd.version}</div>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Badge variant="secondary" className="text-xs">
                                                    {trd.series_count} series
                                                </Badge>
                                                <Badge className={trd.estado === 'vigente' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}>
                                                    {trd.estado}
                                                </Badge>
                                            </div>
                                        </a>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-6 text-gray-500">
                                    <FileText className="h-8 w-8 mx-auto mb-2 text-gray-300" />
                                    <p className="text-sm">No hay TRDs relacionadas</p>
                                    <a href="/admin/trd" className="text-[#2a3d83] text-sm hover:underline mt-1 inline-block">
                                        Ver todas las TRDs
                                    </a>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Series Relacionadas */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <Folder className="h-5 w-5 text-purple-600" />
                                    Series Relacionadas
                                </CardTitle>
                                <CardDescription>
                                    Series documentales vinculadas a este CCD ({series_relacionadas.length})
                                </CardDescription>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {series_relacionadas.length > 0 ? (
                                <div className="space-y-2 max-h-80 overflow-y-auto">
                                    {series_relacionadas.map((serie) => (
                                        <a 
                                            key={serie.id}
                                            href={`/admin/series/${serie.id}`}
                                            className="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 hover:border-purple-300 transition-all"
                                        >
                                            <div>
                                                <div className="font-medium text-gray-900">{serie.nombre}</div>
                                                <div className="text-sm text-gray-500">{serie.codigo} • {serie.trd_nombre}</div>
                                            </div>
                                            <Badge variant="secondary" className="text-xs">
                                                {serie.subseries_count} subseries
                                            </Badge>
                                        </a>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-6 text-gray-500">
                                    <Folder className="h-8 w-8 mx-auto mb-2 text-gray-300" />
                                    <p className="text-sm">No hay series relacionadas</p>
                                    <a href="/admin/series" className="text-purple-600 text-sm hover:underline mt-1 inline-block">
                                        Ver todas las series
                                    </a>
                                </div>
                            )}
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
                                        {(ccd.versiones && ccd.versiones.length > 0) ? 'Aprobar Versión' : 'Aprobar CCD'}
                                    </Button>
                                )}
                                <Button variant="outline" onClick={() => setShowExportModal(true)}>
                                    <Download className="h-4 w-4 mr-2" />
                                    Exportar
                                </Button>
                                <Button variant="outline" onClick={openVersionModal}>
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

                {/* Modal Exportar */}
                <Dialog open={showExportModal} onOpenChange={setShowExportModal}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <Download className="h-5 w-5 text-[#2a3d83]" />
                                Exportar CCD
                            </DialogTitle>
                            <DialogDescription>
                                Seleccione el formato en el que desea exportar el Cuadro de Clasificación Documental
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-3 py-4">
                            {exportFormats.map((format) => {
                                const IconComponent = format.icon;
                                return (
                                    <button
                                        key={format.id}
                                        onClick={() => handleExport(format.id)}
                                        disabled={exportLoading}
                                        className="flex items-start gap-4 p-4 border rounded-lg hover:border-[#2a3d83] hover:bg-gray-50 transition-all text-left disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <div className={`p-2 rounded-lg ${format.color}`}>
                                            <IconComponent className="h-5 w-5" />
                                        </div>
                                        <div className="flex-1">
                                            <div className="font-medium text-gray-900">{format.name}</div>
                                            <div className="text-sm text-gray-500">{format.description}</div>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setShowExportModal(false)}>
                                Cancelar
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Modal Nueva Versión */}
                <Dialog open={showVersionModal} onOpenChange={setShowVersionModal}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <GitBranch className="h-5 w-5 text-[#2a3d83]" />
                                Crear Nueva Versión
                            </DialogTitle>
                            <DialogDescription>
                                Cree una nueva versión del CCD "{ccd.nombre}". La versión actual es {ccd.version}.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="version">Nueva Versión *</Label>
                                <Input
                                    id="version"
                                    value={versionForm.version}
                                    onChange={(e) => setVersionForm({...versionForm, version: e.target.value})}
                                    placeholder="Ej: 2.0, 1.1, etc."
                                />
                                <p className="text-xs text-gray-500">
                                    Versión actual: {ccd.version}
                                </p>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="cambios">Descripción de Cambios *</Label>
                                <Textarea
                                    id="cambios"
                                    value={versionForm.cambios}
                                    onChange={(e) => setVersionForm({...versionForm, cambios: e.target.value})}
                                    placeholder="Describa los cambios realizados en esta versión..."
                                    rows={4}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setShowVersionModal(false)}>
                                Cancelar
                            </Button>
                            <Button onClick={submitNewVersion} className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                                <GitBranch className="h-4 w-4 mr-2" />
                                Crear Versión
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Confirmación Eliminar Versión */}
                <Dialog open={showConfirmDelete} onOpenChange={setShowConfirmDelete}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-red-600">
                                <AlertCircle className="h-5 w-5" />
                                Eliminar Versión
                            </DialogTitle>
                            <DialogDescription>
                                ¿Está seguro de eliminar la versión <strong>{versionToAction?.version}</strong>?
                                <br/><br/>
                                Esta acción no se puede deshacer. Si es la versión actual, el CCD volverá al estado de la versión anterior.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setShowConfirmDelete(false)}>
                                Cancelar
                            </Button>
                            <Button onClick={handleDeleteVersion} variant="destructive">
                                Sí, eliminar versión
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Confirmación Revertir Versión */}
                <Dialog open={showConfirmRevert} onOpenChange={setShowConfirmRevert}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-blue-600">
                                <RotateCcw className="h-5 w-5" />
                                Revertir a Versión Anterior
                            </DialogTitle>
                            <DialogDescription>
                                ¿Está seguro de revertir a la versión <strong>{versionToAction?.version}</strong>?
                                <br/><br/>
                                Se creará un nuevo registro de versión. El CCD volverá a estado <strong>borrador</strong> y requerirá nueva aprobación.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setShowConfirmRevert(false)}>
                                Cancelar
                            </Button>
                            <Button onClick={handleRevertVersion} className="bg-blue-600 hover:bg-blue-700">
                                Sí, revertir versión
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
