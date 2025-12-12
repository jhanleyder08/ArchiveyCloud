import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, User, Mail, Shield, Calendar, Phone, Briefcase, Building2, FileText, Clock, CheckCircle, XCircle } from 'lucide-react';

const breadcrumbItems = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Gestión de usuarios', href: '/admin/users' },
    { title: 'Ver usuario', href: '#' },
];

interface Role {
    id: number;
    name: string;
    description?: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    active: boolean;
    created_at: string;
    updated_at: string;
    role: Role;
    // Campos adicionales
    documento_identidad?: string;
    tipo_documento?: string;
    telefono?: string;
    cargo?: string;
    dependencia?: string;
    fecha_ingreso?: string;
    fecha_vencimiento_cuenta?: string;
    ultimo_acceso?: string;
    estado_cuenta?: string;
}

interface Props {
    user: User;
}

export default function ShowUser({ user }: Props) {
    const getUserStatus = () => {
        if (!user.active) return { text: 'Inactivo', color: 'bg-red-100 text-red-800 border-red-200', icon: XCircle };
        if (!user.email_verified_at) return { text: 'Pendiente Verificación', color: 'bg-yellow-100 text-yellow-800 border-yellow-200', icon: Clock };
        return { text: 'Activo', color: 'bg-green-100 text-green-800 border-green-200', icon: CheckCircle };
    };

    const getTipoDocumento = (tipo?: string) => {
        const tipos: Record<string, string> = {
            'cedula_ciudadania': 'Cédula de Ciudadanía',
            'cedula_extranjeria': 'Cédula de Extranjería',
            'pasaporte': 'Pasaporte',
            'tarjeta_identidad': 'Tarjeta de Identidad'
        };
        return tipos[tipo || ''] || tipo || 'No especificado';
    };

    const formatDate = (date?: string | null) => {
        if (!date) return 'No especificado';
        return new Date(date).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const formatDateTime = (date?: string | null) => {
        if (!date) return 'No especificado';
        return new Date(date).toLocaleString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const status = getUserStatus();
    const StatusIcon = status.icon;

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title={`Ver Usuario - ${user.name}`} />
            
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
                    <div className="flex items-center gap-3">
                        <div className="h-12 w-12 bg-[#2a3d83] rounded-full flex items-center justify-center text-white text-xl font-medium">
                            {user.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900">
                                {user.name}
                            </h1>
                            <p className="text-sm text-gray-600">{user.email}</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className={`flex items-center gap-2 px-3 py-1.5 rounded-lg border ${status.color}`}>
                            <StatusIcon className="h-4 w-4" />
                            <span className="text-sm font-medium">{status.text}</span>
                        </div>
                        <Link
                            href={`/admin/users/${user.id}/edit`}
                            className="px-4 py-2 bg-[#2a3d83] text-white rounded-lg hover:bg-[#1e2b5f] transition-colors"
                        >
                            Editar Usuario
                        </Link>
                    </div>
                </div>

                {/* Información Básica */}
                <div className="bg-white rounded-lg border shadow-sm">
                    <div className="px-6 py-4 border-b">
                        <h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <User className="h-5 w-5 text-[#2a3d83]" />
                            Información Básica
                        </h2>
                    </div>
                    <div className="p-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="space-y-1">
                                <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                    <User className="h-4 w-4" />
                                    Nombre Completo
                                </label>
                                <p className="text-base text-gray-900">{user.name}</p>
                            </div>
                            <div className="space-y-1">
                                <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                    <Mail className="h-4 w-4" />
                                    Correo Electrónico
                                </label>
                                <p className="text-base text-gray-900">{user.email}</p>
                            </div>
                            <div className="space-y-1">
                                <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                    <Shield className="h-4 w-4" />
                                    Rol
                                </label>
                                <p className="text-base text-gray-900">{user.role?.name || 'Sin rol asignado'}</p>
                                {user.role?.description && (
                                    <p className="text-sm text-gray-600">{user.role.description}</p>
                                )}
                            </div>
                            <div className="space-y-1">
                                <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                    <CheckCircle className="h-4 w-4" />
                                    Estado de la Cuenta
                                </label>
                                <p className="text-base text-gray-900">{user.estado_cuenta || 'Activo'}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Información Personal y Laboral */}
                <div className="bg-white rounded-lg border shadow-sm">
                    <div className="px-6 py-4 border-b">
                        <h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <Briefcase className="h-5 w-5 text-[#2a3d83]" />
                            Información Personal y Laboral
                        </h2>
                    </div>
                    <div className="p-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="space-y-1">
                                <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                    <FileText className="h-4 w-4" />
                                    Documento de Identidad
                                </label>
                                <p className="text-base text-gray-900">{user.documento_identidad || 'No especificado'}</p>
                                <p className="text-sm text-gray-600">{getTipoDocumento(user.tipo_documento)}</p>
                            </div>
                            <div className="space-y-1">
                                <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                    <Phone className="h-4 w-4" />
                                    Teléfono
                                </label>
                                <p className="text-base text-gray-900">{user.telefono || 'No especificado'}</p>
                            </div>
                            <div className="space-y-1">
                                <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                    <Briefcase className="h-4 w-4" />
                                    Cargo
                                </label>
                                <p className="text-base text-gray-900">{user.cargo || 'No especificado'}</p>
                            </div>
                            <div className="space-y-1">
                                <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                    <Building2 className="h-4 w-4" />
                                    Dependencia
                                </label>
                                <p className="text-base text-gray-900">{user.dependencia || 'No especificado'}</p>
                            </div>
                            <div className="space-y-1">
                                <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                    <Calendar className="h-4 w-4" />
                                    Fecha de Ingreso
                                </label>
                                <p className="text-base text-gray-900">{formatDate(user.fecha_ingreso)}</p>
                            </div>
                            <div className="space-y-1">
                                <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                    <Calendar className="h-4 w-4" />
                                    Fecha de Vencimiento de Cuenta
                                </label>
                                <p className="text-base text-gray-900">{formatDate(user.fecha_vencimiento_cuenta)}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Información del Sistema */}
                <div className="bg-white rounded-lg border shadow-sm">
                    <div className="px-6 py-4 border-b">
                        <h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <Clock className="h-5 w-5 text-[#2a3d83]" />
                            Información del Sistema
                        </h2>
                    </div>
                    <div className="p-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="space-y-1">
                                <label className="text-sm font-medium text-gray-500">
                                    Email Verificado
                                </label>
                                <p className="text-base text-gray-900">
                                    {user.email_verified_at ? (
                                        <span className="flex items-center gap-2 text-green-600">
                                            <CheckCircle className="h-4 w-4" />
                                            Verificado el {formatDate(user.email_verified_at)}
                                        </span>
                                    ) : (
                                        <span className="flex items-center gap-2 text-yellow-600">
                                            <Clock className="h-4 w-4" />
                                            Pendiente de verificación
                                        </span>
                                    )}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <label className="text-sm font-medium text-gray-500">
                                    Último Acceso
                                </label>
                                <p className="text-base text-gray-900">{formatDateTime(user.ultimo_acceso)}</p>
                            </div>
                            <div className="space-y-1">
                                <label className="text-sm font-medium text-gray-500">
                                    Fecha de Registro
                                </label>
                                <p className="text-base text-gray-900">{formatDateTime(user.created_at)}</p>
                            </div>
                            <div className="space-y-1">
                                <label className="text-sm font-medium text-gray-500">
                                    Última Actualización
                                </label>
                                <p className="text-base text-gray-900">{formatDateTime(user.updated_at)}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
