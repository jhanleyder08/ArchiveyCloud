import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    CheckCircle, 
    XCircle, 
    FileText, 
    User, 
    Calendar, 
    Clock,
    AlertTriangle,
    MessageSquare,
    Shield,
    GitBranch,
    Send
} from 'lucide-react';

interface WorkflowData {
    id: number;
    estado: string;
    descripcion: string;
    prioridad: string;
    fecha_solicitud: string;
    fecha_vencimiento: string;
    documento: {
        id: number;
        nombre: string;
        codigo: string;
    };
    solicitante: string;
    nivel_actual: number;
    total_niveles: number;
}

interface Props {
    workflow: WorkflowData;
}

interface FormData {
    accion: 'aprobado' | 'rechazado' | '';
    comentarios: string;
    archivos_adjuntos: File[];
}

export default function WorkflowAprobar({ workflow }: Props) {
    const [mostrarConfirmacion, setMostrarConfirmacion] = useState(false);
    
    const { data, setData, post, processing, errors } = useForm<FormData>({
        accion: '',
        comentarios: '',
        archivos_adjuntos: []
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!data.accion) {
            return;
        }

        if (data.accion === 'rechazado' && !data.comentarios.trim()) {
            return;
        }

        setMostrarConfirmacion(true);
    };

    const confirmarAccion = () => {
        post(`/admin/workflow/${workflow.id}/procesar`, {
            onSuccess: () => {
                setMostrarConfirmacion(false);
            },
            onError: () => {
                setMostrarConfirmacion(false);
            }
        });
    };

    const getPrioridadBadge = (prioridad: string) => {
        const badges = {
            'Crítica': <Badge variant="destructive">Crítica</Badge>,
            'Alta': <Badge className="bg-orange-100 text-orange-800">Alta</Badge>,
            'Media': <Badge variant="outline" className="text-blue-600">Media</Badge>,
            'Baja': <Badge variant="outline" className="text-gray-600">Baja</Badge>
        };
        return badges[prioridad as keyof typeof badges] || <Badge variant="outline">{prioridad}</Badge>;
    };

    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const estaVencido = new Date(workflow.fecha_vencimiento) < new Date();

    return (
        <AppLayout>
            <Head title={`Aprobar Workflow - ${workflow.documento.nombre}`} />
            
            <div className="container mx-auto py-6">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <GitBranch className="w-8 h-8 text-blue-600" />
                            Aprobar Documento
                        </h1>
                        <p className="text-gray-600 mt-1">
                            Revisa y toma una decisión sobre este documento
                        </p>
                    </div>
                    <Link href={`/admin/workflow/${workflow.id}`}>
                        <Button variant="outline">
                            Volver a Detalles
                        </Button>
                    </Link>
                </div>

                {/* Alertas */}
                {estaVencido && (
                    <Alert className="mb-6 border-red-200 bg-red-50">
                        <AlertTriangle className="h-4 w-4 text-red-600" />
                        <AlertDescription className="text-red-800">
                            <strong>¡Documento vencido!</strong> Este workflow ha excedido su fecha límite de aprobación.
                        </AlertDescription>
                    </Alert>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Formulario de Aprobación */}
                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <CheckCircle className="w-5 h-5 text-green-600" />
                                    Decisión de Aprobación
                                </CardTitle>
                                <CardDescription>
                                    Selecciona tu decisión y proporciona comentarios sobre el documento
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSubmit} className="space-y-6">
                                    {/* Selección de Acción */}
                                    <div className="space-y-4">
                                        <Label className="text-base font-medium">¿Cuál es tu decisión? *</Label>
                                        <RadioGroup 
                                            value={data.accion} 
                                            onValueChange={(value) => setData('accion', value as 'aprobado' | 'rechazado')}
                                            className="grid grid-cols-1 md:grid-cols-2 gap-4"
                                        >
                                            <div className="flex items-center space-x-3 border rounded-lg p-4 hover:bg-green-50 hover:border-green-300 transition-colors">
                                                <RadioGroupItem value="aprobado" id="aprobado" />
                                                <Label 
                                                    htmlFor="aprobado" 
                                                    className="flex items-center gap-2 cursor-pointer flex-1"
                                                >
                                                    <CheckCircle className="w-5 h-5 text-green-600" />
                                                    <div>
                                                        <div className="font-medium text-green-700">Aprobar Documento</div>
                                                        <div className="text-sm text-green-600">El documento cumple con los requisitos</div>
                                                    </div>
                                                </Label>
                                            </div>
                                            
                                            <div className="flex items-center space-x-3 border rounded-lg p-4 hover:bg-red-50 hover:border-red-300 transition-colors">
                                                <RadioGroupItem value="rechazado" id="rechazado" />
                                                <Label 
                                                    htmlFor="rechazado" 
                                                    className="flex items-center gap-2 cursor-pointer flex-1"
                                                >
                                                    <XCircle className="w-5 h-5 text-red-600" />
                                                    <div>
                                                        <div className="font-medium text-red-700">Rechazar Documento</div>
                                                        <div className="text-sm text-red-600">El documento requiere correcciones</div>
                                                    </div>
                                                </Label>
                                            </div>
                                        </RadioGroup>
                                        {errors.accion && (
                                            <p className="text-sm text-red-600">{errors.accion}</p>
                                        )}
                                    </div>

                                    <Separator />

                                    {/* Comentarios */}
                                    <div className="space-y-3">
                                        <Label htmlFor="comentarios" className="text-base font-medium">
                                            Comentarios {data.accion === 'rechazado' && <span className="text-red-500">*</span>}
                                        </Label>
                                        <div className="space-y-2">
                                            <Textarea
                                                id="comentarios"
                                                placeholder={
                                                    data.accion === 'aprobado' 
                                                        ? "Comentarios adicionales sobre la aprobación (opcional)..."
                                                        : data.accion === 'rechazado'
                                                            ? "Explica los motivos del rechazo y qué correcciones son necesarias..."
                                                            : "Proporciona tu retroalimentación sobre el documento..."
                                                }
                                                value={data.comentarios}
                                                onChange={(e) => setData('comentarios', e.target.value)}
                                                rows={6}
                                                className={errors.comentarios ? 'border-red-500' : ''}
                                            />
                                            <div className="flex justify-between text-sm text-gray-500">
                                                <span>
                                                    {data.accion === 'rechazado' ? 'Requerido para rechazos' : 'Opcional pero recomendado'}
                                                </span>
                                                <span>{data.comentarios.length}/1000</span>
                                            </div>
                                        </div>
                                        {errors.comentarios && (
                                            <p className="text-sm text-red-600">{errors.comentarios}</p>
                                        )}
                                    </div>

                                    {/* Archivos Adjuntos */}
                                    <div className="space-y-3">
                                        <Label className="text-base font-medium">Archivos Adjuntos (Opcional)</Label>
                                        <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                                            <FileText className="w-12 h-12 mx-auto text-gray-400 mb-4" />
                                            <p className="text-sm text-gray-600 mb-2">
                                                Arrastra archivos aquí o haz clic para seleccionar
                                            </p>
                                            <p className="text-xs text-gray-500">
                                                Documentos de soporte, correcciones sugeridas, etc.
                                            </p>
                                            <input
                                                type="file"
                                                multiple
                                                className="hidden"
                                                onChange={(e) => {
                                                    const files = Array.from(e.target.files || []);
                                                    setData('archivos_adjuntos', files);
                                                }}
                                            />
                                        </div>
                                        {data.archivos_adjuntos.length > 0 && (
                                            <div className="space-y-2">
                                                <p className="text-sm font-medium">Archivos seleccionados:</p>
                                                {data.archivos_adjuntos.map((file, index) => (
                                                    <div key={index} className="flex items-center gap-2 text-sm">
                                                        <FileText className="w-4 h-4" />
                                                        <span>{file.name}</span>
                                                        <span className="text-gray-500">({(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>

                                    {/* Botones de Acción */}
                                    <div className="flex gap-3 pt-4">
                                        <Button
                                            type="submit"
                                            disabled={!data.accion || (data.accion === 'rechazado' && !data.comentarios.trim()) || processing}
                                            className={
                                                data.accion === 'aprobado' 
                                                    ? 'bg-green-600 hover:bg-green-700' 
                                                    : 'bg-red-600 hover:bg-red-700'
                                            }
                                        >
                                            <Send className="w-4 h-4 mr-2" />
                                            {processing 
                                                ? 'Procesando...' 
                                                : data.accion === 'aprobado' 
                                                    ? 'Aprobar Documento' 
                                                    : 'Rechazar Documento'
                                            }
                                        </Button>
                                        <Link href={`/admin/workflow/${workflow.id}`}>
                                            <Button variant="outline">
                                                Cancelar
                                            </Button>
                                        </Link>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Panel Lateral - Información del Workflow */}
                    <div className="space-y-6">
                        {/* Información del Documento */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <FileText className="w-5 h-5" />
                                    Documento
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div>
                                    <div className="text-sm text-gray-600 mb-1">Nombre</div>
                                    <p className="font-medium">{workflow.documento.nombre}</p>
                                </div>
                                <div>
                                    <div className="text-sm text-gray-600 mb-1">Código</div>
                                    <p className="font-medium">{workflow.documento.codigo}</p>
                                </div>
                                <Separator />
                                <Link href={`/admin/documentos/${workflow.documento.id}`}>
                                    <Button variant="outline" size="sm" className="w-full">
                                        Ver Documento Completo
                                    </Button>
                                </Link>
                            </CardContent>
                        </Card>

                        {/* Información del Workflow */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <GitBranch className="w-5 h-5" />
                                    Workflow
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <div className="flex items-center gap-2 text-sm text-gray-600 mb-1">
                                        <User className="w-4 h-4" />
                                        Solicitante
                                    </div>
                                    <p className="font-medium">{workflow.solicitante}</p>
                                </div>

                                <div>
                                    <div className="flex items-center gap-2 text-sm text-gray-600 mb-1">
                                        <Shield className="w-4 h-4" />
                                        Prioridad
                                    </div>
                                    {getPrioridadBadge(workflow.prioridad)}
                                </div>

                                <div>
                                    <div className="flex items-center gap-2 text-sm text-gray-600 mb-1">
                                        <Calendar className="w-4 h-4" />
                                        Solicitud
                                    </div>
                                    <p className="font-medium">{formatearFecha(workflow.fecha_solicitud)}</p>
                                </div>

                                <div>
                                    <div className="flex items-center gap-2 text-sm text-gray-600 mb-1">
                                        <Clock className="w-4 h-4" />
                                        Vencimiento
                                    </div>
                                    <p className={`font-medium ${estaVencido ? 'text-red-600' : ''}`}>
                                        {formatearFecha(workflow.fecha_vencimiento)}
                                    </p>
                                    {estaVencido && (
                                        <Badge variant="destructive" className="mt-1 text-xs">
                                            Vencido
                                        </Badge>
                                    )}
                                </div>

                                <div>
                                    <div className="flex items-center gap-2 text-sm text-gray-600 mb-1">
                                        <CheckCircle className="w-4 h-4" />
                                        Progreso
                                    </div>
                                    <p className="font-medium">
                                        Nivel {workflow.nivel_actual} de {workflow.total_niveles}
                                    </p>
                                </div>

                                {workflow.descripcion && (
                                    <>
                                        <Separator />
                                        <div>
                                            <div className="flex items-center gap-2 text-sm text-gray-600 mb-2">
                                                <MessageSquare className="w-4 h-4" />
                                                Descripción
                                            </div>
                                            <div className="bg-gray-50 p-3 rounded-lg">
                                                <p className="text-sm">{workflow.descripcion}</p>
                                            </div>
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Ayuda */}
                        <Card className="border-blue-200 bg-blue-50">
                            <CardHeader>
                                <CardTitle className="text-blue-800">💡 Consejos</CardTitle>
                            </CardHeader>
                            <CardContent className="text-sm text-blue-700 space-y-2">
                                <p>• <strong>Aprobación:</strong> El documento pasará al siguiente nivel o se completará el workflow.</p>
                                <p>• <strong>Rechazo:</strong> El documento será devuelto al solicitante con tus comentarios.</p>
                                <p>• <strong>Comentarios:</strong> Son obligatorios para rechazos y muy recomendados para aprobaciones.</p>
                                <p>• <strong>Archivos:</strong> Puedes adjuntar documentos de soporte o correcciones sugeridas.</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Modal de Confirmación */}
                {mostrarConfirmacion && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                        <div className="bg-white rounded-lg max-w-md w-full p-6">
                            <div className="flex items-center gap-3 mb-4">
                                {data.accion === 'aprobado' ? (
                                    <CheckCircle className="w-8 h-8 text-green-600" />
                                ) : (
                                    <XCircle className="w-8 h-8 text-red-600" />
                                )}
                                <h3 className="text-lg font-medium">
                                    Confirmar {data.accion === 'aprobado' ? 'Aprobación' : 'Rechazo'}
                                </h3>
                            </div>
                            
                            <p className="text-gray-600 mb-6">
                                ¿Estás seguro de que deseas {data.accion === 'aprobado' ? 'aprobar' : 'rechazar'} este documento?
                                {data.accion === 'aprobado' && workflow.nivel_actual === workflow.total_niveles && 
                                    ' Esta acción completará el workflow de aprobación.'
                                }
                            </p>

                            <div className="flex gap-3">
                                <Button
                                    onClick={confirmarAccion}
                                    disabled={processing}
                                    className={
                                        data.accion === 'aprobado' 
                                            ? 'bg-green-600 hover:bg-green-700' 
                                            : 'bg-red-600 hover:bg-red-700'
                                    }
                                >
                                    {processing ? 'Procesando...' : 'Confirmar'}
                                </Button>
                                <Button 
                                    variant="outline" 
                                    onClick={() => setMostrarConfirmacion(false)}
                                    disabled={processing}
                                >
                                    Cancelar
                                </Button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
