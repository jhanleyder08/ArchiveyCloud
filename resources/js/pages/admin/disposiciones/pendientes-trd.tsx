import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Checkbox } from '@/components/ui/checkbox';
import { 
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { 
    Plus, 
    ArrowLeft,
    FileText,
    Calendar,
    AlertTriangle,
    CheckCircle,
    Clock,
    Archive,
    Zap,
    RefreshCw
} from 'lucide-react';

interface EstadisticasTRD {
    vencidos_sin_disposicion: number;
    proximos_30_dias: number;
    proximos_60_dias: number;
    proximos_90_dias: number;
    total_pendientes: number;
    por_tipo_disposicion: Record<string, number>;
}

interface ExpedientePendiente {
    id: number;
    codigo: string;
    titulo: string;
    fecha_cierre: string;
    fecha_eliminacion: string;
    dias_para_disposicion: number;
    ya_vencido: boolean;
    tipo_disposicion_sugerido: string;
    serie: string;
    subserie: string;
    responsable: string;
    info_retencion: {
        fecha_cierre: string;
        anos_archivo_gestion: number;
        anos_archivo_central: number;
        anos_total_retencion: number;
        fecha_fin_archivo_gestion: string;
        fecha_fin_archivo_central: string;
        disposicion_configurada: string;
        origen_disposicion: string;
    };
}

interface Props {
    expedientes: ExpedientePendiente[];
    estadisticasTRD: EstadisticasTRD;
    diasAnticipacion: number;
}

const tipoLabels: Record<string, string> = {
    conservacion_permanente: 'Conservación Permanente',
    eliminacion_controlada: 'Eliminación Controlada',
    transferencia_historica: 'Transferencia Histórica',
    digitalizacion: 'Digitalización',
    microfilmacion: 'Microfilmación',
};

const tipoColors: Record<string, string> = {
    conservacion_permanente: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    eliminacion_controlada: 'bg-red-100 text-red-800 border-red-200',
    transferencia_historica: 'bg-blue-100 text-blue-800 border-blue-200',
    digitalizacion: 'bg-indigo-100 text-indigo-800 border-indigo-200',
    microfilmacion: 'bg-violet-100 text-violet-800 border-violet-200',
};

export default function PendientesTRD({ expedientes, estadisticasTRD, diasAnticipacion }: Props) {
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [isGenerating, setIsGenerating] = useState(false);
    const { post, processing } = useForm({});

    const toggleSelect = (id: number) => {
        setSelectedIds(prev => 
            prev.includes(id) 
                ? prev.filter(i => i !== id)
                : [...prev, id]
        );
    };

    const toggleSelectAll = () => {
        if (selectedIds.length === expedientes.length) {
            setSelectedIds([]);
        } else {
            setSelectedIds(expedientes.map(e => e.id));
        }
    };

    const generarDisposicionesSeleccionadas = () => {
        if (selectedIds.length === 0) return;
        
        setIsGenerating(true);
        router.post('/admin/disposiciones/generar-automaticas', {
            expediente_ids: selectedIds,
        }, {
            onFinish: () => {
                setIsGenerating(false);
                setSelectedIds([]);
            }
        });
    };

    const generarTodasVencidas = () => {
        if (!confirm('¿Está seguro de generar disposiciones para TODOS los expedientes vencidos? Esta acción creará disposiciones automáticas basadas en la configuración de la TRD.')) {
            return;
        }
        
        setIsGenerating(true);
        router.post('/admin/disposiciones/generar-automaticas', {
            generar_todos_vencidos: true,
        }, {
            onFinish: () => setIsGenerating(false)
        });
    };

    const formatearFecha = (fecha: string) => {
        if (!fecha) return 'N/A';
        return new Date(fecha).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };

    return (
        <AppLayout>
            <Head title="Expedientes Pendientes de Disposición - TRD" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/admin/disposiciones">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                                Expedientes Pendientes de Disposición
                            </h2>
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                Expedientes que requieren disposición según la Tabla de Retención Documental (TRD)
                            </p>
                        </div>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        {estadisticasTRD.vencidos_sin_disposicion > 0 && (
                            <Button 
                                onClick={generarTodasVencidas}
                                disabled={isGenerating}
                                className="bg-red-600 hover:bg-red-700"
                            >
                                <Zap className="h-4 w-4 mr-2" />
                                {isGenerating ? 'Generando...' : `Generar Todas Vencidas (${estadisticasTRD.vencidos_sin_disposicion})`}
                            </Button>
                        )}
                    </div>
                </div>

                {/* Estadísticas TRD */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <Card className={estadisticasTRD.vencidos_sin_disposicion > 0 ? 'border-red-200 bg-red-50' : ''}>
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Vencidos</p>
                                    <p className="text-2xl font-semibold text-red-600">{estadisticasTRD.vencidos_sin_disposicion}</p>
                                </div>
                                <AlertTriangle className="h-8 w-8 text-red-400" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Próximos 30 días</p>
                                    <p className="text-2xl font-semibold text-orange-600">{estadisticasTRD.proximos_30_dias}</p>
                                </div>
                                <Clock className="h-8 w-8 text-orange-400" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Próximos 60 días</p>
                                    <p className="text-2xl font-semibold text-yellow-600">{estadisticasTRD.proximos_60_dias}</p>
                                </div>
                                <Calendar className="h-8 w-8 text-yellow-400" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Próximos 90 días</p>
                                    <p className="text-2xl font-semibold text-blue-600">{estadisticasTRD.proximos_90_dias}</p>
                                </div>
                                <Calendar className="h-8 w-8 text-blue-400" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Total Pendientes</p>
                                    <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticasTRD.total_pendientes}</p>
                                </div>
                                <Archive className="h-8 w-8 text-[#2a3d83]" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Información sobre la generación automática */}
                <Alert className="border-blue-200 bg-blue-50">
                    <FileText className="h-4 w-4 text-[#2a3d83]" />
                    <AlertDescription>
                        <strong className="text-blue-800">Generación Automática:</strong> El sistema puede crear disposiciones automáticamente 
                        basándose en la configuración de tiempos de retención y tipo de disposición definidos en la TRD para cada serie/subserie. 
                        Seleccione los expedientes y haga clic en "Generar Disposiciones" o use "Generar Todas Vencidas" para procesar todos los expedientes vencidos.
                    </AlertDescription>
                </Alert>

                {/* Acciones de selección */}
                {selectedIds.length > 0 && (
                    <div className="flex items-center justify-between bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div className="flex items-center space-x-2">
                            <CheckCircle className="h-5 w-5 text-[#2a3d83]" />
                            <span className="font-medium text-[#2a3d83]">
                                {selectedIds.length} expediente(s) seleccionado(s)
                            </span>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Button variant="outline" onClick={() => setSelectedIds([])}>
                                Deseleccionar
                            </Button>
                            <Button 
                                onClick={generarDisposicionesSeleccionadas}
                                disabled={isGenerating}
                                className="bg-[#2a3d83] hover:bg-[#1e2b5f]"
                            >
                                <Zap className="h-4 w-4 mr-2" />
                                {isGenerating ? 'Generando...' : 'Generar Disposiciones'}
                            </Button>
                        </div>
                    </div>
                )}

                {/* Tabla de Expedientes */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            <span>Expedientes Pendientes ({expedientes.length})</span>
                            <Button variant="ghost" size="sm" onClick={() => router.reload()}>
                                <RefreshCw className="h-4 w-4 mr-2" />
                                Actualizar
                            </Button>
                        </CardTitle>
                        <CardDescription>
                            Lista de expedientes que han cumplido o están por cumplir su tiempo de retención
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {expedientes.length > 0 ? (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-12">
                                                <Checkbox 
                                                    checked={selectedIds.length === expedientes.length}
                                                    onCheckedChange={toggleSelectAll}
                                                />
                                            </TableHead>
                                            <TableHead>Código</TableHead>
                                            <TableHead>Título</TableHead>
                                            <TableHead>Serie / Subserie</TableHead>
                                            <TableHead>Retención</TableHead>
                                            <TableHead>Vencimiento</TableHead>
                                            <TableHead>Disposición Sugerida</TableHead>
                                            <TableHead className="text-right">Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {expedientes.map((exp) => (
                                            <TableRow 
                                                key={exp.id} 
                                                className={`${exp.ya_vencido ? 'bg-red-50' : ''} ${selectedIds.includes(exp.id) ? 'bg-blue-50' : ''}`}
                                            >
                                                <TableCell>
                                                    <Checkbox 
                                                        checked={selectedIds.includes(exp.id)}
                                                        onCheckedChange={() => toggleSelect(exp.id)}
                                                    />
                                                </TableCell>
                                                <TableCell className="font-medium">{exp.codigo}</TableCell>
                                                <TableCell className="max-w-xs">
                                                    <span className="line-clamp-2">{exp.titulo}</span>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-sm">
                                                        <p className="font-medium">{exp.serie || 'Sin serie'}</p>
                                                        {exp.subserie && <p className="text-gray-500">{exp.subserie}</p>}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-sm">
                                                        <p>AG: {exp.info_retencion?.anos_archivo_gestion || 0} años</p>
                                                        <p>AC: {exp.info_retencion?.anos_archivo_central || 0} años</p>
                                                        <p className="text-gray-500">
                                                            Total: {exp.info_retencion?.anos_total_retencion || 0} años
                                                        </p>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        <div className="flex items-center space-x-2">
                                                            <Calendar className="h-4 w-4 text-gray-400" />
                                                            <span className={exp.ya_vencido ? 'text-red-600 font-medium' : ''}>
                                                                {formatearFecha(exp.fecha_eliminacion)}
                                                            </span>
                                                        </div>
                                                        {exp.ya_vencido ? (
                                                            <Badge variant="destructive" className="text-xs">
                                                                <AlertTriangle className="h-3 w-3 mr-1" />
                                                                ¡Vencido!
                                                            </Badge>
                                                        ) : exp.dias_para_disposicion <= 30 ? (
                                                            <Badge variant="outline" className="text-xs border-orange-300 text-orange-700">
                                                                <Clock className="h-3 w-3 mr-1" />
                                                                En {exp.dias_para_disposicion} días
                                                            </Badge>
                                                        ) : (
                                                            <span className="text-xs text-gray-500">
                                                                En {exp.dias_para_disposicion} días
                                                            </span>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {exp.tipo_disposicion_sugerido ? (
                                                        <Badge variant="outline" className={tipoColors[exp.tipo_disposicion_sugerido] || 'bg-gray-100'}>
                                                            {tipoLabels[exp.tipo_disposicion_sugerido] || exp.tipo_disposicion_sugerido}
                                                        </Badge>
                                                    ) : (
                                                        <span className="text-gray-400 text-sm">Sin configurar</span>
                                                    )}
                                                    {exp.info_retencion?.origen_disposicion && (
                                                        <p className="text-xs text-gray-400 mt-1">
                                                            Desde: {exp.info_retencion.origen_disposicion}
                                                        </p>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end space-x-2">
                                                        <Button size="sm" variant="outline" className="text-[#2a3d83]" asChild>
                                                            <Link href={`/admin/disposiciones/create?expediente_id=${exp.id}`}>
                                                                <Plus className="h-4 w-4 mr-1" />
                                                                Manual
                                                            </Link>
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        ) : (
                            <div className="text-center py-12">
                                <CheckCircle className="h-12 w-12 text-green-500 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">
                                    ¡Todo al día!
                                </h3>
                                <p className="text-gray-500">
                                    No hay expedientes pendientes de disposición en los próximos {diasAnticipacion} días.
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
