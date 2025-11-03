import React, { useState, useCallback } from 'react';
import ReactFlow, {
    Node,
    Edge,
    addEdge,
    Background,
    Controls,
    MiniMap,
    Connection,
    useNodesState,
    useEdgesState,
    NodeTypes,
} from 'reactflow';
import 'reactflow/dist/style.css';

// Nodos personalizados
import StartNode from './nodes/StartNode';
import TaskNode from './nodes/TaskNode';
import DecisionNode from './nodes/DecisionNode';
import EndNode from './nodes/EndNode';
import ParallelNode from './nodes/ParallelNode';
import TimerNode from './nodes/TimerNode';

/**
 * Editor Visual de Workflows con React Flow
 * Permite crear workflows mediante drag & drop
 */
const WorkflowEditor: React.FC = () => {
    const [nodes, setNodes, onNodesChange] = useNodesState([]);
    const [edges, setEdges, onEdgesChange] = useEdgesState([]);
    const [selectedNode, setSelectedNode] = useState<Node | null>(null);
    const [workflowName, setWorkflowName] = useState('Nuevo Workflow');

    // Tipos de nodos disponibles
    const nodeTypes: NodeTypes = {
        start: StartNode,
        task: TaskNode,
        decision: DecisionNode,
        end: EndNode,
        parallel: ParallelNode,
        timer: TimerNode,
    };

    // Conectar nodos
    const onConnect = useCallback(
        (params: Connection) => setEdges((eds) => addEdge(params, eds)),
        [setEdges]
    );

    // Agregar nuevo nodo
    const addNode = (type: string) => {
        const newNode: Node = {
            id: `node_${Date.now()}`,
            type,
            position: { x: Math.random() * 400, y: Math.random() * 400 },
            data: { 
                label: getNodeLabel(type),
                config: getDefaultNodeConfig(type),
            },
        };

        setNodes((nds) => [...nds, newNode]);
    };

    // Obtener etiqueta del nodo
    const getNodeLabel = (type: string): string => {
        const labels: Record<string, string> = {
            start: 'Inicio',
            task: 'Tarea',
            decision: 'Decisión',
            end: 'Fin',
            parallel: 'Paralelo',
            timer: 'Temporizador',
        };
        return labels[type] || 'Nodo';
    };

    // Configuración por defecto del nodo
    const getDefaultNodeConfig = (type: string): any => {
        const configs: Record<string, any> = {
            start: {},
            task: {
                asignado_type: 'usuario',
                asignado_id: null,
                dias_vencimiento: 5,
                requiere_aprobacion: false,
            },
            decision: {
                condition: '',
                true_path: null,
                false_path: null,
            },
            end: {
                auto_close: true,
            },
            parallel: {
                wait_for_all: true,
                branches: [],
            },
            timer: {
                delay_minutes: 60,
            },
        };
        return configs[type] || {};
    };

    // Eliminar nodo
    const deleteNode = (nodeId: string) => {
        setNodes((nds) => nds.filter((n) => n.id !== nodeId));
        setEdges((eds) => eds.filter((e) => e.source !== nodeId && e.target !== nodeId));
    };

    // Guardar workflow
    const saveWorkflow = async () => {
        const workflow = {
            nombre: workflowName,
            nodes: nodes.map(node => ({
                id: node.id,
                type: node.type,
                position: node.position,
                data: node.data,
            })),
            edges: edges.map(edge => ({
                id: edge.id,
                source: edge.source,
                target: edge.target,
            })),
        };

        console.log('Guardando workflow:', workflow);

        try {
            const response = await fetch('/api/workflows', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(workflow),
            });

            if (response.ok) {
                alert('Workflow guardado exitosamente');
            }
        } catch (error) {
            console.error('Error guardando workflow:', error);
        }
    };

    // Validar workflow
    const validateWorkflow = (): string[] => {
        const errors: string[] = [];

        // Debe tener al menos un nodo de inicio
        const startNodes = nodes.filter(n => n.type === 'start');
        if (startNodes.length === 0) {
            errors.push('El workflow debe tener al menos un nodo de inicio');
        }
        if (startNodes.length > 1) {
            errors.push('El workflow solo puede tener un nodo de inicio');
        }

        // Debe tener al menos un nodo de fin
        const endNodes = nodes.filter(n => n.type === 'end');
        if (endNodes.length === 0) {
            errors.push('El workflow debe tener al menos un nodo de fin');
        }

        // Todos los nodos deben estar conectados
        const connectedNodes = new Set<string>();
        edges.forEach(edge => {
            connectedNodes.add(edge.source);
            connectedNodes.add(edge.target);
        });

        const disconnectedNodes = nodes.filter(n => !connectedNodes.has(n.id) && n.type !== 'start');
        if (disconnectedNodes.length > 0) {
            errors.push(`Hay ${disconnectedNodes.length} nodo(s) sin conectar`);
        }

        return errors;
    };

    // Exportar a JSON
    const exportToJSON = () => {
        const workflow = {
            nombre: workflowName,
            nodes,
            edges,
        };

        const blob = new Blob([JSON.stringify(workflow, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${workflowName.replace(/\s+/g, '_')}.json`;
        a.click();
    };

    return (
        <div className="workflow-editor h-screen flex flex-col">
            {/* Header */}
            <div className="bg-white shadow-sm p-4 flex justify-between items-center">
                <div className="flex items-center gap-4">
                    <input
                        type="text"
                        value={workflowName}
                        onChange={(e) => setWorkflowName(e.target.value)}
                        className="text-xl font-semibold border-none focus:outline-none"
                    />
                </div>
                <div className="flex gap-2">
                    <button
                        onClick={() => {
                            const errors = validateWorkflow();
                            if (errors.length > 0) {
                                alert('Errores de validación:\n' + errors.join('\n'));
                            } else {
                                alert('Workflow válido ✓');
                            }
                        }}
                        className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                    >
                        Validar
                    </button>
                    <button
                        onClick={exportToJSON}
                        className="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                    >
                        Exportar
                    </button>
                    <button
                        onClick={saveWorkflow}
                        className="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
                    >
                        Guardar
                    </button>
                </div>
            </div>

            <div className="flex flex-1">
                {/* Paleta de nodos */}
                <div className="w-64 bg-gray-50 p-4 border-r overflow-y-auto">
                    <h3 className="font-semibold mb-4">Componentes</h3>
                    <div className="space-y-2">
                        <NodePaletteItem 
                            label="Inicio" 
                            icon="▶️" 
                            onClick={() => addNode('start')} 
                        />
                        <NodePaletteItem 
                            label="Tarea" 
                            icon="📋" 
                            onClick={() => addNode('task')} 
                        />
                        <NodePaletteItem 
                            label="Decisión" 
                            icon="◆" 
                            onClick={() => addNode('decision')} 
                        />
                        <NodePaletteItem 
                            label="Paralelo" 
                            icon="⚡" 
                            onClick={() => addNode('parallel')} 
                        />
                        <NodePaletteItem 
                            label="Temporizador" 
                            icon="⏱️" 
                            onClick={() => addNode('timer')} 
                        />
                        <NodePaletteItem 
                            label="Fin" 
                            icon="⏹️" 
                            onClick={() => addNode('end')} 
                        />
                    </div>

                    <div className="mt-8">
                        <h3 className="font-semibold mb-2">Estadísticas</h3>
                        <div className="text-sm text-gray-600">
                            <p>Nodos: {nodes.length}</p>
                            <p>Conexiones: {edges.length}</p>
                        </div>
                    </div>
                </div>

                {/* Canvas */}
                <div className="flex-1">
                    <ReactFlow
                        nodes={nodes}
                        edges={edges}
                        onNodesChange={onNodesChange}
                        onEdgesChange={onEdgesChange}
                        onConnect={onConnect}
                        nodeTypes={nodeTypes}
                        onNodeClick={(_, node) => setSelectedNode(node)}
                        fitView
                    >
                        <Background />
                        <Controls />
                        <MiniMap />
                    </ReactFlow>
                </div>

                {/* Panel de propiedades */}
                {selectedNode && (
                    <div className="w-80 bg-white p-4 border-l overflow-y-auto">
                        <div className="flex justify-between items-center mb-4">
                            <h3 className="font-semibold">Propiedades</h3>
                            <button
                                onClick={() => deleteNode(selectedNode.id)}
                                className="text-red-500 hover:text-red-700"
                            >
                                🗑️ Eliminar
                            </button>
                        </div>

                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium mb-1">
                                    Nombre
                                </label>
                                <input
                                    type="text"
                                    value={selectedNode.data.label}
                                    onChange={(e) => {
                                        setNodes((nds) =>
                                            nds.map((n) =>
                                                n.id === selectedNode.id
                                                    ? { ...n, data: { ...n.data, label: e.target.value } }
                                                    : n
                                            )
                                        );
                                    }}
                                    className="w-full border rounded px-3 py-2"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium mb-1">
                                    Tipo
                                </label>
                                <p className="text-gray-600">{selectedNode.type}</p>
                            </div>

                            {/* Configuración específica según tipo */}
                            {selectedNode.type === 'task' && (
                                <TaskNodeProperties 
                                    node={selectedNode} 
                                    updateNode={(updates) => {
                                        setNodes((nds) =>
                                            nds.map((n) =>
                                                n.id === selectedNode.id
                                                    ? { ...n, data: { ...n.data, config: { ...n.data.config, ...updates } } }
                                                    : n
                                            )
                                        );
                                    }}
                                />
                            )}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

// Componente de paleta de nodos
const NodePaletteItem: React.FC<{ label: string; icon: string; onClick: () => void }> = ({ 
    label, 
    icon, 
    onClick 
}) => (
    <button
        onClick={onClick}
        className="w-full p-3 bg-white border rounded hover:bg-gray-50 flex items-center gap-2"
    >
        <span className="text-2xl">{icon}</span>
        <span>{label}</span>
    </button>
);

// Propiedades específicas de nodo Task
const TaskNodeProperties: React.FC<{ node: Node; updateNode: (updates: any) => void }> = ({ 
    node, 
    updateNode 
}) => {
    const config = node.data.config || {};

    return (
        <>
            <div>
                <label className="block text-sm font-medium mb-1">
                    Asignar a
                </label>
                <select
                    value={config.asignado_type || 'usuario'}
                    onChange={(e) => updateNode({ asignado_type: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                >
                    <option value="usuario">Usuario</option>
                    <option value="rol">Rol</option>
                </select>
            </div>

            <div>
                <label className="block text-sm font-medium mb-1">
                    Días de vencimiento
                </label>
                <input
                    type="number"
                    value={config.dias_vencimiento || 5}
                    onChange={(e) => updateNode({ dias_vencimiento: parseInt(e.target.value) })}
                    className="w-full border rounded px-3 py-2"
                    min="1"
                    max="365"
                />
            </div>

            <div className="flex items-center">
                <input
                    type="checkbox"
                    checked={config.requiere_aprobacion || false}
                    onChange={(e) => updateNode({ requiere_aprobacion: e.target.checked })}
                    className="mr-2"
                />
                <label className="text-sm font-medium">
                    Requiere aprobación
                </label>
            </div>
        </>
    );
};

export default WorkflowEditor;
