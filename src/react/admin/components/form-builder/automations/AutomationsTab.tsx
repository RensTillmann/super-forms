/**
 * AutomationsTab Component
 * Main tab wrapper for visual workflow builder (visual mode only)
 */

import { VisualBuilder } from './VisualBuilder';

interface AutomationsTabProps {
  formId: number;
}

export function AutomationsTab({ formId }: AutomationsTabProps) {
  // Check for URL params
  const urlParams = new URLSearchParams(window.location.search);
  const automationId = urlParams.get('automation_id')
    ? parseInt(urlParams.get('automation_id')!)
    : null;

  // Check for new_trigger_event param (from button properties "Create Automation")
  const newTriggerEvent = urlParams.get('new_trigger_event');
  const buttonId = urlParams.get('button_id');

  return (
    <div className="automations-tab h-full">
      <VisualBuilder
        formId={formId}
        automationId={automationId}
        newTriggerEvent={newTriggerEvent}
        buttonId={buttonId}
      />
    </div>
  );
}
