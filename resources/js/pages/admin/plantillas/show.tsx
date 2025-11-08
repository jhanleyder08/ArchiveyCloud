import React, { useState, useEffect } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { 
  ArrowLeft, 
  Edit, 
  Copy, 
  Play,
  FileText,
  Clock,
  User,
  FolderOpen,
  TrendingUp,
  Download,
  Eye,
  Settings
} from 'lucide-react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
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
  contenido_html: string;
  campos_variables: Array<{
    nombre: string;
    tipo: string;
    etiqueta: string;
    requerido: boolean;
    valor_defecto: string;
  }>;
  tags: string[];
  observaciones: string;
  usuario_creador: {
    id: number;
    name: string;
    email: string;
  };
  serie_documental: {
    id: number;
    codigo: string;
    nombre: string;
  } | null;
  subserie_documental: {
    id: number;
    codigo: string;
    nombre: string;
  } | null;
  plantilla_padre: {
    id: number;
    nombre: string;
    version: number;
  } | null;
  versiones: Array<{
    id: number;
    version: number;
    estado: string;
    created_at: string;
  }>;
  created_at: string;
  updated_at: string;
}

interface DocumentoGenerado {
  id: number;
  nombre: string;
  expediente: {
    id: number;
    numero_expediente: string;
    titulo: string;
  } | null;
  usuario_creador: {
    id: number;
    name: string;
  };
  created_at: string;
}

interface EstadisticasUso {
  documentos_generados: number;
  documentos_ultimo_mes: number;
  usuarios_utilizan: number;
  version_actual: number;
  es_version_reciente: boolean;
}

interface PageProps {
  plantilla: PlantillaDocumental;
  documentos_generados: DocumentoGenerado[];
  estadisticas_uso: EstadisticasUso;
  puede_editar: boolean;
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

export default function PlantillasShow({ 
  plantilla, 
  documentos_generados, 
  estadisticas_uso, 
  puede_editar 
}: PageProps) {
  const { flash } = usePage<{flash?: {success?: string, error?: string}}>().props;
  const [showPreview, setShowPreview] = useState(false);
  const [showCambiarEstado, setShowCambiarEstado] = useState(false);
  const [showGenerarDocumento, setShowGenerarDocumento] = useState(false);

  useEffect(() => {
    if (flash?.success) {
      toast.success(flash.success);
    }
    if (flash?.error) {
      toast.error(flash.error);
    }
  }, [flash]);

  const estadoForm = useForm({
    estado: plantilla.estado,
    observaciones: plantilla.observaciones || ''
  });

  const documentoForm = useForm({
    nombre_documento: '',
    expediente_id: '',
    variables: {} as Record<string, any>
  });

  const duplicarPlantilla = () => {
    router.post(route('admin.plantillas.duplicar', plantilla.id), {}, {
      onSuccess: () => {
        toast.success('Plantilla duplicada exitosamente');
      },
      onError: (errors) => {
        toast.error('Error al duplicar plantilla');
      }
    });
  };

  const crearNuevaVersion = () => {
    router.post(route('admin.plantillas.crear-version', plantilla.id), {}, {
      onSuccess: () => {
        toast.success('Nueva versión creada exitosamente');
      },
      onError: (errors) => {
        toast.error('Error al crear nueva versión');
      }
    });
  };

  const exportarPlantilla = (formato: string = 'json') => {
    window.location.href = route('admin.plantillas.exportar', [plantilla.id, formato]);
  };

  const generarDocumento = () => {
    setShowGenerarDocumento(true);
  };

  const handleGenerarDocumento = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Preparar variables desde los campos
    const variables: Record<string, any> = {};
    if (plantilla.campos_variables) {
      plantilla.campos_variables.forEach(campo => {
        const input = document.getElementById(`variable-${campo.nombre}`) as HTMLInputElement | HTMLTextAreaElement;
        if (input) {
          variables[campo.nombre] = input.value || campo.valor_defecto || '';
        }
      });
    }

    documentoForm.post(route('admin.plantillas.generar-documento', plantilla.id), {
      data: {
        nombre_documento: documentoForm.data.nombre_documento,
        expediente_id: documentoForm.data.expediente_id || null,
        variables: variables
      },
      onSuccess: () => {
        setShowGenerarDocumento(false);
        documentoForm.reset();
        toast.success('Documento generado exitosamente');
      },
      onError: (errors) => {
        console.error('Errores al generar documento:', errors);
        if (errors) {
          Object.keys(errors).forEach(field => {
            const message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
            toast.error(`Error en ${field}: ${message}`);
          });
        } else {
          toast.error('Error al generar documento');
        }
      }
    });
  };

