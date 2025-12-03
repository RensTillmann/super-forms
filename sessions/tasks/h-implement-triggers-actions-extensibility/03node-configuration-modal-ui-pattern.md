# Node Configuration Modal UI Pattern

## Executive Summary

**Pattern**: Shadcn/UI Dialog Modal with tabbed property panels, inspired by GmailChrome's inline editing approach but adapted for modal context.

**Trigger**: User clicks or double-clicks on a node in the visual canvas

**Components**:
- `NodeConfigModal.tsx` - Main modal container
- `NodePropertyPanel.tsx` - Node-specific property editor
- Property field components (text, email, select, code, etc.)

---

## Design Philosophy

### Why Modal Instead of Inline Editing?

The GmailChrome component uses inline editing (InlineEditableField) which works well for email preview because:
- Only one email is shown at a time
- Fields are large and clearly separated
- Visual space is abundant

For node configuration, we use **modal dialog** because:
- Canvas has many nodes (10-30+) in dense layout
- Inline editing would clutter the canvas
- Focused editing experience prevents mistakes
- Industry standard (n8n, Zapier, Make all use modals)
- Allows complex multi-field configuration without canvas clutter

### Inspiration from GmailChrome

What we **borrow** from GmailChrome pattern:
- ✅ Field validation on blur
- ✅ Callback-based updates (`updateEmailField` → `updateNodeConfig`)
- ✅ Clear visual hierarchy
- ✅ Test functionality (test email → test node execution)
- ✅ Inline field editing components reused

What we **adapt** for modal context:
- Modal container with header/footer
- Save/Cancel buttons (modal pattern)
- Tabs for grouping related settings
- Compact field layout for modal space
- Close on save or Escape key

---

## User Flow

### Opening the Modal

**Trigger Actions**:
1. **Click on node** → Selects node
2. **Double-click on node** → Opens config modal
3. **Click settings icon on node** → Opens config modal
4. **Right-click → "Configure"** → Opens config modal

**State Management**:
```typescript
const [selectedNode, setSelectedNode] = useState<Node | null>(null);
const [showConfigModal, setShowConfigModal] = useState(false);

// Node.tsx - in the node component
const handleDoubleClick = () => {
  onOpenConfig(node.id);
};

// Canvas.jsx - in the parent
const openNodeConfig = (nodeId: string) => {
  const node = nodes.find(n => n.id === nodeId);
  setSelectedNode(node);
  setShowConfigModal(true);
};
```

### Modal Lifecycle

**1. Open**:
- Modal fades in with backdrop
- Node config loaded into form state
- First input field auto-focused
- Escape key listener attached

**2. Edit**:
- User edits fields
- Changes tracked in modal state (not saved to node yet)
- Real-time validation feedback
- "Unsaved changes" warning if user tries to close

**3. Save**:
- Validate all fields
- If valid: Update node config, close modal
- If invalid: Show errors, keep modal open

**4. Cancel**:
- Discard changes
- Show confirmation if unsaved changes exist
- Close modal

**5. Test** (for action nodes):
- Execute node with test data
- Show result in modal footer
- Keep modal open

---

## Component Architecture

### File Structure

```
src/react/admin/components/triggers/
├── NodeConfigModal.tsx          # Main modal container
├── NodePropertyPanel.tsx        # Property editor for specific node type
├── property-panels/
│   ├── TriggerPropertyPanel.tsx     # For trigger nodes
│   ├── ActionPropertyPanel.tsx      # For action nodes
│   ├── ConditionPropertyPanel.tsx   # For condition nodes
│   └── node-specific/
│       ├── SendEmailPanel.tsx       # send_email action config
│       ├── WebhookPanel.tsx         # webhook action config
│       ├── HttpRequestPanel.tsx     # http_request action config
│       └── ...                      # (20+ action-specific panels)
└── fields/
    ├── NodeTextField.tsx            # Text input
    ├── NodeEmailField.tsx           # Email input with validation
    ├── NodeSelectField.tsx          # Dropdown select
    ├── NodeCodeEditor.tsx           # Code editor (Monaco)
    ├── NodeTagInput.tsx             # Tag/multi-value input
    └── NodeConditionBuilder.tsx     # Condition expression builder
```

### Component Hierarchy

