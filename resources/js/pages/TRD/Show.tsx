import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { 
    ArrowLeft, 
    Edit, 
    Trash2, 
    CheckCircle, 
    Clock, 
    AlertCircle, 
    Eye,
    History,
    Download,
    FileText,
    Calendar,
    User,
    Building,
    Hash,
    Tag
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
    Table, 
    TableBody, 
    TableCell, 
    TableHead, 
    TableHeader, 
    TableRow 
} from '@/components/ui/table';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Separator } from '@/components/ui/separator';

interface TrdSubseries {
    id: number;
    subseries_code: string;
    subseries_name: string;
    description: string;
    document_type: string;
    retention_archive_management: number;
    retention_central_archive: number;
    final_disposition: 'conservation_total' | 'selection' | 'elimination';
    access_restrictions?: string;
    procedure?: string;
    order_index: number;
}

interface TrdSeries {
    id: number;
    series_code: string;
    series_name: string;
    description: string;
    order_index: number;
    subseries: TrdSubseries[];
}

interface TrdSection {
    id: number;
    section_code: string;
    section_name: string;
    description: string;
    order_index: number;
    series: TrdSeries[];
}

interface TrdVersion {
    id: number;
    version: string;
    changes_summary: any;
    change_notes?: string;
    created_at: string;
    creator: {
        name: string;
    };
}

interface TrdTable {
    id: number;
    name: string;
    description?: string;
    code: string;
    entity_name: string;
    entity_code: string;
    version: string;
    status: 'draft' | 'active' | 'archived' | 'obsolete';
    approval_date?: string;
    effective_date?: string;
    expiry_date?: string;
    created_at: string;
    updated_at: string;
    creator: {
        name: string;
    };
    approver?: {
        name: string;
    };
    sections: TrdSection[];
    versions: TrdVersion[];
}

interface Props {
    trd: TrdTable;
}

const statusConfig = {
    draft: { label: 'Borrador', color: 'bg-gray-100 text-gray-800', icon: Clock },
    active: { label: 'Activa', color: 'bg-green-100 text-green-800', icon: CheckCircle },
    archived: { label: 'Archivada', color: 'bg-blue-100 text-blue-800', icon: Eye },
    obsolete: { label: 'Obsoleta', color: 'bg-red-100 text-red-800', icon: AlertCircle }
};

const dispositionConfig = {
    conservation_total: { label: 'CT', color: 'bg-green-100 text-green-800', description: 'Conservación Total' },
    selection: { label: 'S', color: 'bg-yellow-100 text-yellow-800', description: 'Selección' },
    elimination: { label: 'E', color: 'bg-red-100 text-red-800', description: 'Eliminación' }
};

