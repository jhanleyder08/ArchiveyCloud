import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    ArrowLeft,
    Database,
    FileText,
    FolderOpen,
    User,
    Calendar,
    MapPin,
    Shield,
    Archive,
    Star,
    HardDrive,
    Hash,
    Clock,
    RefreshCw,
    Download,
    Edit,
    Trash2,
    ExternalLink,
    AlertTriangle,
    CheckCircle,
    Info
} from 'lucide-react';

interface IndiceData {
    id: number;
    tipo_entidad: string;
    entidad_id: number;
    codigo_clasificacion: string;
    titulo: string;
    descripcion: string;
    metadatos: any;
    palabras_clave: string[];
    serie_documental: string;
    subserie_documental: string;
    fecha_inicio: string;
    fecha_fin: string;
    responsable: string;
    ubicacion_fisica: string;
    ubicacion_digital: string;
    nivel_acceso: string;
    estado_conservacion: string;
    cantidad_folios: number;
    formato_archivo: string;
    tamaño_bytes: number;
    hash_integridad: string;
    es_vital: boolean;
    es_historico: boolean;
    fecha_indexacion: string;
    fecha_ultima_actualizacion: string;
    usuario_indexacion: {
        name: string;
        email: string;
    };
    usuario_actualizacion?: {
        name: string;
        email: string;
    };
    tamaño_formateado: string;
    etiqueta_nivel_acceso: string;
    etiqueta_estado_conservacion: string;
    codigo_completo: string;
    periodo_conservacion: string;
    es_reciente: boolean;
    necesita_actualizacion: boolean;
}

interface EntidadRelacionada {
    id: number;
    titulo?: string;
    codigo?: string;
    estado?: string;
    fecha_apertura?: string;
    fecha_cierre?: string;
    responsable?: {
        name: string;
    };
    documentos?: any[];
    expediente?: any;
}

interface Props {
    indice: IndiceData;
    entidadRelacionada: EntidadRelacionada;
}