```
<NodeConfigModal>
  <Dialog>
    <DialogContent>
      <DialogHeader>
        <NodeIcon />
        <DialogTitle>Configure {NodeType}</DialogTitle>
        <DialogDescription>
      </DialogHeader>

      <Tabs>
        <TabsList>
          <TabsTrigger value="general">General</TabsTrigger>
          <TabsTrigger value="advanced">Advanced</TabsTrigger>
          <TabsTrigger value="conditions">Conditions</TabsTrigger>
        </TabsList>

        <TabsContent value="general">
          <NodePropertyPanel node={selectedNode} />
        </TabsContent>

        <TabsContent value="advanced">
          {/* Advanced settings */}
        </TabsContent>

        <TabsContent value="conditions">
          {/* Condition builder */}
        </TabsContent>
      </Tabs>

      <DialogFooter>
        <Button variant="outline" onClick={handleCancel}>
          Cancel
        </Button>
        <Button variant="default" onClick={handleTestNode}>
          Test Node
        </Button>
        <Button variant="primary" onClick={handleSave}>
          Save Changes
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</NodeConfigModal>
```

---

## Implementation: Main Modal Component

### NodeConfigModal.tsx

```typescript
import React, { useState, useEffect } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle, CheckCircle, Play } from 'lucide-react';
import { getNodeType } from '../data/nodeTypes';
import NodePropertyPanel from './NodePropertyPanel';
import { clsx } from 'clsx';

interface NodeConfigModalProps {
  node: Node | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSave: (nodeId: string, config: Record<string, any>) => void;
  onTestNode?: (nodeId: string, config: Record<string, any>) => Promise<TestResult>;
}

interface TestResult {
  success: boolean;
  message: string;
  data?: any;
}

export function NodeConfigModal({
  node,
  open,
  onOpenChange,
  onSave,
  onTestNode
}: NodeConfigModalProps) {
  // Local state for config being edited
  const [config, setConfig] = useState<Record<string, any>>({});
  const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);
  const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});
  const [testResult, setTestResult] = useState<TestResult | null>(null);
  const [isTesting, setIsTesting] = useState(false);

  // Load node config when modal opens
  useEffect(() => {
    if (node && open) {
      setConfig({ ...node.config });
      setHasUnsavedChanges(false);
      setValidationErrors({});
      setTestResult(null);
    }
  }, [node, open]);

  if (!node) return null;

  const nodeType = getNodeType(node.type);
  if (!nodeType) return null;

  const NodeIcon = nodeType.icon;

  // Update config field
  const updateField = (field: string, value: any) => {
    setConfig(prev => ({
      ...prev,
      [field]: value
    }));
    setHasUnsavedChanges(true);

    // Clear validation error for this field
    if (validationErrors[field]) {
      setValidationErrors(prev => {
        const updated = { ...prev };
        delete updated[field];
        return updated;
      });
    }
  };

  // Validate all fields
  const validateConfig = (): boolean => {
    const errors: Record<string, string> = {};

    // Example validation rules (customize per node type)
    if (node.type === 'send-email') {
      if (!config.to || config.to.trim() === '') {
        errors.to = 'Recipient email is required';
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(config.to)) {
        errors.to = 'Invalid email format';
      }

      if (!config.subject || config.subject.trim() === '') {
        errors.subject = 'Subject is required';
      }

      if (!config.body || config.body.trim() === '') {
        errors.body = 'Email body is required';
      }
    }

    if (node.type === 'webhook') {
      if (!config.url || config.url.trim() === '') {
        errors.url = 'Webhook URL is required';
      } else if (!/^https?:\/\/.+/.test(config.url)) {
        errors.url = 'URL must start with http:// or https://';
      }
    }

    // ... validation for other node types

    setValidationErrors(errors);
    return Object.keys(errors).length === 0;
  };

  // Handle save
  const handleSave = () => {
    if (validateConfig()) {
      onSave(node.id, config);
      onOpenChange(false);
    }
  };

  // Handle cancel
  const handleCancel = () => {
    if (hasUnsavedChanges) {
      const confirmed = window.confirm(
        'You have unsaved changes. Are you sure you want to close?'
      );
      if (!confirmed) return;
    }
    onOpenChange(false);
  };

  // Handle test node
  const handleTestNode = async () => {
    if (!onTestNode) return;

    if (!validateConfig()) {
      setTestResult({
        success: false,
        message: 'Please fix validation errors before testing'
      });
      return;
    }

    setIsTesting(true);
    setTestResult(null);

    try {
      const result = await onTestNode(node.id, config);
      setTestResult(result);
    } catch (error) {
      setTestResult({
        success: false,
        message: error.message || 'Test failed'
      });
    } finally {
      setIsTesting(false);
    }
  };

  // Close on Escape key
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && open) {
        handleCancel();
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [open, hasUnsavedChanges]);

  return (
    <Dialog open={open} onOpenChange={handleCancel}>
      <DialogContent
        className="max-w-3xl max-h-[85vh] overflow-hidden flex flex-col"
        data-testid="node-config-modal"
      >
        <DialogHeader>
          <div className="flex items-center gap-3">
            <div
              className="w-10 h-10 rounded-lg flex items-center justify-center"
              style={{ backgroundColor: `${nodeType.color}20` }}
            >
              <NodeIcon
                className="w-5 h-5"
                style={{ color: nodeType.color }}
              />
            </div>
            <div>
              <DialogTitle>Configure {nodeType.name}</DialogTitle>
              <DialogDescription>
                {nodeType.description}
              </DialogDescription>
            </div>
          </div>
        </DialogHeader>

        {/* Tabs for different configuration sections */}
        <Tabs defaultValue="general" className="flex-1 overflow-hidden flex flex-col">
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="general">General</TabsTrigger>
            <TabsTrigger value="advanced">Advanced</TabsTrigger>
            <TabsTrigger value="conditions">Conditions</TabsTrigger>
          </TabsList>

          <div className="flex-1 overflow-auto mt-4">
            <TabsContent value="general" className="mt-0">
              <NodePropertyPanel
                node={node}
                nodeType={nodeType}
                config={config}
                onUpdateField={updateField}
                validationErrors={validationErrors}
              />
            </TabsContent>

            <TabsContent value="advanced" className="mt-0">
              <div className="space-y-4">
                <p className="text-sm text-gray-500">
                  Advanced settings for {nodeType.name}
                </p>
                {/* Advanced settings go here */}
              </div>
            </TabsContent>

            <TabsContent value="conditions" className="mt-0">
              <div className="space-y-4">
                <p className="text-sm text-gray-500">
                  Add conditions to control when this node executes
                </p>
                {/* Condition builder goes here */}
              </div>
            </TabsContent>
          </div>
        </Tabs>

        {/* Test Result */}
        {testResult && (
          <Alert
            variant={testResult.success ? 'default' : 'destructive'}
            className="mt-4"
          >
            {testResult.success ? (
              <CheckCircle className="h-4 w-4" />
            ) : (
              <AlertCircle className="h-4 w-4" />
            )}
            <AlertDescription>{testResult.message}</AlertDescription>
          </Alert>
        )}

        <DialogFooter className="gap-2">
          <Button
            variant="outline"
            onClick={handleCancel}
            data-testid="cancel-btn"
          >
            Cancel
          </Button>

          {/* Test button (only for action nodes) */}
          {nodeType.category === 'action' && onTestNode && (
            <Button
              variant="secondary"
              onClick={handleTestNode}
              disabled={isTesting}
              data-testid="test-node-btn"
            >
              <Play className="w-4 h-4 mr-2" />
              {isTesting ? 'Testing...' : 'Test Node'}
            </Button>
          )}

          <Button
            variant="default"
            onClick={handleSave}
            data-testid="save-btn"
            className="bg-blue-600 hover:bg-blue-700"
          >
            Save Changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
```

