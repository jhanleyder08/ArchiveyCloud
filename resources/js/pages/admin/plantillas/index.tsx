import React, { useState, useEffect, useRef } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { 
  Plus, 
  Search, 
  Filter, 
  FileText, 
  Eye, 
  Edit, 
  Copy, 
  Trash2,
  Clock,
  User,
  FolderOpen,
  TrendingUp,
  FileType,
  CheckCircle,
  AlertCircle,
  Save
} from 'lucide-react';
import { toast } from 'sonner';

interface PlantillaDocumental {
  id: number;
  codigo: string;
  nombre: string;
  descripcion: string;
  categoria: string;
  estado: string;
  es_publica: boolean;
  version: number;
  usuario_creador: {
    id: number;
    name: string;
  };
  serie_documental: {
    id: number;
    codigo: string;
    nombre: string;
  } | null;
  created_at: string;
  updated_at: string;
}

interface Estadisticas {
  total: number;
  activas: number;
  borradores: number;
  publicas: number;
  por_categoria: Record<string, number>;
  mas_usadas: Array<{
    id: number;
    nombre: string;
    documentos_generados_count: number;
  }>;
}

interface SerieDocumental {
  id: number;
  codigo: string;
  nombre: string;
  subseries?: Array<{
    id: number;
    codigo: string;
    nombre: string;
  }>;
}

interface PageProps {
  plantillas: {
    data: PlantillaDocumental[];
    links: any[];
    meta: any;
  };
  estadisticas: Estadisticas;
  series: SerieDocumental[];
  filtros: {
    categoria?: string;
    estado?: string;
    es_publica?: boolean;
    serie_documental_id?: number;
    buscar?: string;
  };
  categorias: Record<string, string>;
  estados: Record<string, string>;
}

const getEstadoColor = (estado: string) => {
  const colors = {
    borrador: 'bg-gray-100 text-gray-800',
    revision: 'bg-yellow-100 text-yellow-800',
    activa: 'bg-green-100 text-green-800',
    archivada: 'bg-blue-100 text-blue-800',
    obsoleta: 'bg-red-100 text-red-800'
  };
  return colors[estado as keyof typeof colors] || 'bg-gray-100 text-gray-800';
};

const getCategoriaIcon = (categoria: string) => {
  const icons = {
    memorando: '📝',
    oficio: '📄',
    resolucion: '⚖️',
    acta: '📋',
    informe: '📊',
    circular: '🔄',
    comunicacion: '💬',
    otro: '📎'
  };
  return icons[categoria as keyof typeof icons] || '📎';
};

