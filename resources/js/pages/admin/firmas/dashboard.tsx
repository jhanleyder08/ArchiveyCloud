import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    Shield, 
    CheckCircle, 
    XCircle,
    Clock,
    TrendingUp,
    FileText,
    Calendar,
    Award
} from 'lucide-react';

interface Estadisticas {
    documentos_firmados: number;
    firmas_este_mes: number;
    firmas_validas: number;
    ultima_firma: string | null;
}

interface FirmaReciente {
    id: number;
    documento_nombre: string;
    expediente: string | null;
    fecha_firma: string;
    motivo: string;
    valida: boolean;
    vigente: boolean;
}

interface Props {
    estadisticas: Estadisticas;
    firmasRecientes: FirmaReciente[];
}

export default function DashboardFirmas({ estadisticas, firmasRecientes }: Props) {
    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleString();
    };

    const getIconoValidez = (valida: boolean, vigente: boolean) => {
        if (!valida) {
            return <XCircle className="w-4 h-4 text-red-600" />;
        }
        if (!vigente) {
            return <Clock className="w-4 h-4 text-yellow-600" />;
        }
        return <CheckCircle className="w-4 h-4 text-green-600" />;
    };

    const getBadgeValidez = (valida: boolean, vigente: boolean) => {
        if (!valida) {
            return <Badge variant="destructive">Inválida</Badge>;
        }
        if (!vigente) {
            return <Badge variant="outline" className="text-yellow-600">Expirada</Badge>;
        }
        return <Badge className="bg-green-100 text-green-800">Válida</Badge>;
    };

    return (
        <AppLayout>
            <Head title="Dashboard de Firmas Digitales" />
            
            <div className="container mx-auto py-6">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <Shield className="w-8 h-8 text-blue-600" />
                            Dashboard de Firmas Digitales
                        </h1>
                        <p className="text-gray-600 mt-1">
                            Resumen de su actividad de firma digital y estadísticas personales
                        </p>
                    </div>
                    <Link href="/admin/documentos">
                        <Button>
                            <FileText className="w-4 h-4 mr-2" />
                            Ver Documentos
                        </Button>
                    </Link>
                </div>

                {/* Estadísticas Principales */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Documentos Firmados</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{estadisticas.documentos_firmados}</div>
                            <p className="text-xs text-muted-foreground">Total de documentos</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Firmas Este Mes</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{estadisticas.firmas_este_mes}</div>
                            <p className="text-xs text-muted-foreground">En {new Date().toLocaleDateString('es-ES', { month: 'long' })}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Firmas Válidas</CardTitle>
                            <CheckCircle className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-600">{estadisticas.firmas_validas}</div>
                            <p className="text-xs text-muted-foreground">Verificadas</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Última Firma</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-sm font-medium">
                                {estadisticas.ultima_firma ? 
                                    new Date(estadisticas.ultima_firma).toLocaleDateString() : 
                                    'Nunca'
                                }
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {estadisticas.ultima_firma && 
                                    `Hace ${Math.floor((Date.now() - new Date(estadisticas.ultima_firma).getTime()) / (1000 * 60 * 60 * 24))} días`
                                }
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Firmas Recientes */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Award className="w-5 h-5" />
                            Firmas Recientes
                        </CardTitle>
                        <CardDescription>
                            Sus últimas 10 firmas digitales aplicadas
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {firmasRecientes.length > 0 ? (
                            <div className="space-y-4">
                                {firmasRecientes.map((firma) => (
                                    <div key={firma.id} className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                                        <div className="flex items-start gap-3">
                                            {getIconoValidez(firma.valida, firma.vigente)}
                                            <div className="flex-1">
                                                <div className="font-medium text-gray-900">
                                                    {firma.documento_nombre}
                                                </div>
                                                {firma.expediente && (
                                                    <div className="text-sm text-gray-600">
                                                        Expediente: {firma.expediente}
                                                    </div>
                                                )}
                                                <div className="flex items-center gap-2 mt-1">
                                                    <Calendar className="w-4 h-4 text-gray-400" />
                                                    <span className="text-sm text-gray-600">
                                                        {formatearFecha(firma.fecha_firma)}
                                                    </span>
                                                </div>
                                                {firma.motivo && (
                                                    <div className="text-sm text-gray-500 mt-1 italic">
                                                        "{firma.motivo}"
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            {getBadgeValidez(firma.valida, firma.vigente)}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-8">
                                <Shield className="w-16 h-16 mx-auto text-gray-300 mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">
                                    No hay firmas digitales
                                </h3>
                                <p className="text-gray-600 mb-6">
                                    Aún no has firmado ningún documento digitalmente.
                                </p>
                                <Link href="/admin/documentos">
                                    <Button>
                                        <FileText className="w-4 h-4 mr-2" />
                                        Explorar Documentos
                                    </Button>
                                </Link>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Información sobre Firmas Digitales */}
                <div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">¿Qué es una Firma Digital?</CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-gray-600">
                            <ul className="space-y-2">
                                <li>• <strong>Autenticidad:</strong> Confirma la identidad del firmante</li>
                                <li>• <strong>Integridad:</strong> Garantiza que el documento no fue alterado</li>
                                <li>• <strong>No repudio:</strong> El firmante no puede negar la firma</li>
                                <li>• <strong>Tiempo:</strong> Registro de cuándo se realizó la firma</li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Estados de Firma</CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-gray-600">
                            <ul className="space-y-2">
                                <li className="flex items-center gap-2">
                                    <CheckCircle className="w-4 h-4 text-green-600" />
                                    <strong>Válida:</strong> Firma correcta y vigente
                                </li>
                                <li className="flex items-center gap-2">
                                    <Clock className="w-4 h-4 text-yellow-600" />
                                    <strong>Expirada:</strong> Firma válida pero fuera del período de vigencia
                                </li>
                                <li className="flex items-center gap-2">
                                    <XCircle className="w-4 h-4 text-red-600" />
                                    <strong>Inválida:</strong> Firma comprometida o documento alterado
                                </li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
