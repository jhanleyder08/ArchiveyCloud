import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
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
    AlertTriangle,
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
        niveles: { value: string; label: string; }[];
        padres_disponibles: any[];
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
    
    const { data, setData, put, processing, errors, reset } = useForm<FormData>({
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
                                        placeholder="Ej: F.01, S.01.01"
                                        className={errors.codigo ? 'border-red-500' : ''}
                                        required
                                    />
                                    {errors.codigo && (
                                        <p className="text-sm text-red-600 mt-1">{errors.codigo}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="nivel">Nivel Jerárquico *</Label>
                                    <Select 
                                        value={(data.nivel || 1).toString()} 
                                        onValueChange={(value) => {
                                            setData('nivel', parseInt(value));
                                            setData('padre_id', '');
                                        }}
                                    >
                                        <SelectTrigger id="nivel" className={errors.nivel ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Seleccionar nivel" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {opciones.niveles.map((nivel) => (
                                                <SelectItem key={nivel.value} value={nivel.value}>
                                                    {nivel.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.nivel && (
                                        <p className="text-sm text-red-600 mt-1">{errors.nivel}</p>
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
                                    placeholder="Nombre descriptivo del CCD"
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
                                    placeholder="Descripción detallada del elemento"
                                    rows={3}
                                    className={errors.descripcion ? 'border-red-500' : ''}
                                />
                                {errors.descripcion && (
                                    <p className="text-sm text-red-600 mt-1">{errors.descripcion}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Información Organizacional</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="entidad">Entidad *</Label>
                                    <Input
                                        id="entidad"
                                        type="text"
                                        value={data.entidad}
                                        onChange={(e) => setData('entidad', e.target.value)}
                                        placeholder="Nombre de la entidad"
                                        className={errors.entidad ? 'border-red-500' : ''}
                                        required
                                    />
                                    {errors.entidad && (
                                        <p className="text-sm text-red-600 mt-1">{errors.entidad}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="dependencia">Dependencia</Label>
                                    <Input
                                        id="dependencia"
                                        type="text"
                                        value={data.dependencia}
                                        onChange={(e) => setData('dependencia', e.target.value)}
                                        placeholder="Dependencia responsable"
                                        className={errors.dependencia ? 'border-red-500' : ''}
                                    />
                                    {errors.dependencia && (
                                        <p className="text-sm text-red-600 mt-1">{errors.dependencia}</p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Estructura Jerárquica</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="padre_id">Elemento Padre</Label>
                                    <Select 
                                        value={data.padre_id} 
                                        onValueChange={(value) => setData('padre_id', value)}
                                    >
                                        <SelectTrigger id="padre_id" className={errors.padre_id ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Sin elemento padre (raíz)" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="null">Sin elemento padre (raíz)</SelectItem>
                                            {padresDisponibles.map((padre) => (
                                                <SelectItem key={padre.id} value={padre.id.toString()}>
                                                    {padre.codigo} - {padre.nombre}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.padre_id && (
                                        <p className="text-sm text-red-600 mt-1">{errors.padre_id}</p>
                                    )}
                                    {data.nivel > 1 && padresDisponibles.length === 0 && (
                                        <p className="text-sm text-amber-600 mt-1">
                                            <AlertTriangle className="h-4 w-4 inline mr-1" />
                                            No hay elementos padre disponibles para este nivel
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="orden_jerarquico">Orden Jerárquico</Label>
                                    <Input
                                        id="orden_jerarquico"
                                        type="number"
                                        min="1"
                                        value={data.orden_jerarquico}
                                        onChange={(e) => setData('orden_jerarquico', parseInt(e.target.value) || 1)}
                                        className={errors.orden_jerarquico ? 'border-red-500' : ''}
                                    />
                                    {errors.orden_jerarquico && (
                                        <p className="text-sm text-red-600 mt-1">{errors.orden_jerarquico}</p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Configuración y Notas</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="estado">Estado *</Label>
                                    <Select 
                                        value={data.estado} 
                                        onValueChange={(value) => setData('estado', value)}
                                    >
                                        <SelectTrigger id="estado" className={errors.estado ? 'border-red-500' : ''}>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {opciones.estados.map((estado) => (
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

                                <div className="flex items-center space-x-2">
                                    <input
                                        id="activo"
                                        type="checkbox"
                                        checked={data.activo}
                                        onChange={(e) => setData('activo', e.target.checked)}
                                        className="rounded border-gray-300"
                                    />
                                    <Label htmlFor="activo">Elemento Activo</Label>
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="alcance">Alcance</Label>
                                <Textarea
                                    id="alcance"
                                    value={data.alcance}
                                    onChange={(e) => setData('alcance', e.target.value)}
                                    placeholder="Alcance y cobertura del elemento"
                                    rows={2}
                                    className={errors.alcance ? 'border-red-500' : ''}
                                />
                                {errors.alcance && (
                                    <p className="text-sm text-red-600 mt-1">{errors.alcance}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="notas">Notas</Label>
                                <Textarea
                                    id="notas"
                                    value={data.notas}
                                    onChange={(e) => setData('notas', e.target.value)}
                                    placeholder="Notas adicionales y observaciones"
                                    rows={3}
                                    className={errors.notas ? 'border-red-500' : ''}
                                />
                                {errors.notas && (
                                    <p className="text-sm text-red-600 mt-1">{errors.notas}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Información de auditoría */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Información de Auditoría</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                                <div>
                                    <Label className="text-gray-500">Fecha de Creación</Label>
                                    <p>{new Date(ccd.created_at).toLocaleString('es-ES')}</p>
                                </div>
                                <div>
                                    <Label className="text-gray-500">Última Modificación</Label>
                                    <p>{new Date(ccd.updated_at).toLocaleString('es-ES')}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Botones de acción */}
                    <div className="flex justify-end gap-3 pt-6">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit('/admin/ccd')}
                            disabled={processing || isSubmitting}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing || isSubmitting}
                            className="bg-[#2a3d83] hover:bg-[#1e2b5f] flex items-center gap-2"
                        >
                            <Save className="h-4 w-4" />
                            {processing || isSubmitting ? 'Guardando...' : 'Actualizar CCD'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
