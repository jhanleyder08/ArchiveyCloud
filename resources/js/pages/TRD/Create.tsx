import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { ArrowLeft, Save, FileText } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Separator } from '@/components/ui/separator';

interface TrdTemplate {
    id: number;
    name: string;
    description: string;
    category: string;
    template_data: any;
    is_active: boolean;
}

interface Props {
    templates: TrdTemplate[];
}

export default function Create({ templates }: Props) {
    const [selectedTemplate, setSelectedTemplate] = useState<TrdTemplate | null>(null);
    const [useTemplate, setUseTemplate] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        code: '',
        entity_name: '',
        entity_code: '',
        effective_date: '',
        expiry_date: '',
        template_id: ''
    });

    const handleTemplateSelect = (templateId: string) => {
        if (templateId) {
            const template = templates.find(t => t.id.toString() === templateId);
            setSelectedTemplate(template || null);
            setData('template_id', templateId);
            setUseTemplate(true);
        } else {
            setSelectedTemplate(null);
            setData('template_id', '');
            setUseTemplate(false);
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/trd');
    };

    const templatesByCategory = templates.reduce((acc, template) => {
        if (!acc[template.category]) {
            acc[template.category] = [];
        }
        acc[template.category].push(template);
        return acc;
    }, {} as Record<string, TrdTemplate[]>);

    const categoryLabels: Record<string, string> = {
        gobierno: 'Entidades Gubernamentales',
        educacion: 'Instituciones Educativas',
        salud: 'Entidades de Salud',
        empresarial: 'Empresas Privadas'
    };

    return (
        <AppLayout>
            <Head title="Crear TRD" />
            
            <div className="flex items-center gap-4 mb-6">
                <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => router.get('/trd')}
                >
                    <ArrowLeft className="w-4 h-4 mr-2" />
                    Volver
                </Button>
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Crear Nueva TRD
                    </h2>
                    <p className="text-sm text-gray-600">
                        Tabla de Retención Documental
                    </p>
                </div>
            </div>

            <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form onSubmit={submit} className="space-y-6">
                    {/* Plantillas Predefinidas */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="w-5 h-5" />
                                Plantillas Predefinidas
                            </CardTitle>
                            <CardDescription>
                                Seleccione una plantilla para acelerar la creación de su TRD
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Select onValueChange={handleTemplateSelect}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar plantilla (opcional)" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Sin plantilla</SelectItem>
                                    {Object.entries(templatesByCategory).map(([category, categoryTemplates]) => (
                                        <React.Fragment key={category}>
                                            <div className="px-2 py-1 text-sm font-medium text-gray-500">
                                                {categoryLabels[category] || category}
                                            </div>
                                            {categoryTemplates.map((template) => (
                                                <SelectItem key={template.id} value={template.id.toString()}>
                                                    <div>
                                                        <div className="font-medium">{template.name}</div>
                                                        <div className="text-sm text-gray-500">{template.description}</div>
                                                    </div>
                                                </SelectItem>
                                            ))}
                                        </React.Fragment>
                                    ))}
                                </SelectContent>
                            </Select>
                        </CardContent>
                    </Card>

                    {/* Información Básica */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Información Básica</CardTitle>
                            <CardDescription>
                                Complete los datos generales de la Tabla de Retención Documental
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
                                        placeholder="Ej: TRD Alcaldía Municipal 2024"
                                    />
                                    {errors.name && (
                                        <p className="text-sm text-red-600">{errors.name}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="code">Código de la TRD *</Label>
                                    <Input
                                        id="code"
                                        type="text"
                                        value={data.code}
                                        onChange={(e) => setData('code', e.target.value)}
                                        className={errors.code ? 'border-red-500' : ''}
                                        placeholder="Ej: TRD-AM-2024"
                                    />
                                    {errors.code && (
                                        <p className="text-sm text-red-600">{errors.code}</p>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="entity_name">Nombre de la Entidad *</Label>
                                    <Input
                                        id="entity_name"
                                        type="text"
                                        value={data.entity_name}
                                        onChange={(e) => setData('entity_name', e.target.value)}
                                        className={errors.entity_name ? 'border-red-500' : ''}
                                        placeholder="Ej: Alcaldía Municipal de Bogotá"
                                    />
                                    {errors.entity_name && (
                                        <p className="text-sm text-red-600">{errors.entity_name}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="entity_code">Código de la Entidad *</Label>
                                    <Input
                                        id="entity_code"
                                        type="text"
                                        value={data.entity_code}
                                        onChange={(e) => setData('entity_code', e.target.value)}
                                        className={errors.entity_code ? 'border-red-500' : ''}
                                        placeholder="Ej: AM-BOG"
                                    />
                                    {errors.entity_code && (
                                        <p className="text-sm text-red-600">{errors.entity_code}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Descripción</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setData('description', e.target.value)}
                                    rows={3}
                                    placeholder="Descripción opcional de la TRD"
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

                    <Separator />

                    {/* Botones de Acción */}
                    <div className="flex justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.get('/trd')}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="w-4 h-4 mr-2" />
                            {processing ? 'Creando...' : 'Crear TRD'}
                        </Button>
                    </div>

                    {useTemplate && selectedTemplate && (
                        <Alert>
                            <AlertDescription>
                                <strong>Nota:</strong> Al crear la TRD con la plantilla "{selectedTemplate.name}", 
                                se incluirá la estructura predefinida que podrá modificar posteriormente.
                            </AlertDescription>
                        </Alert>
                    )}
                </form>
            </div>
        </AppLayout>
    );
}
