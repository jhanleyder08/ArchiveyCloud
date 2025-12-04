import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Mail, Plus, PlayCircle, Settings, Trash2, CheckCircle, XCircle, RefreshCw } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface EmailAccount {
    id: number;
    nombre: string;
    email: string;
    host: string;
    port: number;
    protocol: string;
    auto_capture: boolean;
    active: boolean;
    last_capture_at: string | null;
    total_captured: number;
    captures_count: number;
}

interface Props {
    accounts: EmailAccount[];
}

export default function EmailAccountsIndex({ accounts }: Props) {
    const [showDialog, setShowDialog] = useState(false);
    const [editingAccount, setEditingAccount] = useState<EmailAccount | null>(null);
    const [formData, setFormData] = useState({
        nombre: '',
        email: '',
        password: '',
        host: 'imap.gmail.com',
        port: 993,
        encryption: 'ssl',
        protocol: 'imap',
        auto_capture: true,
        serie_documental_id: '',
    });
    const [alert, setAlert] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
    const [testing, setTesting] = useState<Record<number, boolean>>({});
    const [capturing, setCapturing] = useState<Record<number, boolean>>({});

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setAlert(null);

        try {
            if (editingAccount) {
                await axios.patch(`/email-accounts/${editingAccount.id}`, formData);
                setAlert({ type: 'success', message: 'Cuenta actualizada correctamente' });
            } else {
                await axios.post('/email-accounts', formData);
                setAlert({ type: 'success', message: 'Cuenta creada correctamente' });
            }

            setShowDialog(false);
            resetForm();
            router.reload();
        } catch (error: any) {
            setAlert({
                type: 'error',
                message: error.response?.data?.message || 'Error al guardar la cuenta',
            });
        }
    };

    const handleTestConnection = async (account: EmailAccount) => {
        setTesting(prev => ({ ...prev, [account.id]: true }));
        setAlert(null);

        try {
            const response = await axios.post(`/email-accounts/${account.id}/test`);
            setAlert({
                type: 'success',
                message: 'Conexión exitosa con el servidor de correo',
            });
        } catch (error: any) {
            setAlert({
                type: 'error',
                message: error.response?.data?.message || 'Error al conectar con el servidor',
            });
        } finally {
            setTesting(prev => ({ ...prev, [account.id]: false }));
        }
    };

    const handleCapture = async (account: EmailAccount) => {
        setCapturing(prev => ({ ...prev, [account.id]: true }));
        setAlert(null);

        try {
            const response = await axios.post(`/email-accounts/${account.id}/capture`, {
                async: true,
                limit: 100,
            });
            setAlert({
                type: 'success',
                message: 'Captura iniciada. Se procesarán en segundo plano.',
            });
        } catch (error: any) {
            setAlert({
                type: 'error',
                message: error.response?.data?.message || 'Error al iniciar captura',
            });
        } finally {
            setCapturing(prev => ({ ...prev, [account.id]: false }));
        }
    };

    const handleCaptureAll = async () => {
        setAlert(null);

        try {
            await axios.post('/email-accounts/capture-all');
            setAlert({
                type: 'success',
                message: 'Captura masiva iniciada para todas las cuentas activas',
            });
        } catch (error: any) {
            setAlert({
                type: 'error',
                message: error.response?.data?.message || 'Error al iniciar captura masiva',
            });
        }
    };

    const handleDelete = async (account: EmailAccount) => {
        if (!confirm(`¿Eliminar la cuenta ${account.email}?`)) return;

        try {
            await axios.delete(`/email-accounts/${account.id}`);
            setAlert({ type: 'success', message: 'Cuenta eliminada correctamente' });
            router.reload();
        } catch (error: any) {
            setAlert({
                type: 'error',
                message: error.response?.data?.message || 'Error al eliminar cuenta',
            });
        }
    };

    const openCreateDialog = () => {
        resetForm();
        setEditingAccount(null);
        setShowDialog(true);
    };

    const openEditDialog = (account: EmailAccount) => {
        setFormData({
            nombre: account.nombre,
            email: account.email,
            password: '',
            host: account.host,
            port: account.port,
            encryption: 'ssl',
            protocol: account.protocol,
            auto_capture: account.auto_capture,
            serie_documental_id: '',
        });
        setEditingAccount(account);
        setShowDialog(true);
    };

    const resetForm = () => {
        setFormData({
            nombre: '',
            email: '',
            password: '',
            host: 'imap.gmail.com',
            port: 993,
            encryption: 'ssl',
            protocol: 'imap',
            auto_capture: true,
            serie_documental_id: '',
        });
    };

    const formatDate = (date: string | null) => {
        if (!date) return 'Nunca';
        return new Date(date).toLocaleString('es-ES');
    };

    return (
        <AppLayout>
            <Head title="Cuentas de Correo" />

            <div className="container mx-auto py-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Cuentas de Correo</h1>
                        <p className="text-muted-foreground mt-1">
                            Gestión de cuentas para captura automática de emails
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button onClick={handleCaptureAll} variant="outline">
                            <RefreshCw className="h-4 w-4 mr-2" />
                            Capturar Todas
                        </Button>
                        <Button onClick={openCreateDialog}>
                            <Plus className="h-4 w-4 mr-2" />
                            Nueva Cuenta
                        </Button>
                    </div>
                </div>

                {alert && (
                    <Alert variant={alert.type === 'error' ? 'destructive' : 'default'}>
                        <AlertDescription>{alert.message}</AlertDescription>
                    </Alert>
                )}

                {/* Lista de cuentas */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {accounts.map((account) => (
                        <Card key={account.id}>
                            <CardHeader>
                                <div className="flex items-start justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="p-2 bg-primary/10 rounded">
                                            <Mail className="h-5 w-5 text-primary" />
                                        </div>
                                        <div>
                                            <CardTitle className="text-lg">{account.nombre}</CardTitle>
                                            <CardDescription>{account.email}</CardDescription>
                                        </div>
                                    </div>
                                    <div className="flex gap-1">
                                        {account.active ? (
                                            <Badge variant="default">
                                                <CheckCircle className="h-3 w-3 mr-1" />
                                                Activa
                                            </Badge>
                                        ) : (
                                            <Badge variant="secondary">
                                                <XCircle className="h-3 w-3 mr-1" />
                                                Inactiva
                                            </Badge>
                                        )}
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p className="text-muted-foreground">Servidor</p>
                                        <p className="font-medium">
                                            {account.host}:{account.port}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground">Protocolo</p>
                                        <p className="font-medium uppercase">{account.protocol}</p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground">Emails Capturados</p>
                                        <p className="font-medium">{account.total_captured}</p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground">Última Captura</p>
                                        <p className="font-medium text-xs">
                                            {formatDate(account.last_capture_at)}
                                        </p>
                                    </div>
                                </div>

                                {account.auto_capture && (
                                    <Badge variant="outline" className="w-full justify-center">
                                        Captura Automática Activada
                                    </Badge>
                                )}

                                <div className="flex gap-2">
                                    <Button
                                        onClick={() => handleTestConnection(account)}
                                        disabled={testing[account.id]}
                                        size="sm"
                                        variant="outline"
                                        className="flex-1"
                                    >
                                        {testing[account.id] ? 'Probando...' : 'Probar Conexión'}
                                    </Button>
                                    <Button
                                        onClick={() => handleCapture(account)}
                                        disabled={capturing[account.id]}
                                        size="sm"
                                        className="flex-1"
                                    >
                                        <PlayCircle className="h-4 w-4 mr-2" />
                                        {capturing[account.id] ? 'Capturando...' : 'Capturar'}
                                    </Button>
                                </div>

                                <div className="flex gap-2">
                                    <Button
                                        onClick={() => router.visit(`/email-accounts/${account.id}/captures`)}
                                        size="sm"
                                        variant="ghost"
                                        className="flex-1"
                                    >
                                        Ver Capturas ({account.captures_count})
                                    </Button>
                                    <Button
                                        onClick={() => openEditDialog(account)}
                                        size="sm"
                                        variant="ghost"
                                    >
                                        <Settings className="h-4 w-4" />
                                    </Button>
                                    <Button
                                        onClick={() => handleDelete(account)}
                                        size="sm"
                                        variant="ghost"
                                        className="text-destructive"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {accounts.length === 0 && (
                    <Card>
                        <CardContent className="text-center py-12 text-muted-foreground">
                            <Mail className="h-12 w-12 mx-auto mb-4 opacity-50" />
                            <p className="mb-4">No hay cuentas de correo configuradas</p>
                            <Button onClick={openCreateDialog}>
                                <Plus className="h-4 w-4 mr-2" />
                                Crear Primera Cuenta
                            </Button>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Dialog Crear/Editar */}
            <Dialog open={showDialog} onOpenChange={setShowDialog}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editingAccount ? 'Editar Cuenta' : 'Nueva Cuenta de Correo'}
                        </DialogTitle>
                        <DialogDescription>
                            Configura la cuenta de correo para captura automática
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="col-span-2">
                                <Label>Nombre</Label>
                                <Input
                                    value={formData.nombre}
                                    onChange={(e) => setFormData({ ...formData, nombre: e.target.value })}
                                    placeholder="Ej: Archivo General"
                                    required
                                />
                            </div>

                            <div className="col-span-2">
                                <Label>Email</Label>
                                <Input
                                    type="email"
                                    value={formData.email}
                                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                    placeholder="archivo@empresa.com"
                                    required
                                />
                            </div>

                            <div className="col-span-2">
                                <Label>Contraseña</Label>
                                <Input
                                    type="password"
                                    value={formData.password}
                                    onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                                    placeholder={editingAccount ? 'Dejar vacío para mantener' : ''}
                                    required={!editingAccount}
                                />
                            </div>

                            <div>
                                <Label>Servidor (Host)</Label>
                                <Input
                                    value={formData.host}
                                    onChange={(e) => setFormData({ ...formData, host: e.target.value })}
                                    placeholder="imap.gmail.com"
                                    required
                                />
                            </div>

                            <div>
                                <Label>Puerto</Label>
                                <Input
                                    type="number"
                                    value={formData.port}
                                    onChange={(e) =>
                                        setFormData({ ...formData, port: parseInt(e.target.value) })
                                    }
                                    required
                                />
                            </div>

                            <div>
                                <Label>Protocolo</Label>
                                <Select
                                    value={formData.protocol}
                                    onValueChange={(v) => setFormData({ ...formData, protocol: v })}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="imap">IMAP</SelectItem>
                                        <SelectItem value="pop3">POP3</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label>Encriptación</Label>
                                <Select
                                    value={formData.encryption}
                                    onValueChange={(v) => setFormData({ ...formData, encryption: v })}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="ssl">SSL</SelectItem>
                                        <SelectItem value="tls">TLS</SelectItem>
                                        <SelectItem value="none">Ninguna</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="col-span-2 flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="auto_capture"
                                    checked={formData.auto_capture}
                                    onChange={(e) =>
                                        setFormData({ ...formData, auto_capture: e.target.checked })
                                    }
                                    className="h-4 w-4"
                                />
                                <Label htmlFor="auto_capture" className="cursor-pointer">
                                    Habilitar captura automática programada
                                </Label>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setShowDialog(false)}>
                                Cancelar
                            </Button>
                            <Button type="submit">
                                {editingAccount ? 'Actualizar' : 'Crear'} Cuenta
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
