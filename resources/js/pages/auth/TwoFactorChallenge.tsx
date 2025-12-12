import { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Shield, Smartphone, Mail, RefreshCw, AlertTriangle } from 'lucide-react';

interface Props {
    method: 'totp' | 'sms' | 'email';
}

export default function TwoFactorChallenge({ method }: Props) {
    const [code, setCode] = useState('');
    const [loading, setLoading] = useState(false);
    const [alert, setAlert] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
    const [canResend, setCanResend] = useState(false);
    const [countdown, setCountdown] = useState(60);

    useEffect(() => {
        if (method === 'totp') {
            setCanResend(false);
            return;
        }

        // Countdown para reenviar código
        const timer = setInterval(() => {
            setCountdown((prev) => {
                if (prev <= 1) {
                    setCanResend(true);
                    clearInterval(timer);
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);

        return () => clearInterval(timer);
    }, [method]);

    const handleVerify = async () => {
        if (!code || code.length !== 6) {
            setAlert({ type: 'error', message: 'Por favor ingresa un código de 6 dígitos' });
            return;
        }

        setLoading(true);
        setAlert(null);

        try {
            const response = await axios.post('/two-factor/verify', { code });

            if (response.data.success) {
                // Usar router.visit de Inertia para mantener la sesión
                router.visit(response.data.redirect || '/dashboard', {
                    preserveState: false,
                    replace: true,
                });
            }
        } catch (error: any) {
            setAlert({
                type: 'error',
                message: error.response?.data?.message || 'Código inválido. Por favor intenta nuevamente.',
            });
            setCode('');
            setLoading(false);
        }
    };

    const handleResend = async () => {
        if (!canResend || method === 'totp') {
            return;
        }

        setLoading(true);
        setAlert(null);

        try {
            const response = await axios.post('/two-factor/resend');

            if (response.data.success) {
                setAlert({ type: 'success', message: response.data.message });
                setCanResend(false);
                setCountdown(60);

                // Reiniciar countdown
                const timer = setInterval(() => {
                    setCountdown((prev) => {
                        if (prev <= 1) {
                            setCanResend(true);
                            clearInterval(timer);
                            return 0;
                        }
                        return prev - 1;
                    });
                }, 1000);
            }
        } catch (error: any) {
            setAlert({
                type: 'error',
                message: error.response?.data?.message || 'Error al reenviar el código',
            });
        } finally {
            setLoading(false);
        }
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            handleVerify();
        }
    };

    const getMethodInfo = () => {
        switch (method) {
            case 'totp':
                return {
                    icon: <Smartphone className="h-6 w-6" />,
                    title: 'Autenticación de Dos Factores',
                    description: 'Ingresa el código de 6 dígitos de tu aplicación de autenticación',
                    placeholder: '000000',
                };
            case 'sms':
                return {
                    icon: <Smartphone className="h-6 w-6" />,
                    title: 'Verificación por SMS',
                    description: 'Hemos enviado un código de verificación a tu teléfono',
                    placeholder: '000000',
                };
            case 'email':
                return {
                    icon: <Mail className="h-6 w-6" />,
                    title: 'Verificación por Correo',
                    description: 'Hemos enviado un código de verificación a tu correo electrónico',
                    placeholder: '000000',
                };
        }
    };

    const methodInfo = getMethodInfo();

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-4">
            <Head title="Verificación 2FA" />

            <Card className="w-full max-w-md shadow-xl">
                <CardHeader className="space-y-3">
                    <div className="flex items-center justify-center w-16 h-16 bg-primary/10 rounded-full mx-auto">
                        <Shield className="h-8 w-8 text-primary" />
                    </div>
                    <CardTitle className="text-2xl text-center flex items-center justify-center gap-2">
                        {methodInfo.icon}
                        {methodInfo.title}
                    </CardTitle>
                    <CardDescription className="text-center text-base">
                        {methodInfo.description}
                    </CardDescription>
                </CardHeader>

                <CardContent className="space-y-6">
                    {alert && (
                        <Alert variant={alert.type === 'error' ? 'destructive' : 'default'}>
                            <AlertTriangle className="h-4 w-4" />
                            <AlertDescription>{alert.message}</AlertDescription>
                        </Alert>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="code">Código de Verificación</Label>
                        <Input
                            id="code"
                            type="text"
                            inputMode="numeric"
                            pattern="[0-9]*"
                            value={code}
                            onChange={(e) => {
                                const value = e.target.value.replace(/\D/g, '').slice(0, 6);
                                setCode(value);
                            }}
                            onKeyPress={handleKeyPress}
                            placeholder={methodInfo.placeholder}
                            maxLength={6}
                            autoFocus
                            className="text-center text-2xl tracking-widest font-mono"
                            disabled={loading}
                        />
                        <p className="text-xs text-muted-foreground text-center">
                            Ingresa el código de 6 dígitos
                        </p>
                    </div>

                    <Button
                        onClick={handleVerify}
                        disabled={loading || code.length !== 6}
                        className="w-full"
                        size="lg"
                    >
                        {loading ? 'Verificando...' : 'Verificar Código'}
                    </Button>

                    {method !== 'totp' && (
                        <div className="space-y-2">
                            <div className="relative">
                                <div className="absolute inset-0 flex items-center">
                                    <span className="w-full border-t" />
                                </div>
                                <div className="relative flex justify-center text-xs uppercase">
                                    <span className="bg-background px-2 text-muted-foreground">
                                        ¿No recibiste el código?
                                    </span>
                                </div>
                            </div>

                            <Button
                                onClick={handleResend}
                                disabled={!canResend || loading}
                                variant="outline"
                                className="w-full"
                            >
                                <RefreshCw className={`h-4 w-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                                {canResend
                                    ? 'Reenviar Código'
                                    : `Reenviar en ${countdown}s`}
                            </Button>
                        </div>
                    )}

                    <div className="bg-blue-50 dark:bg-blue-950 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                        <p className="text-sm text-blue-900 dark:text-blue-100">
                            💡 <strong>Consejo de Seguridad:</strong> Nunca compartas tus códigos de
                            verificación con nadie. El equipo de soporte nunca te pedirá este código.
                        </p>
                    </div>

                    <div className="text-center">
                        <Button
                            variant="link"
                            onClick={() => {
                                // Logout del usuario
                                window.location.href = '/logout';
                            }}
                            className="text-sm text-muted-foreground hover:text-foreground"
                        >
                            Cancelar e iniciar sesión con otra cuenta
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
