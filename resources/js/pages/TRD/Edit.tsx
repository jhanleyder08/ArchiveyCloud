import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { 
    ArrowLeft, 
    Save, 
    Plus, 
    Trash2, 
    GripVertical,
    Building,
    FileText,
    Hash
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface TrdSubseries {
    id?: number;
    subseries_code: string;
    subseries_name: string;
    description: string;
    document_type: string;
    retention_archive_management: number;
    retention_central_archive: number;
    final_disposition: 'conservation_total' | 'selection' | 'elimination';
    access_restrictions?: string;
    procedure?: string;
    order_index: number;
}

interface TrdSeries {
    id?: number;
    series_code: string;
    series_name: string;
    description: string;
    order_index: number;
    subseries: TrdSubseries[];
}

interface TrdSection {
    id?: number;
    section_code: string;
    section_name: string;
    description: string;
    order_index: number;
    series: TrdSeries[];
}

interface Props {
    trd: {
        id: number;
        name: string;
        description?: string;
        code: string;
        entity_name: string;
        entity_code: string;
        version: string;
        status: string;
        effective_date?: string;
        expiry_date?: string;
        sections: TrdSection[];
    };
}

export default function Edit({ trd }: Props) {
    const [activeTab, setActiveTab] = useState('basic');
    
    const { data, setData, put, processing, errors, reset } = useForm({
        name: trd.name,
        description: trd.description || '',
        entity_name: trd.entity_name,
        entity_code: trd.entity_code,
        effective_date: trd.effective_date || '',
        expiry_date: trd.expiry_date || '',
        sections: trd.sections || [],
        change_notes: ''
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/trd/${trd.id}`);
    };

    // Funciones para manejar secciones
    const addSection = () => {
        const newSection: TrdSection = {
            section_code: '',
            section_name: '',
            description: '',
            order_index: data.sections.length,
            series: []
        };
        setData('sections', [...data.sections, newSection]);
    };

    const updateSection = (index: number, field: keyof TrdSection, value: any) => {
        const updatedSections = [...data.sections];
        updatedSections[index] = { ...updatedSections[index], [field]: value };
        setData('sections', updatedSections);
    };

    const removeSection = (index: number) => {
        const updatedSections = data.sections.filter((_, i) => i !== index);
        setData('sections', updatedSections);
    };

    // Funciones para manejar series
    const addSeries = (sectionIndex: number) => {
        const newSeries: TrdSeries = {
            series_code: '',
            series_name: '',
            description: '',
            order_index: data.sections[sectionIndex].series.length,
            subseries: []
        };
        const updatedSections = [...data.sections];
        updatedSections[sectionIndex].series.push(newSeries);
        setData('sections', updatedSections);
    };

    const updateSeries = (sectionIndex: number, seriesIndex: number, field: keyof TrdSeries, value: any) => {
        const updatedSections = [...data.sections];
        updatedSections[sectionIndex].series[seriesIndex] = {
            ...updatedSections[sectionIndex].series[seriesIndex],
            [field]: value
        };
        setData('sections', updatedSections);
    };

    const removeSeries = (sectionIndex: number, seriesIndex: number) => {
        const updatedSections = [...data.sections];
        updatedSections[sectionIndex].series = updatedSections[sectionIndex].series.filter((_, i) => i !== seriesIndex);
        setData('sections', updatedSections);
    };

    // Funciones para manejar subseries
    const addSubseries = (sectionIndex: number, seriesIndex: number) => {
        const newSubseries: TrdSubseries = {
            subseries_code: '',
            subseries_name: '',
            description: '',
            document_type: '',
            retention_archive_management: 0,
            retention_central_archive: 0,
            final_disposition: 'conservation_total',
            access_restrictions: '',
            procedure: '',
            order_index: data.sections[sectionIndex].series[seriesIndex].subseries.length
        };
        const updatedSections = [...data.sections];
        updatedSections[sectionIndex].series[seriesIndex].subseries.push(newSubseries);
        setData('sections', updatedSections);
    };

    const updateSubseries = (sectionIndex: number, seriesIndex: number, subseriesIndex: number, field: keyof TrdSubseries, value: any) => {
        const updatedSections = [...data.sections];
        updatedSections[sectionIndex].series[seriesIndex].subseries[subseriesIndex] = {
            ...updatedSections[sectionIndex].series[seriesIndex].subseries[subseriesIndex],
            [field]: value
        };
        setData('sections', updatedSections);
    };

    const removeSubseries = (sectionIndex: number, seriesIndex: number, subseriesIndex: number) => {
        const updatedSections = [...data.sections];
        updatedSections[sectionIndex].series[seriesIndex].subseries = 
            updatedSections[sectionIndex].series[seriesIndex].subseries.filter((_, i) => i !== subseriesIndex);
        setData('sections', updatedSections);
    };

    return (
        <AppLayout>
            <div className="flex items-center gap-4 mb-6">
                <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => router.get(`/trd/${trd.id}`)}
                >
                    <ArrowLeft className="w-4 h-4 mr-2" />
                    Volver
                </Button>
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Editar TRD: {trd.name}
                    </h2>
                    <p className="text-sm text-gray-600">
                        Código: {trd.code} • Versión {trd.version}
                    </p>
                </div>
            </div>
            <Head title={`Editar TRD - ${trd.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-6">
                        <Tabs value={activeTab} onValueChange={setActiveTab}>
                            <TabsList className="grid w-full grid-cols-2">
                                <TabsTrigger value="basic">Información Básica</TabsTrigger>
                                <TabsTrigger value="structure">Estructura TRD</TabsTrigger>
                            </TabsList>

                            <TabsContent value="basic" className="space-y-6">
                                {/* Información Básica */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Información General</CardTitle>
                                        <CardDescription>
                                            Datos generales de la Tabla de Retención Documental
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="name">Nombre de la TRD *</Label>
                                                <Input
                                                    id="name"
                                                    type="text"
                                                    value={data.name}
                                                    onChange={(e) => setData('name', e.target.value)}
                                                    className={errors.name ? 'border-red-500' : ''}
                                                />
                                                {errors.name && (
                                                    <p className="text-sm text-red-600">{errors.name}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="entity_name">Entidad *</Label>
                                                <Input
                                                    id="entity_name"
                                                    type="text"
                                                    value={data.entity_name}
                                                    onChange={(e) => setData('entity_name', e.target.value)}
                                                    className={errors.entity_name ? 'border-red-500' : ''}
                                                />
                                                {errors.entity_name && (
                                                    <p className="text-sm text-red-600">{errors.entity_name}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="description">Descripción</Label>
                                            <Textarea
                                                id="description"
                                                value={data.description}
                                                onChange={(e) => setData('description', e.target.value)}
                                                rows={3}
                                            />
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="effective_date">Fecha de Vigencia</Label>
                                                <Input
                                                    id="effective_date"
                                                    type="date"
                                                    value={data.effective_date}
                                                    onChange={(e) => setData('effective_date', e.target.value)}
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="expiry_date">Fecha de Vencimiento</Label>
                                                <Input
                                                    id="expiry_date"
                                                    type="date"
                                                    value={data.expiry_date}
                                                    onChange={(e) => setData('expiry_date', e.target.value)}
                                                />
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            <TabsContent value="structure" className="space-y-6">
                                {/* Estructura TRD */}
                                <Card>
                                    <CardHeader>
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <CardTitle>Estructura de la TRD</CardTitle>
                                                <CardDescription>
                                                    Configure las secciones, series y subseries documentales
                                                </CardDescription>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={addSection}
                                            >
                                                <Plus className="w-4 h-4 mr-2" />
                                                Agregar Sección
                                            </Button>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        {data.sections.length > 0 ? (
                                            <div className="space-y-6">
                                                {data.sections.map((section, sectionIndex) => (
                                                    <Card key={sectionIndex} className="border-l-4 border-blue-500">
                                                        <CardHeader>
                                                            <div className="flex items-center justify-between">
                                                                <CardTitle className="flex items-center gap-2 text-lg">
                                                                    <Building className="w-4 h-4" />
                                                                    Sección {sectionIndex + 1}
                                                                </CardTitle>
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => removeSection(sectionIndex)}
                                                                    className="text-red-600"
                                                                >
                                                                    <Trash2 className="w-4 h-4" />
                                                                </Button>
                                                            </div>
                                                        </CardHeader>
                                                        <CardContent className="space-y-4">
                                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                                <div className="space-y-2">
                                                                    <Label>Código de la Sección</Label>
                                                                    <Input
                                                                        value={section.section_code}
                                                                        onChange={(e) => updateSection(sectionIndex, 'section_code', e.target.value)}
                                                                        placeholder="Ej: 100"
                                                                    />
                                                                </div>
                                                                <div className="space-y-2">
                                                                    <Label>Nombre de la Sección</Label>
                                                                    <Input
                                                                        value={section.section_name}
                                                                        onChange={(e) => updateSection(sectionIndex, 'section_name', e.target.value)}
                                                                        placeholder="Ej: Oficina de Planeación"
                                                                    />
                                                                </div>
                                                            </div>
                                                            
                                                            <div className="space-y-2">
                                                                <Label>Descripción</Label>
                                                                <Textarea
                                                                    value={section.description}
                                                                    onChange={(e) => updateSection(sectionIndex, 'description', e.target.value)}
                                                                    rows={2}
                                                                />
                                                            </div>

                                                            <Separator />

                                                            {/* Series */}
                                                            <div className="space-y-4">
                                                                <div className="flex items-center justify-between">
                                                                    <h4 className="font-medium flex items-center gap-2">
                                                                        <FileText className="w-4 h-4" />
                                                                        Series Documentales
                                                                    </h4>
                                                                    <Button
                                                                        type="button"
                                                                        variant="outline"
                                                                        size="sm"
                                                                        onClick={() => addSeries(sectionIndex)}
                                                                    >
                                                                        <Plus className="w-3 h-3 mr-1" />
                                                                        Serie
                                                                    </Button>
                                                                </div>

                                                                {section.series.map((series, seriesIndex) => (
                                                                    <Card key={seriesIndex} className="ml-4 border-l-2 border-green-400">
                                                                        <CardHeader className="pb-3">
                                                                            <div className="flex items-center justify-between">
                                                                                <CardTitle className="text-base">
                                                                                    Serie {seriesIndex + 1}
                                                                                </CardTitle>
                                                                                <Button
                                                                                    type="button"
                                                                                    variant="ghost"
                                                                                    size="sm"
                                                                                    onClick={() => removeSeries(sectionIndex, seriesIndex)}
                                                                                    className="text-red-600"
                                                                                >
                                                                                    <Trash2 className="w-3 h-3" />
                                                                                </Button>
                                                                            </div>
                                                                        </CardHeader>
                                                                        <CardContent className="space-y-3">
                                                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                                                <Input
                                                                                    value={series.series_code}
                                                                                    onChange={(e) => updateSeries(sectionIndex, seriesIndex, 'series_code', e.target.value)}
                                                                                    placeholder="Código serie (Ej: 100.01)"
                                                                                />
                                                                                <Input
                                                                                    value={series.series_name}
                                                                                    onChange={(e) => updateSeries(sectionIndex, seriesIndex, 'series_name', e.target.value)}
                                                                                    placeholder="Nombre de la serie"
                                                                                />
                                                                            </div>

                                                                            {/* Subseries */}
                                                                            <div className="space-y-3">
                                                                                <div className="flex items-center justify-between">
                                                                                    <span className="text-sm font-medium flex items-center gap-1">
                                                                                        <Hash className="w-3 h-3" />
                                                                                        Subseries
                                                                                    </span>
                                                                                    <Button
                                                                                        type="button"
                                                                                        variant="outline"
                                                                                        size="sm"
                                                                                        onClick={() => addSubseries(sectionIndex, seriesIndex)}
                                                                                    >
                                                                                        <Plus className="w-3 h-3 mr-1" />
                                                                                        Subserie
                                                                                    </Button>
                                                                                </div>

                                                                                {series.subseries.map((subseries, subseriesIndex) => (
                                                                                    <div key={subseriesIndex} className="ml-4 p-3 border border-gray-200 rounded-lg space-y-2">
                                                                                        <div className="flex items-center justify-between">
                                                                                            <Badge variant="outline">
                                                                                                Subserie {subseriesIndex + 1}
                                                                                            </Badge>
                                                                                            <Button
                                                                                                type="button"
                                                                                                variant="ghost"
                                                                                                size="sm"
                                                                                                onClick={() => removeSubseries(sectionIndex, seriesIndex, subseriesIndex)}
                                                                                                className="text-red-600"
                                                                                            >
                                                                                                <Trash2 className="w-3 h-3" />
                                                                                            </Button>
                                                                                        </div>
                                                                                        
                                                                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                                                            <Input
                                                                                                value={subseries.subseries_code}
                                                                                                onChange={(e) => updateSubseries(sectionIndex, seriesIndex, subseriesIndex, 'subseries_code', e.target.value)}
                                                                                                placeholder="Código (Ej: 100.01.01)"
                                                                                                className="text-sm"
                                                                                            />
                                                                                            <Input
                                                                                                value={subseries.subseries_name}
                                                                                                onChange={(e) => updateSubseries(sectionIndex, seriesIndex, subseriesIndex, 'subseries_name', e.target.value)}
                                                                                                placeholder="Nombre subserie"
                                                                                                className="text-sm"
                                                                                            />
                                                                                        </div>

                                                                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-2">
                                                                                            <Input
                                                                                                value={subseries.document_type}
                                                                                                onChange={(e) => updateSubseries(sectionIndex, seriesIndex, subseriesIndex, 'document_type', e.target.value)}
                                                                                                placeholder="Tipo documental"
                                                                                                className="text-sm"
                                                                                            />
                                                                                            <Input
                                                                                                type="number"
                                                                                                value={subseries.retention_archive_management}
                                                                                                onChange={(e) => updateSubseries(sectionIndex, seriesIndex, subseriesIndex, 'retention_archive_management', parseInt(e.target.value) || 0)}
                                                                                                placeholder="AG (años)"
                                                                                                className="text-sm"
                                                                                            />
                                                                                            <Input
                                                                                                type="number"
                                                                                                value={subseries.retention_central_archive}
                                                                                                onChange={(e) => updateSubseries(sectionIndex, seriesIndex, subseriesIndex, 'retention_central_archive', parseInt(e.target.value) || 0)}
                                                                                                placeholder="AC (años)"
                                                                                                className="text-sm"
                                                                                            />
                                                                                        </div>

                                                                                        <Select
                                                                                            value={subseries.final_disposition}
                                                                                            onValueChange={(value) => updateSubseries(sectionIndex, seriesIndex, subseriesIndex, 'final_disposition', value)}
                                                                                        >
                                                                                            <SelectTrigger className="text-sm">
                                                                                                <SelectValue />
                                                                                            </SelectTrigger>
                                                                                            <SelectContent>
                                                                                                <SelectItem value="conservation_total">Conservación Total (CT)</SelectItem>
                                                                                                <SelectItem value="selection">Selección (S)</SelectItem>
                                                                                                <SelectItem value="elimination">Eliminación (E)</SelectItem>
                                                                                            </SelectContent>
                                                                                        </Select>
                                                                                    </div>
                                                                                ))}
                                                                            </div>
                                                                        </CardContent>
                                                                    </Card>
                                                                ))}
                                                            </div>
                                                        </CardContent>
                                                    </Card>
                                                ))}
                                            </div>
                                        ) : (
                                            <div className="text-center py-8">
                                                <p className="text-gray-500 mb-4">
                                                    No hay secciones definidas. Agregue la primera sección para comenzar.
                                                </p>
                                                <Button type="button" onClick={addSection}>
                                                    <Plus className="w-4 h-4 mr-2" />
                                                    Agregar Primera Sección
                                                </Button>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>
                        </Tabs>

                        {/* Notas de Cambios */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Notas de Cambios</CardTitle>
                                <CardDescription>
                                    Describa los cambios realizados (opcional)
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Textarea
                                    value={data.change_notes}
                                    onChange={(e) => setData('change_notes', e.target.value)}
                                    placeholder="Describa los cambios realizados en esta versión..."
                                    rows={3}
                                />
                            </CardContent>
                        </Card>

                        <Separator />

                        {/* Botones de Acción */}
                        <div className="flex justify-end gap-4">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => router.get(`/trd/${trd.id}`)}
                            >
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing}>
                                <Save className="w-4 h-4 mr-2" />
                                {processing ? 'Guardando...' : 'Guardar Cambios'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