export default function IndicesShow({ indice, entidadRelacionada }: Props) {
    const { post, processing } = useForm();

    const actualizarIndice = () => {
        post(`/admin/indices/${indice.id}/actualizar`, {
            preserveScroll: true,
        });
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

    const formatearFecha = (fecha: string) => {
        if (!fecha) return 'N/A';
        return new Date(fecha).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    return (
        <AppLayout>
            <Head title={`Índice - ${indice.titulo}`} />
            
            <div className="container mx-auto py-6">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <div className="flex items-center gap-3 mb-2">
                            <Link href="/admin/indices">
                                <Button variant="outline" size="sm">
                                    <ArrowLeft className="w-4 h-4 mr-2" />
                                    Volver
                                </Button>
                            </Link>
                            {indice.tipo_entidad === 'expediente' ? 
                                <FolderOpen className="w-8 h-8 text-blue-600" /> : 
                                <FileText className="w-8 h-8 text-green-600" />
                            }
                        </div>
                        <h1 className="text-3xl font-bold text-gray-900">{indice.titulo}</h1>
                        <p className="text-gray-600 mt-1">
                            Índice electrónico #{indice.id} - {indice.codigo_completo}
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <Button onClick={actualizarIndice} disabled={processing} variant="outline">
                            <RefreshCw className={`w-4 h-4 mr-2 ${processing ? 'animate-spin' : ''}`} />
                            Actualizar
                        </Button>
                        <Button variant="outline">
                            <Download className="w-4 h-4 mr-2" />
                            Exportar
                        </Button>
                    </div>
                </div>

                {/* Alertas y estados */}
                <div className="space-y-3 mb-6">
                    {indice.es_reciente && (
                        <Alert className="border-green-200 bg-green-50">
                            <CheckCircle className="h-4 w-4 text-green-600" />
                            <AlertDescription className="text-green-800">
                                Este índice fue creado recientemente (menos de 30 días).
                            </AlertDescription>
                        </Alert>
                    )}
                    
                    {indice.necesita_actualizacion && (
                        <Alert className="border-yellow-200 bg-yellow-50">
                            <AlertTriangle className="h-4 w-4 text-yellow-600" />
                            <AlertDescription className="text-yellow-800">
                                Este índice necesita actualización (más de 6 meses sin actualizar).
                            </AlertDescription>
                        </Alert>
                    )}
                </div>

                {/* Badges principales */}
                <div className="flex items-center gap-2 flex-wrap mb-6">
                    <Badge variant="outline" className="capitalize text-base px-3 py-1">
                        {indice.tipo_entidad}
                    </Badge>
                    {getNivelAccesoBadge(indice.nivel_acceso, indice.etiqueta_nivel_acceso)}
                    {getEstadoConservacionBadge(indice.estado_conservacion, indice.etiqueta_estado_conservacion)}
                    {indice.es_vital && (
                        <Badge className="bg-yellow-100 text-yellow-800">
                            <Star className="w-4 h-4 mr-1" />
                            Información Vital
                        </Badge>
                    )}
                    {indice.es_historico && (
                        <Badge className="bg-purple-100 text-purple-800">
                            <Archive className="w-4 h-4 mr-1" />
                            Valor Histórico
                        </Badge>
                    )}
                </div>

                <Tabs defaultValue="general" className="space-y-6">
                    <TabsList className="grid w-full grid-cols-4">
                        <TabsTrigger value="general">Información General</TabsTrigger>
                        <TabsTrigger value="clasificacion">Clasificación</TabsTrigger>
                        <TabsTrigger value="metadatos">Metadatos Técnicos</TabsTrigger>
                        <TabsTrigger value="relacionado">Entidad Relacionada</TabsTrigger>
                    </TabsList>

                    {/* Información General */}
                    <TabsContent value="general" className="space-y-6">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Info className="w-5 h-5" />
                                        Información Básica
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Título</Label>
                                        <p className="font-medium">{indice.titulo}</p>
                                    </div>

                                    {indice.descripcion && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Descripción</Label>
                                            <p className="text-gray-700">{indice.descripcion}</p>
                                        </div>
                                    )}

                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Código Completo</Label>
                                        <p className="font-mono text-sm bg-gray-100 px-2 py-1 rounded">{indice.codigo_completo}</p>
                                    </div>

                                    {indice.responsable && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Responsable</Label>
                                            <p className="flex items-center gap-2">
                                                <User className="w-4 h-4" />
                                                {indice.responsable}
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Calendar className="w-5 h-5" />
                                        Fechas y Cronología
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Fecha de Indexación</Label>
                                        <p>{formatearFecha(indice.fecha_indexacion)}</p>
                                    </div>

                                    {indice.fecha_ultima_actualizacion && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Última Actualización</Label>
                                            <p>{formatearFecha(indice.fecha_ultima_actualizacion)}</p>
                                        </div>
                                    )}

                                    {indice.fecha_inicio && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Fecha Inicio</Label>
                                            <p>{formatearFecha(indice.fecha_inicio)}</p>
                                        </div>
                                    )}

                                    {indice.fecha_fin && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Fecha Fin</Label>
                                            <p>{formatearFecha(indice.fecha_fin)}</p>
                                        </div>
                                    )}

                                    {indice.periodo_conservacion && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Período de Conservación</Label>
                                            <p>{indice.periodo_conservacion}</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <MapPin className="w-5 h-5" />
                                        Ubicación
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {indice.ubicacion_fisica && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Ubicación Física</Label>
                                            <p>{indice.ubicacion_fisica}</p>
                                        </div>
                                    )}

                                    {indice.ubicacion_digital && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Ubicación Digital</Label>
                                            <p className="font-mono text-sm break-all">{indice.ubicacion_digital}</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <User className="w-5 h-5" />
                                        Auditoría
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Indexado por</Label>
                                        <p>{indice.usuario_indexacion.name}</p>
                                        <p className="text-sm text-gray-500">{indice.usuario_indexacion.email}</p>
                                    </div>

                                    {indice.usuario_actualizacion && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Actualizado por</Label>
                                            <p>{indice.usuario_actualizacion.name}</p>
                                            <p className="text-sm text-gray-500">{indice.usuario_actualizacion.email}</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    {/* Clasificación */}
                    <TabsContent value="clasificacion" className="space-y-6">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Database className="w-5 h-5" />
                                        Clasificación Archivística
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {indice.serie_documental && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Serie Documental</Label>
                                            <p className="font-medium">{indice.serie_documental}</p>
                                        </div>
                                    )}

                                    {indice.subserie_documental && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Subserie Documental</Label>
                                            <p className="font-medium">{indice.subserie_documental}</p>
                                        </div>
                                    )}

                                    {indice.codigo_clasificacion && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Código de Clasificación</Label>
                                            <p className="font-mono text-sm bg-gray-100 px-2 py-1 rounded">{indice.codigo_clasificacion}</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Shield className="w-5 h-5" />
                                        Seguridad y Acceso
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Nivel de Acceso</Label>
                                        <div className="mt-1">
                                            {getNivelAccesoBadge(indice.nivel_acceso, indice.etiqueta_nivel_acceso)}
                                        </div>
                                    </div>

                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Estado de Conservación</Label>
                                        <div className="mt-1">
                                            {getEstadoConservacionBadge(indice.estado_conservacion, indice.etiqueta_estado_conservacion)}
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Información Vital</Label>
                                            <p className={indice.es_vital ? 'text-yellow-600 font-medium' : 'text-gray-500'}>
                                                {indice.es_vital ? 'Sí' : 'No'}
                                            </p>
                                        </div>
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Valor Histórico</Label>
                                            <p className={indice.es_historico ? 'text-purple-600 font-medium' : 'text-gray-500'}>
                                                {indice.es_historico ? 'Sí' : 'No'}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {indice.palabras_clave && indice.palabras_clave.length > 0 && (
                                <Card className="lg:col-span-2">
                                    <CardHeader>
                                        <CardTitle>Palabras Clave</CardTitle>
                                        <CardDescription>
                                            Keywords generadas automáticamente para facilitar la búsqueda
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="flex flex-wrap gap-2">
                                            {indice.palabras_clave.map((palabra, index) => (
                                                <Badge key={index} variant="outline" className="text-sm">
                                                    {palabra}
                                                </Badge>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            )}
                        </div>
                    </TabsContent>

                    {/* Metadatos Técnicos */}
                    <TabsContent value="metadatos" className="space-y-6">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <HardDrive className="w-5 h-5" />
                                        Información de Archivo
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {indice.cantidad_folios && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Cantidad de Folios</Label>
                                            <p className="font-medium">{indice.cantidad_folios.toLocaleString()}</p>
                                        </div>
                                    )}

                                    {indice.formato_archivo && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Formato de Archivo</Label>
                                            <p className="font-mono text-sm bg-gray-100 px-2 py-1 rounded inline-block">{indice.formato_archivo.toUpperCase()}</p>
                                        </div>
                                    )}

                                    {indice.tamaño_bytes && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Tamaño</Label>
                                            <p className="font-medium">{indice.tamaño_formateado}</p>
                                            <p className="text-sm text-gray-500">{indice.tamaño_bytes.toLocaleString()} bytes</p>
                                        </div>
                                    )}

                                    {indice.hash_integridad && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Hash de Integridad</Label>
                                            <p className="font-mono text-xs bg-gray-100 px-2 py-1 rounded break-all">{indice.hash_integridad}</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {indice.metadatos && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <Hash className="w-5 h-5" />
                                            Metadatos Específicos
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <pre className="text-sm bg-gray-100 p-3 rounded overflow-auto max-h-64">
                                            {JSON.stringify(indice.metadatos, null, 2)}
                                        </pre>
                                    </CardContent>
                                </Card>
                            )}
                        </div>
                    </TabsContent>

                    {/* Entidad Relacionada */}
                    <TabsContent value="relacionado" className="space-y-6">
                        {entidadRelacionada ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        {indice.tipo_entidad === 'expediente' ? 
                                            <FolderOpen className="w-5 h-5 text-blue-600" /> : 
                                            <FileText className="w-5 h-5 text-green-600" />
                                        }
                                        {indice.tipo_entidad === 'expediente' ? 'Expediente' : 'Documento'} Relacionado
                                    </CardTitle>
                                    <CardDescription>
                                        Información de la entidad original indexada
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">
                                            {indice.tipo_entidad === 'expediente' ? 'Número de Expediente' : 'Título del Documento'}
                                        </Label>
                                        <p className="font-medium">
                                            {entidadRelacionada.codigo || entidadRelacionada.titulo}
                                        </p>
                                    </div>

                                    {entidadRelacionada.estado && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Estado</Label>
                                            <Badge variant="outline" className="ml-2 capitalize">
                                                {entidadRelacionada.estado}
                                            </Badge>
                                        </div>
                                    )}

                                    {entidadRelacionada.responsable && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Responsable</Label>
                                            <p>{entidadRelacionada.responsable.name}</p>
                                        </div>
                                    )}

                                    {entidadRelacionada.documentos && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Total Documentos</Label>
                                            <p className="font-medium">{entidadRelacionada.documentos.length}</p>
                                        </div>
                                    )}

                                    <div className="pt-4">
                                        <Link 
                                            href={`/admin/${indice.tipo_entidad === 'expediente' ? 'expedientes' : 'documentos'}/${entidadRelacionada.id}`}
                                            className="inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-800"
                                        >
                                            <ExternalLink className="w-4 h-4" />
                                            Ver {indice.tipo_entidad} completo
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>
                        ) : (
                            <Card>
                                <CardContent className="text-center py-8">
                                    <AlertTriangle className="w-16 h-16 mx-auto text-gray-400 mb-4" />
                                    <p className="text-gray-500">
                                        No se pudo cargar la información de la entidad relacionada.
                                    </p>
                                    <p className="text-sm text-gray-400 mt-2">
                                        La entidad original puede haber sido eliminada o no tener acceso.
                                    </p>
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}

function Label({ className, children, ...props }: { className?: string; children: React.ReactNode }) {
    return (
        <label className={`block text-sm font-medium text-gray-700 ${className || ''}`} {...props}>
            {children}
        </label>
    );
}