---

## Implementation: Property Panel Component

### NodePropertyPanel.tsx

```typescript
import React from 'react';
import { Node, NodeType } from '../types';
import SendEmailPanel from './property-panels/node-specific/SendEmailPanel';
import WebhookPanel from './property-panels/node-specific/WebhookPanel';
import HttpRequestPanel from './property-panels/node-specific/HttpRequestPanel';
import DelayPanel from './property-panels/node-specific/DelayPanel';
import ConditionalPanel from './property-panels/node-specific/ConditionalPanel';
// ... import 20+ more panels

interface NodePropertyPanelProps {
  node: Node;
  nodeType: NodeType;
  config: Record<string, any>;
  onUpdateField: (field: string, value: any) => void;
  validationErrors: Record<string, string>;
}

export default function NodePropertyPanel({
  node,
  nodeType,
  config,
  onUpdateField,
  validationErrors
}: NodePropertyPanelProps) {
  // Route to specific panel based on node type
  switch (node.type) {
    case 'send-email':
      return (
        <SendEmailPanel
          config={config}
          onUpdateField={onUpdateField}
          errors={validationErrors}
        />
      );

    case 'webhook':
      return (
        <WebhookPanel
          config={config}
          onUpdateField={onUpdateField}
          errors={validationErrors}
        />
      );

    case 'http-request':
      return (
        <HttpRequestPanel
          config={config}
          onUpdateField={onUpdateField}
          errors={validationErrors}
        />
      );

    case 'delay-execution':
      return (
        <DelayPanel
          config={config}
          onUpdateField={onUpdateField}
          errors={validationErrors}
        />
      );

    case 'conditional-action':
    case 'if-condition':
      return (
        <ConditionalPanel
          config={config}
          onUpdateField={onUpdateField}
          errors={validationErrors}
        />
      );

    // ... 20+ more cases for each action type

    default:
      return (
        <div className="text-sm text-gray-500">
          No configuration panel available for {nodeType.name}
        </div>
      );
  }
}
```

