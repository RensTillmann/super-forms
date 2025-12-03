import React from 'react';
import { PropertyField } from '../shared';
import { X, Plus } from 'lucide-react';
import { v4 as uuidv4 } from 'uuid';

interface StepWizardPropertiesProps {
  element: any;
  onUpdate: (property: string, value: any) => void;
}

export const StepWizardProperties: React.FC<StepWizardPropertiesProps> = ({ 
  element, 
  onUpdate 
}) => {
  return (
    <>
      <PropertyField label="Show Step Numbers">
        <input
          type="checkbox"
          checked={element.properties?.showStepNumbers !== false}
          onChange={(e) => onUpdate('showStepNumbers', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      <PropertyField label="Allow Back Navigation">
        <input
          type="checkbox"
          checked={element.properties?.allowBackNavigation !== false}
          onChange={(e) => onUpdate('allowBackNavigation', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      <PropertyField label="Steps">
        <div className="space-y-3">
          {element.properties?.steps?.map((step: any, index: number) => (
            <div key={step.id} className="border rounded p-3">
              <input
                type="text"
                value={step.title}
                onChange={(e) => {
                  const newSteps = [...(element.properties.steps || [])];
                  newSteps[index] = { ...step, title: e.target.value };
                  onUpdate('steps', newSteps);
                }}
                className="form-input mb-2"
                placeholder="Step Title"
              />
              <input
                type="text"
                value={step.description || ''}
                onChange={(e) => {
                  const newSteps = [...(element.properties.steps || [])];
                  newSteps[index] = { ...step, description: e.target.value };
                  onUpdate('steps', newSteps);
                }}
                className="form-input mb-2"
                placeholder="Step Description"
              />
              <button
                onClick={() => {
                  const newSteps = element.properties.steps?.filter((_: any, i: number) => i !== index);
                  onUpdate('steps', newSteps);
                }}
                className="btn btn-ghost btn-icon text-red-500"
              >
                <X size={14} />
              </button>
            </div>
          ))}
          <button
            onClick={() => {
              const newSteps = [...(element.properties.steps || []), { 
                id: uuidv4(), 
                title: `Step ${(element.properties.steps?.length || 0) + 1}`, 
                description: 'Step description' 
              }];
              onUpdate('steps', newSteps);
            }}
            className="btn btn-ghost text-sm"
          >
            <Plus size={14} />
            Add Step
          </button>
        </div>
      </PropertyField>
    </>
  );
};