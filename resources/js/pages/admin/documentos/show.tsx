import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { 
    DocumentTextIcon, 
    CloudArrowDownIcon, 
    EyeIcon,
    PencilIcon,
    DocumentDuplicateIcon,
    ShieldCheckIcon 
} from '@heroicons/react/24/outline';

interface Documento {
    id: number;
    codigo: string;
    nombre: string;
    descripcion?: string;
    estado: string;
    tipo_soporte: string;
    formato: string;
    tamaño?: number;
    version: string;
    created_at: string;
    url_descarga?: string;
    url_directa?: string;
    expediente?: {
        codigo: string;
        nombre: string;
        descripcion: string;
    };
    tipologia?: {
        nombre: string;
        categoria: string;
        descripcion: string;
    };
    usuario_creador?: {
        name: string;
        email: string;
    };
    versiones?: Array<{
        codigo: string;
        version: string;
        fecha_modificacion: string;
        observaciones: string;
        tamaño: number;
    }>;
    estadisticas?: {
        integridad_verificada: boolean;
        hash_integridad: string;
        tiene_firma_digital: boolean;
    };
}

interface Props {
    documento: Documento;
    puedeVisualizar: boolean;
}

const DocumentoShow = ({ documento, puedeVisualizar }: Props) => {
    const formatFileSize = (bytes?: number) => {
        if (!bytes) return 'N/A';
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    };

    const getEstadoBadgeColor = (estado: string) => {
        const colors: Record<string, string> = {
            'borrador': 'bg-gray-100 text-gray-800',
            'pendiente': 'bg-yellow-100 text-yellow-800',
            'aprobado': 'bg-blue-100 text-blue-800',
            'activo': 'bg-green-100 text-green-800',
            'archivado': 'bg-purple-100 text-purple-800',
            'obsoleto': 'bg-red-100 text-red-800',
        };
        return colors[estado] || 'bg-gray-100 text-gray-800';
    };

    const handleDescargar = () => {
        const url = documento.url_directa || documento.url_descarga;
        if (url) {
            // Para descarga, crear un enlace temporal
            const link = document.createElement('a');
            link.href = url;
            link.download = documento.nombre || 'documento';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else {
            alert('No se pudo generar la URL de descarga');
        }
    };

    const handlePreview = () => {
        window.open(`/admin/documentos/${documento.id}/preview`, '_blank');
    };

    return (
        <AppLayout>
            <Head title={`Documento: ${documento.nombre}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="md:flex md:items-center md:justify-between mb-6">
                        <div className="min-w-0 flex-1">
                            <nav className="flex" aria-label="Breadcrumb">
                                <ol className="flex items-center space-x-4">
                                    <li>
                                        <Button
                                            variant="ghost"
                                            onClick={() => router.visit('/admin/documentos')}
                                            className="text-gray-500 hover:text-gray-700"
                                        >
                                            Documentos
                                        </Button>
                                    </li>
                                    <li className="text-gray-500">/</li>
                                    <li className="text-gray-900 font-medium">{documento.codigo}</li>
                                </ol>
                            </nav>
                            <h1 className="mt-2 text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                                {documento.nombre}
                            </h1>
                            <div className="mt-1 flex items-center space-x-3">
                                <Badge className={getEstadoBadgeColor(documento.estado)}>
                                    {documento.estado}
                                </Badge>
                                <span className="text-sm text-gray-500">
                                    Versión {documento.version}
                                </span>
                            </div>
                        </div>
                        <div className="mt-4 flex md:ml-4 md:mt-0 space-x-3">
                            {puedeVisualizar && (
                                <Button
                                    variant="outline"
                                    onClick={handlePreview}
                                    className="flex items-center space-x-2"
                                >
                                    <EyeIcon className="h-4 w-4" />
                                    <span>Vista Previa</span>
                                </Button>
                            )}
                            <Button
                                variant="outline"
                                onClick={handleDescargar}
                                className="flex items-center space-x-2"
                            >
                                <CloudArrowDownIcon className="h-4 w-4" />
                                <span>Descargar</span>
                            </Button>
                            <Button
                                onClick={() => router.visit(`/admin/documentos/${documento.id}/edit`)}
                                className="flex items-center space-x-2 bg-[#2a3d83] hover:bg-[#1e2a5c]"
                            >
                                <PencilIcon className="h-4 w-4" />
                                <span>Editar</span>
                            </Button>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Información Principal */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Detalles del Documento */}
                            <div className="bg-white rounded-lg border p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                    Información del Documento
                                </h2>
                                <dl className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Código</dt>
                                        <dd className="mt-1 text-sm text-gray-900">{documento.codigo}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Formato</dt>
                                        <dd className="mt-1 text-sm text-gray-900 uppercase">{documento.formato}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Tipo de Soporte</dt>
                                        <dd className="mt-1 text-sm text-gray-900 capitalize">{documento.tipo_soporte}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Tamaño</dt>
                                        <dd className="mt-1 text-sm text-gray-900">{formatFileSize(documento.tamaño)}</dd>
                                    </div>
                                    <div className="sm:col-span-2">
                                        <dt className="text-sm font-medium text-gray-500">Descripción</dt>
                                        <dd className="mt-1 text-sm text-gray-900">
                                            {documento.descripcion || 'Sin descripción'}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            {/* Expediente y Tipología */}
                            <div className="bg-white rounded-lg border p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                    Clasificación Archivística
                                </h2>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {documento.expediente && (
                                        <div>
                                            <h3 className="text-sm font-medium text-gray-500 mb-2">Expediente</h3>
                                            <div className="p-3 bg-blue-50 rounded-lg">
                                                <p className="font-medium text-blue-900">{documento.expediente.nombre}</p>
                                                <p className="text-sm text-blue-700">{documento.expediente.codigo}</p>
                                                {documento.expediente.descripcion && (
                                                    <p className="text-xs text-blue-600 mt-1">
                                                        {documento.expediente.descripcion}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                    {documento.tipologia && (
                                        <div>
                                            <h3 className="text-sm font-medium text-gray-500 mb-2">Tipología Documental</h3>
                                            <div className="p-3 bg-green-50 rounded-lg">
                                                <p className="font-medium text-green-900">{documento.tipologia.nombre}</p>
                                                <p className="text-sm text-green-700">{documento.tipologia.categoria}</p>
                                                {documento.tipologia.descripcion && (
                                                    <p className="text-xs text-green-600 mt-1">
                                                        {documento.tipologia.descripcion}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Visor de Documento */}
                            <div className="bg-white rounded-lg border p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                    Vista Previa del Documento
                                </h2>
                                <div className="border rounded-lg overflow-hidden bg-gray-50 p-6">
                                    <div className="text-center space-y-4">
                                        <div className="mx-auto w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center">
                                            <DocumentTextIcon className="h-12 w-12 text-blue-600" />
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-medium text-gray-900 mb-2">
                                                {documento.nombre}
                                            </h3>
                                            <p className="text-sm text-gray-600 mb-1">
                                                Formato: <span className="font-medium uppercase">{documento.formato}</span>
                                            </p>
                                            <p className="text-sm text-gray-600">
                                                Tamaño: <span className="font-medium">{formatFileSize(documento.tamaño)}</span>
                                            </p>
                                        </div>
                                        <div className="flex flex-col sm:flex-row gap-3 justify-center">
                                            <Button
                                                onClick={() => {
                                                    const url = documento.url_directa || documento.url_descarga;
                                                    if (url) {
                                                        window.open(url, '_blank');
                                                    } else {
                                                        alert('No se pudo generar la URL del documento');
                                                    }
                                                }}
                                                className="flex items-center space-x-2 bg-blue-600 hover:bg-blue-700"
                                            >
                                                <EyeIcon className="h-4 w-4" />
                                                <span>Ver Documento</span>
                                            </Button>
                                            <Button
                                                variant="outline"
                                                onClick={handleDescargar}
                                                className="flex items-center space-x-2"
                                            >
                                                <CloudArrowDownIcon className="h-4 w-4" />
                                                <span>Descargar</span>
                                            </Button>
                                        </div>
                                        <p className="text-xs text-gray-500">
                                            Haz clic en "Ver Documento" para abrir el archivo en una nueva pestaña
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Versiones */}
                            {documento.versiones && documento.versiones.length > 0 && (
                                <div className="bg-white rounded-lg border p-6">
                                    <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                        Historial de Versiones
                                    </h2>
                                    <div className="space-y-3">
                                        {documento.versiones.map((version, index) => (
                                            <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                <div className="flex items-center space-x-3">
                                                    <DocumentDuplicateIcon className="h-5 w-5 text-gray-400" />
                                                    <div>
                                                        <p className="text-sm font-medium text-gray-900">
                                                            Versión {version.version}
                                                        </p>
                                                        <p className="text-xs text-gray-500">
                                                            {new Date(version.fecha_modificacion).toLocaleString('es-ES')}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <p className="text-sm text-gray-900">{formatFileSize(version.tamaño)}</p>
                                                    {version.observaciones && (
                                                        <p className="text-xs text-gray-500">{version.observaciones}</p>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Panel Lateral */}
                        <div className="space-y-6">
                            {/* Información del Sistema */}
                            <div className="bg-white rounded-lg border p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                    Información del Sistema
                                </h2>
                                <dl className="space-y-3">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Fecha de Creación</dt>
                                        <dd className="mt-1 text-sm text-gray-900">
                                            {new Date(documento.created_at).toLocaleString('es-ES')}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Creado Por</dt>
                                        <dd className="mt-1 text-sm text-gray-900">
                                            {documento.usuario_creador?.name || 'N/A'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Estado Actual</dt>
                                        <dd className="mt-1">
                                            <Badge className={getEstadoBadgeColor(documento.estado)}>
                                                {documento.estado}
                                            </Badge>
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            {/* Seguridad e Integridad */}
                            {documento.estadisticas && (
                                <div className="bg-white rounded-lg border p-6">
                                    <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                        Seguridad e Integridad
                                    </h2>
                                    <div className="space-y-3">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-gray-700">Integridad Verificada</span>
                                            <div className="flex items-center space-x-2">
                                                <ShieldCheckIcon className={`h-4 w-4 ${
                                                    documento.estadisticas.integridad_verificada 
                                                        ? 'text-green-500' 
                                                        : 'text-red-500'
                                                }`} />
                                                <span className={`text-sm ${
                                                    documento.estadisticas.integridad_verificada 
                                                        ? 'text-green-600' 
                                                        : 'text-red-600'
                                                }`}>
                                                    {documento.estadisticas.integridad_verificada ? 'Sí' : 'No'}
                                                </span>
                                            </div>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-gray-700">Firma Digital</span>
                                            <span className={`text-sm ${
                                                documento.estadisticas.tiene_firma_digital 
                                                    ? 'text-green-600' 
                                                    : 'text-gray-500'
                                            }`}>
                                                {documento.estadisticas.tiene_firma_digital ? 'Firmado' : 'Sin firmar'}
                                            </span>
                                        </div>
                                        {documento.estadisticas.hash_integridad && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Hash de Integridad</dt>
                                                <dd className="mt-1 text-xs text-gray-900 font-mono break-all">
                                                    {documento.estadisticas.hash_integridad.substring(0, 32)}...
                                                </dd>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Acciones Rápidas */}
                            <div className="bg-white rounded-lg border p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                    Acciones Rápidas
                                </h2>
                                <div className="space-y-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="w-full justify-start"
                                        onClick={() => router.post(`/admin/documentos/${documento.id}/crear-version`)}
                                    >
                                        <DocumentDuplicateIcon className="h-4 w-4 mr-2" />
                                        Nueva Versión
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="w-full justify-start"
                                        onClick={() => router.post(`/admin/documentos/${documento.id}/firmar`)}
                                    >
                                        <ShieldCheckIcon className="h-4 w-4 mr-2" />
                                        Firmar Digitalmente
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
};

export default DocumentoShow;
