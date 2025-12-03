# Form Builder Elements System

This directory contains the modular element rendering system extracted from the massive `renderElementPreview` function in `FormBuilderComplete.tsx`.

## Directory Structure

```
elements/
├── ElementRenderer.tsx          # Main factory component with lazy loading
├── index.ts                     # Exports and type definitions
├── shared/
│   └── InlineEditableText.tsx  # Shared editable text component
├── basic/                       # Basic input elements
│   ├── TextInput.tsx           # Text, email, phone, URL, password, number inputs
│   └── TextArea.tsx            # Multi-line text input
├── choice/                      # Selection elements  
│   ├── Select.tsx              # Dropdown selection
│   ├── RadioCards.tsx          # Card-style radio buttons
│   └── CheckboxCards.tsx       # Card-style checkboxes
├── containers/                  # Layout container elements
│   └── ColumnsContainer.tsx    # Multi-column layout
├── advanced/                    # Advanced input types (future)
├── upload/                      # File upload elements (future)
├── layout/                      # Layout elements (future)
└── integration/                 # Third-party integrations (future)
```

## Extracted Elements

### Currently Implemented

1. **TextInput** (`basic/TextInput.tsx`)
   - Handles: text, email, phone, url, password, number, number-formatted
   - Unified component with type-specific input attributes
   - Dynamic placeholder generation

2. **TextArea** (`basic/TextArea.tsx`)
   - Multi-line text input with configurable rows
   - Supports all standard text input properties

3. **Select** (`choice/Select.tsx`)
   - Dropdown selection with options
   - Configurable placeholder text

4. **RadioCards** (`choice/RadioCards.tsx`)
   - Card-style radio button selection
   - Grid layout with configurable columns
   - Inline editable option labels
   - Optional descriptions

5. **CheckboxCards** (`choice/CheckboxCards.tsx`)
   - Card-style checkbox selection
   - Grid layout with configurable columns  
   - Inline editable option labels
   - Optional descriptions

6. **ColumnsContainer** (`containers/ColumnsContainer.tsx`)
   - Multi-column layout container
   - Configurable column count and gap
   - Drop zones for nested elements

## ElementRenderer Factory

The `ElementRenderer.tsx` component acts as a factory that:

- Uses lazy loading for code splitting
- Provides a unified interface for all element types
- Includes fallback for unextracted elements
- Handles common props generation
- Includes loading states with Suspense

## Usage

```tsx
import { ElementRenderer } from './elements';

// In your component
<ElementRenderer 
  element={element} 
  updateElementProperty={updateElementProperty}
/>
```

## Integration with FormBuilderComplete

The integration replaces the 650+ line `renderElementPreview` function with:

```tsx
<ElementRenderer 
  element={element} 
  updateElementProperty={updateElementProperty}
/>
```

The original function has been commented out but preserved for reference.

## Benefits

1. **Modular Architecture**: Each element type is in its own file
2. **Lazy Loading**: Elements are loaded only when needed
3. **Code Splitting**: Reduces initial bundle size
4. **Maintainability**: Easier to modify individual element types
5. **Testability**: Each element can be tested in isolation
6. **Extensibility**: Easy to add new element types

## Future Work

- Extract remaining element types (40+ more elements)
- Implement proper TypeScript interfaces for all element properties
- Add comprehensive testing for each element type
- Implement element-specific validation
- Add accessibility features
- Create element documentation and examples

## Element Categories Plan

Based on the original switch statement, elements are categorized as:

- **basic**: text, email, phone, url, password, number, textarea
- **choice**: select, radio, checkbox, radio-cards, checkbox-cards  
- **containers**: columns, section, tabs, accordion, step-wizard, repeater
- **advanced**: date, datetime, time, location, rating, slider, signature
- **upload**: file, image
- **layout**: divider, spacer, page-break, heading, paragraph, html-block
- **integration**: webhook, payment, embed, calculation

## Dependencies

- React 18+ with Suspense support
- Lucide React for icons
- Existing form builder styles and CSS classes
- InlineEditableText shared component