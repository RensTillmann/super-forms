# Form Builder UI Components Library

A comprehensive collection of reusable UI components extracted from the Form Builder application. These components are designed to be flexible, accessible, and production-ready.

## Overview

This library provides a complete set of UI components organized into the following categories:

### 🍞 Toast Notifications
- **Toast**: Individual toast notification component
- **ToastProvider**: Context provider for managing toast notifications
- **useToast**: Hook for displaying toast messages

### 📊 Panels
- **BasePanel**: Base component for all panel types
- **SharePanel**: Form sharing and collaboration panel
- **ExportPanel**: Form export options panel
- **AnalyticsPanel**: Form analytics and insights panel
- **VersionHistoryPanel**: Form version history and restore panel

### 🎯 Overlays
- **ErrorBoundary**: Enhanced error boundary with error reporting
- **ContextMenu**: Right-click context menu
- **FloatingToolbar**: Text formatting toolbar
- **GridOverlay**: Canvas grid overlay
- **ResizableBottomTray**: Collapsible bottom tray component

### 🎮 Controls
- **FormSelector**: Form selection dropdown
- **ZoomControls**: Canvas zoom controls
- **InlineEditableText**: Click-to-edit text component

### 🪝 Hooks
- **useModal**: Modal state management
- **useClickOutside**: Detect clicks outside element
- **useKeyPress**: Keyboard shortcut handler
- **useDebounce**: Debounce value changes
- **useLocalStorage**: Persist state to localStorage

## Installation

The components are part of the Form Builder project. To use them in your components:

```typescript
import { 
  Toast, 
  ToastProvider, 
  useToast,
  SharePanel,
  ErrorBoundary,
  ContextMenu,
  // ... other components
} from './ui-components';
```

## Usage Examples

### Toast Notifications

```tsx
// Wrap your app with ToastProvider
function App() {
  return (
    <ToastProvider position="top-right" maxToasts={5}>
      <YourApp />
    </ToastProvider>
  );
}

// Use toast in any component
function MyComponent() {
  const { addToast } = useToast();
  
  const handleSave = () => {
    // ... save logic
    addToast('Form saved successfully!', 'success');
  };
  
  return <button onClick={handleSave}>Save</button>;
}
```

### Error Boundary

```tsx
<ErrorBoundary
  onError={(error, errorInfo) => {
    // Log to error tracking service
    console.error('Form Builder Error:', error, errorInfo);
  }}
>
  <FormBuilder />
</ErrorBoundary>
```

### Share Panel

```tsx
function FormBuilderHeader() {
  const [isShareOpen, setIsShareOpen] = useState(false);
  
  return (
    <>
      <button onClick={() => setIsShareOpen(true)}>Share</button>
      
      <SharePanel
        isOpen={isShareOpen}
        onClose={() => setIsShareOpen(false)}
        formUrl="https://forms.example.com/my-form"
        collaborators={[
          { id: '1', name: 'John Doe', role: 'editor' }
        ]}
        onInviteCollaborator={() => {
          // Handle invite logic
        }}
      />
    </>
  );
}
```

### Context Menu

```tsx
function ElementComponent() {
  const [contextMenu, setContextMenu] = useState<{x: number, y: number} | null>(null);
  
  const handleContextMenu = (e: React.MouseEvent) => {
    e.preventDefault();
    setContextMenu({ x: e.clientX, y: e.clientY });
  };
  
  return (
    <div onContextMenu={handleContextMenu}>
      {/* Your element content */}
      
      {contextMenu && (
        <ContextMenu
          x={contextMenu.x}
          y={contextMenu.y}
          onClose={() => setContextMenu(null)}
          onAction={(action) => {
            console.log('Action:', action);
            // Handle actions
          }}
        />
      )}
    </div>
  );
}
```

### Inline Editable Text

```tsx
function EditableTitle() {
  const [title, setTitle] = useState('Click to edit');
  
  return (
    <InlineEditableText
      value={title}
      onChange={setTitle}
      placeholder="Enter title"
      className="form-title"
      onFormat={(format) => {
        // Handle text formatting
        console.log('Format:', format);
      }}
    />
  );
}
```

## Component APIs

### ToastProvider Props
- `children`: ReactNode
- `maxToasts?`: number (default: 5)
- `defaultDuration?`: number (default: 5000)
- `position?`: 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left' | 'top-center' | 'bottom-center'

### BasePanel Props
- `isOpen`: boolean
- `onClose`: () => void
- `title?`: string
- `className?`: string
- `position?`: 'left' | 'right' | 'center'
- `size?`: 'sm' | 'md' | 'lg' | 'xl' | 'full'

### ErrorBoundary Props
- `children`: ReactNode
- `fallback?`: (error, errorInfo, reset) => ReactNode
- `onError?`: (error, errorInfo) => void
- `resetKeys?`: string[]
- `resetOnPropsChange?`: boolean

## Styling

All components use CSS classes that can be customized through your global styles. The components are designed to work with the existing Form Builder styles but can be adapted for other projects.

## Accessibility

All components follow accessibility best practices:
- Proper ARIA labels and roles
- Keyboard navigation support
- Focus management
- Screen reader friendly

## Browser Support

The components support all modern browsers:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)

## Contributing

When adding new components to the library:
1. Create appropriate TypeScript interfaces in the `types` directory
2. Implement the component with proper accessibility
3. Export from the appropriate category index file
4. Update this README with usage examples

## License

This component library is part of the Form Builder project and follows the same license terms.