import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Progress } from '@/components/ui/progress';
import { 
    HardDrive, 
    Archive, 
    Clock, 
    CheckCircle,
    XCircle,
    Download,
    Trash2,
    AlertTriangle,
    Database,
    FileText,
    Zap
} from 'lucide-react';

interface BackupHistoryItem {
    id: string;
    type: string;
    created_at: string;
    status: string;
    size: number;
    compressed: boolean;
    filename: string;
    compression_ratio?: number;
}

interface StorageInfo {
    free_space: number;
    total_space: number;
    used_space: number;
    usage_percentage: number;
    free_space_formatted: string;
    total_space_formatted: string;
    used_space_formatted: string;
}

interface BackupConfig {
    retention_days: number;
    enabled: boolean;
    compression: boolean;
    types: string[];
}

interface PageProps {
    backupHistory: BackupHistoryItem[];
    storageInfo: StorageInfo;
    backupConfig: BackupConfig;
}

export default function BackupsPage({ backupHistory, storageInfo, backupConfig }: PageProps) {
    const [loading, setLoading] = useState(false);
    const [backupType, setBackupType] = useState('full');
    const [compress, setCompress] = useState(true);
    const [cleanup, setCleanup] = useState(true);
    const [retentionDays, setRetentionDays] = useState(backupConfig.retention_days);
    const [dryRun, setDryRun] = useState(false);
    const [showCreateDialog, setShowCreateDialog] = useState(false);

    const createBackup = async () => {
        setLoading(true);
        try {
            const options = {
                type: backupType,
                compress,
                cleanup,
                retention_days: retentionDays,
                dry_run: dryRun
            };

            const response = await fetch(route('admin.optimizacion.backups.create'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(options),
            });

            const result = await response.json();
            
            if (result.success) {
                alert('Backup creado exitosamente');
                router.reload();
            } else {
                alert('Error durante backup: ' + result.message);
            }
        } catch (error) {
            console.error('Error creating backup:', error);
            alert('Error interno del servidor');
        } finally {
            setLoading(false);
            setShowCreateDialog(false);
        }
    };

    const getBackupTypeIcon = (type: string) => {
        switch (type) {
            case 'full':
                return <Archive className="h-4 w-4" />;
            case 'database':
                return <Database className="h-4 w-4" />;
            case 'files':
                return <FileText className="h-4 w-4" />;
            default:
                return <HardDrive className="h-4 w-4" />;
        }
    };

    const getBackupTypeColor = (type: string) => {
        switch (type) {
            case 'full':
                return 'bg-blue-100 text-blue-800';
            case 'database':
                return 'bg-green-100 text-green-800';
            case 'files':
                return 'bg-purple-100 text-purple-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const formatFileSize = (bytes: number): string => {
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let size = bytes;
        let unitIndex = 0;
        
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        
        return `${size.toFixed(2)} ${units[unitIndex]}`;
    };

    return (
        <AppSidebarLayout>
            <Head title="Gestión de Backups" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Header */}
                    <div className="bg-white p-6 rounded-lg shadow">
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Gestión de Backups
                        </h2>
                    </div>

                    {/* Información de almacenamiento */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Espacio Libre</CardTitle>
                                <HardDrive className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{storageInfo.free_space_formatted}</div>
                                <p className="text-xs text-muted-foreground">
                                    Disponible
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Espacio Usado</CardTitle>
                                <Archive className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{storageInfo.used_space_formatted}</div>
                                <p className="text-xs text-muted-foreground">
                                    {storageInfo.usage_percentage.toFixed(1)}% del total
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total de Backups</CardTitle>
                                <Database className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{backupHistory.length}</div>
                                <p className="text-xs text-muted-foreground">
                                    Almacenados
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Retención</CardTitle>
                                <Clock className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{backupConfig.retention_days}</div>
                                <p className="text-xs text-muted-foreground">
                                    Días
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Barra de uso de almacenamiento */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Uso de Almacenamiento</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span>Usado: {storageInfo.used_space_formatted}</span>
                                    <span>Libre: {storageInfo.free_space_formatted}</span>
                                </div>
                                <Progress value={storageInfo.usage_percentage} className="w-full" />
                                <p className="text-xs text-muted-foreground">
                                    {storageInfo.usage_percentage.toFixed(1)}% de {storageInfo.total_space_formatted} usado
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Panel de creación de backups */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Zap className="h-5 w-5" />
                                    Crear Backup
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <Alert>
                                    <AlertTriangle className="h-4 w-4" />
                                    <AlertTitle>Importante</AlertTitle>
                                    <AlertDescription>
                                        Los backups pueden tomar tiempo según el tamaño de los datos.
                                        Se recomienda ejecutar durante períodos de bajo tráfico.
                                    </AlertDescription>
                                </Alert>

                                <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
                                    <DialogTrigger asChild>
                                        <Button className="w-full flex items-center gap-2">
                                            <Archive className="h-4 w-4" />
                                            Crear Nuevo Backup
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent className="sm:max-w-md">
                                        <DialogHeader>
                                            <DialogTitle>Crear Backup</DialogTitle>
                                            <DialogDescription>
                                                Configura las opciones para el nuevo backup.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="space-y-4">
                                            <div className="space-y-2">
                                                <Label>Tipo de Backup</Label>
                                                <select
                                                    value={backupType}
                                                    onChange={(e) => setBackupType(e.target.value)}
                                                    className="w-full p-2 border rounded-md"
                                                >
                                                    <option value="full">Backup Completo</option>
                                                    <option value="database">Solo Base de Datos</option>
                                                    <option value="files">Solo Archivos</option>
                                                </select>
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="retentionDays">Días de Retención</Label>
                                                <Input
                                                    id="retentionDays"
                                                    type="number"
                                                    value={retentionDays}
                                                    onChange={(e) => setRetentionDays(parseInt(e.target.value))}
                                                    min="1"
                                                    max="365"
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <div className="flex items-center space-x-2">
                                                    <input
                                                        type="checkbox"
                                                        id="compress"
                                                        checked={compress}
                                                        onChange={(e) => setCompress(e.target.checked)}
                                                    />
                                                    <Label htmlFor="compress">Comprimir backup</Label>
                                                </div>
                                                
                                                <div className="flex items-center space-x-2">
                                                    <input
                                                        type="checkbox"
                                                        id="cleanup"
                                                        checked={cleanup}
                                                        onChange={(e) => setCleanup(e.target.checked)}
                                                    />
                                                    <Label htmlFor="cleanup">Limpiar backups antiguos</Label>
                                                </div>

                                                <div className="flex items-center space-x-2">
                                                    <input
                                                        type="checkbox"
                                                        id="dryRun"
                                                        checked={dryRun}
                                                        onChange={(e) => setDryRun(e.target.checked)}
                                                    />
                                                    <Label htmlFor="dryRun">Modo simulación (dry run)</Label>
                                                </div>
                                            </div>
                                        </div>
                                        <DialogFooter>
                                            <Button
                                                variant="outline"
                                                onClick={() => setShowCreateDialog(false)}
                                            >
                                                Cancelar
                                            </Button>
                                            <Button
                                                onClick={createBackup}
                                                disabled={loading}
                                            >
                                                {loading ? 'Creando...' : 'Crear Backup'}
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>

                                {loading && (
                                    <div className="space-y-2">
                                        <p className="text-sm text-muted-foreground">Creando backup...</p>
                                        <Progress value={undefined} className="w-full" />
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Configuración */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Configuración</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="text-center p-3 border rounded-lg">
                                        <div className="text-2xl font-bold text-green-600">
                                            {backupConfig.enabled ? 'Habilitado' : 'Deshabilitado'}
                                        </div>
                                        <div className="text-sm text-muted-foreground">Estado</div>
                                    </div>
                                    <div className="text-center p-3 border rounded-lg">
                                        <div className="text-2xl font-bold text-blue-600">
                                            {backupConfig.compression ? 'Sí' : 'No'}
                                        </div>
                                        <div className="text-sm text-muted-foreground">Compresión</div>
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label>Tipos Soportados:</Label>
                                    <div className="flex flex-wrap gap-2">
                                        {backupConfig.types.map((type) => (
                                            <Badge key={type} variant="outline" className="flex items-center gap-1">
                                                {getBackupTypeIcon(type)}
                                                {type}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Historial de backups */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Historial de Backups</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {backupHistory.map((backup, index) => (
                                    <div
                                        key={index}
                                        className="flex items-center justify-between p-4 border rounded-lg"
                                    >
                                        <div className="flex items-center space-x-4">
                                            <div className={`p-2 rounded-lg ${getBackupTypeColor(backup.type)}`}>
                                                {getBackupTypeIcon(backup.type)}
                                            </div>
                                            <div>
                                                <p className="font-medium">{backup.filename}</p>
                                                <p className="text-sm text-muted-foreground">
                                                    {new Date(backup.created_at).toLocaleString()}
                                                </p>
                                                <div className="flex items-center gap-2 mt-1">
                                                    <Badge variant="outline" className="text-xs">
                                                        {backup.type}
                                                    </Badge>
                                                    {backup.compressed && (
                                                        <Badge variant="outline" className="text-xs">
                                                            Comprimido
                                                        </Badge>
                                                    )}
                                                    {backup.compression_ratio && (
                                                        <span className="text-xs text-muted-foreground">
                                                            Ratio: {backup.compression_ratio.toFixed(1)}%
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <div className="text-right">
                                                <div className="font-mono text-sm">
                                                    {formatFileSize(backup.size)}
                                                </div>
                                                {backup.status === 'completed' ? (
                                                    <CheckCircle className="h-4 w-4 text-green-600 mx-auto" />
                                                ) : (
                                                    <XCircle className="h-4 w-4 text-red-600 mx-auto" />
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                                {backupHistory.length === 0 && (
                                    <p className="text-center text-muted-foreground py-8">
                                        No hay backups disponibles
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppSidebarLayout>
    );
}
