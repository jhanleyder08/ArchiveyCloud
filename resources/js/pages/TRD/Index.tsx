import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Trash2, Eye, Edit, Plus, FileUp, CheckCircle, Clock, AlertCircle } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { 
    Table, 
    TableBody, 
    TableCell, 
    TableHead, 
    TableHeader, 
    TableRow 
} from '@/components/ui/table';

interface TrdTable {
    id: number;
    name: string;
    code: string;
    entity_name: string;
    entity_code: string;
    version: string;
    status: 'draft' | 'active' | 'archived' | 'obsolete';
    approval_date: string | null;
    effective_date: string | null;
    expiry_date: string | null;
    created_at: string;
    creator: {
        name: string;
    };
    approver?: {
        name: string;
    };
}

interface Props {
    trdTables: {
        data: TrdTable[];
        links?: any[];
        meta?: any;
        current_page?: number;
        last_page?: number;
        per_page?: number;
        total?: number;
    };
    filters: {
        search?: string;
        status?: string;
        entity_code?: string;
    };
}

const statusConfig = {
    draft: { label: 'Borrador', color: 'bg-gray-100 text-gray-800', icon: Clock },
    active: { label: 'Activa', color: 'bg-green-100 text-green-800', icon: CheckCircle },
    archived: { label: 'Archivada', color: 'bg-blue-100 text-blue-800', icon: Eye },
    obsolete: { label: 'Obsoleta', color: 'bg-red-100 text-red-800', icon: AlertCircle }
};

export default function Index({ trdTables, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [entityCode, setEntityCode] = useState(filters.entity_code || '');

    const handleFilter = () => {
        router.get('/trd', { 
            search: search || undefined, 
            status: status || undefined,
            entity_code: entityCode || undefined
        }, {
            preserveState: true,
            replace: true
        });
    };

    const handleDelete = (trd: TrdTable) => {
        if (confirm(`¿Está seguro de eliminar la TRD "${trd.name}"?`)) {
            router.delete(`/trd/${trd.id}`);
        }
    };

    const handleApprove = (trd: TrdTable) => {
        if (confirm(`¿Está seguro de aprobar la TRD "${trd.name}"?`)) {
            router.get('/trd', {params: {id: trd.id, approve: true}});
        }
    };

    return (
        <AppLayout>
            <div className="flex justify-between items-center mb-6">
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    Tablas de Retención Documental (TRD)
                </h2>
                <div className="flex gap-2">
                    <Link href="/trd/import">
                        <Button variant="outline" size="sm">
                            <FileUp className="w-4 h-4 mr-2" />
                            Importar
                        </Button>
                    </Link>
                    <Link href="/trd/create">
                        <Button size="sm">
                            <Plus className="w-4 h-4 mr-2" />
                            Nueva TRD
                        </Button>
                    </Link>
                </div>
            </div>
            <Head title="Tablas de Retención Documental" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filtros */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>Filtros</CardTitle>
                            <CardDescription>
                                Busque y filtre las Tablas de Retención Documental
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-col sm:flex-row gap-4">
                                <div className="flex-1">
                                    <Input
                                        placeholder="Buscar por nombre, código o entidad..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                                    />
                                </div>
                                <div className="w-full sm:w-48">
                                    <Select value={status} onValueChange={setStatus}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Estado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Todos los estados</SelectItem>
                                            <SelectItem value="draft">Borrador</SelectItem>
                                            <SelectItem value="active">Activa</SelectItem>
                                            <SelectItem value="archived">Archivada</SelectItem>
                                            <SelectItem value="obsolete">Obsoleta</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="w-full sm:w-48">
                                    <Input
                                        placeholder="Código entidad"
                                        value={entityCode}
                                        onChange={(e) => setEntityCode(e.target.value)}
                                    />
                                </div>
                                <Button onClick={handleFilter}>
                                    Filtrar
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Tabla de TRDs */}
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                Tablas de Retención Documental ({trdTables.meta?.total || 0})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {trdTables?.data?.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Código</TableHead>
                                                <TableHead>Nombre</TableHead>
                                                <TableHead>Entidad</TableHead>
                                                <TableHead>Versión</TableHead>
                                                <TableHead>Estado</TableHead>
                                                <TableHead>Creada por</TableHead>
                                                <TableHead>Fecha creación</TableHead>
                                                <TableHead className="text-right">Acciones</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {trdTables?.data?.map((trd) => {
                                                const statusInfo = statusConfig[trd.status];
                                                const StatusIcon = statusInfo.icon;
                                                
                                                return (
                                                    <TableRow key={trd.id}>
                                                        <TableCell className="font-mono text-sm">
                                                            {trd.code}
                                                        </TableCell>
                                                        <TableCell>
                                                            <div>
                                                                <div className="font-medium">{trd.name}</div>
                                                                {trd.entity_code && (
                                                                    <div className="text-sm text-gray-500">
                                                                        {trd.entity_code}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>{trd.entity_name}</TableCell>
                                                        <TableCell className="font-mono">
                                                            v{trd.version}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge 
                                                                variant="secondary" 
                                                                className={statusInfo.color}
                                                            >
                                                                <StatusIcon className="w-3 h-3 mr-1" />
                                                                {statusInfo.label}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell>{trd.creator.name}</TableCell>
                                                        <TableCell>
                                                            {new Date(trd.created_at).toLocaleDateString()}
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            <div className="flex justify-end gap-2">
                                                                <Link href={`/trd/${trd.id}`}>
                                                                    <Button variant="ghost" size="sm">
                                                                        <Eye className="w-4 h-4" />
                                                                    </Button>
                                                                </Link>
                                                                
                                                                <Link href={`/trd/${trd.id}/edit`}>
                                                                    <Button variant="ghost" size="sm">
                                                                        <Edit className="w-4 h-4" />
                                                                    </Button>
                                                                </Link>

                                                                {trd.status === 'draft' && (
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() => handleApprove(trd)}
                                                                        className="text-green-600 hover:text-green-700"
                                                                    >
                                                                        <CheckCircle className="w-4 h-4" />
                                                                    </Button>
                                                                )}
                                                                
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => handleDelete(trd)}
                                                                    className="text-red-600 hover:text-red-700"
                                                                >
                                                                    <Trash2 className="w-4 h-4" />
                                                                </Button>
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })}
                                        </TableBody>
                                    </Table>
                                </div>
                            ) : (
                                <div className="text-center py-8">
                                    <div className="text-gray-500 mb-4">
                                        No se encontraron Tablas de Retención Documental
                                    </div>
                                    <Link href="/trd/create">
                                        <Button>
                                            <Plus className="w-4 h-4 mr-2" />
                                            Crear primera TRD
                                        </Button>
                                    </Link>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Paginación */}
                    {trdTables?.links && trdTables.links.length > 3 && (
                        <div className="mt-6 flex justify-center">
                            <div className="flex gap-2">
                                {trdTables.links.map((link: any, index: number) => (
                                    <Button
                                        key={index}
                                        variant={link.active ? "default" : "outline"}
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url)}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
