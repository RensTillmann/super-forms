/**
 * useNodeEditor Hook
 * Core state management for visual workflow builder
 * Based on ai-automation architecture - pure React hooks (no Redux/Zustand)
 */

import { useState, useCallback, useRef } from 'react';
import type {
  WorkflowNode,
  WorkflowConnection,
  WorkflowGroup,
  Viewport,
  DragState,
  NodeEditorState,
  WorkflowGraph,
} from '../types/workflow.types';

const GRID_SIZE = 20;
const SNAP_RADIUS = 12;
const MAX_HISTORY = 50;

export function useNodeEditor() {
  // State
  const [nodes, setNodes] = useState<WorkflowNode[]>([]);
  const [connections, setConnections] = useState<WorkflowConnection[]>([]);
  const [groups, setGroups] = useState<WorkflowGroup[]>([]);
  const [viewport, setViewport] = useState<Viewport>({ x: 0, y: 0, zoom: 1 });
  const [selectedNodes, setSelectedNodes] = useState<string[]>([]);
  const [selectedConnections, setSelectedConnections] = useState<string[]>([]);
  const [dragState, setDragState] = useState<DragState | null>(null);
  const [isConnecting, setIsConnecting] = useState(false);
  const [connectionStart, setConnectionStart] = useState<{
    nodeId: string;
    outputName: string;
  } | null>(null);

  // History for undo/redo
  const [history, setHistory] = useState<WorkflowGraph[]>([]);
  const [historyIndex, setHistoryIndex] = useState(-1);

  // Counters for ID generation
  const nodeCounter = useRef(1);
  const connectionCounter = useRef(1);
  const groupCounter = useRef(1);

  /**
   * Convert screen coordinates to canvas coordinates
   */
  const screenToCanvas = useCallback((screenX: number, screenY: number) => {
    return {
      x: (screenX - viewport.x) / viewport.zoom,
      y: (screenY - viewport.y) / viewport.zoom,
    };
  }, [viewport]);

  /**
   * Convert canvas coordinates to screen coordinates
   */
  const canvasToScreen = useCallback((canvasX: number, canvasY: number) => {
    return {
      x: canvasX * viewport.zoom + viewport.x,
      y: canvasY * viewport.zoom + viewport.y,
    };
  }, [viewport]);

  /**
   * Snap position to grid
   */
  const snapToGrid = useCallback((x: number, y: number) => {
    return {
      x: Math.round(x / GRID_SIZE) * GRID_SIZE,
      y: Math.round(y / GRID_SIZE) * GRID_SIZE,
    };
  }, []);

  /**
   * Save current state to history
   */
  const saveHistory = useCallback(() => {
    const currentState: WorkflowGraph = {
      nodes,
      connections,
      groups,
      viewport,
    };

    setHistory((prev) => {
      const newHistory = prev.slice(0, historyIndex + 1);
      newHistory.push(currentState);
      if (newHistory.length > MAX_HISTORY) {
        newHistory.shift();
        return newHistory;
      }
      return newHistory;
    });

    setHistoryIndex((prev) => Math.min(prev + 1, MAX_HISTORY - 1));
  }, [nodes, connections, groups, viewport, historyIndex]);

  /**
   * Undo last change
   */
  const undo = useCallback(() => {
    if (historyIndex > 0) {
      const previousState = history[historyIndex - 1];
      setNodes(previousState.nodes);
      setConnections(previousState.connections);
      setGroups(previousState.groups);
      setViewport(previousState.viewport);
      setHistoryIndex(historyIndex - 1);
    }
  }, [history, historyIndex]);

  /**
   * Redo last undone change
   */
  const redo = useCallback(() => {
    if (historyIndex < history.length - 1) {
      const nextState = history[historyIndex + 1];
      setNodes(nextState.nodes);
      setConnections(nextState.connections);
      setGroups(nextState.groups);
      setViewport(nextState.viewport);
      setHistoryIndex(historyIndex + 1);
    }
  }, [history, historyIndex]);

  /**
   * Add a new node to the canvas
   */
  const addNode = useCallback((type: string, position: { x: number; y: number }, config: Record<string, any> = {}) => {
    const snapped = snapToGrid(position.x, position.y);
    const newNode: WorkflowNode = {
      id: `node-${nodeCounter.current++}`,
      type,
      position: snapped,
      config,
      selected: false,
      zIndex: nodes.length + 1,
    };

    saveHistory();
    setNodes((prev) => [...prev, newNode]);
    return newNode.id;
  }, [nodes, snapToGrid, saveHistory]);

  /**
   * Update node configuration
   */
  const updateNode = useCallback((nodeId: string, updates: Partial<WorkflowNode>) => {
    saveHistory();
    setNodes((prev) =>
      prev.map((node) =>
        node.id === nodeId ? { ...node, ...updates } : node
      )
    );
  }, [saveHistory]);

  /**
   * Delete node and connected edges
   */
  const deleteNode = useCallback((nodeId: string) => {
    saveHistory();
    setNodes((prev) => prev.filter((n) => n.id !== nodeId));
    setConnections((prev) =>
      prev.filter((c) => c.from !== nodeId && c.to !== nodeId)
    );
  }, [saveHistory]);

  /**
   * Delete multiple nodes
   */
  const deleteNodes = useCallback((nodeIds: string[]) => {
    saveHistory();
    setNodes((prev) => prev.filter((n) => !nodeIds.includes(n.id)));
    setConnections((prev) =>
      prev.filter((c) => !nodeIds.includes(c.from) && !nodeIds.includes(c.to))
    );
    setSelectedNodes([]);
  }, [saveHistory]);

  /**
   * Add a connection between nodes
   */
  const addConnection = useCallback((
    from: string,
    fromOutput: string,
    to: string,
    toInput: string
  ) => {
    // Check if connection already exists
    const exists = connections.some(
      (c) =>
        c.from === from &&
        c.fromOutput === fromOutput &&
        c.to === to &&
        c.toInput === toInput
    );

    if (exists) {
      return null;
    }

    const newConnection: WorkflowConnection = {
      id: `conn-${connectionCounter.current++}`,
      from,
      fromOutput,
      to,
      toInput,
      selected: false,
    };

    saveHistory();
    setConnections((prev) => [...prev, newConnection]);
    return newConnection.id;
  }, [connections, saveHistory]);

  /**
   * Delete connection
   */
  const deleteConnection = useCallback((connectionId: string) => {
    saveHistory();
    setConnections((prev) => prev.filter((c) => c.id !== connectionId));
  }, [saveHistory]);

  /**
   * Select/deselect nodes
   */
  const selectNode = useCallback((nodeId: string, addToSelection = false) => {
    if (addToSelection) {
      setSelectedNodes((prev) =>
        prev.includes(nodeId)
          ? prev.filter((id) => id !== nodeId)
          : [...prev, nodeId]
      );
    } else {
      setSelectedNodes([nodeId]);
    }

    // Update node visual state
    setNodes((prev) =>
      prev.map((node) => ({
        ...node,
        selected: addToSelection
          ? node.id === nodeId
            ? !node.selected
            : node.selected
          : node.id === nodeId,
      }))
    );
  }, []);

  /**
   * Select multiple nodes at once (for selection rectangle)
   */
  const selectNodes = useCallback((nodeIds: string[]) => {
    setSelectedNodes(nodeIds);
    setNodes((prev) =>
      prev.map((node) => ({
        ...node,
        selected: nodeIds.includes(node.id),
      }))
    );
  }, []);

  /**
   * Clear all selections
   */
  const clearSelection = useCallback(() => {
    setSelectedNodes([]);
    setSelectedConnections([]);
    setNodes((prev) => prev.map((n) => ({ ...n, selected: false })));
    setConnections((prev) => prev.map((c) => ({ ...c, selected: false })));
  }, []);

  /**
   * Pan viewport
   */
  const panViewport = useCallback((deltaX: number, deltaY: number) => {
    setViewport((prev) => ({
      ...prev,
      x: prev.x + deltaX,
      y: prev.y + deltaY,
    }));
  }, []);

  /**
   * Zoom viewport
   * Uses zoom factor approach from ai-automation for smooth zooming
   */
  const zoomViewport = useCallback((delta: number, centerX?: number, centerY?: number) => {
    setViewport((prev) => {
      // Use zoom factor (0.95 for zoom out, 1.05 for zoom in)
      const zoomFactor = delta > 0 ? 1.05 : 0.95;
      const newZoom = Math.max(0.1, Math.min(3, prev.zoom * zoomFactor));

      if (centerX !== undefined && centerY !== undefined) {
        // Zoom towards mouse position (from ai-automation logic)
        const zoomRatio = newZoom / prev.zoom;
        const newX = centerX - (centerX - prev.x) * zoomRatio;
        const newY = centerY - (centerY - prev.y) * zoomRatio;

        return {
          x: newX,
          y: newY,
          zoom: newZoom,
        };
      }

      return { ...prev, zoom: newZoom };
    });
  }, []);

  /**
   * Reset viewport
   */
  const resetViewport = useCallback(() => {
    setViewport({ x: 0, y: 0, zoom: 1 });
  }, []);

  /**
   * Load workflow from data
   */
  const loadWorkflow = useCallback((graph: WorkflowGraph) => {
    setNodes(graph.nodes);
    setConnections(graph.connections);
    setGroups(graph.groups);
    setViewport(graph.viewport);
    setHistory([graph]);
    setHistoryIndex(0);
    clearSelection();
  }, [clearSelection]);

  /**
   * Export current workflow
   */
  const exportWorkflow = useCallback((): WorkflowGraph => {
    return {
      nodes,
      connections,
      groups,
      viewport,
    };
  }, [nodes, connections, groups, viewport]);

  /**
   * Clear entire workflow
   */
  const clearWorkflow = useCallback(() => {
    saveHistory();
    setNodes([]);
    setConnections([]);
    setGroups([]);
    clearSelection();
  }, [saveHistory, clearSelection]);

  /**
   * Check if two nodes can be connected
   */
  const canConnect = useCallback(
    (fromNode: string, fromOutput: string, toNode: string, toInput: string) => {
      // Prevent self-connection
      if (fromNode === toNode) return false;

      // Check if connection already exists
      const existing = connections.find(
        (conn) =>
          conn.from === fromNode &&
          conn.fromOutput === fromOutput &&
          conn.to === toNode &&
          conn.toInput === toInput
      );
      if (existing) return false;

      // Check for cycles (prevent circular dependencies)
      const visited = new Set<string>();
      const hasPath = (from: string, to: string): boolean => {
        if (from === to) return true;
        if (visited.has(from)) return false;
        visited.add(from);

        const outgoing = connections.filter((conn) => conn.from === from);
        return outgoing.some((conn) => hasPath(conn.to, to));
      };

      return !hasPath(toNode, fromNode);
    },
    [connections]
  );

  /**
   * Start creating a connection from an output port
   */
  const startConnection = useCallback((nodeId: string, outputName: string) => {
    console.log('Starting connection from:', nodeId, outputName);
    setIsConnecting(true);
    setConnectionStart({ nodeId, outputName });
  }, []);

  /**
   * Complete a connection to an input port
   */
  const endConnection = useCallback(
    (targetNodeId: string, inputName: string) => {
      console.log('Ending connection to:', targetNodeId, inputName);
      if (
        connectionStart &&
        canConnect(
          connectionStart.nodeId,
          connectionStart.outputName,
          targetNodeId,
          inputName
        )
      ) {
        console.log('Creating connection:', connectionStart.nodeId, '->', targetNodeId);
        addConnection(
          connectionStart.nodeId,
          connectionStart.outputName,
          targetNodeId,
          inputName
        );
      }

      console.log('Setting isConnecting to false');
      setIsConnecting(false);
      setConnectionStart(null);
    },
    [connectionStart, canConnect, addConnection]
  );

  /**
   * Cancel connection creation
   */
  const cancelConnection = useCallback(() => {
    console.log('Canceling connection');
    setIsConnecting(false);
    setConnectionStart(null);
  }, []);

  /**
   * Add a new group
   */
  const addGroup = useCallback(
    (name: string, bounds: { x: number; y: number; width: number; height: number }, nodeIds: string[] = []) => {
      const newGroup = {
        id: `group-${groupCounter.current++}`,
        name,
        nodeIds,
        bounds,
        color: 'rgba(59, 130, 246, 0.3)',
        zIndex: -1,
      };

      setGroups((prev) => [...prev, newGroup]);
      saveHistory();

      return newGroup.id;
    },
    [saveHistory]
  );

  /**
   * Update a group
   */
  const updateGroup = useCallback((groupId: string, updates: Partial<typeof groups[0]>) => {
    setGroups((prev) =>
      prev.map((group) =>
        group.id === groupId ? { ...group, ...updates } : group
      )
    );
  }, []);

  /**
   * Remove a group
   */
  const removeGroup = useCallback((groupId: string) => {
    setGroups((prev) => prev.filter((group) => group.id !== groupId));
    saveHistory();
  }, [saveHistory]);

  /**
   * Move a group and its contained nodes
   */
  const moveGroup = useCallback(
    (groupId: string, deltaX: number, deltaY: number, phase: 'start' | 'move' | 'end') => {
      const group = groups.find((g) => g.id === groupId);
      if (!group) return;

      if (phase === 'start') {
        // Mark nodes in group as being group-dragged and store initial positions
        setNodes((prev) =>
          prev.map((node) => ({
            ...node,
            isGroupDragging: group.nodeIds.includes(node.id),
            groupDragOffset: group.nodeIds.includes(node.id) ? { x: 0, y: 0 } : undefined,
          }))
        );
      } else if (phase === 'move') {
        // Update visual offset for nodes in group
        setNodes((prev) =>
          prev.map((node) =>
            group.nodeIds.includes(node.id)
              ? { ...node, groupDragOffset: { x: deltaX, y: deltaY } }
              : node
          )
        );
      } else if (phase === 'end') {
        // Apply final positions to nodes and group
        setNodes((prev) =>
          prev.map((node) =>
            group.nodeIds.includes(node.id)
              ? {
                  ...node,
                  position: {
                    x: node.position.x + deltaX,
                    y: node.position.y + deltaY,
                  },
                  isGroupDragging: false,
                  groupDragOffset: undefined,
                }
              : node
          )
        );

        // Update group bounds
        setGroups((prev) =>
          prev.map((g) =>
            g.id === groupId
              ? {
                  ...g,
                  bounds: {
                    ...g.bounds,
                    x: g.bounds.x + deltaX,
                    y: g.bounds.y + deltaY,
                  },
                }
              : g
          )
        );

        saveHistory();
      }
    },
    [groups, saveHistory]
  );

  /**
   * Create group from selected nodes
   */
  const createGroupFromSelection = useCallback(() => {
    if (selectedNodes.length < 2) return null;

    // Calculate bounding box of selected nodes
    const selectedNodeObjects = nodes.filter((n) => selectedNodes.includes(n.id));
    if (selectedNodeObjects.length === 0) return null;

    const padding = 20;
    const nodeWidth = 200;
    const nodeHeight = 100;

    let minX = Infinity;
    let minY = Infinity;
    let maxX = -Infinity;
    let maxY = -Infinity;

    selectedNodeObjects.forEach((node) => {
      minX = Math.min(minX, node.position.x);
      minY = Math.min(minY, node.position.y);
      maxX = Math.max(maxX, node.position.x + nodeWidth);
      maxY = Math.max(maxY, node.position.y + nodeHeight);
    });

    const bounds = {
      x: minX - padding,
      y: minY - padding,
      width: maxX - minX + padding * 2,
      height: maxY - minY + padding * 2,
    };

    return addGroup(`Group ${groupCounter.current}`, bounds, selectedNodes);
  }, [selectedNodes, nodes, addGroup]);

  return {
    // State
    nodes,
    connections,
    groups,
    viewport,
    selectedNodes,
    selectedConnections,
    dragState,
    isConnecting,
    connectionStart,

    // Node operations
    addNode,
    updateNode,
    deleteNode,
    deleteNodes,
    selectNode,
    selectNodes,

    // Connection operations
    addConnection,
    deleteConnection,
    startConnection,
    endConnection,
    cancelConnection,
    canConnect,

    // Selection
    clearSelection,

    // Group operations
    addGroup,
    updateGroup,
    removeGroup,
    moveGroup,
    createGroupFromSelection,

    // Viewport operations
    panViewport,
    zoomViewport,
    resetViewport,
    screenToCanvas,
    canvasToScreen,
    snapToGrid,

    // History
    undo,
    redo,
    canUndo: historyIndex > 0,
    canRedo: historyIndex < history.length - 1,

    // Workflow management
    loadWorkflow,
    exportWorkflow,
    clearWorkflow,

    // Drag state
    setDragState,
  };
}
