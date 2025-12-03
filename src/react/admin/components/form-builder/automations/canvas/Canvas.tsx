/**
 * Canvas Component
 * Main canvas area for visual workflow builder
 * Handles pan, zoom, drag, and node rendering
 */

import { useRef, useCallback, useEffect, MouseEvent as ReactMouseEvent } from 'react';
import type { WorkflowNode, WorkflowConnection, WorkflowGroup, DragState } from '../types/workflow.types';
import { Node } from './Node';
import { ConnectionOverlay } from './ConnectionOverlay';
import { GroupContainer } from './GroupContainer';

interface CanvasProps {
  nodes: WorkflowNode[];
  connections: WorkflowConnection[];
  groups: WorkflowGroup[];
  viewport: { x: number; y: number; zoom: number };
  selectedNodes: string[];
  selectedConnections: string[];
  onNodeClick: (nodeId: string, addToSelection: boolean) => void;
  onNodeDragStart: (nodeId: string, startX: number, startY: number) => void;
  onNodeDrag: (nodeId: string, position: { x: number; y: number }) => void;
  onNodeDragEnd: () => void;
  onCanvasPan: (deltaX: number, deltaY: number) => void;
  onCanvasClick: () => void;
  onZoom: (delta: number, centerX?: number, centerY?: number) => void;
  dragState: DragState | null;
  setDragState: (state: DragState | null) => void;
  isConnecting?: boolean;
  connectionStart?: { nodeId: string; outputName: string } | null;
  onConnectionStart?: (nodeId: string, outputName: string) => void;
  onConnectionEnd?: (nodeId: string, inputName: string) => void;
  onConnectionDropOnCanvas?: (position: { x: number; y: number }) => void;
  onConnectionClick?: (connectionId: string) => void;
  onDeleteConnection?: (connectionId: string) => void;
  onCancelConnection?: () => void;
  onSelectNodesInRect?: (nodeIds: string[]) => void;
  onUpdateGroup?: (groupId: string, updates: Partial<WorkflowGroup>) => void;
  onRemoveGroup?: (groupId: string) => void;
  onMoveGroup?: (groupId: string, deltaX: number, deltaY: number, phase: 'start' | 'move' | 'end') => void;
}

