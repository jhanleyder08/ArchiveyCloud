import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Label } from '@/components/ui/label';
import { 
    ArrowLeft, 
    Edit, 
    RotateCcw,
    CheckCircle,
    User,
    Calendar,
    FileText,
    Archive,
    MapPin,
    Mail,
    Phone,
    Building,
    IdCard,
    AlertTriangle,
    Clock
} from 'lucide-react';

interface Expediente {
    id: number;
    codigo: string;
    titulo: string;
    ubicacion_fisica: string;
}

interface Documento {
    id: number;
    titulo: string;
    codigo_documento: string;
    expediente?: {
        codigo: string;
        titulo: string;
    };
}

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Prestamo {
    id: number;
    tipo_prestamo: 'expediente' | 'documento';
    tipo_solicitante: 'usuario' | 'externo';
    expediente?: Expediente;
    documento?: Documento;
    solicitante?: Usuario;
    datos_solicitante_externo?: {
        nombre_completo: string;
        tipo_documento: string;
        numero_documento: string;
        email: string;
        telefono?: string;
        cargo?: string;
        dependencia?: string;
    };
    prestamista: Usuario;
    motivo: string;
    fecha_prestamo: string;
    fecha_devolucion_esperada: string;
    fecha_devolucion_real?: string;
    observaciones?: string;
    observaciones_devolucion?: string;
    estado: 'prestado' | 'devuelto' | 'vencido';
    estado_devolucion?: 'completa' | 'parcial' | 'con_daños';
    renovaciones: number;
    created_at: string;
    updated_at: string;
    // Campos calculados
    nombre_solicitante?: string;
    contacto_solicitante?: string;
    esta_vencido?: boolean;
    dias_restantes?: number;
}

interface Props {
    prestamo: Prestamo;
}

