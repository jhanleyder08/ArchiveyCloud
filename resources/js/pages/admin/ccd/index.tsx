import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { 
    FileText, 
    Plus, 
    Search, 
    Eye, 
    Edit, 
    Trash2, 
    TrendingUp,
    Users,
    CheckCircle,
    AlertCircle,
    FolderTree
} from 'lucide-react';
import { toast } from 'sonner';

interface CCD {
    id: number;
    codigo: string;
    nombre: string;
    descripcion?: string;
    estado: 'borrador' | 'activo' | 'inactivo' | 'historico';
    created_at: string;
    updated_at: string;
    creador?: {
        id: number;
        name: string;
    };
    niveles_count?: number;
}

interface CCDIndexProps {
    ccds: {
        data: CCD[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: any[];
    };
    estadisticas: {
        total: number;
        activos: number;
        borradores: number;
        vigentes: number;
    };
    filters: {
        search?: string;
        estado?: string;
    };
}

export default function CCDIndex({ ccds, estadisticas, filters }: CCDIndexProps) {
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [filterEstado, setFilterEstado] = useState(filters?.estado || 'all');

    const handleSearch = () => {
        router.get('/admin/ccd', {
            search: searchTerm,
            estado: filterEstado !== 'all' ? filterEstado : undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const getEstadoBadge = (estado: string) => {
        const badges = {
            'borrador': <Badge variant="secondary" className="bg-gray-100 text-gray-800">Borrador</Badge>,
            'activo': <Badge variant="default" className="bg-green-100 text-green-800">Activo</Badge>,
            'inactivo': <Badge variant="destructive" className="bg-red-100 text-red-800">Inactivo</Badge>,
            'historico': <Badge variant="outline" className="bg-blue-100 text-blue-800">Histórico</Badge>,
        };
        return badges[estado as keyof typeof badges] || <Badge>{estado}</Badge>;
    };

    return (
        <AppLayout>
            <Head title="Cuadros de Clasificación Documental" />
            
            <div className="space-y-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Cuadros de Clasificación Documental</h1>
                        <p className="text-muted-foreground">
                            Gestión de cuadros de clasificación documental del sistema
                        </p>
                    </div>
                    <Link href="/admin/ccd/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo CCD
                        </Button>
                    </Link>
                </div>

                {/* Estadísticas */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total CCDs</CardTitle>
                            <FolderTree className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{estadisticas?.total || 0}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Activos</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{estadisticas?.activos || 0}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Borradores</CardTitle>
                            <AlertCircle className="h-4 w-4 text-yellow-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{estadisticas?.borradores || 0}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Vigentes</CardTitle>
                            <TrendingUp className="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{estadisticas?.vigentes || 0}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filtros */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-4">
                            <div className="flex-1">
                                <Input
                                    placeholder="Buscar por código o nombre..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                />
                            </div>
                            <Select
                                value={filterEstado}
                                onValueChange={setFilterEstado}
                            >
                                <SelectTrigger className="w-48">
                                    <SelectValue placeholder="Estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="borrador">Borrador</SelectItem>
                                    <SelectItem value="activo">Activo</SelectItem>
                                    <SelectItem value="inactivo">Inactivo</SelectItem>
                                    <SelectItem value="historico">Histórico</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button onClick={handleSearch}>
                                <Search className="mr-2 h-4 w-4" />
                                Buscar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Lista de CCDs */}
                <Card>
                    <CardHeader>
                        <CardTitle>Lista de CCDs</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {ccds.data.length === 0 ? (
                            <div className="text-center py-8">
                                <FileText className="mx-auto h-12 w-12 text-muted-foreground" />
                                <h3 className="mt-2 text-sm font-semibold text-gray-900">No hay CCDs</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    No se encontraron cuadros de clasificación documental.
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left py-3 px-4">Código</th>
                                            <th className="text-left py-3 px-4">Nombre</th>
                                            <th className="text-left py-3 px-4">Estado</th>
                                            <th className="text-left py-3 px-4">Niveles</th>
                                            <th className="text-left py-3 px-4">Creado</th>
                                            <th className="text-right py-3 px-4">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {ccds.data.map((ccd) => (
                                            <tr key={ccd.id} className="border-b hover:bg-gray-50">
                                                <td className="py-3 px-4 font-medium">{ccd.codigo}</td>
                                                <td className="py-3 px-4">
                                                    <div>
                                                        <div className="font-medium">{ccd.nombre}</div>
                                                        {ccd.descripcion && (
                                                            <div className="text-sm text-gray-500">{ccd.descripcion}</div>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="py-3 px-4">
                                                    {getEstadoBadge(ccd.estado)}
                                                </td>
                                                <td className="py-3 px-4">
                                                    <Badge variant="outline">
                                                        {ccd.niveles_count || 0} niveles
                                                    </Badge>
                                                </td>
                                                <td className="py-3 px-4 text-sm text-gray-500">
                                                    {new Date(ccd.created_at).toLocaleDateString()}
                                                </td>
                                                <td className="py-3 px-4 text-right">
                                                    <div className="flex justify-end gap-2">
                                                        <Link href={`/admin/ccd/${ccd.id}`}>
                                                            <Button variant="outline" size="sm">
                                                                <Eye className="h-4 w-4" />
                                                            </Button>
                                                        </Link>
                                                        <Link href={`/admin/ccd/${ccd.id}/edit`}>
                                                            <Button variant="outline" size="sm">
                                                                <Edit className="h-4 w-4" />
                                                            </Button>
                                                        </Link>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Paginación */}
                {ccds.last_page > 1 && (
                    <div className="flex justify-between items-center">
                        <div className="text-sm text-gray-700">
                            Mostrando {((ccds.current_page - 1) * ccds.per_page) + 1} a{' '}
                            {Math.min(ccds.current_page * ccds.per_page, ccds.total)} de {ccds.total} resultados
                        </div>
                        <div className="flex gap-2">
                            {ccds.current_page > 1 && (
                                <Button
                                    variant="outline"
                                    onClick={() => router.get(`/admin/ccd?page=${ccds.current_page - 1}`, {
                                        search: searchTerm,
                                        estado: filterEstado !== 'all' ? filterEstado : undefined,
                                    })}
                                >
                                    Anterior
                                </Button>
                            )}
                            {ccds.current_page < ccds.last_page && (
                                <Button
                                    variant="outline"
                                    onClick={() => router.get(`/admin/ccd?page=${ccds.current_page + 1}`, {
                                        search: searchTerm,
                                        estado: filterEstado !== 'all' ? filterEstado : undefined,
                                    })}
                                >
                                    Siguiente
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
