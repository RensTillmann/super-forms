/**
 * PropertiesPanel Component
 * Right sidebar for configuring selected node
 */

import { X, Settings, Code, Power, Mail, ExternalLink } from 'lucide-react';
import { getNodeType } from '../data/superFormsNodeTypes';
import type { WorkflowNode } from '../types/workflow.types';

interface PropertiesPanelProps {
  selectedNode: WorkflowNode | null;
  onUpdateNode: (nodeId: string, updates: Partial<WorkflowNode>) => void;
  onClose: () => void;
  onOpenEmailBuilder?: (node: WorkflowNode) => void;
}

export function PropertiesPanel({
  selectedNode,
  onUpdateNode,
  onClose,
  onOpenEmailBuilder,
}: PropertiesPanelProps) {
  if (!selectedNode) {
    return (
      <div className="properties-panel flex flex-col h-full bg-white border-l border-gray-200">
        <div className="flex-1 flex items-center justify-center p-8 text-center">
          <div>
            <Settings className="w-12 h-12 text-gray-300 mx-auto mb-4" />
            <h3 className="text-sm font-medium text-gray-900 mb-1">
              No Node Selected
            </h3>
            <p className="text-xs text-gray-500">
              Click on a node to view its properties
            </p>
          </div>
        </div>
      </div>
    );
  }

  const nodeType = getNodeType(selectedNode.type);

  if (!nodeType) {
    return (
      <div className="properties-panel flex flex-col h-full bg-white border-l border-gray-200">
        <div className="flex-1 flex items-center justify-center p-8 text-center">
          <div>
            <p className="text-sm text-red-600">Unknown node type: {selectedNode.type}</p>
          </div>
        </div>
      </div>
    );
  }

  const Icon = nodeType.icon;

  /**
   * Update node configuration
   */
  const updateConfig = (key: string, value: any) => {
    onUpdateNode(selectedNode.id, {
      config: {
        ...selectedNode.config,
        [key]: value,
      },
    });
  };

  /**
   * Render config field based on type
   */
  const renderConfigField = (key: string, value: any) => {
    // Special handling for specific fields
    if (key === 'formId') {
      return (
        <div key={key} className="space-y-2">
          <label className="block text-sm font-medium text-gray-700">
            Form
          </label>
          <select
            value={value || ''}
            onChange={(e) => updateConfig(key, e.target.value ? parseInt(e.target.value) : null)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">All Forms</option>
            {window.sfuiData?.forms?.map((form) => (
              <option key={form.id} value={form.id}>
                {form.title}
              </option>
            ))}
          </select>
        </div>
      );
    }

    if (key === 'method' && selectedNode.type === 'http_request') {
      return (
        <div key={key} className="space-y-2">
          <label className="block text-sm font-medium text-gray-700">
            HTTP Method
          </label>
          <select
            value={value || 'POST'}
            onChange={(e) => updateConfig(key, e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="GET">GET</option>
            <option value="POST">POST</option>
            <option value="PUT">PUT</option>
            <option value="PATCH">PATCH</option>
            <option value="DELETE">DELETE</option>
          </select>
        </div>
      );
    }

    if (key === 'bodyType' && selectedNode.type === 'send_email') {
      return (
        <div key={key} className="space-y-2">
          <label className="block text-sm font-medium text-gray-700">
            Body Type
          </label>
          <select
            value={value || 'html'}
            onChange={(e) => updateConfig(key, e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="html">HTML</option>
            <option value="text">Plain Text</option>
          </select>
        </div>
      );
    }

    // Text area for long text fields
    if (key === 'body' || key === 'message' || key === 'payload' || key === 'postContent') {
      return (
        <div key={key} className="space-y-2">
          <label className="block text-sm font-medium text-gray-700 capitalize">
            {key.replace(/([A-Z])/g, ' $1').trim()}
          </label>
          <textarea
            value={value || ''}
            onChange={(e) => updateConfig(key, e.target.value)}
            rows={6}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono"
            placeholder="Supports {tags} for dynamic values"
          />
          <p className="text-xs text-gray-500">
            💡 Use {'{field_name}'} to insert form field values
          </p>
        </div>
      );
    }

    // Number input
    if (typeof value === 'number' || key === 'duration' || key === 'quantity') {
      return (
        <div key={key} className="space-y-2">
          <label className="block text-sm font-medium text-gray-700 capitalize">
            {key.replace(/([A-Z])/g, ' $1').trim()}
          </label>
          <input
            type="number"
            value={value || ''}
            onChange={(e) => updateConfig(key, parseFloat(e.target.value))}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
      );
    }

    // Boolean checkbox
    if (typeof value === 'boolean') {
      return (
        <div key={key} className="flex items-center gap-3">
          <input
            type="checkbox"
            id={`config-${key}`}
            checked={value}
            onChange={(e) => updateConfig(key, e.target.checked)}
            className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
          />
          <label htmlFor={`config-${key}`} className="text-sm font-medium text-gray-700 capitalize">
            {key.replace(/([A-Z])/g, ' $1').trim()}
          </label>
        </div>
      );
    }

    // Array (JSON editor)
    if (Array.isArray(value)) {
      return (
        <div key={key} className="space-y-2">
          <label className="block text-sm font-medium text-gray-700 capitalize">
            {key.replace(/([A-Z])/g, ' $1').trim()}
          </label>
          <textarea
            value={JSON.stringify(value, null, 2)}
            onChange={(e) => {
              try {
                const parsed = JSON.parse(e.target.value);
                updateConfig(key, parsed);
              } catch (err) {
                // Invalid JSON, don't update
              }
            }}
            rows={4}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono"
          />
        </div>
      );
    }

    // Object (JSON editor)
    if (typeof value === 'object' && value !== null) {
      return (
        <div key={key} className="space-y-2">
          <label className="block text-sm font-medium text-gray-700 capitalize">
            {key.replace(/([A-Z])/g, ' $1').trim()}
          </label>
          <textarea
            value={JSON.stringify(value, null, 2)}
            onChange={(e) => {
              try {
                const parsed = JSON.parse(e.target.value);
                updateConfig(key, parsed);
              } catch (err) {
                // Invalid JSON, don't update
              }
            }}
            rows={4}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono"
          />
        </div>
      );
    }

    // Default: text input
    return (
      <div key={key} className="space-y-2">
        <label className="block text-sm font-medium text-gray-700 capitalize">
          {key.replace(/([A-Z])/g, ' $1').trim()}
        </label>
        <input
          type="text"
          value={value || ''}
          onChange={(e) => updateConfig(key, e.target.value)}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="Supports {tags}"
        />
      </div>
    );
  };

  return (
    <div className="properties-panel flex flex-col h-full bg-white border-l border-gray-200">
      {/* Header */}
      <div className="flex items-center justify-between p-4 border-b border-gray-200">
        <div className="flex items-center gap-3">
          <div
            className="w-10 h-10 rounded-lg flex items-center justify-center"
            style={{ backgroundColor: `${nodeType.color}20` }}
          >
            <Icon className="w-5 h-5" style={{ color: nodeType.color }} />
          </div>
          <div>
            <h3 className="text-sm font-semibold text-gray-900">
              {nodeType.name}
            </h3>
            <p className="text-xs text-gray-500 capitalize">
              {nodeType.category}
            </p>
          </div>
        </div>
        <button
          onClick={onClose}
          className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <X className="w-4 h-4 text-gray-500" />
        </button>
      </div>

      {/* Description */}
      <div className="p-4 bg-gray-50 border-b border-gray-200">
        <p className="text-sm text-gray-600">{nodeType.description}</p>
      </div>

      {/* Enable/Disable Toggle */}
      <div className="p-4 border-b border-gray-200">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Power className="w-4 h-4 text-gray-500" />
            <span className="text-sm font-medium text-gray-700">Node Enabled</span>
          </div>
          <button
            onClick={() => onUpdateNode(selectedNode.id, {
              config: {
                ...selectedNode.config,
                _enabled: selectedNode.config._enabled === false,
              },
            })}
            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
              selectedNode.config._enabled !== false
                ? 'bg-green-500'
                : 'bg-gray-300'
            }`}
          >
            <span
              className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm ${
                selectedNode.config._enabled !== false
                  ? 'translate-x-6'
                  : 'translate-x-1'
              }`}
            />
          </button>
        </div>
        <p className="text-xs text-gray-500 mt-1">
          {selectedNode.config._enabled !== false
            ? 'This node will execute when triggered'
            : 'This node is paused and will be skipped'}
        </p>
      </div>

      {/* Open Email Builder Button (for send_email nodes) */}
      {selectedNode.type === 'send_email' && onOpenEmailBuilder && (
        <div className="p-4 border-b border-gray-200">
          <button
            onClick={() => onOpenEmailBuilder(selectedNode)}
            className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <Mail className="w-4 h-4" />
            <span className="font-medium">Open Email Builder</span>
            <ExternalLink className="w-3 h-3 ml-1" />
          </button>
          <p className="text-xs text-gray-500 mt-2 text-center">
            Design your email template visually
          </p>
        </div>
      )}

      {/* Configuration Fields */}
      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        {Object.keys(selectedNode.config).filter(k => !k.startsWith('_')).length > 0 ? (
          Object.entries(selectedNode.config)
            .filter(([key]) => !key.startsWith('_'))
            .map(([key, value]) => renderConfigField(key, value))
        ) : (
          <div className="text-center py-8 text-gray-500">
            <Code className="w-8 h-8 text-gray-300 mx-auto mb-2" />
            <p className="text-sm">No configuration needed</p>
            <p className="text-xs mt-1">This node works out of the box</p>
          </div>
        )}
      </div>

      {/* Footer - Node Info */}
      <div className="p-4 border-t border-gray-200 bg-gray-50 space-y-2">
        <div className="flex items-center justify-between text-xs text-gray-600">
          <span>Node ID:</span>
          <code className="font-mono bg-white px-2 py-1 rounded border border-gray-200">
            {selectedNode.id}
          </code>
        </div>
        <div className="flex items-center justify-between text-xs text-gray-600">
          <span>Type:</span>
          <code className="font-mono bg-white px-2 py-1 rounded border border-gray-200">
            {selectedNode.type}
          </code>
        </div>
      </div>
    </div>
  );
}
