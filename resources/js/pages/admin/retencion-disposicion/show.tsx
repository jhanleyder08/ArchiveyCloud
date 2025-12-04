import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { toast } from 'sonner';
import { 
    ArrowLeft, Clock, AlertTriangle, Archive, Send, Trash2, 
    PauseCircle, PlayCircle, Lock, Unlock, AlertCircle, CheckCircle,
    FileText, Calendar, Bell, User, Info, History, Shield,
    Edit, Save, X, ChevronRight
} from 'lucide-react';

// Simplified interfaces for the component
interface ProcesoRetencion {
    id: number;
    codigo_proceso: string;
    tipo_entidad: string;
    documento?: any;
    expediente?: any;
    trd: any;
    serie_documental?: any;
    subserie_documental?: any;
    fecha_creacion_documento: string;
    periodo_retencion_archivo_gestion: number;
    periodo_retencion_archivo_central: number;
    fecha_vencimiento_gestion: string;
    fecha_vencimiento_central: string;
    fecha_alerta_previa: string;
    estado: string;
    accion_disposicion?: string;
    aplazado: boolean;
    fecha_aplazamiento?: string;
    razon_aplazamiento?: string;
    fecha_fin_aplazamiento?: string;
    usuario_aplazamiento?: any;
    alertas_activas: boolean;
    dias_alerta_previa: number;
    canales_notificacion?: string[];
    ultima_alerta_enviada?: string;
    hash_integridad?: string;
    bloqueado_eliminacion: boolean;
    razon_bloqueo?: string;
    metadatos_adicionales?: any;
    observaciones?: string;
    usuario_creador?: any;
    usuario_modificador?: any;
    historial_acciones?: any[];
    alertas?: any[];
    created_at: string;
    updated_at: string;
}

interface AccionDisponible {
    key: string;
    label: string;
    description: string;
    icon: string;
    color: string;
}

interface Props {
    proceso: ProcesoRetencion;
    diasRestantes: number;
    alertasActivas: number;
    integridadValida: boolean;
    acciones_disponibles: AccionDisponible[];
    flash?: {
        success?: string;
        error?: string;
    };
}

