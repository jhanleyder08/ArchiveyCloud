import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { Shield, Users, Check, X, Save, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Label } from '@/components/ui/label';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';

const breadcrumbItems = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Gestión de Roles', href: '/admin/roles' },
];

interface Permiso {
    id: number;
    nombre: string;
    descripcion: string;
    categoria: string;
    subcategoria?: string;
}

interface Role {
    id: number;
    name: string;
    description: string;
    nivel_jerarquico: number;
    activo: boolean;
    sistema: boolean;
    permisos_count: number;
    permisos: number[];
}

interface Props {
    roles: Role[];
    permisos: Record<string, Permiso[]>;
}

export default function RolesManagement({ roles, permisos }: Props) {
    const [hasChanges, setHasChanges] = useState(false);
    const [selectedRole, setSelectedRole] = useState<Role | null>(roles[0] || null);
    const [selectedPermisos, setSelectedPermisos] = useState<number[]>(
        selectedRole?.permisos || []
    );

    // Cuando cambia el rol seleccionado
    const handleRoleChange = (role: Role) => {
        if (hasChanges) {
            if (!confirm('Tienes cambios sin guardar. ¿Deseas continuar sin guardar?')) {
                return;
            }
        }
        setSelectedRole(role);
        setSelectedPermisos(role.permisos);
        setHasChanges(false);
    };

    // Toggle permiso individual
    const togglePermiso = (permisoId: number) => {
        if (selectedRole?.name === 'Super Administrador') {
            return; // No se puede modificar
        }

        const newPermisos = selectedPermisos.includes(permisoId)
            ? selectedPermisos.filter(id => id !== permisoId)
            : [...selectedPermisos, permisoId];
        
        setSelectedPermisos(newPermisos);
        setHasChanges(true);
    };

    // Seleccionar todos los permisos de una categoría
    const toggleCategoria = (categoria: string) => {
        if (selectedRole?.name === 'Super Administrador') {
            return;
        }

        const permisosCategoria = permisos[categoria].map(p => p.id);
        const todosSeleccionados = permisosCategoria.every(id => 
            selectedPermisos.includes(id)
        );

        let newPermisos: number[];
        if (todosSeleccionados) {
            // Deseleccionar todos
            newPermisos = selectedPermisos.filter(id => !permisosCategoria.includes(id));
        } else {
            // Seleccionar todos
            newPermisos = [...new Set([...selectedPermisos, ...permisosCategoria])];
        }

        setSelectedPermisos(newPermisos);
        setHasChanges(true);
    };

    // Guardar cambios
    const handleSave = () => {
        if (!selectedRole) return;

        router.put(`/admin/roles/${selectedRole.id}/permissions`, {
            permisos: selectedPermisos,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setHasChanges(false);
            },
        });
    };

    // Verificar si todos los permisos de una categoría están seleccionados
    const isCategoriaTotalmenteSeleccionada = (categoria: string) => {
        const permisosCategoria = permisos[categoria].map(p => p.id);
        return permisosCategoria.every(id => selectedPermisos.includes(id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title="Gestión de Roles y Permisos" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-2">
                        <Shield className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Gestión de Roles y Permisos
                        </h1>
                    </div>
                    {hasChanges && (
                        <Button 
                            onClick={handleSave}
                            className="bg-[#2a3d83] hover:bg-[#1e2b5f]"
                        >
                            <Save className="h-4 w-4 mr-2" />
                            Guardar Cambios
                        </Button>
                    )}
                </div>

                {/* Alerta Solo Super Admin */}
                <Alert className="border-blue-200 bg-blue-50">
                    <AlertCircle className="h-4 w-4 text-blue-600" />
                    <AlertDescription className="text-blue-800">
                        <strong>Atención:</strong> Solo el Super Administrador puede modificar los permisos de los roles. 
                        Los cambios afectarán a todos los usuarios con ese rol.
                    </AlertDescription>
                </Alert>

                <div className="grid grid-cols-12 gap-6">
                    {/* Listado de Roles */}
                    <div className="col-span-12 lg:col-span-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Roles del Sistema</CardTitle>
                                <CardDescription>
                                    Selecciona un rol para gestionar sus permisos
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {roles.map((role) => (
                                    <button
                                        key={role.id}
                                        onClick={() => handleRoleChange(role)}
                                        className={`w-full text-left p-4 rounded-lg border-2 transition-all ${
                                            selectedRole?.id === role.id
                                                ? 'border-[#2a3d83] bg-blue-50'
                                                : 'border-gray-200 hover:border-gray-300'
                                        }`}
                                    >
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2">
                                                    <h3 className="font-semibold text-gray-900">
                                                        {role.name}
                                                    </h3>
                                                    {role.name === 'Super Administrador' && (
                                                        <Badge className="bg-yellow-100 text-yellow-800 hover:bg-yellow-100">
                                                            Protegido
                                                        </Badge>
                                                    )}
                                                </div>
                                                <p className="text-sm text-gray-600 mt-1">
                                                    {role.description}
                                                </p>
                                                <div className="flex items-center gap-4 mt-2">
                                                    <span className="text-xs text-gray-500">
                                                        Nivel {role.nivel_jerarquico}
                                                    </span>
                                                    <span className="text-xs text-gray-500">
                                                        {role.permisos_count} permisos
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                ))}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Gestión de Permisos */}
                    <div className="col-span-12 lg:col-span-8">
                        {selectedRole ? (
                            <Card>
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <CardTitle>Permisos de: {selectedRole.name}</CardTitle>
                                            <CardDescription>
                                                {selectedRole.name === 'Super Administrador' 
                                                    ? 'Este rol tiene todos los permisos y no puede ser modificado'
                                                    : 'Selecciona los permisos que tendrá este rol'}
                                            </CardDescription>
                                        </div>
                                        <Badge variant="outline" className="text-lg">
                                            {selectedPermisos.length} / {Object.values(permisos).flat().length}
                                        </Badge>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {selectedRole.name === 'Super Administrador' ? (
                                        <Alert className="border-yellow-200 bg-yellow-50">
                                            <Shield className="h-4 w-4 text-yellow-600" />
                                            <AlertDescription className="text-yellow-800">
                                                El rol Super Administrador tiene acceso completo al sistema y no puede ser modificado.
                                            </AlertDescription>
                                        </Alert>
                                    ) : (
                                        <Tabs defaultValue={Object.keys(permisos)[0]} className="w-full">
                                            <TabsList className="flex flex-wrap h-auto">
                                                {Object.keys(permisos).map((categoria) => (
                                                    <TabsTrigger key={categoria} value={categoria} className="capitalize">
                                                        {categoria}
                                                        <Badge variant="secondary" className="ml-2">
                                                            {permisos[categoria].filter(p => 
                                                                selectedPermisos.includes(p.id)
                                                            ).length}/{permisos[categoria].length}
                                                        </Badge>
                                                    </TabsTrigger>
                                                ))}
                                            </TabsList>

                                            {Object.entries(permisos).map(([categoria, permisosCategoria]) => (
                                                <TabsContent key={categoria} value={categoria} className="space-y-4 mt-4">
                                                    {/* Botón para seleccionar todos de la categoría */}
                                                    <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                        <Label className="font-semibold capitalize">
                                                            Todos los permisos de {categoria}
                                                        </Label>
                                                        <Checkbox
                                                            checked={isCategoriaTotalmenteSeleccionada(categoria)}
                                                            onCheckedChange={() => toggleCategoria(categoria)}
                                                        />
                                                    </div>

                                                    {/* Lista de permisos */}
                                                    <div className="space-y-2">
                                                        {permisosCategoria.map((permiso) => (
                                                            <div
                                                                key={permiso.id}
                                                                className="flex items-start justify-between p-3 border rounded-lg hover:bg-gray-50 transition-colors"
                                                            >
                                                                <div className="flex-1">
                                                                    <Label className="font-medium text-gray-900">
                                                                        {permiso.nombre}
                                                                    </Label>
                                                                    <p className="text-sm text-gray-600 mt-1">
                                                                        {permiso.descripcion}
                                                                    </p>
                                                                    {permiso.subcategoria && (
                                                                        <Badge variant="outline" className="mt-1">
                                                                            {permiso.subcategoria}
                                                                        </Badge>
                                                                    )}
                                                                </div>
                                                                <Checkbox
                                                                    checked={selectedPermisos.includes(permiso.id)}
                                                                    onCheckedChange={() => togglePermiso(permiso.id)}
                                                                    className="ml-4"
                                                                />
                                                            </div>
                                                        ))}
                                                    </div>
                                                </TabsContent>
                                            ))}
                                        </Tabs>
                                    )}
                                </CardContent>
                            </Card>
                        ) : (
                            <Card>
                                <CardContent className="flex items-center justify-center py-12">
                                    <div className="text-center">
                                        <Users className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                        <p className="text-gray-600">
                                            Selecciona un rol para gestionar sus permisos
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>

                {/* Footer con indicador de cambios */}
                {hasChanges && (
                    <Alert className="border-amber-200 bg-amber-50">
                        <AlertCircle className="h-4 w-4 text-amber-600" />
                        <AlertDescription className="text-amber-800 flex items-center justify-between">
                            <span>Tienes cambios sin guardar en el rol <strong>{selectedRole?.name}</strong></span>
                            <div className="flex gap-2">
                                <Button 
                                    variant="outline" 
                                    size="sm"
                                    onClick={() => {
                                        if (selectedRole) {
                                            setSelectedPermisos(selectedRole.permisos);
                                            setHasChanges(false);
                                        }
                                    }}
                                >
                                    Cancelar
                                </Button>
                                <Button 
                                    size="sm"
                                    onClick={handleSave}
                                    className="bg-[#2a3d83] hover:bg-[#1e2b5f]"
                                >
                                    <Save className="h-4 w-4 mr-2" />
                                    Guardar
                                </Button>
                            </div>
                        </AlertDescription>
                    </Alert>
                )}
            </div>
        </AppLayout>
    );
}