---

## Implementation: Example Property Panel

### SendEmailPanel.tsx

```typescript
import React from 'react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Info } from 'lucide-react';
import EmailTagInput from '../fields/EmailTagInput';
import CodeEditor from '../fields/CodeEditor';

interface SendEmailPanelProps {
  config: Record<string, any>;
  onUpdateField: (field: string, value: any) => void;
  errors: Record<string, string>;
}

export default function SendEmailPanel({
  config,
  onUpdateField,
  errors
}: SendEmailPanelProps) {
  return (
    <div className="space-y-6">
      {/* Info alert */}
      <Alert>
        <Info className="h-4 w-4" />
        <AlertDescription>
          You can use form field variables like {'{field_name}'} or {'{email}'} in any field.
        </AlertDescription>
      </Alert>

      {/* To field */}
      <div className="space-y-2">
        <Label htmlFor="to">
          To <span className="text-red-500">*</span>
        </Label>
        <EmailTagInput
          id="to"
          value={config.to || ''}
          onChange={(value) => onUpdateField('to', value)}
          placeholder="recipient@example.com or {email}"
          error={errors.to}
        />
        {errors.to && (
          <p className="text-sm text-red-500">{errors.to}</p>
        )}
      </div>

      {/* From Name field */}
      <div className="space-y-2">
        <Label htmlFor="from-name">From Name</Label>
        <Input
          id="from-name"
          value={config.from_name || ''}
          onChange={(e) => onUpdateField('from_name', e.target.value)}
          placeholder="Your Company"
        />
      </div>

      {/* From Email field */}
      <div className="space-y-2">
        <Label htmlFor="from-email">From Email</Label>
        <Input
          id="from-email"
          type="email"
          value={config.from_email || ''}
          onChange={(e) => onUpdateField('from_email', e.target.value)}
          placeholder="noreply@yourcompany.com"
        />
      </div>

      {/* Subject field */}
      <div className="space-y-2">
        <Label htmlFor="subject">
          Subject <span className="text-red-500">*</span>
        </Label>
        <Input
          id="subject"
          value={config.subject || ''}
          onChange={(e) => onUpdateField('subject', e.target.value)}
          placeholder="Thank you for your submission"
          error={errors.subject}
        />
        {errors.subject && (
          <p className="text-sm text-red-500">{errors.subject}</p>
        )}
      </div>

      {/* Body Type selector */}
      <div className="space-y-2">
        <Label htmlFor="body-type">Email Body Type</Label>
        <Select
          value={config.body_type || 'html'}
          onValueChange={(value) => onUpdateField('body_type', value)}
        >
          <SelectTrigger id="body-type">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="html">HTML</SelectItem>
            <SelectItem value="visual">Visual Builder</SelectItem>
            <SelectItem value="text">Plain Text</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* Body field (conditional based on body_type) */}
      <div className="space-y-2">
        <Label htmlFor="body">
          Email Body <span className="text-red-500">*</span>
        </Label>

        {config.body_type === 'html' || !config.body_type ? (
          <CodeEditor
            id="body"
            value={config.body || ''}
            onChange={(value) => onUpdateField('body', value)}
            language="html"
            height="200px"
            error={errors.body}
          />
        ) : config.body_type === 'visual' ? (
          <div className="p-4 border border-gray-200 rounded-lg bg-gray-50 text-sm text-gray-600">
            Visual builder content will be edited in the Email Builder tab.
            This workflow will use the configured email template.
          </div>
        ) : (
          <Textarea
            id="body"
            value={config.body || ''}
            onChange={(e) => onUpdateField('body', e.target.value)}
            placeholder="Enter email body..."
            rows={8}
            className={errors.body ? 'border-red-500' : ''}
          />
        )}

        {errors.body && (
          <p className="text-sm text-red-500">{errors.body}</p>
        )}
      </div>

      {/* CC field */}
      <div className="space-y-2">
        <Label htmlFor="cc">CC (optional)</Label>
        <EmailTagInput
          id="cc"
          value={config.cc || ''}
          onChange={(value) => onUpdateField('cc', value)}
          placeholder="Add Cc recipients..."
        />
      </div>

      {/* BCC field */}
      <div className="space-y-2">
        <Label htmlFor="bcc">BCC (optional)</Label>
        <EmailTagInput
          id="bcc"
          value={config.bcc || ''}
          onChange={(value) => onUpdateField('bcc', value)}
          placeholder="Add Bcc recipients..."
        />
      </div>

      {/* Reply-To field */}
      <div className="space-y-2">
        <Label htmlFor="reply-to">Reply-To (optional)</Label>
        <Input
          id="reply-to"
          type="email"
          value={config.reply_to || ''}
          onChange={(e) => onUpdateField('reply_to', e.target.value)}
          placeholder="support@yourcompany.com"
        />
      </div>

      {/* Attachments */}
      <div className="space-y-2">
        <Label htmlFor="attachments">Attachments</Label>
        <Input
          id="attachments"
          value={config.attachments || ''}
          onChange={(e) => onUpdateField('attachments', e.target.value)}
          placeholder="{field_file_upload} or /path/to/file.pdf"
        />
        <p className="text-xs text-gray-500">
          Comma-separated list of file paths or form field variables
        </p>
      </div>
    </div>
  );
}
```

