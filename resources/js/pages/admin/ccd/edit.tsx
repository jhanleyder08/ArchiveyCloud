import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { 
    ArrowLeft, 
    Save, 
    FolderTree,
    Info
} from 'lucide-react';
import { toast } from 'sonner';
import { useInertiaActions } from '@/hooks/useInertiaActions';

interface CCD {
    id: number;
    codigo: string;
    nombre: string;
    descripcion?: string;
    version: string;
    estado: 'borrador' | 'activo' | 'inactivo' | 'archivado';
    fecha_aprobacion?: string;
    fecha_vigencia_inicio?: string;
    fecha_vigencia_fin?: string;
    aprobado_por?: number;
    vocabulario_controlado?: any;
    metadata?: any;
    created_by: number;
    updated_by?: number;
    created_at: string;
    updated_at: string;
}

interface EditCCDProps {
    ccd: CCD;
    opciones: {
        estados: { value: string; label: string; }[];
    };
}

interface FormData {
    codigo: string;
    nombre: string;
    descripcion: string;
    version: string;
    estado: string;
    fecha_vigencia_inicio: string;
    fecha_vigencia_fin: string;
}

export default function EditCCD({ ccd, opciones }: EditCCDProps) {
    // Hook para acciones sin recarga de página
    const actions = useInertiaActions({
        only: ['ccd'], // Solo recarga el CCD actualizado
    });

    const [isSubmitting, setIsSubmitting] = useState(false);
    
    const { data, setData, processing, errors } = useForm<FormData>({
        codigo: ccd.codigo || '',
        nombre: ccd.nombre || '',
        descripcion: ccd.descripcion || '',
        version: ccd.version || '1.0',
        estado: ccd.estado || 'borrador',
        fecha_vigencia_inicio: ccd.fecha_vigencia_inicio ? ccd.fecha_vigencia_inicio.split('T')[0] : '',
        fecha_vigencia_fin: ccd.fecha_vigencia_fin ? ccd.fecha_vigencia_fin.split('T')[0] : '',
    });

    const breadcrumbItems = [
        { title: 'Inicio', href: '/dashboard' },
        { title: 'Administración', href: '/admin' },
        { title: 'CCD', href: '/admin/ccd' },
        { title: `Editar: ${ccd.codigo}`, href: `/admin/ccd/${ccd.id}/edit` },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        actions.update(`/admin/ccd/${ccd.id}`, data, {
            successMessage: 'Cuadro de Clasificación Documental actualizado exitosamente',
            errorMessage: 'Error al actualizar el CCD. Revisa los campos marcados.',
            onSuccess: () => {
                setIsSubmitting(false);
                // Opcional: navegar de vuelta a la lista
                actions.visit('/admin/ccd');
            },
            onError: (errors) => {
                setIsSubmitting(false);
                console.error('Errores de validación:', errors);
            }
        });
    };

    const estadosOpciones = opciones?.estados || [
        { value: 'borrador', label: 'Borrador' },
        { value: 'activo', label: 'Activo' },
        { value: 'inactivo', label: 'Inactivo' },
        { value: 'archivado', label: 'Archivado' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title={`Editar CCD: ${ccd.codigo}`} />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <FolderTree className="h-6 w-6 text-[#2a3d83]" />
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900">
                                Editar Cuadro de Clasificación Documental
                            </h1>
                            <p className="text-gray-600 mt-1">
                                Código: <span className="font-medium">{ccd.codigo}</span>
                            </p>
                        </div>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => actions.visit('/admin/ccd')}
                        className="flex items-center gap-2"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Volver
                    </Button>
                </div>

                {/* Info Alert */}
                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        Modificando información del Cuadro de Clasificación Documental. Los cambios se aplicarán inmediatamente.
                    </AlertDescription>
                </Alert>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Información Básica</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="codigo">Código *</Label>
                                    <Input
                                        id="codigo"
                                        type="text"
                                        value={data.codigo}
                                        onChange={(e) => setData('codigo', e.target.value)}
                                        placeholder="Ej: CCD-001"
                                        className={errors.codigo ? 'border-red-500' : ''}
                                        required
                                    />
                                    {errors.codigo && (
                                        <p className="text-sm text-red-600 mt-1">{errors.codigo}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="version">Versión *</Label>
                                    <Input
                                        id="version"
                                        type="text"
                                        value={data.version}
                                        onChange={(e) => setData('version', e.target.value)}
                                        placeholder="Ej: 1.0"
                                        className={errors.version ? 'border-red-500' : ''}
                                        required
                                    />
                                    {errors.version && (
                                        <p className="text-sm text-red-600 mt-1">{errors.version}</p>
                                    )}
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="nombre">Nombre *</Label>
                                <Input
                                    id="nombre"
                                    type="text"
                                    value={data.nombre}
                                    onChange={(e) => setData('nombre', e.target.value)}
                                    placeholder="Nombre del CCD"
                                    className={errors.nombre ? 'border-red-500' : ''}
                                    required
                                />
                                {errors.nombre && (
                                    <p className="text-sm text-red-600 mt-1">{errors.nombre}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="descripcion">Descripción</Label>
                                <Textarea
                                    id="descripcion"
                                    value={data.descripcion}
                                    onChange={(e) => setData('descripcion', e.target.value)}
                                    placeholder="Descripción del CCD"
                                    className={errors.descripcion ? 'border-red-500' : ''}
                                    rows={3}
                                />
                                {errors.descripcion && (
                                    <p className="text-sm text-red-600 mt-1">{errors.descripcion}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="estado">Estado *</Label>
                                    <Select 
                                        value={data.estado} 
                                        onValueChange={(value) => setData('estado', value)}
                                    >
                                        <SelectTrigger id="estado" className={errors.estado ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Seleccionar estado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {estadosOpciones.map((estado) => (
                                                <SelectItem key={estado.value} value={estado.value}>
                                                    {estado.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.estado && (
                                        <p className="text-sm text-red-600 mt-1">{errors.estado}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="fecha_vigencia_inicio">Fecha Vigencia Inicio</Label>
                                    <Input
                                        id="fecha_vigencia_inicio"
                                        type="date"
                                        value={data.fecha_vigencia_inicio}
                                        onChange={(e) => setData('fecha_vigencia_inicio', e.target.value)}
                                        className={errors.fecha_vigencia_inicio ? 'border-red-500' : ''}
                                    />
                                    {errors.fecha_vigencia_inicio && (
                                        <p className="text-sm text-red-600 mt-1">{errors.fecha_vigencia_inicio}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="fecha_vigencia_fin">Fecha Vigencia Fin</Label>
                                    <Input
                                        id="fecha_vigencia_fin"
                                        type="date"
                                        value={data.fecha_vigencia_fin}
                                        onChange={(e) => setData('fecha_vigencia_fin', e.target.value)}
                                        className={errors.fecha_vigencia_fin ? 'border-red-500' : ''}
                                    />
                                    {errors.fecha_vigencia_fin && (
                                        <p className="text-sm text-red-600 mt-1">{errors.fecha_vigencia_fin}</p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center justify-between">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => actions.visit('/admin/ccd')}
                        >
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Cancelar
                        </Button>

                        <Button
                            type="submit"
                            disabled={processing || isSubmitting}
                            className="flex items-center gap-2"
                        >
                            <Save className="h-4 w-4" />
                            {isSubmitting ? 'Guardando...' : 'Guardar Cambios'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