  const handleCambiarEstado = (e: React.FormEvent) => {
    e.preventDefault();
    estadoForm.patch(route('admin.plantillas.cambiar-estado', plantilla.id), {
      onSuccess: () => {
        setShowCambiarEstado(false);
        toast.success('Estado actualizado exitosamente');
      },
      onError: (errors) => {
        toast.error('Error al cambiar estado');
      }
    });
  };

  return (
    <AppLayout
      header={
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-4">
            <Link
              href={route('admin.plantillas.index')}
              className="text-gray-500 hover:text-gray-700"
            >
              <ArrowLeft className="w-6 h-6" />
            </Link>
            <div>
              <div className="flex items-center space-x-2">
                <h2 className="text-xl font-semibold text-gray-800">
                  {plantilla.nombre}
                </h2>
                <Badge className={getEstadoColor(plantilla.estado)}>
                  {plantilla.estado.toUpperCase()}
                </Badge>
                {plantilla.es_publica && (
                  <Badge variant="secondary">Pública</Badge>
                )}
              </div>
              <div className="flex items-center space-x-4 text-sm text-gray-600 mt-1">
                <span>{plantilla.codigo}</span>
                <span>v{plantilla.version}</span>
                <span>
                  {getCategoriaIcon(plantilla.categoria)} {plantilla.categoria}
                </span>
              </div>
            </div>
          </div>
          <div className="flex space-x-2">
            <Button variant="outline" onClick={() => setShowPreview(true)}>
              <Eye className="w-4 h-4 mr-2" />
              Vista Previa
            </Button>
            <Button variant="outline" onClick={duplicarPlantilla}>
              <Copy className="w-4 h-4 mr-2" />
              Duplicar
            </Button>
            <Button onClick={generarDocumento}>
              <Play className="w-4 h-4 mr-2" />
              Generar Documento
            </Button>
            <Dialog open={showGenerarDocumento} onOpenChange={setShowGenerarDocumento}>
              <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                  <DialogTitle>Generar Documento desde Plantilla</DialogTitle>
                  <DialogDescription>
                    Completa los datos para generar un nuevo documento
                  </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleGenerarDocumento} className="space-y-4 mt-4">
                  <div className="space-y-2">
                    <Label htmlFor="nombre_documento">Nombre del Documento *</Label>
                    <Input
                      id="nombre_documento"
                      value={documentoForm.data.nombre_documento}
                      onChange={(e) => documentoForm.setData('nombre_documento', e.target.value)}
                      placeholder="Ej: Memorando Interno 001"
                      required
                    />
                    {documentoForm.errors.nombre_documento && (
                      <p className="text-sm text-red-600">{documentoForm.errors.nombre_documento}</p>
                    )}
                  </div>

                  {plantilla.campos_variables && plantilla.campos_variables.length > 0 && (
                    <div className="space-y-4">
                      <Label>Variables de la Plantilla</Label>
                      {plantilla.campos_variables.map((campo, index) => (
                        <div key={index} className="space-y-2">
                          <Label htmlFor={`variable-${campo.nombre}`}>
                            {campo.etiqueta}
                            {campo.requerido && <span className="text-red-500"> *</span>}
                          </Label>
                          {campo.tipo === 'texto' || campo.tipo === 'text' ? (
                            <Input
                              id={`variable-${campo.nombre}`}
                              defaultValue={campo.valor_defecto || ''}
                              placeholder={campo.etiqueta}
                              required={campo.requerido}
                            />
                          ) : campo.tipo === 'textarea' || campo.tipo === 'texto_largo' ? (
                            <Textarea
                              id={`variable-${campo.nombre}`}
                              defaultValue={campo.valor_defecto || ''}
                              placeholder={campo.etiqueta}
                              rows={4}
                              required={campo.requerido}
                            />
                          ) : (
                            <Input
                              id={`variable-${campo.nombre}`}
                              type={campo.tipo === 'numero' ? 'number' : 'text'}
                              defaultValue={campo.valor_defecto || ''}
                              placeholder={campo.etiqueta}
                              required={campo.requerido}
                            />
                          )}
                        </div>
                      ))}
                    </div>
                  )}

                  <div className="flex justify-end space-x-2">
                    <Button
                      type="button"
                      variant="outline"
                      onClick={() => setShowGenerarDocumento(false)}
                    >
                      Cancelar
                    </Button>
                    <Button type="submit" disabled={documentoForm.processing}>
                      {documentoForm.processing ? 'Generando...' : 'Generar Documento'}
                    </Button>
                  </div>
                </form>
              </DialogContent>
            </Dialog>
            {puede_editar && (
              <Button asChild>
                <Link href={route('admin.plantillas.edit', plantilla.id)}>
                  <Edit className="w-4 h-4 mr-2" />
                  Editar
                </Link>
              </Button>
            )}
          </div>
        </div>
      }
    >
      <Head title={`Plantilla: ${plantilla.nombre}`} />

