import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { 
    BarChart, 
    Bar, 
    XAxis, 
    YAxis, 
    CartesianGrid, 
    Tooltip, 
    Legend, 
    LineChart,
    Line,
    Area,
    AreaChart,
    PieChart,
    Pie,
    Cell,
    ResponsiveContainer
} from 'recharts';
import { 
    HardDrive, 
    FileText, 
    TrendingUp,
    Download,
    Database,
    Archive,
    AlertTriangle,
    Info,
    Zap
} from 'lucide-react';

interface EspacioPorTipo {
    tipo: string;
    cantidad: number;
    tamaño_total_mb: number;
    tamaño_promedio_mb: number;
    porcentaje: number;
}

interface CrecimientoAlmacenamiento {
    mes: string;
    documentos: number;
    tamaño_agregado_mb: number;
}

interface DocumentoGrande {
    id: number;
    nombre: string;
    expediente: string;
    tipo: string;
    tamaño_mb: number;
    fecha_creacion: string;
}

interface ResumenAlmacenamiento {
    total_documentos: number;
    tamaño_total_gb: number;
    tamaño_promedio_mb: number;
    documentos_este_mes: number;
}

interface Props {
    espacioPorTipo: EspacioPorTipo[];
    crecimientoAlmacenamiento: CrecimientoAlmacenamiento[];
    documentosMasGrandes: DocumentoGrande[];
    resumenAlmacenamiento: ResumenAlmacenamiento;
}

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D', '#FFC658', '#8DD1E1'];