---

## Field Components

### NodeTextField.tsx

```typescript
import React from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { clsx } from 'clsx';

interface NodeTextFieldProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  required?: boolean;
  error?: string;
  helpText?: string;
  type?: 'text' | 'email' | 'url' | 'number';
}

export function NodeTextField({
  label,
  value,
  onChange,
  placeholder,
  required = false,
  error,
  helpText,
  type = 'text'
}: NodeTextFieldProps) {
  return (
    <div className="space-y-2">
      <Label>
        {label}
        {required && <span className="text-red-500 ml-1">*</span>}
      </Label>

      <Input
        type={type}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className={clsx(error && 'border-red-500')}
      />

      {error && <p className="text-sm text-red-500">{error}</p>}
      {helpText && !error && (
        <p className="text-xs text-gray-500">{helpText}</p>
      )}
    </div>
  );
}
```

### NodeCodeEditor.tsx

```typescript
import React from 'react';
import { Label } from '@/components/ui/label';
import { clsx } from 'clsx';
import MonacoEditor from '@monaco-editor/react';

interface NodeCodeEditorProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  language: 'html' | 'javascript' | 'json' | 'css';
  height?: string;
  required?: boolean;
  error?: string;
  helpText?: string;
}

export function NodeCodeEditor({
  label,
  value,
  onChange,
  language,
  height = '200px',
  required = false,
  error,
  helpText
}: NodeCodeEditorProps) {
  return (
    <div className="space-y-2">
      <Label>
        {label}
        {required && <span className="text-red-500 ml-1">*</span>}
      </Label>

      <div
        className={clsx(
          'border rounded-md overflow-hidden',
          error ? 'border-red-500' : 'border-gray-200'
        )}
      >
        <MonacoEditor
          height={height}
          language={language}
          value={value}
          onChange={(value) => onChange(value || '')}
          theme="vs-light"
          options={{
            minimap: { enabled: false },
            lineNumbers: 'on',
            scrollBeyondLastLine: false,
            wordWrap: 'on',
            fontSize: 13
          }}
        />
      </div>

      {error && <p className="text-sm text-red-500">{error}</p>}
      {helpText && !error && (
        <p className="text-xs text-gray-500">{helpText}</p>
      )}
    </div>
  );
}
```