export default function AdminRetencionDisposicionShow({ 
    proceso, 
    diasRestantes, 
    alertasActivas,
    integridadValida,
    acciones_disponibles, 
    flash 
}: Props) {
    const [showDisposicionModal, setShowDisposicionModal] = useState(false);
    const [showAplazarModal, setShowAplazarModal] = useState(false);
    const [showBloqueoModal, setShowBloqueoModal] = useState(false);
    
    // Form states
    const [disposicionForm, setDisposicionForm] = useState({
        accion: '',
        observaciones: '',
        confirmacion: false
    });
    
    const [aplazarForm, setAplazarForm] = useState({
        fecha_fin_aplazamiento: '',
        razon_aplazamiento: '',
        confirmacion: false
    });
    
    const [bloqueoForm, setBloqueoForm] = useState({
        razon_bloqueo: ''
    });

    // Helper functions
    const getEstadoBadgeColor = (estado: string) => {
        switch (estado) {
            case 'activo': return 'bg-green-100 text-green-800';
            case 'alerta_previa': return 'bg-yellow-100 text-yellow-800';
            case 'vencido': return 'bg-red-100 text-red-800';
            case 'en_disposicion': return 'bg-blue-100 text-blue-800';
            case 'transferido': return 'bg-purple-100 text-purple-800';
            case 'eliminado': return 'bg-gray-100 text-gray-800';
            case 'conservado': return 'bg-emerald-100 text-emerald-800';
            case 'aplazado': return 'bg-orange-100 text-orange-800';
            case 'suspendido': return 'bg-gray-100 text-gray-600';
            default: return 'bg-gray-100 text-gray-600';
        }
    };

    const formatEstado = (estado: string) => {
        const estados: Record<string, string> = {
            'activo': 'Activo',
            'alerta_previa': 'Alerta Previa',
            'vencido': 'Vencido',
            'en_disposicion': 'En Disposición',
            'transferido': 'Transferido',
            'eliminado': 'Eliminado',
            'conservado': 'Conservado',
            'aplazado': 'Aplazado',
            'suspendido': 'Suspendido'
        };
        return estados[estado] || estado;
    };

    // Action handlers
    const handleEjecutarDisposicion = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(`/admin/retencion-disposicion/${proceso.id}/ejecutar-disposicion`, disposicionForm, {
            onSuccess: () => {
                setShowDisposicionModal(false);
                toast.success('Acción de disposición ejecutada correctamente');
            },
            onError: () => {
                toast.error('Error al ejecutar la disposición');
            }
        });
    };

    const handleAplazarDisposicion = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(`/admin/retencion-disposicion/${proceso.id}/aplazar`, aplazarForm, {
            onSuccess: () => {
                setShowAplazarModal(false);
                toast.success('Disposición aplazada correctamente');
            },
            onError: () => {
                toast.error('Error al aplazar la disposición');
            }
        });
    };

    const handleBloquearEliminacion = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(`/admin/retencion-disposicion/${proceso.id}/bloquear-eliminacion`, bloqueoForm, {
            onSuccess: () => {
                setShowBloqueoModal(false);
                toast.success('Eliminación bloqueada correctamente');
            },
            onError: () => {
                toast.error('Error al bloquear la eliminación');
            }
        });
    };

    return (
        <AppLayout>
            <Head title={`Proceso ${proceso.codigo_proceso}`} />

            <div className="p-6">
                {/* Header */}
                <div className="mb-6">
                    <div className="flex items-center gap-2 text-sm text-gray-600 mb-2">
                        <Link href="/admin/retencion-disposicion" className="hover:text-gray-900">
                            Retención y Disposición
                        </Link>
                        <ChevronRight className="h-4 w-4" />
                        <span className="text-gray-900">{proceso.codigo_proceso}</span>
                    </div>
                    
                    <div className="flex justify-between items-start">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">
                                Proceso de Retención: {proceso.codigo_proceso}
                            </h1>
                            <p className="mt-1 text-sm text-gray-600">
                                {proceso.tipo_entidad === 'documento' 
                                    ? `Documento: ${proceso.documento?.titulo || 'Sin título'}`
                                    : `Expediente: ${proceso.expediente?.titulo || 'Sin título'}`
                                }
                            </p>
                        </div>
                        
                        <Link href="/admin/retencion-disposicion">
                            <Button variant="outline">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Critical alerts */}
                {diasRestantes <= 0 && (
                    <div className="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                        <div className="flex">
                            <AlertTriangle className="h-5 w-5 text-red-400" />
                            <div className="ml-3">
                                <p className="text-sm text-red-700">
                                    <strong>Atención:</strong> Este proceso ha vencido y requiere disposición inmediata.
                                    {diasRestantes < 0 && ` Vencido hace ${Math.abs(diasRestantes)} días.`}
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Main content */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Process Information */}
                    <div className="lg:col-span-2">
                        <Card className="p-6">
                            <h2 className="text-lg font-semibold mb-4">Información del Proceso</h2>
                            
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-gray-500">Estado actual</p>
                                    <Badge className={`${getEstadoBadgeColor(proceso.estado)} mt-1`}>
                                        {formatEstado(proceso.estado)}
                                    </Badge>
                                </div>
                                
                                <div>
                                    <p className="text-sm text-gray-500">Tipo de entidad</p>
                                    <p className="font-medium capitalize">{proceso.tipo_entidad}</p>
                                </div>
                                
                                <div>
                                    <p className="text-sm text-gray-500">TRD aplicada</p>
                                    <p className="font-medium">{proceso.trd?.nombre || 'No especificada'}</p>
                                    {proceso.trd?.version && (
                                        <p className="text-xs text-gray-500">Versión {proceso.trd.version}</p>
                                    )}
                                </div>
                                
                                {proceso.serie_documental && (
                                    <div>
                                        <p className="text-sm text-gray-500">Serie documental</p>
                                        <p className="font-medium">{proceso.serie_documental.nombre}</p>
                                    </div>
                                )}
                                
                                <div>
                                    <p className="text-sm text-gray-500">Fecha creación documento</p>
                                    <p className="font-medium">
                                        {new Date(proceso.fecha_creacion_documento).toLocaleDateString('es-ES')}
                                    </p>
                                </div>
                                
                                <div>
                                    <p className="text-sm text-gray-500">Vencimiento archivo gestión</p>
                                    <p className="font-medium">
                                        {new Date(proceso.fecha_vencimiento_gestion).toLocaleDateString('es-ES')}
                                    </p>
                                </div>
                                
                                <div>
                                    <p className="text-sm text-gray-500">Vencimiento archivo central</p>
                                    <p className="font-medium">
                                        {new Date(proceso.fecha_vencimiento_central).toLocaleDateString('es-ES')}
                                    </p>
                                </div>
                                
                                <div>
                                    <p className="text-sm text-gray-500">Días hasta vencimiento</p>
                                    <p className={`font-bold ${diasRestantes <= 0 ? 'text-red-600' : diasRestantes <= 30 ? 'text-yellow-600' : 'text-green-600'}`}>
                                        {diasRestantes > 0 
                                            ? `${diasRestantes} días`
                                            : diasRestantes === 0 
                                            ? 'Vence hoy'
                                            : `Vencido hace ${Math.abs(diasRestantes)} días`
                                        }
                                    </p>
                                </div>
                            </div>

                            {/* Aplazamiento info if applicable */}
                            {proceso.aplazado && (
                                <div className="mt-4 p-4 bg-orange-50 rounded-lg">
                                    <h3 className="text-sm font-semibold text-orange-800 mb-2">
                                        <PauseCircle className="inline h-4 w-4 mr-1" />
                                        Proceso Aplazado
                                    </h3>
                                    <div className="space-y-2 text-sm">
                                        {proceso.fecha_fin_aplazamiento && (
                                            <p>
                                                <span className="text-gray-600">Fin del aplazamiento:</span>{' '}
                                                {new Date(proceso.fecha_fin_aplazamiento).toLocaleDateString('es-ES')}
                                            </p>
                                        )}
                                        {proceso.razon_aplazamiento && (
                                            <p>
                                                <span className="text-gray-600">Razón:</span>{' '}
                                                {proceso.razon_aplazamiento}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}
                        </Card>

                        {/* History and alerts tabs */}
                        <Card className="p-6 mt-6">
                            <Tabs defaultValue="historial">
                                <TabsList>
                                    <TabsTrigger value="historial">Historial</TabsTrigger>
                                    <TabsTrigger value="alertas">Alertas</TabsTrigger>
                                </TabsList>

                                <TabsContent value="historial" className="mt-4">
                                    <div className="space-y-3">
                                        {proceso.historial_acciones && proceso.historial_acciones.length > 0 ? (
                                            proceso.historial_acciones.slice(0, 5).map((accion, idx) => (
                                                <div key={idx} className="border-l-2 border-gray-200 pl-4 pb-3">
                                                    <p className="text-sm">{accion.descripcion_accion}</p>
                                                    <p className="text-xs text-gray-500 mt-1">
                                                        {new Date(accion.fecha_accion).toLocaleString('es-ES')}
                                                    </p>
                                                </div>
                                            ))
                                        ) : (
                                            <p className="text-sm text-gray-500">No hay historial disponible</p>
                                        )}
                                    </div>
                                </TabsContent>

                                <TabsContent value="alertas" className="mt-4">
                                    <div className="space-y-3">
                                        {proceso.alertas && proceso.alertas.length > 0 ? (
                                            proceso.alertas.slice(0, 5).map((alerta, idx) => (
                                                <div key={idx} className="border rounded p-3">
                                                    <h4 className="font-medium text-sm">{alerta.titulo_alerta}</h4>
                                                    <p className="text-xs text-gray-600 mt-1">{alerta.mensaje_alerta}</p>
                                                </div>
                                            ))
                                        ) : (
                                            <p className="text-sm text-gray-500">No hay alertas disponibles</p>
                                        )}
                                    </div>
                                </TabsContent>
                            </Tabs>
                        </Card>
                    </div>

                    {/* Actions panel */}
                    <div className="space-y-4">
                        <Card className="p-6">
                            <h2 className="text-lg font-semibold mb-4">Acciones Disponibles</h2>
                            
                            <div className="space-y-2">
                                {acciones_disponibles.map((accion) => (
                                    <Button
                                        key={accion.key}
                                        variant="outline"
                                        className="w-full justify-start"
                                        onClick={() => {
                                            if (accion.key === 'aplazar') {
                                                setShowAplazarModal(true);
                                            } else {
                                                setDisposicionForm({ ...disposicionForm, accion: accion.key });
                                                setShowDisposicionModal(true);
                                            }
                                        }}
                                    >
                                        <span className="ml-2">{accion.label}</span>
                                    </Button>
                                ))}

                                {proceso.aplazado && (
                                    <Button
                                        variant="outline"
                                        className="w-full justify-start"
                                        onClick={() => router.post(`/admin/retencion-disposicion/${proceso.id}/reactivar`)}
                                    >
                                        <PlayCircle className="h-4 w-4 mr-2" />
                                        Reactivar Proceso
                                    </Button>
                                )}

                                {!proceso.bloqueado_eliminacion ? (
                                    <Button
                                        variant="outline"
                                        className="w-full justify-start"
                                        onClick={() => setShowBloqueoModal(true)}
                                    >
                                        <Lock className="h-4 w-4 mr-2" />
                                        Bloquear Eliminación
                                    </Button>
                                ) : (
                                    <Button
                                        variant="outline"
                                        className="w-full justify-start"
                                        onClick={() => router.post(`/admin/retencion-disposicion/${proceso.id}/desbloquear-eliminacion`)}
                                    >
                                        <Unlock className="h-4 w-4 mr-2" />
                                        Desbloquear Eliminación
                                    </Button>
                                )}
                            </div>
                        </Card>

                        <Card className="p-6">
                            <h3 className="text-sm font-semibold mb-3">Indicadores</h3>
                            <div className="space-y-3">
                                <div className="flex justify-between items-center">
                                    <span className="text-sm text-gray-600">Alertas activas</span>
                                    <Badge variant={alertasActivas > 0 ? "destructive" : "secondary"}>
                                        {alertasActivas}
                                    </Badge>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-sm text-gray-600">Integridad</span>
                                    <Badge variant={integridadValida ? "default" : "destructive"}>
                                        {integridadValida ? 'Válida' : 'Comprometida'}
                                    </Badge>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>

            {/* Modals */}
            <Dialog open={showDisposicionModal} onOpenChange={setShowDisposicionModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Ejecutar Disposición</DialogTitle>
                        <DialogDescription>
                            Esta acción es irreversible. Por favor, confirme que desea proceder.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleEjecutarDisposicion}>
                        <div className="space-y-4 py-4">
                            <div>
                                <Label>Observaciones</Label>
                                <Textarea
                                    value={disposicionForm.observaciones}
                                    onChange={(e) => setDisposicionForm({...disposicionForm, observaciones: e.target.value})}
                                    placeholder="Ingrese observaciones adicionales..."
                                />
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="confirmacion"
                                    checked={disposicionForm.confirmacion}
                                    onCheckedChange={(checked) => 
                                        setDisposicionForm({...disposicionForm, confirmacion: !!checked})
                                    }
                                />
                                <Label htmlFor="confirmacion">
                                    Confirmo que deseo ejecutar esta acción
                                </Label>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setShowDisposicionModal(false)}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={!disposicionForm.confirmacion}>
                                Ejecutar
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={showAplazarModal} onOpenChange={setShowAplazarModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Aplazar Disposición</DialogTitle>
                        <DialogDescription>
                            Indique la fecha hasta la cual desea aplazar la disposición.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleAplazarDisposicion}>
                        <div className="space-y-4 py-4">
                            <div>
                                <Label>Fecha fin de aplazamiento</Label>
                                <Input
                                    type="date"
                                    value={aplazarForm.fecha_fin_aplazamiento}
                                    onChange={(e) => setAplazarForm({...aplazarForm, fecha_fin_aplazamiento: e.target.value})}
                                    required
                                />
                            </div>
                            <div>
                                <Label>Razón del aplazamiento</Label>
                                <Textarea
                                    value={aplazarForm.razon_aplazamiento}
                                    onChange={(e) => setAplazarForm({...aplazarForm, razon_aplazamiento: e.target.value})}
                                    placeholder="Explique la razón del aplazamiento..."
                                    required
                                />
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="confirmacion-aplazar"
                                    checked={aplazarForm.confirmacion}
                                    onCheckedChange={(checked) => 
                                        setAplazarForm({...aplazarForm, confirmacion: !!checked})
                                    }
                                />
                                <Label htmlFor="confirmacion-aplazar">
                                    Confirmo el aplazamiento
                                </Label>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setShowAplazarModal(false)}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={!aplazarForm.confirmacion}>
                                Aplazar
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
