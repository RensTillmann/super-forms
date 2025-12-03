/**
 * ConnectionModal Component
 * Modal for selecting a node to create and connect when dropping a connection on empty canvas
 * Based on ai-automation ConnectionModal
 */

import { useState, useMemo } from 'react';
import { SUPER_FORMS_NODE_TYPES } from '../data/superFormsNodeTypes';
import { X, Search } from 'lucide-react';

interface ConnectionModalProps {
  isOpen: boolean;
  onClose: () => void;
  connectionStart: { nodeId: string; outputName: string } | null;
  position: { x: number; y: number } | null;
  onCreateAndConnect: (nodeType: string, position: { x: number; y: number }) => void;
}

export function ConnectionModal({
  isOpen,
  onClose,
  connectionStart,
  position,
  onCreateAndConnect,
}: ConnectionModalProps) {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState<'all' | 'action' | 'condition'>('all');

  const categories = [
    { id: 'all' as const, name: 'All Nodes', icon: '🔗' },
    { id: 'action' as const, name: 'Actions', icon: '⚡' },
    { id: 'condition' as const, name: 'Conditions', icon: '❓' },
  ];

  // Get compatible nodes (actions and conditions that can receive input)
  const compatibleNodes = useMemo(() => {
    const allNodeTypes = Object.entries(SUPER_FORMS_NODE_TYPES)
      .map(([id, nodeType]) => ({ id, ...nodeType }))
      .filter((nodeType) => {
        // Only show nodes that have inputs (can receive connections)
        if (!nodeType.inputs || nodeType.inputs.length === 0) return false;

        // Filter by category
        if (selectedCategory !== 'all' && nodeType.category !== selectedCategory) {
          return false;
        }

        // Filter by search term
        if (searchTerm) {
          const search = searchTerm.toLowerCase();
          return (
            nodeType.name.toLowerCase().includes(search) ||
            (nodeType.description && nodeType.description.toLowerCase().includes(search))
          );
        }

        return true;
      });

    return allNodeTypes;
  }, [selectedCategory, searchTerm]);

  const handleNodeSelect = (nodeType: { id: string; name: string }) => {
    if (position) {
      onCreateAndConnect(nodeType.id, position);
      onClose();
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Escape') {
      onClose();
    }
  };

  if (!isOpen) return null;

  return (
    <div
      className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      onClick={onClose}
    >
      <div
        className="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-96 max-h-[600px] flex flex-col"
        onClick={(e) => e.stopPropagation()}
        onKeyDown={handleKeyDown}
      >
        {/* Header */}
        <div className="p-4 border-b border-gray-200 dark:border-gray-700">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
              Connect to New Node
            </h3>
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors"
              title="Close"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
          <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Choose a node to create and connect to
          </p>
        </div>

        {/* Search */}
        <div className="p-4 border-b border-gray-200 dark:border-gray-700">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              type="text"
              placeholder="Search nodes..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg pl-10 pr-3 py-2 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
              autoFocus
            />
          </div>
        </div>

        {/* Category filters */}
        <div className="p-4 border-b border-gray-200 dark:border-gray-700">
          <div className="flex gap-2">
            {categories.map((category) => (
              <button
                key={category.id}
                onClick={() => setSelectedCategory(category.id)}
                className={`px-3 py-1 rounded-md text-sm font-medium transition-colors ${
                  selectedCategory === category.id
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                }`}
              >
                {category.icon} {category.name}
              </button>
            ))}
          </div>
        </div>

        {/* Node list */}
        <div className="flex-1 overflow-y-auto p-4">
          <div className="space-y-2">
            {compatibleNodes.map((nodeType) => {
              const Icon = nodeType.icon;
              return (
                <div
                  key={nodeType.id}
                  onClick={() => handleNodeSelect(nodeType)}
                  className="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 cursor-pointer transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-700 hover:shadow-lg border border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500"
                  style={{
                    borderLeftColor: nodeType.color,
                    borderLeftWidth: '4px',
                  }}
                >
                  <div className="flex items-center gap-3">
                    <span className="text-xl flex-shrink-0">
                      <Icon className="w-5 h-5" style={{ color: nodeType.color }} />
                    </span>
                    <div className="flex-1 min-w-0">
                      <div className="text-gray-900 dark:text-white font-medium text-sm">
                        {nodeType.name}
                      </div>
                      {nodeType.description && (
                        <div className="text-gray-600 dark:text-gray-400 text-xs">
                          {nodeType.description}
                        </div>
                      )}
                      <div className="flex items-center gap-3 mt-1 text-xs">
                        {nodeType.inputs && nodeType.inputs.length > 0 && (
                          <div className="flex items-center gap-1">
                            <div className="w-2 h-2 rounded-full bg-blue-500"></div>
                            <span className="text-gray-500 dark:text-gray-400">
                              {nodeType.inputs.length} input{nodeType.inputs.length > 1 ? 's' : ''}
                            </span>
                          </div>
                        )}
                        {nodeType.outputs && nodeType.outputs.length > 0 && (
                          <div className="flex items-center gap-1">
                            <div className="w-2 h-2 rounded-full bg-green-500"></div>
                            <span className="text-gray-500 dark:text-gray-400">
                              {nodeType.outputs.length} output{nodeType.outputs.length > 1 ? 's' : ''}
                            </span>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}

            {compatibleNodes.length === 0 && (
              <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                <div className="text-4xl mb-3">🔍</div>
                <p>No matching nodes found</p>
                <p className="text-xs mt-1">Try adjusting your search terms</p>
              </div>
            )}
          </div>
        </div>

        {/* Footer */}
        <div className="p-4 border-t border-gray-200 dark:border-gray-700">
          <div className="flex justify-end gap-2">
            <button
              onClick={onClose}
              className="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
