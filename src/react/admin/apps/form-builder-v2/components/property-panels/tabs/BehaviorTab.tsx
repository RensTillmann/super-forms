import React from 'react';
import { ShieldCheck, GitBranch, Calculator, AlertCircle } from 'lucide-react';
import { SchemaPropertyPanel } from '../schema';
import { isElementRegistered } from '../../../../../schemas/core/registry';
import { CollapsibleSection } from '../style-sections/CollapsibleSection';
import { Input } from '../../../../../components/ui/input';
import { Checkbox } from '../../../../../components/ui/checkbox';
import { Label } from '../../../../../components/ui/label';
import { cn } from '../../../../../lib/utils';

interface BehaviorTabProps {
  element: {
    id: string;
    type: string;
    properties?: Record<string, unknown>;
  };
  onPropertyChange: (propertyName: string, value: unknown) => void;
}

/**
 * Behavior tab - renders validation, conditional logic, and calculations.
 * Organized into collapsible sections for progressive disclosure.
 */
export const BehaviorTab: React.FC<BehaviorTabProps> = ({
  element,
  onPropertyChange,
}) => {
  const hasSchema = isElementRegistered(element.type);
  const properties = element.properties || {};

  // Get validation properties
  const isRequired = properties.required as boolean || false;
  const requiredMessage = properties.requiredMessage as string || '';
  const validation = (properties.validation || {}) as Record<string, unknown>;

  // Check if there are validation overrides
  const hasValidationOverrides = isRequired ||
    Object.keys(validation).some(k => validation[k] !== undefined && validation[k] !== '');

  const updateValidation = (rule: string, value: unknown) => {
    const newValidation = { ...validation, [rule]: value };
    onPropertyChange('validation', newValidation);
  };

  return (
    <div className="space-y-2 p-4">
      {/* Validation Section */}
      <CollapsibleSection
        title="Validation"
        icon={<ShieldCheck className="h-3.5 w-3.5" />}
        defaultExpanded={true}
        hasOverrides={hasValidationOverrides}
      >
        <div className="space-y-4">
          {/* Required Field Toggle */}
          <div className="flex items-start gap-3">
            <Checkbox
              id={`${element.id}-required`}
              checked={isRequired}
              onCheckedChange={(checked) => onPropertyChange('required', checked)}
              className="mt-0.5"
            />
            <div className="flex-1">
              <Label
                htmlFor={`${element.id}-required`}
                className="text-sm font-medium cursor-pointer"
              >
                Required field
              </Label>
              <p className="text-xs text-gray-500 mt-0.5">
                User must fill this field to submit the form
              </p>
            </div>
          </div>

          {/* Custom Required Message - shown when required is checked */}
          {isRequired && (
            <div className="pl-6 border-l-2 border-gray-100">
              <Label className="text-xs text-gray-600 mb-1.5 block">
                Custom error message
              </Label>
              <Input
                type="text"
                value={requiredMessage}
                onChange={(e) => onPropertyChange('requiredMessage', e.target.value)}
                placeholder="This field is required"
                className="h-9 text-sm"
              />
            </div>
          )}

          {/* Schema-driven validation if available */}
          {hasSchema ? (
            <div className="pt-2 border-t border-gray-100">
              <SchemaPropertyPanel
                elementType={element.type}
                properties={properties}
                onPropertyChange={onPropertyChange}
                categories={['validation']}
              />
            </div>
          ) : (
            /* Fallback validation controls for non-schema elements */
            <div className="space-y-3 pt-2 border-t border-gray-100">
              {/* Min/Max Length */}
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label className="text-xs text-gray-600 mb-1.5 block">
                    Min length
                  </Label>
                  <Input
                    type="number"
                    value={(validation.minLength as number) || ''}
                    onChange={(e) => updateValidation('minLength', e.target.value ? parseInt(e.target.value) : undefined)}
                    min={0}
                    placeholder="0"
                    className="h-9 text-sm"
                  />
                </div>
                <div>
                  <Label className="text-xs text-gray-600 mb-1.5 block">
                    Max length
                  </Label>
                  <Input
                    type="number"
                    value={(validation.maxLength as number) || ''}
                    onChange={(e) => updateValidation('maxLength', e.target.value ? parseInt(e.target.value) : undefined)}
                    min={1}
                    placeholder="—"
                    className="h-9 text-sm"
                  />
                </div>
              </div>

              {/* Pattern */}
              <div>
                <Label className="text-xs text-gray-600 mb-1.5 block">
                  Pattern (RegEx)
                </Label>
                <Input
                  type="text"
                  value={(validation.pattern as string) || ''}
                  onChange={(e) => updateValidation('pattern', e.target.value)}
                  placeholder="^[a-zA-Z0-9]+$"
                  className="h-9 text-sm font-mono"
                />
              </div>

              {/* Format Validation */}
              <div className="flex flex-wrap gap-4">
                <div className="flex items-center gap-2">
                  <Checkbox
                    id={`${element.id}-email`}
                    checked={(validation.email as boolean) || false}
                    onCheckedChange={(checked) => updateValidation('email', checked)}
                  />
                  <Label
                    htmlFor={`${element.id}-email`}
                    className="text-xs text-gray-600 cursor-pointer"
                  >
                    Email format
                  </Label>
                </div>
                <div className="flex items-center gap-2">
                  <Checkbox
                    id={`${element.id}-url`}
                    checked={(validation.url as boolean) || false}
                    onCheckedChange={(checked) => updateValidation('url', checked)}
                  />
                  <Label
                    htmlFor={`${element.id}-url`}
                    className="text-xs text-gray-600 cursor-pointer"
                  >
                    URL format
                  </Label>
                </div>
              </div>

              {/* Custom Error Message */}
              <div>
                <Label className="text-xs text-gray-600 mb-1.5 block">
                  Custom error message
                </Label>
                <Input
                  type="text"
                  value={(validation.errorMessage as string) || ''}
                  onChange={(e) => updateValidation('errorMessage', e.target.value)}
                  placeholder="Please enter a valid value"
                  className="h-9 text-sm"
                />
              </div>
            </div>
          )}
        </div>
      </CollapsibleSection>

      {/* Conditional Logic Section */}
      <CollapsibleSection
        title="Conditional Logic"
        icon={<GitBranch className="h-3.5 w-3.5" />}
        defaultExpanded={false}
      >
        <div className="text-center py-6">
          <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-3">
            <GitBranch className="h-5 w-5 text-gray-400" />
          </div>
          <h4 className="text-sm font-medium text-gray-700 mb-1">
            Show/Hide Conditions
          </h4>
          <p className="text-xs text-gray-500 max-w-[200px] mx-auto">
            Control when this field is visible based on other field values.
          </p>
          <button
            className={cn(
              "mt-4 inline-flex items-center gap-1.5 px-3 py-1.5",
              "text-xs font-medium text-primary",
              "bg-primary/5 hover:bg-primary/10 rounded-md transition-colors"
            )}
            onClick={() => {
              // TODO: Open condition builder
              console.log('Open condition builder');
            }}
          >
            <span>+ Add condition</span>
          </button>
        </div>
      </CollapsibleSection>

      {/* Calculations Section */}
      <CollapsibleSection
        title="Calculations"
        icon={<Calculator className="h-3.5 w-3.5" />}
        defaultExpanded={false}
      >
        <div className="text-center py-6">
          <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-3">
            <Calculator className="h-5 w-5 text-gray-400" />
          </div>
          <h4 className="text-sm font-medium text-gray-700 mb-1">
            Calculated Value
          </h4>
          <p className="text-xs text-gray-500 max-w-[200px] mx-auto">
            Auto-fill this field based on a formula using other field values.
          </p>
          <button
            className={cn(
              "mt-4 inline-flex items-center gap-1.5 px-3 py-1.5",
              "text-xs font-medium text-primary",
              "bg-primary/5 hover:bg-primary/10 rounded-md transition-colors"
            )}
            onClick={() => {
              // TODO: Open formula builder
              console.log('Open formula builder');
            }}
          >
            <span>+ Add formula</span>
          </button>
        </div>
      </CollapsibleSection>

      {/* Accessibility Hint */}
      <div className="mt-4 flex items-start gap-2 p-3 bg-blue-50 rounded-lg">
        <AlertCircle className="h-4 w-4 text-blue-500 flex-shrink-0 mt-0.5" />
        <div>
          <p className="text-xs text-blue-700">
            <strong>Tip:</strong> Required fields will show an asterisk (*) next to the label
            and prevent form submission until filled.
          </p>
        </div>
      </div>
    </div>
  );
};

export default BehaviorTab;
