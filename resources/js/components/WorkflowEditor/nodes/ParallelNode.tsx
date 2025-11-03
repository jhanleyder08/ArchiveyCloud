import React from 'react';
import { Handle, Position } from 'reactflow';

const ParallelNode: React.FC<any> = ({ data, selected }) => (
    <div className={`bg-purple-500 text-white rounded-lg p-4 shadow-lg border-2 min-w-[180px] ${
        selected ? 'border-purple-700 ring-2 ring-purple-300' : 'border-purple-600'
    }`}>
        <Handle type="target" position={Position.Top} className="w-3 h-3 bg-white border-2 border-purple-600" />
        <div className="text-center">
            <span className="text-2xl block mb-2">⚡</span>
            <div className="font-bold">{data.label || 'Paralelo'}</div>
            <div className="text-xs opacity-80 mt-1">
                {data.config?.wait_for_all ? 'AND Gateway' : 'OR Gateway'}
            </div>
        </div>
        <Handle type="source" position={Position.Bottom} id="out1" style={{ left: '33%' }} className="w-3 h-3 bg-white border-2 border-purple-600" />
        <Handle type="source" position={Position.Bottom} id="out2" style={{ left: '66%' }} className="w-3 h-3 bg-white border-2 border-purple-600" />
    </div>
);

export default ParallelNode;
