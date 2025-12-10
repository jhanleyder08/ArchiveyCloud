import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    Key, 
    Search,
    Filter,
    CheckCircle,
    AlertTriangle,
    XCircle,
    Calendar,
    Shield,
    Download,
    Upload,
    Trash2,
    Eye,
    RefreshCw,
    FileKey
} from 'lucide-react';

interface CertificadoDigital {
    id: number;
    nombre_certificado: string;
    numero_serie: string;
    emisor: string;
    sujeto: string;
    algoritmo_firma: string;
    longitud_clave: number;
    huella_digital: string;
    fecha_emision: string;
    fecha_vencimiento: string;
    estado: 'activo' | 'vencido' | 'revocado' | 'suspendido';
    tipo_certificado: 'usuario' | 'servidor' | 'autoridad_certificadora' | 'sello_tiempo';
    uso_permitido: string[] | null;
    revocado_en: string | null;
    razon_revocacion: string | null;
    vigente: boolean;
    dias_restantes: number;
    usuario: {
        id: number;
        name: string;
        email: string;
    };
}

interface Props {
    certificados: {
        data: CertificadoDigital[];
        meta: {
            current_page: number;
            last_page: number;
            total: number;
            per_page: number;
            from: number;
            to: number;
        };
    };
    filtros: {
        search?: string;
        estado?: string;
        tipo?: string;
        usuario_id?: number;
    };
    usuarios: Array<{
        id: number;
        name: string;
        email: string;
    }>;
    estadisticas?: {
        total: number;
        activos: number;
        proximos_vencer: number;
        vencidos: number;
        revocados: number;
    };
}

