import { useElementsStore } from '../../apps/form-builder-v2/store/useElementsStore';
import { ButtonActionSchema, ButtonActionResponse } from '../schemas/buttonActionSchema';

// Declare wp.apiFetch type
declare const wp: {
  apiFetch: <T>(options: {
    path: string;
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
    data?: Record<string, unknown>;
  }) => Promise<T>;
};

/**
 * Generate a short unique ID for elements
 */
function generateId(): string {
  return Math.random().toString(36).substring(2, 10);
}

/**
 * MCP Handler for button-related actions.
 *
 * This handler enables LLM agents to:
 * - Add button elements to forms
 * - Configure button properties and actions
 * - Create buttons with automation bindings
 * - Add wizard navigation buttons
 */
export async function handleButtonAction(
  rawAction: unknown
): Promise<ButtonActionResponse> {
  // Validate the action
  const parseResult = ButtonActionSchema.safeParse(rawAction);
  if (!parseResult.success) {
    return {
      success: false,
      error: `Invalid action: ${parseResult.error.message}`,
    };
  }

  const action = parseResult.data;

  try {
    switch (action.action) {
      // =====================================================================
      // Add Button
      // =====================================================================

      case 'addButton': {
        const store = useElementsStore.getState();
        const elementId = `button-${generateId()}`;
        const name = action.name || `btn_${action.actionType}_${Date.now()}`;

        // Build properties from action params
        const properties: Record<string, unknown> = {
          buttonText: action.buttonText,
          actionType: action.actionType,
          name,
        };

        // Add optional properties if provided
        if (action.variant) properties.variant = action.variant;
        if (action.size) properties.size = action.size;
        if (action.icon) properties.icon = action.icon;
        if (action.iconPosition) properties.iconPosition = action.iconPosition;
        if (action.fullWidth !== undefined) properties.fullWidth = action.fullWidth;

        // Action-specific properties
        if (action.eventId) properties.eventId = action.eventId;
        if (action.saveState) properties.saveState = action.saveState;
        if (action.navigateDirection) properties.navigateDirection = action.navigateDirection;
        if (action.targetStep) properties.targetStep = action.targetStep;
        if (action.overlayType) properties.overlayType = action.overlayType;
        if (action.overlayTitle) properties.overlayTitle = action.overlayTitle;
        if (action.overlayContent) properties.overlayContent = action.overlayContent;
        if (action.targetElements) properties.targetElements = action.targetElements;
        if (action.visibilityAction) properties.visibilityAction = action.visibilityAction;
        if (action.copySource) properties.copySource = action.copySource;
        if (action.copyContent) properties.copyContent = action.copyContent;
        if (action.sourceField) properties.sourceField = action.sourceField;

        // Behavior flags
        if (action.validateBeforeAction !== undefined) {
          properties.validateBeforeAction = action.validateBeforeAction;
        }
        if (action.confirmBeforeAction !== undefined) {
          properties.confirmBeforeAction = action.confirmBeforeAction;
        }
        if (action.confirmationMessage) properties.confirmationMessage = action.confirmationMessage;
        if (action.loadingText) properties.loadingText = action.loadingText;
        if (action.successText) properties.successText = action.successText;
        if (action.successMessage) properties.successMessage = action.successMessage;

        // Create the element
        const element = {
          type: 'button' as const,
          id: elementId,
          properties,
        };

        // Determine insertion index
        let insertIndex: number | undefined;
        if (action.afterElementId) {
          const afterIndex = store.order.indexOf(action.afterElementId);
          if (afterIndex !== -1) {
            insertIndex = afterIndex + 1;
          }
        } else if (action.position === 'start') {
          insertIndex = 0;
        }
        // Default (end) is handled by addElement when index is undefined

        // Add to store
        store.addElement(element, insertIndex);

        return {
          success: true,
          data: {
            elementId,
            name,
            actionType: action.actionType,
            message: `Button "${action.buttonText}" added successfully`,
          },
        };
      }

      // =====================================================================
      // Configure Button
      // =====================================================================

      case 'configureButton': {
        const store = useElementsStore.getState();
        const element = store.items[action.elementId];

        if (!element) {
          return {
            success: false,
            error: `Element not found: ${action.elementId}`,
          };
        }

        if (element.type !== 'button') {
          return {
            success: false,
            error: `Element ${action.elementId} is not a button (type: ${element.type})`,
          };
        }

        // Update properties by merging with existing
        const updatedProperties = {
          ...element.properties,
          ...action.updates,
        };

        store.updateElement(action.elementId, { properties: updatedProperties });

        return {
          success: true,
          data: {
            elementId: action.elementId,
            updates: action.updates,
            message: 'Button configured successfully',
          },
        };
      }

      // =====================================================================
      // Create Button with Automation
      // =====================================================================

      case 'createButtonAutomation': {
        const store = useElementsStore.getState();
        const elementId = `button-${generateId()}`;
        const name = `btn_${action.eventId}`;

        // Create button element
        const properties: Record<string, unknown> = {
          buttonText: action.buttonText,
          actionType: 'trigger_automation',
          eventId: action.eventId,
          name,
          awaitResponse: true,
        };

        if (action.variant) properties.variant = action.variant;
        if (action.size) properties.size = action.size;
        if (action.icon) properties.icon = action.icon;
        if (action.loadingText) properties.loadingText = action.loadingText;
        if (action.successText) properties.successText = action.successText;

        const element = {
          type: 'button' as const,
          id: elementId,
          properties,
        };

        // Determine insertion index
        let insertIndex: number | undefined;
        if (action.afterElementId) {
          const afterIndex = store.order.indexOf(action.afterElementId);
          if (afterIndex !== -1) {
            insertIndex = afterIndex + 1;
          }
        } else if (action.position === 'start') {
          insertIndex = 0;
        }

        store.addElement(element, insertIndex);

        // Create automation via REST API
        let automationId: number | null = null;
        try {
          interface AutomationResponse {
            id: number;
            name: string;
          }

          const automationData = {
            name: action.automationName || `Button: ${action.buttonText}`,
            form_id: action.formId,
            trigger_event: `button.${action.eventId}.clicked`,
            enabled: true,
            actions: action.automationActions,
          };

          const response = await wp.apiFetch<AutomationResponse>({
            path: '/super-forms/v1/automations',
            method: 'POST',
            data: automationData,
          });

          automationId = response.id;
        } catch (err) {
          // Automation creation failed but button was added
          return {
            success: true,
            data: {
              elementId,
              name,
              eventId: action.eventId,
              automationId: null,
              warning: `Button added but automation creation failed: ${err instanceof Error ? err.message : 'Unknown error'}`,
            },
          };
        }

        return {
          success: true,
          data: {
            elementId,
            name,
            eventId: action.eventId,
            automationId,
            message: `Button "${action.buttonText}" with automation created successfully`,
          },
        };
      }

      // =====================================================================
      // Add Navigation Buttons
      // =====================================================================

      case 'addNavigationButtons': {
        const store = useElementsStore.getState();
        const addedButtons: Array<{ id: string; direction: string }> = [];

        // Add Previous button
        if (action.showPrevious) {
          const prevId = `button-prev-${generateId()}`;
          const prevProps: Record<string, unknown> = {
            buttonText: action.previousText,
            actionType: 'navigate',
            navigateDirection: 'previous',
            name: `btn_prev_step_${action.stepIndex ?? 'current'}`,
            variant: 'outline',
          };

          if (action.previousIcon) prevProps.icon = action.previousIcon;

          store.addElement({
            type: 'button' as const,
            id: prevId,
            properties: prevProps,
          });

          addedButtons.push({ id: prevId, direction: 'previous' });
        }

        // Add Next/Submit button
        if (action.showNext) {
          const nextId = `button-next-${generateId()}`;
          const isFinal = action.isFinalStep;

          const nextProps: Record<string, unknown> = {
            buttonText: isFinal ? (action.submitText || 'Submit') : action.nextText,
            actionType: isFinal ? 'submit' : 'navigate',
            navigateDirection: isFinal ? undefined : 'next',
            name: isFinal ? 'btn_submit' : `btn_next_step_${action.stepIndex ?? 'current'}`,
            variant: 'primary',
            validateBeforeAction: action.validateBeforeNext,
          };

          if (action.nextIcon) nextProps.icon = action.nextIcon;

          store.addElement({
            type: 'button' as const,
            id: nextId,
            properties: nextProps,
          });

          addedButtons.push({ id: nextId, direction: isFinal ? 'submit' : 'next' });
        }

        return {
          success: true,
          data: {
            buttons: addedButtons,
            stepIndex: action.stepIndex,
            message: `Navigation buttons added (${addedButtons.length} buttons)`,
          },
        };
      }

      // =====================================================================
      // Get Button
      // =====================================================================

      case 'getButton': {
        const store = useElementsStore.getState();

        let element;

        if (action.elementId) {
          element = store.items[action.elementId];
        } else if (action.name) {
          // Search by name
          element = Object.values(store.items).find(
            (item) => item.type === 'button' && item.properties?.name === action.name
          );
        }

        if (!element) {
          return {
            success: false,
            error: 'Button not found',
          };
        }

        return {
          success: true,
          data: {
            element,
          },
        };
      }

      // =====================================================================
      // List Buttons
      // =====================================================================

      case 'listButtons': {
        const store = useElementsStore.getState();

        let buttons = Object.values(store.items).filter(
          (item) => item.type === 'button'
        );

        // Filter by action type if specified
        if (action.actionType) {
          buttons = buttons.filter(
            (btn) => btn.properties?.actionType === action.actionType
          );
        }

        return {
          success: true,
          data: {
            buttons: buttons.map((btn) => ({
              id: btn.id,
              name: btn.properties?.name,
              buttonText: btn.properties?.buttonText,
              actionType: btn.properties?.actionType,
              eventId: btn.properties?.eventId,
            })),
            count: buttons.length,
          },
        };
      }

      // =====================================================================
      // Remove Button
      // =====================================================================

      case 'removeButton': {
        const store = useElementsStore.getState();
        const element = store.items[action.elementId];

        if (!element) {
          return {
            success: false,
            error: `Element not found: ${action.elementId}`,
          };
        }

        if (element.type !== 'button') {
          return {
            success: false,
            error: `Element ${action.elementId} is not a button`,
          };
        }

        store.removeElement(action.elementId);

        return {
          success: true,
          data: {
            removed: action.elementId,
            message: 'Button removed successfully',
          },
        };
      }

      // =====================================================================
      // Duplicate Button
      // =====================================================================

      case 'duplicateButton': {
        const store = useElementsStore.getState();
        const original = store.items[action.elementId];

        if (!original) {
          return {
            success: false,
            error: `Element not found: ${action.elementId}`,
          };
        }

        if (original.type !== 'button') {
          return {
            success: false,
            error: `Element ${action.elementId} is not a button`,
          };
        }

        const newId = `button-${generateId()}`;
        const newProperties = { ...original.properties };

        if (action.newName) {
          newProperties.name = action.newName;
        } else {
          newProperties.name = `${original.properties?.name || 'btn'}_copy`;
        }

        if (action.newButtonText) {
          newProperties.buttonText = action.newButtonText;
        }

        // Get index after original
        const originalIndex = store.order.indexOf(action.elementId);
        const insertIndex = originalIndex !== -1 ? originalIndex + 1 : undefined;

        store.addElement(
          {
            type: 'button' as const,
            id: newId,
            properties: newProperties,
          },
          insertIndex
        );

        return {
          success: true,
          data: {
            originalId: action.elementId,
            newId,
            name: newProperties.name,
            message: 'Button duplicated successfully',
          },
        };
      }

      default:
        return {
          success: false,
          error: 'Unknown action',
        };
    }
  } catch (error) {
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error',
    };
  }
}

