import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { 
    Search, 
    Eye, 
    Edit, 
    Trash2, 
    Plus,
    FolderOpen,
    Calendar,
    User,
    Archive,
    FileText
} from 'lucide-react';

interface Expediente {
    id: number;
    numero_expediente: string;
    titulo: string;
    descripcion: string;
    tipo_expediente: string;
    estado_ciclo_vida: string;
    fecha_apertura: string;
    fecha_cierre?: string;
    volumen_actual: number;
    volumen_maximo: number;
    tamaño_mb: number;
    ubicacion_fisica?: string;
    ubicacion_digital?: string;
    created_at: string;
}

interface Props {
    expedientes: {
        data: Expediente[];
        current_page: number;
        last_page: number;
        total: number;
    };
    estadisticas: {
        total: number;
        tramite: number;
        gestion: number;
        central: number;
        historico: number;
        electronicos: number;
        fisicos: number;
        hibridos: number;
        tamaño_total: number;
    };
    opciones: {
        estados: Array<{ value: string; label: string; }>;
        tipos: Array<{ value: string; label: string; }>;
        series_disponibles: Array<{ id: number; codigo: string; nombre: string; }>;
    };
    filtros: {
        search?: string;
        estado?: string;
        tipo_expediente?: string;
        serie_id?: string;
    };
}

const breadcrumbItems = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Expedientes Electrónicos', href: '/admin/expedientes' }
];

export default function Index({ expedientes, estadisticas, opciones, filtros }: Props) {
    const [searchQuery, setSearchQuery] = useState(filtros.search || '');
    const [showFilters, setShowFilters] = useState(false);

    const handleSearch = () => {
        router.get('/admin/expedientes', { 
            search: searchQuery,
            estado: filtros.estado,
            tipo_expediente: filtros.tipo_expediente,
            serie_id: filtros.serie_id
        });
    };

    const getEstadoBadge = (estado: string) => {
        const badges = {
            'tramite': <Badge variant="outline" className="text-blue-600">En Trámite</Badge>,
            'gestion': <Badge variant="outline" className="text-yellow-600">Gestión</Badge>,
            'central': <Badge variant="outline" className="text-green-600">Central</Badge>,
            'historico': <Badge variant="outline" className="text-purple-600">Histórico</Badge>,
            'eliminado': <Badge variant="destructive">Eliminado</Badge>
        };
        return badges[estado as keyof typeof badges] || <Badge variant="outline">{estado}</Badge>;
    };

    const getTipoBadge = (tipo: string) => {
        const badges = {
            'electronico': <Badge className="bg-blue-100 text-blue-800">Electrónico</Badge>,
            'fisico': <Badge className="bg-green-100 text-green-800">Físico</Badge>,
            'hibrido': <Badge className="bg-purple-100 text-purple-800">Híbrido</Badge>
        };
        return badges[tipo as keyof typeof badges] || <Badge>{tipo}</Badge>;
    };

    return (
        <AppLayout>
            <Head title="Expedientes Electrónicos" />
            
            <div className="container mx-auto py-6">
                {/* Header */}
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Expedientes Electrónicos</h1>
                        <p className="text-gray-600 mt-1">Gestión integral de expedientes documentales</p>
                    </div>
                    <Link href="/admin/expedientes/create">
                        <Button className="bg-blue-600 hover:bg-blue-700">
                            <Plus className="w-4 h-4 mr-2" />
                            Nuevo Expediente
                        </Button>
                    </Link>
                </div>

                {/* Estadísticas */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Expedientes</CardTitle>
                            <FolderOpen className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{estadisticas.total}</div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">En Trámite</CardTitle>
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{estadisticas.tramite}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Electrónicos</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{estadisticas.electronicos}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Tamaño Total</CardTitle>
                            <Archive className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{Number(estadisticas.tamaño_total || 0).toFixed(2)} MB</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filtros y Búsqueda */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle>Filtros y Búsqueda</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-4 items-center">
                            <div className="flex-1">
                                <Input
                                    placeholder="Buscar por número, título o descripción..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="w-full"
                                />
                            </div>
                            <Button onClick={handleSearch} className="bg-blue-600 hover:bg-blue-700">
                                <Search className="w-4 h-4 mr-2" />
                                Buscar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Tabla de Expedientes */}
                <Card>
                    <CardHeader>
                        <CardTitle>Lista de Expedientes</CardTitle>
                        <CardDescription>
                            {expedientes.total} expedientes encontrados
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full border-collapse">
                                <thead>
                                    <tr className="border-b">
                                        <th className="text-left p-3">Número</th>
                                        <th className="text-left p-3">Título</th>
                                        <th className="text-left p-3">Tipo</th>
                                        <th className="text-left p-3">Estado</th>
                                        <th className="text-left p-3">Fecha Apertura</th>
                                        <th className="text-left p-3">Tamaño</th>
                                        <th className="text-left p-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {expedientes.data.map((expediente) => (
                                        <tr key={expediente.id} className="border-b hover:bg-gray-50">
                                            <td className="p-3 font-mono text-sm">
                                                {expediente.numero_expediente}
                                            </td>
                                            <td className="p-3">
                                                <div>
                                                    <div className="font-medium">{expediente.titulo}</div>
                                                    <div className="text-sm text-gray-500 truncate max-w-xs">
                                                        {expediente.descripcion}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="p-3">
                                                {getTipoBadge(expediente.tipo_expediente)}
                                            </td>
                                            <td className="p-3">
                                                {getEstadoBadge(expediente.estado_ciclo_vida)}
                                            </td>
                                            <td className="p-3 text-sm">
                                                {new Date(expediente.fecha_apertura).toLocaleDateString()}
                                            </td>
                                            <td className="p-3 text-sm">
                                                {Number(expediente.tamaño_mb || 0).toFixed(2)} MB
                                            </td>
                                            <td className="p-3">
                                                <div className="flex gap-2">
                                                    <Link href={`/admin/expedientes/${expediente.id}`}>
                                                        <Button variant="outline" size="sm">
                                                            <Eye className="w-4 h-4" />
                                                        </Button>
                                                    </Link>
                                                    <Link href={`/admin/expedientes/${expediente.id}/edit`}>
                                                        <Button variant="outline" size="sm">
                                                            <Edit className="w-4 h-4" />
                                                        </Button>
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {expedientes.data.length === 0 && (
                            <div className="text-center py-8 text-gray-500">
                                <FolderOpen className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                                <p>No se encontraron expedientes</p>
                                <Link href="/admin/expedientes/create">
                                    <Button className="mt-4">
                                        <Plus className="w-4 h-4 mr-2" />
                                        Crear primer expediente
                                    </Button>
                                </Link>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
