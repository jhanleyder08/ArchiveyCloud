import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Users, Plus, Search, Filter, Edit, Trash2, UserCheck, UserX, Shield, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useState, useEffect, useRef } from 'react';
import { usePermissions } from '@/hooks/usePermissions';

const breadcrumbItems = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Gestión de usuarios', href: '/admin/users' },
];

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    active: boolean;
    created_at: string;
    updated_at: string;
    role_id: number | null;
    role?: {
        id: number;
        name: string;
    } | null;
}

interface PaginatedUsers {
    data: User[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    meta?: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
    current_page?: number;
    from?: number;
    last_page?: number;
    per_page?: number;
    to?: number;
    total?: number;
}

interface Stats {
    total: number;
    active: number;
    pending: number;
    without_role: number;
    deleted: number; // Usuarios eliminados (soft delete)
}

interface Role {
    id: number;
    name: string;
    description: string;
}

interface Props {
    users: PaginatedUsers;
    stats: Stats;
    roles: Role[];
    filters: {
        search?: string;
        status?: string;
    };
}

export default function AdminUsers({ users, stats, roles, filters }: Props) {
    const { flash } = usePage<{ flash: { success?: string, error?: string } }>().props;
    const permissions = usePermissions();
    const isFirstRender = useRef(true);
    
    const [search, setSearch] = useState(filters.search || '');
    const [showDeleteModal, setShowDeleteModal] = useState<User | null>(null);
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState<User | null>(null);
    const [createForm, setCreateForm] = useState({ name: '', email: '', role_id: '', password: '', password_confirmation: '', verify_email: false });
    const [createErrors, setCreateErrors] = useState<Record<string, string>>({});
    const [editForm, setEditForm] = useState({ name: '', email: '', role_id: '', active: true });
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    // Auto-submit search with debounce - skip first render to avoid unnecessary reload
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        
        const timeoutId = setTimeout(() => {
            router.get('/admin/users', {
                search: searchQuery || undefined,
                status: statusFilter || undefined
            }, {
                preserveState: false, // Importante: no preservar estado para obtener datos frescos
                replace: true,
            });
        }, 500);

        return () => clearTimeout(timeoutId);
    }, [searchQuery, statusFilter]);

    // Debug: Monitorear cuando se abre el modal de edición (solo en desarrollo)
    useEffect(() => {
        if (showEditModal && import.meta.env.DEV) {
            console.log('Modal de edición abierto para usuario:', showEditModal);
            console.log('Estado inicial de editForm:', editForm);
        }
    }, [showEditModal]);

    const handleToggleStatus = (user: User) => {
        router.patch(`/admin/users/${user.id}/toggle-status`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['users', 'stats'] });
            }
        });
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-ES');
    };

    const getUserStatus = (user: User) => {
        if (!user.active) return { text: 'Inactivo', color: 'bg-red-100 text-red-800' };
        if (!user.email_verified_at) return { text: 'Pendiente', color: 'bg-yellow-100 text-yellow-800' };
        return { text: 'Activo', color: 'bg-green-100 text-green-800' };
    };

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title="Gestión de Usuarios" />

            <div className="space-y-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        {flash.error}
                    </div>
                )}

                {/* Header */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <Users className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">Gestión de Usuarios</h1>
                    </div>

                    <div className="flex gap-2">
                        {/* Botón Gestionar Roles - Solo para Super Administrador */}
                        {permissions.isSuperAdmin() && (
                            <Link href="/admin/roles">
                                <Button variant="outline" className="border-[#2a3d83] text-[#2a3d83] hover:bg-blue-50">
                                    <Shield className="h-4 w-4 mr-2" />
                                    Gestionar Roles
                                </Button>
                            </Link>
                        )}

                        <Button 
                            onClick={() => setShowCreateModal(true)}
                            className="flex items-center gap-2 px-4 py-2 bg-[#2a3d83] text-white rounded-lg hover:bg-[#1e2b5f] transition-colors"
                        >
                            <Plus className="h-4 w-4" />
                            Nuevo Usuario
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total Usuarios</p>
                                <p className="text-2xl font-semibold text-gray-900">{stats.total}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <Users className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Usuarios Activos</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{stats.active}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <div className="h-6 w-6 bg-[#2a3d83] rounded-full flex items-center justify-center">
                                    <div className="h-2 w-2 bg-white rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Pendientes</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{stats.pending}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <div className="h-6 w-6 bg-[#2a3d83] rounded-full flex items-center justify-center">
                                    <div className="h-2 w-2 bg-white rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {stats.without_role > 0 && (
                        <div className="bg-white rounded-lg border border-red-200 p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-red-600 font-medium">⚠️ Sin Rol</p>
                                    <p className="text-2xl font-semibold text-red-700">{stats.without_role}</p>
                                </div>
                                <div className="p-3 bg-red-100 rounded-full">
                                    <AlertCircle className="h-6 w-6 text-red-600" />
                                </div>
                            </div>
                        </div>
                    )}

                    {stats.deleted > 0 && (
                        <div className="bg-white rounded-lg border border-gray-200 p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Eliminados</p>
                                    <p className="text-2xl font-semibold text-gray-700">{stats.deleted}</p>
                                    <p className="text-xs text-gray-500 mt-1">Sus correos pueden reutilizarse</p>
                                </div>
                                <div className="p-3 bg-gray-100 rounded-full">
                                    <Trash2 className="h-6 w-6 text-gray-600" />
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Search and Filters */}
                <div className="bg-white rounded-lg border p-6">
                    <div className="flex flex-col sm:flex-row gap-4 items-center justify-between">
                        <div className="flex items-center gap-4 w-full sm:w-auto">
                            <div className="relative flex-1 sm:w-80">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    placeholder="Buscar usuarios..."
                                    className="pl-10"
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Select value={statusFilter || "all"} onValueChange={(value) => setStatusFilter(value === "all" ? "" : value)}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="active">Activos</SelectItem>
                                    <SelectItem value="inactive">Inactivos</SelectItem>
                                    <SelectItem value="pending">Pendientes</SelectItem>
                                    {stats.without_role > 0 && (
                                        <SelectItem value="without_role" className="text-red-600 font-medium">
                                            ⚠️ Sin Rol ({stats.without_role})
                                        </SelectItem>
                                    )}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                {/* Users Table */}
                <div className="bg-white rounded-lg border overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b">
                                <tr>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Usuario</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Email</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Rol</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Estado</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Registro</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {users.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="py-8 px-6 text-center text-gray-500">
                                            No se encontraron usuarios.
                                        </td>
                                    </tr>
                                ) : (
                                    users.data.map((user) => {
                                        const status = getUserStatus(user);
                                        return (
                                            <tr key={user.id} className="hover:bg-gray-50 transition-colors">
                                                <td className="py-4 px-6">
                                                    <div className="flex items-center gap-3">
                                                        <div className="h-8 w-8 bg-[#2a3d83] rounded-full flex items-center justify-center text-white text-sm font-medium">
                                                            {user.name.charAt(0).toUpperCase()}
                                                        </div>
                                                        <span className="font-medium text-gray-900">{user.name}</span>
                                                    </div>
                                                </td>
                                                <td className="py-4 px-6 text-gray-600">{user.email}</td>
                                                <td className="py-4 px-6">
                                                    {user.role?.name ? (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-[#2a3d83]">
                                                            {user.role.name}
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                            ⚠️ Sin rol asignado
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="py-4 px-6">
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${status.color}`}>
                                                        {status.text}
                                                    </span>
                                                </td>
                                                <td className="py-4 px-6 text-gray-600">{formatDate(user.created_at)}</td>
                                                <td className="py-4 px-6">
                                                    <TooltipProvider>
                                                        <div className="flex items-center gap-2">
                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <Link 
                                                                        href={`/admin/users/${user.id}/edit`}
                                                                        className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors inline-flex"
                                                                    >
                                                                        <Edit className="h-4 w-4" />
                                                                    </Link>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>Editar usuario</p>
                                                                </TooltipContent>
                                                            </Tooltip>

                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <button
                                                                        onClick={() => handleToggleStatus(user)}
                                                                        className={`p-2 rounded-md transition-colors ${
                                                                            user.active 
                                                                                ? 'text-orange-600 hover:text-orange-800 hover:bg-orange-50' 
                                                                                : 'text-green-600 hover:text-green-800 hover:bg-green-50'
                                                                        }`}
                                                                    >
                                                                        {user.active ? (
                                                                            <UserX className="h-4 w-4" />
                                                                        ) : (
                                                                            <UserCheck className="h-4 w-4" />
                                                                        )}
                                                                    </button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>{user.active ? 'Desactivar usuario' : 'Activar usuario'}</p>
                                                                </TooltipContent>
                                                            </Tooltip>

                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <button
                                                                        onClick={() => setShowDeleteModal(user)}
                                                                        className="p-2 rounded-md text-red-600 hover:text-red-800 hover:bg-red-50 transition-colors"
                                                                    >
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>Eliminar usuario</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        </div>
                                                    </TooltipProvider>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Pagination */}
                {users.data.length > 0 && (
                    <div className="flex items-center justify-between bg-white border rounded-lg px-6 py-3">
                        <div className="text-sm text-gray-600">
                            Mostrando <span className="font-medium">{users.meta?.from || users.from || 0}</span> a{' '}
                            <span className="font-medium">{users.meta?.to || users.to || 0}</span> de{' '}
                            <span className="font-medium">{users.meta?.total || users.total || 0}</span> usuarios
                        </div>
                        <div className="flex items-center gap-2">
                            {users.links.map((link, index) => {
                                if (link.label.includes('Previous')) {
                                    return (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            preserveState
                                            className={`px-3 py-2 border border-gray-300 rounded-md text-sm font-medium ${
                                                link.url 
                                                    ? 'text-gray-700 hover:bg-gray-50' 
                                                    : 'text-gray-300 cursor-not-allowed'
                                            }`}
                                        >
                                            Anterior
                                        </Link>
                                    );
                                }
                                
                                if (link.label.includes('Next')) {
                                    return (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            preserveState
                                            className={`px-3 py-2 border border-gray-300 rounded-md text-sm font-medium ${
                                                link.url 
                                                    ? 'text-gray-700 hover:bg-gray-50' 
                                                    : 'text-gray-300 cursor-not-allowed'
                                            }`}
                                        >
                                            Siguiente
                                        </Link>
                                    );
                                }

                                // Number pages
                                if (!isNaN(Number(link.label))) {
                                    return (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            preserveState
                                            className={`px-3 py-2 rounded-md text-sm font-medium ${
                                                link.active
                                                    ? 'bg-[#2a3d83] text-white'
                                                    : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                                            }`}
                                        >
                                            {link.label}
                                        </Link>
                                    );
                                }

                                return null;
                            })}
                        </div>
                    </div>
                )}

                {/* Create User Dialog */}
                <Dialog open={showCreateModal} onOpenChange={setShowCreateModal}>
                    <DialogContent className="sm:max-w-[500px]">
                        <DialogHeader>
                            <DialogTitle>Registrar Nuevo Usuario</DialogTitle>
                            <DialogDescription>
                                Complete los datos para crear un nuevo usuario en el sistema.
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={(e) => {
                            e.preventDefault();
                            setCreateErrors({});
                            console.log('Enviando formulario:', createForm);
                            router.post('/admin/users', createForm, {
                                preserveScroll: true,
                                onSuccess: () => {
                                    console.log('Usuario creado exitosamente');
                                    setShowCreateModal(false);
                                    setCreateForm({ name: '', email: '', role_id: '', password: '', password_confirmation: '', verify_email: false });
                                    // Forzar recarga de datos frescos
                                    router.reload({ only: ['users', 'stats'] });
                                },
                                onError: (errors) => {
                                    console.log('Errores de validación:', errors);
                                    setCreateErrors(errors);
                                }
                            });
                        }}>
                            <div className="grid gap-4 py-4">
                                {/* Mostrar errores generales */}
                                {Object.keys(createErrors).length > 0 && (
                                    <Alert variant="destructive">
                                        <AlertDescription>
                                            {Object.values(createErrors).map((error, i) => (
                                                <div key={i}>{error}</div>
                                            ))}
                                        </AlertDescription>
                                    </Alert>
                                )}
                                <div className="grid gap-2">
                                    <Label htmlFor="create-name">Nombre completo</Label>
                                    <Input
                                        id="create-name"
                                        value={createForm.name}
                                        onChange={(e) => setCreateForm({ ...createForm, name: e.target.value })}
                                        placeholder="Nombre del usuario"
                                    />
                                    {createErrors.name && <InputError message={createErrors.name} />}
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="create-email">Correo electrónico</Label>
                                    <Input
                                        id="create-email"
                                        type="email"
                                        value={createForm.email}
                                        onChange={(e) => setCreateForm({ ...createForm, email: e.target.value })}
                                        placeholder="correo@ejemplo.com"
                                    />
                                    {createErrors.email && <InputError message={createErrors.email} />}
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="create-role">Rol</Label>
                                    <Select
                                        value={createForm.role_id}
                                        onValueChange={(value) => setCreateForm({ ...createForm, role_id: value })}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar rol" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {roles.map((role) => (
                                                <SelectItem key={role.id} value={role.id.toString()}>
                                                    {role.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {createErrors.role_id && <InputError message={createErrors.role_id} />}
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="create-password">Contraseña</Label>
                                    <Input
                                        id="create-password"
                                        type="password"
                                        value={createForm.password}
                                        onChange={(e) => setCreateForm({ ...createForm, password: e.target.value })}
                                        placeholder="••••••••"
                                    />
                                    {createErrors.password && <InputError message={createErrors.password} />}
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="create-password-confirm">Confirmar contraseña</Label>
                                    <Input
                                        id="create-password-confirm"
                                        type="password"
                                        value={createForm.password_confirmation}
                                        onChange={(e) => setCreateForm({ ...createForm, password_confirmation: e.target.value })}
                                        placeholder="••••••••"
                                    />
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="create-verify-email"
                                        checked={createForm.verify_email}
                                        onCheckedChange={(checked) => setCreateForm({ ...createForm, verify_email: checked as boolean })}
                                    />
                                    <Label htmlFor="create-verify-email" className="text-sm font-normal">
                                        Marcar email como verificado
                                    </Label>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setShowCreateModal(false)}>
                                    Cancelar
                                </Button>
                                <Button type="submit" className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                                    Crear Usuario
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Delete User Dialog */}
                <Dialog open={!!showDeleteModal} onOpenChange={(open) => !open && setShowDeleteModal(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Confirmar Eliminación</DialogTitle>
                            <DialogDescription>
                                ¿Estás seguro de que deseas eliminar al usuario <span className="font-semibold">{showDeleteModal?.name}</span>?
                                Esta acción no se puede deshacer.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setShowDeleteModal(null)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                variant="destructive"
                                onClick={() => {
                                    if (showDeleteModal) {
                                        router.delete(`/admin/users/${showDeleteModal.id}`, {
                                            preserveScroll: true,
                                            onSuccess: () => {
                                                setShowDeleteModal(null);
                                                router.reload({ only: ['users', 'stats'] });
                                            }
                                        });
                                    }
                                }}
                            >
                                Eliminar
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
