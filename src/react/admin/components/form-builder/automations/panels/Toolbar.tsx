/**
 * Toolbar Component
 * Top toolbar with save, undo/redo, template, and utility controls
 */

import { Save, Undo2, Redo2, Download, Upload, Trash2, Play, Eye } from 'lucide-react';

interface ToolbarProps {
  workflowName: string;
  onWorkflowNameChange: (name: string) => void;
  onSave: () => void;
  onUndo: () => void;
  onRedo: () => void;
  onClear: () => void;
  onTest: () => void;
  onPreview: () => void;
  canUndo: boolean;
  canRedo: boolean;
  isSaving?: boolean;
}

export function Toolbar({
  workflowName,
  onWorkflowNameChange,
  onSave,
  onUndo,
  onRedo,
  onClear,
  onTest,
  onPreview,
  canUndo,
  canRedo,
  isSaving = false,
}: ToolbarProps) {
  return (
    <div className="toolbar flex items-center justify-between px-4 py-3 bg-white border-b border-gray-200">
      {/* Left: Workflow Name */}
      <div className="flex items-center gap-4">
        <input
          type="text"
          value={workflowName}
          onChange={(e) => onWorkflowNameChange(e.target.value)}
          placeholder="Untitled Workflow"
          className="text-lg font-semibold text-gray-900 bg-transparent border-none focus:outline-none focus:ring-0 min-w-[300px]"
        />
      </div>

      {/* Center: Edit Controls */}
      <div className="flex items-center gap-2">
        <button
          onClick={onUndo}
          disabled={!canUndo}
          className="p-2 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          title="Undo (Ctrl+Z)"
        >
          <Undo2 className="w-4 h-4 text-gray-700" />
        </button>

        <button
          onClick={onRedo}
          disabled={!canRedo}
          className="p-2 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          title="Redo (Ctrl+Y)"
        >
          <Redo2 className="w-4 h-4 text-gray-700" />
        </button>

        <div className="w-px h-6 bg-gray-300 mx-2" />

        <button
          onClick={onClear}
          className="p-2 rounded hover:bg-gray-100 transition-colors"
          title="Clear Workflow"
        >
          <Trash2 className="w-4 h-4 text-gray-700" />
        </button>
      </div>

      {/* Right: Action Buttons */}
      <div className="flex items-center gap-2">
        <button
          onClick={onPreview}
          className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
        >
          <Eye className="w-4 h-4" />
          Preview
        </button>

        <button
          onClick={onTest}
          className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
        >
          <Play className="w-4 h-4" />
          Test
        </button>

        <button
          onClick={onSave}
          disabled={isSaving}
          className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <Save className="w-4 h-4" />
          {isSaving ? 'Saving...' : 'Save Workflow'}
        </button>
      </div>
    </div>
  );
}
