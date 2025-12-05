import React from 'react';
import {
  GeneralProperties,
  ValidationProperties,
  SliderProperties,
  FileProperties,
  ContentProperties,
  RatingProperties
} from './basic';
import { ChoiceProperties } from './choice';
import { ContainerProperties } from './container';
import {
  StepWizardProperties,
  RepeaterProperties,
  ConditionalGroupProperties
} from './layout';
import { PaymentProperties } from './advanced';
import { SchemaPropertyPanel } from './schema';
import { isElementRegistered } from '../../../../schemas/core/registry';

// Import element schemas to register them
import '../../../../schemas/elements';

interface PropertyPanelRegistryProps {
  element: any;
  onUpdate: (property: string, value: any) => void;
}

// Panel configuration mapping element types to their property panels
const PANEL_CONFIG = {
  // Basic input elements
  text: ['general', 'validation'],
  email: ['general', 'validation'],
  password: ['general', 'validation'],
  url: ['general', 'validation'],
  phone: ['general', 'validation'],
  number: ['general', 'validation'],
  textarea: ['general', 'validation'],
  
  // File upload
  file: ['general', 'file'],
  
  // Choice elements
  select: ['general', 'choice', 'validation'],
  radio: ['general', 'choice'],
  checkbox: ['general', 'choice'],
  'radio-cards': ['general', 'choice'],
  'checkbox-cards': ['general', 'choice'],
  
  // Slider and rating
  slider: ['general', 'slider'],
  rating: ['general', 'rating'],
  
  // Container elements
  columns: ['container'],
  tabs: ['container'],
  accordion: ['container'],
  section: ['container'],
  card: ['container'],
  
  // Layout elements
  'step-wizard': ['step-wizard'],
  repeater: ['repeater'],
  'conditional-group': ['conditional-group'],
  
  // Advanced elements
  payment: ['general', 'payment'],
  
  // Content elements
  paragraph: ['content'],
  'html-block': ['content'],
};

// Component mapping for each panel type
const PANEL_COMPONENTS = {
  general: GeneralProperties,
  validation: ValidationProperties,
  choice: ChoiceProperties,
  container: ContainerProperties,
  slider: SliderProperties,
  file: FileProperties,
  rating: RatingProperties,
  content: ContentProperties,
  payment: PaymentProperties,
  'step-wizard': StepWizardProperties,
  repeater: RepeaterProperties,
  'conditional-group': ConditionalGroupProperties,
};

// Panel labels for display
const PANEL_LABELS = {
  general: 'General',
  validation: 'Validation',
  choice: 'Options',
  container: 'Layout',
  slider: 'Range',
  file: 'File Upload',
  rating: 'Rating',
  content: 'Content',
  payment: 'Payment',
  'step-wizard': 'Steps',
  repeater: 'Repeater',
  'conditional-group': 'Conditions',
};

export const PropertyPanelRegistry: React.FC<PropertyPanelRegistryProps> = ({
  element,
  onUpdate
}) => {
  // Check if this element type has a schema registered
  // If so, use the schema-driven panel instead of hardcoded panels
  if (isElementRegistered(element.type)) {
    return (
      <SchemaPropertyPanel
        elementType={element.type}
        properties={element.properties || {}}
        onPropertyChange={onUpdate}
      />
    );
  }

  // Fallback to legacy hardcoded panels for elements without schemas
  // Get the panels for this element type
  const panelTypes = PANEL_CONFIG[element.type as keyof typeof PANEL_CONFIG] || ['general'];

  // If only one panel, render it directly
  if (panelTypes.length === 1) {
    const PanelComponent = PANEL_COMPONENTS[panelTypes[0] as keyof typeof PANEL_COMPONENTS];
    return PanelComponent ? (
      <PanelComponent element={element} onUpdate={onUpdate} />
    ) : null;
  }

  // Multiple panels - render with tabs or sections
  const [activePanel, setActivePanel] = React.useState(panelTypes[0]);

  const ActivePanelComponent = PANEL_COMPONENTS[activePanel as keyof typeof PANEL_COMPONENTS];

  return (
    <div className="property-panel-registry">
      {/* Panel Navigation */}
      <div className="panel-tabs">
        {panelTypes.map((panelType) => (
          <button
            key={panelType}
            onClick={() => setActivePanel(panelType)}
            className={`panel-tab ${activePanel === panelType ? 'active' : ''}`}
          >
            {PANEL_LABELS[panelType as keyof typeof PANEL_LABELS] || panelType}
          </button>
        ))}
      </div>

      {/* Active Panel Content */}
      <div className="panel-content">
        {ActivePanelComponent && (
          <ActivePanelComponent element={element} onUpdate={onUpdate} />
        )}
      </div>
    </div>
  );
};

// Helper function to get available panels for an element type
export const getAvailablePanels = (elementType: string): string[] => {
  return PANEL_CONFIG[elementType as keyof typeof PANEL_CONFIG] || ['general'];
};

// Helper function to check if an element has a specific panel
export const hasPanel = (elementType: string, panelType: string): boolean => {
  const panels = PANEL_CONFIG[elementType as keyof typeof PANEL_CONFIG] || [];
  return panels.includes(panelType);
};

// Export the configuration for external use
export { PANEL_CONFIG, PANEL_COMPONENTS, PANEL_LABELS };