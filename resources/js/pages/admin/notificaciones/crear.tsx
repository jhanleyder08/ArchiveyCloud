import React, { useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { 
    Select, 
    SelectContent, 
    SelectItem, 
    SelectTrigger, 
    SelectValue 
} from '@/components/ui/select';
import { 
    Bell,
    Send,
    ArrowLeft,
    Users,
    User,
    Calendar,
    AlertTriangle,
    CheckCircle,
    Loader2,
    Info
} from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Props {
    usuarios: Usuario[];
}

const tipoOptions = [
    { value: 'general', label: 'General' },
    { value: 'sistema', label: 'Sistema' },
    { value: 'seguridad', label: 'Seguridad' },
    { value: 'expediente_vencido', label: 'Expediente Vencido' },
    { value: 'expediente_proximo_vencer', label: 'Expediente Próximo a Vencer' },
    { value: 'prestamo_vencido', label: 'Préstamo Vencido' },
    { value: 'prestamo_proximo_vencer', label: 'Préstamo Próximo a Vencer' },
    { value: 'disposicion_pendiente', label: 'Disposición Pendiente' },
    { value: 'documento_subido', label: 'Documento Subido' },
    { value: 'usuario_nuevo', label: 'Usuario Nuevo' },
];

export default function CrearNotificacion({ usuarios }: Props) {
    const { flash } = usePage<{ flash: { success?: string; error?: string } }>().props;
    const [selectAll, setSelectAll] = useState(false);
    const [selectedUsers, setSelectedUsers] = useState<number[]>([]);
    const [searchTerm, setSearchTerm] = useState('');

    const { data, setData, post, processing, errors, reset } = useForm({
        user_ids: [] as number[],
        titulo: '',
        mensaje: '',
        tipo: 'general',
        prioridad: 'media',
        accion_url: '',
        programada_para: '',
    });

    const filteredUsuarios = usuarios.filter(usuario => 
        usuario.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        usuario.email.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const handleSelectAll = (checked: boolean) => {
        setSelectAll(checked);
        if (checked) {
            const allIds = filteredUsuarios.map(u => u.id);
            setSelectedUsers(allIds);
            setData('user_ids', allIds);
        } else {
            setSelectedUsers([]);
            setData('user_ids', []);
        }
    };

    const handleSelectUser = (userId: number, checked: boolean) => {
        let newSelected: number[];
        if (checked) {
            newSelected = [...selectedUsers, userId];
        } else {
            newSelected = selectedUsers.filter(id => id !== userId);
        }
        setSelectedUsers(newSelected);
        setData('user_ids', newSelected);
        setSelectAll(newSelected.length === filteredUsuarios.length);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/notificaciones', {
            onSuccess: () => {
                reset();
                setSelectedUsers([]);
                setSelectAll(false);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Administración', href: '#' },
            { title: 'Notificaciones', href: '/admin/notificaciones' },
            { title: 'Crear Notificación', href: '/admin/notificaciones/crear' },
        ]}>
            <Head title="Crear Notificación" />

            <div className="space-y-6">
                {/* Mensaje de éxito */}
                {flash?.success && (
                    <Alert className="bg-green-50 border-green-200">
                        <CheckCircle className="h-4 w-4 text-green-600" />
                        <AlertDescription className="text-green-800">
                            {flash.success}
                        </AlertDescription>
                    </Alert>
                )}

                {/* Mensaje de error */}
                {flash?.error && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                {/* Header */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/admin/notificaciones/admin">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Link>
                        </Button>
                        <div className="flex items-center gap-2">
                            <Send className="h-6 w-6 text-[#2a3d83]" />
                            <h1 className="text-2xl font-semibold text-gray-900">
                                Crear Nueva Notificación
                            </h1>
                        </div>
                    </div>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Formulario principal */}
                        <div className="lg:col-span-2 space-y-6">
                            <Card className="bg-white border border-gray-200">
                                <CardHeader>
                                    <CardTitle className="text-lg font-semibold text-gray-900">
                                        Contenido de la Notificación
                                    </CardTitle>
                                    <CardDescription>
                                        Define el mensaje que recibirán los usuarios seleccionados
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="titulo">Título *</Label>
                                        <Input
                                            id="titulo"
                                            value={data.titulo}
                                            onChange={(e) => setData('titulo', e.target.value)}
                                            placeholder="Ej: Recordatorio de vencimiento"
                                            className={errors.titulo ? 'border-red-500' : ''}
                                        />
                                        {errors.titulo && (
                                            <p className="text-sm text-red-500">{errors.titulo}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="mensaje">Mensaje *</Label>
                                        <Textarea
                                            id="mensaje"
                                            value={data.mensaje}
                                            onChange={(e) => setData('mensaje', e.target.value)}
                                            placeholder="Escribe el contenido del mensaje..."
                                            rows={4}
                                            className={errors.mensaje ? 'border-red-500' : ''}
                                        />
                                        {errors.mensaje && (
                                            <p className="text-sm text-red-500">{errors.mensaje}</p>
                                        )}
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="tipo">Tipo de Notificación *</Label>
                                            <Select value={data.tipo} onValueChange={(value) => setData('tipo', value)}>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Seleccionar tipo" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {tipoOptions.map((option) => (
                                                        <SelectItem key={option.value} value={option.value}>
                                                            {option.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors.tipo && (
                                                <p className="text-sm text-red-500">{errors.tipo}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="prioridad">Prioridad</Label>
                                            <Select value={data.prioridad} onValueChange={(value) => setData('prioridad', value)}>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Seleccionar prioridad" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="baja">Baja</SelectItem>
                                                    <SelectItem value="media">Media</SelectItem>
                                                    <SelectItem value="alta">Alta</SelectItem>
                                                    <SelectItem value="critica">Crítica</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="accion_url">URL de Acción (opcional)</Label>
                                        <Input
                                            id="accion_url"
                                            value={data.accion_url}
                                            onChange={(e) => setData('accion_url', e.target.value)}
                                            placeholder="Ej: /admin/expedientes/123"
                                        />
                                        <p className="text-xs text-gray-500">
                                            URL a la que se redirigirá al usuario al hacer clic en la notificación
                                        </p>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="programada_para">Programar Envío (opcional)</Label>
                                        <Input
                                            id="programada_para"
                                            type="datetime-local"
                                            value={data.programada_para}
                                            onChange={(e) => setData('programada_para', e.target.value)}
                                        />
                                        <p className="text-xs text-gray-500">
                                            Deja vacío para enviar inmediatamente
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Selección de usuarios */}
                        <div className="space-y-6">
                            <Card className="bg-white border border-gray-200">
                                <CardHeader>
                                    <CardTitle className="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                        <Users className="h-5 w-5 text-[#2a3d83]" />
                                        Destinatarios
                                    </CardTitle>
                                    <CardDescription>
                                        Selecciona los usuarios que recibirán esta notificación
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {errors.user_ids && (
                                        <Alert variant="destructive">
                                            <AlertTriangle className="h-4 w-4" />
                                            <AlertDescription>{errors.user_ids}</AlertDescription>
                                        </Alert>
                                    )}

                                    <div className="space-y-2">
                                        <Input
                                            placeholder="Buscar usuarios..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                        />
                                    </div>

                                    <div className="flex items-center space-x-2 pb-2 border-b">
                                        <Checkbox
                                            id="select-all"
                                            checked={selectAll}
                                            onCheckedChange={handleSelectAll}
                                        />
                                        <Label htmlFor="select-all" className="text-sm font-medium">
                                            Seleccionar todos ({filteredUsuarios.length})
                                        </Label>
                                    </div>

                                    <div className="max-h-80 overflow-y-auto space-y-2">
                                        {filteredUsuarios.map((usuario) => (
                                            <div 
                                                key={usuario.id} 
                                                className={`flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-50 transition-colors ${
                                                    selectedUsers.includes(usuario.id) ? 'bg-blue-50' : ''
                                                }`}
                                            >
                                                <Checkbox
                                                    id={`user-${usuario.id}`}
                                                    checked={selectedUsers.includes(usuario.id)}
                                                    onCheckedChange={(checked) => handleSelectUser(usuario.id, checked as boolean)}
                                                />
                                                <div className="flex items-center gap-2 flex-1">
                                                    <div className="p-1.5 bg-blue-100 rounded-full">
                                                        <User className="h-3 w-3 text-[#2a3d83]" />
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-medium text-gray-900">{usuario.name}</p>
                                                        <p className="text-xs text-gray-500">{usuario.email}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {selectedUsers.length > 0 && (
                                        <div className="pt-2 border-t">
                                            <p className="text-sm text-[#2a3d83] font-medium">
                                                {selectedUsers.length} usuario(s) seleccionado(s)
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Botón de envío */}
                            <Card className="bg-white border border-gray-200">
                                <CardContent className="pt-6">
                                    <Button 
                                        type="submit" 
                                        className="w-full bg-[#2a3d83] hover:bg-[#1e2b5f]"
                                        disabled={processing || selectedUsers.length === 0}
                                    >
                                        {processing ? (
                                            <>
                                                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                                Enviando...
                                            </>
                                        ) : (
                                            <>
                                                <Send className="h-4 w-4 mr-2" />
                                                Enviar Notificación
                                            </>
                                        )}
                                    </Button>

                                    {selectedUsers.length === 0 && (
                                        <p className="text-xs text-center text-gray-500 mt-2">
                                            Selecciona al menos un usuario
                                        </p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Información */}
                            <Alert>
                                <Info className="h-4 w-4" />
                                <AlertDescription>
                                    Las notificaciones se enviarán inmediatamente a menos que especifiques una fecha de programación.
                                </AlertDescription>
                            </Alert>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
