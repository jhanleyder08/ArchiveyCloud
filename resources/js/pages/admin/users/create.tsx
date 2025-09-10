import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, User, Mail, Lock, Shield, Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';

const breadcrumbItems = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Gestión de usuarios', href: '/admin/users' },
    { title: 'Crear usuario', href: '/admin/users/create' },
];

interface Role {
    id: number;
    name: string;
}

interface Props {
    roles: Role[];
}

export default function CreateUser({ roles }: Props) {
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirm, setShowPasswordConfirm] = useState(false);
    
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role_id: 1,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/users');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title="Crear Usuario" />
            
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

                <div className="flex items-center gap-2">
                    <User className="h-6 w-6 text-[#2a3d83]" />
                    <h1 className="text-2xl font-semibold text-gray-900">
                        Crear Nuevo Usuario
                    </h1>
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

                            {/* Contraseña */}
                            <div className="space-y-2">
                                <label htmlFor="password" className="text-sm font-medium text-gray-700">
                                    Contraseña
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
                                        placeholder="Contraseña segura"
                                        required
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

                            {/* Confirmar Contraseña */}
                            <div className="space-y-2">
                                <label htmlFor="password_confirmation" className="text-sm font-medium text-gray-700">
                                    Confirmar contraseña
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
                                        placeholder="Confirma la contraseña"
                                        required
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
                                    onChange={(e) => setData('role_id', parseInt(e.target.value))}
                                    className={`w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#2a3d83] focus:border-[#2a3d83] ${
                                        errors.role_id ? 'border-red-300' : 'border-gray-300'
                                    }`}
                                    required
                                >
                                    {roles.map((role) => (
                                        <option key={role.id} value={role.id}>
                                            {role.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            {errors.role_id && (
                                <p className="text-sm text-red-600">{errors.role_id}</p>
                            )}
                        </div>

                        {/* Información adicional */}
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div className="flex items-start gap-3">
                                <div className="p-1 bg-blue-100 rounded-full">
                                    <Shield className="h-4 w-4 text-blue-600" />
                                </div>
                                <div className="text-sm text-blue-800">
                                    <p className="font-medium mb-1">Información importante:</p>
                                    <ul className="space-y-1 text-blue-700">
                                        <li>• El usuario será creado con estado activo</li>
                                        <li>• El email será verificado automáticamente</li>
                                        <li>• Se recomienda una contraseña de al menos 8 caracteres</li>
                                        <li>• El usuario podrá cambiar su contraseña al primer login</li>
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
                                {processing ? 'Creando...' : 'Crear Usuario'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
