/**
 * AutomationsTab Component
 * Main tab wrapper for visual workflow builder (visual mode only)
 */

import { VisualBuilder } from './VisualBuilder';

interface AutomationsTabProps {
  formId: number;
}

export function AutomationsTab({ formId }: AutomationsTabProps) {
  // Check for automation_id in URL params
  const urlParams = new URLSearchParams(window.location.search);
  const automationId = urlParams.get('automation_id')
    ? parseInt(urlParams.get('automation_id')!)
    : null;

  return (
    <div className="automations-tab h-full">
      <VisualBuilder formId={formId} automationId={automationId} />
    </div>
  );
}
