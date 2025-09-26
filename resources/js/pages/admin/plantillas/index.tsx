import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
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
  TrendingUp
} from 'lucide-react';

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

interface PageProps {
  plantillas: {
    data: PlantillaDocumental[];
    links: any[];
    meta: any;
  };
  estadisticas: Estadisticas;
  series: Array<{
    id: number;
    codigo: string;
    nombre: string;
  }>;
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

  useEffect(() => {
    const timer = setTimeout(() => {
      if (busqueda !== filtros.buscar) {
        handleFiltroChange('buscar', busqueda);
      }
    }, 500);

    return () => clearTimeout(timer);
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

  return (
    <AppLayout>
      <Head title="Plantillas Documentales" />

      <div className="py-6">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          {/* Header */}
          <div className="flex justify-between items-center mb-6">
            <div>
              <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                Plantillas Documentales
              </h2>
              <p className="text-sm text-gray-600 mt-1">
                Gestión y creación de plantillas para documentos estandarizados
              </p>
            </div>
            <Link
              href={route('admin.plantillas.create')}
              className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 transition ease-in-out duration-150"
            >
              <Plus className="w-4 h-4 mr-2" />
              Nueva Plantilla
            </Link>
          </div>

          {/* Estadísticas */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total Plantillas</CardTitle>
                <FileText className="h-4 w-4 text-muted-foreground" />
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
                <User className="h-4 w-4 text-muted-foreground" />
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
                <Clock className="h-4 w-4 text-muted-foreground" />
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
                <TrendingUp className="h-4 w-4 text-muted-foreground" />
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
          <Card className="mb-6">
            <CardHeader>
              <CardTitle className="text-lg flex items-center">
                <Filter className="w-5 h-5 mr-2" />
                Filtros de búsqueda
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="space-y-2">
                  <label className="text-sm font-medium">Búsqueda</label>
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                    <Input
                      placeholder="Buscar plantillas..."
                      value={busqueda}
                      onChange={(e) => setBusqueda(e.target.value)}
                      className="pl-10"
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium">Categoría</label>
                  <Select
                    value={filtrosLocales.categoria || 'all'}
                    onValueChange={(value) => handleFiltroChange('categoria', value)}
                  >
                    <SelectTrigger>
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
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium">Estado</label>
                  <Select
                    value={filtrosLocales.estado || 'all'}
                    onValueChange={(value) => handleFiltroChange('estado', value)}
                  >
                    <SelectTrigger>
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
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium">Serie Documental</label>
                  <Select
                    value={filtrosLocales.serie_documental_id?.toString() || 'all'}
                    onValueChange={(value) => handleFiltroChange('serie_documental_id', value)}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Todas las series" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">Todas las series</SelectItem>
                      {series.map((serie) => (
                        <SelectItem key={serie.id} value={serie.id.toString()}>
                          {serie.codigo} - {serie.nombre}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>

              {Object.keys(filtrosLocales).length > 0 && (
                <div className="mt-4">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={limpiarFiltros}
                    className="text-gray-600"
                  >
                    Limpiar filtros
                  </Button>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Tabla de Plantillas */}
          <Card>
            <CardHeader>
              <CardTitle>Plantillas Documentales</CardTitle>
              <CardDescription>
                {plantillas?.meta?.total || 0} plantilla{(plantillas?.meta?.total || 0) !== 1 ? 's' : ''} encontrada{(plantillas?.meta?.total || 0) !== 1 ? 's' : ''}
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Plantilla</TableHead>
                    <TableHead>Categoría</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead>Serie Documental</TableHead>
                    <TableHead>Creador</TableHead>
                    <TableHead>Versión</TableHead>
                    <TableHead>Fecha</TableHead>
                    <TableHead className="text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {plantillas.data.map((plantilla) => (
                    <TableRow key={plantilla.id}>
                      <TableCell>
                        <div className="space-y-1">
                          <div className="flex items-center space-x-2">
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
                      </TableCell>

                      <TableCell>
                        <div className="flex items-center space-x-2">
                          <span className="text-lg">
                            {getCategoriaIcon(plantilla.categoria)}
                          </span>
                          <span className="capitalize">
                            {categorias[plantilla.categoria]}
                          </span>
                        </div>
                      </TableCell>

                      <TableCell>
                        <Badge className={getEstadoColor(plantilla.estado)}>
                          {estados[plantilla.estado]}
                        </Badge>
                      </TableCell>

                      <TableCell>
                        {plantilla.serie_documental ? (
                          <div className="flex items-center space-x-1">
                            <FolderOpen className="w-4 h-4 text-gray-400" />
                            <span className="text-sm">
                              {plantilla.serie_documental.codigo}
                            </span>
                          </div>
                        ) : (
                          <span className="text-gray-400 text-sm">Sin serie</span>
                        )}
                      </TableCell>

                      <TableCell>
                        <div className="flex items-center space-x-1">
                          <User className="w-4 h-4 text-gray-400" />
                          <span className="text-sm">{plantilla.usuario_creador.name}</span>
                        </div>
                      </TableCell>

                      <TableCell>
                        <Badge variant="outline">
                          v{plantilla.version}
                        </Badge>
                      </TableCell>

                      <TableCell>
                        <div className="text-sm text-gray-500">
                          {new Date(plantilla.created_at).toLocaleDateString('es-ES')}
                        </div>
                      </TableCell>

                      <TableCell className="text-right">
                        <div className="flex justify-end space-x-2">
                          <Button
                            variant="ghost"
                            size="sm"
                            asChild
                          >
                            <Link href={route('admin.plantillas.show', plantilla.id)}>
                              <Eye className="w-4 h-4" />
                            </Link>
                          </Button>
                          
                          <Button
                            variant="ghost"
                            size="sm"
                            asChild
                          >
                            <Link href={route('admin.plantillas.edit', plantilla.id)}>
                              <Edit className="w-4 h-4" />
                            </Link>
                          </Button>
                          
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => duplicarPlantilla(plantilla.id)}
                          >
                            <Copy className="w-4 h-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>

              {plantillas.data.length === 0 && (
                <div className="text-center py-8">
                  <FileText className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                  <h3 className="text-lg font-medium text-gray-900 mb-2">
                    No se encontraron plantillas
                  </h3>
                  <p className="text-gray-600 mb-4">
                    Comienza creando tu primera plantilla documental
                  </p>
                  <Link
                    href={route('admin.plantillas.create')}
                    className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                  >
                    <Plus className="w-4 h-4 mr-2" />
                    Crear Primera Plantilla
                  </Link>
                </div>
              )}

              {/* Paginación */}
              {plantillas.links && plantillas.links.length > 3 && (
                <div className="mt-6 flex justify-between items-center">
                  <div className="text-sm text-gray-700">
                    Mostrando {plantillas?.meta?.from || 0} a {plantillas?.meta?.to || 0} de {plantillas?.meta?.total || 0} resultados
                  </div>
                  <div className="flex space-x-1">
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
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