---

## Integration with Canvas

### Node.tsx (Updated)

```typescript
// In Node.jsx component, add double-click handler
const handleDoubleClick = (e: React.MouseEvent) => {
  e.stopPropagation();
  onOpenConfig(node.id);
};

return (
  <div
    className="node"
    onDoubleClick={handleDoubleClick}
    // ... other props
  >
    {/* Node content */}

    {/* Settings button */}
    <button
      onClick={(e) => {
        e.stopPropagation();
        onOpenConfig(node.id);
      }}
      className="absolute top-2 right-2 w-6 h-6 bg-white rounded shadow hover:bg-gray-100"
      title="Configure node"
    >
      <Settings className="w-4 h-4" />
    </button>
  </div>
);
```

### Canvas.jsx (Updated)

```typescript
// Add state for config modal
const [configModalNode, setConfigModalNode] = useState<Node | null>(null);
const [showConfigModal, setShowConfigModal] = useState(false);

// Open config modal
const openNodeConfig = (nodeId: string) => {
  const node = nodes.find(n => n.id === nodeId);
  if (node) {
    setConfigModalNode(node);
    setShowConfigModal(true);
  }
};

// Save config
const saveNodeConfig = (nodeId: string, config: Record<string, any>) => {
  updateNode(nodeId, { config });
};

// Test node (optional)
const testNode = async (nodeId: string, config: Record<string, any>) => {
  try {
    const response = await fetch('/wp-json/super-forms/v1/automations/test-node', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nodeId, config, context: {} })
    });

    const result = await response.json();
    return {
      success: result.success,
      message: result.message,
      data: result.data
    };
  } catch (error) {
    return {
      success: false,
      message: error.message
    };
  }
};

return (
  <>
    <div className="canvas">
      {nodes.map(node => (
        <Node
          key={node.id}
          node={node}
          onOpenConfig={openNodeConfig}
          // ... other props
        />
      ))}
    </div>

    {/* Config Modal */}
    <NodeConfigModal
      node={configModalNode}
      open={showConfigModal}
      onOpenChange={setShowConfigModal}
      onSave={saveNodeConfig}
      onTestNode={testNode}
    />
  </>
);
```

---

## Visual Design

### Modal Appearance

**Size**: 800px wide × 600px tall (responsive)
**Z-index**: `z-[100000]` (above WordPress admin bar)
**Backdrop**: Semi-transparent black (`bg-black/50`)
**Animation**: Fade in/out with scale transform

### Typography

- **Title**: 20px, font-semibold
- **Description**: 14px, text-gray-600
- **Field Labels**: 13px, font-medium
- **Help Text**: 12px, text-gray-500
- **Error Text**: 12px, text-red-500

### Color Scheme

