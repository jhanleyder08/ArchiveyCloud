import React from 'react';
import { Handle, Position } from 'reactflow';

const EndNode: React.FC<any> = ({ data }) => (
    <div className="bg-red-500 text-white rounded-full p-4 shadow-lg border-4 border-red-600 min-w-[120px] text-center">
        <Handle type="target" position={Position.Top} className="w-3 h-3 bg-white border-2 border-red-600" />
        <div className="flex items-center justify-center gap-2">
            <span className="text-2xl">⏹️</span>
            <div>
                <div className="font-bold text-sm">{data.label || 'Fin'}</div>
                <div className="text-xs opacity-80">Finalización</div>
            </div>
        </div>
    </div>
);

export default EndNode;
