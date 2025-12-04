import React from 'react';
import { Handle, Position } from 'reactflow';

/**
 * Nodo de Inicio del Workflow
 */
const StartNode: React.FC<any> = ({ data }) => {
    return (
        <div className="bg-green-500 text-white rounded-full p-4 shadow-lg border-4 border-green-600 min-w-[120px] text-center">
            <div className="flex items-center justify-center gap-2">
                <span className="text-2xl">▶️</span>
                <div>
                    <div className="font-bold text-sm">{data.label || 'Inicio'}</div>
                    <div className="text-xs opacity-80">Punto de partida</div>
                </div>
            </div>
            
            {/* Handle de salida */}
            <Handle
                type="source"
                position={Position.Bottom}
                className="w-3 h-3 bg-white border-2 border-green-600"
            />
        </div>
    );
};

export default StartNode;
