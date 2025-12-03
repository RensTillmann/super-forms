/**
 * VisualBuilder Component
 * Main visual workflow builder - integrates all components
 */

import { useState, useCallback, useEffect, useRef } from 'react';
import { useNodeEditor } from './hooks/useNodeEditor';
import { Canvas } from './canvas/Canvas';
import { NodePalette } from './panels/NodePalette';
import { PropertiesPanel } from './panels/PropertiesPanel';
import { Toolbar } from './panels/Toolbar';
import { ConnectionModal } from './modals/ConnectionModal';
import { SendEmailModal } from './modals/SendEmailModal';
import type { WorkflowNode, WorkflowGraph } from './types/workflow.types';

interface VisualBuilderProps {
  formId?: number;
  automationId?: number | null;
}

export function VisualBuilder({ formId, automationId }: VisualBuilderProps) {
  const editor = useNodeEditor();
  const [workflowName, setWorkflowName] = useState('Untitled Workflow');
  const [selectedNodeId, setSelectedNodeId] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);
  const canvasRef = useRef<HTMLDivElement>(null);

  // Connection modal state
  const [showConnectionModal, setShowConnectionModal] = useState(false);
  const [connectionModalPosition, setConnectionModalPosition] = useState<{ x: number; y: number } | null>(null);

  // Email builder modal state
  const [showEmailBuilder, setShowEmailBuilder] = useState(false);
  const [emailBuilderNode, setEmailBuilderNode] = useState<WorkflowNode | null>(null);

  // Load existing workflow if automationId provided
  useEffect(() => {
    if (automationId) {
      loadWorkflow(automationId);
    }
  }, [automationId]);

  /**
   * Load workflow from backend
   */
  const loadWorkflow = async (id: number) => {
    try {
      const response = await fetch(
        `${window.sfuiData.restUrl}/automations/${id}`,
        {
          headers: {
            'X-WP-Nonce': window.sfuiData.restNonce,
          },
        }
      );

      if (!response.ok) throw new Error('Failed to load workflow');

      const data = await response.json();
      setWorkflowName(data.name || 'Untitled Workflow');

      if (data.workflow_graph) {
        const graph: WorkflowGraph = JSON.parse(data.workflow_graph);
        editor.loadWorkflow(graph);
      }
    } catch (error) {
      console.error('Failed to load workflow:', error);
      alert('Failed to load workflow. Please try again.');
    }
  };

  /**
   * Save workflow to backend
   */
  const handleSave = async () => {
    if (!workflowName.trim()) {
      alert('Please enter a workflow name');
      return;
    }

    setIsSaving(true);

    try {
      const graph = editor.exportWorkflow();

      // Extract event ID from trigger nodes
      const triggerNode = editor.nodes.find((n) =>
        n.type.startsWith('form.') ||
        n.type.startsWith('entry.') ||
        n.type.startsWith('payment.') ||
        n.type.startsWith('session.')
      );

      if (!triggerNode) {
        alert('Please add at least one trigger node (event) to your workflow');
        setIsSaving(false);
        return;
      }

      const payload = {
        name: workflowName,
        type: 'visual',
        workflow_graph: JSON.stringify(graph),
        enabled: true,
        // NOTE: No scope/scope_id/event_id at trigger level!
        // Scope is configured in event node config (node.config.scope, node.config.formId)
      };

      const url = automationId
        ? `${window.sfuiData.restUrl}/automations/${automationId}`
        : `${window.sfuiData.restUrl}/automations`;

      const response = await fetch(url, {
        method: automationId ? 'PUT' : 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': window.sfuiData.restNonce,
        },
        body: JSON.stringify(payload),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Failed to save workflow');
      }

      const result = await response.json();

      alert('Workflow saved successfully!');

      // If this was a new workflow, redirect to edit mode
      if (!automationId && result.id) {
        window.location.href = `${window.location.pathname}?page=super_create_form&id=${formId}&tab=automations&automation_id=${result.id}`;
      }
    } catch (error) {
      console.error('Failed to save workflow:', error);
      alert('Failed to save workflow. Please try again.');
    } finally {
      setIsSaving(false);
    }
  };

  /**
   * Handle node drag from palette
   */
  const handleCanvasDrop = useCallback(
    (e: React.DragEvent) => {
      e.preventDefault();

      const nodeType = e.dataTransfer.getData('application/super-forms-node');
      if (!nodeType || !canvasRef.current) return;

      // Get canvas position
      const rect = canvasRef.current.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;

      // Convert to canvas coordinates
      const canvasPos = editor.screenToCanvas(x, y);

      // Add node
      editor.addNode(nodeType, canvasPos);
    },
    [editor]
  );

  /**
   * Handle canvas drag over (required for drop)
   */
  const handleCanvasDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'copy';
  }, []);

  /**
   * Handle node click
   */
  const handleNodeClick = useCallback(
    (nodeId: string, addToSelection: boolean) => {
      editor.selectNode(nodeId, addToSelection);
      setSelectedNodeId(nodeId);
    },
    [editor]
  );

  /**
   * Handle connection end - if on empty canvas, show modal
   */
  const handleConnectionEnd = useCallback(
    (nodeId: string, inputName: string) => {
      // Complete the connection via editor
      editor.endConnection(nodeId, inputName);
    },
    [editor]
  );

  /**
   * Handle connection drop on empty canvas
   */
  const handleConnectionDropOnCanvas = useCallback(
    (position: { x: number; y: number }) => {
      setConnectionModalPosition(position);
      setShowConnectionModal(true);
    },
    []
  );

  /**
   * Handle create and connect from modal
   */
  const handleCreateAndConnect = useCallback(
    (nodeType: string, position: { x: number; y: number }) => {
      // Create the new node at the position
      const newNode = editor.addNode(nodeType, position);

      if (newNode && editor.connectionStart) {
        // Get the first input from the node type
        const nodeTypeDef = Object.values(editor.nodes).find(n => n.type === nodeType);
        const inputName = nodeTypeDef?.config?.inputs?.[0] || 'data';

        // Connect the nodes
        editor.endConnection(newNode, inputName);
      }

      setShowConnectionModal(false);
      setConnectionModalPosition(null);
    },
    [editor]
  );

  /**
   * Handle node drag
   */
  const handleNodeDragStart = useCallback(
    (nodeId: string, startX: number, startY: number) => {
      // Node drag is handled by editor
    },
    []
  );

  const handleNodeDrag = useCallback(
    (nodeId: string, position: { x: number; y: number }) => {
      // Update node position (already snapped by Canvas)
      editor.updateNode(nodeId, {
        position,
      });
    },
    [editor]
  );

  const handleNodeDragEnd = useCallback(() => {
    // Drag end handled by editor
  }, []);

  /**
   * Get selected node object
   */
  const selectedNode = selectedNodeId
    ? editor.nodes.find((n) => n.id === selectedNodeId) || null
    : null;

  /**
   * Clear workflow with confirmation
   */
  const handleClear = useCallback(() => {
    if (editor.nodes.length === 0) return;

    if (
      confirm(
        'Are you sure you want to clear this workflow? This action cannot be undone.'
      )
    ) {
      editor.clearWorkflow();
      setSelectedNodeId(null);
    }
  }, [editor]);

  /**
   * Test workflow
   */
  const handleTest = useCallback(() => {
    alert('Test functionality coming soon! This will fire a test event with sample data.');
  }, []);

  /**
   * Preview workflow
   */
  const handlePreview = useCallback(() => {
    alert('Preview functionality coming soon! This will show the workflow execution flow.');
  }, []);

  /**
   * Open email builder for a Send Email node
   */
  const handleOpenEmailBuilder = useCallback((node: WorkflowNode) => {
    setEmailBuilderNode(node);
    setShowEmailBuilder(true);
  }, []);

  /**
   * Close email builder
   */
  const handleCloseEmailBuilder = useCallback(() => {
    setShowEmailBuilder(false);
    setEmailBuilderNode(null);
  }, []);

  return (
    <div className="visual-builder flex flex-col h-full bg-gray-100">
      {/* Toolbar */}
      <Toolbar
        workflowName={workflowName}
        onWorkflowNameChange={setWorkflowName}
        onSave={handleSave}
        onUndo={editor.undo}
        onRedo={editor.redo}
        onClear={handleClear}
        onTest={handleTest}
        onPreview={handlePreview}
        canUndo={editor.canUndo}
        canRedo={editor.canRedo}
        isSaving={isSaving}
      />

      {/* Main content */}
      <div className="flex-1 flex overflow-hidden">
        {/* Left: Node Palette */}
        <div className="w-80 flex-shrink-0">
          <NodePalette
            onAddNode={(type, position) => editor.addNode(type, position)}
          />
        </div>

        {/* Center: Canvas */}
        <div
          ref={canvasRef}
          className="flex-1 relative"
          onDrop={handleCanvasDrop}
          onDragOver={handleCanvasDragOver}
        >
          {/* Canvas with nodes and connections */}
          <Canvas
            nodes={editor.nodes}
            connections={editor.connections}
            groups={editor.groups}
            viewport={editor.viewport}
            selectedNodes={editor.selectedNodes}
            selectedConnections={editor.selectedConnections}
            onNodeClick={handleNodeClick}
            onNodeDragStart={handleNodeDragStart}
            onNodeDrag={handleNodeDrag}
            onNodeDragEnd={handleNodeDragEnd}
            onCanvasPan={editor.panViewport}
            onCanvasClick={() => {
              editor.clearSelection();
              setSelectedNodeId(null);
            }}
            onZoom={editor.zoomViewport}
            dragState={editor.dragState}
            setDragState={editor.setDragState}
            isConnecting={editor.isConnecting}
            connectionStart={editor.connectionStart}
            onConnectionStart={editor.startConnection}
            onConnectionEnd={handleConnectionEnd}
            onConnectionDropOnCanvas={handleConnectionDropOnCanvas}
            onConnectionClick={(id) => {
              // TODO: Handle connection selection
            }}
            onDeleteConnection={editor.deleteConnection}
            onCancelConnection={editor.cancelConnection}
            onSelectNodesInRect={editor.selectNodes}
            onUpdateGroup={editor.updateGroup}
            onRemoveGroup={editor.removeGroup}
            onMoveGroup={editor.moveGroup}
          />
        </div>

        {/* Right: Properties Panel - Only show when node selected */}
        {selectedNode && (
          <div className="w-96 flex-shrink-0">
            <PropertiesPanel
              selectedNode={selectedNode}
              onUpdateNode={editor.updateNode}
              onClose={() => {
                editor.clearSelection();
                setSelectedNodeId(null);
              }}
              onOpenEmailBuilder={handleOpenEmailBuilder}
            />
          </div>
        )}
      </div>

      {/* Send Email Builder Modal */}
      {emailBuilderNode && (
        <SendEmailModal
          isOpen={showEmailBuilder}
          onClose={handleCloseEmailBuilder}
          node={emailBuilderNode}
          onUpdateNode={editor.updateNode}
        />
      )}

      {/* Connection Modal */}
      <ConnectionModal
        isOpen={showConnectionModal}
        onClose={() => {
          setShowConnectionModal(false);
          setConnectionModalPosition(null);
          editor.cancelConnection();
        }}
        connectionStart={editor.connectionStart}
        position={connectionModalPosition}
        onCreateAndConnect={handleCreateAndConnect}
      />
    </div>
  );
}
