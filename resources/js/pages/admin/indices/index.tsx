import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { 
    Search, 
    Database,
    FileText,
    FolderOpen,
    Filter,
    RotateCcw,
    Download,
    BarChart3,
    Shield,
    Clock,
    HardDrive,
    Star,
    Archive,
    Plus,
    Eye,
    Trash2,
    RefreshCw,
    AlertTriangle,
    ChevronDown,
    FileSpreadsheet,
    FileImage
} from 'lucide-react';

interface IndiceElectronico {
    id: number;
    tipo_entidad: string;
    entidad_id: number;
    codigo_completo: string;
    titulo: string;
    descripcion: string;
    serie_documental: string;
    nivel_acceso: string;
    estado_conservacion: string;
    cantidad_folios: number;
    tamaño_formateado: string;
    es_vital: boolean;
    es_historico: boolean;
    fecha_indexacion: string;
    es_reciente: boolean;
    necesita_actualizacion: boolean;
    etiqueta_nivel_acceso: string;
    etiqueta_estado_conservacion: string;
}

interface Estadisticas {
    total_indices: number;
    por_tipo: Record<string, number>;
    documentos_vitales: number;
    documentos_historicos: number;
    tamaño_total: string;
    indices_recientes: number;
    indices_desactualizados: number;
}

interface Props {
    indices: {
        data: IndiceElectronico[];
        total: number;
        current_page: number;
        last_page: number;
        per_page: number;
        from: number;
        to: number;
    };
    estadisticas: Estadisticas;
    filtros: any;
    opcionesFiltros: {
        tipos_entidad: Record<string, string>;
        niveles_acceso: Record<string, string>;
        series_documentales: string[];
        estados_conservacion: Record<string, string>;
    };
}

