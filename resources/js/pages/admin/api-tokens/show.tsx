import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    ArrowLeft, 
    Key, 
    Edit, 
    RefreshCw, 
    Power, 
    User, 
    Calendar, 
    Shield, 
    Activity,
    Network,
    BarChart3,
    TrendingUp,
    CheckCircle,
    AlertTriangle,
    Clock
} from 'lucide-react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, BarChart, Bar } from 'recharts';
import { formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';

interface ApiToken {
    id: number;
    nombre: string;
    descripcion?: string;
    usuario: {
        id: number;
        name: string;
        email: string;
    };
    permisos: string[];
    estado: 'activo' | 'inactivo' | 'expirado' | 'limite_alcanzado';
    fecha_expiracion?: string;
    limite_usos?: number;
    usos_realizados: number;
    porcentaje_uso?: number;
    dias_hasta_expiracion?: number;
    ultimo_uso?: string;
    ultima_ip?: string;
    ips_permitidas?: string[];
    created_at: string;
    updated_at: string;
}

interface Estadisticas {
    resumen: {
        total_requests: number;
        dias_activos: number;
        ultimo_request?: string;
        ips_diferentes: number;
        promedio_requests_dia: number;
    };
    requests_por_dia: Record<string, number>;
    rutas_mas_usadas: Record<string, number>;
}

interface Props {
    token: ApiToken;
    estadisticas: Estadisticas;
}

export default function ShowApiToken({ token, estadisticas }: Props) {

    const getEstadoBadge = (estado: string) => {
        switch (estado) {
            case 'activo':
                return <Badge className="bg-green-100 text-green-800">Activo</Badge>;
            case 'inactivo':
                return <Badge variant="secondary">Inactivo</Badge>;
            case 'expirado':
                return <Badge className="bg-red-100 text-red-800">Expirado</Badge>;
            case 'limite_alcanzado':
                return <Badge className="bg-orange-100 text-orange-800">Límite alcanzado</Badge>;
            default:
                return <Badge variant="outline">{estado}</Badge>;
        }
    };

    // Convertir datos para gráficos
    const datosRequestsPorDia = Object.entries(estadisticas.requests_por_dia || {}).map(([fecha, requests]) => ({
        fecha,
        requests,
        fechaFormateada: new Date(fecha).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' })
    }));

    const datosRutasMasUsadas = Object.entries(estadisticas.rutas_mas_usadas || {}).map(([ruta, requests]) => ({
        ruta: ruta.length > 30 ? ruta.substring(0, 30) + '...' : ruta,
        rutaCompleta: ruta,
        requests
    }));

    return (
        <AppLayout>
            <Head title={`${token.nombre} - API Tokens - ArchiveyCloud`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href="/admin/api-tokens">
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver a Tokens
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">{token.nombre}</h1>
                            <p className="text-gray-600 mt-1">
                                Detalles y estadísticas del token API
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-3">
                        <Link href={`/admin/api-tokens/${token.id}/edit`}>
                            <Button variant="outline">
                                <Edit className="h-4 w-4 mr-2" />
                                Editar
                            </Button>
                        </Link>
                        {token.estado === 'activo' && (
                            <Button variant="outline">
                                <RefreshCw className="h-4 w-4 mr-2" />
                                Renovar
                            </Button>
                        )}
                    </div>
                </div>

                {/* Estado del token */}
                {(token.estado === 'expirado' || token.estado === 'limite_alcanzado') && (
                    <Alert className="border-orange-200 bg-orange-50">
                        <AlertTriangle className="h-4 w-4 text-orange-600" />
                        <AlertDescription className="text-orange-800">
                            {token.estado === 'expirado' 
                                ? 'Este token ha expirado y no puede ser usado para realizar requests'
                                : 'Este token ha alcanzado su límite de usos'
                            }
                        </AlertDescription>
                    </Alert>
                )}

                {/* Información general */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Key className="h-5 w-5 mr-2" />
                                    Información del Token
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-sm font-medium text-gray-600">Estado</label>
                                        <div className="mt-1">
                                            {getEstadoBadge(token.estado)}
                                        </div>
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-600">Propietario</label>
                                        <div className="mt-1 flex items-center">
                                            <User className="h-4 w-4 mr-2 text-gray-400" />
                                            <span>{token.usuario.name}</span>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-600">Creado</label>
                                        <div className="mt-1 flex items-center">
                                            <Calendar className="h-4 w-4 mr-2 text-gray-400" />
                                            <span>
                                                {formatDistanceToNow(new Date(token.created_at), { 
                                                    addSuffix: true, 
                                                    locale: es 
                                                })}
                                            </span>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-600">Último uso</label>
                                        <div className="mt-1 flex items-center">
                                            <Activity className="h-4 w-4 mr-2 text-gray-400" />
                                            <span>
                                                {token.ultimo_uso ? (
                                                    formatDistanceToNow(new Date(token.ultimo_uso), { 
                                                        addSuffix: true, 
                                                        locale: es 
                                                    })
                                                ) : (
                                                    'Nunca usado'
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {token.descripcion && (
                                    <div>
                                        <label className="text-sm font-medium text-gray-600">Descripción</label>
                                        <p className="mt-1 text-gray-900">{token.descripcion}</p>
                                    </div>
                                )}

                                {/* Configuración avanzada */}
                                <div className="pt-4 border-t">
                                    <h4 className="font-medium mb-3">Configuración</h4>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {token.fecha_expiracion && (
                                            <div>
                                                <label className="text-sm font-medium text-gray-600">Expira</label>
                                                <div className="mt-1 flex items-center">
                                                    <Clock className="h-4 w-4 mr-2 text-gray-400" />
                                                    <span>
                                                        {new Date(token.fecha_expiracion).toLocaleDateString('es-ES')}
                                                        {token.dias_hasta_expiracion !== null && (
                                                            <span className="ml-2 text-sm text-gray-500">
                                                                ({token.dias_hasta_expiracion} días)
                                                            </span>
                                                        )}
                                                    </span>
                                                </div>
                                            </div>
                                        )}

                                        {token.limite_usos && (
                                            <div>
                                                <label className="text-sm font-medium text-gray-600">Límite de usos</label>
                                                <div className="mt-1">
                                                    <div className="flex items-center justify-between mb-1">
                                                        <span>{token.usos_realizados} / {token.limite_usos}</span>
                                                        <span className="text-sm text-gray-500">{token.porcentaje_uso}%</span>
                                                    </div>
                                                    <div className="w-full bg-gray-200 rounded-full h-2">
                                                        <div 
                                                            className={`h-2 rounded-full ${
                                                                (token.porcentaje_uso || 0) > 80 ? 'bg-red-600' :
                                                                (token.porcentaje_uso || 0) > 60 ? 'bg-orange-600' : 'bg-green-600'
                                                            }`}
                                                            style={{ width: `${token.porcentaje_uso || 0}%` }}
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* IPs permitidas */}
                                {token.ips_permitidas && token.ips_permitidas.length > 0 && (
                                    <div className="pt-4 border-t">
                                        <label className="text-sm font-medium text-gray-600">IPs Permitidas</label>
                                        <div className="mt-2 flex flex-wrap gap-2">
                                            {token.ips_permitidas.map((ip) => (
                                                <Badge key={ip} variant="outline" className="flex items-center">
                                                    <Network className="h-3 w-3 mr-1" />
                                                    {ip}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Estadísticas rápidas */}
                    <div className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">Resumen de Uso</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="text-center">
                                    <p className="text-3xl font-bold text-blue-600">{estadisticas.resumen.total_requests}</p>
                                    <p className="text-sm text-gray-600">Total requests</p>
                                </div>

                                <div className="text-center">
                                    <p className="text-2xl font-bold text-green-600">{estadisticas.resumen.dias_activos}</p>
                                    <p className="text-sm text-gray-600">Días activos</p>
                                </div>

                                <div className="text-center">
                                    <p className="text-2xl font-bold text-purple-600">{estadisticas.resumen.ips_diferentes}</p>
                                    <p className="text-sm text-gray-600">IPs diferentes</p>
                                </div>

                                <div className="text-center">
                                    <p className="text-2xl font-bold text-orange-600">{estadisticas.resumen.promedio_requests_dia}</p>
                                    <p className="text-sm text-gray-600">Promedio por día</p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Permisos */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg flex items-center">
                                    <Shield className="h-4 w-4 mr-2" />
                                    Permisos
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    {token.permisos.map((permiso) => (
                                        <Badge key={permiso} variant="outline" className="block w-full justify-start">
                                            <Shield className="h-3 w-3 mr-2" />
                                            {permiso}
                                        </Badge>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Gráficos de estadísticas */}
                {estadisticas.resumen.total_requests > 0 && (
                    <>
                        {/* Gráfico de requests por día */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <TrendingUp className="h-5 w-5 mr-2" />
                                    Actividad Diaria (Últimos 30 días)
                                </CardTitle>
                                <CardDescription>
                                    Número de requests realizados por día
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="h-80">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <AreaChart data={datosRequestsPorDia}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis 
                                                dataKey="fechaFormateada" 
                                                tick={{ fontSize: 12 }}
                                            />
                                            <YAxis tick={{ fontSize: 12 }} />
                                            <Tooltip 
                                                labelFormatter={(value) => `Fecha: ${value}`}
                                                formatter={(value: any, name: any) => [value, 'Requests']}
                                            />
                                            <Area 
                                                type="monotone" 
                                                dataKey="requests" 
                                                stroke="#3b82f6" 
                                                fill="#3b82f6" 
                                                fillOpacity={0.2}
                                            />
                                        </AreaChart>
                                    </ResponsiveContainer>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Rutas más usadas */}
                        {datosRutasMasUsadas.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <BarChart3 className="h-5 w-5 mr-2" />
                                        Rutas Más Usadas
                                    </CardTitle>
                                    <CardDescription>
                                        Endpoints más consultados por este token
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="h-80">
                                        <ResponsiveContainer width="100%" height="100%">
                                            <BarChart data={datosRutasMasUsadas} layout="horizontal">
                                                <CartesianGrid strokeDasharray="3 3" />
                                                <XAxis type="number" tick={{ fontSize: 12 }} />
                                                <YAxis 
                                                    type="category" 
                                                    dataKey="ruta" 
                                                    tick={{ fontSize: 10 }}
                                                    width={120}
                                                />
                                                <Tooltip 
                                                    formatter={(value: any, name: any, props: any) => [
                                                        value, 
                                                        'Requests',
                                                        props.payload.rutaCompleta
                                                    ]}
                                                />
                                                <Bar 
                                                    dataKey="requests" 
                                                    fill="#3b82f6" 
                                                />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </>
                )}

                {/* Sin actividad */}
                {estadisticas.resumen.total_requests === 0 && (
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-center py-8">
                                <Activity className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">Sin actividad</h3>
                                <p className="text-gray-600">
                                    Este token aún no ha sido usado para realizar requests
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