// =============================================================================
// MCP Tool Definition (for LLM agents)
// =============================================================================

export const buttonToolDefinition = {
  name: 'form_buttons',
  description: `
Manage form button elements with various action types.

## Button Action Types

- **submit**: Standard form submission
- **save_state**: Save as draft/pending/incomplete
- **reset**: Clear all form fields
- **navigate**: Move between wizard steps (next/previous/specific)
- **trigger_automation**: Fire custom event for automation workflow
- **open_overlay**: Open modal, drawer, popup, or dialog
- **toggle_visibility**: Show/hide other form elements
- **copy_to_clipboard**: Copy content from field, static text, or template

## Common Operations

### Add a submit button:
{
  "action": "addButton",
  "formId": 123,
  "buttonText": "Submit",
  "actionType": "submit",
  "variant": "primary"
}

### Add a save draft button:
{
  "action": "addButton",
  "formId": 123,
  "buttonText": "Save Draft",
  "actionType": "save_state",
  "saveState": "draft",
  "variant": "outline",
  "icon": "save"
}

### Add a button that triggers PDF generation:
{
  "action": "createButtonAutomation",
  "formId": 123,
  "buttonText": "Generate PDF",
  "eventId": "generate_pdf",
  "icon": "file-text",
  "loadingText": "Generating...",
  "successText": "PDF Ready!",
  "automationActions": [
    {
      "type": "generate_file",
      "config": {
        "template": "invoice",
        "format": "pdf"
      }
    }
  ]
}

### Add wizard navigation buttons:
{
  "action": "addNavigationButtons",
  "formId": 123,
  "stepIndex": 1,
  "showPrevious": true,
  "showNext": true,
  "nextText": "Continue",
  "previousText": "Go Back"
}

### Add a modal trigger button:
{
  "action": "addButton",
  "formId": 123,
  "buttonText": "View Terms",
  "actionType": "open_overlay",
  "overlayType": "modal",
  "overlayTitle": "Terms & Conditions",
  "overlayContent": "Your terms content here...",
  "variant": "link"
}

### List all buttons in a form:
{
  "action": "listButtons",
  "formId": 123
}

### Configure an existing button:
{
  "action": "configureButton",
  "elementId": "button-abc123",
  "updates": {
    "buttonText": "Updated Text",
    "variant": "destructive"
  }
}
`,
  inputSchema: ButtonActionSchema,
};