export default function Show({ trd }: Props) {
    const [activeTab, setActiveTab] = useState('structure');
    const statusInfo = statusConfig[trd.status];
    const StatusIcon = statusInfo.icon;

    const handleDelete = () => {
        if (confirm(`¿Está seguro de eliminar la TRD "${trd.name}"?`)) {
            router.delete(`/trd/${trd.id}`);
        }
    };

    const handleApprove = () => {
        if (confirm(`¿Está seguro de aprobar la TRD "${trd.name}"?`)) {
            router.post(`/trd/${trd.id}/approve`);
        }
    };

    const handleRestoreVersion = (versionId: number) => {
        if (confirm('¿Está seguro de restaurar esta versión? Se perderán los cambios actuales.')) {
            router.post(`/trd/${trd.id}/restore-version/${versionId}`);
        }
    };

    const getTotalSubseries = () => {
        return trd.sections.reduce((total, section) => {
            return total + section.series.reduce((seriesTotal, series) => {
                return seriesTotal + series.subseries.length;
            }, 0);
        }, 0);
    };

    return (
        <AppLayout>
            <div className="flex items-center justify-between mb-6">
                <div className="flex items-center gap-4">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => router.get('/trd')}
                    >
                        <ArrowLeft className="w-4 h-4 mr-2" />
                        Volver
                    </Button>
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            {trd.name}
                        </h2>
                        <p className="text-sm text-gray-600">
                            Código: {trd.code} • Versión {trd.version}
                        </p>
                    </div>
                </div>
                <div className="flex gap-2">
                    {trd.status === 'draft' && (
                        <Button
                            variant="default"
                            size="sm"
                            onClick={handleApprove}
                            className="text-white bg-green-600 hover:bg-green-700"
                        >
                            <CheckCircle className="w-4 h-4 mr-2" />
                            Aprobar
                        </Button>
                    )}
                    
                    <Link href={`/trd/${trd.id}/edit`}>
                        <Button variant="outline" size="sm">
                            <Edit className="w-4 h-4 mr-2" />
                            Editar
                        </Button>
                    </Link>

                    <Button
                        variant="destructive"
                        size="sm"
                        onClick={handleDelete}
                    >
                        <Trash2 className="w-4 h-4 mr-2" />
                        Eliminar
                    </Button>
                </div>
            </div>
            <Head title={`TRD - ${trd.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Información General */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Total Secciones
                                </CardTitle>
                                <Building className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{trd.sections.length}</div>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Total Series
                                </CardTitle>
                                <FileText className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {trd.sections.reduce((total, section) => total + section.series.length, 0)}
                                </div>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Total Subseries
                                </CardTitle>
                                <Hash className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{getTotalSubseries()}</div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Información Detallada */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>Información General</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-4">
                                    <div className="flex items-center gap-2">
                                        <Building className="w-4 h-4 text-gray-400" />
                                        <div>
                                            <p className="text-sm text-gray-600">Entidad</p>
                                            <p className="font-medium">{trd.entity_name}</p>
                                            <p className="text-sm text-gray-500">{trd.entity_code}</p>
                                        </div>
                                    </div>
                                    
                                    <div className="flex items-center gap-2">
                                        <User className="w-4 h-4 text-gray-400" />
                                        <div>
                                            <p className="text-sm text-gray-600">Creada por</p>
                                            <p className="font-medium">{trd.creator.name}</p>
                                            <p className="text-sm text-gray-500">
                                                {new Date(trd.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>

                                    {trd.approver && (
                                        <div className="flex items-center gap-2">
                                            <CheckCircle className="w-4 h-4 text-green-500" />
                                            <div>
                                                <p className="text-sm text-gray-600">Aprobada por</p>
                                                <p className="font-medium">{trd.approver.name}</p>
                                                {trd.approval_date && (
                                                    <p className="text-sm text-gray-500">
                                                        {new Date(trd.approval_date).toLocaleDateString()}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </div>

                                <div className="space-y-4">
                                    {trd.description && (
                                        <div>
                                            <p className="text-sm text-gray-600 mb-1">Descripción</p>
                                            <p className="text-sm">{trd.description}</p>
                                        </div>
                                    )}

                                    {(trd.effective_date || trd.expiry_date) && (
                                        <div className="flex items-center gap-2">
                                            <Calendar className="w-4 h-4 text-gray-400" />
                                            <div>
                                                <p className="text-sm text-gray-600">Vigencia</p>
                                                <p className="text-sm">
                                                    {trd.effective_date ? new Date(trd.effective_date).toLocaleDateString() : 'N/A'} 
                                                    {' - '}
                                                    {trd.expiry_date ? new Date(trd.expiry_date).toLocaleDateString() : 'Indefinida'}
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Tabs de Contenido */}
                    <Tabs value={activeTab} onValueChange={setActiveTab}>
                        <TabsList className="grid w-full grid-cols-2">
                            <TabsTrigger value="structure">Estructura TRD</TabsTrigger>
                            <TabsTrigger value="versions">
                                Historial de Versiones ({trd.versions.length})
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="structure" className="space-y-6">
                            {trd.sections.length > 0 ? (
                                trd.sections.map((section) => (
                                    <Card key={section.id}>
                                        <CardHeader>
                                            <CardTitle className="flex items-center gap-2">
                                                <Badge variant="outline">{section.section_code}</Badge>
                                                {section.section_name}
                                            </CardTitle>
                                            {section.description && (
                                                <CardDescription>{section.description}</CardDescription>
                                            )}
                                        </CardHeader>
                                        <CardContent>
                                            {section.series.length > 0 ? (
                                                <div className="space-y-6">
                                                    {section.series.map((series) => (
                                                        <div key={series.id} className="border-l-2 border-gray-200 pl-4">
                                                            <div className="mb-3">
                                                                <div className="flex items-center gap-2 mb-1">
                                                                    <Badge variant="secondary">{series.series_code}</Badge>
                                                                    <span className="font-medium">{series.series_name}</span>
                                                                </div>
                                                                {series.description && (
                                                                    <p className="text-sm text-gray-600">{series.description}</p>
                                                                )}
                                                            </div>

                                                            {series.subseries.length > 0 && (
                                                                <div className="overflow-x-auto">
                                                                    <Table>
                                                                        <TableHeader>
                                                                            <TableRow>
                                                                                <TableHead>Código</TableHead>
                                                                                <TableHead>Subserie</TableHead>
                                                                                <TableHead>Tipo Documental</TableHead>
                                                                                <TableHead className="text-center">AG</TableHead>
                                                                                <TableHead className="text-center">AC</TableHead>
                                                                                <TableHead className="text-center">Total</TableHead>
                                                                                <TableHead className="text-center">Disposición</TableHead>
                                                                            </TableRow>
                                                                        </TableHeader>
                                                                        <TableBody>
                                                                            {series.subseries.map((subseries) => {
                                                                                const disposition = dispositionConfig[subseries.final_disposition];
                                                                                const totalRetention = subseries.retention_archive_management + subseries.retention_central_archive;
                                                                                
                                                                                return (
                                                                                    <TableRow key={subseries.id}>
                                                                                        <TableCell className="font-mono text-sm">
                                                                                            {subseries.subseries_code}
                                                                                        </TableCell>
                                                                                        <TableCell>
                                                                                            <div>
                                                                                                <div className="font-medium">{subseries.subseries_name}</div>
                                                                                                {subseries.description && (
                                                                                                    <div className="text-sm text-gray-500">{subseries.description}</div>
                                                                                                )}
                                                                                            </div>
                                                                                        </TableCell>
                                                                                        <TableCell>{subseries.document_type}</TableCell>
                                                                                        <TableCell className="text-center">
                                                                                            {subseries.retention_archive_management} años
                                                                                        </TableCell>
                                                                                        <TableCell className="text-center">
                                                                                            {subseries.retention_central_archive} años
                                                                                        </TableCell>
                                                                                        <TableCell className="text-center font-medium">
                                                                                            {totalRetention} años
                                                                                        </TableCell>
                                                                                        <TableCell className="text-center">
                                                                                            <Badge 
                                                                                                variant="secondary" 
                                                                                                className={disposition.color}
                                                                                                title={disposition.description}
                                                                                            >
                                                                                                {disposition.label}
                                                                                            </Badge>
                                                                                        </TableCell>
                                                                                    </TableRow>
                                                                                );
                                                                            })}
                                                                        </TableBody>
                                                                    </Table>
                                                                </div>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : (
                                                <p className="text-gray-500 text-center py-4">
                                                    No hay series documentales definidas para esta sección.
                                                </p>
                                            )}
                                        </CardContent>
                                    </Card>
                                ))
                            ) : (
                                <Card>
                                    <CardContent className="text-center py-8">
                                        <p className="text-gray-500 mb-4">
                                            Esta TRD no tiene estructura definida aún.
                                        </p>
                                        <Link href={`/trd/${trd.id}/edit`}>
                                            <Button>
                                                <Edit className="w-4 h-4 mr-2" />
                                                Editar Estructura
                                            </Button>
                                        </Link>
                                    </CardContent>
                                </Card>
                            )}
                        </TabsContent>

                        <TabsContent value="versions" className="space-y-4">
                            {trd.versions.length > 0 ? (
                                trd.versions.map((version) => (
                                    <Card key={version.id}>
                                        <CardHeader>
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <CardTitle className="flex items-center gap-2">
                                                        <History className="w-4 h-4" />
                                                        Versión {version.version}
                                                    </CardTitle>
                                                    <CardDescription>
                                                        {version.creator.name} • {new Date(version.created_at).toLocaleString()}
                                                    </CardDescription>
                                                </div>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleRestoreVersion(version.id)}
                                                >
                                                    Restaurar
                                                </Button>
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            {version.change_notes && (
                                                <p className="text-sm mb-2">{version.change_notes}</p>
                                            )}
                                            {version.changes_summary && (
                                                <div className="text-sm text-gray-600">
                                                    <strong>Resumen:</strong> {version.changes_summary.summary || version.changes_summary.action}
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                ))
                            ) : (
                                <Card>
                                    <CardContent className="text-center py-8">
                                        <p className="text-gray-500">
                                            No hay versiones anteriores disponibles.
                                        </p>
                                    </CardContent>
                                </Card>
                            )}
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </AppLayout>
    );
}
