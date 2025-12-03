/**
 * AutomationsPage Component
 * Page wrapper for the Automations tab in Form Builder
 */

import { AutomationsTab } from '@/components/form-builder/automations/AutomationsTab';

interface AutomationsPageProps {
  formId?: number;
  forms?: Array<{ id: number; title: string }>;
  restUrl?: string;
  restNonce?: string;
}

export default function AutomationsPage({
  formId,
  forms,
  restUrl,
  restNonce,
}: AutomationsPageProps) {
  // Ensure formId is available
  if (!formId) {
    return (
      <div className="flex items-center justify-center h-full p-8">
        <div className="text-center">
          <h2 className="text-xl font-semibold text-gray-900 mb-2">
            No Form Selected
          </h2>
          <p className="text-gray-600">
            Please select a form to manage automations and workflows.
          </p>
        </div>
      </div>
    );
  }

  return <AutomationsTab formId={formId} />;
}
