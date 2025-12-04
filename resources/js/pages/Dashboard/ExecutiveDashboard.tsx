import { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import {
    FileText,
    Folder,
    Users,
    TrendingUp,
    Activity,
    Clock,
    CheckCircle,
    AlertTriangle,
    Download,
    Calendar,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface KPI {
    label: string;
    value: number | string;
    change?: number;
    trend?: 'up' | 'down';
    icon: any;
}

interface ChartData {
    labels: string[];
    datasets: Array<{
        label: string;
        data: number[];
        backgroundColor?: string;
        borderColor?: string;
    }>;
}

export default function ExecutiveDashboard() {
    const [period, setPeriod] = useState<string>('30');
    const [loading, setLoading] = useState(true);
    const [kpis, setKpis] = useState<KPI[]>([]);
    const [documentsChart, setDocumentsChart] = useState<ChartData | null>(null);
    const [activityChart, setActivityChart] = useState<ChartData | null>(null);
    const [seriesDistribution, setSeriesDistribution] = useState<any[]>([]);
    const [recentActivity, setRecentActivity] = useState<any[]>([]);
    const [complianceMetrics, setComplianceMetrics] = useState<any>(null);

    useEffect(() => {
        fetchDashboardData();
    }, [period]);

    const fetchDashboardData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(`/api/dashboard/executive?period=${period}`);
            const data = response.data;

            // Configurar KPIs
            setKpis([
                {
                    label: 'Total Documentos',
                    value: data.kpis.total_documentos.toLocaleString(),
                    change: data.kpis.documentos_change,
                    trend: data.kpis.documentos_change > 0 ? 'up' : 'down',
                    icon: FileText,
                },
                {
                    label: 'Total Expedientes',
                    value: data.kpis.total_expedientes.toLocaleString(),
                    change: data.kpis.expedientes_change,
                    trend: data.kpis.expedientes_change > 0 ? 'up' : 'down',
                    icon: Folder,
                },
                {
                    label: 'Usuarios Activos',
                    value: data.kpis.usuarios_activos.toLocaleString(),
                    change: data.kpis.usuarios_change,
                    trend: data.kpis.usuarios_change > 0 ? 'up' : 'down',
                    icon: Users,
                },
                {
                    label: 'Tasa de Cumplimiento',
                    value: `${data.kpis.cumplimiento_rate}%`,
                    change: data.kpis.cumplimiento_change,
                    trend: data.kpis.cumplimiento_change > 0 ? 'up' : 'down',
                    icon: CheckCircle,
                },
            ]);

            setDocumentsChart(data.charts.documents_over_time);
            setActivityChart(data.charts.user_activity);
            setSeriesDistribution(data.series_distribution);
            setRecentActivity(data.recent_activity);
            setComplianceMetrics(data.compliance);
        } catch (error) {
            console.error('Error fetching dashboard data:', error);
        } finally {
            setLoading(false);
        }
    };

    const exportReport = async (format: 'pdf' | 'excel') => {
        try {
            const response = await axios.post(
                `/api/dashboard/export`,
                { period, format },
                { responseType: 'blob' }
            );

            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `reporte-ejecutivo-${Date.now()}.${format === 'pdf' ? 'pdf' : 'xlsx'}`);
            document.body.appendChild(link);
            link.click();
            link.remove();
        } catch (error) {
            console.error('Error exporting report:', error);
        }
    };

    return (
        <>
            <Head title="Dashboard Ejecutivo - Archivey Cloud" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Dashboard Ejecutivo</h1>
                        <p className="text-muted-foreground mt-1">
                            Métricas y estadísticas del sistema SGDEA
                        </p>
                    </div>
                    <div className="flex items-center gap-4">
                        <Select value={period} onValueChange={setPeriod}>
                            <SelectTrigger className="w-[180px]">
                                <Calendar className="h-4 w-4 mr-2" />
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="7">Últimos 7 días</SelectItem>
                                <SelectItem value="30">Últimos 30 días</SelectItem>
                                <SelectItem value="90">Últimos 90 días</SelectItem>
                                <SelectItem value="365">Último año</SelectItem>
                            </SelectContent>
                        </Select>

                        <Button variant="outline" onClick={() => exportReport('excel')}>
                            <Download className="h-4 w-4 mr-2" />
                            Excel
                        </Button>
                        <Button variant="outline" onClick={() => exportReport('pdf')}>
                            <Download className="h-4 w-4 mr-2" />
                            PDF
                        </Button>
                    </div>
                </div>

                {/* KPIs Grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {kpis.map((kpi, index) => (
                        <Card key={index}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">{kpi.label}</CardTitle>
                                <kpi.icon className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{kpi.value}</div>
                                {kpi.change !== undefined && (
                                    <p className="text-xs text-muted-foreground">
                                        <span
                                            className={`inline-flex items-center ${
                                                kpi.trend === 'up' ? 'text-green-600' : 'text-red-600'
                                            }`}
                                        >
                                            <TrendingUp
                                                className={`h-3 w-3 mr-1 ${
                                                    kpi.trend === 'down' ? 'rotate-180' : ''
                                                }`}
                                            />
                                            {Math.abs(kpi.change)}%
                                        </span>{' '}
                                        vs período anterior
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Charts Row */}
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Documents Over Time Chart */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Documentos Capturados</CardTitle>
                            <CardDescription>Tendencia de captura documental</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {documentsChart && (
                                <div className="h-[300px] flex items-center justify-center">
                                    <p className="text-muted-foreground">
                                        Gráfico de línea: {documentsChart.labels.length} puntos de datos
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* User Activity Chart */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Actividad de Usuarios</CardTitle>
                            <CardDescription>Sesiones y accesos al sistema</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {activityChart && (
                                <div className="h-[300px] flex items-center justify-center">
                                    <p className="text-muted-foreground">
                                        Gráfico de barras: {activityChart.labels.length} días
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Distribution and Activity Row */}
                <div className="grid gap-4 md:grid-cols-3">
                    {/* Series Distribution */}
                    <Card className="md:col-span-1">
                        <CardHeader>
                            <CardTitle>Distribución por Series</CardTitle>
                            <CardDescription>Top 10 series documentales</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {seriesDistribution.slice(0, 10).map((serie: any, index: number) => (
                                    <div key={index} className="flex items-center justify-between">
                                        <div className="flex-1 space-y-1">
                                            <p className="text-sm font-medium leading-none">
                                                {serie.nombre}
                                            </p>
                                            <div className="flex items-center gap-2">
                                                <div className="h-2 flex-1 bg-gray-200 rounded-full overflow-hidden">
                                                    <div
                                                        className="h-full bg-primary"
                                                        style={{
                                                            width: `${(serie.count / seriesDistribution[0].count) * 100}%`,
                                                        }}
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <Badge variant="secondary" className="ml-2">
                                            {serie.count}
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Recent Activity */}
                    <Card className="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Actividad Reciente</CardTitle>
                            <CardDescription>Últimas acciones en el sistema</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {recentActivity.slice(0, 8).map((activity: any, index: number) => (
                                    <div key={index} className="flex items-center gap-4">
                                        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10">
                                            <Activity className="h-4 w-4 text-primary" />
                                        </div>
                                        <div className="flex-1 space-y-1">
                                            <p className="text-sm font-medium leading-none">
                                                {activity.descripcion}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {activity.usuario} • {activity.modulo}
                                            </p>
                                        </div>
                                        <div className="text-sm text-muted-foreground flex items-center gap-1">
                                            <Clock className="h-3 w-3" />
                                            {activity.tiempo_relativo}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Compliance Metrics */}
                {complianceMetrics && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Métricas de Cumplimiento Normativo</CardTitle>
                            <CardDescription>Indicadores de gestión documental</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 md:grid-cols-4">
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium">Documentos con TRD</span>
                                        <Badge variant={complianceMetrics.trd_compliance > 90 ? 'default' : 'destructive'}>
                                            {complianceMetrics.trd_compliance}%
                                        </Badge>
                                    </div>
                                    <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-green-500"
                                            style={{ width: `${complianceMetrics.trd_compliance}%` }}
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium">Metadatos Completos</span>
                                        <Badge variant={complianceMetrics.metadata_compliance > 90 ? 'default' : 'destructive'}>
                                            {complianceMetrics.metadata_compliance}%
                                        </Badge>
                                    </div>
                                    <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-blue-500"
                                            style={{ width: `${complianceMetrics.metadata_compliance}%` }}
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium">Documentos Firmados</span>
                                        <Badge variant={complianceMetrics.signature_compliance > 70 ? 'default' : 'secondary'}>
                                            {complianceMetrics.signature_compliance}%
                                        </Badge>
                                    </div>
                                    <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-purple-500"
                                            style={{ width: `${complianceMetrics.signature_compliance}%` }}
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium">Trazabilidad</span>
                                        <Badge variant={complianceMetrics.audit_compliance > 95 ? 'default' : 'destructive'}>
                                            {complianceMetrics.audit_compliance}%
                                        </Badge>
                                    </div>
                                    <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-orange-500"
                                            style={{ width: `${complianceMetrics.audit_compliance}%` }}
                                        />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