export default function CertificadosIndex({ certificados, filtros, usuarios, estadisticas }: Props) {
    // Debug: Ver qué está llegando
    console.log('Props recibidas:', { certificados, filtros, usuarios, estadisticas });
    
    // Valores por defecto para estadisticas si es undefined
    const stats = estadisticas || {
        total: 0,
        activos: 0,
        proximos_vencer: 0,
        vencidos: 0,
        revocados: 0
    };
    
    console.log('Stats calculadas:', stats);
    
    const [filtrosLocales, setFiltrosLocales] = useState(filtros);
    const [certificadoSeleccionado, setCertificadoSeleccionado] = useState<CertificadoDigital | null>(null);
    const [mostrarDetalles, setMostrarDetalles] = useState(false);
    const [procesando, setProcesando] = useState(false);

    const aplicarFiltros = () => {
        router.get(route('admin.firmas.certificados'), filtrosLocales, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const limpiarFiltros = () => {
        setFiltrosLocales({});
        router.get(route('admin.firmas.certificados'));
    };

    const handleFiltroChange = (campo: string, valor: any) => {
        const nuevosFiltros = { ...filtrosLocales } as any;
        nuevosFiltros[campo] = valor;
        
        if (!valor || valor === 'all') {
            delete nuevosFiltros[campo];
        }
        
        setFiltrosLocales(nuevosFiltros);
    };

    const getBadgeEstado = (estado: string, vigente: boolean) => {
        if (!vigente) {
            return <Badge variant="destructive">Vencido</Badge>;
        }
        
        switch (estado) {
            case 'activo':
                return <Badge className="bg-green-500 text-white">Activo</Badge>;
            case 'vencido':
                return <Badge variant="destructive">Vencido</Badge>;
            case 'revocado':
                return <Badge className="bg-red-600 text-white">Revocado</Badge>;
            case 'suspendido':
                return <Badge className="bg-orange-500 text-white">Suspendido</Badge>;
            default:
                return <Badge variant="secondary">{estado}</Badge>;
        }
    };

    const getBadgeTipo = (tipo: string) => {
        const tipos = {
            'usuario': { color: 'bg-blue-500 text-white', label: 'Usuario' },
            'servidor': { color: 'bg-purple-500 text-white', label: 'Servidor' },
            'autoridad_certificadora': { color: 'bg-indigo-600 text-white', label: 'AC' },
            'sello_tiempo': { color: 'bg-teal-500 text-white', label: 'TSA' }
        };
        
        const config = tipos[tipo as keyof typeof tipos] || { color: 'bg-gray-500 text-white', label: tipo };
        return <Badge className={config.color}>{config.label}</Badge>;
    };

    const getIconoEstado = (estado: string, vigente: boolean) => {
        if (!vigente) {
            return <XCircle className="w-4 h-4 text-red-600" />;
        }
        
        switch (estado) {
            case 'activo':
                return <CheckCircle className="w-4 h-4 text-green-600" />;
            case 'vencido':
                return <XCircle className="w-4 h-4 text-red-600" />;
            case 'revocado':
                return <XCircle className="w-4 h-4 text-red-600" />;
            case 'suspendido':
                return <AlertTriangle className="w-4 h-4 text-orange-600" />;
            default:
                return <AlertTriangle className="w-4 h-4 text-gray-600" />;
        }
    };

    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const getDiasRestantes = (dias: number) => {
        if (dias < 0) return 'Vencido';
        if (dias === 0) return 'Hoy';
        if (dias === 1) return 'Mañana';
        return `${dias} días`;
    };

    const revocarCertificado = async (certificado: CertificadoDigital) => {
        if (!confirm(`¿Estás seguro de revocar el certificado ${certificado.nombre_certificado}?`)) {
            return;
        }

        setProcesando(true);
        try {
            await router.post(route('admin.firmas.certificado.revocar', certificado.id), {
                razon: 'Revocación manual por administrador'
            }, {
                preserveScroll: true
            });
        } finally {
            setProcesando(false);
        }
    };

    const verDetalles = (certificado: CertificadoDigital) => {
        setCertificadoSeleccionado(certificado);
        setMostrarDetalles(true);
    };

    return (
        <AppLayout>
            <Head title="Certificados Digitales" />
            
            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="md:flex md:items-center md:justify-between mb-6">
                        <div className="flex-1 min-w-0">
                            <h2 className="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                                Certificados Digitales PKI 🔐
                            </h2>
                            <p className="mt-1 text-sm text-gray-500">
                                Gestión de certificados X.509 para firma electrónica
                            </p>
                        </div>
                        <div className="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                            <Button onClick={() => router.get(route('admin.firmas.certificado.crear'))}>
                                <Upload className="w-4 h-4 mr-2" />
                                Importar Certificado
                            </Button>
                            <Link href={route('admin.firmas.dashboard')}>
                                <Button variant="outline">
                                    <Shield className="w-4 h-4 mr-2" />
                                    Dashboard
                                </Button>
                            </Link>
                        </div>
                    </div>

                    {/* Estadísticas */}
                    <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium">Total</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.total}</div>
                                <p className="text-xs text-muted-foreground">certificados</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium">Activos</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-green-600">{stats.activos}</div>
                                <p className="text-xs text-muted-foreground">vigentes</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium">Próximos a Vencer</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-orange-600">{stats.proximos_vencer}</div>
                                <p className="text-xs text-muted-foreground">{'<'} 30 días</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium">Vencidos</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-red-600">{stats.vencidos}</div>
                                <p className="text-xs text-muted-foreground">inválidos</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium">Revocados</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-red-800">{stats.revocados}</div>
                                <p className="text-xs text-muted-foreground">cancelados</p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Alertas */}
                    {stats.proximos_vencer > 0 && (
                        <Alert className="mb-6 border-orange-200 bg-orange-50">
                            <AlertTriangle className="h-4 w-4 text-orange-600" />
                            <AlertDescription className="text-orange-800">
                                Tienes {stats.proximos_vencer} certificado(s) que vencerán en los próximos 30 días.
                                Es recomendable renovarlos o generar nuevos certificados.
                            </AlertDescription>
                        </Alert>
                    )}

                    {/* Filtros */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle className="text-lg flex items-center">
                                <Filter className="w-5 h-5 mr-2" />
                                Filtros de Búsqueda
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                                <div>
                                    <label className="text-sm font-medium">Buscar</label>
                                    <div className="relative">
                                        <Search className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                                        <Input
                                            placeholder="Nombre, serie, emisor..."
                                            value={filtrosLocales.search || ''}
                                            onChange={(e) => handleFiltroChange('search', e.target.value)}
                                            className="pl-10"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="text-sm font-medium">Estado</label>
                                    <Select value={filtrosLocales.estado || 'all'} onValueChange={(valor) => handleFiltroChange('estado', valor)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Todos los estados" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todos los estados</SelectItem>
                                            <SelectItem value="activo">Activo</SelectItem>
                                            <SelectItem value="vencido">Vencido</SelectItem>
                                            <SelectItem value="revocado">Revocado</SelectItem>
                                            <SelectItem value="suspendido">Suspendido</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <label className="text-sm font-medium">Tipo</label>
                                    <Select value={filtrosLocales.tipo || 'all'} onValueChange={(valor) => handleFiltroChange('tipo', valor)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Todos los tipos" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todos los tipos</SelectItem>
                                            <SelectItem value="usuario">Usuario</SelectItem>
                                            <SelectItem value="servidor">Servidor</SelectItem>
                                            <SelectItem value="autoridad_certificadora">Autoridad Certificadora</SelectItem>
                                            <SelectItem value="sello_tiempo">Sello de Tiempo</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <label className="text-sm font-medium">Usuario</label>
                                    <Select value={filtrosLocales.usuario_id?.toString() || 'all'} onValueChange={(valor) => handleFiltroChange('usuario_id', valor === 'all' ? null : parseInt(valor))}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Todos los usuarios" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todos los usuarios</SelectItem>
                                            {usuarios.map((usuario) => (
                                                <SelectItem key={usuario.id} value={usuario.id.toString()}>
                                                    {usuario.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="flex items-end space-x-2">
                                    <Button onClick={aplicarFiltros} className="flex-1">
                                        <Search className="w-4 h-4 mr-2" />
                                        Filtrar
                                    </Button>
                                    <Button onClick={limpiarFiltros} variant="outline">
                                        <RefreshCw className="w-4 h-4" />
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Tabla de certificados */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Certificados Digitales</CardTitle>
                            <CardDescription>
                                {certificados.meta.total} certificado(s) encontrado(s)
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Estado</TableHead>
                                            <TableHead>Certificado</TableHead>
                                            <TableHead>Tipo</TableHead>
                                            <TableHead>Usuario</TableHead>
                                            <TableHead>Emisor</TableHead>
                                            <TableHead>Vencimiento</TableHead>
                                            <TableHead>Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {certificados.data.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={7} className="text-center py-8">
                                                    <FileKey className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                                                    <p className="text-gray-500">No se encontraron certificados</p>
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            certificados.data.map((certificado) => (
                                                <TableRow key={certificado.id}>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-2">
                                                            {getIconoEstado(certificado.estado, certificado.vigente)}
                                                            {getBadgeEstado(certificado.estado, certificado.vigente)}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div>
                                                            <div className="font-medium">{certificado.nombre_certificado}</div>
                                                            <div className="text-sm text-gray-500">
                                                                Serie: {certificado.numero_serie}
                                                            </div>
                                                            <div className="text-xs text-gray-400">
                                                                {certificado.algoritmo_firma} ({certificado.longitud_clave} bits)
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        {getBadgeTipo(certificado.tipo_certificado)}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div>
                                                            <div className="font-medium">{certificado.usuario.name}</div>
                                                            <div className="text-sm text-gray-500">{certificado.usuario.email}</div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="text-sm">{certificado.emisor}</div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div>
                                                            <div className="flex items-center space-x-1">
                                                                <Calendar className="w-4 h-4 text-gray-400" />
                                                                <span className="text-sm">{formatearFecha(certificado.fecha_vencimiento)}</span>
                                                            </div>
                                                            <div className={`text-xs ${certificado.dias_restantes < 30 ? 'text-orange-600' : 'text-gray-500'}`}>
                                                                {getDiasRestantes(certificado.dias_restantes)}
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-2">
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => verDetalles(certificado)}
                                                            >
                                                                <Eye className="w-4 h-4" />
                                                            </Button>
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => router.get(route('admin.firmas.certificado.descargar', certificado.id))}
                                                            >
                                                                <Download className="w-4 h-4" />
                                                            </Button>
                                                            {certificado.estado === 'activo' && (
                                                                <Button
                                                                    size="sm"
                                                                    variant="destructive"
                                                                    onClick={() => revocarCertificado(certificado)}
                                                                    disabled={procesando}
                                                                >
                                                                    <Trash2 className="w-4 h-4" />
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Paginación */}
                            {certificados.meta.last_page > 1 && (
                                <div className="flex items-center justify-between mt-6 pt-4 border-t">
                                    <div className="text-sm text-gray-500">
                                        Mostrando {certificados.meta.from} a {certificados.meta.to} de {certificados.meta.total} certificados
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        {certificados.meta.current_page > 1 && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => router.get(route('admin.firmas.certificados'), { ...filtrosLocales, page: certificados.meta.current_page - 1 })}
                                            >
                                                Anterior
                                            </Button>
                                        )}
                                        <span className="text-sm text-gray-500">
                                            Página {certificados.meta.current_page} de {certificados.meta.last_page}
                                        </span>
                                        {certificados.meta.current_page < certificados.meta.last_page && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => router.get(route('admin.firmas.certificados'), { ...filtrosLocales, page: certificados.meta.current_page + 1 })}
                                            >
                                                Siguiente
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Modal de detalles */}
            <Dialog open={mostrarDetalles} onOpenChange={setMostrarDetalles}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Detalles del Certificado</DialogTitle>
                        <DialogDescription>
                            Información completa del certificado digital
                        </DialogDescription>
                    </DialogHeader>
                    
                    {certificadoSeleccionado && (
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Nombre</label>
                                    <p className="text-sm">{certificadoSeleccionado.nombre_certificado}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Número de Serie</label>
                                    <p className="text-sm font-mono">{certificadoSeleccionado.numero_serie}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Estado</label>
                                    <div className="flex items-center space-x-2">
                                        {getBadgeEstado(certificadoSeleccionado.estado, certificadoSeleccionado.vigente)}
                                    </div>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Tipo</label>
                                    <div>{getBadgeTipo(certificadoSeleccionado.tipo_certificado)}</div>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Algoritmo</label>
                                    <p className="text-sm">{certificadoSeleccionado.algoritmo_firma}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Longitud de Clave</label>
                                    <p className="text-sm">{certificadoSeleccionado.longitud_clave} bits</p>
                                </div>
                            </div>
                            
                            <div>
                                <label className="text-sm font-medium text-gray-500">Emisor</label>
                                <p className="text-sm bg-gray-50 p-2 rounded text-wrap">{certificadoSeleccionado.emisor}</p>
                            </div>
                            
                            <div>
                                <label className="text-sm font-medium text-gray-500">Sujeto</label>
                                <p className="text-sm bg-gray-50 p-2 rounded text-wrap">{certificadoSeleccionado.sujeto}</p>
                            </div>
                            
                            <div>
                                <label className="text-sm font-medium text-gray-500">Huella Digital (SHA-256)</label>
                                <p className="text-xs font-mono bg-gray-50 p-2 rounded break-all">{certificadoSeleccionado.huella_digital}</p>
                            </div>
                            
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Fecha de Emisión</label>
                                    <p className="text-sm">{formatearFecha(certificadoSeleccionado.fecha_emision)}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Fecha de Vencimiento</label>
                                    <p className="text-sm">{formatearFecha(certificadoSeleccionado.fecha_vencimiento)}</p>
                                </div>
                            </div>
                            
                            {certificadoSeleccionado.uso_permitido && (
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Uso Permitido</label>
                                    <div className="flex flex-wrap gap-1 mt-1">
                                        {certificadoSeleccionado.uso_permitido.map((uso, index) => (
                                            <Badge key={index} variant="outline" className="text-xs">
                                                {uso}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                            )}
                            
                            {certificadoSeleccionado.revocado_en && (
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Revocado</label>
                                    <p className="text-sm text-red-600">
                                        {formatearFecha(certificadoSeleccionado.revocado_en)}
                                        {certificadoSeleccionado.razon_revocacion && (
                                            <span className="block text-xs text-gray-500 mt-1">
                                                Razón: {certificadoSeleccionado.razon_revocacion}
                                            </span>
                                        )}
                                    </p>
                                </div>
                            )}
                        </div>
                    )}

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setMostrarDetalles(false)}>
                            Cerrar
                        </Button>
                        {certificadoSeleccionado && (
                            <Button onClick={() => router.get(route('admin.firmas.certificado.descargar', certificadoSeleccionado.id))}>
                                <Download className="w-4 h-4 mr-2" />
                                Descargar
                            </Button>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
