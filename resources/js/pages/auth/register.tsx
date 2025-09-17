import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import { login } from '@/routes/index';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle, Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

export default function Register() {
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');

    // Función para evaluar fortaleza de contraseña
    const getPasswordStrength = (password: string) => {
        if (password.length === 0) return { score: 0, text: '', color: '' };
        
        let score = 0;
        const checks = {
            length: password.length >= 8,
            lowercase: /[a-z]/.test(password),
            uppercase: /[A-Z]/.test(password),
            numbers: /\d/.test(password),
            symbols: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };

        Object.values(checks).forEach(check => check && score++);

        if (score < 2) return { score, text: 'Muy débil', color: 'text-red-600' };
        if (score < 3) return { score, text: 'Débil', color: 'text-orange-600' };
        if (score < 4) return { score, text: 'Moderada', color: 'text-yellow-600' };
        if (score < 5) return { score, text: 'Fuerte', color: 'text-green-600' };
        return { score, text: 'Muy fuerte', color: 'text-green-700' };
    };

    const passwordStrength = getPasswordStrength(password);
    const passwordsMatch = password && confirmPassword && password === confirmPassword;

    return (
        <AuthLayout title="Crear una cuenta" description="Ingresa tus datos para crear tu cuenta">
            <Head title="Registro - Archivey Cloud" />
            <Form
                {...RegisteredUserController.store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nombre</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Nombre completo"
                                />
                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Correo electrónico</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={2}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="correo@ejemplo.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Contraseña</Label>
                                <div className="relative">
                                    <Input
                                        id="password"
                                        type={showPassword ? "text" : "password"}
                                        required
                                        tabIndex={3}
                                        autoComplete="new-password"
                                        name="password"
                                        placeholder="Contraseña"
                                        className="pr-10"
                                        value={password}
                                        onChange={(e) => setPassword(e.target.value)}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                                        tabIndex={-1}
                                    >
                                        {showPassword ? (
                                            <EyeOff className="h-4 w-4" />
                                        ) : (
                                            <Eye className="h-4 w-4" />
                                        )}
                                    </button>
                                </div>
                                
                                {/* Indicador de fortaleza de contraseña */}
                                {password && (
                                    <div className="mt-2 space-y-2">
                                        <div className="flex items-center gap-2">
                                            <div className="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                <div 
                                                    className={`h-full transition-all duration-300 ${
                                                        passwordStrength.score === 1 ? 'bg-red-500 w-1/5' :
                                                        passwordStrength.score === 2 ? 'bg-orange-500 w-2/5' :
                                                        passwordStrength.score === 3 ? 'bg-yellow-500 w-3/5' :
                                                        passwordStrength.score === 4 ? 'bg-green-500 w-4/5' :
                                                        passwordStrength.score === 5 ? 'bg-green-600 w-full' : ''
                                                    }`}
                                                />
                                            </div>
                                            <span className={`text-sm font-medium ${passwordStrength.color}`}>
                                                {passwordStrength.text}
                                            </span>
                                        </div>
                                        <div className="text-xs text-gray-600 space-y-1">
                                            <p className="font-medium">Requisitos para una contraseña segura:</p>
                                            <ul className="space-y-1">
                                                <li className={password.length >= 8 ? 'text-green-600' : 'text-gray-500'}>
                                                    ✓ Al menos 8 caracteres
                                                </li>
                                                <li className={/[a-z]/.test(password) ? 'text-green-600' : 'text-gray-500'}>
                                                    ✓ Una letra minúscula
                                                </li>
                                                <li className={/[A-Z]/.test(password) ? 'text-green-600' : 'text-gray-500'}>
                                                    ✓ Una letra mayúscula
                                                </li>
                                                <li className={/\d/.test(password) ? 'text-green-600' : 'text-gray-500'}>
                                                    ✓ Un número
                                                </li>
                                                <li className={/[!@#$%^&*(),.?":{}|<>]/.test(password) ? 'text-green-600' : 'text-gray-500'}>
                                                    ✓ Un carácter especial (!@#$%^&*...)
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                )}
                                
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">Confirmar contraseña</Label>
                                <div className="relative">
                                    <Input
                                        id="password_confirmation"
                                        type={showConfirmPassword ? "text" : "password"}
                                        required
                                        tabIndex={4}
                                        autoComplete="new-password"
                                        name="password_confirmation"
                                        placeholder="Confirmar contraseña"
                                        className={`pr-10 ${
                                            confirmPassword && !passwordsMatch ? 'border-red-500 focus:border-red-500' :
                                            confirmPassword && passwordsMatch ? 'border-green-500 focus:border-green-500' : ''
                                        }`}
                                        value={confirmPassword}
                                        onChange={(e) => setConfirmPassword(e.target.value)}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                                        className="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                                        tabIndex={-1}
                                    >
                                        {showConfirmPassword ? (
                                            <EyeOff className="h-4 w-4" />
                                        ) : (
                                            <Eye className="h-4 w-4" />
                                        )}
                                    </button>
                                </div>
                                
                                {/* Validación de coincidencia de contraseñas */}
                                {confirmPassword && (
                                    <div className="mt-2">
                                        {passwordsMatch ? (
                                            <div className="flex items-center gap-2 text-green-600 text-sm">
                                                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                                </svg>
                                                Las contraseñas coinciden
                                            </div>
                                        ) : (
                                            <div className="flex items-center gap-2 text-red-600 text-sm">
                                                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Las contraseñas no coinciden
                                            </div>
                                        )}
                                    </div>
                                )}
                                
                                <InputError message={errors.password_confirmation} />
                            </div>

                            <Button type="submit" className="mt-2 w-full" tabIndex={5}>
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                Crear cuenta
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            ¿Ya tienes una cuenta?{' '}
                            <TextLink href={login()} tabIndex={6}>
                                Iniciar sesión
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