export default function PrestamosShow({ prestamo }: Props) {
    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const formatearFechaCorta = (fecha: string) => {
        return new Date(fecha).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const obtenerEstadoColor = (estado: string) => {
        switch (estado) {
            case 'prestado':
                return prestamo.esta_vencido 
                    ? 'bg-red-100 text-red-800 border-red-200'
                    : 'bg-blue-100 text-blue-800 border-blue-200';
            case 'devuelto':
                return 'bg-green-100 text-green-800 border-green-200';
            case 'vencido':
                return 'bg-red-100 text-red-800 border-red-200';
            default:
                return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    };

    const obtenerItemPrestado = () => {
        if (prestamo.tipo_prestamo === 'expediente' && prestamo.expediente) {
            return {
                titulo: `${prestamo.expediente.codigo} - ${prestamo.expediente.titulo}`,
                subtitulo: `Ubicación: ${prestamo.expediente.ubicacion_fisica}`,
                icono: <Archive className="h-5 w-5" />
            };
        }
        if (prestamo.tipo_prestamo === 'documento' && prestamo.documento) {
            return {
                titulo: prestamo.documento.titulo,
                subtitulo: prestamo.documento.expediente 
                    ? `Expediente: ${prestamo.documento.expediente.codigo} - ${prestamo.documento.expediente.titulo}`
                    : `Código: ${prestamo.documento.codigo_documento}`,
                icono: <FileText className="h-5 w-5" />
            };
        }
        return {
            titulo: 'Item no disponible',
            subtitulo: '',
            icono: <FileText className="h-5 w-5" />
        };
    };

    const itemPrestado = obtenerItemPrestado();

    return (
        <AppLayout breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Administración', href: '#' },
            { title: 'Préstamos', href: '/admin/prestamos' },
            { title: `Préstamo #${prestamo.id}`, href: `/admin/prestamos/${prestamo.id}` },
        ]}>
            <Head title={`Préstamo #${prestamo.id}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/admin/prestamos">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Préstamo #{prestamo.id}</h1>
                            <p className="text-sm text-gray-500">
                                Creado el {formatearFecha(prestamo.created_at)}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Badge className={`${obtenerEstadoColor(prestamo.estado)} border`}>
                            {prestamo.estado === 'prestado' && prestamo.esta_vencido ? 'Vencido' : prestamo.estado}
                        </Badge>
                        {prestamo.estado === 'prestado' && (
                            <>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/admin/prestamos/${prestamo.id}/edit`}>
                                        <Edit className="h-4 w-4 mr-2" />
                                        Editar
                                    </Link>
                                </Button>
                                <Button variant="outline" size="sm">
                                    <RotateCcw className="h-4 w-4 mr-2" />
                                    Renovar
                                </Button>
                                <Button size="sm" className="bg-green-600 hover:bg-green-700">
                                    <CheckCircle className="h-4 w-4 mr-2" />
                                    Devolver
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                {/* Alertas de estado */}
                {prestamo.estado === 'prestado' && prestamo.esta_vencido && (
                    <Alert className="border-red-200 bg-red-50">
                        <AlertTriangle className="h-4 w-4 text-red-600" />
                        <AlertDescription className="text-red-800">
                            <strong>Préstamo Vencido:</strong> Este préstamo debía ser devuelto el {formatearFechaCorta(prestamo.fecha_devolucion_esperada)}.
                        </AlertDescription>
                    </Alert>
                )}

                {prestamo.estado === 'prestado' && !prestamo.esta_vencido && prestamo.dias_restantes !== undefined && prestamo.dias_restantes <= 3 && (
                    <Alert className="border-yellow-200 bg-yellow-50">
                        <Clock className="h-4 w-4 text-yellow-600" />
                        <AlertDescription className="text-yellow-800">
                            <strong>Próximo a vencer:</strong> Quedan {prestamo.dias_restantes} días para la devolución.
                        </AlertDescription>
                    </Alert>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Información del Item Prestado */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                {itemPrestado.icono}
                                <span>Item Prestado</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <h3 className="font-medium text-gray-900">{itemPrestado.titulo}</h3>
                                <p className="text-sm text-gray-500">{itemPrestado.subtitulo}</p>
                            </div>
                            
                            <div className="grid grid-cols-2 gap-4 pt-4 border-t">
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Tipo de Préstamo</Label>
                                    <p className="text-sm font-medium capitalize">{prestamo.tipo_prestamo}</p>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Renovaciones</Label>
                                    <p className="text-sm font-medium">{prestamo.renovaciones} veces</p>
                                </div>
                            </div>

                            <div>
                                <Label className="text-sm font-medium text-gray-500">Motivo del Préstamo</Label>
                                <p className="text-sm mt-1 p-3 bg-gray-50 rounded-md">{prestamo.motivo}</p>
                            </div>

                            {prestamo.observaciones && (
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Observaciones</Label>
                                    <p className="text-sm mt-1 p-3 bg-gray-50 rounded-md">{prestamo.observaciones}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Información del Solicitante */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <User className="h-5 w-5" />
                                <span>Solicitante</span>
                            </CardTitle>
                            <CardDescription>
                                {prestamo.tipo_solicitante === 'usuario' ? 'Usuario Registrado' : 'Solicitante Externo'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {prestamo.tipo_solicitante === 'usuario' && prestamo.solicitante ? (
                                <>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Nombre</Label>
                                        <p className="text-sm font-medium">{prestamo.solicitante.name}</p>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Mail className="h-4 w-4 text-gray-400" />
                                        <span className="text-sm">{prestamo.solicitante.email}</span>
                                    </div>
                                </>
                            ) : prestamo.datos_solicitante_externo ? (
                                <>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-500">Nombre Completo</Label>
                                        <p className="text-sm font-medium">{prestamo.datos_solicitante_externo.nombre_completo}</p>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <IdCard className="h-4 w-4 text-gray-400" />
                                        <span className="text-sm">
                                            {prestamo.datos_solicitante_externo.tipo_documento}: {prestamo.datos_solicitante_externo.numero_documento}
                                        </span>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Mail className="h-4 w-4 text-gray-400" />
                                        <span className="text-sm">{prestamo.datos_solicitante_externo.email}</span>
                                    </div>
                                    {prestamo.datos_solicitante_externo.telefono && (
                                        <div className="flex items-center space-x-2">
                                            <Phone className="h-4 w-4 text-gray-400" />
                                            <span className="text-sm">{prestamo.datos_solicitante_externo.telefono}</span>
                                        </div>
                                    )}
                                    {prestamo.datos_solicitante_externo.cargo && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-500">Cargo</Label>
                                            <p className="text-sm">{prestamo.datos_solicitante_externo.cargo}</p>
                                        </div>
                                    )}
                                    {prestamo.datos_solicitante_externo.dependencia && (
                                        <div className="flex items-center space-x-2">
                                            <Building className="h-4 w-4 text-gray-400" />
                                            <span className="text-sm">{prestamo.datos_solicitante_externo.dependencia}</span>
                                        </div>
                                    )}
                                </>
                            ) : (
                                <p className="text-sm text-gray-500">Información no disponible</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Información de Fechas y Estado */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center space-x-2">
                                <Calendar className="h-5 w-5 text-blue-500" />
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Fecha de Préstamo</p>
                                    <p className="text-sm font-bold">{formatearFechaCorta(prestamo.fecha_prestamo)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center space-x-2">
                                <Calendar className="h-5 w-5 text-orange-500" />
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Devolución Esperada</p>
                                    <p className="text-sm font-bold">{formatearFechaCorta(prestamo.fecha_devolucion_esperada)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {prestamo.fecha_devolucion_real && (
                        <Card>
                            <CardContent className="p-6">
                                <div className="flex items-center space-x-2">
                                    <CheckCircle className="h-5 w-5 text-green-500" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">Fecha de Devolución</p>
                                        <p className="text-sm font-bold">{formatearFechaCorta(prestamo.fecha_devolucion_real)}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center space-x-2">
                                <User className="h-5 w-5 text-purple-500" />
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Prestamista</p>
                                    <p className="text-sm font-bold">{prestamo.prestamista.name}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Información de Devolución */}
                {prestamo.estado === 'devuelto' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <CheckCircle className="h-5 w-5 text-green-600" />
                                <span>Información de Devolución</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Estado de Devolución</Label>
                                    <Badge className="mt-1 bg-green-100 text-green-800 border-green-200">
                                        {prestamo.estado_devolucion || 'Completa'}
                                    </Badge>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Fecha de Devolución</Label>
                                    <p className="text-sm font-medium">{prestamo.fecha_devolucion_real ? formatearFecha(prestamo.fecha_devolucion_real) : 'No registrada'}</p>
                                </div>
                            </div>
                            
                            {prestamo.observaciones_devolucion && (
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Observaciones de Devolución</Label>
                                    <p className="text-sm mt-1 p-3 bg-gray-50 rounded-md">{prestamo.observaciones_devolucion}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
