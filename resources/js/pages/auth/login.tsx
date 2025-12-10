import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLoginLayout from '@/layouts/auth/auth-login-layout';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle, Eye, EyeOff, User, Lock } from 'lucide-react';
import { useState } from 'react';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const [showPassword, setShowPassword] = useState(false);

    return (
        <>
            <Head title="Iniciar Sesión - Archivey Cloud" />
            <AuthLoginLayout>
                <Form {...AuthenticatedSessionController.store.form()} resetOnSuccess={['password']} className="flex flex-col gap-8">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-6">
                                {/* Email Field */}
                                <div className="grid gap-2">
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                            <User className="h-5 w-5 text-[#2a3d83]" />
                                        </div>
                                        <Input
                                            id="email"
                                            type="email"
                                            name="email"
                                            required
                                            autoFocus
                                            tabIndex={1}
                                            autoComplete="email"
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
                                            name="password"
                                            required
                                            tabIndex={2}
                                            autoComplete="current-password"
                                            placeholder="************"
                                            className="pl-12 pr-12 h-12 bg-gray-50 border border-gray-200 rounded-md text-foreground placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-[#2a3d83] focus-visible:border-[#2a3d83]"
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
                                    <InputError message={errors.password} />
                                </div>

                                {/* Options Row */}
                                <div className="flex items-center justify-between">
                                    {canResetPassword && (
                                        <TextLink href={route('password.request')} className="text-sm text-[#2a3d83] hover:text-[#1e2b5f] transition-colors" tabIndex={5}>
                                            ¿Olvidaste tu contraseña?
                                        </TextLink>
                                    )}
                                    <div className="flex items-center space-x-2 ml-auto">
                                        <Checkbox id="remember" name="remember" tabIndex={3} className="border-[#2a3d83] data-[state=checked]:bg-[#2a3d83] data-[state=checked]:border-[#2a3d83]" />
                                        <Label htmlFor="remember" className="text-sm text-[#2a3d83] cursor-pointer">
                                            Recordarme
                                        </Label>
                                    </div>
                                </div>

                                {/* Submit Button */}
                                <Button 
                                    type="submit" 
                                    className="mt-6 w-full bg-[#2a3d83] hover:bg-[#1e2b5f] text-white" 
                                    tabIndex={4} 
                                    disabled={processing}
                                >
                                    {processing && <LoaderCircle className="h-4 w-4 animate-spin mr-2" />}
                                    Iniciar Sesión
                                </Button>
                            </div>

                            {status && (
                                <div className="text-center text-sm font-medium text-green-600">
                                    {status}
                                </div>
                            )}

                            <div className="text-center text-sm text-foreground">
                                ¿No tienes una cuenta?{' '}
                                <TextLink href={route('register')} className="text-[#2a3d83] hover:text-[#1e2b5f]" tabIndex={6}>
                                    Regístrate
                                </TextLink>
                            </div>
                        </>
                    )}
                </Form>
            </AuthLoginLayout>
        </>
    );
}
