import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { toast } from 'sonner';
import { 
    Users, 
    ArrowLeft, 
    Save, 
    Settings, 
    Shield, 
    Mail, 
    Bell, 
    Smartphone,
    Eye,
    Clock,
    Database,
    RefreshCw
} from 'lucide-react';

interface User {
    id: number;
    name: string;
    email: string;
    role_id: number;
}

interface Role {
    id: number;
    name: string;
    description: string;
    users: User[];
}

interface ConfiguracionRol {
    id?: number;
    clave: string;
    valor: string;
    categoria: string;
    tipo: string;
    descripcion: string;
    activo: boolean;
}

interface Props {
    roles: Role[];
    configuraciones_roles: Record<number, Record<string, ConfiguracionRol>>;
}

export default function ConfiguracionRoles({ 
    roles = [], 
    configuraciones_roles = {} 
}: Props) {
    const [selectedRole, setSelectedRole] = useState<number | null>(roles[0]?.id || null);
    const [configChanges, setConfigChanges] = useState<Record<number, Record<string, any>>>({});
    const [loading, setLoading] = useState(false);
    const [showAddConfigDialog, setShowAddConfigDialog] = useState(false);
    const [newConfigForm, setNewConfigForm] = useState({
        clave: '',
        valor: '',
        descripcion: '',
        tipo: 'texto'
    });

    const selectedRoleData = roles.find(role => role.id === selectedRole);
    const selectedRoleConfigs = configuraciones_roles[selectedRole || 0] || {};

    const handleConfigChange = (roleId: number, clave: string, valor: any) => {
        setConfigChanges(prev => ({
            ...prev,
            [roleId]: {
                ...(prev[roleId] || {}),
                [clave]: valor
            }
        }));
    };

    const saveRoleConfigurations = async (roleId: number) => {
        const changes = configChanges[roleId];
        if (!changes || Object.keys(changes).length === 0) {
            toast.info('No hay cambios para guardar');
            return;
        }

        setLoading(true);
        try {
            const configuraciones = Object.entries(changes).map(([clave, valor]) => ({
                clave,
                valor: valor.toString(),
                activo: true
            }));

            await router.put(route('admin.configuracion.roles.update', roleId), {
                configuraciones
            });

            // Limpiar cambios guardados
            setConfigChanges(prev => ({
                ...prev,
                [roleId]: {}
            }));

            toast.success('Configuraciones de rol guardadas exitosamente');
        } catch (error) {
            console.error('Error saving role configurations:', error);
            toast.error('Error al guardar configuraciones de rol');
        } finally {
            setLoading(false);
        }
    };

    const addNewConfiguration = async () => {
        if (!selectedRole || !newConfigForm.clave || !newConfigForm.valor) {
            toast.error('Complete todos los campos requeridos');
            return;
        }

        try {
            handleConfigChange(selectedRole, newConfigForm.clave, newConfigForm.valor);
            
            setNewConfigForm({
                clave: '',
                valor: '',
                descripcion: '',
                tipo: 'texto'
            });
            setShowAddConfigDialog(false);
            
            toast.success('Configuración agregada. Recuerda guardar los cambios.');
        } catch (error) {
            console.error('Error adding configuration:', error);
            toast.error('Error al agregar configuración');
        }
    };

    const getConfigValue = (roleId: number, clave: string) => {
        const changes = configChanges[roleId];
        if (changes && changes[clave] !== undefined) {
            return changes[clave];
        }
        const config = configuraciones_roles[roleId]?.[clave];
        return config?.valor || '';
    };

    const hasChanges = (roleId: number) => {
        const changes = configChanges[roleId];
        return changes && Object.keys(changes).length > 0;
    };

    const getDefaultConfigsForRole = (roleName: string) => {
        const defaults = {
            'Super Administrador': [
                { clave: 'max_documents_per_day', valor: '1000', descripcion: 'Máximo documentos por día', tipo: 'numero' },
                { clave: 'can_delete_documents', valor: 'true', descripcion: 'Puede eliminar documentos', tipo: 'boolean' },
                { clave: 'can_modify_settings', valor: 'true', descripcion: 'Puede modificar configuraciones', tipo: 'boolean' },
                { clave: 'email_notifications_all', valor: 'true', descripcion: 'Recibir todas las notificaciones', tipo: 'boolean' },
            ],
            'Administrador SGDEA': [
                { clave: 'max_documents_per_day', valor: '500', descripcion: 'Máximo documentos por día', tipo: 'numero' },
                { clave: 'can_delete_documents', valor: 'true', descripcion: 'Puede eliminar documentos', tipo: 'boolean' },
                { clave: 'can_modify_settings', valor: 'false', descripcion: 'Puede modificar configuraciones', tipo: 'boolean' },
                { clave: 'email_notifications_important', valor: 'true', descripcion: 'Notificaciones importantes', tipo: 'boolean' },
            ],
            'Gestor Documental': [
                { clave: 'max_documents_per_day', valor: '200', descripcion: 'Máximo documentos por día', tipo: 'numero' },
                { clave: 'can_delete_documents', valor: 'false', descripcion: 'Puede eliminar documentos', tipo: 'boolean' },
                { clave: 'can_export_reports', valor: 'true', descripcion: 'Puede exportar reportes', tipo: 'boolean' },
                { clave: 'email_notifications_assigned', valor: 'true', descripcion: 'Notificaciones asignadas', tipo: 'boolean' },
            ]
        };

        return defaults[roleName as keyof typeof defaults] || [];
    };

    const loadDefaultConfigs = async (roleId: number, roleName: string) => {
        const defaultConfigs = getDefaultConfigsForRole(roleName);
        
        if (defaultConfigs.length === 0) {
            toast.info('No hay configuraciones predeterminadas para este rol');
            return;
        }

        defaultConfigs.forEach(config => {
            handleConfigChange(roleId, config.clave, config.valor);
        });

        toast.success(`${defaultConfigs.length} configuraciones predeterminadas cargadas`);
    };

    const renderConfigInput = (roleId: number, configKey: string, config?: ConfiguracionRol) => {
        const currentValue = getConfigValue(roleId, configKey);
        const tipo = config?.tipo || 'texto';
        const descripcion = config?.descripcion || configKey.replace(/_/g, ' ');

        switch (tipo) {
            case 'boolean':
                return (
                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label className="text-sm font-medium">{descripcion}</Label>
                            <p className="text-xs text-muted-foreground">{configKey}</p>
                        </div>
                        <Switch
                            checked={currentValue === 'true' || currentValue === true}
                            onCheckedChange={(checked) => 
                                handleConfigChange(roleId, configKey, checked ? 'true' : 'false')
                            }
                        />
                    </div>
                );

            case 'numero':
                return (
                    <div className="space-y-2">
                        <Label className="text-sm font-medium">{descripcion}</Label>
                        <Input
                            type="number"
                            value={currentValue}
                            onChange={(e) => handleConfigChange(roleId, configKey, e.target.value)}
                            placeholder="0"
                        />
                        <p className="text-xs text-muted-foreground">{configKey}</p>
                    </div>
                );

            default:
                return (
                    <div className="space-y-2">
                        <Label className="text-sm font-medium">{descripcion}</Label>
                        <Input
                            value={currentValue}
                            onChange={(e) => handleConfigChange(roleId, configKey, e.target.value)}
                            placeholder="Valor"
                        />
                        <p className="text-xs text-muted-foreground">{configKey}</p>
                    </div>
                );
        }
    };

    const getRoleIcon = (roleName: string) => {
        if (roleName.includes('Super') || roleName.includes('Administrador')) {
            return <Shield className="h-4 w-4" />;
        }
        return <Users className="h-4 w-4" />;
    };

    const getRoleBadgeColor = (roleName: string) => {
        if (roleName.includes('Super')) return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        if (roleName.includes('Administrador')) return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        if (roleName.includes('Gestor')) return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    };

    return (
        <AppSidebarLayout title="Configuración por Roles">
            <Head title="Configuración por Roles" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" onClick={() => router.get(route('admin.configuracion.index'))}>
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Volver
                        </Button>
                        <div className="space-y-1">
                            <h2 className="text-3xl font-bold tracking-tight">Configuración por Roles</h2>
                            <p className="text-muted-foreground">
                                Define configuraciones específicas para cada rol del sistema
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        {selectedRole && hasChanges(selectedRole) && (
                            <Button onClick={() => saveRoleConfigurations(selectedRole)} disabled={loading}>
                                <Save className="mr-2 h-4 w-4" />
                                Guardar Cambios
                            </Button>
                        )}
                        <Button variant="outline" onClick={() => router.reload()}>
                            <RefreshCw className="mr-2 h-4 w-4" />
                            Actualizar
                        </Button>
                    </div>
                </div>

                {/* Resumen de Roles */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {roles.map(role => (
                        <Card 
                            key={role.id} 
                            className={`cursor-pointer transition-all ${selectedRole === role.id ? 'ring-2 ring-primary' : 'hover:bg-muted/50'}`}
                            onClick={() => setSelectedRole(role.id)}
                        >
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium flex items-center gap-2">
                                    {getRoleIcon(role.name)}
                                    {role.name}
                                </CardTitle>
                                <Badge className={getRoleBadgeColor(role.name)}>
                                    {role.users?.length || 0} usuarios
                                </Badge>
                            </CardHeader>
                            <CardContent>
                                <p className="text-xs text-muted-foreground mb-2">{role.description}</p>
                                <div className="flex items-center gap-2">
                                    <Settings className="h-3 w-3 text-muted-foreground" />
                                    <span className="text-xs text-muted-foreground">
                                        {Object.keys(configuraciones_roles[role.id] || {}).length} configuraciones
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Panel de Configuración del Rol Seleccionado */}
                {selectedRole && selectedRoleData && (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    {getRoleIcon(selectedRoleData.name)}
                                    <div>
                                        <CardTitle>Configuración: {selectedRoleData.name}</CardTitle>
                                        <CardDescription>
                                            {selectedRoleData.description} • {selectedRoleData.users?.length || 0} usuarios asignados
                                        </CardDescription>
                                    </div>
                                </div>
                                <div className="flex gap-2">
                                    <Button 
                                        variant="outline" 
                                        size="sm"
                                        onClick={() => loadDefaultConfigs(selectedRole, selectedRoleData.name)}
                                    >
                                        <Settings className="mr-2 h-4 w-4" />
                                        Cargar Predeterminadas
                                    </Button>
                                    <Dialog open={showAddConfigDialog} onOpenChange={setShowAddConfigDialog}>
                                        <DialogTrigger asChild>
                                            <Button size="sm">
                                                Agregar Configuración
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>Nueva Configuración</DialogTitle>
                                                <DialogDescription>
                                                    Agrega una nueva configuración específica para el rol {selectedRoleData.name}
                                                </DialogDescription>
                                            </DialogHeader>
                                            <div className="space-y-4">
                                                <div className="space-y-2">
                                                    <Label>Clave de configuración</Label>
                                                    <Input
                                                        value={newConfigForm.clave}
                                                        onChange={(e) => setNewConfigForm(prev => ({ ...prev, clave: e.target.value }))}
                                                        placeholder="max_documents_per_day"
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label>Valor</Label>
                                                    <Input
                                                        value={newConfigForm.valor}
                                                        onChange={(e) => setNewConfigForm(prev => ({ ...prev, valor: e.target.value }))}
                                                        placeholder="100"
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label>Descripción</Label>
                                                    <Input
                                                        value={newConfigForm.descripcion}
                                                        onChange={(e) => setNewConfigForm(prev => ({ ...prev, descripcion: e.target.value }))}
                                                        placeholder="Descripción de la configuración"
                                                    />
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button variant="outline" onClick={() => setShowAddConfigDialog(false)}>
                                                    Cancelar
                                                </Button>
                                                <Button onClick={addNewConfiguration}>
                                                    Agregar
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {/* Usuarios del Rol */}
                            {selectedRoleData.users && selectedRoleData.users.length > 0 && (
                                <div className="mb-6">
                                    <Label className="text-sm font-medium mb-2 block">Usuarios con este rol</Label>
                                    <div className="flex flex-wrap gap-2">
                                        {selectedRoleData.users.map(user => (
                                            <Badge key={user.id} variant="secondary" className="flex items-center gap-1">
                                                <Users className="h-3 w-3" />
                                                {user.name}
                                            </Badge>
                                        ))}
                                    </div>
                                    <Separator className="mt-4" />
                                </div>
                            )}

                            {/* Configuraciones */}
                            <div className="space-y-6">
                                <div className="flex items-center justify-between">
                                    <Label className="text-lg font-medium">Configuraciones del Rol</Label>
                                    {hasChanges(selectedRole) && (
                                        <Badge variant="outline" className="bg-orange-50 text-orange-600 border-orange-200">
                                            Cambios sin guardar
                                        </Badge>
                                    )}
                                </div>

                                <div className="grid gap-4 md:grid-cols-1 lg:grid-cols-2">
                                    {/* Configuraciones existentes */}
                                    {Object.entries(selectedRoleConfigs).map(([clave, config]) => (
                                        <Card key={clave}>
                                            <CardContent className="pt-4">
                                                {renderConfigInput(selectedRole, clave, config)}
                                            </CardContent>
                                        </Card>
                                    ))}

                                    {/* Configuraciones pendientes (cambios no guardados) */}
                                    {configChanges[selectedRole] && 
                                        Object.entries(configChanges[selectedRole])
                                            .filter(([clave]) => !selectedRoleConfigs[clave])
                                            .map(([clave]) => (
                                                <Card key={clave} className="border-orange-200 bg-orange-50 dark:bg-orange-900/10">
                                                    <CardContent className="pt-4">
                                                        <Badge className="mb-2" variant="outline">Nueva</Badge>
                                                        {renderConfigInput(selectedRole, clave)}
                                                    </CardContent>
                                                </Card>
                                            ))
                                    }
                                </div>

                                {Object.keys(selectedRoleConfigs).length === 0 && 
                                 (!configChanges[selectedRole] || Object.keys(configChanges[selectedRole]).length === 0) && (
                                    <div className="text-center py-12">
                                        <Settings className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                                        <h3 className="text-lg font-medium mb-2">Sin configuraciones específicas</h3>
                                        <p className="text-muted-foreground mb-4">
                                            Este rol no tiene configuraciones específicas definidas.
                                        </p>
                                        <div className="flex gap-2 justify-center">
                                            <Button 
                                                variant="outline"
                                                onClick={() => loadDefaultConfigs(selectedRole, selectedRoleData.name)}
                                            >
                                                Cargar Configuraciones Predeterminadas
                                            </Button>
                                            <Button onClick={() => setShowAddConfigDialog(true)}>
                                                Crear Nueva Configuración
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {!selectedRole && (
                    <Card>
                        <CardContent className="text-center py-12">
                            <Users className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                            <h3 className="text-lg font-medium mb-2">Selecciona un rol</h3>
                            <p className="text-muted-foreground">
                                Selecciona un rol de arriba para configurar sus opciones específicas.
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppSidebarLayout>
    );
}