export default function AlmacenamientoReporte({ 
    espacioPorTipo, 
    crecimientoAlmacenamiento, 
    documentosMasGrandes,
    resumenAlmacenamiento
}: Props) {
    
    const exportarReporte = () => {
        // Implementar exportación
        console.log('Exportar reporte de almacenamiento');
    };

    // Calcular estadísticas adicionales
    const crecimientoTotalMes = crecimientoAlmacenamiento.reduce((acc, item) => acc + item.tamaño_agregado_mb, 0);
    const promedioMensual = Math.round(crecimientoTotalMes / crecimientoAlmacenamiento.length) || 0;
    const documentoMasGrande = documentosMasGrandes[0];

    // Datos acumulativos para el gráfico
    let acumulado = 0;
    const datosAcumulativos = crecimientoAlmacenamiento.map(item => {
        acumulado += item.tamaño_agregado_mb;
        return {
            ...item,
            acumulado_gb: Math.round(acumulado / 1024 * 100) / 100
        };
    });

    // Convertir espacioPorTipo para mejor visualización
    const espacioPorTipoChart = espacioPorTipo.slice(0, 8).map((item, index) => ({
        ...item,
        tamaño_gb: Math.round(item.tamaño_total_mb / 1024 * 100) / 100,
        color: COLORS[index % COLORS.length]
    }));

    return (
        <AppLayout>
            <Head title="Almacenamiento - Reportes" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center space-x-2">
                            <Link 
                                href={route('admin.reportes.dashboard')}
                                className="text-sm text-muted-foreground hover:text-foreground"
                            >
                                Reportes
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">Almacenamiento</span>
                        </div>
                        <h1 className="text-2xl font-bold mt-1">Análisis de Almacenamiento</h1>
                        <p className="text-muted-foreground">Gestión y análisis del espacio de almacenamiento utilizado</p>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <Button variant="outline" onClick={exportarReporte}>
                            <Download className="h-4 w-4 mr-2" />
                            Exportar
                        </Button>
                    </div>
                </div>

                {/* Métricas principales */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Documentos</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{resumenAlmacenamiento.total_documentos.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">
                                +{resumenAlmacenamiento.documentos_este_mes} este mes
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Espacio Total</CardTitle>
                            <HardDrive className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{resumenAlmacenamiento.tamaño_total_gb} GB</div>
                            <p className="text-xs text-muted-foreground">
                                Almacenamiento utilizado
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Tamaño Promedio</CardTitle>
                            <Database className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{resumenAlmacenamiento.tamaño_promedio_mb} MB</div>
                            <p className="text-xs text-muted-foreground">
                                Por documento
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Crecimiento Mensual</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{promedioMensual} MB</div>
                            <p className="text-xs text-muted-foreground">
                                Promedio por mes
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Documento más grande destacado */}
                {documentoMasGrande && (
                    <Card className="border-yellow-200 bg-yellow-50">
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <AlertTriangle className="h-5 w-5 text-yellow-600" />
                                <span>Documento Más Grande</span>
                            </CardTitle>
                            <CardDescription>El documento que consume más espacio de almacenamiento</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between p-4 bg-white rounded border">
                                <div>
                                    <h3 className="font-semibold text-lg">{documentoMasGrande.nombre}</h3>
                                    <p className="text-sm text-muted-foreground">{documentoMasGrande.expediente}</p>
                                    <div className="flex items-center space-x-4 text-xs text-muted-foreground mt-1">
                                        <span>Tipo: {documentoMasGrande.tipo}</span>
                                        <span>Creado: {documentoMasGrande.fecha_creacion}</span>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <div className="text-3xl font-bold text-yellow-600">{documentoMasGrande.tamaño_mb} MB</div>
                                    <Badge variant="outline" className="border-yellow-600 text-yellow-600">
                                        Optimización requerida
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Gráficos principales */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Distribución por Tipo de Documento</CardTitle>
                            <CardDescription>Espacio utilizado por cada tipo de documento</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <PieChart>
                                    <Pie
                                        data={espacioPorTipoChart}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ tipo, porcentaje }) => `${tipo}: ${porcentaje}%`}
                                        outerRadius={80}
                                        fill="#8884d8"
                                        dataKey="tamaño_gb"
                                    >
                                        {espacioPorTipoChart.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={entry.color} />
                                        ))}
                                    </Pie>
                                    <Tooltip formatter={(value: any) => [`${value} GB`, 'Tamaño']} />
                                </PieChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Crecimiento de Almacenamiento</CardTitle>
                            <CardDescription>Evolución del almacenamiento utilizado por mes</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <AreaChart data={datosAcumulativos}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="mes" />
                                    <YAxis />
                                    <Tooltip formatter={(value: any, name: string) => [
                                        name === 'acumulado_gb' ? `${value} GB` : `${value} documentos`, 
                                        name === 'acumulado_gb' ? 'Almacenamiento Acumulado' : 'Documentos'
                                    ]} />
                                    <Area 
                                        type="monotone" 
                                        dataKey="acumulado_gb" 
                                        stroke="#8884d8" 
                                        fill="#8884d8"
                                        fillOpacity={0.6}
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>

                {/* Tabla detallada por tipo */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Archive className="h-5 w-5" />
                            <span>Análisis Detallado por Tipo</span>
                        </CardTitle>
                        <CardDescription>Información completa del uso de almacenamiento por tipo de documento</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {espacioPorTipo.map((tipo, index) => (
                                <div key={tipo.tipo} className="p-4 border rounded-lg">
                                    <div className="flex items-center justify-between mb-2">
                                        <div className="flex items-center space-x-3">
                                            <div 
                                                className="w-4 h-4 rounded"
                                                style={{ backgroundColor: COLORS[index % COLORS.length] }}
                                            ></div>
                                            <h3 className="font-semibold text-lg">{tipo.tipo}</h3>
                                        </div>
                                        <Badge variant="outline">{tipo.porcentaje}% del total</Badge>
                                    </div>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                                        <div>
                                            <p className="text-muted-foreground">Cantidad de archivos</p>
                                            <p className="font-semibold">{tipo.cantidad.toLocaleString()}</p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground">Espacio total</p>
                                            <p className="font-semibold">{Math.round(tipo.tamaño_total_mb / 1024 * 100) / 100} GB</p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground">Tamaño promedio</p>
                                            <p className="font-semibold">{tipo.tamaño_promedio_mb} MB</p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground">% del almacenamiento</p>
                                            <Progress value={tipo.porcentaje} className="mt-1" />
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* Documentos más grandes */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Zap className="h-5 w-5" />
                            <span>Documentos de Mayor Tamaño</span>
                        </CardTitle>
                        <CardDescription>Top 20 documentos que ocupan más espacio</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {documentosMasGrandes.map((documento, index) => (
                                <div key={documento.id} className="flex items-center justify-between p-3 border rounded-lg">
                                    <div className="flex items-center space-x-3">
                                        <div className={`flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold text-white ${
                                            index === 0 ? 'bg-red-500' : 
                                            index === 1 ? 'bg-orange-500' : 
                                            index === 2 ? 'bg-yellow-500' : 
                                            'bg-gray-400'
                                        }`}>
                                            {index + 1}
                                        </div>
                                        <div>
                                            <h4 className="font-medium">{documento.nombre}</h4>
                                            <p className="text-sm text-muted-foreground">{documento.expediente}</p>
                                            <div className="flex items-center space-x-4 text-xs text-muted-foreground">
                                                <span>Tipo: {documento.tipo}</span>
                                                <span>Creado: {documento.fecha_creacion}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-lg font-bold">{documento.tamaño_mb} MB</div>
                                        {documento.tamaño_mb > 100 && (
                                            <Badge variant="outline" className="border-yellow-600 text-yellow-600">
                                                Considerar optimización
                                            </Badge>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* Crecimiento mensual detallado */}
                <Card>
                    <CardHeader>
                        <CardTitle>Crecimiento Mensual Detallado</CardTitle>
                        <CardDescription>Documentos agregados y espacio utilizado por mes</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <BarChart data={crecimientoAlmacenamiento}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="mes" />
                                <YAxis yAxisId="left" />
                                <YAxis yAxisId="right" orientation="right" />
                                <Tooltip />
                                <Legend />
                                <Bar yAxisId="left" dataKey="documentos" fill="#8884d8" name="Documentos" />
                                <Line yAxisId="right" type="monotone" dataKey="tamaño_agregado_mb" stroke="#82ca9d" name="MB agregados" />
                            </BarChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>

                {/* Recomendaciones */}
                <Card className="border-blue-200 bg-blue-50">
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Info className="h-5 w-5 text-blue-600" />
                            <span>Recomendaciones de Optimización</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            <div className="flex items-start space-x-3">
                                <div className="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                                <div>
                                    <h4 className="font-medium">Compresión de Archivos</h4>
                                    <p className="text-sm text-muted-foreground">
                                        Considerar comprimir documentos de más de 50 MB para optimizar el almacenamiento.
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-start space-x-3">
                                <div className="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                                <div>
                                    <h4 className="font-medium">Archivado Automático</h4>
                                    <p className="text-sm text-muted-foreground">
                                        Implementar archivado automático de documentos antiguos poco consultados.
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-start space-x-3">
                                <div className="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                                <div>
                                    <h4 className="font-medium">Políticas de Retención</h4>
                                    <p className="text-sm text-muted-foreground">
                                        Revisar y aplicar políticas de retención para documentos vencidos.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
