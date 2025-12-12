import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
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
    Clock,
    Calendar,
    Save,
    X
} from 'lucide-react';
import { toast } from 'sonner';

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
    hijos?: CCDNivel[];
}

interface TiempoRetencion {
    id: number;
    trd_id: number;
    ccd_nivel_id: number;
    retencion_archivo_gestion: number;
    retencion_archivo_central: number;
    disposicion_final: string;
    soporte_fisico: boolean;
    soporte_electronico: boolean;
    soporte_hibrido: boolean;
    procedimiento?: string;
    observaciones?: string;
}

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
    descripcion?: string;
    version: number;
    estado: string;
    vigente: boolean;
    fecha_aprobacion?: string;
    fecha_vigencia_inicio?: string;
    fecha_vigencia_fin?: string;
    created_at: string;
    updated_at: string;
    cuadro_clasificacion?: CCD;
    creador?: {
        id: number;
        name: string;
    };
}

interface Props {
    trd: TRD;
    estructura: CCDNivel[];
    tiemposRetencion: Record<number, TiempoRetencion>;
    estadisticas: {
        series_count: number;
        expedientes_count: number;
        documentos_count: number;
        estado_actual: string;
        version_actual: number;
        niveles_con_tiempos: number;
        niveles_totales: number;
    };
    tieneDocumentosAsociados: boolean;
}

