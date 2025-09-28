import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { PlusCircle, Search, Filter, Download, Shield, AlertTriangle, CheckCircle, XCircle, Clock, RefreshCw } from 'lucide-react';
import AppLayout from '../../../layouts/app/app-sidebar-layout';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Input } from '../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { Badge } from '../../../components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';

interface CertificadoDigital {
    id: number;
    nombre_certificado: string;
    numero_serie: string;
    emisor: string;
    sujeto: string;
    algoritmo_firma: string;
    longitud_clave: number;
    fecha_emision: string;
    fecha_vencimiento: string;
    estado: string;
    tipo_certificado: string;
    uso_permitido: string[];
    dias_restantes: number;
    vigente: boolean;
    proximo_a_vencer: boolean;
    usuario: {
        id: number;
        name: string;
        email: string;
    };
}

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Estadisticas {
    total: number;
    activos: number;
    vencidos: number;
    proximos_vencer: number;
    revocados: number;
    por_tipo: Record<string, number>;
    usuarios_con_certificados: number;
    firmas_realizadas: number;
}

interface Props {
    certificados: {
        data: CertificadoDigital[];
        links: any[];
        meta: any;
    };
    estadisticas: Estadisticas;
    usuarios: Usuario[];
    filtros: {
        buscar?: string;
        estado?: string;
        tipo_certificado?: string;
        usuario_id?: string;
        vencimiento?: string;
    };
}

