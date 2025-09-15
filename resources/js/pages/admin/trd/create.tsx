import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { FileText, ArrowLeft, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { useState } from 'react';

const breadcrumbItems = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Tablas de Retención Documental', href: '/admin/trd' },
    { title: 'Crear TRD', href: '/admin/trd/create' },
];

interface Props {
    estados: Record<string, string>;
}

export default function AdminTRDCreate({ estados }: Props) {
    const [form, setForm] = useState({
        codigo: '',
        nombre: '',
        descripcion: '',
        justificacion: '',
        version: '1.0',
        estado: 'borrador',
        formato_archivo: 'XML',
        metadatos_asociados: ''
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        // Procesar metadatos si es un JSON válido
        let metadatos = null;
        if (form.metadatos_asociados.trim()) {
            try {
                metadatos = JSON.parse(form.metadatos_asociados);
            } catch (error) {
                setErrors({ metadatos_asociados: 'Formato JSON inválido' });
                setProcessing(false);
                return;
            }
        }

        const formData = {
            ...form,
            metadatos_asociados: metadatos
        };

        router.post('/admin/trd', formData, {
            onError: (errors) => {
                setErrors(errors);
                setProcessing(false);
            },
            onFinish: () => {
                setProcessing(false);
            }
        });
    };

    const handleInputChange = (field: string, value: string) => {
        setForm(prev => ({ ...prev, [field]: value }));
        // Limpiar error del campo si existe
        if (errors[field]) {
            setErrors(prev => ({ ...prev, [field]: '' }));
        }
    };

    return (
        <AppLayout breadcrumbItems={breadcrumbItems}>
            <Head title="Crear TRD - Tablas de Retención Documental" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/admin/trd">
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Crear Nueva TRD</h1>
                            <p className="text-gray-600 mt-1">Complete la información para crear una nueva Tabla de Retención Documental</p>
                        </div>
                    </div>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5 text-[#2a3d83]" />
                                Información Básica
                            </CardTitle>
                            <CardDescription>
                                Datos principales de la Tabla de Retención Documental
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <Label htmlFor="codigo">Código *</Label>
                                    <Input
                                        id="codigo"
                                        type="text"
                                        value={form.codigo}
                                        onChange={(e) => handleInputChange('codigo', e.target.value)}
                                        placeholder="Ej: TRD-2024-001"
                                        required
                                        className={errors.codigo ? 'border-red-500' : ''}
                                    />
                                    {errors.codigo && (
                                        <p className="text-sm text-red-600">{errors.codigo}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="version">Versión *</Label>
                                    <Input
                                        id="version"
                                        type="text"
                                        value={form.version}
                                        onChange={(e) => handleInputChange('version', e.target.value)}
                                        placeholder="Ej: 1.0, 2.1"
                                        required
                                        className={errors.version ? 'border-red-500' : ''}
                                    />
                                    {errors.version && (
                                        <p className="text-sm text-red-600">{errors.version}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="nombre">Nombre de la TRD *</Label>
                                <Input
                                    id="nombre"
                                    type="text"
                                    value={form.nombre}
                                    onChange={(e) => handleInputChange('nombre', e.target.value)}
                                    placeholder="Ej: TRD General Administrativa 2024"
                                    required
                                    className={errors.nombre ? 'border-red-500' : ''}
                                />
                                {errors.nombre && (
                                    <p className="text-sm text-red-600">{errors.nombre}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="descripcion">Descripción</Label>
                                <Textarea
                                    id="descripcion"
                                    value={form.descripcion}
                                    onChange={(e) => handleInputChange('descripcion', e.target.value)}
                                    placeholder="Describa el propósito y alcance de esta TRD"
                                    rows={4}
                                    className={errors.descripcion ? 'border-red-500' : ''}
                                />
                                {errors.descripcion && (
                                    <p className="text-sm text-red-600">{errors.descripcion}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="justificacion">Justificación</Label>
                                <Textarea
                                    id="justificacion"
                                    value={form.justificacion}
                                    onChange={(e) => handleInputChange('justificacion', e.target.value)}
                                    placeholder="Justifique la creación o modificación de esta TRD"
                                    rows={3}
                                    className={errors.justificacion ? 'border-red-500' : ''}
                                />
                                {errors.justificacion && (
                                    <p className="text-sm text-red-600">{errors.justificacion}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Configuración y Estado</CardTitle>
                            <CardDescription>
                                Establezca el estado inicial y configuraciones técnicas
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <Label htmlFor="estado">Estado *</Label>
                                    <Select value={form.estado} onValueChange={(value) => handleInputChange('estado', value)}>
                                        <SelectTrigger className={errors.estado ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Selecciona un estado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(estados).map(([key, value]) => (
                                                <SelectItem key={key} value={key}>
                                                    {value}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.estado && (
                                        <p className="text-sm text-red-600">{errors.estado}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="formato_archivo">Formato de Archivo</Label>
                                    <Select value={form.formato_archivo} onValueChange={(value) => handleInputChange('formato_archivo', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona formato" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="XML">XML</SelectItem>
                                            <SelectItem value="JSON">JSON</SelectItem>
                                            <SelectItem value="PDF">PDF</SelectItem>
                                            <SelectItem value="EXCEL">Excel</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="metadatos_asociados">Metadatos Asociados (JSON)</Label>
                                <Textarea
                                    id="metadatos_asociados"
                                    value={form.metadatos_asociados}
                                    onChange={(e) => handleInputChange('metadatos_asociados', e.target.value)}
                                    placeholder='{"categoria": "administrativa", "nivel_acceso": "publico", "palabras_clave": ["gestion", "documental"]}'
                                    rows={4}
                                    className={`font-mono text-sm ${errors.metadatos_asociados ? 'border-red-500' : ''}`}
                                />
                                <p className="text-xs text-gray-500">
                                    Opcional: Ingrese metadatos adicionales en formato JSON válido
                                </p>
                                {errors.metadatos_asociados && (
                                    <p className="text-sm text-red-600">{errors.metadatos_asociados}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Form Actions */}
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex justify-end gap-4">
                                <Link href="/admin/trd">
                                    <Button type="button" variant="outline" disabled={processing}>
                                        Cancelar
                                    </Button>
                                </Link>
                                <Button 
                                    type="submit" 
                                    className="bg-[#2a3d83] hover:bg-[#1e2b5f]"
                                    disabled={processing}
                                >
                                    {processing ? (
                                        <>
                                            <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                            Creando...
                                        </>
                                    ) : (
                                        <>
                                            <Save className="h-4 w-4 mr-2" />
                                            Crear TRD
                                        </>
                                    )}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>

                {/* Help Information */}
                <Card className="bg-blue-50 border-blue-200">
                    <CardContent className="pt-6">
                        <div className="flex items-start gap-3">
                            <div className="bg-blue-100 rounded-full p-2 flex-shrink-0">
                                <FileText className="h-4 w-4 text-blue-600" />
                            </div>
                            <div>
                                <h3 className="font-medium text-blue-900 mb-2">Información sobre TRDs</h3>
                                <ul className="text-sm text-blue-800 space-y-1">
                                    <li>• El código debe ser único y seguir la nomenclatura establecida</li>
                                    <li>• Las TRDs en estado "Borrador" pueden ser modificadas libremente</li>
                                    <li>• Solo las TRDs "Aprobadas" pueden marcarse como vigentes</li>
                                    <li>• Los metadatos ayudan a la clasificación y búsqueda de la TRD</li>
                                    <li>• Una vez creada, se generará automáticamente un identificador único</li>
                                </ul>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
