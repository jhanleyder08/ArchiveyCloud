import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { 
    ArrowLeft, 
    Settings, 
    Save, 
    Mail, 
    MessageSquare,
    Clock,
    Shield,
    AlertTriangle,
    CheckCircle,
    Users,
    Send,
    Loader2,
    UserCheck,
    X
} from 'lucide-react';
import axios from 'axios';

interface ConfiguracionActual {
    email_habilitado: boolean;
    sms_habilitado: boolean;
    resumen_diario_hora: string;
    throttling_email: number;
    throttling_sms: number;
    destinatarios_resumen: number[];
    ambiente: string;
    mail_driver: string;
    queue_connection: string;
}

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Props {
    configuracion_actual: ConfiguracionActual;
    usuarios_admin: Usuario[];
}

export default function ServiciosExternosConfiguracion({ configuracion_actual, usuarios_admin }: Props) {
    const [config, setConfig] = useState<ConfiguracionActual>(configuracion_actual);
    const [saving, setSaving] = useState(false);
    const [sendingResumenes, setSendingResumenes] = useState(false);
    const [result, setResult] = useState<{success: boolean, message: string} | null>(null);

    const handleSave = async () => {
        setSaving(true);
        setResult(null);

        try {
            const response = await axios.post('/admin/servicios-externos/configuracion', config);
            
            setResult({
                success: true,
                message: 'Configuración actualizada exitosamente'
            });
        } catch (error: any) {
            setResult({
                success: false,
                message: error.response?.data?.message || 'Error actualizando configuración'
            });
        } finally {
            setSaving(false);
        }
    };

    const handleForzarResumenes = async () => {
        setSendingResumenes(true);
        setResult(null);

        try {
            const response = await axios.post('/admin/servicios-externos/forzar-resumenes');
            
            setResult({
                success: true,
                message: 'Resúmenes enviados exitosamente a todos los usuarios administrativos'
            });
        } catch (error: any) {
            setResult({
                success: false,
                message: error.response?.data?.message || 'Error enviando resúmenes'
            });
        } finally {
            setSendingResumenes(false);
        }
    };

    const updateConfig = (key: keyof ConfiguracionActual, value: any) => {
        setConfig(prev => ({
            ...prev,
            [key]: value
        }));
    };

    const toggleDestinatario = (userId: number) => {
        const destinatarios = config.destinatarios_resumen || [];
        const isSelected = destinatarios.includes(userId);
        
        if (isSelected) {
            updateConfig('destinatarios_resumen', destinatarios.filter(id => id !== userId));
        } else {
            updateConfig('destinatarios_resumen', [...destinatarios, userId]);
        }
    };

    return (
        <AppLayout>
            <Head title="Configuración - Servicios Externos - ArchiveyCloud" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href="/admin/servicios-externos">
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Configuración de Servicios Externos</h1>
                            <p className="text-gray-600 mt-1">
                                Configurar parámetros de email, SMS y notificaciones automáticas
                            </p>
                        </div>
                    </div>
                    <Settings className="h-8 w-8 text-blue-600" />
                </div>

                {/* Resultado */}
                {result && (
                    <Alert className={result.success ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'}>
                        <div className="flex items-center space-x-2">
                            {result.success ? (
                                <CheckCircle className="h-4 w-4 text-green-600" />
                            ) : (
                                <AlertTriangle className="h-4 w-4 text-red-600" />
                            )}
                            <AlertDescription className={result.success ? 'text-green-800' : 'text-red-800'}>
                                {result.message}
                            </AlertDescription>
                        </div>
                    </Alert>
                )}

                <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    {/* Configuración de Servicios */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Shield className="h-5 w-5 text-blue-600" />
                                <span>Configuración de Servicios</span>
                            </CardTitle>
                            <CardDescription>
                                Habilitar o deshabilitar servicios de notificación
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Email */}
                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <div className="space-y-1">
                                        <div className="flex items-center space-x-2">
                                            <Mail className="h-4 w-4 text-blue-600" />
                                            <Label className="text-sm font-medium">Servicio de Email</Label>
                                        </div>
                                        <p className="text-xs text-gray-500">
                                            Notificaciones por correo electrónico
                                        </p>
                                    </div>
                                    <Switch
                                        checked={config.email_habilitado}
                                        onCheckedChange={(checked) => updateConfig('email_habilitado', checked)}
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <Label className="text-xs text-gray-600">Driver actual</Label>
                                        <Badge variant="outline" className="mt-1">{config.mail_driver}</Badge>
                                    </div>
                                    <div>
                                        <Label className="text-xs text-gray-600">Queue</Label>
                                        <Badge variant="outline" className="mt-1">{config.queue_connection}</Badge>
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            {/* SMS */}
                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <div className="space-y-1">
                                        <div className="flex items-center space-x-2">
                                            <MessageSquare className="h-4 w-4 text-green-600" />
                                            <Label className="text-sm font-medium">Servicio de SMS</Label>
                                        </div>
                                        <p className="text-xs text-gray-500">
                                            Mensajes de texto para alertas críticas
                                        </p>
                                    </div>
                                    <Switch
                                        checked={config.sms_habilitado}
                                        onCheckedChange={(checked) => updateConfig('sms_habilitado', checked)}
                                    />
                                </div>
                                <div className="text-sm">
                                    <Badge variant="outline" className="bg-orange-50 text-orange-700">
                                        Ambiente: {config.ambiente}
                                    </Badge>
                                    {config.ambiente === 'local' && (
                                        <p className="text-xs text-orange-600 mt-1">
                                            En desarrollo los SMS se simulan
                                        </p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Configuración de Throttling */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Shield className="h-5 w-5 text-orange-600" />
                                <span>Límites y Throttling</span>
                            </CardTitle>
                            <CardDescription>
                                Controlar la frecuencia de envío de notificaciones
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="space-y-4">
                                <div>
                                    <Label htmlFor="throttling-email" className="text-sm font-medium">
                                        Límite de Emails por hora (por usuario)
                                    </Label>
                                    <Input
                                        id="throttling-email"
                                        type="number"
                                        min="1"
                                        max="20"
                                        value={config.throttling_email}
                                        onChange={(e) => updateConfig('throttling_email', parseInt(e.target.value))}
                                        className="mt-1"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Recomendado: 5 emails por hora
                                    </p>
                                </div>

                                <div>
                                    <Label htmlFor="throttling-sms" className="text-sm font-medium">
                                        Límite de SMS por día (por usuario)
                                    </Label>
                                    <Input
                                        id="throttling-sms"
                                        type="number"
                                        min="1"
                                        max="10"
                                        value={config.throttling_sms}
                                        onChange={(e) => updateConfig('throttling_sms', parseInt(e.target.value))}
                                        className="mt-1"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Recomendado: 3 SMS por día (solo críticos)
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Configuración de Resúmenes */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Clock className="h-5 w-5 text-purple-600" />
                                <span>Resúmenes Diarios</span>
                            </CardTitle>
                            <CardDescription>
                                Configurar el envío automático de resúmenes diarios
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div>
                                <Label htmlFor="hora-resumen" className="text-sm font-medium">
                                    Hora de envío (formato 24h)
                                </Label>
                                <Select
                                    value={config.resumen_diario_hora}
                                    onValueChange={(value) => updateConfig('resumen_diario_hora', value)}
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="06:00">06:00 AM</SelectItem>
                                        <SelectItem value="07:00">07:00 AM</SelectItem>
                                        <SelectItem value="08:00">08:00 AM</SelectItem>
                                        <SelectItem value="09:00">09:00 AM</SelectItem>
                                        <SelectItem value="10:00">10:00 AM</SelectItem>
                                        <SelectItem value="18:00">06:00 PM</SelectItem>
                                        <SelectItem value="19:00">07:00 PM</SelectItem>
                                        <SelectItem value="20:00">08:00 PM</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p className="text-xs text-gray-500 mt-1">
                                    Los resúmenes se envían solo si hay actividad pendiente
                                </p>
                            </div>

                            <div className="pt-4 border-t">
                                <Label className="text-sm font-medium">Destinatarios de Resúmenes</Label>
                                <p className="text-xs text-gray-500 mt-1 mb-3">
                                    Selecciona usuarios específicos para recibir resúmenes diarios
                                </p>
                                
                                <div className="space-y-2 max-h-32 overflow-y-auto">
                                    {usuarios_admin.map((usuario) => {
                                        const isSelected = (config.destinatarios_resumen || []).includes(usuario.id);
                                        return (
                                            <div 
                                                key={usuario.id} 
                                                className={`flex items-center justify-between p-2 rounded-md border cursor-pointer transition-colors ${
                                                    isSelected 
                                                        ? 'bg-blue-50 border-blue-200' 
                                                        : 'bg-gray-50 border-gray-200 hover:bg-gray-100'
                                                }`}
                                                onClick={() => toggleDestinatario(usuario.id)}
                                            >
                                                <div className="flex items-center space-x-2">
                                                    <UserCheck className={`h-3 w-3 ${isSelected ? 'text-blue-600' : 'text-gray-400'}`} />
                                                    <span className="text-sm">{usuario.name}</span>
                                                    <Badge variant="outline" className="text-xs">{usuario.email}</Badge>
                                                </div>
                                                {isSelected && (
                                                    <CheckCircle className="h-4 w-4 text-blue-600" />
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                                
                                <div className="mt-2 flex items-center justify-between text-xs text-gray-500">
                                    <span>
                                        {(config.destinatarios_resumen || []).length} de {usuarios_admin.length} seleccionados
                                    </span>
                                    {(config.destinatarios_resumen || []).length === 0 && (
                                        <span className="text-orange-600">
                                            ⚠️ Sin destinatarios específicos, se enviará a todos los admin
                                        </span>
                                    )}
                                </div>
                            </div>

                            <Button
                                onClick={handleForzarResumenes}
                                disabled={sendingResumenes}
                                className="w-full"
                                variant="outline"
                            >
                                {sendingResumenes ? (
                                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                ) : (
                                    <Send className="h-4 w-4 mr-2" />
                                )}
                                Enviar Resúmenes Ahora
                            </Button>
                        </CardContent>
                    </Card>

                    {/* Información del Sistema */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <AlertTriangle className="h-5 w-5 text-yellow-600" />
                                <span>Información del Sistema</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-3 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Cron Jobs:</span>
                                    <Badge variant="outline">Configurado</Badge>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Queue Workers:</span>
                                    <Badge variant="outline">{config.queue_connection}</Badge>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Ambiente:</span>
                                    <Badge variant={config.ambiente === 'production' ? 'default' : 'secondary'}>
                                        {config.ambiente}
                                    </Badge>
                                </div>
                            </div>

                            <Separator />

                            <div className="space-y-2 text-xs text-gray-500">
                                <p><strong>Notas importantes:</strong></p>
                                <ul className="space-y-1 ml-4 list-disc">
                                    <li>Los cambios requieren restart del queue worker</li>
                                    <li>SMS solo funciona en producción con API key válida</li>
                                    <li>Los límites de throttling usan caché de Laravel</li>
                                    <li>Los resúmenes se procesan por cron job diario</li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Botón de guardar */}
                <div className="flex justify-end">
                    <Button
                        onClick={handleSave}
                        disabled={saving}
                        size="lg"
                    >
                        {saving ? (
                            <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                        ) : (
                            <Save className="h-4 w-4 mr-2" />
                        )}
                        Guardar Configuración
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
