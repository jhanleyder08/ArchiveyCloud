import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ArrowLeft, Save, Plus, Trash2, AlertCircle } from 'lucide-react';

interface PlantillaDocumental {
  id: number;
  codigo: string;
  nombre: string;
  descripcion: string;
  categoria: string;
  tipo_documento: string;
  serie_documental_id: number | null;
  subserie_documental_id: number | null;
  contenido_html: string;
  campos_variables: Array<{
    nombre: string;
    tipo: string;
    etiqueta: string;
    requerido: boolean;
    valor_defecto: string;
    opciones: string[];
  }>;
  metadatos_predefinidos: Record<string, any>;
  configuracion_formato: Record<string, any>;
  es_publica: boolean;
  tags: string[];
  observaciones: string;
  estado: string;
  version: number;
  serie_documental?: {
    id: number;
    codigo: string;
    nombre: string;
  };
  subserie_documental?: {
    id: number;
    codigo: string;
    nombre: string;
  };
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
  plantilla: PlantillaDocumental;
  series: SerieDocumental[];
  categorias: Record<string, string>;
}

interface CampoVariable {
  nombre: string;
  tipo: string;
  etiqueta: string;
  requerido: boolean;
  valor_defecto: string;
  opciones: string[];
}

export default function PlantillasEdit({ plantilla, series, categorias }: PageProps) {
  const { data, setData, put, processing, errors, isDirty } = useForm({
    nombre: plantilla.nombre || '',
    descripcion: plantilla.descripcion || '',
    categoria: plantilla.categoria || 'otro',
    tipo_documento: plantilla.tipo_documento || '',
    serie_documental_id: plantilla.serie_documental_id?.toString() || '',
    subserie_documental_id: plantilla.subserie_documental_id?.toString() || '',
    contenido_html: plantilla.contenido_html || '',
    campos_variables: plantilla.campos_variables || [],
    metadatos_predefinidos: plantilla.metadatos_predefinidos || {},
    configuracion_formato: plantilla.configuracion_formato || {},
    es_publica: plantilla.es_publica || false,
    tags: plantilla.tags || [],
    observaciones: plantilla.observaciones || ''
  });

  const [selectedSerie, setSelectedSerie] = useState<SerieDocumental | null>(
    series.find(s => s.id.toString() === data.serie_documental_id) || null
  );
  const [newTag, setNewTag] = useState('');
  const [newCampo, setNewCampo] = useState<CampoVariable>({
    nombre: '',
    tipo: 'texto',
    etiqueta: '',
    requerido: false,
    valor_defecto: '',
    opciones: []
  });
  const [showUnsavedWarning, setShowUnsavedWarning] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    put(route('admin.plantillas.update', plantilla.id));
  };

  const handleSerieChange = (serieId: string) => {
    const serie = series.find(s => s.id.toString() === serieId);
    setSelectedSerie(serie || null);
    setData('serie_documental_id', serieId);
    setData('subserie_documental_id', '');
  };

  const agregarTag = () => {
    if (newTag.trim() && !data.tags.includes(newTag.trim())) {
      setData('tags', [...data.tags, newTag.trim()]);
      setNewTag('');
    }
  };

  const eliminarTag = (index: number) => {
    setData('tags', data.tags.filter((_, i) => i !== index));
  };

  const agregarCampo = () => {
    if (newCampo.nombre && newCampo.etiqueta) {
      setData('campos_variables', [...data.campos_variables, { ...newCampo }]);
      setNewCampo({
        nombre: '',
        tipo: 'texto',
        etiqueta: '',
        requerido: false,
        valor_defecto: '',
        opciones: []
      });
    }
  };

  const eliminarCampo = (index: number) => {
    setData('campos_variables', data.campos_variables.filter((_, i) => i !== index));
  };

  const handleBackClick = (e: React.MouseEvent) => {
    if (isDirty) {
      e.preventDefault();
      setShowUnsavedWarning(true);
    }
  };

  return (
    <AppLayout
      header={
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-4">
            <Link
              href={route('admin.plantillas.show', plantilla.id)}
              className="text-gray-500 hover:text-gray-700"
              onClick={handleBackClick}
            >
              <ArrowLeft className="w-6 h-6" />
            </Link>
            <div>
              <h2 className="text-xl font-semibold text-gray-800">
                Editar Plantilla: {plantilla.nombre}
              </h2>
              <div className="flex items-center space-x-4 text-sm text-gray-600 mt-1">
                <span>{plantilla.codigo}</span>
                <span>v{plantilla.version}</span>
                <Badge className="text-xs">{plantilla.estado}</Badge>
              </div>
            </div>
          </div>
          {isDirty && (
            <div className="flex items-center space-x-2 text-amber-600">
              <AlertCircle className="w-4 h-4" />
              <span className="text-sm">Cambios sin guardar</span>
            </div>
          )}
        </div>
      }
    >
      <Head title={`Editar: ${plantilla.nombre}`} />

      <div className="py-6">
        <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
          <form onSubmit={handleSubmit} className="space-y-6">
            <Tabs defaultValue="basicos" className="w-full">
              <TabsList className="grid w-full grid-cols-4">
                <TabsTrigger value="basicos">Datos Básicos</TabsTrigger>
                <TabsTrigger value="contenido">Contenido</TabsTrigger>
                <TabsTrigger value="variables">Variables</TabsTrigger>
                <TabsTrigger value="configuracion">Configuración</TabsTrigger>
              </TabsList>

              <TabsContent value="basicos" className="space-y-6">
                <Card>
                  <CardHeader>
                    <CardTitle>Información General</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <Label htmlFor="nombre">Nombre de la Plantilla *</Label>
                        <Input
                          id="nombre"
                          value={data.nombre}
                          onChange={(e) => setData('nombre', e.target.value)}
                          className={errors.nombre ? 'border-red-500' : ''}
                        />
                        {errors.nombre && (
                          <p className="text-sm text-red-600">{errors.nombre}</p>
                        )}
                      </div>

                      <div className="space-y-2">
                        <Label htmlFor="categoria">Categoría *</Label>
                        <Select value={data.categoria} onValueChange={(value) => setData('categoria', value)}>
                          <SelectTrigger className={errors.categoria ? 'border-red-500' : ''}>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            {Object.entries(categorias).map(([key, label]) => (
                              <SelectItem key={key} value={key}>{label}</SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="descripcion">Descripción</Label>
                      <Textarea
                        id="descripcion"
                        value={data.descripcion}
                        onChange={(e) => setData('descripcion', e.target.value)}
                        rows={3}
                      />
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="tipo_documento">Tipo de Documento</Label>
                      <Input
                        id="tipo_documento"
                        value={data.tipo_documento}
                        onChange={(e) => setData('tipo_documento', e.target.value)}
                        placeholder="Ej: Memorando interno, Oficio externo..."
                      />
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <Label>Serie Documental</Label>
                        <Select 
                          value={data.serie_documental_id} 
                          onValueChange={handleSerieChange}
                        >
                          <SelectTrigger>
                            <SelectValue placeholder="Seleccionar serie" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="">Sin serie específica</SelectItem>
                            {series.map((serie) => (
                              <SelectItem key={serie.id} value={serie.id.toString()}>
                                {serie.codigo} - {serie.nombre}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>

                      {selectedSerie?.subseries && (
                        <div className="space-y-2">
                          <Label>Subserie Documental</Label>
                          <Select 
                            value={data.subserie_documental_id} 
                            onValueChange={(value) => setData('subserie_documental_id', value)}
                          >
                            <SelectTrigger>
                              <SelectValue placeholder="Seleccionar subserie" />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="">Sin subserie</SelectItem>
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
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="contenido" className="space-y-6">
                <Card>
                  <CardHeader>
                    <CardTitle>Contenido HTML</CardTitle>
                    <CardDescription>
                      Modifica el contenido base de la plantilla usando HTML
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    <Textarea
                      value={data.contenido_html}
                      onChange={(e) => setData('contenido_html', e.target.value)}
                      rows={12}
                      placeholder="Ingresa el contenido HTML de la plantilla..."
                      className="font-mono text-sm"
                    />
                    <div className="mt-2 text-sm text-gray-500">
                      <p>Puedes usar variables con la sintaxis: <code>{'{{VARIABLE_NAME}}'}</code></p>
                      <p>Variables del sistema disponibles: <code>{'{{FECHA_ACTUAL}}, {{USUARIO_ACTUAL}}, {{CODIGO_DOCUMENTO}}'}</code></p>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="variables" className="space-y-6">
                <Card>
                  <CardHeader>
                    <CardTitle>Campos Variables</CardTitle>
                    <CardDescription>
                      Administra los campos que se pueden personalizar al generar documentos
                    </CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border rounded-lg">
                      <div className="space-y-2">
                        <Label>Nombre de la Variable</Label>
                        <Input
                          value={newCampo.nombre}
                          onChange={(e) => setNewCampo({...newCampo, nombre: e.target.value})}
                          placeholder="nombre_variable"
                        />
                      </div>
                      <div className="space-y-2">
                        <Label>Etiqueta</Label>
                        <Input
                          value={newCampo.etiqueta}
                          onChange={(e) => setNewCampo({...newCampo, etiqueta: e.target.value})}
                          placeholder="Etiqueta visible"
                        />
                      </div>
                      <div className="space-y-2">
                        <Label>Tipo</Label>
                        <Select value={newCampo.tipo} onValueChange={(value) => setNewCampo({...newCampo, tipo: value})}>
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="texto">Texto</SelectItem>
                            <SelectItem value="numero">Número</SelectItem>
                            <SelectItem value="fecha">Fecha</SelectItem>
                            <SelectItem value="email">Email</SelectItem>
                            <SelectItem value="textarea">Texto largo</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                      <div className="space-y-2">
                        <Label>Valor por defecto</Label>
                        <Input
                          value={newCampo.valor_defecto}
                          onChange={(e) => setNewCampo({...newCampo, valor_defecto: e.target.value})}
                          placeholder="Valor inicial"
                        />
                      </div>
                      <div className="flex items-center space-x-2">
                        <Switch
                          checked={newCampo.requerido}
                          onCheckedChange={(checked) => setNewCampo({...newCampo, requerido: checked})}
                        />
                        <Label>Campo requerido</Label>
                      </div>
                      <div>
                        <Button type="button" onClick={agregarCampo} variant="outline">
                          <Plus className="w-4 h-4 mr-2" />
                          Agregar Campo
                        </Button>
                      </div>
                    </div>

                    {data.campos_variables.length > 0 && (
                      <div className="space-y-2">
                        <h4 className="font-medium">Campos configurados:</h4>
                        {data.campos_variables.map((campo, index) => (
                          <div key={index} className="flex items-center justify-between p-3 border rounded">
                            <div>
                              <span className="font-medium">{campo.etiqueta}</span>
                              <span className="ml-2 text-sm text-gray-500">
                                ({campo.nombre} - {campo.tipo})
                              </span>
                              {campo.requerido && (
                                <Badge variant="secondary" className="ml-2">Requerido</Badge>
                              )}
                            </div>
                            <Button
                              type="button"
                              variant="ghost"
                              size="sm"
                              onClick={() => eliminarCampo(index)}
                            >
                              <Trash2 className="w-4 h-4" />
                            </Button>
                          </div>
                        ))}
                      </div>
                    )}
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="configuracion" className="space-y-6">
                <Card>
                  <CardHeader>
                    <CardTitle>Configuración Adicional</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="flex items-center space-x-2">
                      <Switch
                        checked={data.es_publica}
                        onCheckedChange={(checked) => setData('es_publica', checked)}
                      />
                      <Label>Plantilla pública (disponible para todos los usuarios)</Label>
                    </div>

                    <div className="space-y-2">
                      <Label>Tags</Label>
                      <div className="flex space-x-2">
                        <Input
                          value={newTag}
                          onChange={(e) => setNewTag(e.target.value)}
                          placeholder="Agregar tag"
                          onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), agregarTag())}
                        />
                        <Button type="button" onClick={agregarTag} variant="outline">
                          <Plus className="w-4 h-4" />
                        </Button>
                      </div>
                      {data.tags.length > 0 && (
                        <div className="flex flex-wrap gap-2 mt-2">
                          {data.tags.map((tag, index) => (
                            <Badge key={index} variant="secondary" className="cursor-pointer" onClick={() => eliminarTag(index)}>
                              {tag} ×
                            </Badge>
                          ))}
                        </div>
                      )}
                    </div>

                    <div className="space-y-2">
                      <Label>Observaciones</Label>
                      <Textarea
                        value={data.observaciones}
                        onChange={(e) => setData('observaciones', e.target.value)}
                        rows={3}
                        placeholder="Notas adicionales sobre la plantilla"
                      />
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>
            </Tabs>

            <div className="flex justify-between">
              <Link
                href={route('admin.plantillas.show', plantilla.id)}
                className="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400"
                onClick={handleBackClick}
              >
                Cancelar
              </Link>
              <Button type="submit" disabled={processing}>
                <Save className="w-4 h-4 mr-2" />
                {processing ? 'Guardando...' : 'Guardar Cambios'}
              </Button>
            </div>
          </form>
        </div>
      </div>

      {/* Warning modal for unsaved changes */}
      {showUnsavedWarning && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white p-6 rounded-lg max-w-md">
            <h3 className="text-lg font-medium mb-4">¿Descartar cambios?</h3>
            <p className="text-gray-600 mb-6">
              Tienes cambios sin guardar. ¿Estás seguro de que quieres salir sin guardar?
            </p>
            <div className="flex justify-end space-x-3">
              <Button 
                variant="outline" 
                onClick={() => setShowUnsavedWarning(false)}
              >
                Continuar editando
              </Button>
              <Button 
                variant="destructive"
                onClick={() => window.history.back()}
              >
                Descartar cambios
              </Button>
            </div>
          </div>
        </div>
      )}
    </AppLayout>
  );
}
