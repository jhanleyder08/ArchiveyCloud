import React from 'react';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import {
    TrendingUp,
    Users,
    FileText,
    Folder,
    Database,
    CheckCircle,
} from 'lucide-react';

interface MetricasGenerales {
    total_documentos: number;
    total_expedientes: number;
    total_usuarios: number;
    total_series: number;
    almacenamiento_total: number;
    indices_generados: number;
}

interface KPIsCriticos {
    documentos_procesados_semana: number;
    expedientes_creados_semana: number;
    expedientes_vencidos: number;
    expedientes_proximo_vencimiento: number;
    workflows_pendientes: number;
    workflows_vencidos: number;
    prestamos_activos: number;
    prestamos_vencidos: number;
    disposiciones_pendientes: number;
    disposiciones_vencidas: number;
}

interface Props {
    metricas_generales: MetricasGenerales;
    kpis_criticos: KPIsCriticos;
    alertas_criticas: any;
    cumplimiento: any;
    tendencias: any;
    usuarios_activos: any[];
    distribucion_trabajo: any;
}

export default function DashboardEjecutivoSimple({
    metricas_generales,
    kpis_criticos,
}: Props) {
    
    const formatNumber = (num: number) => {
        return new Intl.NumberFormat('es-CO').format(num);
    };

    return (
        <AppLayout>
            <Head title="Dashboard Ejecutivo" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">
                            🎯 Dashboard Ejecutivo
                        </h1>
                        <p className="text-gray-600">
                            Visión integral del sistema de gestión documental ArchiveyCloud
                        </p>
                    </div>

                    {/* Métricas Principales */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Total Documentos
                                </CardTitle>
                                <FileText className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-blue-600">
                                    {formatNumber(metricas_generales.total_documentos)}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Documentos en el sistema
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Total Expedientes
                                </CardTitle>
                                <Folder className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-green-600">
                                    {formatNumber(metricas_generales.total_expedientes)}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Expedientes registrados
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Usuarios Activos
                                </CardTitle>
                                <Users className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-purple-600">
                                    {formatNumber(metricas_generales.total_usuarios)}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Usuarios en el sistema
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* KPIs de la Semana */}
                    <Card className="mb-8">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <TrendingUp className="h-5 w-5 text-blue-500" />
                                KPIs de la Semana
                            </CardTitle>
                            <CardDescription>
                                Indicadores clave de rendimiento del sistema
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div className="text-center p-4 bg-blue-50 rounded-lg">
                                    <div className="text-2xl font-bold text-blue-600 mb-1">
                                        {formatNumber(kpis_criticos.documentos_procesados_semana)}
                                    </div>
                                    <div className="text-sm text-gray-600">
                                        Documentos Procesados
                                    </div>
                                </div>
                                
                                <div className="text-center p-4 bg-green-50 rounded-lg">
                                    <div className="text-2xl font-bold text-green-600 mb-1">
                                        {formatNumber(kpis_criticos.expedientes_creados_semana)}
                                    </div>
                                    <div className="text-sm text-gray-600">
                                        Expedientes Creados
                                    </div>
                                </div>
                                
                                <div className="text-center p-4 bg-orange-50 rounded-lg">
                                    <div className="text-2xl font-bold text-orange-600 mb-1">
                                        {formatNumber(kpis_criticos.expedientes_vencidos)}
                                    </div>
                                    <div className="text-sm text-gray-600">
                                        Expedientes Vencidos
                                    </div>
                                </div>
                                
                                <div className="text-center p-4 bg-purple-50 rounded-lg">
                                    <div className="text-2xl font-bold text-purple-600 mb-1">
                                        {formatNumber(metricas_generales.almacenamiento_total)} GB
                                    </div>
                                    <div className="text-sm text-gray-600">
                                        Almacenamiento Total
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Estado del Sistema */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CheckCircle className="h-5 w-5 text-green-500" />
                                Estado del Sistema
                            </CardTitle>
                            <CardDescription>
                                Resumen del estado actual del sistema ArchiveyCloud
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div className="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                    <span className="font-medium text-green-800">Sistema Operativo</span>
                                    <Badge variant="outline" className="text-green-600 border-green-600">
                                        ✅ Funcionando
                                    </Badge>
                                </div>
                                
                                <div className="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                    <span className="font-medium text-blue-800">Base de Datos</span>
                                    <Badge variant="outline" className="text-blue-600 border-blue-600">
                                        ✅ Conectada
                                    </Badge>
                                </div>
                                
                                <div className="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                                    <span className="font-medium text-purple-800">Último Backup</span>
                                    <Badge variant="outline" className="text-purple-600 border-purple-600">
                                        📅 {new Date().toLocaleDateString()}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                </div>
            </div>
        </AppLayout>
    );
}
