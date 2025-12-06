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
// Theme Actions
// =============================================================================

const ThemeCategorySchema = z.enum([
  'light',
  'dark',
  'minimal',
  'corporate',
  'playful',
  'highContrast',
]);

const ListThemesAction = z.object({
  action: z.literal('listThemes'),
  includeSystem: z.boolean().optional().default(true),
  includeStubs: z.boolean().optional().default(true),
  category: ThemeCategorySchema.optional(),
});

const GetThemeAction = z.object({
  action: z.literal('getTheme'),
  themeId: z.union([z.string(), z.number()]),
});

const ApplyThemeAction = z.object({
  action: z.literal('applyTheme'),
  themeId: z.union([z.string(), z.number()]), // ID or slug
  formId: z.number().optional(), // If omitted, applies to local registry only
});

const CreateThemeAction = z.object({
  action: z.literal('createTheme'),
  name: z.string().min(1).max(100),
  description: z.string().max(500).optional(),
  category: ThemeCategorySchema.optional().default('light'),
});

const DeleteThemeAction = z.object({
  action: z.literal('deleteTheme'),
  themeId: z.union([z.string(), z.number()]),
});

const GenerateThemeAction = z.object({
  action: z.literal('generateTheme'),
  name: z.string().min(1).max(100),
  prompt: z.string().max(500).optional(), // "warm friendly", "corporate blue"
  baseColor: z
    .string()
    .regex(/^#[0-9A-Fa-f]{6}$/)
    .optional(), // Brand color
  density: z
    .enum(['compact', 'comfortable', 'spacious'])
    .optional()
    .default('comfortable'),
  cornerStyle: z
    .enum(['sharp', 'rounded', 'pill'])
    .optional()
    .default('rounded'),
  contrastPreference: z
    .enum(['high', 'standard', 'soft'])
    .optional()
    .default('standard'),
  save: z.boolean().optional().default(true), // Persist to database?
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

  // Theme operations
  ListThemesAction,
  GetThemeAction,
  ApplyThemeAction,
  CreateThemeAction,
  DeleteThemeAction,
  GenerateThemeAction,
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
