import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
    ArrowLeft, 
    Key, 
    Plus, 
    TrendingUp, 
    Activity, 
    Users, 
    Calendar,
    Shield,
    CheckCircle,
    Clock,
    AlertTriangle
} from 'lucide-react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';

interface Estadisticas {
    tokens: {
        total: number;
        activos: number;
        expirados: number;
        inactivos: number;
    };
    uso_reciente: {
        requests_hoy: number;
        requests_semana: number;
        usuarios_activos: number;
    };
}

interface TokenMasUsado {
    id: number;
    nombre: string;
    usuario: {
        id: number;
        name: string;
    };
    logs_count: number;
}

interface Props {
    estadisticas: Estadisticas;
    tokens_mas_usados: TokenMasUsado[];
    actividad_diaria: Record<string, number>;
}

export default function ApiTokensDashboard({ estadisticas, tokens_mas_usados, actividad_diaria }: Props) {
    
    // Convertir datos de actividad diaria para el gráfico
    const datosGrafico = Object.entries(actividad_diaria).map(([fecha, requests]) => ({
        fecha,
        requests,
        fechaFormateada: new Date(fecha).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' })
    }));

    return (
        <AppLayout>
            <Head title="Dashboard - API Tokens - ArchiveyCloud" />

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
                            <h1 className="text-3xl font-bold text-gray-900">Dashboard API Tokens</h1>
                            <p className="text-gray-600 mt-1">
                                Análisis y estadísticas de uso de tokens API
                            </p>
                        </div>
                    </div>
                    <Link href="/admin/api-tokens/create">
                        <Button>
                            <Plus className="h-4 w-4 mr-2" />
                            Crear Token
                        </Button>
                    </Link>
                </div>

                {/* Estadísticas principales */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <Key className="h-8 w-8 text-blue-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total Tokens</p>
                                    <p className="text-2xl font-bold">{estadisticas.tokens.total}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <CheckCircle className="h-8 w-8 text-green-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Tokens Activos</p>
                                    <p className="text-2xl font-bold">{estadisticas.tokens.activos}</p>
                                    <p className="text-xs text-gray-500">
                                        {estadisticas.tokens.total > 0 ? 
                                            Math.round((estadisticas.tokens.activos / estadisticas.tokens.total) * 100) : 0
                                        }% del total
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <Activity className="h-8 w-8 text-purple-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Requests Hoy</p>
                                    <p className="text-2xl font-bold">{estadisticas.uso_reciente.requests_hoy}</p>
                                    <p className="text-xs text-gray-500">
                                        {estadisticas.uso_reciente.requests_semana} esta semana
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <Users className="h-8 w-8 text-orange-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Usuarios Activos</p>
                                    <p className="text-2xl font-bold">{estadisticas.uso_reciente.usuarios_activos}</p>
                                    <p className="text-xs text-gray-500">
                                        Última semana
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Estados de tokens */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Tokens Expirados</p>
                                    <p className="text-3xl font-bold text-red-600">{estadisticas.tokens.expirados}</p>
                                </div>
                                <Clock className="h-8 w-8 text-red-600" />
                            </div>
                            {estadisticas.tokens.expirados > 0 && (
                                <div className="mt-4">
                                    <Badge className="bg-red-100 text-red-800">
                                        Requieren atención
                                    </Badge>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Tokens Inactivos</p>
                                    <p className="text-3xl font-bold text-gray-600">{estadisticas.tokens.inactivos}</p>
                                </div>
                                <AlertTriangle className="h-8 w-8 text-gray-600" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Tasa de Uso</p>
                                    <p className="text-3xl font-bold text-blue-600">
                                        {estadisticas.tokens.activos > 0 ? 
                                            Math.round((estadisticas.uso_reciente.usuarios_activos / estadisticas.tokens.activos) * 100) : 0
                                        }%
                                    </p>
                                </div>
                                <TrendingUp className="h-8 w-8 text-blue-600" />
                            </div>
                            <p className="text-xs text-gray-500 mt-2">
                                Tokens con actividad reciente
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Gráfico de actividad */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center">
                            <Activity className="h-5 w-5 mr-2" />
                            Actividad de API (Últimos 30 días)
                        </CardTitle>
                        <CardDescription>
                            Número de requests realizados por día
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="h-80">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={datosGrafico}>
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

                {/* Tokens más usados */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center">
                            <TrendingUp className="h-5 w-5 mr-2" />
                            Tokens Más Usados (Último mes)
                        </CardTitle>
                        <CardDescription>
                            Ranking de tokens por número de requests
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {tokens_mas_usados.length > 0 ? (
                                tokens_mas_usados.map((token, index) => (
                                    <div key={token.id} className="flex items-center justify-between p-4 border rounded-lg">
                                        <div className="flex items-center space-x-4">
                                            <div className="flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-full font-bold">
                                                {index + 1}
                                            </div>
                                            <div>
                                                <h3 className="font-medium">{token.nombre}</h3>
                                                <p className="text-sm text-gray-600">Por: {token.usuario.name}</p>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-2xl font-bold text-blue-600">{token.logs_count}</p>
                                            <p className="text-xs text-gray-500">requests</p>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="text-center py-8">
                                    <Key className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">No hay actividad</h3>
                                    <p className="text-gray-600">
                                        No se han registrado requests en el último mes
                                    </p>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Acciones rápidas */}
                <Card>
                    <CardHeader>
                        <CardTitle>Acciones Rápidas</CardTitle>
                        <CardDescription>
                            Gestión rápida de tokens API
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <Link href="/admin/api-tokens/create">
                                <Button className="w-full h-20 flex flex-col items-center justify-center space-y-2">
                                    <Plus className="h-6 w-6" />
                                    <span>Crear Token</span>
                                </Button>
                            </Link>

                            <Link href="/admin/api-tokens?estado=expirado">
                                <Button variant="outline" className="w-full h-20 flex flex-col items-center justify-center space-y-2">
                                    <Clock className="h-6 w-6" />
                                    <span>Ver Expirados</span>
                                </Button>
                            </Link>

                            <Link href="/admin/api-tokens">
                                <Button variant="outline" className="w-full h-20 flex flex-col items-center justify-center space-y-2">
                                    <Key className="h-6 w-6" />
                                    <span>Gestionar Todos</span>
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
