/**
 * ConnectionOverlay Component
 * SVG overlay for rendering connections between nodes
 * Uses cubic Bezier curves for smooth paths
 * Based on ai-automation architecture with connection preview, snap-to-port,
 * animated electric lights, connection labels, and delete hints
 */

import { useCallback, useEffect, useState, useMemo } from 'react';
import type { WorkflowConnection, WorkflowNode } from '../types/workflow.types';
import { SUPER_FORMS_NODE_TYPES } from '../data/superFormsNodeTypes';

interface ConnectionOverlayProps {
  connections: WorkflowConnection[];
  nodes: WorkflowNode[];
  viewport: { x: number; y: number; zoom: number };
  selectedConnections: string[];
  onConnectionClick?: (connectionId: string) => void;
  onDeleteConnection?: (connectionId: string) => void;
  isConnecting?: boolean;
  connectionStart?: { nodeId: string; outputName: string } | null;
}

export function ConnectionOverlay({
  connections,
  nodes,
  viewport,
  selectedConnections,
  onConnectionClick,
  onDeleteConnection,
  isConnecting = false,
  connectionStart = null,
}: ConnectionOverlayProps) {
  const [mousePos, setMousePos] = useState({ x: 0, y: 0 });
  const [hoveredConnection, setHoveredConnection] = useState<string | null>(null);

  /**
   * Get port position for a node (output or input)
   */
  const getPortPosition = useCallback(
    (nodeId: string, portName: string, isOutput = false) => {
      const node = nodes.find((n) => n.id === nodeId);
      if (!node) return { x: 0, y: 0 };

      const nodeType = SUPER_FORMS_NODE_TYPES[node.type];
      if (!nodeType) return { x: 0, y: 0 };

      const ports = isOutput ? nodeType.outputs : nodeType.inputs;
      const portIndex = ports.findIndex((p) => p === portName);

      const nodeWidth = 200;
      const nodeHeight = 100; // Approximate node height

      const position = isOutput
        ? {
            x: node.position.x + nodeWidth,
            y: node.position.y + nodeHeight / 2 + portIndex * 20,
          }
        : {
            x: node.position.x,
            y: node.position.y + nodeHeight / 2 + portIndex * 20,
          };

      return position;
    },
    [nodes]
  );

  /**
   * Generate SVG path for connection using cubic Bezier curve
   */
  const createConnectionPath = useCallback(
    (startPos: { x: number; y: number }, endPos: { x: number; y: number }) => {
      const dx = endPos.x - startPos.x;

      // Control points for smooth curve
      const controlOffset = Math.max(50, Math.abs(dx) * 0.5);
      const cp1x = startPos.x + controlOffset;
      const cp1y = startPos.y;
      const cp2x = endPos.x - controlOffset;
      const cp2y = endPos.y;

      return `M ${startPos.x} ${startPos.y} C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${endPos.x} ${endPos.y}`;
    },
    []
  );

  /**
   * Find nearby input port for snapping during connection creation
   */
  const findNearbyConnectionDot = useCallback(
    (mouseX: number, mouseY: number) => {
      const snapDistance = 30; // pixels - larger for easier snapping
      let closestDot = null;
      let closestDistance = snapDistance;

      nodes.forEach((node) => {
        if (node.id === connectionStart?.nodeId) return; // Skip source node

        const nodeType = SUPER_FORMS_NODE_TYPES[node.type];
        if (!nodeType?.inputs || nodeType.inputs.length === 0) return;

        nodeType.inputs.forEach((input) => {
          const dotPos = getPortPosition(node.id, input, false);

          const distance = Math.sqrt(
            Math.pow(mouseX - dotPos.x, 2) + Math.pow(mouseY - dotPos.y, 2)
          );

          if (distance < closestDistance) {
            closestDistance = distance;
            closestDot = {
              nodeId: node.id,
              inputName: input,
              position: dotPos,
            };
          }
        });
      });

      return closestDot;
    },
    [nodes, connectionStart, getPortPosition]
  );

  /**
   * Update mouse position during connection creation
   * Track mouse in canvas coordinates (relative to the transformed nodes-layer)
   */
  useEffect(() => {
    if (!isConnecting) return;

    const handleMouseMove = (e: MouseEvent) => {
      const canvasContainer = document.querySelector('.canvas-container');
      if (!canvasContainer) return;

      const containerRect = canvasContainer.getBoundingClientRect();

      // Convert screen coordinates to canvas coordinates using viewport prop
      const canvasX = (e.clientX - containerRect.left - viewport.x) / viewport.zoom;
      const canvasY = (e.clientY - containerRect.top - viewport.y) / viewport.zoom;

      // Check for nearby connection dots to snap to
      const nearbyDot = findNearbyConnectionDot(canvasX, canvasY);
      if (nearbyDot) {
        setMousePos(nearbyDot.position);
      } else {
        setMousePos({ x: canvasX, y: canvasY });
      }
    };

    // Listen on document for global mouse tracking
    document.addEventListener('mousemove', handleMouseMove);

    return () => {
      document.removeEventListener('mousemove', handleMouseMove);
    };
  }, [isConnecting, viewport, findNearbyConnectionDot]);

  /**
   * Initialize mouse position when connection starts
   */
  useEffect(() => {
    if (isConnecting && connectionStart) {
      const startPos = getPortPosition(connectionStart.nodeId, connectionStart.outputName, true);
      setMousePos(startPos);
    }
  }, [isConnecting, connectionStart, getPortPosition]);

  /**
   * Memoized connection paths
   */
  const connectionPaths = useMemo(() => {
    return connections.map((connection) => {
      const startPos = getPortPosition(connection.from, connection.fromOutput, true);
      const endPos = getPortPosition(connection.to, connection.toInput, false);

      return {
        ...connection,
        path: createConnectionPath(startPos, endPos),
        startPos,
        endPos,
      };
    });
  }, [connections, getPortPosition, createConnectionPath]);

  /**
   * Connection preview path
   */
  const previewPath = useMemo(() => {
    if (!isConnecting || !connectionStart) return null;

    const startPos = getPortPosition(connectionStart.nodeId, connectionStart.outputName, true);
    const endPos = { x: mousePos.x, y: mousePos.y };

    return createConnectionPath(startPos, endPos);
  }, [isConnecting, connectionStart, getPortPosition, createConnectionPath, mousePos]);

  /**
   * Handle connection click - delete if handler provided
   */
  const handleConnectionClick = (e: React.MouseEvent, connectionId: string) => {
    e.stopPropagation();
    if (onDeleteConnection) {
      onDeleteConnection(connectionId);
    } else {
      onConnectionClick?.(connectionId);
    }
  };

  return (
    <svg
      className="connection-overlay absolute pointer-events-none z-10"
      style={{
        // No transform needed - inherits from parent nodes-layer
        left: 0,
        top: 0,
        width: '100%',
        height: '100%',
        overflow: 'visible',
      }}
    >
      <defs>
        {/* Electric glow filter for the light source */}
        <filter id="electric-glow" x="-50%" y="-50%" width="200%" height="200%">
          <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
          <feGaussianBlur stdDeviation="6" result="outerGlow"/>
          <feGaussianBlur stdDeviation="12" result="farGlow"/>
          <feMerge>
            <feMergeNode in="farGlow"/>
            <feMergeNode in="outerGlow"/>
            <feMergeNode in="coloredBlur"/>
            <feMergeNode in="SourceGraphic"/>
          </feMerge>
        </filter>

        {/* Electric light source gradient */}
        <radialGradient id="electric-light" cx="50%" cy="50%" r="50%">
          <stop offset="0%" style={{stopColor: '#ffffff', stopOpacity: 1}} />
          <stop offset="30%" style={{stopColor: '#bfdbfe', stopOpacity: 0.9}} />
          <stop offset="60%" style={{stopColor: '#3b82f6', stopOpacity: 0.6}} />
          <stop offset="100%" style={{stopColor: '#1d4ed8', stopOpacity: 0}} />
        </radialGradient>

        {/* Glow filter for connections */}
        <filter id="glow">
          <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
          <feMerge>
            <feMergeNode in="coloredBlur"/>
            <feMergeNode in="SourceGraphic"/>
          </feMerge>
        </filter>

        {/* Hover glow filter */}
        <filter id="hover-glow">
          <feGaussianBlur stdDeviation="4" result="coloredBlur"/>
          <feMerge>
            <feMergeNode in="coloredBlur"/>
            <feMergeNode in="SourceGraphic"/>
          </feMerge>
        </filter>

        {/* Arrow marker for connection end */}
        <marker
          id="arrowhead"
          markerWidth="10"
          markerHeight="10"
          refX="9"
          refY="3"
          orient="auto"
          markerUnits="strokeWidth"
        >
          <path d="M0,0 L0,6 L9,3 z" fill="#6b7280" />
        </marker>

        {/* Arrow marker for selected connections */}
        <marker
          id="arrowhead-selected"
          markerWidth="10"
          markerHeight="10"
          refX="9"
          refY="3"
          orient="auto"
          markerUnits="strokeWidth"
        >
          <path d="M0,0 L0,6 L9,3 z" fill="#3b82f6" />
        </marker>

        {/* Arrow marker for hovered connections */}
        <marker
          id="arrowhead-hovered"
          markerWidth="10"
          markerHeight="10"
          refX="9"
          refY="3"
          orient="auto"
          markerUnits="strokeWidth"
        >
          <path d="M0,0 L0,6 L9,3 z" fill="#ef4444" />
        </marker>

        {/* Arrow marker for connection preview */}
        <marker
          id="arrowhead-preview"
          markerWidth="10"
          markerHeight="10"
          refX="9"
          refY="3"
          orient="auto"
          markerUnits="strokeWidth"
        >
          <path d="M0,0 L0,6 L9,3 z" fill="#10b981" />
        </marker>
      </defs>

      {/* Render existing connections */}
      {connectionPaths.map((connection) => {
        const isSelected = selectedConnections.includes(connection.id);
        const isHovered = hoveredConnection === connection.id;

        return (
          <g
            key={connection.id}
            onMouseEnter={() => setHoveredConnection(connection.id)}
            onMouseLeave={() => setHoveredConnection(null)}
          >
            {/* Invisible wider path for easier clicking */}
            <path
              d={connection.path}
              stroke="transparent"
              strokeWidth="20"
              fill="none"
              className="pointer-events-auto cursor-pointer"
              onClick={(e) => handleConnectionClick(e, connection.id)}
            />

            {/* Visible connection path */}
            <path
              d={connection.path}
              stroke={isSelected ? '#3b82f6' : isHovered ? '#ef4444' : '#6b7280'}
              strokeWidth={isSelected ? '3' : '2'}
              strokeLinecap="round"
              strokeLinejoin="round"
              fill="none"
              markerEnd={isSelected ? 'url(#arrowhead-selected)' : isHovered ? 'url(#arrowhead-hovered)' : 'url(#arrowhead)'}
              className="pointer-events-none"
              style={{
                filter: isSelected ? 'url(#glow)' : isHovered ? 'url(#hover-glow)' : 'none',
              }}
            />

            {/* Electric light source moving along path */}
            <g className="pointer-events-none">
              <ellipse
                rx="10"
                ry="3"
                fill="url(#electric-light)"
                filter="url(#electric-glow)"
                style={{ opacity: 0.9 }}
              >
                <animateMotion
                  dur="2s"
                  repeatCount="indefinite"
                  path={connection.path}
                  rotate="auto"
                />
                <animate
                  attributeName="opacity"
                  values="0.6;1;0.6"
                  dur="0.4s"
                  repeatCount="indefinite"
                />
              </ellipse>

              {/* Inner bright core */}
              <ellipse
                rx="5"
                ry="1.5"
                fill="#ffffff"
                style={{ opacity: 0.85 }}
              >
                <animateMotion
                  dur="2s"
                  repeatCount="indefinite"
                  path={connection.path}
                  rotate="auto"
                />
                <animate
                  attributeName="opacity"
                  values="0.7;1;0.7"
                  dur="0.25s"
                  repeatCount="indefinite"
                />
              </ellipse>
            </g>

            {/* Connection label */}
            <text
              x={(connection.startPos.x + connection.endPos.x) / 2}
              y={(connection.startPos.y + connection.endPos.y) / 2 - 8}
              fill={isHovered ? '#ef4444' : '#9ca3af'}
              fontSize="10"
              textAnchor="middle"
              className="pointer-events-none select-none"
            >
              {connection.fromOutput}
            </text>

            {/* Click to delete hint on hover */}
            {isHovered && (
              <text
                x={(connection.startPos.x + connection.endPos.x) / 2}
                y={(connection.startPos.y + connection.endPos.y) / 2 + 12}
                fill="#ef4444"
                fontSize="9"
                textAnchor="middle"
                className="pointer-events-none select-none"
                style={{ fontWeight: 500 }}
              >
                Click to delete
              </text>
            )}
          </g>
        );
      })}

      {/* Connection preview while dragging */}
      {isConnecting && previewPath && (
        <path
          d={previewPath}
          stroke="#10b981"
          strokeWidth="2"
          fill="none"
          strokeDasharray="5,5"
          markerEnd="url(#arrowhead-preview)"
          className="pointer-events-none"
          style={{
            animation: 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
          }}
        />
      )}
    </svg>
  );
}