export default function PlantillasIndex({ 
  plantillas, 
  estadisticas, 
  series, 
  filtros, 
  categorias, 
  estados 
}: PageProps) {
  // Validar que estadisticas existe y tiene las propiedades requeridas
  const statsDefault = {
    total: 0,
    activas: 0,
    borradores: 0,
    publicas: 0,
    por_categoria: {},
    mas_usadas: []
  };
  
  const stats = estadisticas && typeof estadisticas === 'object' ? {
    ...statsDefault,
    ...estadisticas
  } : statsDefault;

  const [filtrosLocales, setFiltrosLocales] = useState(filtros);
  const [busqueda, setBusqueda] = useState(filtros.buscar || '');
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [selectedSerie, setSelectedSerie] = useState<SerieDocumental | null>(null);
  const isInitialMount = useRef(true);

  const { data: createForm, setData: setCreateForm, post, processing, errors, reset } = useForm({
    nombre: '',
    descripcion: '',
    categoria: 'otro',
    tipo_documento: '',
    serie_documental_id: '',
    subserie_documental_id: '',
    contenido_html: '',
    es_publica: false,
  });

  useEffect(() => {
    // Evitar ejecución en el montaje inicial
    if (isInitialMount.current) {
      isInitialMount.current = false;
      return;
    }

    // Solo ejecutar si hay un cambio real
    if (busqueda === (filtros.buscar || '')) {
      return;
    }

    const timer = setTimeout(() => {
      handleFiltroChange('buscar', busqueda);
    }, 500);

    return () => clearTimeout(timer);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [busqueda]);

  const handleFiltroChange = (campo: string, valor: any) => {
    const nuevosFiltros = { ...filtrosLocales } as any;
    nuevosFiltros[campo] = valor;
    
    // Limpiar filtro si está vacío
    if (!valor || valor === 'all') {
      delete nuevosFiltros[campo];
    }
    
    setFiltrosLocales(nuevosFiltros);
    
    router.get(route('admin.plantillas.index'), nuevosFiltros, {
      preserveState: true,
      preserveScroll: true
    });
  };

  const limpiarFiltros = () => {
    setFiltrosLocales({});
    setBusqueda('');
    router.get(route('admin.plantillas.index'));
  };

  const duplicarPlantilla = (plantillaId: number) => {
    router.post(route('admin.plantillas.duplicar', plantillaId), {}, {
      onSuccess: () => {
        // Notificación de éxito manejada por el backend
      }
    });
  };

  const handleSerieChange = (serieId: string) => {
    const serie = series.find(s => s.id.toString() === serieId);
    setSelectedSerie(serie || null);
    setCreateForm('serie_documental_id', serieId);
    setCreateForm('subserie_documental_id', '');
  };

  return (
    <AppLayout>
      <Head title="Plantillas Documentales" />
      
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <FileType className="h-6 w-6 text-[#2a3d83]" />
            <div>
              <h1 className="text-2xl font-semibold text-gray-900">
                Plantillas Documentales
              </h1>
              <p className="text-sm text-gray-600 mt-1">
                Gestión y creación de plantillas para documentos estandarizados
              </p>
            </div>
          </div>
          <Dialog open={showCreateModal} onOpenChange={setShowCreateModal}>
            <DialogTrigger asChild>
              <Button className="bg-[#2a3d83] hover:bg-[#1e2b5f] flex items-center gap-2">
                <Plus className="h-4 w-4" />
                Nueva Plantilla
              </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
              <DialogHeader>
                <DialogTitle className="text-xl font-semibold text-gray-900">
                  Crear Nueva Plantilla Documental
                </DialogTitle>
                <DialogDescription className="text-sm text-gray-600">
                  Complete los siguientes datos para crear una nueva plantilla.
                </DialogDescription>
              </DialogHeader>
              <form onSubmit={(e) => {
                e.preventDefault();
                
                if (!createForm.nombre || !createForm.categoria) {
                  toast.error('Por favor complete los campos requeridos');
                  return;
                }

                post(route('admin.plantillas.store'), {
                  onSuccess: () => {
                    setShowCreateModal(false);
                    reset();
                    setSelectedSerie(null);
                    toast.success('Plantilla creada exitosamente');
                  },
                  onError: (errors) => {
                    console.error('Error al crear plantilla:', errors);
                    if (errors) {
                      Object.keys(errors).forEach(field => {
                        const message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                        toast.error(`Error en ${field}: ${message}`);
                      });
                    }
                  }
                });
              }} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="create-nombre">Nombre de la Plantilla *</Label>
                  <Input
                    id="create-nombre"
                    value={createForm.nombre}
                    onChange={(e) => setCreateForm('nombre', e.target.value)}
                    placeholder="Ej: Memorando Interno"
                    required
                    className={errors.nombre ? 'border-red-500' : ''}
                  />
                  {errors.nombre && (
                    <p className="text-sm text-red-600">{errors.nombre}</p>
                  )}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="create-categoria">Categoría *</Label>
                    <Select 
                      value={createForm.categoria} 
                      onValueChange={(value) => setCreateForm('categoria', value)}
                    >
                      <SelectTrigger className={errors.categoria ? 'border-red-500' : ''}>
                        <SelectValue placeholder="Seleccionar categoría" />
                      </SelectTrigger>
                      <SelectContent>
                        {Object.entries(categorias).map(([key, label]) => (
                          <SelectItem key={key} value={key}>
                            {getCategoriaIcon(key)} {label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    {errors.categoria && (
                      <p className="text-sm text-red-600">{errors.categoria}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="create-tipo-documento">Tipo de Documento</Label>
                    <Input
                      id="create-tipo-documento"
                      value={createForm.tipo_documento}
                      onChange={(e) => setCreateForm('tipo_documento', e.target.value)}
                      placeholder="Ej: Memorando interno"
                      className={errors.tipo_documento ? 'border-red-500' : ''}
                    />
                    {errors.tipo_documento && (
                      <p className="text-sm text-red-600">{errors.tipo_documento}</p>
                    )}
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="create-descripcion">Descripción</Label>
                  <Textarea
                    id="create-descripcion"
                    value={createForm.descripcion}
                    onChange={(e) => setCreateForm('descripcion', e.target.value)}
                    placeholder="Descripción de la plantilla"
                    rows={3}
                    className={errors.descripcion ? 'border-red-500' : ''}
                  />
                  {errors.descripcion && (
                    <p className="text-sm text-red-600">{errors.descripcion}</p>
                  )}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Serie Documental</Label>
                    <Select onValueChange={handleSerieChange} value={createForm.serie_documental_id || undefined}>
                      <SelectTrigger>
                        <SelectValue placeholder="Seleccionar serie" />
                      </SelectTrigger>
                      <SelectContent>
                        {series.map((serie) => (
                          <SelectItem key={serie.id} value={serie.id.toString()}>
                            {serie.codigo} - {serie.nombre}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  {selectedSerie?.subseries && selectedSerie.subseries.length > 0 && (
                    <div className="space-y-2">
                      <Label>Subserie Documental</Label>
                      <Select 
                        value={createForm.subserie_documental_id || undefined} 
                        onValueChange={(value) => setCreateForm('subserie_documental_id', value)}
                      >
                        <SelectTrigger>
                          <SelectValue placeholder="Seleccionar subserie" />
                        </SelectTrigger>
                        <SelectContent>
                          {selectedSerie.subseries.map((subserie) => (
                            <SelectItem key={subserie.id} value={subserie.id.toString()}>
                              {subserie.codigo} - {subserie.nombre}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                  )}
                </div>

                <div className="flex items-center justify-between">
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => {
                      setCreateForm('serie_documental_id', '');
                      setCreateForm('subserie_documental_id', '');
                      setSelectedSerie(null);
                    }}
                    className="text-xs"
                  >
                    Limpiar selección
                  </Button>
                </div>

                <DialogFooter>
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => {
                      setShowCreateModal(false);
                      reset();
                      setSelectedSerie(null);
                    }}
                  >
                    Cancelar
                  </Button>
                  <Button
                    type="submit"
                    disabled={processing}
                    className="bg-[#2a3d83] hover:bg-[#1e2b5f] flex items-center gap-2"
                  >
                    <Save className="h-4 w-4" />
                    {processing ? 'Guardando...' : 'Crear Plantilla'}
                  </Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>
        </div>

        {/* Estadísticas */}
        <div className="grid gap-4 md:grid-cols-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Plantillas</CardTitle>
              <FileType className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.total}</div>
              <p className="text-xs text-muted-foreground">
                {stats.activas} activas
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Plantillas Públicas</CardTitle>
              <User className="h-4 w-4 text-green-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.publicas}</div>
              <p className="text-xs text-muted-foreground">
                Disponibles para todos
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">En Desarrollo</CardTitle>
              <AlertCircle className="h-4 w-4 text-yellow-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.borradores}</div>
              <p className="text-xs text-muted-foreground">
                Borradores pendientes
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Más Usada</CardTitle>
              <TrendingUp className="h-4 w-4 text-blue-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {stats.mas_usadas[0]?.documentos_generados_count || 0}
              </div>
              <p className="text-xs text-muted-foreground">
                {stats.mas_usadas[0]?.nombre || 'Sin uso'}
              </p>
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
                  placeholder="Buscar por nombre, código o descripción..."
                  value={busqueda}
                  onChange={(e) => setBusqueda(e.target.value)}
                  onKeyPress={(e) => {
                    if (e.key === 'Enter') {
                      e.preventDefault();
                      handleFiltroChange('buscar', busqueda);
                    }
                  }}
                />
              </div>
              <Select
                value={filtrosLocales.categoria || 'all'}
                onValueChange={(value) => handleFiltroChange('categoria', value)}
              >
                <SelectTrigger className="w-48">
                  <SelectValue placeholder="Categoría" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todas las categorías</SelectItem>
                  {Object.entries(categorias).map(([key, label]) => (
                    <SelectItem key={key} value={key}>
                      {getCategoriaIcon(key)} {label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <Select
                value={filtrosLocales.estado || 'all'}
                onValueChange={(value) => handleFiltroChange('estado', value)}
              >
                <SelectTrigger className="w-48">
                  <SelectValue placeholder="Estado" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos los estados</SelectItem>
                  {Object.entries(estados).map(([key, label]) => (
                    <SelectItem key={key} value={key}>
                      {label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {Object.keys(filtrosLocales).length > 0 && (
                <Button
                  variant="outline"
                  onClick={limpiarFiltros}
                >
                  Limpiar
                </Button>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Lista de Plantillas */}
        <Card>
          <CardHeader>
            <CardTitle>Lista de Plantillas</CardTitle>
          </CardHeader>
          <CardContent>
            {plantillas.data.length === 0 ? (
              <div className="text-center py-8">
                <FileText className="mx-auto h-12 w-12 text-muted-foreground" />
                <h3 className="mt-2 text-sm font-semibold text-gray-900">No hay plantillas</h3>
                <p className="mt-1 text-sm text-gray-500">
                  No se encontraron plantillas documentales.
                </p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="border-b">
                      <th className="text-left py-3 px-4">Plantilla</th>
                      <th className="text-left py-3 px-4">Categoría</th>
                      <th className="text-left py-3 px-4">Estado</th>
                      <th className="text-left py-3 px-4">Serie</th>
                      <th className="text-left py-3 px-4">Creador</th>
                      <th className="text-left py-3 px-4">Versión</th>
                      <th className="text-left py-3 px-4">Fecha</th>
                      <th className="text-right py-3 px-4">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    {plantillas.data.map((plantilla) => (
                      <tr key={plantilla.id} className="border-b hover:bg-gray-50">
                        <td className="py-3 px-4">
                          <div className="space-y-1">
                            <div className="flex items-center gap-2">
                              <span className="font-medium">{plantilla.nombre}</span>
                              {plantilla.es_publica && (
                                <Badge variant="secondary" className="text-xs">
                                  Pública
                                </Badge>
                              )}
                            </div>
                            <div className="text-sm text-gray-500">
                              {plantilla.codigo}
                            </div>
                            {plantilla.descripcion && (
                              <div className="text-sm text-gray-400 max-w-xs truncate">
                                {plantilla.descripcion}
                              </div>
                            )}
                          </div>
                        </td>
                        <td className="py-3 px-4">
                          <div className="flex items-center gap-2">
                            <span className="text-lg">
                              {getCategoriaIcon(plantilla.categoria)}
                            </span>
                            <span className="capitalize">
                              {categorias[plantilla.categoria]}
                            </span>
                          </div>
                        </td>
                        <td className="py-3 px-4">
                          <Badge className={getEstadoColor(plantilla.estado)}>
                            {estados[plantilla.estado]}
                          </Badge>
                        </td>
                        <td className="py-3 px-4">
                          {plantilla.serie_documental ? (
                            <div className="flex items-center gap-1">
                              <FolderOpen className="w-4 h-4 text-gray-400" />
                              <span className="text-sm">
                                {plantilla.serie_documental.codigo}
                              </span>
                            </div>
                          ) : (
                            <span className="text-gray-400 text-sm">Sin serie</span>
                          )}
                        </td>
                        <td className="py-3 px-4">
                          <div className="flex items-center gap-1">
                            <User className="w-4 h-4 text-gray-400" />
                            <span className="text-sm">{plantilla.usuario_creador.name}</span>
                          </div>
                        </td>
                        <td className="py-3 px-4">
                          <Badge variant="outline">
                            v{plantilla.version}
                          </Badge>
                        </td>
                        <td className="py-3 px-4 text-sm text-gray-500">
                          {new Date(plantilla.created_at).toLocaleDateString('es-ES')}
                        </td>
                        <td className="py-3 px-4 text-right">
                          <div className="flex justify-end gap-2">
                            <Link href={route('admin.plantillas.show', plantilla.id)}>
                              <Button variant="outline" size="sm">
                                <Eye className="h-4 w-4" />
                              </Button>
                            </Link>
                            <Link href={route('admin.plantillas.edit', plantilla.id)}>
                              <Button variant="outline" size="sm">
                                <Edit className="h-4 w-4" />
                              </Button>
                            </Link>
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => duplicarPlantilla(plantilla.id)}
                            >
                              <Copy className="h-4 w-4" />
                            </Button>
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
        {plantillas.links && plantillas.links.length > 3 && (
          <div className="flex justify-between items-center">
            <div className="text-sm text-gray-700">
              Mostrando {plantillas?.meta?.from || 0} a {plantillas?.meta?.to || 0} de {plantillas?.meta?.total || 0} resultados
            </div>
            <div className="flex gap-2">
              {plantillas.links.map((link, index) => (
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
    </AppLayout>
  );
}
