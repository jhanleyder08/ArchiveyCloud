import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Separator } from '@/components/ui/separator';
// AlertDialog components replaced with Dialog
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { toast } from 'sonner';
import { 
    Settings, 
    Palette, 
    Users, 
    Shield, 
    Mail, 
    Smartphone, 
    Bell, 
    Database, 
    Download, 
    Upload,
    Wrench,
    Save,
    RefreshCw
} from 'lucide-react';

interface Configuracion {
    id: number;
    clave: string;
    valor: string;
    categoria: string;
    tipo: string;
    descripcion: string;
    activo: boolean;
}

interface Role {
    id: number;
    name: string;
    description: string;
}

interface Estadisticas {
    configuraciones_total: number;
    configuraciones_activas: number;
    usuarios_total: number;
    roles_total: number;
    cache_size: number;
    storage_size: number;
}

interface Categorias {
    sistema: Configuracion[];
    email: Configuracion[];
    sms: Configuracion[];
    seguridad: Configuracion[];
    branding: Configuracion[];
    notificaciones: Configuracion[];
}

interface Props {
    configuraciones: Record<string, Configuracion>;
    estadisticas: Estadisticas;
    categorias: Categorias;
    roles: Role[];
}

export default function ConfiguracionIndex({ 
    configuraciones = {}, 
    estadisticas,
    categorias,
    roles = [] 
}: Props) {
    const [selectedTab, setSelectedTab] = useState('sistema');
    const [unsavedChanges, setUnsavedChanges] = useState<Record<string, any>>({});
    const [loading, setLoading] = useState(false);

    // Estadísticas con valores por defecto
    const stats = estadisticas || {
        configuraciones_total: 0,
        configuraciones_activas: 0,
        usuarios_total: 0,
        roles_total: 0,
        cache_size: 0,
        storage_size: 0
    };

    // Categorías con valores por defecto
    const cats = categorias || {
        sistema: [],
        email: [],
        sms: [],
        seguridad: [],
        branding: [],
        notificaciones: []
    };

    const formatBytes = (bytes: number) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const handleConfigChange = (clave: string, valor: any) => {
        setUnsavedChanges(prev => ({
            ...prev,
            [clave]: valor
        }));
    };

    const saveConfiguration = async (clave: string) => {
        if (!unsavedChanges[clave]) return;

        setLoading(true);
        try {
            await router.put(route('admin.configuracion.actualizar', clave), {
                valor: unsavedChanges[clave],
                activo: true
            });

            // Limpiar cambio guardado
            setUnsavedChanges(prev => {
                const newChanges = { ...prev };
                delete newChanges[clave];
                return newChanges;
            });

            toast.success('Configuración actualizada exitosamente');
        } catch (error) {
            console.error('Error saving configuration:', error);
            toast.error('Error al actualizar configuración');
        } finally {
            setLoading(false);
        }
    };

    const saveAllChanges = async () => {
        setLoading(true);
        const promises = Object.keys(unsavedChanges).map(clave => 
            router.put(route('admin.configuracion.actualizar', clave), {
                valor: unsavedChanges[clave],
                activo: true
            })
        );

        try {
            await Promise.all(promises);
            setUnsavedChanges({});
            toast.success('Todas las configuraciones guardadas exitosamente');
        } catch (error) {
            console.error('Error saving all configurations:', error);
            toast.error('Error al guardar configuraciones');
        } finally {
            setLoading(false);
        }
    };

    const exportConfig = () => {
        window.open(route('admin.configuracion.exportar'), '_blank');
    };

    const renderConfigInput = (config: Configuracion) => {
        const currentValue = unsavedChanges[config.clave] ?? config.valor;
        const hasChanges = unsavedChanges[config.clave] !== undefined;

        switch (config.tipo) {
            case 'boolean':
                return (
                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label>{config.descripcion}</Label>
                            <p className="text-sm text-muted-foreground">{config.clave}</p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Switch
                                checked={currentValue === 'true' || currentValue === true}
                                onCheckedChange={(checked) => 
                                    handleConfigChange(config.clave, checked ? 'true' : 'false')
                                }
                            />
                            {hasChanges && (
                                <Button 
                                    size="sm" 
                                    variant="ghost"
                                    onClick={() => saveConfiguration(config.clave)}
                                    disabled={loading}
                                >
                                    <Save className="h-4 w-4" />
                                </Button>
                            )}
                        </div>
                    </div>
                );

            case 'numero':
                return (
                    <div className="space-y-2">
                        <Label htmlFor={config.clave}>{config.descripcion}</Label>
                        <div className="flex gap-2">
                            <Input
                                id={config.clave}
                                type="number"
                                value={currentValue}
                                onChange={(e) => handleConfigChange(config.clave, e.target.value)}
                                className={hasChanges ? 'border-orange-500' : ''}
                            />
                            {hasChanges && (
                                <Button 
                                    size="sm" 
                                    variant="ghost"
                                    onClick={() => saveConfiguration(config.clave)}
                                    disabled={loading}
                                >
                                    <Save className="h-4 w-4" />
                                </Button>
                            )}
                        </div>
                        <p className="text-sm text-muted-foreground">{config.clave}</p>
                    </div>
                );

            case 'color':
                return (
                    <div className="space-y-2">
                        <Label htmlFor={config.clave}>{config.descripcion}</Label>
                        <div className="flex gap-2">
                            <Input
                                id={config.clave}
                                type="color"
                                value={currentValue}
                                onChange={(e) => handleConfigChange(config.clave, e.target.value)}
                                className={`w-20 h-10 ${hasChanges ? 'border-orange-500' : ''}`}
                            />
                            <Input
                                value={currentValue}
                                onChange={(e) => handleConfigChange(config.clave, e.target.value)}
                                className={`flex-1 ${hasChanges ? 'border-orange-500' : ''}`}
                                placeholder="#000000"
                            />
                            {hasChanges && (
                                <Button 
                                    size="sm" 
                                    variant="ghost"
                                    onClick={() => saveConfiguration(config.clave)}
                                    disabled={loading}
                                >
                                    <Save className="h-4 w-4" />
                                </Button>
                            )}
                        </div>
                        <p className="text-sm text-muted-foreground">{config.clave}</p>
                    </div>
                );

            case 'seleccion':
                return (
                    <div className="space-y-2">
                        <Label htmlFor={config.clave}>{config.descripcion}</Label>
                        <div className="flex gap-2">
                            <Select value={currentValue} onValueChange={(value) => handleConfigChange(config.clave, value)}>
                                <SelectTrigger className={hasChanges ? 'border-orange-500' : ''}>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {/* Opciones dinámicas según la configuración */}
                                    {config.clave === 'tema_predeterminado' && (
                                        <>
                                            <SelectItem value="light">Claro</SelectItem>
                                            <SelectItem value="dark">Oscuro</SelectItem>
                                            <SelectItem value="auto">Automático</SelectItem>
                                        </>
                                    )}
                                    {config.clave === 'mail_mailer' && (
                                        <>
                                            <SelectItem value="smtp">SMTP</SelectItem>
                                            <SelectItem value="sendmail">Sendmail</SelectItem>
                                        </>
                                    )}
                                    {config.clave === 'mail_encryption' && (
                                        <>
                                            <SelectItem value="tls">TLS</SelectItem>
                                            <SelectItem value="ssl">SSL</SelectItem>
                                            <SelectItem value="null">Sin cifrado</SelectItem>
                                        </>
                                    )}
                                </SelectContent>
                            </Select>
                            {hasChanges && (
                                <Button 
                                    size="sm" 
                                    variant="ghost"
                                    onClick={() => saveConfiguration(config.clave)}
                                    disabled={loading}
                                >
                                    <Save className="h-4 w-4" />
                                </Button>
                            )}
                        </div>
                        <p className="text-sm text-muted-foreground">{config.clave}</p>
                    </div>
                );

            case 'password':
                return (
                    <div className="space-y-2">
                        <Label htmlFor={config.clave}>{config.descripcion}</Label>
                        <div className="flex gap-2">
                            <Input
                                id={config.clave}
                                type="password"
                                value={currentValue}
                                onChange={(e) => handleConfigChange(config.clave, e.target.value)}
                                className={hasChanges ? 'border-orange-500' : ''}
                                placeholder="••••••••"
                            />
                            {hasChanges && (
                                <Button 
                                    size="sm" 
                                    variant="ghost"
                                    onClick={() => saveConfiguration(config.clave)}
                                    disabled={loading}
                                >
                                    <Save className="h-4 w-4" />
                                </Button>
                            )}
                        </div>
                        <p className="text-sm text-muted-foreground">{config.clave}</p>
                    </div>
                );

            default:
                return (
                    <div className="space-y-2">
                        <Label htmlFor={config.clave}>{config.descripcion}</Label>
                        <div className="flex gap-2">
                            <Input
                                id={config.clave}
                                value={currentValue}
                                onChange={(e) => handleConfigChange(config.clave, e.target.value)}
                                className={hasChanges ? 'border-orange-500' : ''}
                            />
                            {hasChanges && (
                                <Button 
                                    size="sm" 
                                    variant="ghost"
                                    onClick={() => saveConfiguration(config.clave)}
                                    disabled={loading}
                                >
                                    <Save className="h-4 w-4" />
                                </Button>
                            )}
                        </div>
                        <p className="text-sm text-muted-foreground">{config.clave}</p>
                    </div>
                );
        }
    };

    const getCategoryIcon = (category: string) => {
        switch (category) {
            case 'sistema': return <Settings className="h-5 w-5" />;
            case 'branding': return <Palette className="h-5 w-5" />;
            case 'email': return <Mail className="h-5 w-5" />;
            case 'sms': return <Smartphone className="h-5 w-5" />;
            case 'seguridad': return <Shield className="h-5 w-5" />;
            case 'notificaciones': return <Bell className="h-5 w-5" />;
            default: return <Settings className="h-5 w-5" />;
        }
    };

    const getCategoryName = (category: string) => {
        switch (category) {
            case 'sistema': return 'Sistema';
            case 'branding': return 'Branding';
            case 'email': return 'Email';
            case 'sms': return 'SMS';
            case 'seguridad': return 'Seguridad';
            case 'notificaciones': return 'Notificaciones';
            default: return category;
        }
    };

    return (
        <AppSidebarLayout title="Configuración del Sistema">
            <Head title="Configuración del Sistema" />
            
            <div className="space-y-6">
                {/* Header con estadísticas */}
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <h2 className="text-3xl font-bold tracking-tight">Configuración del Sistema</h2>
                        <p className="text-muted-foreground">
                            Gestiona la configuración avanzada, branding y personalización del sistema
                        </p>
                    </div>
                    <div className="flex gap-2">
                        {Object.keys(unsavedChanges).length > 0 && (
                            <Button onClick={saveAllChanges} disabled={loading}>
                                <Save className="mr-2 h-4 w-4" />
                                Guardar Todo ({Object.keys(unsavedChanges).length})
                            </Button>
                        )}
                        <Button variant="outline" onClick={() => router.reload()}>
                            <RefreshCw className="mr-2 h-4 w-4" />
                            Actualizar
                        </Button>
                    </div>
                </div>

                {/* Estadísticas generales */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Configuraciones</CardTitle>
                            <Settings className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.configuraciones_total}</div>
                            <p className="text-xs text-muted-foreground">
                                {stats.configuraciones_activas} activas
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Usuarios</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.usuarios_total}</div>
                            <p className="text-xs text-muted-foreground">
                                {stats.roles_total} roles configurados
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Cache</CardTitle>
                            <Database className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatBytes(stats.cache_size)}</div>
                            <p className="text-xs text-muted-foreground">
                                Tamaño en memoria
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Storage</CardTitle>
                            <Database className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatBytes(stats.storage_size)}</div>
                            <p className="text-xs text-muted-foreground">
                                Archivos almacenados
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Panel de configuraciones */}
                <Card>
                    <CardHeader>
                        <CardTitle>Configuraciones por Categoría</CardTitle>
                        <CardDescription>
                            Ajusta las configuraciones del sistema organizadas por categorías
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Tabs value={selectedTab} onValueChange={setSelectedTab}>
                            <TabsList className="grid w-full grid-cols-6">
                                {Object.keys(cats).map((category) => (
                                    <TabsTrigger key={category} value={category} className="flex items-center gap-2">
                                        {getCategoryIcon(category)}
                                        <span className="hidden sm:inline">{getCategoryName(category)}</span>
                                    </TabsTrigger>
                                ))}
                            </TabsList>

                            {Object.entries(cats).map(([category, configs]) => (
                                <TabsContent key={category} value={category} className="space-y-4 mt-6">
                                    <div className="grid gap-6 md:grid-cols-1 lg:grid-cols-2">
                                        {configs.map((config) => (
                                            <Card key={config.clave}>
                                                <CardContent className="pt-6">
                                                    {renderConfigInput(config)}
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                    
                                    {configs.length === 0 && (
                                        <div className="text-center py-8 text-muted-foreground">
                                            No hay configuraciones disponibles en esta categoría
                                        </div>
                                    )}
                                </TabsContent>
                            ))}
                        </Tabs>
                    </CardContent>
                </Card>

                {/* Acciones rápidas */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card className="cursor-pointer hover:bg-muted/50" onClick={() => router.get(route('admin.configuracion.branding'))}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Branding</CardTitle>
                            <Palette className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-xs text-muted-foreground">
                                Personaliza logos, colores y temas
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="cursor-pointer hover:bg-muted/50" onClick={() => router.get(route('admin.configuracion.roles'))}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Roles</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-xs text-muted-foreground">
                                Configuraciones específicas por rol
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="cursor-pointer hover:bg-muted/50" onClick={() => router.get(route('admin.configuracion.mantenimiento'))}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Mantenimiento</CardTitle>
                            <Wrench className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-xs text-muted-foreground">
                                Herramientas del sistema y optimización
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="cursor-pointer hover:bg-muted/50" onClick={exportConfig}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Exportar</CardTitle>
                            <Download className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-xs text-muted-foreground">
                                Descargar configuraciones en JSON
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppSidebarLayout>
    );
}
