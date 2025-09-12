import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { FileText, Upload, Settings, Users, Database, FolderOpen, Clock } from 'lucide-react';
import { useState, useEffect } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    const [currentTime, setCurrentTime] = useState(new Date());

    useEffect(() => {
        const timer = setInterval(() => {
            setCurrentTime(new Date());
        }, 1000);

        return () => clearInterval(timer);
    }, []);

    const formatTime = (date: Date) => {
        return date.toLocaleTimeString('es-CO', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
    };

    const formatDate = (date: Date) => {
        return date.toLocaleDateString('es-CO', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                {/* Sistema Header Card */}
                <Card className="bg-gradient-to-r from-blue-600 to-blue-800 text-white">
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <img 
                                    src="/images/Logo2.PNG" 
                                    alt="ArchiveyCloud Logo" 
                                    className="w-16 h-16 object-contain bg-white rounded-lg p-2"
                                />
                                <div>
                                    <h1 className="text-2xl font-bold">Sistema de Gestión Documental Electrónico de Archivo</h1>
                                    <p className="text-blue-100 mt-1">ArchiveyCloud - Plataforma Integral</p>
                                </div>
                            </div>
                            <div className="text-right">
                                <div className="flex items-center gap-2 mb-2">
                                    <Clock className="w-5 h-5" />
                                    <span className="text-xl font-mono font-bold">{formatTime(currentTime)}</span>
                                </div>
                                <p className="text-blue-100 text-sm">{formatDate(currentTime)}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Welcome Section */}
                <div className="mb-6">
                    <h2 className="text-2xl font-bold text-gray-900">Bienvenido al Dashboard</h2>
                    <p className="text-gray-600 mt-1">Panel de control administrativo del sistema</p>
                </div>

                {/* General Stats */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Sistema</CardTitle>
                            <Settings className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">Activo</div>
                            <p className="text-xs text-muted-foreground">Estado del sistema</p>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Módulos</CardTitle>
                            <Database className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">2</div>
                            <p className="text-xs text-muted-foreground">Gestión Documental y Administración</p>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Acceso</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">Admin</div>
                            <p className="text-xs text-muted-foreground">Nivel de permisos</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Information Card */}
                <Card>
                    <CardHeader>
                        <CardTitle>Información del Sistema</CardTitle>
                        <CardDescription>Detalles sobre las funcionalidades disponibles</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 className="font-semibold text-lg mb-2 flex items-center gap-2">
                                    <Database className="w-5 h-5 text-blue-600" />
                                    Gestión Documental
                                </h3>
                                <p className="text-gray-600 text-sm">
                                    Administra las Tablas de Retención Documental (TRD), crea nuevas estructuras 
                                    documentales e importa datos desde archivos externos.
                                </p>
                            </div>
                            <div>
                                <h3 className="font-semibold text-lg mb-2 flex items-center gap-2">
                                    <Settings className="w-5 h-5 text-green-600" />
                                    Administración
                                </h3>
                                <p className="text-gray-600 text-sm">
                                    Gestiona usuarios del sistema, configura permisos y supervisa 
                                    el funcionamiento general de la plataforma.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
