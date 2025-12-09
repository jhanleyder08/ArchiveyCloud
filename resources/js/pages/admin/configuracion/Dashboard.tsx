import React from 'react';
import { Head } from '@inertiajs/react';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Settings, Database, Shield, Palette, Users, Mail, Smartphone, Bell, Wrench, Download, Upload, Zap, Server } from 'lucide-react';

interface ConfiguracionData {
    clave: string;
    valor: string;
    categoria: string;
    descripcion: string;
    tipo: string;
    activo: boolean;
}

interface Estadisticas {
    configuraciones_total: number;
    configuraciones_activas: number;
    usuarios_total: number;
    roles_total: number;
}

interface Props {
    configuraciones: Record<string, ConfiguracionData>;
    estadisticas: Estadisticas;
    categorias: Record<string, ConfiguracionData[]>;
}

export default function ConfiguracionDashboard({ configuraciones, estadisticas, categorias }: Props) {
    const breadcrumbs = [
        { title: 'Administración', href: '/admin' },
        { title: 'Configuración', href: '/admin/configuracion' },
    ];

    const getCategoryIcon = (categoria: string) => {
        switch (categoria) {
            case 'sistema':
                return <Settings className="h-5 w-5" />;
            case 'branding':
                return <Palette className="h-5 w-5" />;
            case 'email':
                return <Mail className="h-5 w-5" />;
            case 'sms':
                return <Smartphone className="h-5 w-5" />;
            case 'seguridad':
                return <Shield className="h-5 w-5" />;
            case 'usuarios':
                return <Users className="h-5 w-5" />;
            case 'notificaciones':
                return <Bell className="h-5 w-5" />;
            default:
                return <Database className="h-5 w-5" />;
        }
    };

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel de Configuración" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Panel de Configuración</h1>
                        <p className="text-gray-600 mt-2">
                            Gestiona las configuraciones del sistema ArchiveyCloud
                        </p>
                    </div>
                </div>

                {/* Estadísticas */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">
                                Total Configuraciones
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{estadisticas.configuraciones_total}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">
                                Configuraciones Activas
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">
                                {estadisticas.configuraciones_activas}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">
                                Total Usuarios
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{estadisticas.usuarios_total}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">
                                Roles del Sistema
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{estadisticas.roles_total}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Accesos rápidos por categoría */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {Object.entries(categorias).map(([categoria, configs]) => (
                        <Card key={categoria} className="hover:shadow-md transition-shadow">
                            <CardHeader className="pb-4">
                                <CardTitle className="flex items-center gap-2 capitalize">
                                    {getCategoryIcon(categoria)}
                                    {categoria}
                                </CardTitle>
                                <CardDescription>
                                    {configs.length} configuración{configs.length !== 1 ? 'es' : ''}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    {configs.slice(0, 3).map((config) => (
                                        <div key={config.clave} className="flex items-center justify-between text-sm">
                                            <span className="text-gray-600 truncate">
                                                {config.descripcion || config.clave}
                                            </span>
                                            <Badge variant={config.activo ? "default" : "secondary"}>
                                                {config.activo ? "Activo" : "Inactivo"}
                                            </Badge>
                                        </div>
                                    ))}
                                    {configs.length > 3 && (
                                        <div className="text-xs text-gray-500 pt-1">
                                            +{configs.length - 3} más...
                                        </div>
                                    )}
                                </div>
                                <div className="mt-4">
                                    <Button 
                                        variant="outline" 
                                        size="sm" 
                                        className="w-full"
                                        onClick={() => window.location.href = `/admin/configuracion/${categoria}`}
                                    >
                                        Configurar {categoria}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Enlaces de acceso rápido */}
                <Card>
                    <CardHeader>
                        <CardTitle>Acceso Rápido</CardTitle>
                        <CardDescription>
                            Enlaces directos a las principales funciones de configuración
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <Button 
                                variant="outline" 
                                className="h-auto p-4 flex flex-col items-center gap-2"
                                onClick={() => window.location.href = '/admin/configuracion/branding'}
                            >
                                <Palette className="h-8 w-8 text-blue-500" />
                                <span className="font-medium">Branding y Personalización</span>
                                <span className="text-xs text-gray-500">Logos, colores, temas</span>
                            </Button>

                            <Button 
                                variant="outline" 
                                className="h-auto p-4 flex flex-col items-center gap-2" 
                                onClick={() => window.location.href = '/admin/configuracion/roles'}
                            >
                                <Users className="h-8 w-8 text-green-500" />
                                <span className="font-medium">Configuración por Roles</span>
                                <span className="text-xs text-gray-500">Permisos específicos</span>
                            </Button>

                            <Button 
                                variant="outline" 
                                className="h-auto p-4 flex flex-col items-center gap-2"
                                onClick={() => window.location.href = '/admin/configuracion/mantenimiento'}
                            >
                                <Wrench className="h-8 w-8 text-orange-500" />
                                <span className="font-medium">Mantenimiento del Sistema</span>
                                <span className="text-xs text-gray-500">Comandos, caché, optimización</span>
                            </Button>

                            <Button 
                                variant="outline" 
                                className="h-auto p-4 flex flex-col items-center gap-2"
                                onClick={() => window.location.href = '/admin/configuracion/seguridad'}
                            >
                                <Shield className="h-8 w-8 text-red-500" />
                                <span className="font-medium">Seguridad</span>
                                <span className="text-xs text-gray-500">2FA, contraseñas, bloqueos</span>
                            </Button>

                            <Button 
                                variant="outline" 
                                className="h-auto p-4 flex flex-col items-center gap-2"
                                onClick={() => window.location.href = '/admin/configuracion/notificaciones'}
                            >
                                <Bell className="h-8 w-8 text-purple-500" />
                                <span className="font-medium">Notificaciones</span>
                                <span className="text-xs text-gray-500">Email, SMS, navegador</span>
                            </Button>

                            <Button 
                                variant="outline" 
                                className="h-auto p-4 flex flex-col items-center gap-2"
                                onClick={() => window.location.href = '/admin/servicios-externos'}
                            >
                                <Server className="h-8 w-8 text-cyan-500" />
                                <span className="font-medium">Servicios Externos</span>
                                <span className="text-xs text-gray-500">Email, SMS, integraciones</span>
                            </Button>

                            <Button 
                                variant="outline" 
                                className="h-auto p-4 flex flex-col items-center gap-2"
                                onClick={() => window.location.href = '/admin/optimizacion'}
                            >
                                <Zap className="h-8 w-8 text-yellow-500" />
                                <span className="font-medium">Optimización</span>
                                <span className="text-xs text-gray-500">Rendimiento y monitoreo</span>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppSidebarLayout>
    );
}
