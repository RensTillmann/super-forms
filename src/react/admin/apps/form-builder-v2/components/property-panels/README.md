# Property Panel System

This directory contains the modular property panel system that replaced the massive 600+ line `renderTypeSpecificProperties` function in FormBuilderComplete.tsx.

## Architecture

The property panel system is organized into several categories:

### Directory Structure

```
property-panels/
├── basic/              # Basic form field properties
├── choice/             # Choice-based elements (select, radio, checkbox)
├── container/          # Container and layout elements
├── layout/             # Complex layout elements (wizards, repeaters)
├── advanced/           # Advanced elements (payment, integrations)
├── shared/             # Reusable components
├── PropertyPanelRegistry.tsx  # Central registry and management
└── index.ts            # Main exports
```

### Core Components

#### PropertyPanelRegistry
The central component that manages which property panels to display for each element type. It:
- Maps element types to their appropriate property panels
- Handles tabbed interface for elements with multiple panels
- Provides a clean, extensible API for adding new panels

#### Shared Components
- `PropertyField`: Standardized wrapper for property inputs
- `PropertyGroup`: Container for grouping related properties
- `OptionsEditor`: Reusable component for managing option lists

## Property Panel Categories

### Basic Panels
- **GeneralProperties**: Common fields (label, placeholder, required, disabled, etc.)
- **ValidationProperties**: Validation rules (min/max length, patterns, custom messages)
- **SliderProperties**: Range slider specific settings (min, max, step)
- **FileProperties**: File upload settings (types, size limits, multiple files)
- **ContentProperties**: Content elements (paragraph text, HTML blocks)
- **RatingProperties**: Rating element configuration

### Choice Panels
- **ChoiceProperties**: Options management for select, radio, checkbox elements
  - Handles option lists with add/remove functionality
  - Card-specific settings (columns, descriptions)
  - Multiple selection and search options

### Container Panels
- **ContainerProperties**: Layout containers (columns, tabs, accordion, section, card)
  - Column configuration (count, gaps)
  - Tab management (position, titles)
  - Accordion settings (multiple open, sections)
  - Section properties (legend, borders, collapsible)
  - Card properties (headers, footers, elevation)

### Layout Panels
- **StepWizardProperties**: Multi-step form configuration
- **RepeaterProperties**: Repeatable field groups
- **ConditionalGroupProperties**: Conditional visibility rules

### Advanced Panels
- **PaymentProperties**: Payment element configuration
  - Amount types (fixed, user-defined, calculated)
  - Currency selection
  - Payment methods
  - Billing/shipping address collection

## Usage

### Adding a New Property Panel

1. **Create the Panel Component**:
```tsx
// property-panels/basic/MyNewProperties.tsx
import React from 'react';
import { PropertyField } from '../shared';

interface MyNewPropertiesProps {
  element: any;
  onUpdate: (property: string, value: any) => void;
}

export const MyNewProperties: React.FC<MyNewPropertiesProps> = ({ 
  element, 
  onUpdate 
}) => {
  return (
    <PropertyField label="My Setting">
      <input
        type="text"
        value={element.properties?.mySetting || ''}
        onChange={(e) => onUpdate('mySetting', e.target.value)}
        className="form-input"
      />
    </PropertyField>
  );
};
```

2. **Export from Category Index**:
```tsx
// property-panels/basic/index.ts
export { MyNewProperties } from './MyNewProperties';
```

3. **Register in PropertyPanelRegistry**:
```tsx
// Update PANEL_CONFIG
const PANEL_CONFIG = {
  'my-element': ['general', 'my-new'],
  // ...
};

// Update PANEL_COMPONENTS
const PANEL_COMPONENTS = {
  'my-new': MyNewProperties,
  // ...
};

// Update PANEL_LABELS
const PANEL_LABELS = {
  'my-new': 'My Settings',
  // ...
};
```

### Using Existing Panels

The PropertyPanelRegistry automatically handles panel selection based on element type. To use it:

```tsx
<PropertyPanelRegistry element={element} onUpdate={onUpdate} />
```

The registry will:
1. Look up the element type in PANEL_CONFIG
2. Display appropriate panels (single panel or tabbed interface)
3. Handle panel switching and state management

## Benefits

### Before (Single Function)
- 600+ lines of switch statement
- Hard to maintain and extend
- Duplicated code across similar elements
- No reusability
- Difficult to test individual components

### After (Modular System)
- Small, focused components (20-100 lines each)
- Easy to maintain and extend
- Shared components reduce duplication
- Highly reusable and composable
- Easy to test individual panels
- Clean separation of concerns
- Extensible architecture for new element types

## Styling

Property panel styles are defined in `/src/styles/form-builder.css`:

- `.property-panel-registry`: Main container
- `.panel-tabs`: Tab navigation for multi-panel elements
- `.panel-tab`: Individual tab styling
- `.panel-content`: Panel content area
- `.property-field`: Individual property field wrapper
- `.property-label`: Property label styling

## Future Enhancements

1. **Dynamic Panel Loading**: Load panels on-demand to reduce bundle size
2. **Plugin System**: Allow third-party plugins to register custom panels
3. **Panel Groups**: Organize panels into collapsible groups
4. **Context-Aware Panels**: Show different panels based on form context
5. **Panel Validation**: Validate panel configurations before applying
6. **Panel Presets**: Pre-configured panel sets for common use cases