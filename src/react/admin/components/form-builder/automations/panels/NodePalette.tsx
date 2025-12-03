/**
 * NodePalette Component
 * Left sidebar with draggable node types organized by category tabs
 * Based on ai-automation pattern with tabs instead of accordions
 */

import { useState } from 'react';
import { Search, Zap, Play, GitBranch } from 'lucide-react';
import { getNodeTypesByCategory } from '../data/superFormsNodeTypes';
import type { NodeTypeDefinition } from '../types/workflow.types';

interface NodePaletteProps {
  onAddNode: (type: string, position: { x: number; y: number }) => void;
}

const categories = [
  { id: 'event', name: 'Events', icon: Zap, color: '#10b981' },
  { id: 'action', name: 'Actions', icon: Play, color: '#3b82f6' },
  { id: 'condition', name: 'Conditions', icon: GitBranch, color: '#f97316' },
] as const;

export function NodePalette({ onAddNode }: NodePaletteProps) {
  const [activeCategory, setActiveCategory] = useState<'event' | 'action' | 'condition'>('event');
  const [searchQuery, setSearchQuery] = useState('');

  /**
   * Get filtered nodes for active category
   */
  const getFilteredNodes = () => {
    const nodes = getNodeTypesByCategory(activeCategory);
    if (!searchQuery) return nodes;

    const query = searchQuery.toLowerCase();
    return nodes.filter(
      (node) =>
        node.name.toLowerCase().includes(query) ||
        node.description.toLowerCase().includes(query)
    );
  };

  const filteredNodes = getFilteredNodes();

  /**
   * Handle drag start
   */
  const handleDragStart = (e: React.DragEvent, nodeType: NodeTypeDefinition) => {
    e.dataTransfer.setData('application/super-forms-node', nodeType.id);
    e.dataTransfer.effectAllowed = 'copy';
  };

  /**
   * Handle node click - add to canvas at random position
   */
  const handleNodeClick = (nodeType: NodeTypeDefinition) => {
    onAddNode(nodeType.id, {
      x: Math.random() * 200 + 100,
      y: Math.random() * 200 + 100,
    });
  };

  return (
    <div className="node-palette flex flex-col h-full bg-white border-r border-gray-200">
      {/* Header with search */}
      <div className="p-3 border-b border-gray-200">
        <h3 className="text-sm font-semibold text-gray-900 mb-2">
          Node Library
        </h3>
        <div className="relative">
          <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Search nodes..."
            className="w-full pl-8 pr-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
      </div>

      {/* Category Tabs */}
      <div className="flex border-b border-gray-200">
        {categories.map((category) => {
          const Icon = category.icon;
          const isActive = activeCategory === category.id;
          return (
            <button
              key={category.id}
              onClick={() => setActiveCategory(category.id)}
              className={`flex-1 px-2 py-2 text-xs font-medium transition-colors ${
                isActive
                  ? 'bg-gray-50 text-gray-900 border-b-2'
                  : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
              }`}
              style={{
                borderBottomColor: isActive ? category.color : 'transparent',
              }}
            >
              <div className="flex items-center justify-center gap-1">
                <Icon className="w-3.5 h-3.5" />
                <span>{category.name}</span>
              </div>
            </button>
          );
        })}
      </div>

      {/* Node List - scrollable with max height */}
      <div className="flex-1 overflow-y-auto" style={{ maxHeight: '400px' }}>
        <div className="p-2 space-y-1">
          {filteredNodes.map((node) => (
            <NodePaletteItem
              key={node.id}
              node={node}
              onDragStart={(e) => handleDragStart(e, node)}
              onClick={() => handleNodeClick(node)}
            />
          ))}

          {filteredNodes.length === 0 && (
            <div className="text-center py-6 text-gray-500">
              <Search className="w-8 h-8 mx-auto mb-2 opacity-50" />
              <p className="text-sm">No nodes found</p>
              <p className="text-xs mt-1">Try a different search term</p>
            </div>
          )}
        </div>
      </div>

      {/* Footer hint */}
      <div className="p-2 border-t border-gray-200 bg-gray-50">
        <p className="text-xs text-gray-500">
          Drag or click nodes to add them to the canvas
        </p>
      </div>
    </div>
  );
}

/**
 * Individual node palette item - compact styling
 */
function NodePaletteItem({
  node,
  onDragStart,
  onClick,
}: {
  node: NodeTypeDefinition;
  onDragStart: (e: React.DragEvent) => void;
  onClick: () => void;
}) {
  const Icon = node.icon;

  return (
    <div
      draggable
      onDragStart={onDragStart}
      onClick={onClick}
      className="flex items-center gap-2 p-2 rounded-md border border-gray-200 hover:border-gray-300 hover:bg-gray-50 cursor-move transition-all"
      style={{
        borderLeftColor: node.color,
        borderLeftWidth: '3px',
      }}
    >
      <div
        className="flex-shrink-0 w-7 h-7 rounded flex items-center justify-center"
        style={{ backgroundColor: `${node.color}15` }}
      >
        <Icon className="w-4 h-4" style={{ color: node.color }} />
      </div>

      <div className="flex-1 min-w-0">
        <h4 className="text-xs font-medium text-gray-900 truncate">
          {node.name}
        </h4>
        <p className="text-xs text-gray-500 truncate">
          {node.description}
        </p>
      </div>

      {/* Port indicators */}
      <div className="flex items-center gap-1 text-xs text-gray-400">
        {node.inputs.length > 0 && (
          <div className="flex items-center gap-0.5">
            <div className="w-1.5 h-1.5 rounded-full bg-blue-400" />
            <span>{node.inputs.length}</span>
          </div>
        )}
        {node.outputs.length > 0 && (
          <div className="flex items-center gap-0.5">
            <div className="w-1.5 h-1.5 rounded-full bg-green-400" />
            <span>{node.outputs.length}</span>
          </div>
        )}
      </div>
    </div>
  );
}
