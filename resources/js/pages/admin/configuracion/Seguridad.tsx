import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { toast } from 'sonner';
import { Shield, ArrowLeft, RefreshCw, Save } from 'lucide-react';

interface ConfiguracionData {
    id: number;
    clave: string;
    valor: string;
    categoria: string;
    descripcion: string;
    tipo: string;
    activo: boolean;
}

interface Props {
    configuraciones: ConfiguracionData[];
    categoria?: string;
    titulo?: string;
}

export default function Seguridad({ configuraciones, categoria = 'seguridad', titulo = 'Seguridad' }: Props) {
    const [configs, setConfigs] = useState<ConfiguracionData[]>(configuraciones);
    const [loading, setLoading] = useState(false);

    const breadcrumbs = [
        { title: 'Administración', href: '/admin' },
        { title: 'Configuración', href: '/admin/configuracion' },
        { title: titulo, href: `/admin/configuracion/${categoria}` },
    ];

    const handleChange = (id: number, field: 'valor' | 'activo', value: string | boolean) => {
        setConfigs(configs.map(config => 
            config.id === id ? { ...config, [field]: value } : config
        ));
    };

    const handleSave = async (config: ConfiguracionData) => {
        setLoading(true);
        try {
            await router.put(`/admin/configuracion/${config.id}`, {
                valor: config.valor,
                activo: config.activo,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Configuración actualizada correctamente');
                },
                onError: () => {
                    toast.error('Error al actualizar la configuración');
                }
            });
        } finally {
            setLoading(false);
        }
    };

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title={titulo} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-2">
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-primary/10 rounded-lg">
                            <Shield className="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">{titulo}</h1>
                            <p className="text-sm text-muted-foreground">
                                Configuración de {titulo.toLowerCase()}
                            </p>
                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit('/admin/configuracion')}
                        >
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Volver
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6">
                    {configs.length === 0 ? (
                        <Card>
                            <CardContent className="p-6">
                                <p className="text-center text-muted-foreground">
                                    No hay configuraciones disponibles para esta categoría
                                </p>
                            </CardContent>
                        </Card>
                    ) : (
                        configs.map((config) => (
                            <Card key={config.id}>
                                <CardHeader>
                                    <div className="flex items-start justify-between">
                                        <div className="space-y-1">
                                            <CardTitle className="text-lg font-medium">
                                                {config.clave}
                                            </CardTitle>
                                            {config.descripcion && (
                                                <CardDescription>
                                                    {config.descripcion}
                                                </CardDescription>
                                            )}
                                        </div>
                                        <Badge variant={config.activo ? 'default' : 'secondary'}>
                                            {config.activo ? 'Activo' : 'Inactivo'}
                                        </Badge>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid gap-4">
                                        {config.tipo === 'boolean' ? (
                                            <div className="flex items-center justify-between">
                                                <Label htmlFor={`valor-${config.id}`}>
                                                    Valor
                                                </Label>
                                                <Switch
                                                    id={`valor-${config.id}`}
                                                    checked={config.valor === 'true' || config.valor === '1'}
                                                    onCheckedChange={(checked) => 
                                                        handleChange(config.id, 'valor', checked ? 'true' : 'false')
                                                    }
                                                />
                                            </div>
                                        ) : (
                                            <div className="space-y-2">
                                                <Label htmlFor={`valor-${config.id}`}>
                                                    Valor
                                                </Label>
                                                <Input
                                                    id={`valor-${config.id}`}
                                                    type={config.tipo === 'number' ? 'number' : 'text'}
                                                    value={config.valor}
                                                    onChange={(e) => 
                                                        handleChange(config.id, 'valor', e.target.value)
                                                    }
                                                    placeholder="Ingrese el valor"
                                                />
                                            </div>
                                        )}

                                        <div className="flex items-center justify-between">
                                            <Label htmlFor={`activo-${config.id}`}>
                                                Estado
                                            </Label>
                                            <Switch
                                                id={`activo-${config.id}`}
                                                checked={config.activo}
                                                onCheckedChange={(checked) => 
                                                    handleChange(config.id, 'activo', checked)
                                                }
                                            />
                                        </div>
                                    </div>

                                    <Separator />

                                    <div className="flex justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => {
                                                const original = configuraciones.find(c => c.id === config.id);
                                                if (original) {
                                                    setConfigs(configs.map(c => 
                                                        c.id === config.id ? original : c
                                                    ));
                                                }
                                            }}
                                        >
                                            <RefreshCw className="h-4 w-4 mr-2" />
                                            Restablecer
                                        </Button>
                                        <Button
                                            size="sm"
                                            onClick={() => handleSave(config)}
                                            disabled={loading}
                                        >
                                            <Save className="h-4 w-4 mr-2" />
                                            Guardar
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))
                    )}
                </div>
            </div>
        </AppSidebarLayout>
    );
}
