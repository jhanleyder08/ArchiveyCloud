import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    ArrowLeft, 
    Save, 
    Key, 
    Copy, 
    CheckCircle, 
    AlertTriangle,
    Calendar,
    Shield,
    Network,
    Plus,
    Minus
} from 'lucide-react';

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Props {
    usuarios: Usuario[];
    permisos_disponibles: Record<string, string>;
}

interface FormData {
    nombre: string;
    descripcion: string;
    usuario_id: string;
    permisos: string[];
    fecha_expiracion: string;
    limite_usos: string;
    ips_permitidas: string[];
}

export default function CreateApiToken({ usuarios, permisos_disponibles }: Props) {
    const [nuevaIp, setNuevaIp] = useState('');
    const [tokenCreado, setTokenCreado] = useState<{token: any, plain_token: string} | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        nombre: '',
        descripcion: '',
        usuario_id: '',
        permisos: [],
        fecha_expiracion: '',
        limite_usos: '',
        ips_permitidas: [],
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        post('/admin/api-tokens', {
            onSuccess: (response: any) => {
                if (response.props?.flash?.token_data) {
                    setTokenCreado(response.props.flash.token_data);
                }
            },
            onError: (errors) => {
                console.error('Errores:', errors);
            }
        });
    };

    const handlePermisoChange = (permiso: string, checked: boolean) => {
        if (checked) {
            setData('permisos', [...data.permisos, permiso]);
        } else {
            setData('permisos', data.permisos.filter(p => p !== permiso));
        }
    };

    const agregarIp = () => {
        if (nuevaIp && !data.ips_permitidas.includes(nuevaIp)) {
            setData('ips_permitidas', [...data.ips_permitidas, nuevaIp]);
            setNuevaIp('');
        }
    };

    const removerIp = (ip: string) => {
        setData('ips_permitidas', data.ips_permitidas.filter(i => i !== ip));
    };

    const copiarToken = () => {
        if (tokenCreado?.plain_token) {
            navigator.clipboard.writeText(tokenCreado.plain_token);
        }
    };

    if (tokenCreado) {
        return (
            <AppLayout>
                <Head title="Token Creado - API Tokens - ArchiveyCloud" />

                <div className="space-y-6">
                    <div className="flex items-center space-x-4">
                        <Link href="/admin/api-tokens">
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver a Tokens
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Token Creado Exitosamente</h1>
                            <p className="text-gray-600 mt-1">
                                Guarda el token de forma segura, no podrás verlo nuevamente
                            </p>
                        </div>
                    </div>

                    <Alert className="border-green-200 bg-green-50">
                        <CheckCircle className="h-4 w-4 text-green-600" />
                        <AlertDescription className="text-green-800">
                            El token API ha sido creado exitosamente
                        </AlertDescription>
                    </Alert>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <Key className="h-5 w-5 mr-2" />
                                Nuevo Token API
                            </CardTitle>
                            <CardDescription>
                                ⚠️ Este es el único momento en que podrás ver el token completo. Guárdalo en un lugar seguro.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Información del token */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <Label>Nombre</Label>
                                    <Input value={tokenCreado.token.nombre} readOnly />
                                </div>
                                <div>
                                    <Label>Usuario</Label>
                                    <Input value={tokenCreado.token.usuario?.name || ''} readOnly />
                                </div>
                            </div>

                            {/* Token */}
                            <div className="space-y-2">
                                <Label>Token de Autenticación</Label>
                                <div className="flex items-center space-x-2">
                                    <Input
                                        value={tokenCreado.plain_token}
                                        readOnly
                                        className="font-mono text-sm"
                                    />
                                    <Button onClick={copiarToken} variant="outline">
                                        <Copy className="h-4 w-4" />
                                    </Button>
                                </div>
                                <p className="text-sm text-gray-600">
                                    Incluye este token en el header: <code>Authorization: Bearer {tokenCreado.plain_token}</code>
                                </p>
                            </div>

                            {/* Permisos */}
                            <div className="space-y-2">
                                <Label>Permisos Asignados</Label>
                                <div className="flex flex-wrap gap-2">
                                    {tokenCreado.token.permisos?.map((permiso: string) => (
                                        <Badge key={permiso} variant="outline">
                                            <Shield className="h-3 w-3 mr-1" />
                                            {permisos_disponibles[permiso] || permiso}
                                        </Badge>
                                    ))}
                                </div>
                            </div>

                            <div className="flex justify-center space-x-4 pt-4">
                                <Link href="/admin/api-tokens">
                                    <Button variant="outline">
                                        Ver Todos los Tokens
                                    </Button>
                                </Link>
                                <Link href="/admin/api-tokens/create">
                                    <Button>
                                        <Plus className="h-4 w-4 mr-2" />
                                        Crear Otro Token
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="Crear Token - API Tokens - ArchiveyCloud" />

            <div className="space-y-6">
                <div className="flex items-center space-x-4">
                    <Link href="/admin/api-tokens">
                        <Button variant="outline" size="sm">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Crear Token API</h1>
                        <p className="text-gray-600 mt-1">
                            Crea un nuevo token de autenticación para integraciones externas
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Información Básica</CardTitle>
                            <CardDescription>
                                Define la información principal del token API
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="nombre">Nombre del Token *</Label>
                                    <Input
                                        id="nombre"
                                        value={data.nombre}
                                        onChange={(e) => setData('nombre', e.target.value)}
                                        placeholder="Ej: Sistema CRM, Aplicación Móvil"
                                        className={errors.nombre ? 'border-red-500' : ''}
                                    />
                                    {errors.nombre && (
                                        <p className="text-sm text-red-600">{errors.nombre}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="usuario_id">Usuario Propietario *</Label>
                                    <Select value={data.usuario_id} onValueChange={(value) => setData('usuario_id', value)}>
                                        <SelectTrigger className={errors.usuario_id ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Selecciona un usuario" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {usuarios.map((usuario) => (
                                                <SelectItem key={usuario.id} value={usuario.id.toString()}>
                                                    {usuario.name} - {usuario.email}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.usuario_id && (
                                        <p className="text-sm text-red-600">{errors.usuario_id}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="descripcion">Descripción</Label>
                                <Textarea
                                    id="descripcion"
                                    value={data.descripcion}
                                    onChange={(e) => setData('descripcion', e.target.value)}
                                    placeholder="Describe el propósito de este token..."
                                    rows={3}
                                    className={errors.descripcion ? 'border-red-500' : ''}
                                />
                                {errors.descripcion && (
                                    <p className="text-sm text-red-600">{errors.descripcion}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <Shield className="h-5 w-5 mr-2" />
                                Permisos
                            </CardTitle>
                            <CardDescription>
                                Selecciona los permisos que tendrá este token
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {Object.entries(permisos_disponibles).map(([permiso, descripcion]) => (
                                    <div key={permiso} className="flex items-center space-x-2">
                                        <Checkbox
                                            id={permiso}
                                            checked={data.permisos.includes(permiso)}
                                            onCheckedChange={(checked) => handlePermisoChange(permiso, checked as boolean)}
                                        />
                                        <Label htmlFor={permiso} className="text-sm cursor-pointer">
                                            <span className="font-medium">{permiso}</span>
                                            <span className="block text-gray-600 text-xs">{descripcion}</span>
                                        </Label>
                                    </div>
                                ))}
                            </div>
                            {errors.permisos && (
                                <Alert className="border-red-200 bg-red-50">
                                    <AlertTriangle className="h-4 w-4 text-red-600" />
                                    <AlertDescription className="text-red-800">
                                        {errors.permisos}
                                    </AlertDescription>
                                </Alert>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Configuración Avanzada</CardTitle>
                            <CardDescription>
                                Opcional: configura restricciones adicionales
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="fecha_expiracion">Fecha de Expiración</Label>
                                    <Input
                                        id="fecha_expiracion"
                                        type="date"
                                        value={data.fecha_expiracion}
                                        onChange={(e) => setData('fecha_expiracion', e.target.value)}
                                        min={new Date().toISOString().split('T')[0]}
                                        className={errors.fecha_expiracion ? 'border-red-500' : ''}
                                    />
                                    <p className="text-xs text-gray-600">
                                        Deja vacío para que no expire nunca
                                    </p>
                                    {errors.fecha_expiracion && (
                                        <p className="text-sm text-red-600">{errors.fecha_expiracion}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="limite_usos">Límite de Usos</Label>
                                    <Input
                                        id="limite_usos"
                                        type="number"
                                        value={data.limite_usos}
                                        onChange={(e) => setData('limite_usos', e.target.value)}
                                        placeholder="Ej: 1000"
                                        min="1"
                                        className={errors.limite_usos ? 'border-red-500' : ''}
                                    />
                                    <p className="text-xs text-gray-600">
                                        Deja vacío para uso ilimitado
                                    </p>
                                    {errors.limite_usos && (
                                        <p className="text-sm text-red-600">{errors.limite_usos}</p>
                                    )}
                                </div>
                            </div>

                            {/* IPs Permitidas */}
                            <div className="space-y-2">
                                <Label>IPs Permitidas</Label>
                                <div className="space-y-3">
                                    <div className="flex items-center space-x-2">
                                        <Input
                                            value={nuevaIp}
                                            onChange={(e) => setNuevaIp(e.target.value)}
                                            placeholder="192.168.1.100"
                                            className="flex-1"
                                        />
                                        <Button type="button" onClick={agregarIp} variant="outline">
                                            <Plus className="h-4 w-4" />
                                        </Button>
                                    </div>
                                    
                                    {data.ips_permitidas.length > 0 && (
                                        <div className="flex flex-wrap gap-2">
                                            {data.ips_permitidas.map((ip) => (
                                                <Badge key={ip} variant="outline" className="flex items-center space-x-1">
                                                    <Network className="h-3 w-3" />
                                                    <span>{ip}</span>
                                                    <button
                                                        type="button"
                                                        onClick={() => removerIp(ip)}
                                                        className="ml-1 hover:text-red-600"
                                                    >
                                                        <Minus className="h-3 w-3" />
                                                    </button>
                                                </Badge>
                                            ))}
                                        </div>
                                    )}
                                    
                                    <p className="text-xs text-gray-600">
                                        Deja vacío para permitir acceso desde cualquier IP
                                    </p>
                                </div>
                                {errors.ips_permitidas && (
                                    <p className="text-sm text-red-600">{errors.ips_permitidas}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end space-x-4">
                        <Link href="/admin/api-tokens">
                            <Button variant="outline">
                                Cancelar
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            {processing ? (
                                <>
                                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2" />
                                    Creando...
                                </>
                            ) : (
                                <>
                                    <Save className="h-4 w-4 mr-2" />
                                    Crear Token
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
