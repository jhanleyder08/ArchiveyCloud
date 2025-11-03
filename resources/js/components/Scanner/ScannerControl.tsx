import React, { useState, useEffect } from 'react';

/**
 * Interfaz de Control de Scanner
 * UI completa para gestionar escaneo de documentos
 */
const ScannerControl: React.FC = () => {
    const [scanners, setScanners] = useState<any[]>([]);
    const [selectedScanner, setSelectedScanner] = useState<string | null>(null);
    const [scanning, setScanning] = useState(false);
    const [preview, setPreview] = useState<string | null>(null);
    const [scannedFiles, setScannedFiles] = useState<any[]>([]);

    const [config, setConfig] = useState({
        dpi: 300,
        colorMode: 'color',
        format: 'pdf',
        duplex: false,
        autoRotate: true,
        autoDeskew: true,
        blankPageDetection: true,
    });

    // Descubrir scanners al cargar
    useEffect(() => {
        discoverScanners();
    }, []);

    const discoverScanners = async () => {
        try {
            const response = await fetch('/api/scanner/discover');
            const data = await response.json();
            setScanners(data.scanners || []);
            if (data.scanners?.length > 0) {
                setSelectedScanner(data.scanners[0].id);
            }
        } catch (error) {
            console.error('Error descubriendo scanners:', error);
        }
    };

    const executeScan = async () => {
        if (!selectedScanner) {
            alert('Selecciona un scanner primero');
            return;
        }

        setScanning(true);
        try {
            const response = await fetch(`/api/scanner/${selectedScanner}/scan`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(config),
            });

            const data = await response.json();
            setScannedFiles([...scannedFiles, data]);
            setPreview(data.path);
            alert('Escaneo completado');
        } catch (error) {
            console.error('Error escaneando:', error);
            alert('Error al escanear');
        } finally {
            setScanning(false);
        }
    };

    const previewScan = async () => {
        if (!selectedScanner) return;

        try {
            const response = await fetch(`/api/scanner/${selectedScanner}/preview`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...config, dpi: 150 }),
            });

            const data = await response.json();
            setPreview(data.path);
        } catch (error) {
            console.error('Error en preview:', error);
        }
    };

    const batchScan = async () => {
        const maxPages = parseInt(prompt('¿Cuántas páginas deseas escanear?') || '10');
        
        setScanning(true);
        try {
            const response = await fetch(`/api/scanner/${selectedScanner}/batch`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...config, maxPages }),
            });

            const data = await response.json();
            setScannedFiles([...scannedFiles, ...data.results]);
            alert(`${data.results.length} páginas escaneadas`);
        } catch (error) {
            console.error('Error en batch scan:', error);
        } finally {
            setScanning(false);
        }
    };

    return (
        <div className="h-screen flex bg-gray-100">
            {/* Panel de configuración */}
            <div className="w-80 bg-white p-6 overflow-y-auto shadow-lg">
                <h2 className="text-2xl font-bold mb-6">Control de Scanner</h2>

                {/* Selección de scanner */}
                <div className="mb-6">
                    <label className="block text-sm font-medium mb-2">Scanner</label>
                    <select
                        value={selectedScanner || ''}
                        onChange={(e) => setSelectedScanner(e.target.value)}
                        className="w-full border rounded px-3 py-2"
                    >
                        <option value="">Seleccionar...</option>
                        {scanners.map((scanner) => (
                            <option key={scanner.id} value={scanner.id}>
                                {scanner.name}
                            </option>
                        ))}
                    </select>
                    <button
                        onClick={discoverScanners}
                        className="mt-2 text-sm text-blue-600 hover:text-blue-800"
                    >
                        🔄 Buscar scanners
                    </button>
                </div>

                {/* Configuración */}
                <div className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium mb-2">DPI</label>
                        <select
                            value={config.dpi}
                            onChange={(e) => setConfig({ ...config, dpi: parseInt(e.target.value) })}
                            className="w-full border rounded px-3 py-2"
                        >
                            <option value="150">150 DPI (Borrador)</option>
                            <option value="200">200 DPI (Normal)</option>
                            <option value="300">300 DPI (Alta calidad)</option>
                            <option value="400">400 DPI (OCR óptimo)</option>
                            <option value="600">600 DPI (Fotografía)</option>
                            <option value="1200">1200 DPI (Máxima calidad)</option>
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-2">Modo de color</label>
                        <select
                            value={config.colorMode}
                            onChange={(e) => setConfig({ ...config, colorMode: e.target.value })}
                            className="w-full border rounded px-3 py-2"
                        >
                            <option value="color">Color</option>
                            <option value="grayscale">Escala de grises</option>
                            <option value="bw">Blanco y negro</option>
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-2">Formato</label>
                        <select
                            value={config.format}
                            onChange={(e) => setConfig({ ...config, format: e.target.value })}
                            className="w-full border rounded px-3 py-2"
                        >
                            <option value="pdf">PDF</option>
                            <option value="jpg">JPG</option>
                            <option value="png">PNG</option>
                            <option value="tiff">TIFF</option>
                        </select>
                    </div>

                    <div className="space-y-2">
                        <label className="flex items-center">
                            <input
                                type="checkbox"
                                checked={config.duplex}
                                onChange={(e) => setConfig({ ...config, duplex: e.target.checked })}
                                className="mr-2"
                            />
                            <span className="text-sm">Escaneo duplex (ambas caras)</span>
                        </label>

                        <label className="flex items-center">
                            <input
                                type="checkbox"
                                checked={config.autoRotate}
                                onChange={(e) => setConfig({ ...config, autoRotate: e.target.checked })}
                                className="mr-2"
                            />
                            <span className="text-sm">Rotación automática</span>
                        </label>

                        <label className="flex items-center">
                            <input
                                type="checkbox"
                                checked={config.autoDeskew}
                                onChange={(e) => setConfig({ ...config, autoDeskew: e.target.checked })}
                                className="mr-2"
                            />
                            <span className="text-sm">Enderezar automáticamente</span>
                        </label>

                        <label className="flex items-center">
                            <input
                                type="checkbox"
                                checked={config.blankPageDetection}
                                onChange={(e) => setConfig({ ...config, blankPageDetection: e.target.checked })}
                                className="mr-2"
                            />
                            <span className="text-sm">Detectar páginas en blanco</span>
                        </label>
                    </div>
                </div>

                {/* Botones de acción */}
                <div className="mt-8 space-y-2">
                    <button
                        onClick={previewScan}
                        disabled={!selectedScanner || scanning}
                        className="w-full py-2 px-4 bg-gray-500 text-white rounded hover:bg-gray-600 disabled:opacity-50"
                    >
                        👁️ Vista Previa
                    </button>

                    <button
                        onClick={executeScan}
                        disabled={!selectedScanner || scanning}
                        className="w-full py-3 px-4 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50 font-semibold"
                    >
                        {scanning ? '⏳ Escaneando...' : '📄 Escanear'}
                    </button>

                    <button
                        onClick={batchScan}
                        disabled={!selectedScanner || scanning}
                        className="w-full py-2 px-4 bg-purple-500 text-white rounded hover:bg-purple-600 disabled:opacity-50"
                    >
                        📚 Escaneo por lotes
                    </button>
                </div>
            </div>

            {/* Área de preview y resultados */}
            <div className="flex-1 p-6 overflow-y-auto">
                <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h3 className="text-lg font-semibold mb-4">Vista Previa</h3>
                    <div className="flex items-center justify-center bg-gray-100 rounded min-h-[400px]">
                        {preview ? (
                            <img src={`/storage/${preview}`} alt="Preview" className="max-w-full max-h-[600px]" />
                        ) : (
                            <p className="text-gray-400">Sin vista previa</p>
                        )}
                    </div>
                </div>

                {/* Documentos escaneados */}
                {scannedFiles.length > 0 && (
                    <div className="bg-white rounded-lg shadow-lg p-6">
                        <h3 className="text-lg font-semibold mb-4">
                            Documentos Escaneados ({scannedFiles.length})
                        </h3>
                        <div className="grid grid-cols-3 gap-4">
                            {scannedFiles.map((file, index) => (
                                <div key={index} className="border rounded p-3 hover:shadow-lg transition">
                                    <div className="text-sm font-medium mb-2">{file.filename}</div>
                                    <div className="text-xs text-gray-500">
                                        <div>DPI: {file.dpi}</div>
                                        <div>Tamaño: {(file.size / 1024).toFixed(2)} KB</div>
                                        <div>Formato: {file.format.toUpperCase()}</div>
                                    </div>
                                    <button className="mt-2 text-xs text-blue-600 hover:text-blue-800">
                                        Ver/Descargar
                                    </button>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default ScannerControl;
