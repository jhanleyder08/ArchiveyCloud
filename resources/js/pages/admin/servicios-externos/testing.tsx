import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { 
    ArrowLeft, 
    Mail, 
    MessageSquare, 
    TestTube, 
    CheckCircle, 
    AlertTriangle,
    User,
    Phone,
    Send,
    Loader2
} from 'lucide-react';
import axios from 'axios';

interface Usuario {
    id: number;
    name: string;
    email: string;
    telefono?: string;
}

interface TestResult {
    success: boolean;
    message: string;
    detalles?: any;
}

interface Props {
    usuarios: Usuario[];
}

export default function ServiciosExternosTesting({ usuarios }: Props) {
    const [selectedUserId, setSelectedUserId] = useState<string>('');
    const [phoneNumber, setPhoneNumber] = useState<string>('');
    const [emailLoading, setEmailLoading] = useState(false);
    const [smsLoading, setSmsLoading] = useState(false);
    const [emailResult, setEmailResult] = useState<TestResult | null>(null);
    const [smsResult, setSmsResult] = useState<TestResult | null>(null);

    const selectedUser = usuarios.find(user => user.id.toString() === selectedUserId);

    const testEmail = async () => {
        if (!selectedUserId) {
            setEmailResult({
                success: false,
                message: 'Por favor selecciona un usuario'
            });
            return;
        }

        setEmailLoading(true);
        setEmailResult(null);

        try {
            const response = await axios.post('/admin/servicios-externos/test-email', {
                user_id: selectedUserId
            });

            setEmailResult(response.data);
        } catch (error: any) {
            setEmailResult({
                success: false,
                message: error.response?.data?.message || 'Error enviando email de prueba'
            });
        } finally {
            setEmailLoading(false);
        }
    };

    const testSms = async () => {
        if (!phoneNumber) {
            setSmsResult({
                success: false,
                message: 'Por favor ingresa un número de teléfono'
            });
            return;
        }

        setSmsLoading(true);
        setSmsResult(null);

        try {
            const response = await axios.post('/admin/servicios-externos/test-sms', {
                telefono: phoneNumber
            });

            setSmsResult(response.data);
        } catch (error: any) {
            setSmsResult({
                success: false,
                message: error.response?.data?.message || 'Error enviando SMS de prueba'
            });
        } finally {
            setSmsLoading(false);
        }
    };

    const renderResult = (result: TestResult | null, type: string) => {
        if (!result) return null;

        return (
            <Alert className={result.success ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'}>
                <div className="flex items-center space-x-2">
                    {result.success ? (
                        <CheckCircle className="h-4 w-4 text-green-600" />
                    ) : (
                        <AlertTriangle className="h-4 w-4 text-red-600" />
                    )}
                    <AlertDescription className={result.success ? 'text-green-800' : 'text-red-800'}>
                        <strong>{type}:</strong> {result.message}
                        {result.detalles && (
                            <div className="mt-2 text-sm space-y-1">
                                {Object.entries(result.detalles).map(([key, value]) => (
                                    <div key={key} className="flex justify-between">
                                        <span className="capitalize">{key.replace('_', ' ')}:</span>
                                        <span className="font-mono text-xs">{String(value)}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </AlertDescription>
                </div>
            </Alert>
        );
    };

    return (
        <AppLayout>
            <Head title="Probar Servicios Externos - ArchiveyCloud" />

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
                            <h1 className="text-3xl font-bold text-gray-900">Probar Servicios Externos</h1>
                            <p className="text-gray-600 mt-1">
                                Envía emails y SMS de prueba para verificar la configuración
                            </p>
                        </div>
                    </div>
                    <TestTube className="h-8 w-8 text-blue-600" />
                </div>

                {/* Información importante */}
                <Alert>
                    <AlertTriangle className="h-4 w-4" />
                    <AlertDescription>
                        <strong>Importante:</strong> Los emails y SMS de prueba se envían realmente a los destinatarios seleccionados. 
                        Usa esta funcionalidad solo para testing en ambientes de desarrollo o con usuarios de prueba.
                    </AlertDescription>
                </Alert>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Test Email */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Mail className="h-5 w-5 text-blue-600" />
                                <span>Probar Email</span>
                            </CardTitle>
                            <CardDescription>
                                Envía un email de prueba a un usuario específico
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="usuario-select">Usuario Destinatario</Label>
                                <Select value={selectedUserId} onValueChange={setSelectedUserId}>
                                    <SelectTrigger id="usuario-select">
                                        <SelectValue placeholder="Selecciona un usuario..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {usuarios.map((usuario) => (
                                            <SelectItem key={usuario.id} value={usuario.id.toString()}>
                                                <div className="flex items-center space-x-2">
                                                    <User className="h-4 w-4" />
                                                    <span>{usuario.name}</span>
                                                    <Badge variant="outline" className="ml-auto">
                                                        {usuario.email}
                                                    </Badge>
                                                </div>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {selectedUser && (
                                <div className="p-3 bg-blue-50 rounded-lg space-y-2">
                                    <div className="flex items-center space-x-2">
                                        <User className="h-4 w-4 text-blue-600" />
                                        <span className="font-medium">{selectedUser.name}</span>
                                    </div>
                                    <div className="flex items-center space-x-2 text-sm text-gray-600">
                                        <Mail className="h-3 w-3" />
                                        <span>{selectedUser.email}</span>
                                    </div>
                                </div>
                            )}

                            <Button 
                                onClick={testEmail} 
                                disabled={emailLoading || !selectedUserId}
                                className="w-full"
                            >
                                {emailLoading ? (
                                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                ) : (
                                    <Send className="h-4 w-4 mr-2" />
                                )}
                                Enviar Email de Prueba
                            </Button>

                            {renderResult(emailResult, 'Email')}

                            <div className="text-xs text-gray-500 space-y-1">
                                <p><strong>Asunto:</strong> 📋 Prueba desde Interfaz Web - ArchiveyCloud</p>
                                <p><strong>Contenido:</strong> Email de prueba enviado desde la interfaz de administración</p>
                                <p><strong>Prioridad:</strong> Media</p>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Test SMS */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <MessageSquare className="h-5 w-5 text-green-600" />
                                <span>Probar SMS</span>
                            </CardTitle>
                            <CardDescription>
                                Envía un SMS de prueba a un número específico
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="telefono-input">Número de Teléfono</Label>
                                <Input
                                    id="telefono-input"
                                    type="tel"
                                    placeholder="+573001234567"
                                    value={phoneNumber}
                                    onChange={(e) => setPhoneNumber(e.target.value)}
                                    className="font-mono"
                                />
                                <div className="text-xs text-gray-500">
                                    Incluye el código de país (ej: +57 para Colombia)
                                </div>
                            </div>

                            {phoneNumber && (
                                <div className="p-3 bg-green-50 rounded-lg space-y-2">
                                    <div className="flex items-center space-x-2">
                                        <Phone className="h-4 w-4 text-green-600" />
                                        <span className="font-medium font-mono">{phoneNumber}</span>
                                    </div>
                                    <div className="text-sm text-gray-600">
                                        SMS será enviado a este número
                                    </div>
                                </div>
                            )}

                            <Button 
                                onClick={testSms} 
                                disabled={smsLoading || !phoneNumber}
                                className="w-full"
                            >
                                {smsLoading ? (
                                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                ) : (
                                    <Send className="h-4 w-4 mr-2" />
                                )}
                                Enviar SMS de Prueba
                            </Button>

                            {renderResult(smsResult, 'SMS')}

                            <div className="text-xs text-gray-500 space-y-1">
                                <p><strong>Mensaje:</strong> 🚨 ArchiveyCloud: Prueba SMS - Ver: [URL]</p>
                                <p><strong>Longitud:</strong> ~60 caracteres</p>
                                <p><strong>Ambiente:</strong> {process.env.NODE_ENV === 'development' ? 'Simulado (local)' : 'Envío real'}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Información adicional */}
                <Card>
                    <CardHeader>
                        <CardTitle>Información sobre las Pruebas</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                            <div>
                                <h4 className="font-semibold mb-2 flex items-center">
                                    <Mail className="h-4 w-4 mr-2 text-blue-600" />
                                    Email de Prueba
                                </h4>
                                <ul className="space-y-1 text-gray-600">
                                    <li>• Se envía usando la configuración actual de email</li>
                                    <li>• Respeta los límites de throttling (5 emails/hora)</li>
                                    <li>• Crea una notificación real en el sistema</li>
                                    <li>• Incluye información del remitente y timestamp</li>
                                    <li>• Usa el template HTML profesional</li>
                                </ul>
                            </div>
                            <div>
                                <h4 className="font-semibold mb-2 flex items-center">
                                    <MessageSquare className="h-4 w-4 mr-2 text-green-600" />
                                    SMS de Prueba
                                </h4>
                                <ul className="space-y-1 text-gray-600">
                                    <li>• Solo funciona en ambiente de producción</li>
                                    <li>• En desarrollo se simula el envío</li>
                                    <li>• Respeta los límites de throttling (3 SMS/día)</li>
                                    <li>• Formatea automáticamente el número</li>
                                    <li>• Requiere configuración del proveedor SMS</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div className="pt-4 border-t">
                            <h4 className="font-semibold mb-2">Resolución de Problemas</h4>
                            <div className="text-sm text-gray-600 space-y-1">
                                <p>• Si el email falla, verifica la configuración SMTP en el archivo .env</p>
                                <p>• Si el SMS falla, revisa la API key del proveedor en config/services.php</p>
                                <p>• Los límites de throttling pueden impedir múltiples envíos seguidos</p>
                                <p>• Revisa los logs de Laravel para más detalles sobre errores</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
