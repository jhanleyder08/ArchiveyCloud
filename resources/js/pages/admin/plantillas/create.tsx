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
import { AlertCircle, ArrowLeft, Plus, Save, Trash2 } from 'lucide-react';

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

export default function PlantillasCreate({ series, categorias }: PageProps) {
  const { data, setData, post, processing, errors } = useForm({
    nombre: '',
    descripcion: '',
    categoria: 'otro',
    tipo_documento: '',
    serie_documental_id: '',
    subserie_documental_id: '',
    contenido_html: '',
    campos_variables: [] as CampoVariable[],
    metadatos_predefinidos: {},
    configuracion_formato: {},
    es_publica: false,
    tags: [] as string[],
    observaciones: ''
  });

  const [selectedSerie, setSelectedSerie] = useState<SerieDocumental | null>(null);
  const [newTag, setNewTag] = useState('');
  const [newCampo, setNewCampo] = useState<CampoVariable>({
    nombre: '',
    tipo: 'texto',
    etiqueta: '',
    requerido: false,
    valor_defecto: '',
    opciones: []
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('admin.plantillas.store'));
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
              <h2 className="text-xl font-semibold text-gray-800">
                Nueva Plantilla Documental
              </h2>
              <p className="text-sm text-gray-600">
                Crear nueva plantilla para documentos estandarizados
              </p>
            </div>
          </div>
        </div>
      }
    >
      <Head title="Nueva Plantilla Documental" />

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
                        <Select onValueChange={handleSerieChange}>
                          <SelectTrigger>
                            <SelectValue placeholder="Seleccionar serie" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="null">Sin serie específica</SelectItem>
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
                          <Select onValueChange={(value) => setData('subserie_documental_id', value)}>
                            <SelectTrigger>
                              <SelectValue placeholder="Seleccionar subserie" />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="null">Sin subserie</SelectItem>
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
                      Define el contenido base de la plantilla usando HTML
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
                      Define campos que se pueden personalizar al generar documentos
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
                        <h4 className="font-medium">Campos definidos:</h4>
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
                href={route('admin.plantillas.index')}
                className="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400"
              >
                Cancelar
              </Link>
              <Button type="submit" disabled={processing}>
                <Save className="w-4 h-4 mr-2" />
                {processing ? 'Guardando...' : 'Crear Plantilla'}
              </Button>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}
