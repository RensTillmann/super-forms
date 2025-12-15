import { z } from 'zod';

// =============================================================================
// Button Action Types
// =============================================================================

export const ButtonActionTypeSchema = z.enum([
  'submit',
  'save_state',
  'reset',
  'navigate',
  'trigger_automation',
  'open_overlay',
  'toggle_visibility',
  'copy_to_clipboard',
]);

export const ButtonVariantSchema = z.enum([
  'primary',
  'secondary',
  'outline',
  'ghost',
  'link',
  'destructive',
]);

export const ButtonSizeSchema = z.enum(['xs', 'sm', 'md', 'lg', 'xl']);

export const OverlayTypeSchema = z.enum([
  'modal',
  'dialog',
  'drawer',
  'tray',
  'sheet',
  'popup',
]);

export const NavigateDirectionSchema = z.enum([
  'next',
  'previous',
  'first',
  'last',
  'specific',
]);

// =============================================================================
// Individual Action Schemas
// =============================================================================

/**
 * Add a new button element to the form
 */
const AddButtonAction = z.object({
  action: z.literal('addButton'),
  formId: z.number(),
  buttonText: z.string(),
  actionType: ButtonActionTypeSchema.default('submit'),
  // Optional common properties
  name: z.string().optional(),
  variant: ButtonVariantSchema.optional(),
  size: ButtonSizeSchema.optional(),
  icon: z.string().optional(),
  iconPosition: z.enum(['left', 'right']).optional(),
  fullWidth: z.boolean().optional(),
  // Action-specific properties
  eventId: z.string().optional(), // For trigger_automation
  saveState: z.enum(['draft', 'pending_review', 'incomplete']).optional(),
  navigateDirection: NavigateDirectionSchema.optional(),
  targetStep: z.string().optional(),
  overlayType: OverlayTypeSchema.optional(),
  overlayTitle: z.string().optional(),
  overlayContent: z.string().optional(),
  targetElements: z.array(z.string()).optional(),
  visibilityAction: z.enum(['show', 'hide', 'toggle']).optional(),
  copySource: z.enum(['static', 'field', 'template']).optional(),
  copyContent: z.string().optional(),
  sourceField: z.string().optional(),
  // Behavior flags
  validateBeforeAction: z.boolean().optional(),
  confirmBeforeAction: z.boolean().optional(),
  confirmationMessage: z.string().optional(),
  loadingText: z.string().optional(),
  successText: z.string().optional(),
  successMessage: z.string().optional(),
  // Placement
  afterElementId: z.string().optional(), // Insert after this element
  position: z.enum(['start', 'end']).optional(), // Or at start/end of form
});

/**
 * Configure properties of an existing button
 */
const ConfigureButtonAction = z.object({
  action: z.literal('configureButton'),
  elementId: z.string(),
  updates: z.record(z.string(), z.unknown()),
});

/**
 * Create a button with bound automation in one call
 */
const CreateButtonAutomationAction = z.object({
  action: z.literal('createButtonAutomation'),
  formId: z.number(),
  buttonText: z.string(),
  eventId: z.string().regex(/^[a-z][a-z0-9_]*$/),
  // Button styling
  variant: ButtonVariantSchema.optional(),
  size: ButtonSizeSchema.optional(),
  icon: z.string().optional(),
  loadingText: z.string().optional(),
  successText: z.string().optional(),
  // Automation configuration
  automationName: z.string().optional(),
  automationActions: z.array(z.object({
    type: z.string(),
    config: z.record(z.string(), z.unknown()),
  })),
  // Placement
  afterElementId: z.string().optional(),
  position: z.enum(['start', 'end']).optional(),
});

/**
 * Add navigation buttons to a wizard step
 */
const AddNavigationButtonsAction = z.object({
  action: z.literal('addNavigationButtons'),
  formId: z.number(),
  stepIndex: z.number().optional(), // Current step, defaults to last
  stepId: z.string().optional(), // Or by step ID
  showNext: z.boolean().default(true),
  showPrevious: z.boolean().default(true),
  nextText: z.string().default('Next'),
  previousText: z.string().default('Back'),
  nextIcon: z.string().optional(),
  previousIcon: z.string().optional(),
  validateBeforeNext: z.boolean().default(true),
  // Final step options
  isFinalStep: z.boolean().optional(),
  submitText: z.string().optional(), // If final step, show submit instead of next
});

/**
 * Get button by ID or name
 */
const GetButtonAction = z.object({
  action: z.literal('getButton'),
  elementId: z.string().optional(),
  name: z.string().optional(),
  formId: z.number().optional(),
});

/**
 * List all buttons in a form
 */
const ListButtonsAction = z.object({
  action: z.literal('listButtons'),
  formId: z.number(),
  actionType: ButtonActionTypeSchema.optional(), // Filter by action type
});

/**
 * Remove a button element
 */
const RemoveButtonAction = z.object({
  action: z.literal('removeButton'),
  elementId: z.string(),
});

/**
 * Duplicate a button element
 */
const DuplicateButtonAction = z.object({
  action: z.literal('duplicateButton'),
  elementId: z.string(),
  newName: z.string().optional(),
  newButtonText: z.string().optional(),
});

// =============================================================================
// Combined Action Schema
// =============================================================================

export const ButtonActionSchema = z.discriminatedUnion('action', [
  AddButtonAction,
  ConfigureButtonAction,
  CreateButtonAutomationAction,
  AddNavigationButtonsAction,
  GetButtonAction,
  ListButtonsAction,
  RemoveButtonAction,
  DuplicateButtonAction,
]);

export type ButtonAction = z.infer<typeof ButtonActionSchema>;

// =============================================================================
// Response Types
// =============================================================================

export interface ButtonActionResponse {
  success: boolean;
  data?: unknown;
  error?: string;
}
