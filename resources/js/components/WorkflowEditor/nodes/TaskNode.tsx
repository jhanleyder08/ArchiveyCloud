import React from 'react';
import { Handle, Position } from 'reactflow';

/**
 * Nodo de Tarea
 */
const TaskNode: React.FC<any> = ({ data, selected }) => {
    return (
        <div className={`bg-white rounded-lg shadow-lg border-2 p-4 min-w-[200px] transition-all ${
            selected ? 'border-blue-500 ring-2 ring-blue-300' : 'border-gray-300'
        }`}>
            {/* Handle de entrada */}
            <Handle
                type="target"
                position={Position.Top}
                className="w-3 h-3 bg-blue-500 border-2 border-white"
            />
            
            <div className="flex items-start gap-3">
                <span className="text-2xl">📋</span>
                <div className="flex-1">
                    <div className="font-bold text-gray-800">{data.label || 'Tarea'}</div>
                    <div className="text-xs text-gray-500 mt-1">
                        {data.config?.descripcion || 'Tarea del workflow'}
                    </div>
                    
                    {data.config?.asignado_type && (
                        <div className="mt-2 flex gap-2 flex-wrap">
                            <span className="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                {data.config.asignado_type === 'usuario' ? '👤 Usuario' : '👥 Rol'}
                            </span>
                            {data.config?.dias_vencimiento && (
                                <span className="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded">
                                    ⏰ {data.config.dias_vencimiento} días
                                </span>
                            )}
                            {data.config?.requiere_aprobacion && (
                                <span className="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                                    ✓ Aprobación
                                </span>
                            )}
                        </div>
                    )}
                </div>
            </div>
            
            {/* Handle de salida */}
            <Handle
                type="source"
                position={Position.Bottom}
                className="w-3 h-3 bg-blue-500 border-2 border-white"
            />
        </div>
    );
};

export default TaskNode;
