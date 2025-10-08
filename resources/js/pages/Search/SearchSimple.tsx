import { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Search, FileText, Folder, Clock, User, ChevronDown, ChevronUp } from 'lucide-react';
import { formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';
import debounce from 'lodash/debounce';

interface SearchResult {
    id: string;
    score: number;
    source: any;
    highlights?: {
        [key: string]: string[];
    };
}

interface SearchResponse {
    total: number;
    max_score: number;
    results: SearchResult[];
    took: number;
}

interface Props {
    initialQuery?: string;
}

export default function SearchSimple({ initialQuery = '' }: Props) {
    const [query, setQuery] = useState(initialQuery);
    const [searchType, setSearchType] = useState<'documentos' | 'expedientes'>('documentos');
    const [results, setResults] = useState<SearchResult[]>([]);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(false);
    const [took, setTook] = useState(0);
    const [page, setPage] = useState(0);
    const [pageSize] = useState(20);
    const [sortField, setSortField] = useState('_score');
    const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');
    const [suggestions, setSuggestions] = useState<string[]>([]);
    const [showSuggestions, setShowSuggestions] = useState(false);

    // Autocompletado
    const fetchSuggestions = useCallback(
        debounce(async (searchQuery: string) => {
            if (searchQuery.length < 2) {
                setSuggestions([]);
                return;
            }

            try {
                const response = await axios.get('/search/autocomplete', {
                    params: {
                        q: searchQuery,
                        field: 'nombre',
                        type: searchType,
                    },
                });
                setSuggestions(response.data.suggestions || []);
                setShowSuggestions(true);
            } catch (error) {
                console.error('Error en autocompletado:', error);
            }
        }, 300),
        [searchType]
    );

    // Realizar búsqueda
    const performSearch = useCallback(async () => {
        if (!query.trim()) {
            setResults([]);
            setTotal(0);
            return;
        }

        setLoading(true);
        try {
            const response = await axios.post<{ success: boolean; data: SearchResponse }>(
                '/search/simple',
                {
                    q: query,
                    type: searchType,
                    size: pageSize,
                    from: page * pageSize,
                    sort: { [sortField]: sortOrder },
                }
            );

            if (response.data.success) {
                const data = response.data.data;
                setResults(data.results);
                setTotal(data.total);
                setTook(data.took);
            }
        } catch (error) {
            console.error('Error en búsqueda:', error);
        } finally {
            setLoading(false);
        }
    }, [query, searchType, pageSize, page, sortField, sortOrder]);

    // Efecto para autocompletado
    useEffect(() => {
        fetchSuggestions(query);
    }, [query, fetchSuggestions]);

    // Efecto para búsqueda cuando cambian filtros (no query)
    useEffect(() => {
        if (query.trim()) {
            performSearch();
        }
    }, [searchType, page, sortField, sortOrder, performSearch, query]);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        setPage(0);
        performSearch();
        setShowSuggestions(false);
    };

    const handleSuggestionClick = (suggestion: string) => {
        setQuery(suggestion);
        setShowSuggestions(false);
        setPage(0);
        performSearch();
    };

    const renderHighlight = (text: string) => {
        return <span dangerouslySetInnerHTML={{ __html: text }} />;
    };

    return (
        <div className="space-y-6">
            {/* Search Form */}
            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={handleSearch} className="space-y-4">
                        <div className="flex gap-4">
                            <div className="flex-1 relative">
                                <Input
                                    type="text"
                                    value={query}
                                    onChange={(e) => setQuery(e.target.value)}
                                    placeholder="Buscar documentos, expedientes..."
                                    className="pr-10"
                                    onFocus={() => suggestions.length > 0 && setShowSuggestions(true)}
                                    onBlur={() => setTimeout(() => setShowSuggestions(false), 200)}
                                />
                                <Search className="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />

                                {/* Suggestions Dropdown */}
                                {showSuggestions && suggestions.length > 0 && (
                                    <div className="absolute z-10 w-full mt-1 bg-background border rounded-md shadow-lg max-h-60 overflow-auto">
                                        {suggestions.map((suggestion, idx) => (
                                            <button
                                                key={idx}
                                                type="button"
                                                onClick={() => handleSuggestionClick(suggestion)}
                                                className="w-full text-left px-4 py-2 hover:bg-accent transition-colors"
                                            >
                                                {suggestion}
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>

                            <Select value={searchType} onValueChange={(v) => setSearchType(v as any)}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="documentos">Documentos</SelectItem>
                                    <SelectItem value="expedientes">Expedientes</SelectItem>
                                </SelectContent>
                            </Select>

                            <Button type="submit" disabled={loading}>
                                {loading ? 'Buscando...' : 'Buscar'}
                            </Button>
                        </div>

                        {/* Sort Options */}
                        <div className="flex gap-4 items-center">
                            <span className="text-sm text-muted-foreground">Ordenar por:</span>
                            <Select value={sortField} onValueChange={setSortField}>
                                <SelectTrigger className="w-[200px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_score">Relevancia</SelectItem>
                                    <SelectItem value="fecha_creacion">Fecha de Creación</SelectItem>
                                    <SelectItem value="nombre.keyword">Nombre</SelectItem>
                                    <SelectItem value="usuario_creador">Usuario</SelectItem>
                                </SelectContent>
                            </Select>

                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc')}
                            >
                                {sortOrder === 'asc' ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            {/* Results Info */}
            {query && (
                <div className="flex items-center justify-between">
                    <p className="text-sm text-muted-foreground">
                        {total > 0 ? (
                            <>
                                Se encontraron <strong>{total}</strong> resultados en <strong>{took}ms</strong>
                            </>
                        ) : (
                            'No se encontraron resultados'
                        )}
                    </p>
                </div>
            )}

            {/* Results */}
            <div className="space-y-4">
                {loading ? (
                    // Loading skeleton
                    Array.from({ length: 5 }).map((_, idx) => (
                        <Card key={idx}>
                            <CardContent className="pt-6">
                                <Skeleton className="h-6 w-3/4 mb-2" />
                                <Skeleton className="h-4 w-full mb-2" />
                                <Skeleton className="h-4 w-2/3" />
                            </CardContent>
                        </Card>
                    ))
                ) : (
                    results.map((result) => (
                        <Card key={result.id} className="hover:shadow-md transition-shadow">
                            <CardContent className="pt-6">
                                <div className="flex items-start gap-4">
                                    <div className="p-2 bg-primary/10 rounded-lg">
                                        {searchType === 'documentos' ? (
                                            <FileText className="h-6 w-6 text-primary" />
                                        ) : (
                                            <Folder className="h-6 w-6 text-primary" />
                                        )}
                                    </div>

                                    <div className="flex-1 space-y-2">
                                        <div className="flex items-start justify-between">
                                            <div>
                                                <h3 className="text-lg font-semibold">
                                                    {result.highlights?.nombre?.[0] ? (
                                                        renderHighlight(result.highlights.nombre[0])
                                                    ) : (
                                                        result.source.nombre
                                                    )}
                                                </h3>
                                                <p className="text-sm text-muted-foreground">
                                                    {result.source.codigo}
                                                </p>
                                            </div>
                                            <Badge variant="outline">
                                                Score: {result.score.toFixed(2)}
                                            </Badge>
                                        </div>

                                        {result.highlights?.descripcion && (
                                            <p className="text-sm">
                                                {renderHighlight(result.highlights.descripcion[0])}
                                            </p>
                                        )}

                                        {result.highlights?.contenido && (
                                            <div className="bg-accent/50 p-3 rounded-md">
                                                <p className="text-sm italic">
                                                    {renderHighlight(result.highlights.contenido[0])}
                                                </p>
                                            </div>
                                        )}

                                        <div className="flex gap-4 text-xs text-muted-foreground">
                                            {result.source.fecha_creacion && (
                                                <span className="flex items-center gap-1">
                                                    <Clock className="h-3 w-3" />
                                                    {formatDistanceToNow(new Date(result.source.fecha_creacion), {
                                                        addSuffix: true,
                                                        locale: es,
                                                    })}
                                                </span>
                                            )}
                                            {result.source.usuario_creador && (
                                                <span className="flex items-center gap-1">
                                                    <User className="h-3 w-3" />
                                                    {result.source.usuario_creador}
                                                </span>
                                            )}
                                            {result.source.serie_documental_nombre && (
                                                <Badge variant="secondary" className="text-xs">
                                                    {result.source.serie_documental_nombre}
                                                </Badge>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))
                )}
            </div>

            {/* Pagination */}
            {total > pageSize && (
                <div className="flex justify-center gap-2">
                    <Button
                        variant="outline"
                        onClick={() => setPage(page - 1)}
                        disabled={page === 0 || loading}
                    >
                        Anterior
                    </Button>
                    <span className="flex items-center px-4">
                        Página {page + 1} de {Math.ceil(total / pageSize)}
                    </span>
                    <Button
                        variant="outline"
                        onClick={() => setPage(page + 1)}
                        disabled={page >= Math.ceil(total / pageSize) - 1 || loading}
                    >
                        Siguiente
                    </Button>
                </div>
            )}
        </div>
    );
}