- **Primary Button**: Blue-600 (#2563eb)
- **Secondary Button**: Gray-200 (#e5e7eb)
- **Error State**: Red-500 (#ef4444)
- **Success State**: Green-500 (#22c55e)
- **Info Alert**: Blue-50 background with Blue-600 border

### Spacing

- Modal padding: 24px
- Section spacing: 24px (space-y-6)
- Field spacing: 16px (space-y-4)
- Label to input: 8px (space-y-2)

---

## Accessibility

### Keyboard Navigation

- **Tab**: Navigate between fields
- **Shift+Tab**: Navigate backwards
- **Escape**: Close modal (with unsaved changes warning)
- **Enter**: Submit form (when focused on save button)
- **Arrow keys**: Navigate tabs

### ARIA Attributes

```html
<dialog
  role="dialog"
  aria-labelledby="modal-title"
  aria-describedby="modal-description"
  aria-modal="true"
>
  <h2 id="modal-title">Configure Send Email</h2>
  <p id="modal-description">Configure email settings for this action</p>

  <input
    aria-label="To email address"
    aria-required="true"
    aria-invalid={!!errors.to}
    aria-describedby={errors.to ? "to-error" : "to-help"}
  />

  {errors.to && (
    <span id="to-error" role="alert">{errors.to}</span>
  )}
</dialog>
```

### Screen Reader Support

- Modal title announced when opened
- Required fields marked with asterisk and aria-required
- Error messages linked to fields via aria-describedby
- Success/failure announcements after test execution

---

## Performance Optimization

### Lazy Loading Property Panels

```typescript
// Import panels dynamically to reduce initial bundle size
const SendEmailPanel = React.lazy(() =>
  import('./property-panels/node-specific/SendEmailPanel')
);

const WebhookPanel = React.lazy(() =>
  import('./property-panels/node-specific/WebhookPanel')
);

// In NodePropertyPanel.tsx
return (
  <Suspense fallback={<LoadingSpinner />}>
    {renderPanelForNodeType()}
  </Suspense>
);
```

### Debounced Validation

```typescript
import { useDebouncedCallback } from 'use-debounce';

const debouncedValidate = useDebouncedCallback(
  (config) => {
    const errors = validateConfig(config);
    setValidationErrors(errors);
  },
  300 // 300ms delay
);

// Call on field change
const updateField = (field: string, value: any) => {
  const newConfig = { ...config, [field]: value };
  setConfig(newConfig);
  debouncedValidate(newConfig);
};
```

---

## Comparison with Industry Standards

### n8n Pattern
- ✅ Modal-based configuration
- ✅ Tabbed interface
- ✅ Test execution button
- ✅ Field validation

### Zapier Pattern
- ✅ Focused editing experience
- ✅ Clear save/cancel actions
- ✅ Inline help text
- ✅ Tag replacement support ({field_name})

### Make (Integromat) Pattern
- ✅ Advanced settings in separate tab
- ✅ Code editor for custom logic
- ✅ Test with sample data

**Our Approach**: Best of all three, adapted for WordPress/Super Forms context

---

## Testing Strategy

### Unit Tests

```typescript
describe('NodeConfigModal', () => {
  it('opens when node is selected', () => {
    const { getByTestId } = render(
      <NodeConfigModal node={mockNode} open={true} />
    );
    expect(getByTestId('node-config-modal')).toBeInTheDocument();
  });

  it('validates required fields', async () => {
    const { getByTestId, getByText } = render(
      <NodeConfigModal node={mockSendEmailNode} open={true} />
    );

    const saveButton = getByTestId('save-btn');
    fireEvent.click(saveButton);

    expect(getByText('Recipient email is required')).toBeInTheDocument();
  });

  it('calls onSave with config when valid', async () => {
    const onSave = jest.fn();
    const { getByTestId, getByLabelText } = render(
      <NodeConfigModal
        node={mockSendEmailNode}
        open={true}
        onSave={onSave}
      />
    );

    fireEvent.change(getByLabelText('To'), {
      target: { value: 'test@example.com' }
    });
    fireEvent.change(getByLabelText('Subject'), {
      target: { value: 'Test Subject' }
    });

    const saveButton = getByTestId('save-btn');
    fireEvent.click(saveButton);

    expect(onSave).toHaveBeenCalledWith(mockSendEmailNode.id, {
      to: 'test@example.com',
      subject: 'Test Subject',
      // ... other fields
    });
  });
});
```

### Integration Tests

```typescript
describe('Node Configuration Integration', () => {
  it('saves config to node and updates canvas', async () => {
    const { getByTestId } = render(<Canvas />);

    // Double-click node to open config
    const node = getByTestId('node-send-email-1');
    fireEvent.doubleClick(node);

    // Edit config
    const modal = getByTestId('node-config-modal');
    // ... edit fields

    // Save
    const saveButton = getByTestId('save-btn');
    fireEvent.click(saveButton);

    // Verify node updated on canvas
    expect(node).toHaveAttribute('data-config', /* updated config */);
  });
});
```

---

## Conclusion

The node configuration modal system provides:

1. **Focused Editing**: Modal isolates configuration from canvas clutter
2. **Validation**: Real-time field validation prevents errors
3. **Testing**: Test nodes before saving to workflow
4. **Extensibility**: Easy to add new node types with custom panels
5. **Accessibility**: Full keyboard navigation and screen reader support
6. **Performance**: Lazy-loaded panels reduce initial bundle size

This pattern balances the inline editing approach of GmailChrome with the modal-based pattern used by industry leaders (n8n, Zapier, Make), adapted specifically for the WordPress/Super Forms context.
