import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Separator } from '@/components/ui/separator';
import { toast } from 'sonner';
import { Users, Shield, Settings, ArrowLeft, Save, RotateCcw } from 'lucide-react';

interface Role {
    id: number;
    name: string;
    description?: string;
}

interface ConfiguracionData {
    clave: string;
    valor: string;
    categoria: string;
    descripcion: string;
    tipo: string;
    activo: boolean;
}

interface Props {
    roles: Role[];
    configuraciones: Record<string, ConfiguracionData>;
    configuracionesRoles: Record<number, Record<string, any>>;
}

export default function ConfiguracionRoles({ roles, configuraciones, configuracionesRoles }: Props) {
    const breadcrumbs = [
        { title: 'Administración', href: '/admin' },
        { title: 'Configuración', href: '/admin/configuracion' },
        { title: 'Configuración por Roles', href: '/admin/configuracion/roles' },
    ];

    const [configuracionesLocales, setConfiguracionesLocales] = useState(configuracionesRoles);
    const [unsavedChanges, setUnsavedChanges] = useState(false);

    const moduleConfigs = [
        {
            modulo: 'documentos',
            nombre: 'Gestión de Documentos',
            icon: <Shield className="h-5 w-5" />,
            configuraciones: [
                { key: 'documentos_crear', nombre: 'Crear documentos' },
                { key: 'documentos_editar', nombre: 'Editar documentos' },
                { key: 'documentos_eliminar', nombre: 'Eliminar documentos' },
                { key: 'documentos_aprobar', nombre: 'Aprobar documentos' },
            ]
        },
        {
            modulo: 'expedientes',
            nombre: 'Expedientes Electrónicos',
            icon: <Users className="h-5 w-5" />,
            configuraciones: [
                { key: 'expedientes_crear', nombre: 'Crear expedientes' },
                { key: 'expedientes_cerrar', nombre: 'Cerrar expedientes' },
                { key: 'expedientes_transferir', nombre: 'Transferir expedientes' },
            ]
        },
        {
            modulo: 'configuracion',
            nombre: 'Configuración del Sistema',
            icon: <Settings className="h-5 w-5" />,
            configuraciones: [
                { key: 'config_sistema', nombre: 'Configuración del sistema' },
                { key: 'config_usuarios', nombre: 'Gestión de usuarios' },
                { key: 'config_roles', nombre: 'Configuración de roles' },
            ]
        }
    ];

    const handleConfigChange = (roleId: number, configKey: string, value: boolean) => {
        setConfiguracionesLocales(prev => ({
            ...prev,
            [roleId]: {
                ...prev[roleId],
                [configKey]: value
            }
        }));
        setUnsavedChanges(true);
    };

    const saveChanges = () => {
        router.put('/admin/configuracion/roles/update', {
            configuraciones: configuracionesLocales
        }, {
            onSuccess: () => {
                toast.success('Configuraciones guardadas exitosamente');
                setUnsavedChanges(false);
            },
            onError: () => {
                toast.error('Error al guardar las configuraciones');
            }
        });
    };

    const resetChanges = () => {
        setConfiguracionesLocales(configuracionesRoles);
        setUnsavedChanges(false);
        toast.info('Cambios revertidos');
    };

    const isConfigEnabled = (roleId: number, configKey: string): boolean => {
        return configuracionesLocales[roleId]?.[configKey] || false;
    };

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Configuración por Roles" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="outline"
                            size="icon"
                            onClick={() => router.visit('/admin/configuracion')}
                        >
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Configuración por Roles</h1>
                            <p className="text-gray-600 mt-2">
                                Configura permisos específicos para cada rol del sistema
                            </p>
                        </div>
                    </div>

                    {unsavedChanges && (
                        <div className="flex gap-2">
                            <Button variant="outline" onClick={resetChanges}>
                                <RotateCcw className="h-4 w-4 mr-2" />
                                Revertir
                            </Button>
                            <Button onClick={saveChanges}>
                                <Save className="h-4 w-4 mr-2" />
                                Guardar Cambios
                            </Button>
                        </div>
                    )}
                </div>

                {/* Roles Summary */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {roles.map(role => (
                        <Card key={role.id}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-gray-600">
                                    {role.name}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {Object.values(configuracionesLocales[role.id] || {}).filter(Boolean).length}
                                </div>
                                <p className="text-xs text-gray-500">Permisos activos</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Configuration Matrix */}
                <div className="space-y-6">
                    {moduleConfigs.map(module => (
                        <Card key={module.modulo}>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    {module.icon}
                                    {module.nombre}
                                </CardTitle>
                                <CardDescription>
                                    Configuraciones específicas para el módulo de {module.nombre.toLowerCase()}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <table className="w-full">
                                        <thead>
                                            <tr className="border-b">
                                                <th className="text-left py-3 px-4 font-medium">Configuración</th>
                                                {roles.map(role => (
                                                    <th key={role.id} className="text-center py-3 px-4 font-medium min-w-32">
                                                        <Badge variant="outline">{role.name}</Badge>
                                                    </th>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {module.configuraciones.map(config => (
                                                <tr key={config.key} className="border-b">
                                                    <td className="py-3 px-4">
                                                        <div className="font-medium">{config.nombre}</div>
                                                        <div className="text-sm text-gray-500">{config.key}</div>
                                                    </td>
                                                    {roles.map(role => (
                                                        <td key={role.id} className="py-3 px-4 text-center">
                                                            <Switch
                                                                checked={isConfigEnabled(role.id, config.key)}
                                                                onCheckedChange={(checked) => 
                                                                    handleConfigChange(role.id, config.key, checked)
                                                                }
                                                            />
                                                        </td>
                                                    ))}
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Footer Actions */}
                {unsavedChanges && (
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Badge variant="destructive">Cambios sin guardar</Badge>
                                    <span className="text-sm text-gray-600">
                                        Tienes cambios pendientes por guardar
                                    </span>
                                </div>
                                <div className="flex gap-2">
                                    <Button variant="outline" onClick={resetChanges}>
                                        <RotateCcw className="h-4 w-4 mr-2" />
                                        Revertir Cambios
                                    </Button>
                                    <Button onClick={saveChanges}>
                                        <Save className="h-4 w-4 mr-2" />
                                        Guardar Configuraciones
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppSidebarLayout>
    );
}
