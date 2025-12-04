import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    Select, 
    SelectContent, 
    SelectItem, 
    SelectTrigger, 
    SelectValue 
} from '@/components/ui/select';
import { 
    BarChart, 
    Bar, 
    XAxis, 
    YAxis, 
    CartesianGrid, 
    Tooltip, 
    Legend, 
    PieChart, 
    Pie, 
    Cell,
    ResponsiveContainer
} from 'recharts';
import { 
    AlertTriangle, 
    CheckCircle, 
    Clock, 
    FileText, 
    MapPin,
    Download,
    Calendar,
    Shield,
    Archive,
    Search,
    Filter
} from 'lucide-react';

interface ExpedienteRetencion {
    expediente: {
        id: number;
        codigo: string;
        nombre: string;
        estado: string;
        fecha_apertura: string;
        responsable: string;
    };
    tiempo_retencion: number;
    fecha_limite_ag: string;
    dias_restantes: number;
    estado_cumplimiento: 'vigente' | 'vencido';
}

interface DocumentoSinClasificar {
    id: number;
    nombre: string;
    tipo_documento: string;
    created_at: string;
    expediente: {
        codigo: string;
        nombre: string;
    };
}

interface ExpedienteSinUbicacion {
    id: number;
    codigo: string;
    nombre: string;
    estado: string;
    responsable: string;
    fecha_apertura: string;
}

interface ResumenCumplimiento {
    total_expedientes: number;
    expedientes_vigentes: number;
    expedientes_vencidos: number;
    documentos_sin_clasificar: number;
    expedientes_sin_ubicacion: number;
    porcentaje_cumplimiento: number;
}

interface Props {
    expedientesRetencion: ExpedienteRetencion[];
    documentosSinClasificar: DocumentoSinClasificar[];
    expedientesSinUbicacion: ExpedienteSinUbicacion[];
    resumenCumplimiento: ResumenCumplimiento;
    fechaInicio: string;
    fechaFin: string;
}

const COLORS = {
    vigente: '#22C55E',
    vencido: '#EF4444',
    warning: '#F59E0B'
};

