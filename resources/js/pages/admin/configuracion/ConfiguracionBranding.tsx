import React, { useState, useRef } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { toast } from 'sonner';
import { 
    Palette, 
    Upload, 
    Image, 
    Download, 
    Eye, 
    RefreshCw,
    ArrowLeft,
    Save,
    Trash2,
} from 'lucide-react';

interface ConfiguracionData {
    clave: string;
    valor: string;
    categoria: string;
    descripcion: string;
    tipo: string;
    activo: boolean;
}

interface Props {
    configuraciones: Record<string, ConfiguracionData>;
    logos: {
        principal?: string;
        favicon?: string;
        login?: string;
    };
}

export default function ConfiguracionBranding({ configuraciones, logos }: Props) {
    const breadcrumbs = [
        { title: 'Administración', href: '/admin' },
        { title: 'Configuración', href: '/admin/configuracion' },
        { title: 'Branding y Personalización', href: '/admin/configuracion/branding' },
    ];

    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [uploadProgress, setUploadProgress] = useState(0);
    const [currentUploadType, setCurrentUploadType] = useState<string>('');

    const { data, setData, put, processing } = useForm({
        nombre_aplicacion: configuraciones.app_name?.valor || 'ArchiveyCloud',
        descripcion: configuraciones.app_description?.valor || '',
        color_primario: configuraciones.color_primario?.valor || '#3b82f6',
        color_secundario: configuraciones.color_secundario?.valor || '#6b7280',
        tema_default: configuraciones.tema_predeterminado?.valor || 'light',
        mostrar_logo: configuraciones.show_logo?.valor === 'true',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        put('/admin/configuracion/branding/update', {
            onSuccess: () => {
                toast.success('Configuración de branding guardada exitosamente');
            },
            onError: () => {
                toast.error('Error al guardar la configuración');
            }
        });
    };

    const handleFileUpload = (tipo: 'principal' | 'favicon' | 'login') => {
        setCurrentUploadType(tipo);
        if (fileInputRef.current) {
            fileInputRef.current.click();
        }
    };

    const handleFileSelected = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file) return;

        // Validaciones básicas
        const maxSize = 2 * 1024 * 1024; // 2MB
        if (file.size > maxSize) {
            toast.error('El archivo es demasiado grande. Máximo 2MB.');
            return;
        }

        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'];
        if (!allowedTypes.includes(file.type)) {
            toast.error('Tipo de archivo no permitido. Use JPEG, PNG, GIF, WebP, SVG o ICO.');
            return;
        }

        uploadFile(file, currentUploadType);
    };

    const uploadFile = (file: File, tipo: string) => {
        setUploading(true);
        setUploadProgress(0);

        const formData = new FormData();
        formData.append('archivo', file);
        formData.append('tipo', tipo);

        // Simular progreso de subida
        const interval = setInterval(() => {
            setUploadProgress(prev => {
                if (prev >= 90) {
                    clearInterval(interval);
                    return prev;
                }
                return prev + 10;
            });
        }, 200);

        router.post('/admin/configuracion/branding/upload', formData, {
            onSuccess: () => {
                setUploadProgress(100);
                setTimeout(() => {
                    setUploading(false);
                    setUploadProgress(0);
                    toast.success(`${tipo === 'principal' ? 'Logo principal' : tipo === 'favicon' ? 'Favicon' : 'Logo de login'} subido exitosamente`);
                }, 500);
            },
            onError: () => {
                clearInterval(interval);
                setUploading(false);
                setUploadProgress(0);
                toast.error('Error al subir el archivo');
            }
        });
    };

    const previewLogo = (tipo: 'principal' | 'favicon' | 'login') => {
        const logoUrl = logos[tipo];
        if (logoUrl) {
            window.open(logoUrl, '_blank');
        } else {
            toast.info('No hay logo configurado para esta sección');
        }
    };

    const deleteLogo = (tipo: 'principal' | 'favicon' | 'login') => {
        router.delete(`/admin/configuracion/branding/logo/${tipo}`, {
            onSuccess: () => {
                toast.success('Logo eliminado exitosamente');
            },
            onError: () => {
                toast.error('Error al eliminar el logo');
            }
        });
    };

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Branding y Personalización" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="outline"
                            size="icon"
                            onClick={() => router.visit('/admin/configuracion')}
                        >
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">Branding y Personalización</h1>
                            <p className="text-gray-600 dark:text-gray-400 mt-2">
                                Configura la identidad visual y temas de ArchiveyCloud
                            </p>
                        </div>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Información General */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Palette className="h-5 w-5" />
                                Información General
                            </CardTitle>
                            <CardDescription>
                                Configura el nombre y descripción de la aplicación
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="nombre_aplicacion">Nombre de la Aplicación</Label>
                                    <Input
                                        id="nombre_aplicacion"
                                        type="text"
                                        value={data.nombre_aplicacion}
                                        onChange={(e) => setData('nombre_aplicacion', e.target.value)}
                                        placeholder="ArchiveyCloud"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="descripcion">Descripción</Label>
                                    <Input
                                        id="descripcion"
                                        type="text"
                                        value={data.descripcion}
                                        onChange={(e) => setData('descripcion', e.target.value)}
                                        placeholder="Sistema de Gestión Documental"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Colores y Tema */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Colores y Tema</CardTitle>
                            <CardDescription>
                                Define los colores principales del sistema
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="color_primario">Color Primario</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            id="color_primario"
                                            type="color"
                                            value={data.color_primario}
                                            onChange={(e) => setData('color_primario', e.target.value)}
                                            className="w-16 h-10 p-0 border-0"
                                        />
                                        <Input
                                            type="text"
                                            value={data.color_primario}
                                            onChange={(e) => setData('color_primario', e.target.value)}
                                            placeholder="#3b82f6"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="color_secundario">Color Secundario</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            id="color_secundario"
                                            type="color"
                                            value={data.color_secundario}
                                            onChange={(e) => setData('color_secundario', e.target.value)}
                                            className="w-16 h-10 p-0 border-0"
                                        />
                                        <Input
                                            type="text"
                                            value={data.color_secundario}
                                            onChange={(e) => setData('color_secundario', e.target.value)}
                                            placeholder="#6b7280"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="tema_default">Tema por Defecto</Label>
                                    <Select value={data.tema_default} onValueChange={(value) => setData('tema_default', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar tema" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="light">Claro</SelectItem>
                                            <SelectItem value="dark">Oscuro</SelectItem>
                                            <SelectItem value="auto">Automático</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            {/* Vista previa de colores */}
                            <div className="mt-6 p-4 border rounded-lg">
                                <h4 className="font-medium mb-3">Vista Previa</h4>
                                <div className="flex gap-4 items-center">
                                    <div 
                                        className="w-24 h-12 rounded-lg flex items-center justify-center text-white text-sm font-medium shadow-md"
                                        style={{ backgroundColor: data.color_primario }}
                                    >
                                        Primario
                                    </div>
                                    <div 
                                        className="w-24 h-12 rounded-lg flex items-center justify-center text-white text-sm font-medium shadow-md"
                                        style={{ backgroundColor: data.color_secundario }}
                                    >
                                        Secundario
                                    </div>
                                    <div className="flex-1 p-3 rounded-lg border">
                                        <div className="text-sm font-medium" style={{ color: data.color_primario }}>
                                            {data.nombre_aplicacion}
                                        </div>
                                        <div className="text-xs" style={{ color: data.color_secundario }}>
                                            {data.descripcion || 'Descripción de la aplicación'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Logos */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Image className="h-5 w-5" />
                                Gestión de Logos
                            </CardTitle>
                            <CardDescription>
                                Sube y configura los logos del sistema
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                {/* Logo Principal */}
                                <div className="space-y-4">
                                    <h4 className="font-medium">Logo Principal</h4>
                                    <div className="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                                        {logos.principal ? (
                                            <div className="space-y-2">
                                                <img 
                                                    src={logos.principal} 
                                                    alt="Logo Principal" 
                                                    className="max-h-20 mx-auto"
                                                />
                                                <Badge variant="outline">Configurado</Badge>
                                            </div>
                                        ) : (
                                            <div className="space-y-2">
                                                <Image className="h-12 w-12 mx-auto text-gray-400" />
                                                <p className="text-sm text-gray-500">Sin logo</p>
                                            </div>
                                        )}
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handleFileUpload('principal')}
                                            disabled={uploading}
                                        >
                                            <Upload className="h-4 w-4 mr-1" />
                                            Subir
                                        </Button>
                                        {logos.principal && (
                                            <>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => previewLogo('principal')}
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => deleteLogo('principal')}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </>
                                        )}
                                    </div>
                                </div>

                                {/* Favicon */}
                                <div className="space-y-4">
                                    <h4 className="font-medium">Favicon</h4>
                                    <div className="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                                        {logos.favicon ? (
                                            <div className="space-y-2">
                                                <img 
                                                    src={logos.favicon} 
                                                    alt="Favicon" 
                                                    className="max-h-8 mx-auto"
                                                />
                                                <Badge variant="outline">Configurado</Badge>
                                            </div>
                                        ) : (
                                            <div className="space-y-2">
                                                <div className="h-8 w-8 mx-auto bg-gray-300 rounded"></div>
                                                <p className="text-sm text-gray-500">Sin favicon</p>
                                            </div>
                                        )}
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handleFileUpload('favicon')}
                                            disabled={uploading}
                                        >
                                            <Upload className="h-4 w-4 mr-1" />
                                            Subir
                                        </Button>
                                        {logos.favicon && (
                                            <>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => previewLogo('favicon')}
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => deleteLogo('favicon')}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </>
                                        )}
                                    </div>
                                </div>

                                {/* Logo de Login */}
                                <div className="space-y-4">
                                    <h4 className="font-medium">Logo de Login</h4>
                                    <div className="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                                        {logos.login ? (
                                            <div className="space-y-2">
                                                <img 
                                                    src={logos.login} 
                                                    alt="Logo Login" 
                                                    className="max-h-20 mx-auto"
                                                />
                                                <Badge variant="outline">Configurado</Badge>
                                            </div>
                                        ) : (
                                            <div className="space-y-2">
                                                <Image className="h-12 w-12 mx-auto text-gray-400" />
                                                <p className="text-sm text-gray-500">Sin logo</p>
                                            </div>
                                        )}
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handleFileUpload('login')}
                                            disabled={uploading}
                                        >
                                            <Upload className="h-4 w-4 mr-1" />
                                            Subir
                                        </Button>
                                        {logos.login && (
                                            <>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => previewLogo('login')}
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => deleteLogo('login')}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Upload Progress */}
                            {uploading && (
                                <div className="mt-4 space-y-2">
                                    <div className="flex items-center gap-2">
                                        <RefreshCw className="h-4 w-4 animate-spin" />
                                        <span className="text-sm">Subiendo archivo...</span>
                                    </div>
                                    <Progress value={uploadProgress} className="w-full" />
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit('/admin/configuracion')}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? (
                                <>
                                    <RefreshCw className="h-4 w-4 mr-2 animate-spin" />
                                    Guardando...
                                </>
                            ) : (
                                <>
                                    <Save className="h-4 w-4 mr-2" />
                                    Guardar Configuración
                                </>
                            )}
                        </Button>
                    </div>
                </form>

                {/* Hidden file input */}
                <input
                    ref={fileInputRef}
                    type="file"
                    accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,image/x-icon,.ico,.svg"
                    onChange={handleFileSelected}
                    className="hidden"
                />
            </div>
        </AppSidebarLayout>
    );
}
