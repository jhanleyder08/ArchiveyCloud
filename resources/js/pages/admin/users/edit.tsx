import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, User, Mail, Lock, Shield, Eye, EyeOff, UserCheck, UserX } from 'lucide-react';
import { useState } from 'react';

const breadcrumbItems = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Gestión de usuarios', href: '/admin/users' },
    { title: 'Editar usuario', href: '#' },
];

interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    active: boolean;
    created_at: string;
    role: Role;
}

interface Props {
    user: User;
    roles: Role[];
}

export default function EditUser({ user, roles }: Props) {
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirm, setShowPasswordConfirm] = useState(false);
    
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        role_id: user.role?.id?.toString() || '',
        active: user.active,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/users/${user.id}`, {
            preserveScroll: true,
        });
    };

    const getUserStatus = () => {
        if (!user.active) return { text: 'Inactivo', color: 'bg-red-100 text-red-800', icon: UserX };
        if (!user.email_verified_at) return { text: 'Pendiente', color: 'bg-yellow-100 text-yellow-800', icon: User };
        return { text: 'Activo', color: 'bg-green-100 text-green-800', icon: UserCheck };
    };

    const status = getUserStatus();
    const StatusIcon = status.icon;

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title={`Editar Usuario - ${user.name}`} />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Link
                        href="/admin/users"
                        className="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Volver a usuarios
                    </Link>
                </div>

                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <User className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Editar Usuario
                        </h1>
                    </div>
                    <div className="flex items-center gap-2">
                        <StatusIcon className="h-4 w-4" />
                        <span className={`px-2.5 py-0.5 rounded-full text-xs font-medium ${status.color}`}>
                            {status.text}
                        </span>
                    </div>
                </div>

                {/* User Info Card */}
                <div className="bg-gray-50 rounded-lg p-4 border">
                    <div className="flex items-center gap-4">
                        <div className="h-12 w-12 bg-[#2a3d83] rounded-full flex items-center justify-center text-white text-lg font-medium">
                            {user.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h3 className="font-medium text-gray-900">{user.name}</h3>
                            <p className="text-sm text-gray-600">{user.email}</p>
                            <p className="text-xs text-gray-500">
                                Registrado: {new Date(user.created_at).toLocaleDateString('es-ES')}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Form */}
                <div className="bg-white rounded-lg border shadow-sm">
                    <form onSubmit={handleSubmit} className="p-6 space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Nombre */}
                            <div className="space-y-2">
                                <label htmlFor="name" className="text-sm font-medium text-gray-700">
                                    Nombre completo
                                </label>
                                <div className="relative">
                                    <User className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <input
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className={`w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#2a3d83] focus:border-[#2a3d83] ${
                                            errors.name ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                        placeholder="Ingresa el nombre completo"
                                        required
                                    />
                                </div>
                                {errors.name && (
                                    <p className="text-sm text-red-600">{errors.name}</p>
                                )}
                            </div>

                            {/* Email */}
                            <div className="space-y-2">
                                <label htmlFor="email" className="text-sm font-medium text-gray-700">
                                    Correo electrónico
                                </label>
                                <div className="relative">
                                    <Mail className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        className={`w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#2a3d83] focus:border-[#2a3d83] ${
                                            errors.email ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                        placeholder="usuario@archiveycloud.com"
                                        required
                                    />
                                </div>
                                {errors.email && (
                                    <p className="text-sm text-red-600">{errors.email}</p>
                                )}
                            </div>

                            {/* Nueva Contraseña */}
                            <div className="space-y-2">
                                <label htmlFor="password" className="text-sm font-medium text-gray-700">
                                    Nueva contraseña
                                    <span className="text-gray-500 font-normal"> (opcional)</span>
                                </label>
                                <div className="relative">
                                    <Lock className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <input
                                        id="password"
                                        type={showPassword ? "text" : "password"}
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        className={`w-full pl-10 pr-10 py-2 border rounded-lg focus:ring-2 focus:ring-[#2a3d83] focus:border-[#2a3d83] ${
                                            errors.password ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                        placeholder="Dejar vacío para mantener actual"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                    >
                                        {showPassword ? (
                                            <EyeOff className="h-4 w-4" />
                                        ) : (
                                            <Eye className="h-4 w-4" />
                                        )}
                                    </button>
                                </div>
                                {errors.password && (
                                    <p className="text-sm text-red-600">{errors.password}</p>
                                )}
                            </div>

                            {/* Confirmar Nueva Contraseña */}
                            <div className="space-y-2">
                                <label htmlFor="password_confirmation" className="text-sm font-medium text-gray-700">
                                    Confirmar nueva contraseña
                                </label>
                                <div className="relative">
                                    <Lock className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <input
                                        id="password_confirmation"
                                        type={showPasswordConfirm ? "text" : "password"}
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                        className={`w-full pl-10 pr-10 py-2 border rounded-lg focus:ring-2 focus:ring-[#2a3d83] focus:border-[#2a3d83] ${
                                            errors.password_confirmation ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                        placeholder="Confirma la nueva contraseña"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPasswordConfirm(!showPasswordConfirm)}
                                        className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                    >
                                        {showPasswordConfirm ? (
                                            <EyeOff className="h-4 w-4" />
                                        ) : (
                                            <Eye className="h-4 w-4" />
                                        )}
                                    </button>
                                </div>
                                {errors.password_confirmation && (
                                    <p className="text-sm text-red-600">{errors.password_confirmation}</p>
                                )}
                            </div>
                        </div>

                        {/* Rol y Estado */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Rol */}
                            <div className="space-y-2">
                                <label htmlFor="role_id" className="text-sm font-medium text-gray-700">
                                    Rol
                                </label>
                                <div className="relative">
                                    <Shield className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <select
                                        id="role_id"
                                        value={data.role_id}
                                        onChange={(e) => setData('role_id', e.target.value)}
                                        className={`w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#2a3d83] focus:border-[#2a3d83] ${
                                            errors.role_id ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                        required
                                    >
                                        <option value="">Seleccionar rol...</option>
                                        {roles.map((role) => (
                                            <option key={role.id} value={role.id.toString()}>
                                                {role.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                {errors.role_id && (
                                    <p className="text-sm text-red-600">{errors.role_id}</p>
                                )}
                            </div>

                            {/* Estado */}
                            <div className="space-y-2">
                                <label htmlFor="active" className="text-sm font-medium text-gray-700">
                                    Estado
                                </label>
                                <div className="flex items-center gap-4">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="active"
                                            checked={data.active === true}
                                            onChange={() => setData('active', true)}
                                            className="text-[#2a3d83] focus:ring-[#2a3d83]"
                                        />
                                        <UserCheck className="h-4 w-4 text-green-600" />
                                        <span className="text-sm text-gray-700">Activo</span>
                                    </label>
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="active"
                                            checked={data.active === false}
                                            onChange={() => setData('active', false)}
                                            className="text-red-600 focus:ring-red-600"
                                        />
                                        <UserX className="h-4 w-4 text-red-600" />
                                        <span className="text-sm text-gray-700">Inactivo</span>
                                    </label>
                                </div>
                                {errors.active && (
                                    <p className="text-sm text-red-600">{errors.active}</p>
                                )}
                            </div>
                        </div>

                        {/* Información adicional */}
                        <div className="bg-amber-50 border border-amber-200 rounded-lg p-4">
                            <div className="flex items-start gap-3">
                                <div className="p-1 bg-amber-100 rounded-full">
                                    <Shield className="h-4 w-4 text-amber-600" />
                                </div>
                                <div className="text-sm text-amber-800">
                                    <p className="font-medium mb-1">Importante:</p>
                                    <ul className="space-y-1 text-amber-700">
                                        <li>• Solo completa los campos que deseas cambiar</li>
                                        <li>• La contraseña actual se mantendrá si no especificas una nueva</li>
                                        <li>• Cambiar el estado a "Inactivo" impedirá al usuario iniciar sesión</li>
                                        <li>• Los cambios de rol se aplicarán en el próximo login</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center gap-3 justify-end pt-6 border-t">
                            <Link
                                href="/admin/users"
                                className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                            >
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className={`px-6 py-2 bg-[#2a3d83] text-white rounded-lg font-medium transition-colors ${
                                    processing 
                                        ? 'opacity-50 cursor-not-allowed' 
                                        : 'hover:bg-[#1e2b5f]'
                                }`}
                            >
                                {processing ? 'Guardando...' : 'Guardar Cambios'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
