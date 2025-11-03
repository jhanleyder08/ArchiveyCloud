import React, { useState, useEffect } from 'react';

/**
 * Gestor de Expedientes Híbridos
 * Maneja expedientes con componentes físicos y digitales
 */
const ExpedienteHibridoManager: React.FC = () => {
    const [expedientes, setExpedientes] = useState<any[]>([]);
    const [selectedExpediente, setSelectedExpediente] = useState<any>(null);
    const [view, setView] = useState<'list' | 'detail'>('list');

    useEffect(() => {
        fetchExpedientes();
    }, []);

    const fetchExpedientes = async () => {
        try {
            const response = await fetch('/api/expedientes?tipo=hibrido');
            const data = await response.json();
            setExpedientes(data.data || []);
        } catch (error) {
            console.error('Error cargando expedientes:', error);
        }
    };

    const getEstadoColor = (estado: string) => {
        const colors: Record<string, string> = {
            abierto: 'bg-green-100 text-green-800',
            cerrado: 'bg-gray-100 text-gray-800',
            en_tramite: 'bg-blue-100 text-blue-800',
        };
        return colors[estado] || 'bg-gray-100 text-gray-800';
    };

    const getComponenteIcon = (tipo: string) => {
        return tipo === 'digital' ? '💻' : '📦';
    };

    return (
        <div className="h-screen flex flex-col bg-gray-100">
            {/* Header */}
            <div className="bg-white shadow-sm p-4 flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold">Expedientes Híbridos</h1>
                    <p className="text-sm text-gray-600">Gestión de expedientes físicos y digitales</p>
                </div>
                <button className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    + Nuevo Expediente Híbrido
                </button>
            </div>

            <div className="flex flex-1 overflow-hidden">
                {/* Lista de expedientes */}
                <div className="w-96 bg-white border-r overflow-y-auto">
                    <div className="p-4">
                        <input
                            type="text"
                            placeholder="Buscar expedientes..."
                            className="w-full px-4 py-2 border rounded"
                        />
                    </div>
                    
                    <div className="divide-y">
                        {expedientes.map((exp) => (
                            <div
                                key={exp.id}
                                onClick={() => {
                                    setSelectedExpediente(exp);
                                    setView('detail');
                                }}
                                className="p-4 hover:bg-gray-50 cursor-pointer"
                            >
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <div className="font-semibold">{exp.codigo}</div>
                                        <div className="text-sm text-gray-600 mt-1">{exp.nombre}</div>
                                        <div className="flex gap-2 mt-2">
                                            <span className={`text-xs px-2 py-1 rounded ${getEstadoColor(exp.estado)}`}>
                                                {exp.estado}
                                            </span>
                                            <span className="text-xs px-2 py-1 rounded bg-purple-100 text-purple-800">
                                                Híbrido
                                            </span>
                                        </div>
                                    </div>
                                    <div className="text-2xl">📂</div>
                                </div>
                                
                                {/* Indicadores rápidos */}
                                <div className="flex gap-4 mt-3 text-xs text-gray-500">
                                    <span>💻 {exp.componentes_digitales || 0} digitales</span>
                                    <span>📦 {exp.componentes_fisicos || 0} físicos</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Detalle del expediente */}
                <div className="flex-1 overflow-y-auto p-6">
                    {selectedExpediente ? (
                        <div className="space-y-6">
                            {/* Información general */}
                            <div className="bg-white rounded-lg shadow-lg p-6">
                                <div className="flex items-start justify-between mb-4">
                                    <div>
                                        <h2 className="text-2xl font-bold">{selectedExpediente.codigo}</h2>
                                        <p className="text-gray-600 mt-1">{selectedExpediente.nombre}</p>
                                    </div>
                                    <span className={`px-3 py-1 rounded ${getEstadoColor(selectedExpediente.estado)}`}>
                                        {selectedExpediente.estado}
                                    </span>
                                </div>

                                <div className="grid grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <div className="text-sm text-gray-500">Serie Documental</div>
                                        <div className="font-medium">{selectedExpediente.serie_documental || 'N/A'}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-gray-500">Fecha Apertura</div>
                                        <div className="font-medium">{selectedExpediente.fecha_apertura || 'N/A'}</div>
                                    </div>
                                </div>
                            </div>

                            {/* Componentes Digitales */}
                            <div className="bg-white rounded-lg shadow-lg p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="text-lg font-semibold flex items-center gap-2">
                                        <span>💻</span> Componentes Digitales
                                    </h3>
                                    <button className="text-sm text-blue-600 hover:text-blue-800">
                                        + Agregar documento
                                    </button>
                                </div>
                                
                                <div className="space-y-2">
                                    {[1, 2, 3].map((i) => (
                                        <div key={i} className="flex items-center justify-between p-3 border rounded hover:bg-gray-50">
                                            <div className="flex items-center gap-3">
                                                <span className="text-2xl">📄</span>
                                                <div>
                                                    <div className="font-medium">Documento Digital {i}</div>
                                                    <div className="text-sm text-gray-500">PDF • 2.5 MB • 12 páginas</div>
                                                </div>
                                            </div>
                                            <div className="flex gap-2">
                                                <button className="text-sm text-blue-600 hover:text-blue-800">Ver</button>
                                                <button className="text-sm text-gray-600 hover:text-gray-800">Descargar</button>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <div className="mt-4 p-3 bg-blue-50 rounded">
                                    <div className="text-sm font-medium text-blue-800">Estadísticas Digitales</div>
                                    <div className="grid grid-cols-3 gap-4 mt-2 text-sm text-blue-700">
                                        <div>Total: 24 documentos</div>
                                        <div>Tamaño: 156 MB</div>
                                        <div>Páginas: 847</div>
                                    </div>
                                </div>
                            </div>

                            {/* Componentes Físicos */}
                            <div className="bg-white rounded-lg shadow-lg p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="text-lg font-semibold flex items-center gap-2">
                                        <span>📦</span> Componentes Físicos
                                    </h3>
                                    <button className="text-sm text-blue-600 hover:text-blue-800">
                                        + Registrar físico
                                    </button>
                                </div>

                                <div className="space-y-2">
                                    {[1, 2].map((i) => (
                                        <div key={i} className="p-4 border rounded hover:bg-gray-50">
                                            <div className="flex items-start justify-between">
                                                <div className="flex gap-3">
                                                    <span className="text-2xl">📦</span>
                                                    <div>
                                                        <div className="font-medium">Carpeta Física {i}</div>
                                                        <div className="text-sm text-gray-600 mt-1">Caja 045 • Estante B-12</div>
                                                        <div className="grid grid-cols-2 gap-4 mt-2 text-xs text-gray-500">
                                                            <div>
                                                                <span className="font-medium">Ubicación:</span> Archivo Central
                                                            </div>
                                                            <div>
                                                                <span className="font-medium">Folios:</span> 125
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span className="text-xs px-2 py-1 bg-green-100 text-green-800 rounded">
                                                    Disponible
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <div className="mt-4 p-3 bg-orange-50 rounded">
                                    <div className="text-sm font-medium text-orange-800">Estadísticas Físicas</div>
                                    <div className="grid grid-cols-3 gap-4 mt-2 text-sm text-orange-700">
                                        <div>Cajas: 3</div>
                                        <div>Carpetas: 12</div>
                                        <div>Folios: 2,450</div>
                                    </div>
                                </div>
                            </div>

                            {/* Índice de Contenido */}
                            <div className="bg-white rounded-lg shadow-lg p-6">
                                <h3 className="text-lg font-semibold mb-4">Índice de Contenido</h3>
                                <div className="space-y-2">
                                    <div className="flex items-center gap-3 p-2 hover:bg-gray-50 rounded">
                                        <span className="text-gray-400">#1</span>
                                        <span>💻</span>
                                        <span className="flex-1">Acta de Constitución</span>
                                        <span className="text-xs text-gray-500">Digital</span>
                                    </div>
                                    <div className="flex items-center gap-3 p-2 hover:bg-gray-50 rounded">
                                        <span className="text-gray-400">#2</span>
                                        <span>📦</span>
                                        <span className="flex-1">Estatutos Originales</span>
                                        <span className="text-xs text-gray-500">Físico</span>
                                    </div>
                                    <div className="flex items-center gap-3 p-2 hover:bg-gray-50 rounded">
                                        <span className="text-gray-400">#3</span>
                                        <span>💻</span>
                                        <span className="flex-1">Certificado de Registro</span>
                                        <span className="text-xs text-gray-500">Digital</span>
                                    </div>
                                </div>
                            </div>

                            {/* Trazabilidad */}
                            <div className="bg-white rounded-lg shadow-lg p-6">
                                <h3 className="text-lg font-semibold mb-4">Historial de Movimientos</h3>
                                <div className="space-y-3">
                                    {[
                                        { fecha: '2025-11-02 15:30', accion: 'Agregado documento digital', usuario: 'Juan Pérez' },
                                        { fecha: '2025-11-01 10:15', accion: 'Registrado componente físico', usuario: 'María González' },
                                        { fecha: '2025-10-30 14:45', accion: 'Expediente creado', usuario: 'Admin' },
                                    ].map((log, i) => (
                                        <div key={i} className="flex gap-3 pb-3 border-b last:border-0">
                                            <div className="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                                            <div className="flex-1">
                                                <div className="text-sm font-medium">{log.accion}</div>
                                                <div className="text-xs text-gray-500 mt-1">
                                                    {log.fecha} • {log.usuario}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="flex items-center justify-center h-full">
                            <div className="text-center text-gray-400">
                                <div className="text-6xl mb-4">📂</div>
                                <div>Selecciona un expediente para ver sus detalles</div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default ExpedienteHibridoManager;
