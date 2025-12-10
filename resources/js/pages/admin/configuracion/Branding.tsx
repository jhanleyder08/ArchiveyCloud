import React, { useState, useRef } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
// AlertDialog components replaced with Dialog
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
    Monitor,
    Sun,
    Moon
} from 'lucide-react';

interface Configuracion {
    id: number;
    clave: string;
    valor: string;
    categoria: string;
    tipo: string;
    descripcion: string;
    activo: boolean;
}

interface Props {
    configuraciones: Record<string, Configuracion>;
    temas_disponibles: Record<string, string>;
}

export default function ConfiguracionBranding({ 
    configuraciones = {}, 
    temas_disponibles = {} 
}: Props) {
    const [previewMode, setPreviewMode] = useState(false);
    const [uploading, setUploading] = useState<Record<string, boolean>>({});
    const fileInputs = {
        logo_principal: useRef<HTMLInputElement>(null),
        logo_secundario: useRef<HTMLInputElement>(null),
        favicon: useRef<HTMLInputElement>(null),
    };

    const [formValues, setFormValues] = useState({
        app_name: configuraciones.app_name?.valor || 'ArchiveyCloud',
        app_description: configuraciones.app_description?.valor || 'Sistema de Gestión Documental',
        color_primario: configuraciones.color_primario?.valor || '#3b82f6',
        color_secundario: configuraciones.color_secundario?.valor || '#64748b',
        tema_predeterminado: configuraciones.tema_predeterminado?.valor || 'light',
    });

    const uploadForm = useForm({
        archivo: null,
        tipo: '',
    });

    const handleInputChange = (key: string, value: string) => {
        setFormValues(prev => ({
            ...prev,
            [key]: value
        }));
    };

    const saveConfiguration = async (clave: string, valor: string) => {
        try {
            await router.put(route('admin.configuracion.actualizar', clave), {
                valor: valor,
                activo: true
            });
            toast.success('Configuración actualizada exitosamente');
        } catch (error) {
            console.error('Error saving configuration:', error);
            toast.error('Error al actualizar configuración');
        }
    };

    const saveAllBrandingConfig = async () => {
        try {
            const promises = Object.entries(formValues).map(([key, value]) => 
                router.put(route('admin.configuracion.actualizar', key), {
                    valor: value,
                    activo: true
                })
            );
            
            await Promise.all(promises);
            toast.success('Configuración de branding guardada exitosamente');
        } catch (error) {
            console.error('Error saving branding config:', error);
            toast.error('Error al guardar configuración de branding');
        }
    };

    const handleFileUpload = async (tipo: 'logo_principal' | 'logo_secundario' | 'favicon') => {
        const file = fileInputs[tipo].current?.files?.[0];
        if (!file) {
            toast.error('Selecciona un archivo');
            return;
        }

        // Validaciones de archivo
        const maxSize = 2 * 1024 * 1024; // 2MB
        if (file.size > maxSize) {
            toast.error('El archivo no puede ser mayor a 2MB');
            return;
        }

        const allowedTypes = tipo === 'favicon' 
            ? ['image/x-icon', 'image/png', 'image/svg+xml'] 
            : ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'];

        if (!allowedTypes.includes(file.type)) {
            toast.error(`Tipo de archivo no válido para ${tipo}`);
            return;
        }

        setUploading(prev => ({ ...prev, [tipo]: true }));

        try {
            const formData = new FormData();
            formData.append('archivo', file);
            formData.append('tipo', tipo);

            await router.post(route('admin.configuracion.branding.upload'), formData, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(`${tipo.replace('_', ' ')} subido exitosamente`);
                    if (fileInputs[tipo].current) {
                        fileInputs[tipo].current.value = '';
                    }
                },
                onError: (errors) => {
                    console.error('Upload errors:', errors);
                    toast.error('Error al subir archivo');
                },
            });
        } catch (error) {
            console.error('Error uploading file:', error);
            toast.error('Error al subir archivo');
        } finally {
            setUploading(prev => ({ ...prev, [tipo]: false }));
        }
    };

    const getFileUrl = (tipo: string) => {
        const config = configuraciones[tipo];
        if (config?.valor) {
            return `/storage/${config.valor}`;
        }
        return null;
    };

    const removeFile = async (tipo: string) => {
        try {
            await router.put(route('admin.configuracion.actualizar', tipo), {
                valor: '',
                activo: true
            });
            toast.success('Archivo eliminado exitosamente');
        } catch (error) {
            console.error('Error removing file:', error);
            toast.error('Error al eliminar archivo');
        }
    };

    const getThemeIcon = (theme: string) => {
        switch (theme) {
            case 'light': return <Sun className="h-4 w-4" />;
            case 'dark': return <Moon className="h-4 w-4" />;
            default: return <Monitor className="h-4 w-4" />;
        }
    };

    const previewStyles = {
        '--primary': formValues.color_primario,
        '--secondary': formValues.color_secundario,
    } as React.CSSProperties;

    return (
        <AppSidebarLayout title="Branding y Personalización">
            <Head title="Branding y Personalización" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" onClick={() => router.get(route('admin.configuracion.index'))}>
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Volver
                        </Button>
                        <div className="space-y-1">
                            <h2 className="text-3xl font-bold tracking-tight">Branding y Personalización</h2>
                            <p className="text-muted-foreground">
                                Personaliza la apariencia, logos y colores del sistema
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={() => setPreviewMode(!previewMode)}>
                            <Eye className="mr-2 h-4 w-4" />
                            {previewMode ? 'Salir Vista Previa' : 'Vista Previa'}
                        </Button>
                        <Button onClick={saveAllBrandingConfig}>
                            <Save className="mr-2 h-4 w-4" />
                            Guardar Todo
                        </Button>
                    </div>
                </div>

                {previewMode && (
                    <Card className="border-orange-500 bg-orange-50 dark:bg-orange-900/20">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Eye className="h-5 w-5" />
                                Vista Previa
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div style={previewStyles} className="p-6 bg-white rounded-lg border shadow-sm space-y-4">
                                <div className="flex items-center gap-4">
                                    {getFileUrl('logo_principal') && (
                                        <img 
                                            src={getFileUrl('logo_principal')} 
                                            alt="Logo Principal" 
                                            className="h-12 w-auto"
                                        />
                                    )}
                                    <div>
                                        <h3 className="text-xl font-bold" style={{ color: formValues.color_primario }}>
                                            {formValues.app_name}
                                        </h3>
                                        <p className="text-sm" style={{ color: formValues.color_secundario }}>
                                            {formValues.app_description}
                                        </p>
                                    </div>
                                </div>
                                
                                <div className="flex gap-2">
                                    <div 
                                        className="w-16 h-8 rounded border"
                                        style={{ backgroundColor: formValues.color_primario }}
                                        title="Color Primario"
                                    />
                                    <div 
                                        className="w-16 h-8 rounded border"
                                        style={{ backgroundColor: formValues.color_secundario }}
                                        title="Color Secundario"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Configuración General */}
                <Card>
                    <CardHeader>
                        <CardTitle>Información General</CardTitle>
                        <CardDescription>
                            Configura el nombre y descripción de la aplicación
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="app_name">Nombre de la Aplicación</Label>
                                <div className="flex gap-2">
                                    <Input
                                        id="app_name"
                                        value={formValues.app_name}
                                        onChange={(e) => handleInputChange('app_name', e.target.value)}
                                        placeholder="ArchiveyCloud"
                                    />
                                    <Button 
                                        size="sm" 
                                        variant="ghost"
                                        onClick={() => saveConfiguration('app_name', formValues.app_name)}
                                    >
                                        <Save className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            
                            <div className="space-y-2">
                                <Label htmlFor="app_description">Descripción</Label>
                                <div className="flex gap-2">
                                    <Input
                                        id="app_description"
                                        value={formValues.app_description}
                                        onChange={(e) => handleInputChange('app_description', e.target.value)}
                                        placeholder="Sistema de Gestión Documental"
                                    />
                                    <Button 
                                        size="sm" 
                                        variant="ghost"
                                        onClick={() => saveConfiguration('app_description', formValues.app_description)}
                                    >
                                        <Save className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Logos y Archivos */}
                <Card>
                    <CardHeader>
                        <CardTitle>Logos y Archivos</CardTitle>
                        <CardDescription>
                            Sube los logos y favicon de tu organización
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-6 md:grid-cols-3">
                            {/* Logo Principal */}
                            <div className="space-y-4">
                                <div className="text-center">
                                    <Label className="text-sm font-medium">Logo Principal</Label>
                                    <p className="text-xs text-muted-foreground">PNG, JPG, SVG (max 2MB)</p>
                                </div>
                                
                                <div className="border-2 border-dashed rounded-lg p-6 text-center">
                                    {getFileUrl('logo_principal') ? (
                                        <div className="space-y-2">
                                            <img 
                                                src={getFileUrl('logo_principal')} 
                                                alt="Logo Principal" 
                                                className="h-16 w-auto mx-auto"
                                            />
                                            <div className="flex gap-2 justify-center">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => fileInputs.logo_principal.current?.click()}
                                                    disabled={uploading.logo_principal}
                                                >
                                                    <Upload className="h-4 w-4 mr-2" />
                                                    Cambiar
                                                </Button>
                                                <AlertDialog>
                                                    <AlertDialogTrigger asChild>
                                                        <Button size="sm" variant="destructive">
                                                            <Trash2 className="h-4 w-4 mr-2" />
                                                            Eliminar
                                                        </Button>
                                                    </AlertDialogTrigger>
                                                    <AlertDialogContent>
                                                        <AlertDialogHeader>
                                                            <AlertDialogTitle>¿Eliminar logo principal?</AlertDialogTitle>
                                                            <AlertDialogDescription>
                                                                Esta acción no se puede deshacer. El logo será eliminado permanentemente.
                                                            </AlertDialogDescription>
                                                        </AlertDialogHeader>
                                                        <AlertDialogFooter>
                                                            <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                                            <AlertDialogAction
                                                                onClick={() => removeFile('logo_principal')}
                                                                className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                            >
                                                                Eliminar
                                                            </AlertDialogAction>
                                                        </AlertDialogFooter>
                                                    </AlertDialogContent>
                                                </AlertDialog>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="space-y-2">
                                            <Image className="h-12 w-12 mx-auto text-muted-foreground" />
                                            <p className="text-sm text-muted-foreground">No hay logo configurado</p>
                                            <Button
                                                onClick={() => fileInputs.logo_principal.current?.click()}
                                                disabled={uploading.logo_principal}
                                            >
                                                {uploading.logo_principal ? (
                                                    <>
                                                        <RefreshCw className="h-4 w-4 mr-2 animate-spin" />
                                                        Subiendo...
                                                    </>
                                                ) : (
                                                    <>
                                                        <Upload className="h-4 w-4 mr-2" />
                                                        Subir Logo
                                                    </>
                                                )}
                                            </Button>
                                        </div>
                                    )}
                                </div>
                                
                                <input
                                    ref={fileInputs.logo_principal}
                                    type="file"
                                    accept="image/png,image/jpeg,image/jpg,image/svg+xml"
                                    className="hidden"
                                    onChange={() => handleFileUpload('logo_principal')}
                                />
                            </div>

                            {/* Logo Secundario */}
                            <div className="space-y-4">
                                <div className="text-center">
                                    <Label className="text-sm font-medium">Logo Secundario</Label>
                                    <p className="text-xs text-muted-foreground">PNG, JPG, SVG (max 2MB)</p>
                                </div>
                                
                                <div className="border-2 border-dashed rounded-lg p-6 text-center">
                                    {getFileUrl('logo_secundario') ? (
                                        <div className="space-y-2">
                                            <img 
                                                src={getFileUrl('logo_secundario')} 
                                                alt="Logo Secundario" 
                                                className="h-16 w-auto mx-auto"
                                            />
                                            <div className="flex gap-2 justify-center">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => fileInputs.logo_secundario.current?.click()}
                                                    disabled={uploading.logo_secundario}
                                                >
                                                    <Upload className="h-4 w-4 mr-2" />
                                                    Cambiar
                                                </Button>
                                                <AlertDialog>
                                                    <AlertDialogTrigger asChild>
                                                        <Button size="sm" variant="destructive">
                                                            <Trash2 className="h-4 w-4 mr-2" />
                                                            Eliminar
                                                        </Button>
                                                    </AlertDialogTrigger>
                                                    <AlertDialogContent>
                                                        <AlertDialogHeader>
                                                            <AlertDialogTitle>¿Eliminar logo secundario?</AlertDialogTitle>
                                                            <AlertDialogDescription>
                                                                Esta acción no se puede deshacer. El logo será eliminado permanentemente.
                                                            </AlertDialogDescription>
                                                        </AlertDialogHeader>
                                                        <AlertDialogFooter>
                                                            <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                                            <AlertDialogAction
                                                                onClick={() => removeFile('logo_secundario')}
                                                                className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                            >
                                                                Eliminar
                                                            </AlertDialogAction>
                                                        </AlertDialogFooter>
                                                    </AlertDialogContent>
                                                </AlertDialog>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="space-y-2">
                                            <Image className="h-12 w-12 mx-auto text-muted-foreground" />
                                            <p className="text-sm text-muted-foreground">No hay logo configurado</p>
                                            <Button
                                                onClick={() => fileInputs.logo_secundario.current?.click()}
                                                disabled={uploading.logo_secundario}
                                            >
                                                {uploading.logo_secundario ? (
                                                    <>
                                                        <RefreshCw className="h-4 w-4 mr-2 animate-spin" />
                                                        Subiendo...
                                                    </>
                                                ) : (
                                                    <>
                                                        <Upload className="h-4 w-4 mr-2" />
                                                        Subir Logo
                                                    </>
                                                )}
                                            </Button>
                                        </div>
                                    )}
                                </div>
                                
                                <input
                                    ref={fileInputs.logo_secundario}
                                    type="file"
                                    accept="image/png,image/jpeg,image/jpg,image/svg+xml"
                                    className="hidden"
                                    onChange={() => handleFileUpload('logo_secundario')}
                                />
                            </div>

                            {/* Favicon */}
                            <div className="space-y-4">
                                <div className="text-center">
                                    <Label className="text-sm font-medium">Favicon</Label>
                                    <p className="text-xs text-muted-foreground">ICO, PNG, SVG (max 2MB)</p>
                                </div>
                                
                                <div className="border-2 border-dashed rounded-lg p-6 text-center">
                                    {getFileUrl('favicon') ? (
                                        <div className="space-y-2">
                                            <img 
                                                src={getFileUrl('favicon')} 
                                                alt="Favicon" 
                                                className="h-8 w-8 mx-auto"
                                            />
                                            <div className="flex gap-2 justify-center">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => fileInputs.favicon.current?.click()}
                                                    disabled={uploading.favicon}
                                                >
                                                    <Upload className="h-4 w-4 mr-2" />
                                                    Cambiar
                                                </Button>
                                                <AlertDialog>
                                                    <AlertDialogTrigger asChild>
                                                        <Button size="sm" variant="destructive">
                                                            <Trash2 className="h-4 w-4 mr-2" />
                                                            Eliminar
                                                        </Button>
                                                    </AlertDialogTrigger>
                                                    <AlertDialogContent>
                                                        <AlertDialogHeader>
                                                            <AlertDialogTitle>¿Eliminar favicon?</AlertDialogTitle>
                                                            <AlertDialogDescription>
                                                                Esta acción no se puede deshacer. El favicon será eliminado permanentemente.
                                                            </AlertDialogDescription>
                                                        </AlertDialogHeader>
                                                        <AlertDialogFooter>
                                                            <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                                            <AlertDialogAction
                                                                onClick={() => removeFile('favicon')}
                                                                className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                            >
                                                                Eliminar
                                                            </AlertDialogAction>
                                                        </AlertDialogFooter>
                                                    </AlertDialogContent>
                                                </AlertDialog>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="space-y-2">
                                            <Image className="h-8 w-8 mx-auto text-muted-foreground" />
                                            <p className="text-sm text-muted-foreground">No hay favicon configurado</p>
                                            <Button
                                                size="sm"
                                                onClick={() => fileInputs.favicon.current?.click()}
                                                disabled={uploading.favicon}
                                            >
                                                {uploading.favicon ? (
                                                    <>
                                                        <RefreshCw className="h-4 w-4 mr-2 animate-spin" />
                                                        Subiendo...
                                                    </>
                                                ) : (
                                                    <>
                                                        <Upload className="h-4 w-4 mr-2" />
                                                        Subir Favicon
                                                    </>
                                                )}
                                            </Button>
                                        </div>
                                    )}
                                </div>
                                
                                <input
                                    ref={fileInputs.favicon}
                                    type="file"
                                    accept="image/x-icon,image/png,image/svg+xml"
                                    className="hidden"
                                    onChange={() => handleFileUpload('favicon')}
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
                            Personaliza los colores del sistema y el tema predeterminado
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="color_primario">Color Primario</Label>
                                <div className="flex gap-2">
                                    <Input
                                        id="color_primario"
                                        type="color"
                                        value={formValues.color_primario}
                                        onChange={(e) => handleInputChange('color_primario', e.target.value)}
                                        className="w-16 h-10"
                                    />
                                    <Input
                                        value={formValues.color_primario}
                                        onChange={(e) => handleInputChange('color_primario', e.target.value)}
                                        placeholder="#3b82f6"
                                        className="flex-1"
                                    />
                                    <Button 
                                        size="sm" 
                                        variant="ghost"
                                        onClick={() => saveConfiguration('color_primario', formValues.color_primario)}
                                    >
                                        <Save className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="color_secundario">Color Secundario</Label>
                                <div className="flex gap-2">
                                    <Input
                                        id="color_secundario"
                                        type="color"
                                        value={formValues.color_secundario}
                                        onChange={(e) => handleInputChange('color_secundario', e.target.value)}
                                        className="w-16 h-10"
                                    />
                                    <Input
                                        value={formValues.color_secundario}
                                        onChange={(e) => handleInputChange('color_secundario', e.target.value)}
                                        placeholder="#64748b"
                                        className="flex-1"
                                    />
                                    <Button 
                                        size="sm" 
                                        variant="ghost"
                                        onClick={() => saveConfiguration('color_secundario', formValues.color_secundario)}
                                    >
                                        <Save className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label>Tema Predeterminado</Label>
                            <div className="flex gap-2">
                                <Select 
                                    value={formValues.tema_predeterminado} 
                                    onValueChange={(value) => handleInputChange('tema_predeterminado', value)}
                                >
                                    <SelectTrigger className="flex-1">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(temas_disponibles).map(([key, label]) => (
                                            <SelectItem key={key} value={key}>
                                                <div className="flex items-center gap-2">
                                                    {getThemeIcon(key)}
                                                    {label}
                                                </div>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Button 
                                    size="sm" 
                                    variant="ghost"
                                    onClick={() => saveConfiguration('tema_predeterminado', formValues.tema_predeterminado)}
                                >
                                    <Save className="h-4 w-4" />
                                </Button>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Los usuarios pueden cambiar su tema individual en sus preferencias
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppSidebarLayout>
    );
}
