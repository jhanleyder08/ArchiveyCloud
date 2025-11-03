import React from 'react';
import { Handle, Position } from 'reactflow';

const TimerNode: React.FC<any> = ({ data, selected }) => (
    <div className={`bg-orange-500 text-white rounded-lg p-4 shadow-lg border-2 min-w-[160px] ${
        selected ? 'border-orange-700 ring-2 ring-orange-300' : 'border-orange-600'
    }`}>
        <Handle type="target" position={Position.Top} className="w-3 h-3 bg-white border-2 border-orange-600" />
        <div className="text-center">
            <span className="text-2xl block mb-2">⏱️</span>
            <div className="font-bold">{data.label || 'Temporizador'}</div>
            {data.config?.delay_minutes && (
                <div className="text-xs opacity-80 mt-1">
                    Espera: {data.config.delay_minutes} min
                </div>
            )}
        </div>
        <Handle type="source" position={Position.Bottom} className="w-3 h-3 bg-white border-2 border-orange-600" />
    </div>
);

export default TimerNode;