export default function IndicesIndex({ indices, estadisticas, filtros, opcionesFiltros }: Props) {
    const [mostrarFiltros, setMostrarFiltros] = useState(false);
    const [exportandoFormato, setExportandoFormato] = useState<string | null>(null);
    
    const { data, setData, get, processing } = useForm({
        busqueda_texto: filtros.busqueda_texto || '',
        tipo_entidad: filtros.tipo_entidad || '',
        serie_documental: filtros.serie_documental || '',
        nivel_acceso: filtros.nivel_acceso || '',
        fecha_inicio: filtros.fecha_inicio || '',
        fecha_fin: filtros.fecha_fin || '',
        solo_vitales: filtros.solo_vitales || false,
        solo_historicos: filtros.solo_historicos || false,
        orden_por: filtros.orden_por || 'fecha_indexacion',
        direccion: filtros.direccion || 'desc'
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        get('/admin/indices', { preserveState: true });
    };

    const limpiarFiltros = () => {
        setData({
            busqueda_texto: '',
            tipo_entidad: '',
            serie_documental: '',
            nivel_acceso: '',
            fecha_inicio: '',
            fecha_fin: '',
            solo_vitales: false,
            solo_historicos: false,
            orden_por: 'fecha_indexacion',
            direccion: 'desc'
        });
        get('/admin/indices');
    };

    const getNivelAccesoBadge = (nivel: string, etiqueta: string) => {
        const colores = {
            'publico': 'bg-green-100 text-green-800',
            'restringido': 'bg-yellow-100 text-yellow-800',
            'confidencial': 'bg-orange-100 text-orange-800',
            'secreto': 'bg-red-100 text-red-800'
        };
        return <Badge className={colores[nivel as keyof typeof colores] || 'bg-gray-100 text-gray-800'}>{etiqueta}</Badge>;
    };

    const getEstadoConservacionBadge = (estado: string, etiqueta: string) => {
        const colores = {
            'excelente': 'bg-green-100 text-green-800',
            'bueno': 'bg-blue-100 text-blue-800',
            'regular': 'bg-yellow-100 text-yellow-800',
            'malo': 'bg-orange-100 text-orange-800',
            'critico': 'bg-red-100 text-red-800'
        };
        return <Badge className={colores[estado as keyof typeof colores] || 'bg-gray-100 text-gray-800'}>{etiqueta}</Badge>;
    };

    const exportarIndices = async (formato: 'csv' | 'excel' | 'pdf') => {
        setExportandoFormato(formato);
        
        try {
            await router.post('/admin/indices/exportar', {
                formato: formato,
                filtros: data
            }, {
                preserveState: true,
                onSuccess: () => {
                    // El archivo se descarga automáticamente
                },
                onError: (errors) => {
                    console.error('Error al exportar:', errors);
                    alert('Error al exportar los índices. Por favor, inténtelo de nuevo.');
                },
                onFinish: () => {
                    setExportandoFormato(null);
                }
            });
        } catch (error) {
            console.error('Error al exportar:', error);
            alert('Error al exportar los índices. Por favor, inténtelo de nuevo.');
            setExportandoFormato(null);
        }
    };

    const regenerarIndices = () => {
        router.post('/admin/indices/regenerar', {
            tipo: 'todos',
            solo_faltantes: false
        }, {
            preserveState: true,
            onSuccess: () => {
                // Refrescar la página para mostrar los nuevos índices
                router.reload();
            }
        });
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Administración', href: '#' },
            { title: 'Índices Electrónicos', href: '/admin/indices' },
        ]}>
            <Head title="Índices Electrónicos" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-2">
                        <Database className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Índices Electrónicos
                        </h1>
                    </div>
                    <div className="flex gap-3">
                        <Link href="/admin/indices/estadisticas/dashboard">
                            <Button variant="outline">
                                <BarChart3 className="w-4 h-4 mr-2 text-[#2a3d83]" />
                                Estadísticas
                            </Button>
                        </Link>
                        <Button onClick={() => setMostrarFiltros(!mostrarFiltros)} variant="outline">
                            <Filter className="w-4 h-4 mr-2 text-[#2a3d83]" />
                            Filtros Avanzados
                        </Button>
                    </div>
                </div>

                {/* Estadísticas Resumen */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total Índices</p>
                                <p className="text-2xl font-semibold text-gray-900">{estadisticas.total_indices.toLocaleString()}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <Database className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Documentos Vitales</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas.documentos_vitales.toLocaleString()}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <Star className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Valor Histórico</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas.documentos_historicos.toLocaleString()}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <Archive className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Tamaño Total</p>
                                <p className="text-2xl font-semibold text-gray-900">{estadisticas.tamaño_total}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <HardDrive className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Alertas */}
                {estadisticas.indices_desactualizados > 0 && (
                    <Alert className="border-yellow-200 bg-yellow-50">
                        <AlertTriangle className="h-4 w-4 text-[#2a3d83]" />
                        <AlertDescription className="text-yellow-800">
                            Hay <strong>{estadisticas.indices_desactualizados}</strong> índices que necesitan actualización (más de 6 meses sin actualizar).
                        </AlertDescription>
                    </Alert>
                )}

                {/* Filtros */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Search className="w-5 h-5" />
                            Búsqueda y Filtros
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div className="md:col-span-3">
                                    <Label htmlFor="busqueda_texto">Búsqueda de texto completo</Label>
                                    <Input
                                        id="busqueda_texto"
                                        type="text"
                                        placeholder="Buscar por título, descripción, palabras clave..."
                                        value={data.busqueda_texto}
                                        onChange={(e) => setData('busqueda_texto', e.target.value)}
                                    />
                                </div>
                                
                                <div>
                                    <Label htmlFor="tipo_entidad">Tipo de entidad</Label>
                                    <Select value={data.tipo_entidad} onValueChange={(value) => setData('tipo_entidad', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Todos los tipos" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todos los tipos</SelectItem>
                                            {Object.entries(opcionesFiltros.tipos_entidad).map(([key, label]) => (
                                                <SelectItem key={key} value={key}>{label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label htmlFor="nivel_acceso">Nivel de acceso</Label>
                                    <Select value={data.nivel_acceso} onValueChange={(value) => setData('nivel_acceso', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Todos los niveles" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todos los niveles</SelectItem>
                                            {Object.entries(opcionesFiltros.niveles_acceso).map(([key, label]) => (
                                                <SelectItem key={key} value={key}>{label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label htmlFor="serie_documental">Serie documental</Label>
                                    <Select value={data.serie_documental} onValueChange={(value) => setData('serie_documental', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Todas las series" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todas las series</SelectItem>
                                            {opcionesFiltros.series_documentales.map((serie) => (
                                                <SelectItem key={serie} value={serie}>{serie}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            {mostrarFiltros && (
                                <>
                                    <Separator />
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div>
                                            <Label htmlFor="fecha_inicio">Fecha inicio</Label>
                                            <Input
                                                id="fecha_inicio"
                                                type="date"
                                                value={data.fecha_inicio}
                                                onChange={(e) => setData('fecha_inicio', e.target.value)}
                                            />
                                        </div>
                                        
                                        <div>
                                            <Label htmlFor="fecha_fin">Fecha fin</Label>
                                            <Input
                                                id="fecha_fin"
                                                type="date"
                                                value={data.fecha_fin}
                                                onChange={(e) => setData('fecha_fin', e.target.value)}
                                            />
                                        </div>

                                        <div className="flex items-center space-x-2 pt-6">
                                            <input
                                                id="solo_vitales"
                                                type="checkbox"
                                                checked={data.solo_vitales}
                                                onChange={(e) => setData('solo_vitales', e.target.checked)}
                                                className="rounded border-gray-300"
                                            />
                                            <Label htmlFor="solo_vitales">Solo información vital</Label>
                                        </div>

                                        <div className="flex items-center space-x-2 pt-6">
                                            <input
                                                id="solo_historicos"
                                                type="checkbox"
                                                checked={data.solo_historicos}
                                                onChange={(e) => setData('solo_historicos', e.target.checked)}
                                                className="rounded border-gray-300"
                                            />
                                            <Label htmlFor="solo_historicos">Solo valor histórico</Label>
                                        </div>
                                    </div>
                                </>
                            )}

                            <div className="flex gap-3">
                                <Button type="submit" disabled={processing}>
                                    <Search className="w-4 h-4 mr-2" />
                                    {processing ? 'Buscando...' : 'Buscar'}
                                </Button>
                                <Button type="button" variant="outline" onClick={limpiarFiltros}>
                                    <RotateCcw className="w-4 h-4 mr-2" />
                                    Limpiar
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Resultados */}
                <Card>
                    <CardHeader>
                        <div className="flex justify-between items-center">
                            <div>
                                <CardTitle>Resultados de búsqueda</CardTitle>
                                <CardDescription>
                                    {indices.total} índice(s) encontrado(s) - Mostrando {indices.from} a {indices.to}
                                </CardDescription>
                            </div>
                            <div className="flex gap-2">
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="outline" size="sm" disabled={exportandoFormato !== null}>
                                            <Download className="w-4 h-4 mr-1" />
                                            {exportandoFormato ? `Exportando ${exportandoFormato.toUpperCase()}...` : 'Exportar'}
                                            <ChevronDown className="w-3 h-3 ml-1" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem onClick={() => exportarIndices('csv')} disabled={exportandoFormato !== null}>
                                            <FileText className="w-4 h-4 mr-2" />
                                            Exportar CSV
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => exportarIndices('excel')} disabled={exportandoFormato !== null}>
                                            <FileSpreadsheet className="w-4 h-4 mr-2" />
                                            Exportar Excel
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => exportarIndices('pdf')} disabled={exportandoFormato !== null}>
                                            <FileImage className="w-4 h-4 mr-2" />
                                            Exportar PDF
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                                <Button 
                                    variant="outline" 
                                    size="sm" 
                                    onClick={regenerarIndices}
                                    disabled={processing}
                                >
                                    <RefreshCw className={`w-4 h-4 mr-1 ${processing ? 'animate-spin' : ''}`} />
                                    {processing ? 'Regenerando...' : 'Regenerar'}
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {indices.data.length === 0 ? (
                            <div className="text-center py-8">
                                <Database className="w-16 h-16 mx-auto text-[#2a3d83] mb-4" />
                                <p className="text-gray-500">No se encontraron índices con los criterios especificados</p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {indices.data.map((indice) => (
                                    <div key={indice.id} className="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2 mb-2">
                                                    {indice.tipo_entidad === 'expediente' ? 
                                                        <FolderOpen className="w-5 h-5 text-[#2a3d83]" /> : 
                                                        <FileText className="w-5 h-5 text-[#2a3d83]" />
                                                    }
                                                    <h3 className="font-semibold text-gray-900">{indice.titulo}</h3>
                                                    {indice.es_reciente && (
                                                        <Badge variant="outline" className="text-green-600 border-green-300">
                                                            Nuevo
                                                        </Badge>
                                                    )}
                                                    {indice.necesita_actualizacion && (
                                                        <Badge variant="outline" className="text-orange-600 border-orange-300">
                                                            <Clock className="w-3 h-3 mr-1" />
                                                            Desactualizado
                                                        </Badge>
                                                    )}
                                                </div>
                                                
                                                <div className="text-sm text-gray-600 mb-2">
                                                    <span className="font-medium">Código:</span> {indice.codigo_completo} |{' '}
                                                    <span className="font-medium">Serie:</span> {indice.serie_documental || 'Sin clasificar'} |{' '}
                                                    <span className="font-medium">Folios:</span> {indice.cantidad_folios || 'N/A'} |{' '}
                                                    <span className="font-medium">Tamaño:</span> {indice.tamaño_formateado}
                                                </div>

                                                {indice.descripcion && (
                                                    <p className="text-sm text-gray-700 mb-3 line-clamp-2">{indice.descripcion}</p>
                                                )}

                                                <div className="flex items-center gap-2 flex-wrap">
                                                    <Badge variant="outline" className="capitalize">
                                                        {indice.tipo_entidad}
                                                    </Badge>
                                                    {getNivelAccesoBadge(indice.nivel_acceso, indice.etiqueta_nivel_acceso)}
                                                    {getEstadoConservacionBadge(indice.estado_conservacion, indice.etiqueta_estado_conservacion)}
                                                    {indice.es_vital && (
                                                        <Badge className="bg-yellow-100 text-yellow-800">
                                                            <Star className="w-3 h-3 mr-1" />
                                                            Vital
                                                        </Badge>
                                                    )}
                                                    {indice.es_historico && (
                                                        <Badge className="bg-purple-100 text-purple-800">
                                                            <Archive className="w-3 h-3 mr-1" />
                                                            Histórico
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                            
                                            <div className="flex items-center gap-2">
                                                <Link href={`/admin/indices/${indice.id}`}>
                                                    <button className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors">
                                                        <Eye className="w-4 h-4" />
                                                    </button>
                                                </Link>
                                                <button className="p-2 rounded-md text-red-600 hover:text-red-800 hover:bg-red-50 transition-colors">
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Paginación */}
                        {indices.last_page > 1 && (
                            <div className="flex justify-center mt-6">
                                <div className="flex items-center space-x-2">
                                    {Array.from({ length: indices.last_page }, (_, i) => i + 1).map((page) => (
                                        <Link
                                            key={page}
                                            href={`/admin/indices?page=${page}`}
                                            className={`px-3 py-1 rounded ${
                                                page === indices.current_page
                                                    ? 'bg-indigo-600 text-white'
                                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                            }`}
                                        >
                                            {page}
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
