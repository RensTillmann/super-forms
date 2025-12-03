/**
 * GroupContainer Component
 * Visual container for grouping nodes in the workflow
 * Supports dragging, resizing, and automatic node membership
 */

import { useState, useRef, useCallback, useEffect, MouseEvent } from 'react';
import type { WorkflowGroup, WorkflowNode, Viewport } from '../types/workflow.types';

interface GroupContainerProps {
  group: WorkflowGroup;
  viewport: Viewport;
  nodes: WorkflowNode[];
  isAnyNodeDragging?: boolean;
  onUpdateGroup: (groupId: string, updates: Partial<WorkflowGroup>) => void;
  onRemoveGroup: (groupId: string) => void;
  onMoveGroup: (groupId: string, deltaX: number, deltaY: number, phase: 'start' | 'move' | 'end') => void;
}

export function GroupContainer({
  group,
  viewport,
  nodes,
  isAnyNodeDragging = false,
  onUpdateGroup,
  onRemoveGroup,
  onMoveGroup,
}: GroupContainerProps) {
  const { id, name, bounds, color } = group;
  const [isHovered, setIsHovered] = useState(false);
  const [isDragging, setIsDragging] = useState(false);
  const [isResizing, setIsResizing] = useState(false);
  const [resizeHandle, setResizeHandle] = useState<'bottom-left' | 'bottom-right' | null>(null);
  const [dragOffset, setDragOffset] = useState({ x: 0, y: 0 });
  const [resizeOffset, setResizeOffset] = useState({ width: 0, height: 0, x: 0, y: 0 });

  const dragOffsetRef = useRef({ x: 0, y: 0 });
  const resizeOffsetRef = useRef({ width: 0, height: 0, x: 0, y: 0 });
  const dragStartRef = useRef({ x: 0, y: 0, initialBounds: bounds });
  const resizeStartRef = useRef({ x: 0, y: 0, initialBounds: bounds });
  const lastBoundsRef = useRef(bounds);

  // Reset offsets when bounds change externally
  useEffect(() => {
    const boundsChanged =
      bounds.x !== lastBoundsRef.current.x ||
      bounds.y !== lastBoundsRef.current.y ||
      bounds.width !== lastBoundsRef.current.width ||
      bounds.height !== lastBoundsRef.current.height;

    if (boundsChanged && !isDragging && !isResizing) {
      if (dragOffsetRef.current.x !== 0 || dragOffsetRef.current.y !== 0) {
        setDragOffset({ x: 0, y: 0 });
        dragOffsetRef.current = { x: 0, y: 0 };
      }

      if (
        resizeOffsetRef.current.width !== 0 ||
        resizeOffsetRef.current.height !== 0 ||
        resizeOffsetRef.current.x !== 0 ||
        resizeOffsetRef.current.y !== 0
      ) {
        setResizeOffset({ width: 0, height: 0, x: 0, y: 0 });
        resizeOffsetRef.current = { width: 0, height: 0, x: 0, y: 0 };
      }
    }

    lastBoundsRef.current = bounds;
  }, [bounds, isDragging, isResizing]);

  /**
   * Handle group name change
   */
  const handleNameChange = (e: React.KeyboardEvent<HTMLInputElement> | React.FocusEvent<HTMLInputElement>) => {
    if (e.type === 'blur' || (e as React.KeyboardEvent).key === 'Enter') {
      const newName = (e.target as HTMLInputElement).value.trim();
      if (newName && newName !== name) {
        onUpdateGroup(id, { name: newName });
      }
      (e.target as HTMLInputElement).blur();
    }
  };

  /**
   * Handle group deletion
   */
  const handleDeleteGroup = (e: MouseEvent) => {
    e.stopPropagation();
    onRemoveGroup(id);
  };

  /**
   * Handle group drag start
   */
  const handleMouseDown = useCallback(
    (e: MouseEvent) => {
      // Only start dragging if clicking on the group background, not controls
      if ((e.target as HTMLElement).tagName === 'INPUT' || (e.target as HTMLElement).tagName === 'BUTTON') {
        return;
      }

      e.stopPropagation();
      e.preventDefault();

      setIsDragging(true);

      dragStartRef.current = {
        x: e.clientX,
        y: e.clientY,
        initialBounds: { ...bounds },
      };

      // Notify parent to start group drag
      onMoveGroup(id, 0, 0, 'start');

      const handleMouseMove = (moveEvent: globalThis.MouseEvent) => {
        if (!dragStartRef.current.initialBounds) return;

        // Calculate total movement from start position
        const totalDeltaX = (moveEvent.clientX - dragStartRef.current.x) / viewport.zoom;
        const totalDeltaY = (moveEvent.clientY - dragStartRef.current.y) / viewport.zoom;

        // Snap to grid
        const snappedDeltaX = Math.round(totalDeltaX / 20) * 20;
        const snappedDeltaY = Math.round(totalDeltaY / 20) * 20;

        // Update visual offset for immediate feedback
        const newOffset = { x: snappedDeltaX, y: snappedDeltaY };
        setDragOffset(newOffset);
        dragOffsetRef.current = newOffset;

        // Notify parent to move nodes during drag
        onMoveGroup(id, snappedDeltaX, snappedDeltaY, 'move');
      };

      const handleMouseUp = () => {
        const finalOffset = dragOffsetRef.current;
        setIsDragging(false);

        // Apply the final position
        if (finalOffset.x !== 0 || finalOffset.y !== 0) {
          onMoveGroup(id, finalOffset.x, finalOffset.y, 'end');
        }

        // Reset drag state
        setDragOffset({ x: 0, y: 0 });
        dragOffsetRef.current = { x: 0, y: 0 };
        dragStartRef.current = { x: 0, y: 0, initialBounds: bounds };

        document.removeEventListener('mousemove', handleMouseMove);
        document.removeEventListener('mouseup', handleMouseUp);
      };

      document.addEventListener('mousemove', handleMouseMove);
      document.addEventListener('mouseup', handleMouseUp);
    },
    [bounds, viewport.zoom, onMoveGroup, id]
  );

  /**
   * Handle resize
   */
  const handleResizeMouseDown = useCallback(
    (e: MouseEvent, handle: 'bottom-left' | 'bottom-right') => {
      e.stopPropagation();
      e.preventDefault();

      setIsResizing(true);
      setResizeHandle(handle);

      resizeStartRef.current = {
        x: e.clientX,
        y: e.clientY,
        initialBounds: { ...bounds },
      };

      const handleResizeMouseMove = (moveEvent: globalThis.MouseEvent) => {
        if (!resizeStartRef.current.initialBounds) return;

        const totalDeltaX = (moveEvent.clientX - resizeStartRef.current.x) / viewport.zoom;
        const totalDeltaY = (moveEvent.clientY - resizeStartRef.current.y) / viewport.zoom;

        const initialBounds = resizeStartRef.current.initialBounds;
        let newBounds = { ...initialBounds };

        if (handle === 'bottom-right') {
          newBounds.width = Math.max(100, initialBounds.width + totalDeltaX);
          newBounds.height = Math.max(80, initialBounds.height + totalDeltaY);
        } else if (handle === 'bottom-left') {
          const newWidth = Math.max(100, initialBounds.width - totalDeltaX);
          const widthDiff = newWidth - initialBounds.width;
          newBounds.x = initialBounds.x - widthDiff;
          newBounds.width = newWidth;
          newBounds.height = Math.max(80, initialBounds.height + totalDeltaY);
        }

        // Snap to grid
        newBounds.x = Math.round(newBounds.x / 20) * 20;
        newBounds.y = Math.round(newBounds.y / 20) * 20;
        newBounds.width = Math.round(newBounds.width / 20) * 20;
        newBounds.height = Math.round(newBounds.height / 20) * 20;

        // Update visual offset
        const offset = {
          x: newBounds.x - bounds.x,
          y: newBounds.y - bounds.y,
          width: newBounds.width - bounds.width,
          height: newBounds.height - bounds.height,
        };

        setResizeOffset(offset);
        resizeOffsetRef.current = offset;
      };

      const handleResizeMouseUp = () => {
        const finalOffset = resizeOffsetRef.current;
        setIsResizing(false);
        setResizeHandle(null);

        if (
          finalOffset.width !== 0 ||
          finalOffset.height !== 0 ||
          finalOffset.x !== 0 ||
          finalOffset.y !== 0
        ) {
          const newBounds = {
            x: bounds.x + finalOffset.x,
            y: bounds.y + finalOffset.y,
            width: bounds.width + finalOffset.width,
            height: bounds.height + finalOffset.height,
          };

          // Find nodes within the resized bounds
          const nodesInBounds = nodes.filter((node) => {
            const nodeRight = node.position.x + 200;
            const nodeBottom = node.position.y + 100;

            return (
              node.position.x >= newBounds.x &&
              nodeRight <= newBounds.x + newBounds.width &&
              node.position.y >= newBounds.y &&
              nodeBottom <= newBounds.y + newBounds.height
            );
          });

          onUpdateGroup(id, {
            bounds: newBounds,
            nodeIds: nodesInBounds.map((node) => node.id),
          });
        }

        // Reset resize state
        setResizeOffset({ width: 0, height: 0, x: 0, y: 0 });
        resizeOffsetRef.current = { width: 0, height: 0, x: 0, y: 0 };
        resizeStartRef.current = { x: 0, y: 0, initialBounds: bounds };

        document.removeEventListener('mousemove', handleResizeMouseMove);
        document.removeEventListener('mouseup', handleResizeMouseUp);
      };

      document.addEventListener('mousemove', handleResizeMouseMove);
      document.addEventListener('mouseup', handleResizeMouseUp);
    },
    [bounds, viewport.zoom, onUpdateGroup, id, nodes]
  );

  // Check if any nodes in this group are being dragged
  const hasNodeBeingDragged =
    nodes &&
    group.nodeIds.some((nodeId) => {
      const node = nodes.find((n) => n.id === nodeId);
      return node && node.isGroupDragging;
    });

  return (
    <div
      className={`absolute rounded-lg border group-container ${
        isDragging || isResizing || hasNodeBeingDragged || isAnyNodeDragging
          ? ''
          : 'transition-all duration-200'
      }`}
      style={{
        left: bounds.x + dragOffset.x + resizeOffset.x,
        top: bounds.y + dragOffset.y + resizeOffset.y,
        width: bounds.width + resizeOffset.width,
        height: bounds.height + resizeOffset.height,
        borderColor: color || 'rgba(59, 130, 246, 0.3)',
        backgroundColor: 'rgba(59, 130, 246, 0.05)',
        borderStyle: 'dashed',
        borderWidth: '2px',
        zIndex: group.zIndex || -1,
        pointerEvents: 'auto',
      }}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      {/* Group header with name and drag handle */}
      <div
        className="absolute -top-7 left-0 flex items-center gap-1 pointer-events-auto"
        style={{ zIndex: 1 }}
      >
        {/* Drag handle */}
        <div
          className={`cursor-move px-1 py-1 rounded text-center transition-all duration-200 flex items-center justify-center ${
            isHovered ? 'bg-blue-100 border border-blue-300' : 'bg-transparent border border-transparent'
          }`}
          style={{
            width: '24px',
            height: '24px',
          }}
          onMouseDown={handleMouseDown}
          title="Drag to move group"
        >
          <span className="text-blue-500 text-sm leading-none">⋮⋮</span>
        </div>

        <input
          type="text"
          defaultValue={name}
          className={`text-gray-700 text-sm px-2 py-1 rounded min-w-0 transition-all duration-200 outline-none ${
            isHovered ? 'border border-blue-300 bg-blue-50' : 'border border-transparent bg-transparent'
          }`}
          style={{
            height: '24px',
            minWidth: '80px',
          }}
          onKeyDown={handleNameChange}
          onBlur={handleNameChange}
          onClick={(e) => e.stopPropagation()}
          onMouseDown={(e) => e.stopPropagation()}
        />

        {/* Delete group button */}
        <button
          onClick={handleDeleteGroup}
          onMouseDown={(e) => e.stopPropagation()}
          className={`text-red-400 hover:text-red-500 text-sm px-1 py-1 rounded transition-all duration-200 flex items-center justify-center ${
            isHovered ? 'border border-red-300 bg-red-50 opacity-100' : 'border border-transparent bg-transparent opacity-0'
          }`}
          style={{
            width: '24px',
            height: '24px',
          }}
          title="Delete group"
        >
          ×
        </button>
      </div>

      {/* Resize handles */}
      {isHovered && (
        <>
          {/* Bottom-right resize handle */}
          <div
            className="absolute bottom-0 right-0 w-4 h-4 cursor-nw-resize bg-blue-200 border border-blue-400 rounded-tl-lg"
            style={{
              transform: 'translate(2px, 2px)',
              zIndex: 2,
            }}
            onMouseDown={(e) => handleResizeMouseDown(e, 'bottom-right')}
            title="Resize group"
          >
            <div className="absolute inset-1 border-r border-b border-blue-500" />
          </div>

          {/* Bottom-left resize handle */}
          <div
            className="absolute bottom-0 left-0 w-4 h-4 cursor-ne-resize bg-blue-200 border border-blue-400 rounded-tr-lg"
            style={{
              transform: 'translate(-2px, 2px)',
              zIndex: 2,
            }}
            onMouseDown={(e) => handleResizeMouseDown(e, 'bottom-left')}
            title="Resize group"
          >
            <div className="absolute inset-1 border-l border-b border-blue-500" />
          </div>
        </>
      )}

      {/* Drag indicator */}
      {isDragging && (
        <div className="absolute inset-0 bg-blue-500 bg-opacity-10 rounded-lg border-2 border-blue-400 border-opacity-50 pointer-events-none" />
      )}

      {/* Resize indicator */}
      {isResizing && (
        <div className="absolute inset-0 bg-blue-500 bg-opacity-10 rounded-lg border-2 border-blue-400 border-opacity-50 pointer-events-none" />
      )}
    </div>
  );
}
