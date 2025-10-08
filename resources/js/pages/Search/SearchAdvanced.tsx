import { useState } from 'react';
import axios from 'axios';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Plus, X, Search, FileText, AlertCircle } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface SearchField {
    id: string;
    field: string;
    value: string;
}

interface DateRange {
    field: string;
    from: string;
    to: string;
}

export default function SearchAdvanced() {
    const [mustTerms, setMustTerms] = useState<string[]>(['']);
    const [shouldTerms, setShouldTerms] = useState<string[]>([]);
    const [mustNotTerms, setMustNotTerms] = useState<string[]>([]);
    const [fieldSearches, setFieldSearches] = useState<SearchField[]>([]);
    const [dateRanges, setDateRanges] = useState<DateRange[]>([]);
    const [keywords, setKeywords] = useState<string>('');
    const [loading, setLoading] = useState(false);
    const [results, setResults] = useState<any[]>([]);
    const [total, setTotal] = useState(0);
    const [aggregations, setAggregations] = useState<any>(null);

    // Agregar término MUST (AND)
    const addMustTerm = () => setMustTerms([...mustTerms, '']);
    const updateMustTerm = (index: number, value: string) => {
        const newTerms = [...mustTerms];
        newTerms[index] = value;
        setMustTerms(newTerms);
    };
    const removeMustTerm = (index: number) => {
        setMustTerms(mustTerms.filter((_, i) => i !== index));
    };

    // Agregar término SHOULD (OR)
    const addShouldTerm = () => setShouldTerms([...shouldTerms, '']);
    const updateShouldTerm = (index: number, value: string) => {
        const newTerms = [...shouldTerms];
        newTerms[index] = value;
        setShouldTerms(newTerms);
    };
    const removeShouldTerm = (index: number) => {
        setShouldTerms(shouldTerms.filter((_, i) => i !== index));
    };

    // Agregar término MUST_NOT (NOT)
    const addMustNotTerm = () => setMustNotTerms([...mustNotTerms, '']);
    const updateMustNotTerm = (index: number, value: string) => {
        const newTerms = [...mustNotTerms];
        newTerms[index] = value;
        setMustNotTerms(newTerms);
    };
    const removeMustNotTerm = (index: number) => {
        setMustNotTerms(mustNotTerms.filter((_, i) => i !== index));
    };

    // Búsqueda por campos específicos
    const addFieldSearch = () => {
        setFieldSearches([
            ...fieldSearches,
            { id: Date.now().toString(), field: 'nombre', value: '' },
        ]);
    };
    const updateFieldSearch = (id: string, updates: Partial<SearchField>) => {
        setFieldSearches(
            fieldSearches.map((fs) => (fs.id === id ? { ...fs, ...updates } : fs))
        );
    };
    const removeFieldSearch = (id: string) => {
        setFieldSearches(fieldSearches.filter((fs) => fs.id !== id));
    };

    // Rango de fechas
    const addDateRange = () => {
        setDateRanges([
            ...dateRanges,
            { field: 'fecha_creacion', from: '', to: '' },
        ]);
    };
    const updateDateRange = (index: number, updates: Partial<DateRange>) => {
        const newRanges = [...dateRanges];
        newRanges[index] = { ...newRanges[index], ...updates };
        setDateRanges(newRanges);
    };
    const removeDateRange = (index: number) => {
        setDateRanges(dateRanges.filter((_, i) => i !== index));
    };

    // Realizar búsqueda
    const handleSearch = async () => {
        setLoading(true);
        try {
            const searchParams: any = {
                must: mustTerms.filter((t) => t.trim() !== ''),
                should: shouldTerms.filter((t) => t.trim() !== ''),
                must_not: mustNotTerms.filter((t) => t.trim() !== ''),
                fields: {},
                date_range: {},
            };

            // Agregar campos específicos
            fieldSearches.forEach((fs) => {
                if (fs.value.trim()) {
                    searchParams.fields[fs.field] = fs.value;
                }
            });

            // Agregar rangos de fecha
            dateRanges.forEach((dr) => {
                if (dr.from || dr.to) {
                    searchParams.date_range[dr.field] = {
                        from: dr.from || undefined,
                        to: dr.to || undefined,
                    };
                }
            });

            // Agregar palabras clave
            if (keywords.trim()) {
                searchParams.keywords = keywords.split(',').map((k) => k.trim());
            }

            const response = await axios.post('/search/advanced', {
                ...searchParams,
                type: 'documentos',
                size: 20,
                aggregations: ['tipo_documento', 'serie_documental_nombre', 'estado'],
            });

            if (response.data.success) {
                const data = response.data.data;
                setResults(data.results);
                setTotal(data.total);
                setAggregations(data.aggregations);
            }
        } catch (error) {
            console.error('Error en búsqueda avanzada:', error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="space-y-6">
            {/* Información de operadores */}
            <Alert>
                <AlertCircle className="h-4 w-4" />
                <AlertDescription>
                    <strong>Operadores disponibles:</strong> Use <code>*</code> y <code>?</code> como
                    comodines, <code>=término</code> para búsqueda exacta. Los campos se combinan con
                    lógica booleana (AND, OR, NOT).
                </AlertDescription>
            </Alert>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Términos AND (MUST) */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Badge variant="default">AND</Badge>
                            Términos que DEBEN estar
                        </CardTitle>
                        <CardDescription>
                            Todos estos términos deben aparecer en los resultados
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {mustTerms.map((term, index) => (
                            <div key={index} className="flex gap-2">
                                <Input
                                    value={term}
                                    onChange={(e) => updateMustTerm(index, e.target.value)}
                                    placeholder="ej: contrato, doc-2024-*"
                                />
                                {mustTerms.length > 1 && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => removeMustTerm(index)}
                                    >
                                        <X className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                        ))}
                        <Button variant="outline" size="sm" onClick={addMustTerm} className="w-full">
                            <Plus className="h-4 w-4 mr-2" />
                            Agregar término AND
                        </Button>
                    </CardContent>
                </Card>

                {/* Términos OR (SHOULD) */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Badge variant="secondary">OR</Badge>
                            Términos opcionales
                        </CardTitle>
                        <CardDescription>
                            Al menos uno de estos términos debe aparecer
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {shouldTerms.map((term, index) => (
                            <div key={index} className="flex gap-2">
                                <Input
                                    value={term}
                                    onChange={(e) => updateShouldTerm(index, e.target.value)}
                                    placeholder="ej: 2024, 2025"
                                />
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => removeShouldTerm(index)}
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            </div>
                        ))}
                        {shouldTerms.length === 0 && (
                            <p className="text-sm text-muted-foreground text-center py-4">
                                No hay términos opcionales
                            </p>
                        )}
                        <Button variant="outline" size="sm" onClick={addShouldTerm} className="w-full">
                            <Plus className="h-4 w-4 mr-2" />
                            Agregar término OR
                        </Button>
                    </CardContent>
                </Card>

                {/* Términos NOT (MUST_NOT) */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Badge variant="destructive">NOT</Badge>
                            Términos excluidos
                        </CardTitle>
                        <CardDescription>
                            Estos términos NO deben aparecer en los resultados
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {mustNotTerms.map((term, index) => (
                            <div key={index} className="flex gap-2">
                                <Input
                                    value={term}
                                    onChange={(e) => updateMustNotTerm(index, e.target.value)}
                                    placeholder="ej: borrador, cancelado"
                                />
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => removeMustNotTerm(index)}
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            </div>
                        ))}
                        {mustNotTerms.length === 0 && (
                            <p className="text-sm text-muted-foreground text-center py-4">
                                No hay términos excluidos
                            </p>
                        )}
                        <Button variant="outline" size="sm" onClick={addMustNotTerm} className="w-full">
                            <Plus className="h-4 w-4 mr-2" />
                            Agregar término NOT
                        </Button>
                    </CardContent>
                </Card>

                {/* Búsqueda por campos específicos */}
                <Card>
                    <CardHeader>
                        <CardTitle>Búsqueda por campos</CardTitle>
                        <CardDescription>
                            Buscar en campos específicos del documento
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {fieldSearches.map((fs) => (
                            <div key={fs.id} className="flex gap-2">
                                <Select
                                    value={fs.field}
                                    onValueChange={(v) => updateFieldSearch(fs.id, { field: v })}
                                >
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="nombre">Nombre</SelectItem>
                                        <SelectItem value="codigo">Código</SelectItem>
                                        <SelectItem value="descripcion">Descripción</SelectItem>
                                        <SelectItem value="contenido">Contenido</SelectItem>
                                        <SelectItem value="usuario_creador">Usuario</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Input
                                    value={fs.value}
                                    onChange={(e) => updateFieldSearch(fs.id, { value: e.target.value })}
                                    placeholder="Valor a buscar..."
                                />
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => removeFieldSearch(fs.id)}
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            </div>
                        ))}
                        {fieldSearches.length === 0 && (
                            <p className="text-sm text-muted-foreground text-center py-4">
                                No hay búsquedas por campo
                            </p>
                        )}
                        <Button variant="outline" size="sm" onClick={addFieldSearch} className="w-full">
                            <Plus className="h-4 w-4 mr-2" />
                            Agregar campo
                        </Button>
                    </CardContent>
                </Card>
            </div>

            {/* Rangos de fecha */}
            <Card>
                <CardHeader>
                    <CardTitle>Rangos de Fecha</CardTitle>
                    <CardDescription>Filtrar por períodos de tiempo</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {dateRanges.map((dr, index) => (
                        <div key={index} className="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <div>
                                <Label>Campo</Label>
                                <Select
                                    value={dr.field}
                                    onValueChange={(v) => updateDateRange(index, { field: v })}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="fecha_creacion">Fecha Creación</SelectItem>
                                        <SelectItem value="fecha_modificacion">
                                            Fecha Modificación
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Desde</Label>
                                <Input
                                    type="date"
                                    value={dr.from}
                                    onChange={(e) => updateDateRange(index, { from: e.target.value })}
                                />
                            </div>
                            <div>
                                <Label>Hasta</Label>
                                <Input
                                    type="date"
                                    value={dr.to}
                                    onChange={(e) => updateDateRange(index, { to: e.target.value })}
                                />
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => removeDateRange(index)}
                            >
                                <X className="h-4 w-4" />
                            </Button>
                        </div>
                    ))}
                    <Button variant="outline" size="sm" onClick={addDateRange} className="w-full">
                        <Plus className="h-4 w-4 mr-2" />
                        Agregar rango de fecha
                    </Button>
                </CardContent>
            </Card>

            {/* Palabras clave */}
            <Card>
                <CardHeader>
                    <CardTitle>Palabras Clave</CardTitle>
                    <CardDescription>Separadas por comas</CardDescription>
                </CardHeader>
                <CardContent>
                    <Input
                        value={keywords}
                        onChange={(e) => setKeywords(e.target.value)}
                        placeholder="ej: contrato, servicios profesionales, consultoría"
                    />
                </CardContent>
            </Card>

            {/* Botón de búsqueda */}
            <div className="flex justify-end">
                <Button onClick={handleSearch} disabled={loading} size="lg">
                    <Search className="h-4 w-4 mr-2" />
                    {loading ? 'Buscando...' : 'Realizar Búsqueda Avanzada'}
                </Button>
            </div>

            {/* Resultados y Facetas */}
            {total > 0 && (
                <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    {/* Facetas/Aggregations */}
                    {aggregations && (
                        <Card className="lg:col-span-1">
                            <CardHeader>
                                <CardTitle>Filtros</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {Object.entries(aggregations).map(([key, value]: [string, any]) => (
                                    <div key={key}>
                                        <h4 className="font-semibold text-sm mb-2 capitalize">
                                            {key.replace(/_/g, ' ')}
                                        </h4>
                                        <div className="space-y-1">
                                            {value.buckets?.slice(0, 5).map((bucket: any) => (
                                                <div
                                                    key={bucket.key}
                                                    className="flex justify-between text-sm"
                                                >
                                                    <span>{bucket.key}</span>
                                                    <Badge variant="secondary">{bucket.doc_count}</Badge>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}

                    {/* Resultados */}
                    <div className="lg:col-span-3 space-y-4">
                        <p className="text-sm text-muted-foreground">
                            Se encontraron <strong>{total}</strong> resultados
                        </p>
                        {results.map((result) => (
                            <Card key={result.id}>
                                <CardContent className="pt-6">
                                    <div className="flex items-start gap-4">
                                        <FileText className="h-6 w-6 text-primary" />
                                        <div className="flex-1">
                                            <h3 className="font-semibold">{result.source.nombre}</h3>
                                            <p className="text-sm text-muted-foreground">
                                                {result.source.codigo}
                                            </p>
                                            {result.highlights?.contenido && (
                                                <p
                                                    className="text-sm mt-2"
                                                    dangerouslySetInnerHTML={{
                                                        __html: result.highlights.contenido[0],
                                                    }}
                                                />
                                            )}
                                        </div>
                                        <Badge>Score: {result.score.toFixed(2)}</Badge>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
