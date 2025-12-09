import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { toast } from 'sonner';
import { 
    Settings, 
    ArrowLeft,
    RefreshCw,
    Globe,
    Clock,
    Database,
    Shield,
    Bell,
    Mail,
    Smartphone,
    Palette,
} from 'lucide-react';

interface ConfiguracionData {
    id: number;
    clave: string;
    valor: string;
    categoria: string;
    descripcion: string;
    tipo: string;
    activo: boolean;
}

interface Props {
    configuraciones: ConfiguracionData[];
    categoria?: string;
    titulo?: string;
}

export default function ConfiguracionSistema({ configuraciones, categoria = 'sistema', titulo = 'Sistema' }: Props) {
    const breadcrumbs = [
        { title: 'Administración', href: '/admin' },
        { title: 'Configuración', href: '/admin/configuracion' },
        { title: titulo, href: `/admin/configuracion/${categoria}` },
    ];

    const getCategoryIcon = () => {
        switch (categoria) {
            case 'sistema': return <Settings className="h-5 w-5" />;
            case 'email': return <Mail className="h-5 w-5" />;
            case 'sms': return <Smartphone className="h-5 w-5" />;
            case 'seguridad': return <Shield className="h-5 w-5" />;
            case 'branding': return <Palette className="h-5 w-5" />;
            case 'notificaciones': return <Bell className="h-5 w-5" />;
            default: return <Settings className="h-5 w-5" />;
        }
    };

    const [saving, setSaving] = useState<string | null>(null);

    const handleUpdateConfig = (config: ConfiguracionData, newValue: string | boolean) => {
        setSaving(config.clave);
        
        const valor = typeof newValue === 'boolean' ? (newValue ? 'true' : 'false') : newValue;
        
        router.put(`/admin/configuracion/${config.clave}`, {
            valor: valor,
            activo: config.activo,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Configuración "${config.clave}" actualizada`);
                setSaving(null);
            },
            onError: () => {
                toast.error('Error al actualizar la configuración');
                setSaving(null);
            }
        });
    };

    const handleToggleActive = (config: ConfiguracionData) => {
        setSaving(config.clave);
        
        router.put(`/admin/configuracion/${config.clave}`, {
            valor: config.valor,
            activo: !config.activo,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Configuración "${config.clave}" ${!config.activo ? 'activada' : 'desactivada'}`);
                setSaving(null);
            },
            onError: () => {
                toast.error('Error al actualizar la configuración');
                setSaving(null);
            }
        });
    };

    const getConfigIcon = (clave: string) => {
        if (clave.includes('timezone') || clave.includes('time')) return <Clock className="h-4 w-4" />;
        if (clave.includes('locale') || clave.includes('lang')) return <Globe className="h-4 w-4" />;
        if (clave.includes('cache') || clave.includes('database')) return <Database className="h-4 w-4" />;
        if (clave.includes('security') || clave.includes('auth')) return <Shield className="h-4 w-4" />;
        if (clave.includes('notification')) return <Bell className="h-4 w-4" />;
        return <Settings className="h-4 w-4" />;
    };

    const renderConfigInput = (config: ConfiguracionData) => {
        const isSaving = saving === config.clave;

        // Para valores booleanos
        if (config.tipo === 'boolean' || config.valor === 'true' || config.valor === 'false') {
            return (
                <div className="flex items-center gap-2">
                    <Switch
                        checked={config.valor === 'true'}
                        onCheckedChange={(checked) => handleUpdateConfig(config, checked)}
                        disabled={isSaving}
                    />
                    <span className="text-sm text-muted-foreground">
                        {config.valor === 'true' ? 'Activado' : 'Desactivado'}
                    </span>
                </div>
            );
        }

        // Para valores numéricos
        if (config.tipo === 'numero' || !isNaN(Number(config.valor))) {
            return (
                <Input
                    type="number"
                    defaultValue={config.valor}
                    className="max-w-xs"
                    disabled={isSaving}
                    onBlur={(e) => {
                        if (e.target.value !== config.valor) {
                            handleUpdateConfig(config, e.target.value);
                        }
                    }}
                />
            );
        }

        // Para texto normal
        return (
            <Input
                type="text"
                defaultValue={config.valor}
                className="max-w-md"
                disabled={isSaving}
                onBlur={(e) => {
                    if (e.target.value !== config.valor) {
                        handleUpdateConfig(config, e.target.value);
                    }
                }}
            />
        );
    };

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title={`Configuración - ${titulo}`} />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => router.visit('/admin/configuracion')}
                        >
                            <ArrowLeft className="h-5 w-5" />
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                {getCategoryIcon()}
                                Configuración de {titulo}
                            </h1>
                            <p className="text-gray-600 dark:text-gray-400 mt-1">
                                Gestiona las configuraciones de {titulo.toLowerCase()}
                            </p>
                        </div>
                    </div>
                    <Button
                        variant="outline"
                        onClick={() => router.reload()}
                    >
                        <RefreshCw className="h-4 w-4 mr-2" />
                        Actualizar
                    </Button>
                </div>

                {/* Configuraciones */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            {getCategoryIcon()}
                            Configuraciones de {titulo}
                        </CardTitle>
                        <CardDescription>
                            {configuraciones.length} configuración{configuraciones.length !== 1 ? 'es' : ''} disponible{configuraciones.length !== 1 ? 's' : ''}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {configuraciones.length === 0 ? (
                            <div className="text-center py-8 text-muted-foreground">
                                <div className="mx-auto mb-4 opacity-50">{getCategoryIcon()}</div>
                                <p>No hay configuraciones de {titulo.toLowerCase()} disponibles</p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {configuraciones.map((config, index) => (
                                    <div key={config.clave}>
                                        <div className="flex items-start justify-between py-4">
                                            <div className="flex items-start gap-3 flex-1">
                                                <div className="mt-1 text-muted-foreground">
                                                    {getConfigIcon(config.clave)}
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-2 mb-1">
                                                        <Label className="font-medium">
                                                            {config.clave}
                                                        </Label>
                                                        <Badge variant={config.activo ? "default" : "secondary"} className="text-xs">
                                                            {config.activo ? "Activo" : "Inactivo"}
                                                        </Badge>
                                                        {saving === config.clave && (
                                                            <RefreshCw className="h-3 w-3 animate-spin text-primary" />
                                                        )}
                                                    </div>
                                                    {config.descripcion && (
                                                        <p className="text-sm text-muted-foreground mb-2">
                                                            {config.descripcion}
                                                        </p>
                                                    )}
                                                    <div className="mt-2">
                                                        {renderConfigInput(config)}
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="ml-4">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleToggleActive(config)}
                                                    disabled={saving === config.clave}
                                                >
                                                    {config.activo ? 'Desactivar' : 'Activar'}
                                                </Button>
                                            </div>
                                        </div>
                                        {index < configuraciones.length - 1 && <Separator />}
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Información adicional */}
                <Card>
                    <CardHeader>
                        <CardTitle>Información</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-sm text-muted-foreground space-y-2">
                            <p>• Los cambios se guardan automáticamente al modificar cada campo.</p>
                            <p>• Las configuraciones desactivadas no afectarán el comportamiento del sistema.</p>
                            <p>• Algunos cambios pueden requerir limpiar la caché para tomar efecto.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppSidebarLayout>
    );
}