export default function CumplimientoNormativo({ 
    expedientesRetencion, 
    documentosSinClasificar, 
    expedientesSinUbicacion,
    resumenCumplimiento,
    fechaInicio,
    fechaFin
}: Props) {
    const [filtros, setFiltros] = useState({
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
        estado_cumplimiento: '',
        responsable: ''
    });

    const { data, setData, get, processing } = useForm({
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin
    });

    const aplicarFiltros = () => {
        get(route('admin.reportes.cumplimiento-normativo'), {
            data: {
                fecha_inicio: data.fecha_inicio,
                fecha_fin: data.fecha_fin
            },
            preserveState: true
        });
    };

    const exportarReporte = () => {
        // Implementar exportación
        console.log('Exportar reporte de cumplimiento normativo');
    };

    // Preparar datos para gráficos
    const datosCircular = [
        { name: 'Vigentes', value: resumenCumplimiento.expedientes_vigentes, color: COLORS.vigente },
        { name: 'Vencidos', value: resumenCumplimiento.expedientes_vencidos, color: COLORS.vencido }
    ];

    const expedientesPorMes = expedientesRetencion.reduce((acc: any, exp) => {
        const mes = new Date(exp.expediente.fecha_apertura).toISOString().substr(0, 7);
        if (!acc[mes]) {
            acc[mes] = { mes, vigentes: 0, vencidos: 0 };
        }
        if (exp.estado_cumplimiento === 'vigente') {
            acc[mes].vigentes++;
        } else {
            acc[mes].vencidos++;
        }
        return acc;
    }, {});

    const datosBarras = Object.values(expedientesPorMes).sort((a: any, b: any) => a.mes.localeCompare(b.mes));

    // Expedientes próximos a vencer (30 días)
    const expedientesProximosAVencer = expedientesRetencion.filter(exp => 
        exp.estado_cumplimiento === 'vigente' && exp.dias_restantes <= 30 && exp.dias_restantes > 0
    );

    return (
        <AppLayout>
            <Head title="Cumplimiento Normativo - Reportes" />

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
                            <span className="font-medium">Cumplimiento Normativo</span>
                        </div>
                        <h1 className="text-2xl font-bold mt-1">Cumplimiento Normativo</h1>
                        <p className="text-muted-foreground">Análisis de cumplimiento de retención documental</p>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <Button variant="outline" onClick={exportarReporte}>
                            <Download className="h-4 w-4 mr-2" />
                            Exportar
                        </Button>
                    </div>
                </div>

                {/* Filtros */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Filter className="h-5 w-5" />
                            <span>Filtros</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="fecha_inicio">Fecha Inicio</Label>
                                <Input
                                    id="fecha_inicio"
                                    type="date"
                                    value={data.fecha_inicio}
                                    onChange={(e) => setData('fecha_inicio', e.target.value)}
                                />
                            </div>
                            
                            <div className="space-y-2">
                                <Label htmlFor="fecha_fin">Fecha Fin</Label>
                                <Input
                                    id="fecha_fin"
                                    type="date"
                                    value={data.fecha_fin}
                                    onChange={(e) => setData('fecha_fin', e.target.value)}
                                />
                            </div>
                            
                            <div className="space-y-2">
                                <Label>&nbsp;</Label>
                                <Button onClick={aplicarFiltros} disabled={processing} className="w-full">
                                    <Search className="h-4 w-4 mr-2" />
                                    {processing ? 'Aplicando...' : 'Aplicar Filtros'}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Resumen de cumplimiento */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Expedientes</CardTitle>
                            <Archive className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{resumenCumplimiento.total_expedientes}</div>
                            <p className="text-xs text-muted-foreground">En el período seleccionado</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Cumplimiento</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">
                                {resumenCumplimiento.porcentaje_cumplimiento}%
                            </div>
                            <Progress value={resumenCumplimiento.porcentaje_cumplimiento} className="mt-1" />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Expedientes Vigentes</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">
                                {resumenCumplimiento.expedientes_vigentes}
                            </div>
                            <p className="text-xs text-muted-foreground">Dentro del período de retención</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Expedientes Vencidos</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-red-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">
                                {resumenCumplimiento.expedientes_vencidos}
                            </div>
                            <p className="text-xs text-muted-foreground">Requieren disposición final</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Sin Ubicación</CardTitle>
                            <MapPin className="h-4 w-4 text-yellow-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-yellow-600">
                                {resumenCumplimiento.expedientes_sin_ubicacion}
                            </div>
                            <p className="text-xs text-muted-foreground">Expedientes sin ubicación física</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Gráficos de cumplimiento */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Estado de Cumplimiento</CardTitle>
                            <CardDescription>Distribución de expedientes por estado</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <PieChart>
                                    <Pie
                                        data={datosCircular}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(1)}%`}
                                        outerRadius={80}
                                        fill="#8884d8"
                                        dataKey="value"
                                    >
                                        {datosCircular.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={entry.color} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Cumplimiento por Mes</CardTitle>
                            <CardDescription>Evolución del cumplimiento en el tiempo</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={datosBarras}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="mes" />
                                    <YAxis />
                                    <Tooltip />
                                    <Legend />
                                    <Bar dataKey="vigentes" stackId="a" fill={COLORS.vigente} name="Vigentes" />
                                    <Bar dataKey="vencidos" stackId="a" fill={COLORS.vencido} name="Vencidos" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>

                {/* Tabs con detalles */}
                <Tabs defaultValue="proximos-vencer" className="space-y-4">
                    <TabsList className="grid w-full grid-cols-4">
                        <TabsTrigger value="proximos-vencer">Próximos a Vencer</TabsTrigger>
                        <TabsTrigger value="vencidos">Vencidos</TabsTrigger>
                        <TabsTrigger value="sin-clasificar">Sin Clasificar</TabsTrigger>
                        <TabsTrigger value="sin-ubicacion">Sin Ubicación</TabsTrigger>
                    </TabsList>

                    <TabsContent value="proximos-vencer" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <Clock className="h-5 w-5 text-yellow-500" />
                                    <span>Expedientes Próximos a Vencer (30 días)</span>
                                </CardTitle>
                                <CardDescription>
                                    Expedientes que vencen en los próximos 30 días
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {expedientesProximosAVencer.length > 0 ? (
                                    <div className="space-y-3">
                                        {expedientesProximosAVencer.map((exp) => (
                                            <div key={exp.expediente.id} className="flex items-center justify-between p-3 border rounded-lg">
                                                <div>
                                                    <h4 className="font-medium">{exp.expediente.codigo}</h4>
                                                    <p className="text-sm text-muted-foreground">{exp.expediente.nombre}</p>
                                                    <div className="flex items-center space-x-4 text-xs text-muted-foreground mt-1">
                                                        <span>Responsable: {exp.expediente.responsable}</span>
                                                        <span>Fecha límite: {new Date(exp.fecha_limite_ag).toLocaleDateString()}</span>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <Badge variant="outline" className="text-yellow-600 border-yellow-600">
                                                        {exp.dias_restantes} días restantes
                                                    </Badge>
                                                    <p className="text-xs text-muted-foreground mt-1">
                                                        Retención: {exp.tiempo_retencion} años
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <Alert>
                                        <CheckCircle className="h-4 w-4" />
                                        <AlertDescription>
                                            No hay expedientes próximos a vencer en los próximos 30 días.
                                        </AlertDescription>
                                    </Alert>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="vencidos" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <AlertTriangle className="h-5 w-5 text-red-500" />
                                    <span>Expedientes Vencidos</span>
                                </CardTitle>
                                <CardDescription>
                                    Expedientes que han superado su período de retención
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {expedientesRetencion.filter(exp => exp.estado_cumplimiento === 'vencido').length > 0 ? (
                                    <div className="space-y-3">
                                        {expedientesRetencion
                                            .filter(exp => exp.estado_cumplimiento === 'vencido')
                                            .slice(0, 10)
                                            .map((exp) => (
                                            <div key={exp.expediente.id} className="flex items-center justify-between p-3 border rounded-lg border-red-200">
                                                <div>
                                                    <h4 className="font-medium">{exp.expediente.codigo}</h4>
                                                    <p className="text-sm text-muted-foreground">{exp.expediente.nombre}</p>
                                                    <div className="flex items-center space-x-4 text-xs text-muted-foreground mt-1">
                                                        <span>Responsable: {exp.expediente.responsable}</span>
                                                        <span>Vencido el: {new Date(exp.fecha_limite_ag).toLocaleDateString()}</span>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <Badge variant="destructive">
                                                        Vencido hace {Math.abs(exp.dias_restantes)} días
                                                    </Badge>
                                                    <p className="text-xs text-muted-foreground mt-1">
                                                        Retención: {exp.tiempo_retencion} años
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <Alert>
                                        <CheckCircle className="h-4 w-4" />
                                        <AlertDescription>
                                            No hay expedientes vencidos en el período seleccionado.
                                        </AlertDescription>
                                    </Alert>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="sin-clasificar" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <FileText className="h-5 w-5 text-yellow-500" />
                                    <span>Documentos Sin Clasificar</span>
                                </CardTitle>
                                <CardDescription>
                                    Documentos que no tienen serie o subserie asignada
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {documentosSinClasificar.length > 0 ? (
                                    <div className="space-y-3">
                                        {documentosSinClasificar.slice(0, 10).map((doc) => (
                                            <div key={doc.id} className="flex items-center justify-between p-3 border rounded-lg border-yellow-200">
                                                <div>
                                                    <h4 className="font-medium">{doc.nombre}</h4>
                                                    <p className="text-sm text-muted-foreground">
                                                        {doc.expediente ? `${doc.expediente.codigo} - ${doc.expediente.nombre}` : 'Sin expediente'}
                                                    </p>
                                                    <div className="flex items-center space-x-4 text-xs text-muted-foreground mt-1">
                                                        <span>Tipo: {doc.tipo_documento}</span>
                                                        <span>Creado: {new Date(doc.created_at).toLocaleDateString()}</span>
                                                    </div>
                                                </div>
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={route('admin.documentos.edit', doc.id)}>
                                                        Clasificar
                                                    </Link>
                                                </Button>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <Alert>
                                        <CheckCircle className="h-4 w-4" />
                                        <AlertDescription>
                                            Todos los documentos están correctamente clasificados.
                                        </AlertDescription>
                                    </Alert>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="sin-ubicacion" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <MapPin className="h-5 w-5 text-yellow-500" />
                                    <span>Expedientes Sin Ubicación</span>
                                </CardTitle>
                                <CardDescription>
                                    Expedientes que no tienen ubicación física definida
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {expedientesSinUbicacion.length > 0 ? (
                                    <div className="space-y-3">
                                        {expedientesSinUbicacion.slice(0, 10).map((exp) => (
                                            <div key={exp.id} className="flex items-center justify-between p-3 border rounded-lg border-yellow-200">
                                                <div>
                                                    <h4 className="font-medium">{exp.codigo}</h4>
                                                    <p className="text-sm text-muted-foreground">{exp.nombre}</p>
                                                    <div className="flex items-center space-x-4 text-xs text-muted-foreground mt-1">
                                                        <span>Responsable: {exp.responsable}</span>
                                                        <span>Estado: {exp.estado}</span>
                                                        <span>Apertura: {new Date(exp.fecha_apertura).toLocaleDateString()}</span>
                                                    </div>
                                                </div>
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={route('admin.expedientes.edit', exp.id)}>
                                                        Asignar Ubicación
                                                    </Link>
                                                </Button>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <Alert>
                                        <CheckCircle className="h-4 w-4" />
                                        <AlertDescription>
                                            Todos los expedientes tienen ubicación física definida.
                                        </AlertDescription>
                                    </Alert>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
