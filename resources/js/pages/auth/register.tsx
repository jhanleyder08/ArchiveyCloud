import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import { login } from '@/routes/index';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle, Eye, EyeOff, User, Mail, Lock } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLoginLayout from '@/layouts/auth/auth-login-layout';

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
        <>
            <Head title="Registro - Archivey Cloud" />
            <AuthLoginLayout>
                <Form
                    {...RegisteredUserController.store.form()}
                    resetOnSuccess={['password', 'password_confirmation']}
                    disableWhileProcessing
                    className="flex flex-col gap-8"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-6">
                                {/* Name Field */}
                                <div className="grid gap-2">
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                            <User className="h-5 w-5 text-[#2a3d83]" />
                                        </div>
                                        <Input
                                            id="name"
                                            type="text"
                                            required
                                            autoFocus
                                            tabIndex={1}
                                            autoComplete="name"
                                            name="name"
                                            placeholder="Nombre completo"
                                            className="pl-12 h-12 bg-gray-50 border border-gray-200 rounded-md text-foreground placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-[#2a3d83] focus-visible:border-[#2a3d83]"
                                        />
                                    </div>
                                    <InputError message={errors.name} />
                                </div>

                                {/* Email Field */}
                                <div className="grid gap-2">
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                            <Mail className="h-5 w-5 text-[#2a3d83]" />
                                        </div>
                                        <Input
                                            id="email"
                                            type="email"
                                            required
                                            tabIndex={2}
                                            autoComplete="email"
                                            name="email"
                                            placeholder="correo@ejemplo.com"
                                            className="pl-12 h-12 bg-gray-50 border border-gray-200 rounded-md text-foreground placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-[#2a3d83] focus-visible:border-[#2a3d83]"
                                        />
                                    </div>
                                    <InputError message={errors.email} />
                                </div>

                                {/* Password Field */}
                                <div className="grid gap-2">
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                            <Lock className="h-5 w-5 text-[#2a3d83]" />
                                        </div>
                                        <Input
                                            id="password"
                                            type={showPassword ? "text" : "password"}
                                            required
                                            tabIndex={3}
                                            autoComplete="new-password"
                                            name="password"
                                            placeholder="Contraseña"
                                            className="pl-12 pr-12 h-12 bg-gray-50 border border-gray-200 rounded-md text-foreground placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-[#2a3d83] focus-visible:border-[#2a3d83]"
                                            value={password}
                                            onChange={(e) => setPassword(e.target.value)}
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            className="absolute inset-y-0 right-0 flex items-center pr-4 text-[#2a3d83] hover:text-[#1e2b5f] transition-colors"
                                            tabIndex={-1}
                                        >
                                            {showPassword ? (
                                                <EyeOff className="h-5 w-5" />
                                            ) : (
                                                <Eye className="h-5 w-5" />
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
                                            <div className="text-xs text-foreground space-y-1">
                                                <p className="font-medium">Requisitos para una contraseña segura:</p>
                                                <ul className="space-y-1">
                                                    <li className={password.length >= 8 ? 'text-green-600' : 'text-muted-foreground'}>
                                                        ✓ Al menos 8 caracteres
                                                    </li>
                                                    <li className={/[a-z]/.test(password) ? 'text-green-600' : 'text-muted-foreground'}>
                                                        ✓ Una letra minúscula
                                                    </li>
                                                    <li className={/[A-Z]/.test(password) ? 'text-green-600' : 'text-muted-foreground'}>
                                                        ✓ Una letra mayúscula
                                                    </li>
                                                    <li className={/\d/.test(password) ? 'text-green-600' : 'text-muted-foreground'}>
                                                        ✓ Un número
                                                    </li>
                                                    <li className={/[!@#$%^&*(),.?":{}|<>]/.test(password) ? 'text-green-600' : 'text-muted-foreground'}>
                                                        ✓ Un carácter especial (!@#$%^&*...)
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    )}
                                    
                                    <InputError message={errors.password} />
                                </div>

                                {/* Confirm Password Field */}
                                <div className="grid gap-2">
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                            <Lock className="h-5 w-5 text-[#2a3d83]" />
                                        </div>
                                        <Input
                                            id="password_confirmation"
                                            type={showConfirmPassword ? "text" : "password"}
                                            required
                                            tabIndex={4}
                                            autoComplete="new-password"
                                            name="password_confirmation"
                                            placeholder="Confirmar contraseña"
                                            className={`pl-12 pr-12 h-12 bg-gray-50 border rounded-md text-foreground placeholder:text-muted-foreground focus-visible:ring-2 ${
                                                confirmPassword && !passwordsMatch ? 'border-red-500 focus:border-red-500 focus-visible:ring-red-500' :
                                                confirmPassword && passwordsMatch ? 'border-green-500 focus:border-green-500 focus-visible:ring-green-500' :
                                                'border-gray-200 focus-visible:ring-[#2a3d83] focus-visible:border-[#2a3d83]'
                                            }`}
                                            value={confirmPassword}
                                            onChange={(e) => setConfirmPassword(e.target.value)}
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                                            className="absolute inset-y-0 right-0 flex items-center pr-4 text-[#2a3d83] hover:text-[#1e2b5f] transition-colors"
                                            tabIndex={-1}
                                        >
                                            {showConfirmPassword ? (
                                                <EyeOff className="h-5 w-5" />
                                            ) : (
                                                <Eye className="h-5 w-5" />
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

                                {/* Submit Button */}
                                <Button 
                                    type="submit" 
                                    className="mt-6 w-full bg-[#2a3d83] hover:bg-[#1e2b5f] text-white" 
                                    tabIndex={5}
                                    disabled={processing}
                                >
                                    {processing && <LoaderCircle className="h-4 w-4 animate-spin mr-2" />}
                                    Crear cuenta
                                </Button>
                            </div>

                            <div className="text-center text-sm text-foreground">
                                ¿Ya tienes una cuenta?{' '}
                                <TextLink href={login()} className="text-[#2a3d83] hover:text-[#1e2b5f]" tabIndex={6}>
                                    Iniciar sesión
                                </TextLink>
                            </div>
                        </>
                    )}
                </Form>
            </AuthLoginLayout>
        </>
    );
}
