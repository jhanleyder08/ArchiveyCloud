import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Users, Plus, Search, Filter, Edit, Trash2, UserCheck, UserX } from 'lucide-react';
import { useState } from 'react';

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
    role: {
        id: number;
        name: string;
    };
}

interface PaginatedUsers {
    data: User[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    // Laravel pagination can return meta object or properties directly
    meta?: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
    // Direct properties from Laravel paginator
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
}

interface Props {
    users: PaginatedUsers;
    stats: Stats;
    filters: {
        search?: string;
        status?: string;
    };
}

export default function AdminUsers({ users, stats, filters }: Props) {
    const { flash } = usePage<{flash: {success?: string, error?: string}}>().props;
    const [search, setSearch] = useState(filters.search || '');
    const [showDeleteDialog, setShowDeleteDialog] = useState<User | null>(null);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/users', { search, status: filters.status }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleToggleStatus = (user: User) => {
        router.patch(`/admin/users/${user.id}/toggle-status`, {}, {
            preserveState: true,
        });
    };

    const handleDelete = (user: User) => {
        router.delete(`/admin/users/${user.id}`, {
            preserveState: true,
        });
        setShowDeleteDialog(null);
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
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Users className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Gestión de Usuarios
                        </h1>
                    </div>
                    <Link
                        href="/admin/users/create"
                        className="flex items-center gap-2 px-4 py-2 bg-[#2a3d83] text-white rounded-lg hover:bg-[#1e2b5f] transition-colors"
                    >
                        <Plus className="h-4 w-4" />
                        Nuevo Usuario
                    </Link>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                                <p className="text-2xl font-semibold text-green-600">{stats.active}</p>
                            </div>
                            <div className="p-3 bg-green-100 rounded-full">
                                <div className="h-6 w-6 bg-green-500 rounded-full flex items-center justify-center">
                                    <div className="h-2 w-2 bg-white rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Pendientes</p>
                                <p className="text-2xl font-semibold text-yellow-600">{stats.pending}</p>
                            </div>
                            <div className="p-3 bg-yellow-100 rounded-full">
                                <div className="h-6 w-6 bg-yellow-500 rounded-full flex items-center justify-center">
                                    <div className="h-2 w-2 bg-white rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Search and Filters */}
                <div className="bg-white rounded-lg border p-6">
                    <form onSubmit={handleSearch} className="flex flex-col sm:flex-row gap-4 items-center justify-between">
                        <div className="flex items-center gap-4 w-full sm:w-auto">
                            <div className="relative flex-1 sm:w-80">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Buscar usuarios..."
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2a3d83] focus:border-[#2a3d83]"
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <select
                                value={filters.status || ''}
                                onChange={(e) => router.get('/admin/users', { search: filters.search, status: e.target.value || undefined }, { preserveState: true, replace: true })}
                                className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2a3d83] focus:border-[#2a3d83]"
                            >
                                <option value="">Todos los estados</option>
                                <option value="active">Activos</option>
                                <option value="inactive">Inactivos</option>
                                <option value="pending">Pendientes</option>
                            </select>
                            <button
                                type="submit"
                                className="flex items-center gap-2 px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                <Search className="h-4 w-4" />
                                Buscar
                            </button>
                        </div>
                    </form>
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
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-[#2a3d83]">
                                                        {user.role.name}
                                                    </span>
                                                </td>
                                                <td className="py-4 px-6">
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${status.color}`}>
                                                        {status.text}
                                                    </span>
                                                </td>
                                                <td className="py-4 px-6 text-gray-600">{formatDate(user.created_at)}</td>
                                                <td className="py-4 px-6">
                                                    <div className="flex items-center gap-3">
                                                        <Link
                                                            href={`/admin/users/${user.id}/edit`}
                                                            className="inline-flex items-center gap-1 text-[#2a3d83] hover:text-[#1e2b5f] text-sm font-medium"
                                                        >
                                                            <Edit className="h-3 w-3" />
                                                            Editar
                                                        </Link>
                                                        <button
                                                            onClick={() => handleToggleStatus(user)}
                                                            className={`inline-flex items-center gap-1 text-sm font-medium ${
                                                                user.active 
                                                                    ? 'text-orange-600 hover:text-orange-800' 
                                                                    : 'text-green-600 hover:text-green-800'
                                                            }`}
                                                        >
                                                            {user.active ? (
                                                                <>
                                                                    <UserX className="h-3 w-3" />
                                                                    Desactivar
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <UserCheck className="h-3 w-3" />
                                                                    Activar
                                                                </>
                                                            )}
                                                        </button>
                                                        <button
                                                            onClick={() => setShowDeleteDialog(user)}
                                                            className="inline-flex items-center gap-1 text-red-600 hover:text-red-800 text-sm font-medium"
                                                        >
                                                            <Trash2 className="h-3 w-3" />
                                                            Eliminar
                                                        </button>
                                                    </div>
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

                {/* Delete Confirmation Dialog */}
                {showDeleteDialog && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="p-2 bg-red-100 rounded-full">
                                    <Trash2 className="h-5 w-5 text-red-600" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900">
                                    Confirmar eliminación
                                </h3>
                            </div>
                            <p className="text-gray-600 mb-6">
                                ¿Estás seguro de que deseas eliminar al usuario{' '}
                                <span className="font-medium">{showDeleteDialog.name}</span>?{' '}
                                Esta acción no se puede deshacer.
                            </p>
                            <div className="flex items-center gap-3 justify-end">
                                <button
                                    onClick={() => setShowDeleteDialog(null)}
                                    className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                                >
                                    Cancelar
                                </button>
                                <button
                                    onClick={() => handleDelete(showDeleteDialog)}
                                    className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
