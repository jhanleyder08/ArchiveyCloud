import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Search, Filter, Sparkles } from 'lucide-react';
import SearchSimple from './SearchSimple';
import SearchAdvanced from './SearchAdvanced';

interface Props {
    query?: string;
}

export default function SearchIndex({ query = '' }: Props) {
    const [activeTab, setActiveTab] = useState<'simple' | 'advanced'>('simple');

    return (
        <AppLayout>
            <Head title="Búsqueda de Documentos" />

            <div className="container mx-auto py-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Búsqueda de Documentos</h1>
                        <p className="text-muted-foreground mt-1">
                            Sistema de búsqueda avanzada con Elasticsearch
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Sparkles className="h-5 w-5 text-primary" />
                        <span className="text-sm font-medium">Búsqueda de Texto Completo</span>
                    </div>
                </div>

                {/* Tabs: Simple vs Advanced */}
                <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as 'simple' | 'advanced')} className="w-full">
                    <TabsList className="grid w-full max-w-md grid-cols-2">
                        <TabsTrigger value="simple" className="flex items-center gap-2">
                            <Search className="h-4 w-4" />
                            Búsqueda Simple
                        </TabsTrigger>
                        <TabsTrigger value="advanced" className="flex items-center gap-2">
                            <Filter className="h-4 w-4" />
                            Búsqueda Avanzada
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="simple" className="mt-6">
                        <SearchSimple initialQuery={query} />
                    </TabsContent>

                    <TabsContent value="advanced" className="mt-6">
                        <SearchAdvanced />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