      <div className="py-6">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Columna principal */}
            <div className="lg:col-span-2 space-y-6">
              <Tabs defaultValue="informacion" className="w-full">
                <TabsList className="grid w-full grid-cols-4">
                  <TabsTrigger value="informacion">Información</TabsTrigger>
                  <TabsTrigger value="contenido">Contenido</TabsTrigger>
                  <TabsTrigger value="variables">Variables</TabsTrigger>
                  <TabsTrigger value="documentos">Documentos</TabsTrigger>
                </TabsList>

                <TabsContent value="informacion" className="space-y-4">
                  <Card>
                    <CardHeader>
                      <CardTitle>Detalles de la Plantilla</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      {plantilla.descripcion && (
                        <div>
                          <label className="text-sm font-medium text-gray-500">Descripción</label>
                          <p className="text-gray-900">{plantilla.descripcion}</p>
                        </div>
                      )}

                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <label className="text-sm font-medium text-gray-500">Categoría</label>
                          <p className="text-gray-900 flex items-center space-x-1">
                            <span>{getCategoriaIcon(plantilla.categoria)}</span>
                            <span className="capitalize">{plantilla.categoria}</span>
                          </p>
                        </div>

                        <div>
                          <label className="text-sm font-medium text-gray-500">Estado</label>
                          <div className="mt-1">
                            <Badge className={getEstadoColor(plantilla.estado)}>
                              {plantilla.estado.toUpperCase()}
                            </Badge>
                          </div>
                        </div>

                        <div>
                          <label className="text-sm font-medium text-gray-500">Visibilidad</label>
                          <p className="text-gray-900">
                            {plantilla.es_publica ? 'Pública' : 'Privada'}
                          </p>
                        </div>

                        <div>
                          <label className="text-sm font-medium text-gray-500">Versión</label>
                          <p className="text-gray-900">v{plantilla.version}</p>
                        </div>
                      </div>

                      {plantilla.serie_documental && (
                        <div>
                          <label className="text-sm font-medium text-gray-500">Serie Documental</label>
                          <p className="text-gray-900">
                            {plantilla.serie_documental.codigo} - {plantilla.serie_documental.nombre}
                          </p>
                          {plantilla.subserie_documental && (
                            <p className="text-sm text-gray-600 mt-1">
                              Subserie: {plantilla.subserie_documental.codigo} - {plantilla.subserie_documental.nombre}
                            </p>
                          )}
                        </div>
                      )}

                      {plantilla.tags && plantilla.tags.length > 0 && (
                        <div>
                          <label className="text-sm font-medium text-gray-500">Tags</label>
                          <div className="flex flex-wrap gap-2 mt-1">
                            {plantilla.tags.map((tag, index) => (
                              <Badge key={index} variant="outline">{tag}</Badge>
                            ))}
                          </div>
                        </div>
                      )}

                      {plantilla.observaciones && (
                        <div>
                          <label className="text-sm font-medium text-gray-500">Observaciones</label>
                          <p className="text-gray-900">{plantilla.observaciones}</p>
                        </div>
                      )}
                    </CardContent>
                  </Card>

                  {plantilla.versiones && plantilla.versiones.length > 1 && (
                    <Card>
                      <CardHeader>
                        <CardTitle>Historial de Versiones</CardTitle>
                      </CardHeader>
                      <CardContent>
                        <div className="space-y-2">
                          {plantilla.versiones.map((version) => (
                            <div key={version.id} className="flex items-center justify-between p-2 border rounded">
                              <div className="flex items-center space-x-2">
                                <Badge variant="outline">v{version.version}</Badge>
                                <Badge className={getEstadoColor(version.estado)}>
                                  {version.estado}
                                </Badge>
                              </div>
                              <span className="text-sm text-gray-500">
                                {new Date(version.created_at).toLocaleDateString('es-ES')}
                              </span>
                            </div>
                          ))}
                        </div>
                      </CardContent>
                    </Card>
                  )}
                </TabsContent>

                <TabsContent value="contenido">
                  <Card>
                    <CardHeader>
                      <CardTitle>Contenido HTML</CardTitle>
                      <CardDescription>
                        Contenido base de la plantilla
                      </CardDescription>
                    </CardHeader>
                    <CardContent>
                      {plantilla.contenido_html ? (
                        <div className="bg-gray-50 p-4 rounded-lg">
                          <pre className="whitespace-pre-wrap text-sm">
                            {plantilla.contenido_html}
                          </pre>
                        </div>
                      ) : (
                        <p className="text-gray-500 italic">Sin contenido definido</p>
                      )}
                    </CardContent>
                  </Card>
                </TabsContent>

                <TabsContent value="variables">
                  <Card>
                    <CardHeader>
                      <CardTitle>Variables Configuradas</CardTitle>
                      <CardDescription>
                        Campos que se pueden personalizar al generar documentos
                      </CardDescription>
                    </CardHeader>
                    <CardContent>
                      {plantilla.campos_variables && plantilla.campos_variables.length > 0 ? (
                        <div className="space-y-3">
                          {plantilla.campos_variables.map((campo, index) => (
                            <div key={index} className="border rounded-lg p-3">
                              <div className="flex items-center justify-between mb-2">
                                <h4 className="font-medium">{campo.etiqueta}</h4>
                                <div className="flex space-x-2">
                                  <Badge variant="outline">{campo.tipo}</Badge>
                                  {campo.requerido && (
                                    <Badge variant="secondary">Requerido</Badge>
                                  )}
                                </div>
                              </div>
                              <div className="text-sm text-gray-600">
                                <p><strong>Variable:</strong> {campo.nombre}</p>
                                {campo.valor_defecto && (
                                  <p><strong>Valor por defecto:</strong> {campo.valor_defecto}</p>
                                )}
                              </div>
                            </div>
                          ))}
                        </div>
                      ) : (
                        <p className="text-gray-500 italic">No hay variables configuradas</p>
                      )}
                    </CardContent>
                  </Card>
                </TabsContent>

                <TabsContent value="documentos">
                  <Card>
                    <CardHeader>
                      <CardTitle>Documentos Generados</CardTitle>
                      <CardDescription>
                        Documentos creados usando esta plantilla
                      </CardDescription>
                    </CardHeader>
                    <CardContent>
                      {documentos_generados.length > 0 ? (
                        <div className="space-y-3">
                          {documentos_generados.map((documento) => (
                            <div key={documento.id} className="flex items-center justify-between p-3 border rounded">
                              <div>
                                <h4 className="font-medium">{documento.nombre}</h4>
                                <div className="text-sm text-gray-500">
                                  <p>Creado por: {documento.usuario_creador.name}</p>
                                  <p>Fecha: {new Date(documento.created_at).toLocaleDateString('es-ES')}</p>
                                  {documento.expediente && (
                                    <p>Expediente: {documento.expediente.numero_expediente}</p>
                                  )}
                                </div>
                              </div>
                              <Button variant="outline" size="sm">
                                <Eye className="w-4 h-4" />
                              </Button>
                            </div>
                          ))}
                        </div>
                      ) : (
                        <p className="text-gray-500 italic">
                          No se han generado documentos con esta plantilla
                        </p>
                      )}
                    </CardContent>
                  </Card>
                </TabsContent>
              </Tabs>
            </div>

            {/* Sidebar */}
            <div className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle className="text-sm">Estadísticas de Uso</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-gray-500">Documentos generados</span>
                    <span className="font-semibold">{estadisticas_uso.documentos_generados}</span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-gray-500">Último mes</span>
                    <span className="font-semibold">{estadisticas_uso.documentos_ultimo_mes}</span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-gray-500">Usuarios que la usan</span>
                    <span className="font-semibold">{estadisticas_uso.usuarios_utilizan}</span>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle className="text-sm">Información del Autor</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="flex items-center space-x-2">
                    <User className="w-4 h-4 text-gray-400" />
                    <div>
                      <p className="font-medium text-sm">{plantilla.usuario_creador.name}</p>
                      <p className="text-xs text-gray-500">{plantilla.usuario_creador.email}</p>
                    </div>
                  </div>
                  <div className="mt-3 text-xs text-gray-500">
                    <p>Creada: {new Date(plantilla.created_at).toLocaleDateString('es-ES')}</p>
                    <p>Actualizada: {new Date(plantilla.updated_at).toLocaleDateString('es-ES')}</p>
                  </div>
                </CardContent>
              </Card>

              {puede_editar && (
                <Card>
                  <CardHeader>
                    <CardTitle className="text-sm">Acciones Rápidas</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-2">
                    <Dialog open={showCambiarEstado} onOpenChange={setShowCambiarEstado}>
                      <DialogTrigger asChild>
                        <Button variant="outline" size="sm" className="w-full justify-start">
                          <Settings className="w-4 h-4 mr-2" />
                          Cambiar Estado
                        </Button>
                      </DialogTrigger>
                      <DialogContent>
                        <DialogHeader>
                          <DialogTitle>Cambiar Estado de la Plantilla</DialogTitle>
                          <DialogDescription>
                            Selecciona el nuevo estado para esta plantilla
                          </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleCambiarEstado} className="space-y-4 mt-4">
                          <div className="space-y-2">
                            <Label htmlFor="estado">Estado</Label>
                            <Select
                              value={estadoForm.data.estado}
                              onValueChange={(value) => estadoForm.setData('estado', value)}
                            >
                              <SelectTrigger>
                                <SelectValue placeholder="Seleccionar estado" />
                              </SelectTrigger>
                              <SelectContent>
                                <SelectItem value="borrador">Borrador</SelectItem>
                                <SelectItem value="revision">En Revisión</SelectItem>
                                <SelectItem value="activa">Activa</SelectItem>
                                <SelectItem value="archivada">Archivada</SelectItem>
                                <SelectItem value="obsoleta">Obsoleta</SelectItem>
                              </SelectContent>
                            </Select>
                          </div>
                          <div className="space-y-2">
                            <Label htmlFor="observaciones">Observaciones (opcional)</Label>
                            <Textarea
                              id="observaciones"
                              value={estadoForm.data.observaciones}
                              onChange={(e) => estadoForm.setData('observaciones', e.target.value)}
                              placeholder="Agregar observaciones sobre el cambio de estado..."
                              rows={3}
                            />
                          </div>
                          <div className="flex justify-end space-x-2">
                            <Button
                              type="button"
                              variant="outline"
                              onClick={() => setShowCambiarEstado(false)}
                            >
                              Cancelar
                            </Button>
                            <Button type="submit" disabled={estadoForm.processing}>
                              {estadoForm.processing ? 'Guardando...' : 'Guardar Cambios'}
                            </Button>
                          </div>
                        </form>
                      </DialogContent>
                    </Dialog>

                    <Button 
                      variant="outline" 
                      size="sm" 
                      className="w-full justify-start"
                      onClick={crearNuevaVersion}
                    >
                      <Copy className="w-4 h-4 mr-2" />
                      Nueva Versión
                    </Button>

                    <Dialog>
                      <DialogTrigger asChild>
                        <Button variant="outline" size="sm" className="w-full justify-start">
                          <Download className="w-4 h-4 mr-2" />
                          Exportar
                        </Button>
                      </DialogTrigger>
                      <DialogContent>
                        <DialogHeader>
                          <DialogTitle>Exportar Plantilla</DialogTitle>
                          <DialogDescription>
                            Selecciona el formato de exportación
                          </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-2 mt-4">
                          <Button
                            variant="outline"
                            className="w-full justify-start"
                            onClick={() => exportarPlantilla('json')}
                          >
                            <Download className="w-4 h-4 mr-2" />
                            Exportar como JSON
                          </Button>
                          <Button
                            variant="outline"
                            className="w-full justify-start"
                            onClick={() => exportarPlantilla('html')}
                          >
                            <Download className="w-4 h-4 mr-2" />
                            Exportar como HTML
                          </Button>
                          <Button
                            variant="outline"
                            className="w-full justify-start"
                            onClick={() => exportarPlantilla('xml')}
                          >
                            <Download className="w-4 h-4 mr-2" />
                            Exportar como XML
                          </Button>
                        </div>
                      </DialogContent>
                    </Dialog>
                  </CardContent>
                </Card>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Modal de Vista Previa */}
      <Dialog open={showPreview} onOpenChange={setShowPreview}>
        <DialogContent className="max-w-4xl max-h-[80vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Vista Previa - {plantilla.nombre}</DialogTitle>
            <DialogDescription>
              Previsualización del contenido de la plantilla
            </DialogDescription>
          </DialogHeader>
          <div className="mt-4">
            {plantilla.contenido_html ? (
              <div 
                className="prose max-w-none"
                dangerouslySetInnerHTML={{ __html: plantilla.contenido_html }}
              />
            ) : (
              <p className="text-gray-500 italic">Sin contenido para previsualizar</p>
            )}
          </div>
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}
