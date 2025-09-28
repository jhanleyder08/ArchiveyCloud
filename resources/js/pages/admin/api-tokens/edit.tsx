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
import { Switch } from '@/components/ui/switch';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    ArrowLeft, 
    Save, 
    Key, 
    Shield,
    Network,
    Plus,
    Minus,
    AlertTriangle,
    CheckCircle
} from 'lucide-react';

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
    ips_permitidas?: string[];
    activo: boolean;
    created_at: string;
    updated_at: string;
}

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Props {
    token: ApiToken;
    usuarios: Usuario[];
    permisos_disponibles: Record<string, string>;
}

interface FormData {
    nombre: string;
    descripcion: string;
    permisos: string[];
    fecha_expiracion: string;
    limite_usos: string;
    ips_permitidas: string[];
    activo: boolean;
}

export default function EditApiToken({ token, usuarios, permisos_disponibles }: Props) {
    const [nuevaIp, setNuevaIp] = useState('');
    const [hasChanges, setHasChanges] = useState(false);

    const { data, setData, put, processing, errors, reset, isDirty } = useForm<FormData>({
        nombre: token.nombre || '',
        descripcion: token.descripcion || '',
        permisos: token.permisos || [],
        fecha_expiracion: token.fecha_expiracion ? token.fecha_expiracion.split('T')[0] : '',
        limite_usos: token.limite_usos ? token.limite_usos.toString() : '',
        ips_permitidas: token.ips_permitidas || [],
        activo: token.activo,
    });

    React.useEffect(() => {
        setHasChanges(isDirty);
    }, [isDirty]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        put(`/admin/api-tokens/${token.id}`, {
            onSuccess: () => {
                setHasChanges(false);
            },
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

    // Advertencia antes de salir sin guardar
    React.useEffect(() => {
        const handleBeforeUnload = (e: BeforeUnloadEvent) => {
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        };

        window.addEventListener('beforeunload', handleBeforeUnload);
        return () => window.removeEventListener('beforeunload', handleBeforeUnload);
    }, [hasChanges]);

    return (
        <AppLayout>
            <Head title={`Editar ${token.nombre} - API Tokens - ArchiveyCloud`} />

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
                            <h1 className="text-3xl font-bold text-gray-900">Editar Token API</h1>
                            <p className="text-gray-600 mt-1">
                                Modifica la configuración del token
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-3">
                        {getEstadoBadge(token.estado)}
                        <Link href={`/admin/api-tokens/${token.id}`}>
                            <Button variant="outline">
                                Ver Detalles
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Advertencia de cambios sin guardar */}
                {hasChanges && (
                    <Alert className="border-orange-200 bg-orange-50">
                        <AlertTriangle className="h-4 w-4 text-orange-600" />
                        <AlertDescription className="text-orange-800">
                            Tienes cambios sin guardar. Asegúrate de guardar antes de salir.
                        </AlertDescription>
                    </Alert>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Información básica */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <Key className="h-5 w-5 mr-2" />
                                Información del Token
                            </CardTitle>
                            <CardDescription>
                                Configuración básica del token API
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
                                    <Label>Propietario</Label>
                                    <Input
                                        value={`${token.usuario.name} (${token.usuario.email})`}
                                        readOnly
                                        className="bg-gray-50"
                                    />
                                    <p className="text-xs text-gray-600">
                                        El propietario no se puede cambiar
                                    </p>
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

                            {/* Estado activo/inactivo */}
                            <div className="flex items-center space-x-2 pt-4 border-t">
                                <Switch
                                    id="activo"
                                    checked={data.activo}
                                    onCheckedChange={(checked) => setData('activo', checked)}
                                />
                                <Label htmlFor="activo" className="cursor-pointer">
                                    Token activo
                                </Label>
                                <p className="text-sm text-gray-600 ml-4">
                                    {data.activo ? 'El token puede ser usado' : 'El token está deshabilitado'}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Permisos */}
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

                    {/* Configuración avanzada */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Configuración Avanzada</CardTitle>
                            <CardDescription>
                                Restricciones y límites del token
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
                                        Usos actuales: {token.usos_realizados}
                                        {token.limite_usos && ` / ${token.limite_usos}`}
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

                    {/* Botones de acción */}
                    <div className="flex justify-end space-x-4">
                        <Link href="/admin/api-tokens">
                            <Button variant="outline" type="button">
                                Cancelar
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing || !hasChanges}>
                            {processing ? (
                                <>
                                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2" />
                                    Guardando...
                                </>
                            ) : (
                                <>
                                    <Save className="h-4 w-4 mr-2" />
                                    Guardar Cambios
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
