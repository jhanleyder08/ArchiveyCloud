import React, { useState, useRef } from 'react';

/**
 * Editor WYSIWYG de Plantillas de Documentos
 * Permite crear y editar plantillas HTML con variables dinámicas
 */
const PlantillaEditorWYSIWYG: React.FC = () => {
    const [nombre, setNombre] = useState('');
    const [descripcion, setDescripcion] = useState('');
    const [contenidoHTML, setContenidoHTML] = useState('');
    const [variables, setVariables] = useState<string[]>([]);
    const editorRef = useRef<HTMLDivElement>(null);

    // Variables disponibles para plantillas
    const variablesDisponibles = [
        { key: '{{nombre}}', descripcion: 'Nombre del usuario/entidad' },
        { key: '{{fecha}}', descripcion: 'Fecha actual' },
        { key: '{{numero_documento}}', descripcion: 'Número del documento' },
        { key: '{{dependencia}}', descripcion: 'Dependencia' },
        { key: '{{cargo}}', descripcion: 'Cargo' },
        { key: '{{ciudad}}', descripcion: 'Ciudad' },
        { key: '{{asunto}}', descripcion: 'Asunto del documento' },
        { key: '{{contenido}}', descripcion: 'Contenido principal' },
        { key: '{{firma}}', descripcion: 'Bloque de firmas' },
    ];

    // Insertar variable en el editor
    const insertarVariable = (variable: string) => {
        if (editorRef.current) {
            const selection = window.getSelection();
            if (selection && selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                const varNode = document.createElement('span');
                varNode.className = 'bg-blue-100 text-blue-800 px-2 py-1 rounded font-mono text-sm';
                varNode.textContent = variable;
                varNode.contentEditable = 'false';
                range.insertNode(varNode);
                range.collapse(false);
            }
            
            if (!variables.includes(variable)) {
                setVariables([...variables, variable]);
            }
        }
    };

    // Aplicar formato
    const aplicarFormato = (comando: string, valor?: string) => {
        document.execCommand(comando, false, valor);
    };

    // Obtener HTML del editor
    const obtenerHTML = (): string => {
        if (editorRef.current) {
            return editorRef.current.innerHTML;
        }
        return '';
    };

    // Guardar plantilla
    const guardarPlantilla = async () => {
        const plantilla = {
            nombre,
            descripcion,
            contenido_html: obtenerHTML(),
            variables: variables,
        };

        try {
            const response = await fetch('/api/plantillas', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(plantilla),
            });

            if (response.ok) {
                alert('Plantilla guardada exitosamente');
            }
        } catch (error) {
            console.error('Error guardando plantilla:', error);
        }
    };

    return (
        <div className="h-screen flex flex-col bg-gray-100">
            {/* Header */}
            <div className="bg-white shadow-sm p-4 flex justify-between items-center">
                <div className="flex-1">
                    <input
                        type="text"
                        value={nombre}
                        onChange={(e) => setNombre(e.target.value)}
                        placeholder="Nombre de la plantilla"
                        className="text-xl font-semibold border-b-2 border-transparent focus:border-blue-500 focus:outline-none w-full"
                    />
                    <input
                        type="text"
                        value={descripcion}
                        onChange={(e) => setDescripcion(e.target.value)}
                        placeholder="Descripción"
                        className="text-sm text-gray-600 mt-1 border-none focus:outline-none w-full"
                    />
                </div>
                <button
                    onClick={guardarPlantilla}
                    className="ml-4 px-6 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                >
                    Guardar
                </button>
            </div>

            <div className="flex flex-1 overflow-hidden">
                {/* Toolbar */}
                <div className="w-20 bg-white border-r p-2 flex flex-col gap-2">
                    <button
                        onClick={() => aplicarFormato('bold')}
                        className="p-2 hover:bg-gray-100 rounded"
                        title="Negrita"
                    >
                        <span className="font-bold">B</span>
                    </button>
                    <button
                        onClick={() => aplicarFormato('italic')}
                        className="p-2 hover:bg-gray-100 rounded"
                        title="Cursiva"
                    >
                        <span className="italic">I</span>
                    </button>
                    <button
                        onClick={() => aplicarFormato('underline')}
                        className="p-2 hover:bg-gray-100 rounded"
                        title="Subrayado"
                    >
                        <span className="underline">U</span>
                    </button>
                    <div className="border-t my-2"></div>
                    <button
                        onClick={() => aplicarFormato('justifyLeft')}
                        className="p-2 hover:bg-gray-100 rounded"
                        title="Alinear izquierda"
                    >
                        ☰
                    </button>
                    <button
                        onClick={() => aplicarFormato('justifyCenter')}
                        className="p-2 hover:bg-gray-100 rounded"
                        title="Centrar"
                    >
                        ☷
                    </button>
                    <button
                        onClick={() => aplicarFormato('justifyRight')}
                        className="p-2 hover:bg-gray-100 rounded"
                        title="Alinear derecha"
                    >
                        ☰
                    </button>
                    <div className="border-t my-2"></div>
                    <button
                        onClick={() => aplicarFormato('insertUnorderedList')}
                        className="p-2 hover:bg-gray-100 rounded"
                        title="Lista"
                    >
                        •
                    </button>
                    <button
                        onClick={() => aplicarFormato('insertOrderedList')}
                        className="p-2 hover:bg-gray-100 rounded"
                        title="Lista numerada"
                    >
                        1.
                    </button>
                </div>

                {/* Panel de variables */}
                <div className="w-64 bg-white border-r p-4 overflow-y-auto">
                    <h3 className="font-semibold mb-4">Variables Disponibles</h3>
                    <div className="space-y-2">
                        {variablesDisponibles.map((v) => (
                            <div
                                key={v.key}
                                onClick={() => insertarVariable(v.key)}
                                className="p-2 bg-gray-50 hover:bg-blue-50 rounded cursor-pointer border border-gray-200"
                            >
                                <div className="font-mono text-sm text-blue-600">{v.key}</div>
                                <div className="text-xs text-gray-500 mt-1">{v.descripcion}</div>
                            </div>
                        ))}
                    </div>

                    {variables.length > 0 && (
                        <div className="mt-6">
                            <h4 className="font-semibold text-sm mb-2">Variables en uso:</h4>
                            <div className="flex flex-wrap gap-1">
                                {variables.map((v) => (
                                    <span
                                        key={v}
                                        className="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded"
                                    >
                                        {v}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                {/* Editor */}
                <div className="flex-1 bg-white p-8 overflow-y-auto">
                    <div
                        ref={editorRef}
                        contentEditable
                        className="min-h-[800px] p-8 bg-white shadow-lg mx-auto max-w-4xl border focus:outline-none focus:ring-2 focus:ring-blue-500"
                        style={{
                            lineHeight: '1.6',
                            fontSize: '14px',
                        }}
                        placeholder="Comienza a escribir tu plantilla aquí..."
                    />
                </div>

                {/* Preview */}
                <div className="w-80 bg-gray-50 p-4 overflow-y-auto border-l">
                    <h3 className="font-semibold mb-4">Vista Previa</h3>
                    <div className="bg-white p-4 rounded shadow text-sm">
                        <div
                            dangerouslySetInnerHTML={{ __html: obtenerHTML() }}
                            className="prose prose-sm max-w-none"
                        />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default PlantillaEditorWYSIWYG;
