// Components
import PasswordResetLinkController from '@/actions/App/Http/Controllers/Auth/PasswordResetLinkController';

import { Form, Head } from '@inertiajs/react';
import { LoaderCircle, Mail } from 'lucide-react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLoginLayout from '@/layouts/auth/auth-login-layout';

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <>
            <Head title="¿Olvidaste tu contraseña? - Archivey Cloud" />
            <AuthLoginLayout>
                <Form {...PasswordResetLinkController.store.form()} className="flex flex-col gap-8">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-6">
                                <div className="grid gap-2">
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                            <Mail className="h-5 w-5 text-[#2a3d83]" />
                                        </div>
                                        <Input 
                                            id="email" 
                                            type="email" 
                                            name="email" 
                                            autoComplete="email" 
                                            autoFocus 
                                            placeholder="correo@ejemplo.com"
                                            className="pl-12 h-12 bg-gray-50 border border-gray-200 rounded-md text-foreground placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-[#2a3d83] focus-visible:border-[#2a3d83]"
                                        />
                                    </div>
                                    <InputError message={errors.email} />
                                </div>

                                {status && (
                                    <div className="text-center text-sm font-medium text-green-600">
                                        {status}
                                    </div>
                                )}

                                <Button 
                                    type="submit" 
                                    className="mt-6 w-full bg-[#2a3d83] hover:bg-[#1e2b5f] text-white" 
                                    disabled={processing}
                                >
                                    {processing && <LoaderCircle className="h-4 w-4 animate-spin mr-2" />}
                                    Enviar enlace de restablecimiento
                                </Button>
                            </div>

                            <div className="text-center text-sm text-foreground">
                                O, regresar a{' '}
                                <TextLink href={route('login')} className="text-[#2a3d83] hover:text-[#1e2b5f]">
                                    iniciar sesión
                                </TextLink>
                            </div>
                        </>
                    )}
                </Form>
            </AuthLoginLayout>
        </>
    );
}
