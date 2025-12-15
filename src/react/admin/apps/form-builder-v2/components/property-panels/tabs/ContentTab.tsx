import React, { useCallback } from 'react';
import { SchemaPropertyPanel } from '../schema';
import { isElementRegistered } from '../../../../../schemas/core/registry';
import { GeneralProperties } from '../basic';
import { ButtonAutomationPanel } from '../button';

interface ContentTabProps {
  element: {
    id: string;
    type: string;
    properties?: Record<string, unknown>;
  };
  onPropertyChange: (propertyName: string, value: unknown) => void;
}

/**
 * Content tab - renders field-specific properties.
 * Uses schema-driven panel for elements with schemas, falls back to legacy panels.
 */
export const ContentTab: React.FC<ContentTabProps> = ({
  element,
  onPropertyChange,
}) => {
  const hasSchema = isElementRegistered(element.type);
  const formId = window.sfuiData?.formId ?? 0;

  // Check if this is a button with trigger_automation action
  const isAutomationButton = element.type === 'button' &&
    element.properties?.actionType === 'trigger_automation';

  // Handle create automation - navigate to automations tab with pre-filled trigger
  const handleCreateAutomation = useCallback((buttonId: string, eventId: string) => {
    // Navigate to automations tab with params to create new automation
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('tab', 'automations');
    currentUrl.searchParams.set('new_trigger_event', `button.${eventId}.clicked`);
    currentUrl.searchParams.set('button_id', buttonId);
    window.location.href = currentUrl.toString();
  }, []);

  if (hasSchema) {
    return (
      <div className="space-y-4">
        <SchemaPropertyPanel
          elementType={element.type}
          properties={element.properties || {}}
          onPropertyChange={onPropertyChange}
          categories={['general']}
        />

        {/* Button Automation Panel - shown when actionType is trigger_automation */}
        {isAutomationButton && formId > 0 && (
          <ButtonAutomationPanel
            element={{
              id: element.id,
              properties: {
                name: element.properties?.name as string | undefined,
                eventId: element.properties?.eventId as string | undefined,
                actionType: element.properties?.actionType as string | undefined,
              },
            }}
            formId={formId}
            onCreateAutomation={handleCreateAutomation}
          />
        )}
      </div>
    );
  }

  // Fallback for elements without schemas
  return (
    <div className="space-y-4">
      <div className="p-3 bg-amber-50 border border-amber-200 rounded-md">
        <p className="text-xs text-amber-700">
          This element type ({element.type}) doesn't have a schema yet.
          Using legacy property panels.
        </p>
      </div>
      <GeneralProperties element={element} onUpdate={onPropertyChange} />
    </div>
  );
};

export default ContentTab;