// Componente para renderizar un nodo del árbol con tiempos de retención
const TreeNodeWithRetention = ({ 
    node, 
    tiemposRetencion,
    onEditTiempo,
    onDeleteTiempo,
    trdId
}: { 
    node: CCDNivel; 
    tiemposRetencion: Record<number, TiempoRetencion>;
    onEditTiempo: (node: CCDNivel, tiempo?: TiempoRetencion) => void;
    onDeleteTiempo: (nivelId: number) => void;
    trdId: number;
}) => {
    const [isExpanded, setIsExpanded] = useState(true);
    const hasChildren = node.hijos && node.hijos.length > 0;
    const tiempoRetencion = tiemposRetencion[node.id];
    const hasTiempo = !!tiempoRetencion;

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

    const getDisposicionColor = (disposicion: string) => {
        const colors = {
            'CT': 'bg-green-100 text-green-800',
            'E': 'bg-red-100 text-red-800',
            'D': 'bg-blue-100 text-blue-800',
            'S': 'bg-yellow-100 text-yellow-800',
            'M': 'bg-purple-100 text-purple-800',
        };
        return colors[disposicion as keyof typeof colors] || 'bg-gray-100 text-gray-800';
    };

    const getDisposicionTexto = (disposicion: string) => {
        const textos = {
            'CT': 'Conservación Total',
            'E': 'Eliminación',
            'D': 'Digitalización',
            'S': 'Selección',
            'M': 'Microfilmación',
        };
        return textos[disposicion as keyof typeof textos] || disposicion;
    };

    return (
        <div className="ml-4">
            <div className="flex items-start gap-2 py-2 group hover:bg-gray-50 rounded-md px-2 border-b border-gray-100">
                {/* Expand/Collapse Button */}
                <button
                    onClick={() => setIsExpanded(!isExpanded)}
                    className="p-0.5 hover:bg-gray-200 rounded mt-1"
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
                <div className="mt-1">
                    {getTipoIcon(node.tipo_nivel)}
                </div>

                {/* Node Info */}
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
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

                    {/* Tiempos de Retención */}
                    {hasTiempo && (
                        <div className="mt-2 p-2 bg-blue-50 rounded-md border border-blue-200">
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                                <div>
                                    <span className="font-semibold text-gray-600">AG:</span>
                                    <span className="ml-1 text-gray-900">{tiempoRetencion.retencion_archivo_gestion} años</span>
                                </div>
                                <div>
                                    <span className="font-semibold text-gray-600">AC:</span>
                                    <span className="ml-1 text-gray-900">{tiempoRetencion.retencion_archivo_central} años</span>
                                </div>
                                <div>
                                    <span className="font-semibold text-gray-600">Disposición:</span>
                                    <Badge className={`ml-1 text-xs ${getDisposicionColor(tiempoRetencion.disposicion_final)}`}>
                                        {tiempoRetencion.disposicion_final}
                                    </Badge>
                                </div>
                                <div className="flex gap-1">
                                    {tiempoRetencion.soporte_fisico && (
                                        <Badge variant="outline" className="text-xs">F</Badge>
                                    )}
                                    {tiempoRetencion.soporte_electronico && (
                                        <Badge variant="outline" className="text-xs">E</Badge>
                                    )}
                                    {tiempoRetencion.soporte_hibrido && (
                                        <Badge variant="outline" className="text-xs">H</Badge>
                                    )}
                                </div>
                            </div>
                            {tiempoRetencion.procedimiento && (
                                <div className="mt-1 text-xs text-gray-600">
                                    <span className="font-semibold">Procedimiento:</span> {tiempoRetencion.procedimiento}
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {/* Actions */}
                <div className="flex items-center gap-1 mt-1">
                    <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => onEditTiempo(node, tiempoRetencion)}
                        className="h-7 w-7 p-0 text-blue-600 hover:text-blue-800 hover:bg-blue-50"
                        title={hasTiempo ? "Editar tiempo de retención" : "Agregar tiempo de retención"}
                    >
                        {hasTiempo ? <Edit className="h-3 w-3" /> : <Plus className="h-3 w-3" />}
                    </Button>
                    {hasTiempo && (
                        <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => onDeleteTiempo(node.id)}
                            className="h-7 w-7 p-0 hover:bg-red-100 hover:text-red-600"
                            title="Eliminar tiempo de retención"
                        >
                            <Trash2 className="h-3 w-3" />
                        </Button>
                    )}
                </div>
            </div>

            {/* Children */}
            {isExpanded && hasChildren && (
                <div className="border-l-2 border-gray-200 ml-2">
                    {node.hijos!.map((child) => (
                        <TreeNodeWithRetention
                            key={child.id}
                            node={child}
                            tiemposRetencion={tiemposRetencion}
                            onEditTiempo={onEditTiempo}
                            onDeleteTiempo={onDeleteTiempo}
                            trdId={trdId}
                        />
                    ))}
                </div>
            )}
        </div>
    );
};

export default function TRDShow({ trd, estructura, tiemposRetencion, estadisticas, tieneDocumentosAsociados }: Props) {
    const [showTiempoModal, setShowTiempoModal] = useState(false);
    const [selectedNivel, setSelectedNivel] = useState<CCDNivel | null>(null);
    const [tiempoForm, setTiempoForm] = useState({
        retencion_archivo_gestion: 0,
        retencion_archivo_central: 0,
        disposicion_final: 'CT',
        soporte_fisico: false,
        soporte_electronico: false,
        soporte_hibrido: false,
        procedimiento: '',
        observaciones: '',
    });

    const breadcrumbItems = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Administración', href: '#' },
        { title: 'Tablas de Retención Documental', href: '/admin/trd' },
        { title: trd.nombre, href: '#' },
    ];

    const handleEditTiempo = (nivel: CCDNivel, tiempo?: TiempoRetencion) => {
        setSelectedNivel(nivel);
        if (tiempo) {
            setTiempoForm({
                retencion_archivo_gestion: tiempo.retencion_archivo_gestion,
                retencion_archivo_central: tiempo.retencion_archivo_central,
                disposicion_final: tiempo.disposicion_final,
                soporte_fisico: tiempo.soporte_fisico,
                soporte_electronico: tiempo.soporte_electronico,
                soporte_hibrido: tiempo.soporte_hibrido,
                procedimiento: tiempo.procedimiento || '',
                observaciones: tiempo.observaciones || '',
            });
        } else {
            setTiempoForm({
                retencion_archivo_gestion: 0,
                retencion_archivo_central: 0,
                disposicion_final: 'CT',
                soporte_fisico: false,
                soporte_electronico: false,
                soporte_hibrido: false,
                procedimiento: '',
                observaciones: '',
            });
        }
        setShowTiempoModal(true);
    };

    const handleDeleteTiempo = (nivelId: number) => {
        if (confirm('¿Está seguro de eliminar este tiempo de retención?')) {
            router.delete(`/admin/trd/${trd.id}/tiempo-retencion/${nivelId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Tiempo de retención eliminado exitosamente');
                },
                onError: () => {
                    toast.error('Error al eliminar el tiempo de retención');
                }
            });
        }
    };

    const submitTiempo = () => {
        if (!selectedNivel) return;

        router.post(`/admin/trd/${trd.id}/tiempo-retencion`, {
            ccd_nivel_id: selectedNivel.id,
            ...tiempoForm,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Tiempo de retención guardado exitosamente');
                setShowTiempoModal(false);
                setSelectedNivel(null);
            },
            onError: (errors) => {
                console.error('Errores:', errors);
                toast.error('Error al guardar el tiempo de retención');
            }
        });
    };

    const getEstadoBadge = (estado: string) => {
        const colors = {
            'borrador': 'bg-gray-100 text-gray-800',
            'revision': 'bg-yellow-100 text-yellow-800',
            'aprobada': 'bg-green-100 text-green-800',
            'vigente': 'bg-blue-100 text-blue-800',
            'historica': 'bg-purple-100 text-purple-800',
        };
        return colors[estado as keyof typeof colors] || 'bg-gray-100 text-gray-800';
    };

    const formatDate = (date?: string) => {
        if (!date) return 'No especificada';
        return new Date(date).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title={`TRD - ${trd.nombre}`} />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Link
                        href="/admin/trd"
                        className="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Volver a TRDs
                    </Link>
                </div>

                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <FileText className="h-8 w-8 text-[#2a3d83]" />
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900">
                                {trd.nombre}
                            </h1>
                            <p className="text-sm text-gray-600">{trd.codigo} • Versión {trd.version}</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <Badge className={getEstadoBadge(trd.estado)}>
                            {trd.estado}
                        </Badge>
                        {trd.vigente && (
                            <Badge className="bg-green-100 text-green-800">
                                <CheckCircle className="h-3 w-3 mr-1" />
                                Vigente
                            </Badge>
                        )}
                        <Link href={`/admin/trd/${trd.id}/edit`}>
                            <Button className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                                <Edit className="h-4 w-4 mr-2" />
                                Editar TRD
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Información General */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium text-gray-600">CCD Asociado</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {trd.cuadro_clasificacion ? (
                                <div>
                                    <p className="font-semibold">{trd.cuadro_clasificacion.nombre}</p>
                                    <p className="text-sm text-gray-600">{trd.cuadro_clasificacion.codigo} • v{trd.cuadro_clasificacion.version}</p>
                                </div>
                            ) : (
                                <p className="text-sm text-gray-500">No asociado</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium text-gray-600">Fechas</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <div>
                                <span className="font-semibold">Aprobación:</span>
                                <p className="text-gray-600">{formatDate(trd.fecha_aprobacion)}</p>
                            </div>
                            <div>
                                <span className="font-semibold">Vigencia:</span>
                                <p className="text-gray-600">
                                    {formatDate(trd.fecha_vigencia_inicio)} - {formatDate(trd.fecha_vigencia_fin)}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium text-gray-600">Estadísticas</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <div className="flex justify-between">
                                <span>Niveles con tiempos:</span>
                                <span className="font-semibold">{estadisticas.niveles_con_tiempos} / {estadisticas.niveles_totales}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Series:</span>
                                <span className="font-semibold">{estadisticas.series_count}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Expedientes:</span>
                                <span className="font-semibold">{estadisticas.expedientes_count}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Estructura Jerárquica con Tiempos de Retención */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <FolderTree className="h-5 w-5 text-[#2a3d83]" />
                                    Estructura Jerárquica
                                </CardTitle>
                                <CardDescription>
                                    Gestione la estructura del cuadro de clasificación documental con tiempos de retención
                                </CardDescription>
                            </div>
                            <Button 
                                variant="outline"
                                onClick={() => window.location.href = `/admin/trd/${trd.id}/export-pdf`}
                            >
                                <Download className="h-4 w-4 mr-2" />
                                Exportar PDF
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {estructura.length === 0 ? (
                            <div className="text-center py-12 text-gray-500">
                                <FolderTree className="h-12 w-12 mx-auto mb-4 text-gray-300" />
                                <p>No hay estructura configurada</p>
                                <p className="text-sm">El CCD asociado no tiene niveles definidos</p>
                            </div>
                        ) : (
                            <div className="space-y-1">
                                {estructura.map((nodo) => (
                                    <TreeNodeWithRetention
                                        key={nodo.id}
                                        node={nodo}
                                        tiemposRetencion={tiemposRetencion}
                                        onEditTiempo={handleEditTiempo}
                                        onDeleteTiempo={handleDeleteTiempo}
                                        trdId={trd.id}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Modal para Agregar/Editar Tiempo de Retención */}
                <Dialog open={showTiempoModal} onOpenChange={setShowTiempoModal}>
                    <DialogContent className="sm:max-w-[600px]">
                        <DialogHeader>
                            <DialogTitle>
                                {selectedNivel && tiemposRetencion[selectedNivel.id] 
                                    ? 'Editar Tiempo de Retención' 
                                    : 'Agregar Tiempo de Retención'}
                            </DialogTitle>
                            <DialogDescription>
                                {selectedNivel && (
                                    <>
                                        Nivel: <strong>{selectedNivel.nombre}</strong> ({selectedNivel.codigo})
                                    </>
                                )}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            {/* Tiempos de Retención */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Archivo de Gestión (AG) *</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        value={tiempoForm.retencion_archivo_gestion}
                                        onChange={(e) => setTiempoForm({...tiempoForm, retencion_archivo_gestion: parseInt(e.target.value) || 0})}
                                        placeholder="Años"
                                    />
                                    <p className="text-xs text-gray-500">Años en archivo de gestión</p>
                                </div>
                                <div className="space-y-2">
                                    <Label>Archivo Central (AC) *</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        value={tiempoForm.retencion_archivo_central}
                                        onChange={(e) => setTiempoForm({...tiempoForm, retencion_archivo_central: parseInt(e.target.value) || 0})}
                                        placeholder="Años"
                                    />
                                    <p className="text-xs text-gray-500">Años en archivo central</p>
                                </div>
                            </div>

                            {/* Disposición Final */}
                            <div className="space-y-2">
                                <Label>Disposición Final *</Label>
                                <Select 
                                    value={tiempoForm.disposicion_final} 
                                    onValueChange={(value) => setTiempoForm({...tiempoForm, disposicion_final: value})}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="CT">CT - Conservación Total</SelectItem>
                                        <SelectItem value="E">E - Eliminación</SelectItem>
                                        <SelectItem value="D">D - Digitalización</SelectItem>
                                        <SelectItem value="S">S - Selección</SelectItem>
                                        <SelectItem value="M">M - Microfilmación</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Soportes */}
                            <div className="space-y-2">
                                <Label>Soportes</Label>
                                <div className="flex gap-4">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="soporte-fisico"
                                            checked={tiempoForm.soporte_fisico}
                                            onCheckedChange={(checked) => setTiempoForm({...tiempoForm, soporte_fisico: checked as boolean})}
                                        />
                                        <Label htmlFor="soporte-fisico" className="font-normal">Físico</Label>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="soporte-electronico"
                                            checked={tiempoForm.soporte_electronico}
                                            onCheckedChange={(checked) => setTiempoForm({...tiempoForm, soporte_electronico: checked as boolean})}
                                        />
                                        <Label htmlFor="soporte-electronico" className="font-normal">Electrónico</Label>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="soporte-hibrido"
                                            checked={tiempoForm.soporte_hibrido}
                                            onCheckedChange={(checked) => setTiempoForm({...tiempoForm, soporte_hibrido: checked as boolean})}
                                        />
                                        <Label htmlFor="soporte-hibrido" className="font-normal">Híbrido</Label>
                                    </div>
                                </div>
                            </div>

                            {/* Procedimiento */}
                            <div className="space-y-2">
                                <Label>Procedimiento</Label>
                                <Textarea
                                    value={tiempoForm.procedimiento}
                                    onChange={(e) => setTiempoForm({...tiempoForm, procedimiento: e.target.value})}
                                    placeholder="Descripción del procedimiento de disposición final"
                                    rows={3}
                                />
                            </div>

                            {/* Observaciones */}
                            <div className="space-y-2">
                                <Label>Observaciones</Label>
                                <Textarea
                                    value={tiempoForm.observaciones}
                                    onChange={(e) => setTiempoForm({...tiempoForm, observaciones: e.target.value})}
                                    placeholder="Observaciones adicionales"
                                    rows={2}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setShowTiempoModal(false)}>
                                <X className="h-4 w-4 mr-2" />
                                Cancelar
                            </Button>
                            <Button onClick={submitTiempo} className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                                <Save className="h-4 w-4 mr-2" />
                                Guardar
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
