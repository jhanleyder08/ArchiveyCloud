import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { 
    ArrowLeft, 
    Upload, 
    FileSpreadsheet, 
    Download, 
    AlertCircle,
    CheckCircle,
    Clock,
    FileText,
    Settings
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription } from '@/components/ui/alert';
// Progress component will be implemented inline

interface TrdImportConfiguration {
    id: number;
    name: string;
    description: string;
    file_type: string;
    column_mappings: any;
    validation_rules: any;
    is_active: boolean;
}

interface ImportLog {
    id: number;
    filename: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    total_records: number;
    processed_records: number;
    success_records: number;
    failed_records: number;
    created_at: string;
}

interface ImportPageProps {
    configurations: TrdImportConfiguration[];
    importLogs?: ImportLog[];
}

export default function Import({ configurations, importLogs = [] }: ImportPageProps) {
    const [activeTab, setActiveTab] = useState('upload');
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [uploadProgress, setUploadProgress] = useState(0);
    const [isUploading, setIsUploading] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        configuration_id: '',
        file: null as File | null,
        entity_name: '',
        entity_code: '',
        trd_name: '',
        trd_description: '',
        override_existing: false
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setSelectedFile(file);
            setData('file', file);
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!data.file || !data.configuration_id) {
            return;
        }

        setIsUploading(true);
        
        const formData = new FormData();
        formData.append('file', data.file);
        formData.append('configuration_id', data.configuration_id);
        formData.append('entity_name', data.entity_name);
        formData.append('entity_code', data.entity_code);
        formData.append('trd_name', data.trd_name);
        formData.append('trd_description', data.trd_description);
        formData.append('override_existing', data.override_existing ? '1' : '0');

        // Simular progreso de carga
        const interval = setInterval(() => {
            setUploadProgress(prev => {
                if (prev >= 90) {
                    clearInterval(interval);
                    return prev;
                }
                return prev + 10;
            });
        }, 200);

        post('/trd/import', {
            onSuccess: () => {
                setUploadProgress(100);
                clearInterval(interval);
                setIsUploading(false);
                reset();
                setSelectedFile(null);
            },
            onError: () => {
                clearInterval(interval);
                setIsUploading(false);
                setUploadProgress(0);
            }
        });
    };

    const downloadTemplate = (configId: number) => {
        window.open(`/trd/import/template/${configId}`, '_blank');
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'completed':
                return <CheckCircle className="w-4 h-4 text-green-600" />;
            case 'failed':
                return <AlertCircle className="w-4 h-4 text-red-600" />;
            case 'processing':
                return <Clock className="w-4 h-4 text-blue-600 animate-spin" />;
            default:
                return <Clock className="w-4 h-4 text-gray-400" />;
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed': return 'bg-green-100 text-green-800';
            case 'failed': return 'bg-red-100 text-red-800';
            case 'processing': return 'bg-blue-100 text-blue-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AppLayout>
            <div className="flex items-center gap-4 mb-6">
                <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => router.get('/trd')}
                >
                    <ArrowLeft className="w-4 h-4 mr-2" />
                    Volver a TRD
                </Button>
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Importar TRD
                        </h2>
                        <p className="text-sm text-gray-600">
                            Importe Tablas de Retención Documental desde archivos CSV o Excel
                        </p>
                    </div>
            </div>
            <Head title="Importar TRD" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
                        <TabsList className="grid w-full grid-cols-3">
                            <TabsTrigger value="upload">Cargar Archivo</TabsTrigger>
                            <TabsTrigger value="history">Historial</TabsTrigger>
                            <TabsTrigger value="configurations">Configuraciones</TabsTrigger>
                        </TabsList>

                        <TabsContent value="upload" className="space-y-6">
                            {/* Formulario de Carga */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Upload className="w-5 h-5" />
                                        Importar TRD desde Archivo
                                    </CardTitle>
                                    <CardDescription>
                                        Seleccione una configuración y cargue su archivo para importar la estructura TRD
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <form onSubmit={submit} className="space-y-6">
                                        {/* Selección de Configuración */}
                                        <div className="space-y-2">
                                            <Label htmlFor="configuration_id">Configuración de Importación *</Label>
                                            <Select
                                                value={data.configuration_id}
                                                onValueChange={(value) => setData('configuration_id', value)}
                                            >
                                                <SelectTrigger className={errors.configuration_id ? 'border-red-500' : ''}>
                                                    <SelectValue placeholder="Seleccione una configuración" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {configurations.map((config) => (
                                                        <SelectItem key={config.id} value={config.id.toString()}>
                                                            <div className="flex items-center gap-2">
                                                                <FileSpreadsheet className="w-4 h-4" />
                                                                <span>{config.name}</span>
                                                                <Badge variant="outline" className="ml-2">
                                                                    {config.file_type.toUpperCase()}
                                                                </Badge>
                                                            </div>
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors.configuration_id && (
                                                <p className="text-sm text-red-600">{errors.configuration_id}</p>
                                            )}
                                        </div>

                                        {/* Información del TRD */}
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="entity_name">Nombre de la Entidad *</Label>
                                                <Input
                                                    id="entity_name"
                                                    value={data.entity_name}
                                                    onChange={(e) => setData('entity_name', e.target.value)}
                                                    placeholder="Ej: Ministerio de Educación"
                                                    className={errors.entity_name ? 'border-red-500' : ''}
                                                />
                                                {errors.entity_name && (
                                                    <p className="text-sm text-red-600">{errors.entity_name}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="entity_code">Código de la Entidad *</Label>
                                                <Input
                                                    id="entity_code"
                                                    value={data.entity_code}
                                                    onChange={(e) => setData('entity_code', e.target.value)}
                                                    placeholder="Ej: MINED"
                                                    className={errors.entity_code ? 'border-red-500' : ''}
                                                />
                                                {errors.entity_code && (
                                                    <p className="text-sm text-red-600">{errors.entity_code}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="trd_name">Nombre de la TRD *</Label>
                                            <Input
                                                id="trd_name"
                                                value={data.trd_name}
                                                onChange={(e) => setData('trd_name', e.target.value)}
                                                placeholder="Ej: TRD Ministerio de Educación 2024"
                                                className={errors.trd_name ? 'border-red-500' : ''}
                                            />
                                            {errors.trd_name && (
                                                <p className="text-sm text-red-600">{errors.trd_name}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="trd_description">Descripción</Label>
                                            <Input
                                                id="trd_description"
                                                value={data.trd_description}
                                                onChange={(e) => setData('trd_description', e.target.value)}
                                                placeholder="Descripción opcional de la TRD"
                                            />
                                        </div>

                                        {/* Carga de Archivo */}
                                        <div className="space-y-2">
                                            <Label htmlFor="file">Archivo TRD *</Label>
                                            <Input
                                                id="file"
                                                type="file"
                                                accept=".csv,.xlsx,.xls"
                                                onChange={handleFileChange}
                                                className={errors.file ? 'border-red-500' : ''}
                                            />
                                            {errors.file && (
                                                <p className="text-sm text-red-600">{errors.file}</p>
                                            )}
                                            {selectedFile && (
                                                <div className="flex items-center gap-2 text-sm text-green-600">
                                                    <FileText className="w-4 h-4" />
                                                    <span>{selectedFile.name}</span>
                                                    <Badge variant="outline">
                                                        {(selectedFile.size / 1024).toFixed(1)} KB
                                                    </Badge>
                                                </div>
                                            )}
                                        </div>

                                        {/* Progreso de Carga */}
                                        {isUploading && (
                                            <div className="space-y-2">
                                                <div className="flex justify-between text-sm">
                                                    <span>Procesando archivo...</span>
                                                    <span>{uploadProgress}%</span>
                                                </div>
                                                <div className="w-full bg-gray-200 rounded-full h-2">
                                                <div 
                                                    className="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                                    style={{ width: `${uploadProgress}%` }}
                                                ></div>
                                            </div>
                                            </div>
                                        )}

                                        <Separator />

                                        {/* Acciones */}
                                        <div className="flex justify-between items-center">
                                            <div className="flex gap-2">
                                                {data.configuration_id && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => downloadTemplate(parseInt(data.configuration_id))}
                                                    >
                                                        <Download className="w-4 h-4 mr-2" />
                                                        Descargar Plantilla
                                                    </Button>
                                                )}
                                            </div>

                                            <div className="flex gap-2">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() => router.get('/trd')}
                                                >
                                                    Cancelar
                                                </Button>
                                                <Button 
                                                    type="submit" 
                                                    disabled={processing || isUploading || !selectedFile || !data.configuration_id}
                                                >
                                                    <Upload className="w-4 h-4 mr-2" />
                                                    {processing || isUploading ? 'Procesando...' : 'Importar TRD'}
                                                </Button>
                                            </div>
                                        </div>
                                    </form>
                                </CardContent>
                            </Card>

                            {/* Información de la Configuración Seleccionada */}
                            {data.configuration_id && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-lg">Detalles de la Configuración</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        {(() => {
                                            const config = configurations.find(c => c.id.toString() === data.configuration_id);
                                            if (!config) return null;
                                            
                                            return (
                                                <div className="space-y-3">
                                                    <div className="flex items-center gap-2">
                                                        <Settings className="w-4 h-4 text-gray-500" />
                                                        <span className="font-medium">{config.name}</span>
                                                        <Badge variant={config.is_active ? 'default' : 'secondary'}>
                                                            {config.is_active ? 'Activa' : 'Inactiva'}
                                                        </Badge>
                                                    </div>
                                                    <p className="text-sm text-gray-600">{config.description}</p>
                                                    
                                                    {config.column_mappings && (
                                                        <div className="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm">
                                                            <div className="font-medium text-gray-500">Tipo de Archivo:</div>
                                                            <div>{config.file_type.toUpperCase()}</div>
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })()}
                                    </CardContent>
                                </Card>
                            )}
                        </TabsContent>

                        <TabsContent value="history" className="space-y-6">
                            {/* Historial de Importaciones */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Historial de Importaciones</CardTitle>
                                    <CardDescription>
                                        Últimas importaciones realizadas
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {importLogs && importLogs.length > 0 ? (
                                        <div className="space-y-4">
                                            {importLogs.map((log, index) => (
                                                <div key={index} className="flex items-center justify-between p-4 border rounded-lg">
                                                    <div className="flex items-center gap-3">
                                                        {getStatusIcon(log.status)}
                                                        <div>
                                                            <div className="font-medium">{log.filename}</div>
                                                            <div className="text-sm text-gray-500">
                                                                {new Date(log.created_at).toLocaleDateString('es-ES', {
                                                                    year: 'numeric',
                                                                    month: 'long',
                                                                    day: 'numeric',
                                                                    hour: '2-digit',
                                                                    minute: '2-digit'
                                                                })}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div className="flex items-center gap-4">
                                                        <div className="text-right text-sm">
                                                            <div className="font-medium">
                                                                {log.success_records}/{log.total_records} exitosos
                                                            </div>
                                                            <div className="text-gray-500">
                                                                {log.failed_records} errores
                                                            </div>
                                                        </div>
                                                        
                                                        <Badge className={getStatusColor(log.status)}>
                                                            {log.status === 'completed' ? 'Completado' : 
                                                             log.status === 'failed' ? 'Fallido' :
                                                             log.status === 'processing' ? 'Procesando' : 'Pendiente'}
                                                        </Badge>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-center py-8">
                                            <FileSpreadsheet className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                            <p className="text-gray-500 mb-2">No hay importaciones previas</p>
                                            <p className="text-sm text-gray-400">
                                                Las importaciones aparecerán aquí una vez que procese archivos
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="configurations" className="space-y-6">
                            {/* Configuraciones Disponibles */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Configuraciones de Importación</CardTitle>
                                    <CardDescription>
                                        Configuraciones disponibles para importar archivos TRD
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {configurations.length > 0 ? (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            {configurations.map((config) => (
                                                <Card key={config.id} className="border border-gray-200">
                                                    <CardHeader className="pb-3">
                                                        <div className="flex items-center justify-between">
                                                            <CardTitle className="text-base flex items-center gap-2">
                                                                <Settings className="w-4 h-4" />
                                                                {config.name}
                                                            </CardTitle>
                                                            <div className="flex items-center gap-2">
                                                                <Badge variant="outline">
                                                                    {config.file_type.toUpperCase()}
                                                                </Badge>
                                                                <Badge variant={config.is_active ? 'default' : 'secondary'}>
                                                                    {config.is_active ? 'Activa' : 'Inactiva'}
                                                                </Badge>
                                                            </div>
                                                        </div>
                                                    </CardHeader>
                                                    <CardContent className="pt-0">
                                                        <p className="text-sm text-gray-600 mb-3">{config.description}</p>
                                                        
                                                        <div className="flex justify-between items-center">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => downloadTemplate(config.id)}
                                                                disabled={!config.is_active}
                                                            >
                                                                <Download className="w-3 h-3 mr-1" />
                                                                Plantilla
                                                            </Button>
                                                            
                                                            <Button
                                                                variant="default"
                                                                size="sm"
                                                                onClick={() => {
                                                                    setData('configuration_id', config.id.toString());
                                                                    setActiveTab('upload');
                                                                }}
                                                                disabled={!config.is_active}
                                                            >
                                                                Usar
                                                            </Button>
                                                        </div>
                                                    </CardContent>
                                                </Card>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-center py-8">
                                            <Settings className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                            <p className="text-gray-500 mb-2">No hay configuraciones disponibles</p>
                                            <p className="text-sm text-gray-400">
                                                Contacte al administrador para configurar opciones de importación
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </AppLayout>
    );
}
