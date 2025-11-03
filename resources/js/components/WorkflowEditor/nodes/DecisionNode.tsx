import React from 'react';
import { Handle, Position } from 'reactflow';

/**
 * Nodo de Decisión (Condicional)
 */
const DecisionNode: React.FC<any> = ({ data, selected }) => {
    return (
        <div className="relative">
            {/* Handle de entrada */}
            <Handle
                type="target"
                position={Position.Top}
                className="w-3 h-3 bg-yellow-500 border-2 border-white"
            />
            
            <div className={`bg-yellow-400 text-gray-800 p-4 min-w-[150px] text-center shadow-lg transition-all
                transform rotate-45 ${selected ? 'ring-2 ring-yellow-600' : ''}`}
                style={{ width: '120px', height: '120px' }}
            >
                <div className="transform -rotate-45 flex flex-col items-center justify-center h-full">
                    <span className="text-2xl mb-1">◆</span>
                    <div className="font-bold text-sm">{data.label || 'Decisión'}</div>
                    {data.config?.condition && (
                        <div className="text-xs mt-1 opacity-75">
                            IF/THEN
                        </div>
                    )}
                </div>
            </div>
            
            {/* Handles de salida (múltiples direcciones) */}
            <Handle
                type="source"
                position={Position.Right}
                id="true"
                className="w-3 h-3 bg-green-500 border-2 border-white"
                style={{ top: '50%', right: '-6px' }}
            />
            <Handle
                type="source"
                position={Position.Left}
                id="false"
                className="w-3 h-3 bg-red-500 border-2 border-white"
                style={{ top: '50%', left: '-6px' }}
            />
            <Handle
                type="source"
                position={Position.Bottom}
                id="default"
                className="w-3 h-3 bg-gray-500 border-2 border-white"
            />
        </div>
    );
};

export default DecisionNode;
