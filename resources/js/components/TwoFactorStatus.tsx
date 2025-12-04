import { Shield, CheckCircle, AlertCircle, Clock } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

interface TwoFactorStatusProps {
    enabled: boolean;
    method?: 'totp' | 'sms' | 'email';
    confirmedAt?: string;
    className?: string;
    compact?: boolean;
}

export function TwoFactorStatus({
    enabled,
    method,
    confirmedAt,
    className,
    compact = false,
}: TwoFactorStatusProps) {
    const getMethodInfo = () => {
        switch (method) {
            case 'totp':
                return {
                    name: 'Aplicación de Autenticación',
                    icon: '📱',
                    color: 'bg-blue-500',
                };
            case 'sms':
                return {
                    name: 'SMS',
                    icon: '📲',
                    color: 'bg-green-500',
                };
            case 'email':
                return {
                    name: 'Email',
                    icon: '📧',
                    color: 'bg-purple-500',
                };
            default:
                return {
                    name: 'No configurado',
                    icon: '🔐',
                    color: 'bg-gray-500',
                };
        }
    };

    const methodInfo = getMethodInfo();

    if (compact) {
        return (
            <div className={cn('flex items-center gap-2', className)}>
                {enabled ? (
                    <>
                        <CheckCircle className="h-4 w-4 text-green-500" />
                        <span className="text-sm text-muted-foreground">
                            2FA Activo ({methodInfo.name})
                        </span>
                    </>
                ) : (
                    <>
                        <AlertCircle className="h-4 w-4 text-amber-500" />
                        <span className="text-sm text-muted-foreground">2FA Deshabilitado</span>
                    </>
                )}
            </div>
        );
    }

    return (
        <Card className={className}>
            <CardContent className="pt-6">
                <div className="flex items-start gap-4">
                    <div
                        className={cn(
                            'flex h-12 w-12 items-center justify-center rounded-full',
                            enabled ? 'bg-green-100 dark:bg-green-900' : 'bg-amber-100 dark:bg-amber-900'
                        )}
                    >
                        <Shield
                            className={cn(
                                'h-6 w-6',
                                enabled ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'
                            )}
                        />
                    </div>

                    <div className="flex-1 space-y-2">
                        <div className="flex items-center gap-2">
                            <h3 className="font-semibold">Autenticación de Dos Factores</h3>
                            <Badge variant={enabled ? 'default' : 'secondary'}>
                                {enabled ? 'Activo' : 'Inactivo'}
                            </Badge>
                        </div>

                        {enabled ? (
                            <div className="space-y-1">
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <span className="text-lg">{methodInfo.icon}</span>
                                    <span>Método: {methodInfo.name}</span>
                                </div>
                                {confirmedAt && (
                                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                        <Clock className="h-3 w-3" />
                                        <span>
                                            Activado:{' '}
                                            {new Date(confirmedAt).toLocaleDateString('es-ES', {
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric',
                                            })}
                                        </span>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Tu cuenta no está protegida con autenticación de dos factores.
                                Recomendamos habilitarla para mayor seguridad.
                            </p>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