export function Canvas({
  nodes,
  connections,
  groups,
  viewport,
  selectedNodes,
  selectedConnections,
  onNodeClick,
  onNodeDragStart,
  onNodeDrag,
  onNodeDragEnd,
  onCanvasPan,
  onCanvasClick,
  onZoom,
  dragState,
  setDragState,
  isConnecting = false,
  connectionStart = null,
  onConnectionStart,
  onConnectionEnd,
  onConnectionDropOnCanvas,
  onConnectionClick,
  onDeleteConnection,
  onCancelConnection,
  onSelectNodesInRect,
  onUpdateGroup,
  onRemoveGroup,
  onMoveGroup,
}: CanvasProps) {
  const canvasRef = useRef<HTMLDivElement>(null);
  const isDragging = useRef(false);
  const lastMousePos = useRef({ x: 0, y: 0 });

  // Store initial state for drag operations to prevent feedback loops
  const dragStartRef = useRef<{
    startPos: { x: number; y: number };
    initialViewport: { x: number; y: number; zoom: number };
    initialNodePositions: Map<string, { x: number; y: number }>;
  }>({
    startPos: { x: 0, y: 0 },
    initialViewport: { x: 0, y: 0, zoom: 1 },
    initialNodePositions: new Map(),
  });

  /**
   * Handle mouse down on canvas
   */
  const handleCanvasMouseDown = useCallback(
    (e: ReactMouseEvent<HTMLDivElement>) => {
      if (e.button !== 0) return; // Only left click

      const target = e.target as HTMLElement;
      const screenPos = { x: e.clientX, y: e.clientY };

      // Store initial viewport state
      dragStartRef.current.startPos = screenPos;
      dragStartRef.current.initialViewport = { ...viewport };

      // Check if clicking on a node
      const nodeElement = target.closest('[data-node-id]');
      if (nodeElement) {
        const nodeId = nodeElement.getAttribute('data-node-id')!;
        const addToSelection = e.shiftKey || e.metaKey || e.ctrlKey;

        onNodeClick(nodeId, addToSelection);

        // Store initial positions of all nodes that will move
        const nodesToMove = selectedNodes.includes(nodeId)
          ? selectedNodes // If clicking on selected node, move all selected
          : [nodeId]; // If clicking on unselected node, move just that one

        dragStartRef.current.initialNodePositions.clear();
        nodesToMove.forEach((id) => {
          const node = nodes.find((n) => n.id === id);
          if (node) {
            dragStartRef.current.initialNodePositions.set(id, { ...node.position });
          }
        });

        // Start node drag
        isDragging.current = true;
        lastMousePos.current = screenPos;
        onNodeDragStart(nodeId, e.clientX, e.clientY);

        setDragState({
          type: 'node',
          startX: e.clientX,
          startY: e.clientY,
          currentX: e.clientX,
          currentY: e.clientY,
          nodeId,
        });

        return;
      }

      // Check for Ctrl+drag for selection rectangle
      if (e.ctrlKey || e.metaKey) {
        isDragging.current = true;
        lastMousePos.current = screenPos;

        setDragState({
          type: 'selection',
          startX: e.clientX,
          startY: e.clientY,
          currentX: e.clientX,
          currentY: e.clientY,
        });

        return;
      }

      // Start viewport pan
      isDragging.current = true;
      lastMousePos.current = screenPos;

      setDragState({
        type: 'viewport',
        startX: e.clientX,
        startY: e.clientY,
        currentX: e.clientX,
        currentY: e.clientY,
      });
    },
    [onNodeClick, onNodeDragStart, setDragState, viewport, selectedNodes, nodes]
  );

  /**
   * Handle mouse move
   * Uses total delta from initial position to prevent feedback loops
   */
  const handleMouseMove = useCallback(
    (e: ReactMouseEvent<HTMLDivElement>) => {
      if (!isDragging.current || !dragState) return;

      const screenPos = { x: e.clientX, y: e.clientY };

      // Calculate total movement from initial position (pure math, no dependencies)
      const totalDeltaX = screenPos.x - dragStartRef.current.startPos.x;
      const totalDeltaY = screenPos.y - dragStartRef.current.startPos.y;

      if (dragState.type === 'node') {
        // Node dragging - use stored initial zoom to avoid feedback
        const zoom = dragStartRef.current.initialViewport.zoom;

        // Get the initial positions and calculate new positions
        dragStartRef.current.initialNodePositions.forEach((initialPos, nodeId) => {
          const newPosition = {
            x: initialPos.x + totalDeltaX / zoom,
            y: initialPos.y + totalDeltaY / zoom,
          };

          // Snap to grid
          newPosition.x = Math.round(newPosition.x / 20) * 20;
          newPosition.y = Math.round(newPosition.y / 20) * 20;

          // Pass absolute position to avoid feedback loops
          onNodeDrag(nodeId, newPosition);
        });
      } else if (dragState.type === 'viewport') {
        // PURE VIEWPORT PANNING - use total delta from initial position
        const newViewport = {
          x: dragStartRef.current.initialViewport.x + totalDeltaX,
          y: dragStartRef.current.initialViewport.y + totalDeltaY,
        };
        onCanvasPan(newViewport.x - viewport.x, newViewport.y - viewport.y);
      }
      // Selection rectangle - just update coordinates, selection happens on mouse up

      lastMousePos.current = screenPos;

      setDragState({
        ...dragState,
        currentX: e.clientX,
        currentY: e.clientY,
      });
    },
    [dragState, nodes, onNodeDrag, onCanvasPan, setDragState]
  );

  /**
   * Handle mouse up
   */
  const handleMouseUp = useCallback(
    (e: ReactMouseEvent<HTMLDivElement>) => {
      // Handle connection completion on canvas
      if (isConnecting && onConnectionDropOnCanvas) {
        const target = e.target as HTMLElement;

        // Check if releasing on empty canvas (not on a node or port)
        const isOnNode = target.closest('[data-node-id]');
        const isOnPort = target.closest('[data-port-type]');

        if (!isOnNode && !isOnPort) {
          // Convert screen coordinates to canvas coordinates
          const rect = canvasRef.current?.getBoundingClientRect();
          if (rect) {
            const canvasX = (e.clientX - rect.left - viewport.x) / viewport.zoom;
            const canvasY = (e.clientY - rect.top - viewport.y) / viewport.zoom;

            onConnectionDropOnCanvas({ x: canvasX, y: canvasY });
          }
        }
      }

      if (!isDragging.current) return;

      isDragging.current = false;

      if (dragState?.type === 'node') {
        onNodeDragEnd();
      } else if (dragState?.type === 'selection' && onSelectNodesInRect) {
        // Calculate selection rectangle in canvas coordinates
        const rect = canvasRef.current?.getBoundingClientRect();
        if (rect) {
          // Convert screen coordinates to canvas coordinates
          const startX = (dragState.startX - rect.left - viewport.x) / viewport.zoom;
          const startY = (dragState.startY - rect.top - viewport.y) / viewport.zoom;
          const endX = (dragState.currentX - rect.left - viewport.x) / viewport.zoom;
          const endY = (dragState.currentY - rect.top - viewport.y) / viewport.zoom;

          // Calculate selection bounds (handle negative dimensions)
          const selectionRect = {
            x: Math.min(startX, endX),
            y: Math.min(startY, endY),
            width: Math.abs(endX - startX),
            height: Math.abs(endY - startY),
          };

          // Find nodes within selection rectangle
          const nodesInRect = nodes.filter((node) => {
            const nodeWidth = 200;
            const nodeHeight = 100; // Approximate
            const nodeRight = node.position.x + nodeWidth;
            const nodeBottom = node.position.y + nodeHeight;

            // Check if node overlaps with selection rectangle
            return (
              node.position.x < selectionRect.x + selectionRect.width &&
              nodeRight > selectionRect.x &&
              node.position.y < selectionRect.y + selectionRect.height &&
              nodeBottom > selectionRect.y
            );
          });

          if (nodesInRect.length > 0) {
            onSelectNodesInRect(nodesInRect.map((n) => n.id));
          }
        }
      }

      setDragState(null);
    },
    [dragState, onNodeDragEnd, setDragState, isConnecting, onConnectionDropOnCanvas, viewport, nodes, onSelectNodesInRect]
  );

  /**
   * Handle canvas click (deselect)
   */
  const handleCanvasClick = useCallback((e: ReactMouseEvent<HTMLDivElement>) => {
    const target = e.target as HTMLElement;

    // Only deselect if clicking directly on canvas background
    if (target === canvasRef.current || target.closest('.canvas-grid')) {
      onCanvasClick();
    }
  }, [onCanvasClick]);

  /**
   * Handle mouse wheel for zoom
   * Matches ai-automation zoom behavior
   */
  const handleWheel = useCallback(
    (e: WheelEvent) => {
      e.preventDefault();

      const rect = canvasRef.current?.getBoundingClientRect();
      if (!rect) return;

      const mouseX = e.clientX - rect.left;
      const mouseY = e.clientY - rect.top;

      // Positive deltaY = zoom out, negative = zoom in
      onZoom(e.deltaY > 0 ? -1 : 1, mouseX, mouseY);
    },
    [onZoom]
  );

  /**
   * Setup wheel listener
   */
  const canvasCallbackRef = useCallback((node: HTMLDivElement | null) => {
    if (node) {
      node.addEventListener('wheel', handleWheel, { passive: false });
      return () => {
        node.removeEventListener('wheel', handleWheel);
      };
    }
  }, [handleWheel]);

  /**
   * Handle Escape key to cancel connection
   */
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isConnecting && onCancelConnection) {
        e.preventDefault();
        onCancelConnection();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isConnecting, onCancelConnection]);

  return (
    <div
      ref={(node) => {
        canvasRef.current = node;
        canvasCallbackRef(node);
      }}
      className="canvas-container relative w-full h-full bg-gray-50 cursor-grab active:cursor-grabbing"
      onMouseDown={handleCanvasMouseDown}
      onMouseMove={handleMouseMove}
      onMouseUp={handleMouseUp}
      onMouseLeave={handleMouseUp}
      onClick={handleCanvasClick}
    >
      {/* Grid background - dot pattern like ai-automation */}
      <div
        className="canvas-grid absolute inset-0 pointer-events-none"
        style={{
          backgroundImage: 'radial-gradient(circle at 1px 1px, rgba(156, 163, 175, 0.4) 1px, transparent 0)',
          backgroundSize: `${20 * viewport.zoom}px ${20 * viewport.zoom}px`,
          backgroundPosition: `${viewport.x}px ${viewport.y}px`,
        }}
      />

      {/* Nodes and connections layer */}
      <div
        className="nodes-layer absolute inset-0"
        style={{
          transform: `translate(${viewport.x}px, ${viewport.y}px) scale(${viewport.zoom})`,
          transformOrigin: '0 0',
        }}
      >
        {/* Groups - rendered first (behind everything) */}
        {groups.map((group) => (
          <GroupContainer
            key={group.id}
            group={group}
            viewport={viewport}
            nodes={nodes}
            isAnyNodeDragging={dragState?.type === 'node'}
            onUpdateGroup={onUpdateGroup || (() => {})}
            onRemoveGroup={onRemoveGroup || (() => {})}
            onMoveGroup={onMoveGroup || (() => {})}
          />
        ))}

        {/* Connection overlay - rendered second (behind nodes) */}
        <ConnectionOverlay
          connections={connections}
          nodes={nodes}
          viewport={viewport}
          selectedConnections={selectedConnections}
          onConnectionClick={onConnectionClick}
          onDeleteConnection={onDeleteConnection}
          isConnecting={isConnecting}
          connectionStart={connectionStart}
        />

        {/* Nodes */}
        {nodes.map((node) => (
          <Node
            key={node.id}
            node={node}
            selected={selectedNodes.includes(node.id)}
            isDragging={dragState?.type === 'node' && dragState.nodeId === node.id}
            isConnecting={isConnecting}
            connectionStart={connectionStart}
            onConnectionStart={onConnectionStart}
            onConnectionEnd={onConnectionEnd}
          />
        ))}
      </div>

      {/* Selection rectangle */}
      {dragState?.type === 'selection' && (
        <div
          className="absolute border-2 border-blue-500 bg-blue-500/10 pointer-events-none z-40"
          style={{
            left: Math.min(dragState.startX, dragState.currentX) - (canvasRef.current?.getBoundingClientRect().left || 0),
            top: Math.min(dragState.startY, dragState.currentY) - (canvasRef.current?.getBoundingClientRect().top || 0),
            width: Math.abs(dragState.currentX - dragState.startX),
            height: Math.abs(dragState.currentY - dragState.startY),
          }}
        />
      )}

      {/* Connection mode indicator */}
      {isConnecting && (
        <div className="absolute top-4 left-1/2 -translate-x-1/2 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 z-50">
          <div className="w-2 h-2 rounded-full bg-white animate-pulse" />
          <span className="text-sm font-medium">
            Drag to a node input port to connect • Press Escape to cancel
          </span>
        </div>
      )}

      {/* Zoom controls */}
      <div className="absolute bottom-4 right-4 flex flex-col gap-2 bg-white rounded-lg shadow-lg p-2">
        <button
          onClick={() => onZoom(0.1)}
          className="px-3 py-2 hover:bg-gray-100 rounded text-sm font-medium"
          title="Zoom in"
        >
          +
        </button>
        <button
          onClick={() => onZoom(-0.1)}
          className="px-3 py-2 hover:bg-gray-100 rounded text-sm font-medium"
          title="Zoom out"
        >
          −
        </button>
        <button
          onClick={() => onZoom(0)}
          className="px-3 py-2 hover:bg-gray-100 rounded text-xs"
          title="Reset zoom"
        >
          {Math.round(viewport.zoom * 100)}%
        </button>
      </div>
    </div>
  );
}
