import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { FileText, Calendar, MapPin, User, Tag, Shield, Edit, ArrowLeft, Folder, Archive } from 'lucide-react';

interface Expediente {
    id: number;
    codigo: string;
    titulo: string;
    descripcion?: string;
    estado: string;
    tipo_expediente: string;
    nivel_acceso: string;
    fecha_apertura: string;
    fecha_cierre?: string;
    fecha_eliminacion?: string;
    ubicacion_fisica?: string;
    palabras_clave?: string[];
    notas?: string;
    numero_documentos: number;
    anos_archivo_gestion?: number;
    anos_archivo_central?: number;
    disposicion_final?: string;
    serie?: { id: number; codigo: string; nombre: string };
    subserie?: { id: number; codigo: string; nombre: string };
    responsable?: { id: number; name: string; email: string };
    created_at: string;
    updated_at: string;
}

interface Props {
    expediente: Expediente;
}

const estadoColors: Record<string, string> = {
    en_tramite: 'bg-blue-100 text-blue-800',
    activo: 'bg-green-100 text-green-800',
    inactivo: 'bg-gray-100 text-gray-800',
    transferido: 'bg-indigo-100 text-indigo-800',
    eliminado: 'bg-red-100 text-red-800',
};

const estadoLabels: Record<string, string> = {
    en_tramite: 'En Tramite',
    activo: 'Activo',
    inactivo: 'Inactivo',
    transferido: 'Transferido',
    eliminado: 'Eliminado',
};

const nivelAccesoColors: Record<string, string> = {
    publico: 'bg-green-100 text-green-800',
    restringido: 'bg-yellow-100 text-yellow-800',
    confidencial: 'bg-orange-100 text-orange-800',
    reservado: 'bg-red-100 text-red-800',
};

const tipoLabels: Record<string, string> = {
    administrativo: 'Administrativo',
    contable: 'Contable',
    juridico: 'Juridico',
    tecnico: 'Tecnico',
    historico: 'Historico',
    personal: 'Personal',
};

const breadcrumbItems = [
    { title: 'Inicio', href: '/dashboard' },
    { title: 'Expedientes', href: '/admin/expedientes' },
    { title: 'Detalle', href: '#' },
];

export default function Show({ expediente }: Props) {
    const formatDate = (dateString?: string) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('es-CO');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title={'Expediente ' + expediente.codigo} />
            <div className="space-y-6">
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <Link href="/admin/expedientes">
                            <Button variant="outline" size="icon"><ArrowLeft className="h-4 w-4" /></Button>
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold">{expediente.titulo}</h1>
                            <p className="text-muted-foreground font-mono">{expediente.codigo}</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Badge className={estadoColors[expediente.estado] || 'bg-gray-100'}>{estadoLabels[expediente.estado] || expediente.estado}</Badge>
                        <Badge className={nivelAccesoColors[expediente.nivel_acceso] || 'bg-gray-100'}><Shield className="h-3 w-3 mr-1" />{expediente.nivel_acceso}</Badge>
                    </div>
                </div>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2 space-y-6">
                        <Card>
                            <CardHeader><CardTitle className="flex items-center gap-2"><FileText className="h-5 w-5" />Informacion Basica</CardTitle></CardHeader>
                            <CardContent className="space-y-4">
                                {expediente.descripcion && <div><label className="text-sm font-medium text-muted-foreground">Descripcion</label><p className="mt-1">{expediente.descripcion}</p></div>}
                                <div className="grid grid-cols-2 gap-4">
                                    <div><label className="text-sm font-medium text-muted-foreground">Tipo</label><p className="mt-1">{tipoLabels[expediente.tipo_expediente] || expediente.tipo_expediente}</p></div>
                                    <div><label className="text-sm font-medium text-muted-foreground">Documentos</label><p className="mt-1">{expediente.numero_documentos || 0}</p></div>
                                </div>
                                {expediente.notas && <div><label className="text-sm font-medium text-muted-foreground">Notas</label><p className="mt-1 text-sm">{expediente.notas}</p></div>}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader><CardTitle className="flex items-center gap-2"><Folder className="h-5 w-5" />Clasificacion</CardTitle></CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-2 gap-4">
                                    <div><label className="text-sm font-medium text-muted-foreground">Serie</label><p className="mt-1">{expediente.serie ? expediente.serie.codigo + ' - ' + expediente.serie.nombre : 'N/A'}</p></div>
                                    <div><label className="text-sm font-medium text-muted-foreground">Subserie</label><p className="mt-1">{expediente.subserie ? expediente.subserie.codigo + ' - ' + expediente.subserie.nombre : 'N/A'}</p></div>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader><CardTitle className="flex items-center gap-2"><Archive className="h-5 w-5" />Retencion</CardTitle></CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-3 gap-4">
                                    <div><label className="text-sm font-medium text-muted-foreground">Gestion</label><p className="mt-1">{expediente.anos_archivo_gestion || 0} anos</p></div>
                                    <div><label className="text-sm font-medium text-muted-foreground">Central</label><p className="mt-1">{expediente.anos_archivo_central || 0} anos</p></div>
                                    <div><label className="text-sm font-medium text-muted-foreground">Disposicion</label><p className="mt-1 capitalize">{expediente.disposicion_final || 'N/A'}</p></div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                    <div className="space-y-6">
                        <Card>
                            <CardHeader><CardTitle className="flex items-center gap-2"><Calendar className="h-5 w-5" />Fechas</CardTitle></CardHeader>
                            <CardContent className="space-y-3">
                                <div><label className="text-sm font-medium text-muted-foreground">Apertura</label><p className="mt-1">{formatDate(expediente.fecha_apertura)}</p></div>
                                {expediente.fecha_eliminacion && <div><label className="text-sm font-medium text-muted-foreground">Disposicion</label><p className="mt-1">{formatDate(expediente.fecha_eliminacion)}</p></div>}
                                <div><label className="text-sm font-medium text-muted-foreground">Creado</label><p className="mt-1">{formatDate(expediente.created_at)}</p></div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader><CardTitle className="flex items-center gap-2"><User className="h-5 w-5" />Responsable</CardTitle></CardHeader>
                            <CardContent>
                                {expediente.responsable ? <div><p className="font-medium">{expediente.responsable.name}</p><p className="text-sm text-muted-foreground">{expediente.responsable.email}</p></div> : <p className="text-muted-foreground">Sin asignar</p>}
                            </CardContent>
                        </Card>
                        {expediente.ubicacion_fisica && <Card><CardHeader><CardTitle className="flex items-center gap-2"><MapPin className="h-5 w-5" />Ubicacion</CardTitle></CardHeader><CardContent><p>{expediente.ubicacion_fisica}</p></CardContent></Card>}
                        {expediente.palabras_clave && expediente.palabras_clave.length > 0 && <Card><CardHeader><CardTitle className="flex items-center gap-2"><Tag className="h-5 w-5" />Palabras Clave</CardTitle></CardHeader><CardContent><div className="flex flex-wrap gap-2">{expediente.palabras_clave.map((k, i) => <Badge key={i} variant="secondary">{k}</Badge>)}</div></CardContent></Card>}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
