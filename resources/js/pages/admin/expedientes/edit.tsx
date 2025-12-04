import React, { useState, useEffect } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Save, ArrowLeft, X } from 'lucide-react';
import { toast } from 'sonner';

interface Serie { id: number; codigo: string; nombre: string; }
interface Subserie { id: number; serie_id: number; codigo: string; nombre: string; }
interface Usuario { id: number; name: string; email: string; }
interface Expediente {
    id: number;
    codigo: string;
    titulo: string;
    descripcion?: string;
    estado: string;
    tipo_expediente: string;
    nivel_acceso: string;
    serie_id: number;
    subserie_id?: number;
    responsable_id: number;
    ubicacion_fisica?: string;
    palabras_clave?: string[];
    notas?: string;
}
interface Props {
    expediente: Expediente;
    opciones: {
        series: Serie[];
        subseries: Subserie[];
        usuarios: Usuario[];
        tipos_expediente: { value: string; label: string }[];
        niveles_acceso: { value: string; label: string }[];
    };
}

const breadcrumbItems = [
    { title: 'Inicio', href: '/dashboard' },
    { title: 'Expedientes', href: '/admin/expedientes' },
    { title: 'Editar', href: '#' },
];

export default function Edit({ expediente, opciones }: Props) {
    const [palabrasClave, setPalabrasClave] = useState<string[]>(expediente.palabras_clave || []);
    const [nuevaPalabra, setNuevaPalabra] = useState('');
    const [subseriesFiltradas, setSubseriesFiltradas] = useState<Subserie[]>([]);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const { data, setData, errors } = useForm({
        titulo: expediente.titulo || '',
        descripcion: expediente.descripcion || '',
        serie_id: expediente.serie_id?.toString() || '',
        subserie_id: expediente.subserie_id?.toString() || '',
        tipo_expediente: expediente.tipo_expediente || 'administrativo',
        nivel_acceso: expediente.nivel_acceso || 'publico',
        responsable_id: expediente.responsable_id?.toString() || '',
        ubicacion_fisica: expediente.ubicacion_fisica || '',
        palabras_clave: expediente.palabras_clave || [],
        notas: expediente.notas || '',
    });

    useEffect(() => {
        if (data.serie_id && opciones?.subseries) {
            const filtered = opciones.subseries.filter(s => s.serie_id === parseInt(data.serie_id));
            setSubseriesFiltradas(filtered);
        } else {
            setSubseriesFiltradas([]);
        }
    }, [data.serie_id, opciones]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        const formData = {
            ...data,
            codigo: expediente.codigo,
            serie_id: parseInt(data.serie_id) || null,
            subserie_id: data.subserie_id ? parseInt(data.subserie_id) : null,
            responsable_id: parseInt(data.responsable_id) || null,
        };
        router.put('/admin/expedientes/' + expediente.id, formData, {
            onSuccess: () => toast.success('Expediente actualizado'),
            onError: () => toast.error('Error al actualizar'),
            onFinish: () => setIsSubmitting(false),
        });
    };

    const agregarPalabraClave = () => {
        if (nuevaPalabra.trim() && !palabrasClave.includes(nuevaPalabra.trim())) {
            const nuevas = [...palabrasClave, nuevaPalabra.trim()];
            setPalabrasClave(nuevas);
            setData('palabras_clave', nuevas);
            setNuevaPalabra('');
        }
    };

    const eliminarPalabraClave = (palabra: string) => {
        const nuevas = palabrasClave.filter(p => p !== palabra);
        setPalabrasClave(nuevas);
        setData('palabras_clave', nuevas);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title={'Editar ' + expediente.codigo} />
            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href="/admin/expedientes">
                        <Button variant="outline" size="icon"><ArrowLeft className="h-4 w-4" /></Button>
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold">Editar Expediente</h1>
                        <p className="text-muted-foreground font-mono">{expediente.codigo}</p>
                    </div>
                </div>
                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader><CardTitle>Informacion Basica</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label htmlFor="titulo">Titulo *</Label>
                                <Input id="titulo" value={data.titulo} onChange={(e) => setData('titulo', e.target.value)} className={errors.titulo ? 'border-red-500' : ''} />
                                {errors.titulo && <p className="text-sm text-red-600 mt-1">{errors.titulo}</p>}
                            </div>
                            <div>
                                <Label htmlFor="descripcion">Descripcion</Label>
                                <Textarea id="descripcion" value={data.descripcion} onChange={(e) => setData('descripcion', e.target.value)} rows={3} />
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>Clasificacion</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>Serie *</Label>
                                    <Select value={data.serie_id} onValueChange={(v) => { setData('serie_id', v); setData('subserie_id', ''); }}>
                                        <SelectTrigger className={errors.serie_id ? 'border-red-500' : ''}><SelectValue placeholder="Seleccionar" /></SelectTrigger>
                                        <SelectContent>{opciones?.series?.map((s) => <SelectItem key={s.id} value={s.id.toString()}>{s.codigo} - {s.nombre}</SelectItem>)}</SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label>Subserie</Label>
                                    <Select value={data.subserie_id} onValueChange={(v) => setData('subserie_id', v)} disabled={!data.serie_id}>
                                        <SelectTrigger><SelectValue placeholder="Seleccionar" /></SelectTrigger>
                                        <SelectContent>{subseriesFiltradas.map((s) => <SelectItem key={s.id} value={s.id.toString()}>{s.codigo} - {s.nombre}</SelectItem>)}</SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>Configuracion</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>Tipo *</Label>
                                    <Select value={data.tipo_expediente} onValueChange={(v) => setData('tipo_expediente', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>{opciones?.tipos_expediente?.map((t) => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}</SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label>Nivel Acceso *</Label>
                                    <Select value={data.nivel_acceso} onValueChange={(v) => setData('nivel_acceso', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>{opciones?.niveles_acceso?.map((n) => <SelectItem key={n.value} value={n.value}>{n.label}</SelectItem>)}</SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div>
                                <Label>Responsable *</Label>
                                <Select value={data.responsable_id} onValueChange={(v) => setData('responsable_id', v)}>
                                    <SelectTrigger className={errors.responsable_id ? 'border-red-500' : ''}><SelectValue placeholder="Seleccionar" /></SelectTrigger>
                                    <SelectContent>{opciones?.usuarios?.map((u) => <SelectItem key={u.id} value={u.id.toString()}>{u.name} ({u.email})</SelectItem>)}</SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Ubicacion Fisica</Label>
                                <Input value={data.ubicacion_fisica} onChange={(e) => setData('ubicacion_fisica', e.target.value)} />
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>Palabras Clave</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex gap-2">
                                <Input value={nuevaPalabra} onChange={(e) => setNuevaPalabra(e.target.value)} placeholder="Agregar palabra" onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), agregarPalabraClave())} />
                                <Button type="button" variant="outline" onClick={agregarPalabraClave}>Agregar</Button>
                            </div>
                            {palabrasClave.length > 0 && <div className="flex flex-wrap gap-2">{palabrasClave.map((p) => <span key={p} className="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">{p}<button type="button" onClick={() => eliminarPalabraClave(p)} className="hover:text-blue-600"><X className="h-3 w-3" /></button></span>)}</div>}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>Notas</CardTitle></CardHeader>
                        <CardContent>
                            <Textarea value={data.notas} onChange={(e) => setData('notas', e.target.value)} placeholder="Notas adicionales" rows={3} />
                        </CardContent>
                    </Card>
                    <div className="flex justify-end gap-3">
                        <Link href="/admin/expedientes"><Button type="button" variant="outline">Cancelar</Button></Link>
                        <Button type="submit" disabled={isSubmitting} className="bg-[#2a3d83] hover:bg-[#1e2b5f]"><Save className="h-4 w-4 mr-2" />{isSubmitting ? 'Guardando...' : 'Guardar'}</Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
