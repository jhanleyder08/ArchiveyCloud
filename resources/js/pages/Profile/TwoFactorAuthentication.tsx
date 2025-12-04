import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Shield, Key, Smartphone, Mail, QrCode, AlertTriangle, CheckCircle2 } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface Props {
    enabled: boolean;
    confirmed: boolean;
    method: 'totp' | 'sms' | 'email';
}

export default function TwoFactorAuthentication({ enabled, confirmed, method: initialMethod }: Props) {
    const [isEnabled, setIsEnabled] = useState(enabled);
    const [method, setMethod] = useState<'totp' | 'sms' | 'email'>(initialMethod);
    const [qrCode, setQrCode] = useState<string | null>(null);
    const [secret, setSecret] = useState<string | null>(null);
    const [verificationCode, setVerificationCode] = useState('');
    const [password, setPassword] = useState('');
    const [phoneNumber, setPhoneNumber] = useState('');
    const [loading, setLoading] = useState(false);
    const [showQRDialog, setShowQRDialog] = useState(false);
    const [showRecoveryCodes, setShowRecoveryCodes] = useState(false);
    const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);
    const [alert, setAlert] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

    const handleEnable = async () => {
        setLoading(true);
        setAlert(null);

        try {
            const response = await axios.post('/two-factor/enable', {
                method,
                phone_number: method === 'sms' ? phoneNumber : null,
            });

            if (response.data.success) {
                if (method === 'totp') {
                    setQrCode(response.data.qr_code);
                    setSecret(response.data.secret);
                    setShowQRDialog(true);
                }
                setAlert({ type: 'success', message: response.data.message });
            }
        } catch (error: any) {
            setAlert({
                type: 'error',
                message: error.response?.data?.message || 'Error al habilitar 2FA',
            });
        } finally {
            setLoading(false);
        }
    };

    const handleConfirm = async () => {
        setLoading(true);
        setAlert(null);

        try {
            const response = await axios.post('/two-factor/confirm', {
                code: verificationCode,
            });

            if (response.data.success) {
                setIsEnabled(true);
                setShowQRDialog(false);
                setVerificationCode('');
                
                // Mostrar códigos de recuperación automáticamente
                if (response.data.recovery_codes) {
                    setRecoveryCodes(response.data.recovery_codes);
                    setShowRecoveryCodes(true);
                }
                
                setAlert({ type: 'success', message: response.data.message });
                // No recargar inmediatamente para que el usuario vea los códigos
                setTimeout(() => router.reload(), 500);
            }
        } catch (error: any) {
            setAlert({
                type: 'error',
                message: error.response?.data?.message || 'Código inválido',
            });
        } finally {
            setLoading(false);
        }
    };

    const handleDisable = async () => {
        if (!password) {
            setAlert({ type: 'error', message: 'Debes ingresar tu contraseña' });
            return;
        }

        setLoading(true);
        setAlert(null);

        try {
            const response = await axios.post('/two-factor/disable', { password });

            if (response.data.success) {
                setIsEnabled(false);
                setPassword('');
                setAlert({ type: 'success', message: response.data.message });
                router.reload();
            }
        } catch (error: any) {
            setAlert({
                type: 'error',
                message: error.response?.data?.message || 'Error al deshabilitar 2FA',
            });
        } finally {
            setLoading(false);
        }
    };

    const handleRegenerateRecoveryCodes = async () => {
        if (!password) {
            setAlert({ type: 'error', message: 'Debes ingresar tu contraseña' });
            return;
        }

        setLoading(true);
        setAlert(null);

        try {
            const response = await axios.post('/two-factor/recovery-codes/regenerate', { password });

            if (response.data.success) {
                setRecoveryCodes(response.data.codes);
                setShowRecoveryCodes(true);
                setPassword('');
                setAlert({ type: 'success', message: response.data.message });
            }
        } catch (error: any) {
            setAlert({
                type: 'error',
                message: error.response?.data?.message || 'Error al regenerar códigos',
            });
        } finally {
            setLoading(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Autenticación de Dos Factores" />

            <div className="container mx-auto py-6 max-w-4xl space-y-6">
                <div className="flex items-center gap-3">
                    <Shield className="h-8 w-8 text-primary" />
                    <div>
                        <h1 className="text-3xl font-bold">Autenticación de Dos Factores</h1>
                        <p className="text-muted-foreground">
                            Agrega una capa adicional de seguridad a tu cuenta
                        </p>
                    </div>
                </div>

                {alert && (
                    <Alert variant={alert.type === 'error' ? 'destructive' : 'default'}>
                        {alert.type === 'success' ? (
                            <CheckCircle2 className="h-4 w-4" />
                        ) : (
                            <AlertTriangle className="h-4 w-4" />
                        )}
                        <AlertDescription>{alert.message}</AlertDescription>
                    </Alert>
                )}

                {/* Estado actual */}
                <Card>
                    <CardHeader>
                        <CardTitle>Estado Actual</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                {isEnabled ? (
                                    <>
                                        <CheckCircle2 className="h-5 w-5 text-green-500" />
                                        <span className="font-medium">
                                            2FA está <strong>habilitado</strong> con método:{' '}
                                            {method === 'totp' && 'Aplicación de Autenticación'}
                                            {method === 'sms' && 'SMS'}
                                            {method === 'email' && 'Email'}
                                        </span>
                                    </>
                                ) : (
                                    <>
                                        <AlertTriangle className="h-5 w-5 text-amber-500" />
                                        <span className="font-medium">2FA está deshabilitado</span>
                                    </>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {!isEnabled ? (
                    /* Configurar 2FA */
                    <Card>
                        <CardHeader>
                            <CardTitle>Habilitar 2FA</CardTitle>
                            <CardDescription>
                                Elige un método de autenticación de dos factores
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label>Método de Autenticación</Label>
                                <Select
                                    value={method}
                                    onValueChange={(v) => setMethod(v as 'totp' | 'sms' | 'email')}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="totp">
                                            <div className="flex items-center gap-2">
                                                <Smartphone className="h-4 w-4" />
                                                Aplicación de Autenticación (TOTP)
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="sms">
                                            <div className="flex items-center gap-2">
                                                <Smartphone className="h-4 w-4" />
                                                SMS
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="email">
                                            <div className="flex items-center gap-2">
                                                <Mail className="h-4 w-4" />
                                                Correo Electrónico
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {method === 'sms' && (
                                <div>
                                    <Label>Número de Teléfono</Label>
                                    <Input
                                        type="tel"
                                        value={phoneNumber}
                                        onChange={(e) => setPhoneNumber(e.target.value)}
                                        placeholder="+57 300 123 4567"
                                    />
                                </div>
                            )}

                            <div className="bg-blue-50 dark:bg-blue-950 p-4 rounded-lg">
                                <h4 className="font-semibold mb-2">
                                    {method === 'totp' && '📱 Aplicación de Autenticación'}
                                    {method === 'sms' && '📲 SMS'}
                                    {method === 'email' && '📧 Correo Electrónico'}
                                </h4>
                                <p className="text-sm text-muted-foreground">
                                    {method === 'totp' &&
                                        'Usa aplicaciones como Google Authenticator, Microsoft Authenticator o Authy'}
                                    {method === 'sms' && 'Recibirás un código de 6 dígitos vía SMS'}
                                    {method === 'email' &&
                                        'Recibirás un código de verificación en tu correo'}
                                </p>
                            </div>

                            <Button onClick={handleEnable} disabled={loading} className="w-full">
                                {loading ? 'Configurando...' : 'Habilitar 2FA'}
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    /* Gestionar 2FA */
                    <>
                        <Card>
                            <CardHeader>
                                <CardTitle>Códigos de Recuperación</CardTitle>
                                <CardDescription>
                                    Guarda estos códigos en un lugar seguro. Los necesitarás si pierdes acceso
                                    a tu método de 2FA.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label>Contraseña (para confirmar)</Label>
                                    <Input
                                        type="password"
                                        value={password}
                                        onChange={(e) => setPassword(e.target.value)}
                                        placeholder="Ingresa tu contraseña"
                                    />
                                </div>
                                <Button
                                    onClick={handleRegenerateRecoveryCodes}
                                    disabled={loading}
                                    variant="outline"
                                    className="w-full"
                                >
                                    <Key className="h-4 w-4 mr-2" />
                                    {loading ? 'Generando...' : 'Regenerar Códigos de Recuperación'}
                                </Button>
                            </CardContent>
                        </Card>

                        <Card className="border-destructive">
                            <CardHeader>
                                <CardTitle className="text-destructive">Deshabilitar 2FA</CardTitle>
                                <CardDescription>
                                    Esto reducirá la seguridad de tu cuenta
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label>Confirma tu contraseña</Label>
                                    <Input
                                        type="password"
                                        value={password}
                                        onChange={(e) => setPassword(e.target.value)}
                                        placeholder="Ingresa tu contraseña"
                                    />
                                </div>
                                <Button
                                    onClick={handleDisable}
                                    disabled={loading}
                                    variant="destructive"
                                    className="w-full"
                                >
                                    {loading ? 'Deshabilitando...' : 'Deshabilitar 2FA'}
                                </Button>
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>

            {/* Dialog QR Code */}
            <Dialog open={showQRDialog} onOpenChange={setShowQRDialog}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <QrCode className="h-5 w-5" />
                            Escanea el Código QR
                        </DialogTitle>
                        <DialogDescription>
                            Usa tu aplicación de autenticación para escanear este código
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        {qrCode && (
                            <div className="flex justify-center">
                                <img src={qrCode} alt="QR Code" className="w-64 h-64" />
                            </div>
                        )}

                        {secret && (
                            <div className="bg-muted p-3 rounded-lg">
                                <p className="text-xs text-muted-foreground mb-1">
                                    O ingresa este código manualmente:
                                </p>
                                <code className="text-sm font-mono">{secret}</code>
                            </div>
                        )}

                        <div>
                            <Label>Código de Verificación</Label>
                            <Input
                                type="text"
                                value={verificationCode}
                                onChange={(e) => setVerificationCode(e.target.value)}
                                placeholder="000000"
                                maxLength={6}
                            />
                        </div>

                        <Button onClick={handleConfirm} disabled={loading} className="w-full">
                            {loading ? 'Verificando...' : 'Confirmar y Activar'}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Dialog Recovery Codes */}
            <Dialog open={showRecoveryCodes} onOpenChange={setShowRecoveryCodes}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Key className="h-5 w-5" />
                            Códigos de Recuperación
                        </DialogTitle>
                        <DialogDescription>
                            Guarda estos códigos en un lugar seguro. Solo se mostrarán una vez.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <Alert>
                            <AlertTriangle className="h-4 w-4" />
                            <AlertDescription>
                                Cada código solo puede usarse una vez. Guárdalos en un lugar seguro.
                            </AlertDescription>
                        </Alert>

                        <div className="bg-muted p-4 rounded-lg font-mono text-sm grid grid-cols-2 gap-2">
                            {recoveryCodes.map((code, idx) => (
                                <div key={idx} className="bg-background p-2 rounded">
                                    {code}
                                </div>
                            ))}
                        </div>

                        <Button
                            onClick={() => {
                                const text = recoveryCodes.join('\n');
                                navigator.clipboard.writeText(text);
                                setAlert({
                                    type: 'success',
                                    message: 'Códigos copiados al portapapeles',
                                });
                            }}
                            variant="outline"
                            className="w-full"
                        >
                            Copiar Códigos
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
