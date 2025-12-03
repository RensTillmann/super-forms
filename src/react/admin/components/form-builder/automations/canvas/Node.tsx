/**
 * Node Component
 * Individual workflow node rendering
 * Displays icon, name, and connection ports
 */

import { memo, MouseEvent } from 'react';
import type { WorkflowNode } from '../types/workflow.types';
import { SUPER_FORMS_NODE_TYPES } from '../data/superFormsNodeTypes';

interface NodeProps {
  node: WorkflowNode;
  selected: boolean;
  isDragging?: boolean;
  isConnecting?: boolean;
  connectionStart?: { nodeId: string; outputName: string } | null;
  onConnectionStart?: (nodeId: string, outputName: string) => void;
  onConnectionEnd?: (nodeId: string, inputName: string) => void;
}

export const Node = memo(function Node({
  node,
  selected,
  isDragging = false,
  isConnecting = false,
  connectionStart = null,
  onConnectionStart,
  onConnectionEnd,
}: NodeProps) {
  // Get node type definition
  const nodeType = SUPER_FORMS_NODE_TYPES[node.type];

  if (!nodeType) {
    return (
      <div
        data-node-id={node.id}
        className="absolute bg-red-100 border-2 border-red-500 rounded-lg p-4 z-30"
        style={{
          left: node.position.x,
          top: node.position.y,
          zIndex: node.zIndex,
        }}
      >
        <p className="text-sm text-red-600">Unknown node: {node.type}</p>
      </div>
    );
  }

  const Icon = nodeType.icon;
  const isEnabled = node.config._enabled !== false;

  const categoryColor = {
    event: 'border-green-500 bg-green-50',
    action: 'border-blue-500 bg-blue-50',
    condition: 'border-orange-500 bg-orange-50',
  }[nodeType.category];

  const selectedStyle = selected
    ? 'ring-4 ring-blue-300 shadow-xl scale-105'
    : 'shadow-md hover:shadow-lg hover:scale-[1.02]';

  // Conditional transitions - disabled during drag for snappy feel
  const transitionStyle = isDragging ? '' : 'transition-all duration-200';

  // Check if this node can accept a connection (has inputs and isn't the source node)
  const canAcceptConnection = isConnecting &&
    connectionStart?.nodeId !== node.id &&
    nodeType.inputs.length > 0;

  /**
   * Handle mouse up on node body - auto-connect to first input
   */
  const handleNodeMouseUp = (e: MouseEvent) => {
    if (canAcceptConnection && onConnectionEnd) {
      e.preventDefault();
      e.stopPropagation();
      // Connect to the first available input
      onConnectionEnd(node.id, nodeType.inputs[0]);
    }
  };

  return (
    <div
      data-node-id={node.id}
      className={`absolute rounded-lg cursor-move select-none z-30 ${categoryColor} ${selectedStyle} ${transitionStyle}`}
      style={{
        left: node.position.x,
        top: node.position.y,
        width: 200,
        zIndex: node.zIndex,
        border: canAcceptConnection ? '3px solid #3b82f6' : '2px solid',
        boxShadow: canAcceptConnection ? '0 0 20px rgba(59, 130, 246, 0.4)' : undefined,
      }}
      onMouseUp={handleNodeMouseUp}
    >
      {/* Node Header */}
      <div className="flex items-center gap-2 p-3 border-b border-current/20">
        <Icon className="w-5 h-5 flex-shrink-0" style={{ color: nodeType.color }} />
        <span className="font-medium text-sm truncate">{nodeType.name}</span>
      </div>

      {/* Node Body - Config preview */}
      <div className="p-3 text-xs text-gray-600 space-y-1">
        {Object.keys(node.config).length > 0 ? (
          Object.entries(node.config).slice(0, 2).map(([key, value]) => (
            <div key={key} className="truncate">
              <span className="font-medium">{key}:</span>{' '}
              <span className="text-gray-500">
                {typeof value === 'string' && value.length > 20
                  ? value.substring(0, 20) + '...'
                  : String(value)}
              </span>
            </div>
          ))
        ) : (
          <div className="text-gray-400 italic">Not configured</div>
        )}
      </div>

      {/* Input Ports */}
      {nodeType.inputs.length > 0 && (
        <div className="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1/2" style={{ zIndex: 100 }}>
          {nodeType.inputs.map((input, idx) => (
            <div key={input} style={{ marginTop: idx * 20 }} className="relative">
              {/* Visible port dot */}
              <div
                data-port-type="input"
                data-port-name={input}
                className="w-4 h-4 rounded-full bg-gray-400 border-2 border-white shadow-sm transition-all duration-150"
                style={{
                  transform: isConnecting && connectionStart?.nodeId !== node.id ? 'scale(1.3)' : 'scale(1)',
                  backgroundColor: isConnecting && connectionStart?.nodeId !== node.id ? '#3b82f6' : '#9ca3af',
                  boxShadow: isConnecting && connectionStart?.nodeId !== node.id ? '0 0 12px rgba(59, 130, 246, 0.6)' : undefined,
                }}
                title={`Input: ${input}`}
              />

              {/* Invisible hover area for easier clicking */}
              <div
                className="absolute w-6 h-6 -translate-x-1/2 -translate-y-1/2 cursor-pointer"
                style={{ left: '50%', top: '50%', zIndex: 30 }}
                onMouseDown={(e: MouseEvent) => {
                  e.preventDefault();
                  e.stopPropagation();
                }}
                onMouseUp={(e: MouseEvent) => {
                  e.preventDefault();
                  e.stopPropagation();
                  if (isConnecting && connectionStart?.nodeId !== node.id && onConnectionEnd) {
                    onConnectionEnd(node.id, input);
                  }
                }}
              />
            </div>
          ))}
        </div>
      )}

      {/* Output Ports */}
      {nodeType.outputs.length > 0 && (
        <div className="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2" style={{ zIndex: 100 }}>
          {nodeType.outputs.map((output, idx) => (
            <div
              key={output}
              data-port-type="output"
              data-port-name={output}
              className="w-4 h-4 rounded-full border-2 border-white shadow-sm cursor-pointer hover:scale-130 transition-all duration-150"
              style={{
                backgroundColor: nodeType.color,
                marginTop: idx * 20,
              }}
              title={`Output: ${output}`}
              onMouseDown={(e: MouseEvent) => {
                e.preventDefault();
                e.stopPropagation();
                if (onConnectionStart) {
                  onConnectionStart(node.id, output);
                }
              }}
              onMouseEnter={(e) => {
                const target = e.currentTarget;
                target.style.transform = 'scale(1.3)';
                target.style.boxShadow = `0 0 12px ${nodeType.color}99`;
              }}
              onMouseLeave={(e) => {
                const target = e.currentTarget;
                target.style.transform = 'scale(1)';
                target.style.boxShadow = '';
              }}
            />
          ))}
        </div>
      )}

      {/* Status indicator - shows enabled/disabled state */}
      <div className="absolute top-2 right-2">
        <div
          className={`w-2 h-2 rounded-full ${
            isEnabled ? 'bg-green-500 animate-pulse' : 'bg-gray-400'
          }`}
          title={isEnabled ? 'Enabled' : 'Disabled'}
        />
      </div>

      {/* Disabled overlay */}
      {!isEnabled && (
        <div className="absolute inset-0 bg-gray-200/50 rounded-lg pointer-events-none flex items-center justify-center">
          <span className="text-xs font-medium text-gray-500 bg-white/80 px-2 py-1 rounded">
            Paused
          </span>
        </div>
      )}
    </div>
  );
});
