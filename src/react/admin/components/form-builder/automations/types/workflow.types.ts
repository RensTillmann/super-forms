/**
 * Type definitions for Visual Workflow Builder
 * Phase 22: Integrate ai-automation Visual Workflow Builder
 */

import type { LucideIcon } from 'lucide-react';

/**
 * Workflow Node - Represents a single node in the visual workflow
 */
export interface WorkflowNode {
  id: string;
  type: string; // Event ID (e.g., 'form.submitted') or Action ID (e.g., 'send_email')
  position: { x: number; y: number };
  config: Record<string, any>; // Node-specific configuration
  selected: boolean;
  zIndex: number;
  isGroupDragging?: boolean;
  groupDragOffset?: { x: number; y: number };
}

/**
 * Workflow Connection - Represents an edge connecting two nodes
 */
export interface WorkflowConnection {
  id: string;
  from: string; // Source node ID
  fromOutput: string; // Output port name
  to: string; // Target node ID
  toInput: string; // Input port name
  selected: boolean;
}

/**
 * Workflow Group - Logical grouping of nodes for organization
 */
export interface WorkflowGroup {
  id: string;
  name: string;
  nodeIds: string[];
  bounds: {
    x: number;
    y: number;
    width: number;
    height: number;
  };
  color: string;
  zIndex: number;
}

/**
 * Viewport - Canvas pan/zoom state
 */
export interface Viewport {
  x: number;
  y: number;
  zoom: number;
}

/**
 * Node Type Definition - Metadata for a node type
 */
export interface NodeTypeDefinition {
  id: string;
  name: string;
  category: 'event' | 'action' | 'condition';
  color: string;
  icon: LucideIcon;
  description: string;
  inputs: string[]; // Input port names
  outputs: string[]; // Output port names
  config: Record<string, any>; // Default configuration
}

/**
 * Workflow Template - Pre-built workflow for common use cases
 */
export interface WorkflowTemplate {
  id: string;
  name: string;
  description: string;
  category: string;
  nodes: WorkflowNode[];
  connections: WorkflowConnection[];
  groups?: WorkflowGroup[];
}

/**
 * Workflow Graph - Complete workflow state
 */
export interface WorkflowGraph {
  nodes: WorkflowNode[];
  connections: WorkflowConnection[];
  groups: WorkflowGroup[];
  viewport: Viewport;
}

/**
 * Node Editor State - Complete state for the visual editor
 */
export interface NodeEditorState extends WorkflowGraph {
  selectedNodes: string[];
  selectedConnections: string[];
  dragState: DragState | null;
  history: WorkflowGraph[];
  historyIndex: number;
}

/**
 * Drag State - Tracks current drag operation
 */
export interface DragState {
  type: 'node' | 'viewport' | 'selection' | 'connection';
  startX: number;
  startY: number;
  currentX: number;
  currentY: number;
  nodeId?: string;
  fromNode?: string;
  fromOutput?: string;
}

/**
 * Super Forms Trigger Data - Mapped to WordPress database
 *
 * Node-level scope architecture: scope is configured in event nodes within workflow_graph JSON.
 * The trigger table only stores: id, trigger_name, workflow_type, workflow_graph, enabled, timestamps.
 */
export interface SuperFormsTrigger {
  id?: number;
  trigger_name: string;
  workflow_type: 'visual' | 'code';
  workflow_graph: string | WorkflowGraph; // JSON string or decoded object
  enabled: boolean | number; // 1/0 from database, true/false from JS
  created_at?: string;
  updated_at?: string;
}

/**
 * Window data passed from PHP
 */
export interface SFUIData {
  currentPage: string;
  activeTab?: string;
  formId?: number;
  formTitle?: string;
  restUrl: string;
  restNonce: string;
  forms?: Array<{ id: number; title: string }>;
  events?: Array<{ id: string; name: string; category: string }>;
  actionTypes?: Array<{ id: string; name: string; category: string }>;
  triggers?: SuperFormsTrigger[];
}

/**
 * Extend window object for TypeScript
 */
declare global {
  interface Window {
    sfuiData: SFUIData;
  }
}
