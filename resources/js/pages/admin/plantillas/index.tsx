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
    
    router.get('/admin/plantillas', nuevosFiltros, {
      preserveState: true,
      preserveScroll: true
    });
  };

  const limpiarFiltros = () => {
    setFiltrosLocales({});
    setBusqueda('');
    router.get('/admin/plantillas');
  };

  const duplicarPlantilla = (plantillaId: number) => {
    router.post(`/admin/plantillas/${plantillaId}/duplicar`, {}, {
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
    <AppLayout breadcrumbs={[
      { title: "Dashboard", href: "/dashboard" },
      { title: "Administración", href: "#" },
      { title: "Plantillas Documentales", href: "/admin/plantillas" }
    ]}>
      <Head title="Plantillas Documentales" />
      
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between pt-4">
          <div className="flex items-center gap-2">
            <FileType className="h-6 w-6 text-[#2a3d83]" />
            <h1 className="text-2xl font-semibold text-gray-900">
              Gestión de Plantillas Documentales
            </h1>
          </div>
          <Dialog open={showCreateModal} onOpenChange={setShowCreateModal}>
            <DialogTrigger asChild>
              <Button className="flex items-center gap-2 px-4 py-2 bg-[#2a3d83] text-white rounded-lg hover:bg-[#1e2b5f] transition-colors">
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

                // Usar ruta relativa para evitar problemas de concatenación de URLs
                // Inertia maneja la URL base automáticamente
                post('/admin/plantillas', {
                  onStart: () => {
                    // Iniciando envío de formulario
                  },
                  onProgress: () => {
                    // Enviando datos...
                  },
                  onSuccess: () => {
                    setShowCreateModal(false);
                    reset();
                    setSelectedSerie(null);
                    toast.success('Plantilla creada exitosamente');
                  },
                  onError: (errors) => {
                    if (errors) {
                      if (typeof errors === 'object') {
                        Object.keys(errors).forEach(field => {
                          const message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                          toast.error(`Error en ${field}: ${message}`);
                        });
                      } else {
                        toast.error(`Error: ${errors}`);
                      }
                    } else {
                      toast.error('Error al crear la plantilla. Por favor, verifique su conexión e intente nuevamente.');
                    }
                  },
                  onFinish: () => {
                    // Finalizado envío de formulario
                  },
                  preserveState: false,
                  preserveScroll: false,
                  only: [], // Enviar todos los datos
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
                    <Select 
                      onValueChange={handleSerieChange} 
                      value={createForm.serie_documental_id || ''}
                    >
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
                        value={createForm.subserie_documental_id || ''} 
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

                {(createForm.serie_documental_id || createForm.subserie_documental_id) && (
                  <div className="flex items-center justify-end">
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      onClick={() => {
                        setCreateForm('serie_documental_id', null);
                        setCreateForm('subserie_documental_id', null);
                        setSelectedSerie(null);
                      }}
                      className="text-xs"
                    >
                      Limpiar selección
                    </Button>
                  </div>
                )}

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
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div className="bg-white rounded-lg border p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Plantillas</p>
                <p className="text-2xl font-semibold text-gray-900">{stats.total}</p>
              </div>
              <div className="p-3 bg-blue-100 rounded-full">
                <FileType className="h-6 w-6 text-[#2a3d83]" />
              </div>
            </div>
          </div>
          <div className="bg-white rounded-lg border p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Activas</p>
                <p className="text-2xl font-semibold text-[#2a3d83]">{stats.activas}</p>
              </div>
              <div className="p-3 bg-blue-100 rounded-full">
                <CheckCircle className="h-6 w-6 text-[#2a3d83]" />
              </div>
            </div>
          </div>
          <div className="bg-white rounded-lg border p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Borradores</p>
                <p className="text-2xl font-semibold text-[#2a3d83]">{stats.borradores}</p>
              </div>
              <div className="p-3 bg-blue-100 rounded-full">
                <AlertCircle className="h-6 w-6 text-[#2a3d83]" />
              </div>
            </div>
          </div>
          <div className="bg-white rounded-lg border p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Públicas</p>
                <p className="text-2xl font-semibold text-[#2a3d83]">{stats.publicas}</p>
              </div>
              <div className="p-3 bg-blue-100 rounded-full">
                <User className="h-6 w-6 text-[#2a3d83]" />
              </div>
            </div>
          </div>
        </div>

        {/* Filtros */}
        <div className="bg-white rounded-lg border p-6">
          <div className="flex flex-col sm:flex-row gap-4 items-center justify-between">
            <div className="flex items-center gap-4 w-full sm:w-auto">
              <div className="relative flex-1 sm:w-80">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input
                  placeholder="Buscar plantillas..."
                  value={busqueda}
                  onChange={(e) => setBusqueda(e.target.value)}
                  onKeyPress={(e) => {
                    if (e.key === 'Enter') {
                      e.preventDefault();
                      handleFiltroChange('buscar', busqueda);
                    }
                  }}
                  className="pl-10"
                />
              </div>
            </div>
            <div className="flex items-center gap-2">
              <Select
                value={filtrosLocales.categoria || 'all'}
                onValueChange={(value) => handleFiltroChange('categoria', value)}
              >
                <SelectTrigger className="w-[180px]">
                  <SelectValue placeholder="Todas las categorías" />
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
                <SelectTrigger className="w-[180px]">
                  <SelectValue placeholder="Todos los estados" />
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
          </div>
        </div>

        {/* Lista de Plantillas */}
        <div className="bg-white rounded-lg border overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b">
                <tr>
                  <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Plantilla</th>
                  <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Categoría</th>
                  <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Estado</th>
                  <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Serie</th>
                  <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Creador</th>
                  <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Versión</th>
                  <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Fecha</th>
                  <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {plantillas.data.length === 0 ? (
                  <tr>
                    <td colSpan={8} className="py-8 px-6 text-center text-gray-500">
                      No se encontraron plantillas.
                    </td>
                  </tr>
                ) : (
                  plantillas.data.map((plantilla) => (
                    <tr key={plantilla.id} className="hover:bg-gray-50 transition-colors">
                      <td className="py-4 px-6">
                        <div className="space-y-1">
                          <div className="flex items-center gap-2">
                            <span className="font-medium text-gray-900">{plantilla.nombre}</span>
                            {plantilla.es_publica && (
                              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-[#2a3d83]">
                                Pública
                              </span>
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
                      <td className="py-4 px-6">
                        <div className="flex items-center gap-2">
                          <span className="text-lg">
                            {getCategoriaIcon(plantilla.categoria)}
                          </span>
                          <span className="capitalize text-gray-900">
                            {categorias[plantilla.categoria]}
                          </span>
                        </div>
                      </td>
                      <td className="py-4 px-6">
                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getEstadoColor(plantilla.estado)}`}>
                          {estados[plantilla.estado]}
                        </span>
                      </td>
                      <td className="py-4 px-6">
                        {plantilla.serie_documental ? (
                          <div className="flex items-center gap-1">
                            <FolderOpen className="w-4 h-4 text-gray-400" />
                            <span className="text-sm text-gray-900">
                              {plantilla.serie_documental.codigo}
                            </span>
                          </div>
                        ) : (
                          <span className="text-gray-400 text-sm">Sin serie</span>
                        )}
                      </td>
                      <td className="py-4 px-6">
                        <div className="flex items-center gap-1">
                          <User className="w-4 h-4 text-gray-400" />
                          <span className="text-sm text-gray-900">{plantilla.usuario_creador.name}</span>
                        </div>
                      </td>
                      <td className="py-4 px-6">
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-[#2a3d83]">
                          v{plantilla.version}
                        </span>
                      </td>
                      <td className="py-4 px-6 text-sm text-gray-600">
                        {new Date(plantilla.created_at).toLocaleDateString('es-ES')}
                      </td>
                      <td className="py-4 px-6">
                        <div className="flex items-center gap-2">
                          <Link href={`/admin/plantillas/${plantilla.id}`}>
                            <button className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors">
                              <Eye className="h-4 w-4" />
                            </button>
                          </Link>
                          <Link href={`/admin/plantillas/${plantilla.id}/edit`}>
                            <button className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors">
                              <Edit className="h-4 w-4" />
                            </button>
                          </Link>
                          <button
                            className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors"
                            onClick={() => duplicarPlantilla(plantilla.id)}
                          >
                            <Copy className="h-4 w-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Paginación */}
        {plantillas.data.length > 0 && plantillas.links && plantillas.links.length > 3 && (
          <div className="flex items-center justify-between bg-white border rounded-lg px-6 py-3">
            <div className="text-sm text-gray-600">
              Mostrando <span className="font-medium">{plantillas?.meta?.from || 0}</span> a{' '}
              <span className="font-medium">{plantillas?.meta?.to || 0}</span> de{' '}
              <span className="font-medium">{plantillas?.meta?.total || 0}</span> resultados
            </div>
            <div className="flex items-center gap-2">
              {plantillas.links.map((link, index) => {
                if (link.label.includes('Previous')) {
                  return (
                    <Link
                      key={index}
                      href={link.url || '#'}
                      preserveState
                      className={`px-3 py-2 border border-gray-300 rounded-md text-sm font-medium ${
                        link.url 
                          ? 'text-gray-700 hover:bg-gray-50' 
                          : 'text-gray-300 cursor-not-allowed'
                      }`}
                    >
                      Anterior
                    </Link>
                  );
                }
                
                if (link.label.includes('Next')) {
                  return (
                    <Link
                      key={index}
                      href={link.url || '#'}
                      preserveState
                      className={`px-3 py-2 border border-gray-300 rounded-md text-sm font-medium ${
                        link.url 
                          ? 'text-gray-700 hover:bg-gray-50' 
                          : 'text-gray-300 cursor-not-allowed'
                      }`}
                    >
                      Siguiente
                    </Link>
                  );
                }

                // Number pages
                if (!isNaN(Number(link.label))) {
                  return (
                    <Link
                      key={index}
                      href={link.url || '#'}
                      preserveState
                      className={`px-3 py-2 rounded-md text-sm font-medium ${
                        link.active
                          ? 'bg-[#2a3d83] text-white'
                          : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                      }`}
                    >
                      {link.label}
                    </Link>
                  );
                }

                return null;
              })}
            </div>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