export default function CertificadosIndex({ certificados, estadisticas, usuarios, filtros }: Props) {
    const [isLoading, setIsLoading] = useState(false);

    const getBadgeVariant = (estado: string) => {
        switch (estado) {
            case 'activo':
                return 'default';
            case 'vencido':
                return 'destructive';
            case 'revocado':
                return 'secondary';
            case 'suspendido':
                return 'outline';
            default:
                return 'secondary';
        }
    };

    const getEstadoIcon = (certificado: CertificadoDigital) => {
        if (certificado.estado === 'revocado') {
            return <XCircle className="h-4 w-4 text-red-500" />;
        }
        if (!certificado.vigente) {
            return <AlertTriangle className="h-4 w-4 text-orange-500" />;
        }
        if (certificado.proximo_a_vencer) {
            return <Clock className="h-4 w-4 text-yellow-500" />;
        }
        return <CheckCircle className="h-4 w-4 text-green-500" />;
    };

    const getTipoLabel = (tipo: string) => {
        const tipos: Record<string, string> = {
            'usuario': 'Usuario',
            'servidor': 'Servidor',
            'autoridad_certificadora': 'Autoridad CA',
            'sello_tiempo': 'Sello de Tiempo'
        };
        return tipos[tipo] || tipo;
    };

    const handleFiltrar = (campo: string, valor: string) => {
        const nuevaUrl = route('admin.certificados.index', {
            ...filtros,
            [campo]: valor === 'all' ? '' : valor,
            page: 1
        });
        router.get(nuevaUrl);
    };

    const handleBuscar = (buscar: string) => {
        const nuevaUrl = route('admin.certificados.index', {
            ...filtros,
            buscar,
            page: 1
        });
        router.get(nuevaUrl);
    };

    const handleDescargar = (certificadoId: number, formato: string) => {
        setIsLoading(true);
        window.location.href = route('admin.certificados.descargar', { certificado: certificadoId, formato });
        setTimeout(() => setIsLoading(false), 2000);
    };

    const refreshData = () => {
        router.reload();
    };

    return (
        <AppLayout>
            <Head title="Certificados Digitales" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Certificados Digitales</h1>
                        <p className="text-muted-foreground">
                            Gestión y administración de certificados digitales PKI
                        </p>
                    </div>
                    <div className="flex items-center space-x-3">
                        <Button variant="outline" size="sm" onClick={refreshData}>
                            <RefreshCw className="h-4 w-4 mr-2" />
                            Actualizar
                        </Button>
                        <Link href={route('admin.certificados.create')}>
                            <Button>
                                <PlusCircle className="h-4 w-4 mr-2" />
                                Nuevo Certificado
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Estadísticas */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <Shield className="h-8 w-8 text-blue-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total Certificados</p>
                                    <p className="text-2xl font-bold">{estadisticas.total}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <CheckCircle className="h-8 w-8 text-green-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Activos</p>
                                    <p className="text-2xl font-bold">{estadisticas.activos}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <AlertTriangle className="h-8 w-8 text-orange-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Próximos a Vencer</p>
                                    <p className="text-2xl font-bold">{estadisticas.proximos_vencer}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <XCircle className="h-8 w-8 text-red-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Vencidos/Revocados</p>
                                    <p className="text-2xl font-bold">{estadisticas.vencidos + estadisticas.revocados}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filtros */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center">
                            <Filter className="h-5 w-5 mr-2" />
                            Filtros de Búsqueda
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Búsqueda</label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                    <Input
                                        placeholder="Buscar certificados..."
                                        defaultValue={filtros.buscar}
                                        className="pl-9"
                                        onChange={(e) => {
                                            clearTimeout((window as any).searchTimeout);
                                            (window as any).searchTimeout = setTimeout(() => {
                                                handleBuscar(e.target.value);
                                            }, 500);
                                        }}
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Estado</label>
                                <Select value={filtros.estado || 'all'} onValueChange={(value) => handleFiltrar('estado', value)}>
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

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Tipo</label>
                                <Select value={filtros.tipo_certificado || 'all'} onValueChange={(value) => handleFiltrar('tipo_certificado', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los tipos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los tipos</SelectItem>
                                        <SelectItem value="usuario">Usuario</SelectItem>
                                        <SelectItem value="servidor">Servidor</SelectItem>
                                        <SelectItem value="autoridad_certificadora">Autoridad CA</SelectItem>
                                        <SelectItem value="sello_tiempo">Sello de Tiempo</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Usuario</label>
                                <Select value={filtros.usuario_id || 'all'} onValueChange={(value) => handleFiltrar('usuario_id', value)}>
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

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Vencimiento</label>
                                <Select value={filtros.vencimiento || 'all'} onValueChange={(value) => handleFiltrar('vencimiento', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        <SelectItem value="vigentes">Vigentes</SelectItem>
                                        <SelectItem value="proximos_vencer">Próximos a vencer</SelectItem>
                                        <SelectItem value="vencidos">Vencidos</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Lista de Certificados */}
                <Card>
                    <CardHeader>
                        <CardTitle>Certificados Digitales ({certificados.meta?.total || 0})</CardTitle>
                        <CardDescription>
                            Mostrando {certificados.meta?.from || 0} a {certificados.meta?.to || 0} de {certificados.meta?.total || 0} certificados
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Estado</TableHead>
                                        <TableHead>Certificado</TableHead>
                                        <TableHead>Usuario</TableHead>
                                        <TableHead>Tipo</TableHead>
                                        <TableHead>Algoritmo</TableHead>
                                        <TableHead>Vencimiento</TableHead>
                                        <TableHead>Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {certificados.data.map((certificado) => (
                                        <TableRow key={certificado.id}>
                                            <TableCell>
                                                <div className="flex items-center space-x-2">
                                                    {getEstadoIcon(certificado)}
                                                    <Badge variant={getBadgeVariant(certificado.estado)}>
                                                        {certificado.estado}
                                                    </Badge>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div>
                                                    <div className="font-medium">{certificado.nombre_certificado}</div>
                                                    <div className="text-sm text-gray-500">
                                                        Serie: {certificado.numero_serie.slice(0, 16)}...
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div>
                                                    <div className="font-medium">{certificado.usuario.name}</div>
                                                    <div className="text-sm text-gray-500">{certificado.usuario.email}</div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {getTipoLabel(certificado.tipo_certificado)}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="text-sm">
                                                    <div>{certificado.algoritmo_firma}</div>
                                                    <div className="text-gray-500">{certificado.longitud_clave} bits</div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="text-sm">
                                                    <div>{new Date(certificado.fecha_vencimiento).toLocaleDateString()}</div>
                                                    {certificado.vigente && (
                                                        <div className={`text-xs ${certificado.proximo_a_vencer ? 'text-orange-600' : 'text-gray-500'}`}>
                                                            {certificado.dias_restantes} días restantes
                                                        </div>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center space-x-2">
                                                    <Link href={route('admin.certificados.show', certificado.id)}>
                                                        <Button variant="outline" size="sm">
                                                            Ver
                                                        </Button>
                                                    </Link>
                                                    <Select onValueChange={(formato) => handleDescargar(certificado.id, formato)}>
                                                        <SelectTrigger className="w-auto h-8">
                                                            <Download className="h-4 w-4" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="pem">Descargar PEM</SelectItem>
                                                            <SelectItem value="der">Descargar DER</SelectItem>
                                                            <SelectItem value="crt">Descargar CRT</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {certificados.data.length === 0 && (
                            <div className="text-center py-8">
                                <Shield className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">No hay certificados</h3>
                                <p className="text-gray-600 mb-4">
                                    {Object.keys(filtros).length > 0 
                                        ? 'No se encontraron certificados con los filtros aplicados'
                                        : 'Aún no se han creado certificados digitales'
                                    }
                                </p>
                                <Link href={route('admin.certificados.create')}>
                                    <Button>
                                        <PlusCircle className="h-4 w-4 mr-2" />
                                        Crear primer certificado
                                    </Button>
                                </Link>
                            </div>
                        )}

                        {/* Paginación */}
                        {(certificados.meta?.last_page || 0) > 1 && (
                            <div className="flex items-center justify-between mt-6">
                                <div className="text-sm text-gray-700">
                                    Mostrando {certificados.meta?.from || 0} a {certificados.meta?.to || 0} de {certificados.meta?.total || 0} resultados
                                </div>
                                <div className="flex space-x-1">
                                    {(certificados.links || []).map((link: any, index: number) => (
                                        <Button
                                            key={index}
                                            variant={link.active ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => link.url && router.get(link.url)}
                                            disabled={!link.url}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
