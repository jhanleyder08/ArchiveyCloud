import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    Key, 
    Plus, 
    Search, 
    Eye, 
    Edit, 
    Trash2, 
    RefreshCw, 
    Power,
    Calendar,
    User,
    Activity,
    Clock,
    Shield,
    AlertTriangle,
    CheckCircle
} from 'lucide-react';
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
    logs_count: number;
    created_at: string;
}

interface Props {
    tokens?: {
        data: ApiToken[];
        links: any;
        meta: any;
    };
    estadisticas?: {
        total: number;
        activos: number;
        expirados: number;
        inactivos: number;
        usos_ultimo_mes: number;
    };
    filtros: {
        buscar?: string;
        estado?: string;
        usuario_id?: string;
    };
    usuarios: Array<{
        id: number;
        name: string;
        email: string;
    }>;
}

export default function ApiTokensIndex({ tokens, estadisticas, filtros, usuarios }: Props) {
    // DEBUG: Log detallado de props recibidos
    console.log('=== API TOKENS INDEX PROPS DEBUG ===');
    console.log('tokens:', tokens);
    console.log('estadisticas:', estadisticas);
    console.log('tokens type:', typeof tokens);
    console.log('estadisticas type:', typeof estadisticas);
    console.log('tokens is null:', tokens === null);
    console.log('estadisticas is null:', estadisticas === null);
    console.log('tokens is undefined:', tokens === undefined);
    console.log('estadisticas is undefined:', estadisticas === undefined);
    if (tokens) {
        console.log('tokens.meta:', tokens.meta);
        console.log('tokens.data length:', tokens.data?.length);
    }
    if (estadisticas) {
        console.log('estadisticas.total:', estadisticas?.total);
    }
    console.log('=====================================');
    
    // Valores por defecto seguros para estadísticas
    const stats = estadisticas || {
        total: 0,
        activos: 0,
        expirados: 0,
        inactivos: 0,
        usos_ultimo_mes: 0
    };
    
    // Valores por defecto seguros para tokens
    const tokensData = tokens || {
        data: [],
        links: [],
        meta: {
            total: 0,
            from: 0,
            to: 0,
            last_page: 1,
            current_page: 1
        }
    };
    
    const [filtrosLocales, setFiltrosLocales] = useState(filtros);
    const [result, setResult] = useState<{success: boolean, message: string} | null>(null);

    const handleFiltroChange = (campo: string, valor: any) => {
        const nuevosFiltros = { ...filtrosLocales } as any;
        nuevosFiltros[campo] = valor;
        
        if (!valor || valor === 'all') {
            delete nuevosFiltros[campo];
        }
        
        setFiltrosLocales(nuevosFiltros);
        router.get('/admin/api-tokens', nuevosFiltros, { preserveState: true });
    };

    const getEstadoBadge = (token: ApiToken) => {
        switch (token.estado) {
            case 'activo':
                return <Badge className="bg-green-100 text-green-800">Activo</Badge>;
            case 'inactivo':
                return <Badge variant="secondary">Inactivo</Badge>;
            case 'expirado':
                return <Badge className="bg-red-100 text-red-800">Expirado</Badge>;
            case 'limite_alcanzado':
                return <Badge className="bg-orange-100 text-orange-800">Límite alcanzado</Badge>;
            default:
                return <Badge variant="outline">{token.estado}</Badge>;
        }
    };

    const handleAction = async (action: string, tokenId: number) => {
        try {
            await router.post(`/admin/api-tokens/${tokenId}/${action}`, {}, {
                onSuccess: () => {
                    setResult({
                        success: true,
                        message: `Token ${action === 'revocar' ? 'revocado' : 'renovado'} exitosamente`
                    });
                },
                onError: () => {
                    setResult({
                        success: false,
                        message: `Error al ${action === 'revocar' ? 'revocar' : 'renovar'} el token`
                    });
                }
            });
        } catch (error) {
            setResult({
                success: false,
                message: `Error al ${action === 'revocar' ? 'revocar' : 'renovar'} el token`
            });
        }
    };

    return (
        <AppLayout>
            <Head title="API Tokens - ArchiveyCloud" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">API Tokens</h1>
                        <p className="text-gray-600 mt-1">
                            Gestiona tokens de autenticación para integraciones externas
                        </p>
                    </div>
                    <div className="flex items-center space-x-3">
                        <Link href="/admin/api-tokens/dashboard">
                            <Button variant="outline">
                                <Activity className="h-4 w-4 mr-2" />
                                Dashboard
                            </Button>
                        </Link>
                        <Link href="/admin/api-tokens/create">
                            <Button>
                                <Plus className="h-4 w-4 mr-2" />
                                Crear Token
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Resultado */}
                {result && (
                    <Alert className={result.success ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'}>
                        <div className="flex items-center space-x-2">
                            {result.success ? (
                                <CheckCircle className="h-4 w-4 text-green-600" />
                            ) : (
                                <AlertTriangle className="h-4 w-4 text-red-600" />
                            )}
                            <AlertDescription className={result.success ? 'text-green-800' : 'text-red-800'}>
                                {result.message}
                            </AlertDescription>
                        </div>
                    </Alert>
                )}

                {/* Estadísticas - TEMPORALMENTE REMOVIDAS PARA DEBUG */}
                <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <Key className="h-8 w-8 text-blue-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total Tokens</p>
                                    <p className="text-2xl font-bold">DEBUG: 0</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <CheckCircle className="h-8 w-8 text-green-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Activos</p>
                                    <p className="text-2xl font-bold">DEBUG: 0</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <Clock className="h-8 w-8 text-orange-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Expirados</p>
                                    <p className="text-2xl font-bold">DEBUG: 0</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <Power className="h-8 w-8 text-gray-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Inactivos</p>
                                    <p className="text-2xl font-bold">DEBUG: 0</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <Activity className="h-8 w-8 text-purple-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Uso (mes)</p>
                                    <p className="text-2xl font-bold">DEBUG: 0</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filtros */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Filtros</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {/* Búsqueda */}
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Buscar</label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                    <Input
                                        type="text"
                                        placeholder="Nombre, descripción, usuario..."
                                        value={filtrosLocales.buscar || ''}
                                        onChange={(e) => handleFiltroChange('buscar', e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                            </div>

                            {/* Estado */}
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Estado</label>
                                <Select
                                    value={filtrosLocales.estado || 'all'}
                                    onValueChange={(value) => handleFiltroChange('estado', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        <SelectItem value="activo">Activos</SelectItem>
                                        <SelectItem value="inactivo">Inactivos</SelectItem>
                                        <SelectItem value="expirado">Expirados</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Usuario */}
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Usuario</label>
                                <Select
                                    value={filtrosLocales.usuario_id || 'all'}
                                    onValueChange={(value) => handleFiltroChange('usuario_id', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        {usuarios.map((usuario) => (
                                            <SelectItem key={usuario.id} value={usuario.id.toString()}>
                                                {usuario.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Lista de Tokens */}
                <Card>
                    <CardHeader>
                        <CardTitle>Tokens API ({tokensData?.meta?.total || 0})</CardTitle>
                        <CardDescription>
                            Mostrando {tokensData?.meta?.from || 0} a {tokensData?.meta?.to || 0} de {tokensData?.meta?.total || 0} tokens
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {(tokensData?.data || []).map((token) => (
                                <div key={token.id} className="border rounded-lg p-4 hover:bg-gray-50">
                                    <div className="flex items-center justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center space-x-3">
                                                <Key className="h-5 w-5 text-gray-400" />
                                                <div>
                                                    <h3 className="font-medium">{token.nombre}</h3>
                                                    {token.descripcion && (
                                                        <p className="text-sm text-gray-600">{token.descripcion}</p>
                                                    )}
                                                </div>
                                                {getEstadoBadge(token)}
                                            </div>

                                            <div className="mt-3 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                                <div className="flex items-center text-gray-600">
                                                    <User className="h-4 w-4 mr-1" />
                                                    {token.usuario.name}
                                                </div>
                                                
                                                <div className="flex items-center text-gray-600">
                                                    <Shield className="h-4 w-4 mr-1" />
                                                    {token.permisos.length} permiso{token.permisos.length !== 1 ? 's' : ''}
                                                </div>

                                                <div className="flex items-center text-gray-600">
                                                    <Activity className="h-4 w-4 mr-1" />
                                                    {token.logs_count} uso{token.logs_count !== 1 ? 's' : ''}
                                                </div>

                                                <div className="flex items-center text-gray-600">
                                                    <Calendar className="h-4 w-4 mr-1" />
                                                    {token.ultimo_uso ? (
                                                        formatDistanceToNow(new Date(token.ultimo_uso), { 
                                                            addSuffix: true, 
                                                            locale: es 
                                                        })
                                                    ) : (
                                                        'Nunca usado'
                                                    )}
                                                </div>
                                            </div>

                                            {/* Barra de progreso si tiene límite de usos */}
                                            {token.limite_usos && (
                                                <div className="mt-3">
                                                    <div className="flex justify-between text-xs text-gray-600 mb-1">
                                                        <span>Uso: {token.usos_realizados}/{token.limite_usos}</span>
                                                        <span>{token.porcentaje_uso}%</span>
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
                                            )}
                                        </div>

                                        <div className="flex items-center space-x-2">
                                            <Link href={`/admin/api-tokens/${token.id}`}>
                                                <Button variant="outline" size="sm">
                                                    <Eye className="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            
                                            <Link href={`/admin/api-tokens/${token.id}/edit`}>
                                                <Button variant="outline" size="sm">
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                            </Link>

                                            {token.estado === 'activo' && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleAction('renovar', token.id)}
                                                >
                                                    <RefreshCw className="h-4 w-4" />
                                                </Button>
                                            )}

                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('revocar', token.id)}
                                                className="text-red-600 hover:text-red-700"
                                            >
                                                <Power className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))}

                            {(tokensData?.data?.length || 0) === 0 && (
                                <div className="text-center py-8">
                                    <Key className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">No hay tokens</h3>
                                    <p className="text-gray-600 mb-4">
                                        {Object.keys(filtros).length > 0 
                                            ? 'No se encontraron tokens con los filtros aplicados'
                                            : 'Aún no se han creado tokens API'
                                        }
                                    </p>
                                    <Link href="/admin/api-tokens/create">
                                        <Button>
                                            <Plus className="h-4 w-4 mr-2" />
                                            Crear primer token
                                        </Button>
                                    </Link>
                                </div>
                            )}
                        </div>

                        {/* Paginación */}
                        {(tokensData?.meta?.last_page || 0) > 1 && (
                            <div className="flex items-center justify-between mt-6">
                                <div className="text-sm text-gray-700">
                                    Mostrando {tokensData?.meta?.from || 0} a {tokensData?.meta?.to || 0} de {tokensData?.meta?.total || 0} resultados
                                </div>
                                <div className="flex space-x-1">
                                    {(tokensData?.links || []).map((link: any, index: number) => (
                                        <Button
                                            key={index}
                                            variant={link.active ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => link.url && router.get(link.url)}
                                            disabled={!link.url}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
