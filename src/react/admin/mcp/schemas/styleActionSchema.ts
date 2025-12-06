import { z } from 'zod';
import {
  NodeTypeSchema,
  StylePropertySchema,
} from '../../schemas/styles';

// =============================================================================
// Individual Action Schemas
// =============================================================================

const GetGlobalStyleAction = z.object({
  action: z.literal('getGlobalStyle'),
  nodeType: NodeTypeSchema,
});

const SetGlobalStyleAction = z.object({
  action: z.literal('setGlobalStyle'),
  nodeType: NodeTypeSchema,
  updates: StylePropertySchema.partial(),
});

const SetGlobalPropertyAction = z.object({
  action: z.literal('setGlobalProperty'),
  nodeType: NodeTypeSchema,
  property: z.string(),
  value: z.unknown(),
});

const GetNodeCapabilitiesAction = z.object({
  action: z.literal('getNodeCapabilities'),
  nodeType: NodeTypeSchema,
});

const ListNodeTypesAction = z.object({
  action: z.literal('listNodeTypes'),
});

const ListElementNodesAction = z.object({
  action: z.literal('listElementNodes'),
  elementType: z.string(),
});

const GetElementStyleAction = z.object({
  action: z.literal('getElementStyle'),
  elementId: z.string(),
  nodeType: NodeTypeSchema.optional(),
});

const SetElementStyleOverrideAction = z.object({
  action: z.literal('setElementStyleOverride'),
  elementId: z.string(),
  nodeType: NodeTypeSchema,
  property: z.string(),
  value: z.unknown(),
});

const SetElementStyleOverridesAction = z.object({
  action: z.literal('setElementStyleOverrides'),
  elementId: z.string(),
  nodeType: NodeTypeSchema,
  overrides: StylePropertySchema.partial(),
});

const RemoveElementStyleOverrideAction = z.object({
  action: z.literal('removeElementStyleOverride'),
  elementId: z.string(),
  nodeType: NodeTypeSchema,
  property: z.string(),
});

const PromoteToGlobalAction = z.object({
  action: z.literal('promoteToGlobal'),
  elementId: z.string(),
  nodeType: NodeTypeSchema.optional(), // If omitted, promotes all nodes
});

const ResetToGlobalAction = z.object({
  action: z.literal('resetToGlobal'),
  elementId: z.string(),
  nodeType: NodeTypeSchema.optional(), // If omitted, resets all nodes
});

const ResetGlobalToDefaultsAction = z.object({
  action: z.literal('resetGlobalToDefaults'),
  nodeType: NodeTypeSchema.optional(), // If omitted, resets all node types
});

const ExportStylesAction = z.object({
  action: z.literal('exportStyles'),
});

const ImportStylesAction = z.object({
  action: z.literal('importStyles'),
  styles: z.string(), // JSON string
});

// =============================================================================
// Combined Action Schema
// =============================================================================

export const StyleActionSchema = z.discriminatedUnion('action', [
  // Global style queries
  GetGlobalStyleAction,
  SetGlobalStyleAction,
  SetGlobalPropertyAction,
  GetNodeCapabilitiesAction,
  ListNodeTypesAction,
  ListElementNodesAction,
  ResetGlobalToDefaultsAction,

  // Element style operations
  GetElementStyleAction,
  SetElementStyleOverrideAction,
  SetElementStyleOverridesAction,
  RemoveElementStyleOverrideAction,
  PromoteToGlobalAction,
  ResetToGlobalAction,

  // Import/Export
  ExportStylesAction,
  ImportStylesAction,
]);

export type StyleAction = z.infer<typeof StyleActionSchema>;

// =============================================================================
// Response Types
// =============================================================================

export interface StyleActionResponse {
  success: boolean;
  data?: unknown;
  error?: string;
}
