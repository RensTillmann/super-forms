# Phase 22: Integrate ai-automation Visual Workflow Builder

## Terminology Reference

See [Phase 26: Terminology Standardization](26-terminology-standardization.md) for the definitive naming convention:
- **Automation** = The saved entity (container)
- **Trigger** = Event node that starts execution
- **Action** = Task node that does something
- **Condition** = Logic node for branching
- **Control** = Flow node (delay, schedule, stop)

## Overview

Integrate the custom-built ai-automation node-based workflow editor into Super Forms as the visual interface for the Automations system. This provides an n8n-style visual automation builder without external dependencies.

## Work Log

### 2025-11-29 (Session 1)

#### Analysis Complete
- Cloned ai-automation repository for architectural analysis
- **State Management**: Custom `useNodeEditor` hook with pure React state (no Redux/Zustand)
- **Canvas Rendering**: SVG-based connections with CSS transforms for node positioning
- **Drag/Drop**: Native HTML5 drag/drop APIs + mouse event handlers
- **Bundle Size**: 76KB gzipped (70% smaller than ReactFlow)
- **Dependencies**: React 19, Tailwind 3.4, Lucide icons only

### 2025-11-29 (Session 2)

#### Visual Enhancements Implemented
- **GroupContainer Component**: New visual grouping system for organizing related nodes
  - Drag header to move group + all contained nodes simultaneously
  - Bottom-left and bottom-right resize handles with auto-membership detection
  - Editable group name (inline edit on click)
  - Delete button preserves nodes on canvas
  - Dashed border, background tint, hover effects
  - File: `/src/react/admin/components/form-builder/automations-tab/canvas/GroupContainer.tsx` (393 lines)

- **ConnectionOverlay Enhancements**: Electric animations and interactive features
  - Animated electric light source traveling along connection paths (2s loop)
  - Triple-layer glow filter (3px, 6px, 12px Gaussian blur)
  - Radial gradient light (white → light blue → blue → transparent)
  - Pulsing opacity animation (0.6-1.0, 0.4s duration)
  - Connection labels showing output port names at midpoint
  - "Click to delete" hint appears on hover
  - Red hover state with enhanced glow
  - File: `/src/react/admin/components/form-builder/automations-tab/canvas/ConnectionOverlay.tsx` (446 lines)

- **Canvas Component Updates**: Rendering layers and grid background
  - Dot grid background pattern (20px, scales with zoom)
  - Proper z-order rendering: Groups (-1) → Connections (-1) → Nodes (1+)
  - Group integration with viewport transforms
  - File: `/src/react/admin/components/form-builder/automations-tab/canvas/Canvas.tsx` (480 lines)

- **Node Component Refinements**: Visual polish and responsiveness
  - Conditional transitions (disabled during drag for snappy feel)
  - Enhanced port visibility (scale 1.3x + glow when connecting)
  - Status indicator (green pulsing dot in top-right)
  - Invisible 6×6px hit areas for easier port clicking
  - File: `/src/react/admin/components/form-builder/automations-tab/canvas/Node.tsx` (189 lines)

- **useNodeEditor Hook Extensions**: Group management operations
  - `addGroup(name, bounds, nodeIds)` - Create new group
  - `updateGroup(groupId, updates)` - Update group properties
  - `removeGroup(groupId)` - Delete group (preserves nodes)
  - `moveGroup(groupId, deltaX, deltaY, phase)` - Move group and contained nodes
  - `createGroupFromSelection()` - Create group from selected nodes with auto-calculated bounds
  - File: `/src/react/admin/components/form-builder/automations-tab/hooks/useNodeEditor.ts` (700+ lines)

#### Documentation Updated
- **docs/CLAUDE.javascript.md**: Added comprehensive "Visual Workflow Builder (Triggers Tab)" section
  - Core architecture overview
  - GroupContainer component API and implementation details
  - ConnectionOverlay visual enhancements and SVG filters
  - Canvas rendering layers and coordinate systems
  - Node component refinements
  - useNodeEditor group management functions with code examples
  - State structure definitions for WorkflowGroup interface

## Technical Architecture Analysis

### ai-automation Current State

**Core Hook: `useNodeEditor.js` (704 lines)**
- **State Management Pattern**: useState + useCallback (pure React)
- **No external state libraries**: Not using Redux, Zustand, MobX, or Jotai
- **Performance**: useRef for counters, memoization via useCallback
- **Data Structure**:
  ```javascript
  {
    nodes: [
      {
        id: 'node-1',
        type: 'webhook',
        position: { x: 200, y: 200 },
        config: { url: '', method: 'POST' },
        selected: false,
        zIndex: 1
      }
    ],
    connections: [
      {
        id: 'conn-123',
        from: 'node-1',
        fromOutput: 'webhook',
        to: 'node-2',
        toInput: 'data',
        selected: false
      }
    ],
    groups: [
      {
        id: 'group-1',
        name: 'New Group',
        nodeIds: ['node-1', 'node-2'],
        bounds: { x, y, width, height },
        color: '#3b82f6'
      }
    ],
    viewport: { x: 0, y: 0, zoom: 1 }
  }
  ```

**Canvas Rendering: `Canvas.jsx`**
- **Positioning**: CSS transforms for hardware acceleration
- **Coordinate System**: `screenToCanvas()` helper converts screen → canvas coordinates
- **Drag Types**: Node drag, viewport pan, selection rectangle
- **Events**: Native mousedown/mousemove/mouseup with drag state tracking
- **Grid Snapping**: 20px grid alignment (optional)

**Connection Rendering: `ConnectionOverlay.jsx`**
- **SVG Overlay**: Absolute positioned SVG layer above canvas
- **Path Algorithm**: Cubic Bezier curves for smooth connections
  ```javascript
  // Control points calculation
  const dx = endPos.x - startPos.x;
  const dy = endPos.y - startPos.y;
  const distance = Math.sqrt(dx * dx + dy * dy);
  const curve = Math.min(distance / 2, 100);

  // SVG path: M startX startY C cp1x cp1y cp2x cp2y endX endY
  ```
- **Connection Snapping**: 12px snap radius for auto-connecting to input ports
- **Hover Detection**: Path hit detection for selecting connections

**Node Types: `nodeTypes.js`**
- **28 Predefined Types**: 9 triggers + 16 actions + 3 conditions
- **Categories**: Trigger (green), Action (blue), Condition (orange)
- **Configuration Schema**:
  ```javascript
  {
    id: 'webhook',
    name: 'Webhook',
    category: 'trigger',
    color: '#10b981',
    icon: Webhook, // Lucide icon component
    description: 'Receive HTTP requests',
    inputs: [], // Input ports (triggers have none)
    outputs: ['webhook'], // Output port names
    config: { // Default configuration
      url: '',
      method: 'POST',
      authentication: 'none'
    }
  }
  ```

## Integration Strategy

### Option A: Copy Components to Super Forms ⭐ (Recommended)

**Directory Structure:**
```
/src/react/admin/components/form-builder/automations-tab/
├── TriggersTab.tsx               # Main tab wrapper (visual mode only)
├── VisualBuilder.tsx             # Main workflow canvas wrapper
│
├── canvas/                       # FROM ai-automation
│   ├── Canvas.tsx                # Core canvas with pan/zoom/drag
│   ├── ConnectionOverlay.tsx     # SVG connection rendering
│   ├── GroupContainer.tsx        # Group visual containers
│   └── Node.tsx                  # Individual node rendering
│
├── panels/                       # FROM ai-automation
│   ├── NodePalette.tsx           # Left: Draggable node types
│   ├── PropertiesPanel.tsx       # Right: Node configuration
│   ├── NodeSettingsPanel.tsx     # Node-specific settings
│   ├── Toolbar.tsx               # Top: Save/Test/Template controls
│   └── TemplateModal.tsx         # Template selection UI
│
├── hooks/
│   └── useNodeEditor.ts          # Port from .js → .ts with types
│
├── data/
│   ├── nodeTypes.ts              # Adapt to Super Forms events/actions
│   └── templates.ts              # Pre-built workflow templates
│
└── types/
    └── workflow.types.ts         # TypeScript type definitions
```

**Adaptation Required:**

1. **Convert JavaScript → TypeScript**
   - All `.jsx` files → `.tsx`
   - All `.js` files → `.ts`
   - Add type definitions for all interfaces

2. **Replace Node Types with Super Forms Events/Actions**
   ```typescript
   // OLD (ai-automation generic nodes)
   {
     id: 'webhook',
     name: 'Webhook',
     category: 'trigger',
     outputs: ['webhook']
   }

   // NEW (Super Forms specific)
   {
     id: 'form.submitted',
     name: 'Form Submitted',
     category: 'trigger',
     outputs: ['entry_data', 'form_data'],
     config: {
       formId: null,        // Which form to trigger on
       conditions: null     // Optional pre-filter
     }
   }
   ```

3. **Update Styling for WordPress Admin**
   - Current: Dark theme with Tailwind 3.4
   - Target: Light/dark theme matching WP admin
   - Replace color scheme:
     - Triggers: `#10b981` (green) → Keep
     - Actions: `#3b82f6` (blue) → Keep
     - Conditions: `#f97316` (orange) → Keep
     - Background: `#1f2937` (dark) → `#f0f0f1` (WP admin gray)

4. **Add WordPress REST API Integration**
   ```typescript
   // Replace export/import with WordPress API
   const saveWorkflow = async () => {
     const response = await fetch(
       `${window.sfuiData.restUrl}/triggers/${triggerId}`,
       {
         method: triggerId ? 'PUT' : 'POST',
         headers: {
           'Content-Type': 'application/json',
           'X-WP-Nonce': window.sfuiData.restNonce
         },
         body: JSON.stringify({
           name: workflowName,
           workflow_type: 'visual',
           workflow_graph: JSON.stringify({ nodes, connections }),
           enabled: true
           // NOTE: No scope/scope_id/event_id at trigger level!
           // Scope is configured in event node config (node.config.scope, node.config.formId)
         })
       }
     );
   };
   ```

5. **Add PHP Execution Engine**
   ```php
   // /src/includes/class-visual-workflow-executor.php
   class SUPER_Workflow_Executor {
       public function execute($trigger_id, $context) {
           $trigger = SUPER_Automation_DAL::get($trigger_id);
           $graph = json_decode($trigger['workflow_graph'], true);

           return $this->execute_graph(
               $graph['nodes'],
               $graph['connections'],
               $context
           );
       }

       private function execute_graph($nodes, $connections, $context) {
           // Find trigger node (starting point)
           $trigger_node = array_filter($nodes, fn($n) =>
               isset($n['type']) && strpos($n['type'], 'form.') === 0
           );

           if (empty($trigger_node)) {
               throw new Exception('No trigger node found');
           }

           // Execute nodes in dependency order
           $execution_order = $this->topological_sort($nodes, $connections);

           foreach ($execution_order as $node_id) {
               $node = $this->find_node($nodes, $node_id);
               $this->execute_node($node, $context);
           }
       }

       private function execute_node($node, $context) {
           $type = $node['type'];
           $config = $node['config'];

           // Map visual node types to action classes
           if (strpos($type, 'form.') === 0) {
               // Trigger node - skip (already fired)
               return;
           }

           // Map node type to action class
           $action_type = $this->map_node_to_action($type);
           $action = SUPER_Automation_Registry::get_action($action_type);

           if (!$action) {
               throw new Exception("Unknown action: $action_type");
           }

           return $action->execute($config, $context);
       }

       private function map_node_to_action($node_type) {
           // Map visual node types to trigger action types
           $map = [
               'send-email' => 'send_email',
               'http-post' => 'http_request',
               'database-insert' => 'database_insert',
               'if-condition' => 'conditional_action',
               'delay' => 'delay_execution',
               // ... etc
           ];

           return $map[$node_type] ?? null;
       }
   }
   ```

## Node Type Mapping

### Super Forms Events (36) → Visual Trigger Nodes

| Super Forms Event | Visual Node ID | Icon | Color |
|-------------------|----------------|------|-------|
| `form.submitted` | `form-submitted` | FormInput | Green |
| `form.spam_detected` | `spam-detected` | Shield | Green |
| `entry.created` | `entry-created` | FileText | Green |
| `entry.status_changed` | `status-changed` | RefreshCw | Green |
| `payment.stripe.payment_succeeded` | `stripe-payment` | CreditCard | Green |
| `payment.paypal.capture_completed` | `paypal-payment` | DollarSign | Green |
| `session.abandoned` | `session-abandoned` | Clock | Green |

### Super Forms Actions (20+) → Visual Action Nodes

| Super Forms Action | Visual Node ID | Icon | Color |
|--------------------|----------------|------|-------|
| `send_email` | `send-email` | Mail | Blue |
| `webhook` | `webhook-call` | Webhook | Blue |
| `http_request` | `http-request` | Globe | Blue |
| `update_entry_status` | `update-status` | RefreshCw | Blue |
| `delete_entry` | `delete-entry` | Trash | Blue |
| `create_post` | `create-post` | FileText | Blue |
| `mailpoet.add_subscriber` | `mailpoet-subscribe` | Users | Blue |
| `woocommerce.add_to_cart` | `wc-add-cart` | ShoppingCart | Blue |

### Condition Nodes

| Condition Type | Visual Node ID | Icon | Color |
|----------------|----------------|------|-------|
| `conditional_action` | `if-condition` | GitBranch | Orange |
| AND/OR groups | `condition-group` | Filter | Orange |
| Switch/case | `switch` | HelpCircle | Orange |

## Related Documentation

### Real-World Use Cases & Examples

**[01epic-visual-workflow-examples.md](./01epic-visual-workflow-examples.md)** - Comprehensive real-life workflow examples demonstrating the power of visual automation:

- **E-commerce Order Processing** - VIP customer routing with conditional logic
- **Lead Nurturing Campaign** - Multi-day drip campaign with engagement tracking
- **Support Ticket Routing** - Department routing with priority escalation
- **Event Registration with Payment** - Stripe integration, waitlist management
- **Progressive Form Auto-Save** - Session-based recovery and abandonment detection
- **Abandoned Cart Recovery** - Time-based reminder sequence with discount escalation
- **Multi-Step Application Process** - Document upload, reference checks, interview scheduling
- **Subscription Management** - Stripe subscription lifecycle with renewal reminders

**Key Insights from Epics:**
- Visual workflows handle 9-18 nodes per workflow efficiently
- Common patterns: Conditional branching, delays + Action Scheduler, multi-channel communication
- Database storage: Single JSON column per workflow (no joins needed)
- Execution depth: Maximum 5-7 levels prevents stack overflow
- Reusable templates: Extract common sub-workflows for consistency

---

### Architectural Decisions

**[02architectural-decision-visual-list-conversion.md](./02architectural-decision-visual-list-conversion.md)** - Analysis of visual ↔ list view conversion strategies:

**Decision: Option C - Parallel Systems (No Conversion)** ✅

**Why:**
- **Lowest Risk**: No conversion logic = no data loss
- **Fastest Implementation**: 1-2 days vs 5-7 days for bidirectional conversion
- **User Choice**: Power users can stick with list mode if preferred
- **Perfect Backwards Compatibility**: Existing triggers work unchanged
- **Future-Proof**: Can deprecate list mode later if desired

**Implementation:**
- Mode selection at trigger creation (Visual vs List)
- Once created, trigger stays in chosen mode forever
- Separate code paths: Visual uses `workflow_graph`, List uses `trigger_actions` table
- Execution router checks `workflow_type` and routes accordingly

**Migration Path (Optional):**
- Add "Convert to Visual" button in list mode editor
- One-time conversion with preview
- Old action records archived (not deleted)

---

### Node Configuration UI Pattern

**[03node-configuration-modal-ui-pattern.md](./03node-configuration-modal-ui-pattern.md)** - Modal-based node configuration system:

**Pattern:** Shadcn/UI Dialog with tabbed property panels

**Why Modal Instead of Inline Editing:**
- Canvas has many nodes (10-30+) in dense layout
- Inline editing would clutter the canvas
- Focused editing experience prevents mistakes
- Industry standard (n8n, Zapier, Make all use modals)

**Component Architecture:**
```
<NodeConfigModal>
  <Dialog>
    <DialogHeader>
      <NodeIcon + Title + Description>
    </DialogHeader>

    <Tabs>
      <Tab: General> - Main node settings
      <Tab: Advanced> - Advanced configuration
      <Tab: Conditions> - Execution conditions
    </Tabs>

    <DialogFooter>
      <Button: Cancel>
      <Button: Test Node>
      <Button: Save Changes>
    </DialogFooter>
  </Dialog>
</NodeConfigModal>
```

**Key Features:**
- Real-time validation with inline error messages
- Test node execution with sample data
- Unsaved changes warning on close
- Keyboard shortcuts (Escape to close, Tab to navigate)
- Lazy-loaded property panels (reduce bundle size)
- ARIA attributes for accessibility

**Node-Specific Panels:**
- SendEmailPanel.tsx - Email configuration with code editor
- WebhookPanel.tsx - Webhook URL, method, headers, body
- HttpRequestPanel.tsx - Full Postman-like HTTP client
- DelayPanel.tsx - Duration and unit selection
- ConditionalPanel.tsx - Condition builder UI
- 20+ more panels for each action type

---

## UI/UX Requirements

### Automation Management Interface

Users access automations through **Form Edit Page → Triggers Tab**:

```
┌────────────────────────────────────────────────────────────┐
│ [Workflow Name ▼] [+ Add Automation] [🗑️] [Save] [Test]  │  ← Toolbar
└────────────────────────────────────────────────────────────┘
│                                                             │
│  [NodePalette]   [Canvas with Nodes]   [PropertiesPanel]   │
│                                                             │
```

**Key Features:**

1. **Automation Dropdown**
   - Lists ALL automations (no filtering by form)
   - Click to switch between automations
   - Shows automation name and status (enabled/disabled)

2. **Add/Delete Buttons**
   - `[+ Add Automation]` - Create new workflow
   - `[🗑️]` - Delete current automation (with confirmation)

3. **Unsaved Changes Warning**
   - When switching automations or leaving page without saving
   - Dialog shows:
     - "You have unsaved changes"
     - [Discard Changes] [Save Changes] buttons
   - Prevents accidental data loss

4. **Save Workflow**
   - Saves current workflow to database
   - Updates `workflow_graph` JSON with nodes/connections
   - Shows success/error notification

### Node-Level Scope Configuration

Trigger nodes (e.g., "Form Submitted") have scope settings in PropertiesPanel:

```
┌─────────────────────────────────────────────┐
│ Form Submission                             │
├─────────────────────────────────────────────┤
│ Listen to submissions from:                 │
│                                             │
│ ○ Current form (Contact Form #123)         │
│ ○ All forms                                 │
│ ○ Specific form: [Dropdown ▼]              │
│                                             │
│ ✓ Only trigger if:                          │
│   Field "status" equals "approved"          │
└─────────────────────────────────────────────┘
```

**Scope Types:**
- `current` - Listen to current form only (default)
- `all` - Listen to ALL forms globally
- `specific` - Listen to specific form by ID

**Storage:** Scope configuration saved in `node.config.scope` and `node.config.formId`

---

## Implementation Phases

### Phase 22.1: Port Components to TypeScript (3-4 days)

**Files to Convert:**
- `useNodeEditor.js` → `useNodeEditor.ts` (add ~200 lines of types)
- `Canvas.jsx` → `Canvas.tsx`
- `ConnectionOverlay.jsx` → `ConnectionOverlay.tsx`
- `Node.jsx` → `Node.tsx`
- `NodePalette.jsx` → `NodePalette.tsx`
- `PropertiesPanel.jsx` → `PropertiesPanel.tsx`
- `Toolbar.jsx` → `Toolbar.tsx`
- `TemplateModal.jsx` → `TemplateModal.tsx`
- `GroupContainer.jsx` → `GroupContainer.tsx`
- `nodeTypes.js` → `nodeTypes.ts`
- `templates.js` → `templates.ts`

**Type Definitions to Create:**
```typescript
// /src/react/admin/components/form-builder/automations-tab/types/workflow.types.ts

export interface WorkflowNode {
  id: string;
  type: string;
  position: { x: number; y: number };
  config: Record<string, any>;
  selected: boolean;
  zIndex: number;
  isGroupDragging?: boolean;
  groupDragOffset?: { x: number; y: number };
}

export interface WorkflowConnection {
  id: string;
  from: string;
  fromOutput: string;
  to: string;
  toInput: string;
  selected: boolean;
}

export interface WorkflowGroup {
  id: string;
  name: string;
  nodeIds: string[];
  bounds: {
    x: number;
    y: number;
    width: number;
    height: number;
  };
  color: string;
  zIndex: number;
}

export interface Viewport {
  x: number;
  y: number;
  zoom: number;
}

export interface NodeTypeDefinition {
  id: string;
  name: string;
  category: 'trigger' | 'action' | 'condition';
  color: string;
  icon: React.ComponentType<{ className?: string }>;
  description: string;
  inputs: string[];
  outputs: string[];
  config: Record<string, any>;
}

export interface WorkflowTemplate {
  id: string;
  name: string;
  description: string;
  category: string;
  nodes: WorkflowNode[];
  connections: WorkflowConnection[];
  groups?: WorkflowGroup[];
}
```

### Phase 22.2: Map Super Forms Events/Actions to Node Types (2-3 days)

**Create Super Forms Node Definitions:**
```typescript
// /src/react/admin/components/form-builder/automations-tab/data/superFormsNodeTypes.ts

import {
  FormInput, Shield, FileText, RefreshCw, Mail,
  Globe, Webhook, Trash, ShoppingCart, Users,
  CreditCard, DollarSign, Clock, GitBranch, Filter
} from 'lucide-react';

export const SUPER_FORMS_NODE_TYPES = {
  // TRIGGERS (36 events from Super Forms)
  TRIGGERS: {
    FORM_SUBMITTED: {
      id: 'form.submitted',
      name: 'Form Submitted',
      category: 'trigger',
      color: '#10b981',
      icon: FormInput,
      description: 'Trigger when a form is submitted',
      inputs: [],
      outputs: ['entry_data', 'form_data'],
      config: {
        formId: null,
        includeSpam: false
      }
    },
    SPAM_DETECTED: {
      id: 'form.spam_detected',
      name: 'Spam Detected',
      category: 'trigger',
      color: '#10b981',
      icon: Shield,
      description: 'Trigger when spam is detected',
      inputs: [],
      outputs: ['detection_details'],
      config: {
        formId: null,
        detectionMethods: []
      }
    },
    ENTRY_CREATED: {
      id: 'entry.created',
      name: 'Entry Created',
      category: 'trigger',
      color: '#10b981',
      icon: FileText,
      description: 'Trigger after entry is saved to database',
      inputs: [],
      outputs: ['entry_id', 'entry_data'],
      config: {
        formId: null
      }
    },
    // ... 33 more event types
  },

  // ACTIONS (20+ action types from Super Forms)
  ACTIONS: {
    SEND_EMAIL: {
      id: 'send_email',
      name: 'Send Email',
      category: 'action',
      color: '#3b82f6',
      icon: Mail,
      description: 'Send an email notification',
      inputs: ['trigger'],
      outputs: ['success'],
      config: {
        to: '',
        subject: '',
        body: '',
        bodyType: 'html',
        from: '{admin_email}',
        replyTo: ''
      }
    },
    HTTP_REQUEST: {
      id: 'http_request',
      name: 'HTTP Request',
      category: 'action',
      color: '#3b82f6',
      icon: Globe,
      description: 'Make an HTTP API call',
      inputs: ['trigger'],
      outputs: ['response'],
      config: {
        url: '',
        method: 'POST',
        headers: {},
        body: '',
        auth: 'none'
      }
    },
    // ... 18 more action types
  },

  // CONDITIONS
  CONDITIONS: {
    IF_CONDITION: {
      id: 'conditional_action',
      name: 'If Condition',
      category: 'condition',
      color: '#f97316',
      icon: GitBranch,
      description: 'Branch based on conditions',
      inputs: ['trigger'],
      outputs: ['true', 'false'],
      config: {
        operator: 'AND',
        rules: []
      }
    }
  }
};
```

**Update PropertiesPanel for Super Forms:**
```typescript
// Handle Super Forms-specific configuration fields
const renderConfigField = (nodeType, fieldName, value) => {
  switch (fieldName) {
    case 'formId':
      return (
        <select
          value={value || ''}
          onChange={(e) => updateConfig(fieldName, parseInt(e.target.value))}
        >
          <option value="">All Forms</option>
          {window.sfuiData.forms.map(form => (
            <option key={form.id} value={form.id}>
              {form.title}
            </option>
          ))}
        </select>
      );

    case 'to':
    case 'subject':
      // Support tag replacement
      return (
        <div>
          <input
            type="text"
            value={value}
            onChange={(e) => updateConfig(fieldName, e.target.value)}
            placeholder="e.g., {email}, {admin_email}"
          />
          <TagInserter
            onInsertTag={(tag) => updateConfig(fieldName, value + tag)}
          />
        </div>
      );

    case 'rules':
      // Condition builder UI
      return (
        <ConditionBuilder
          rules={value || []}
          onChange={(rules) => updateConfig(fieldName, rules)}
        />
      );

    default:
      return (
        <input
          type="text"
          value={value}
          onChange={(e) => updateConfig(fieldName, e.target.value)}
        />
      );
  }
};
```

### Phase 22.3: WordPress Integration (2-3 days)

**Update Page Mount Point:**
```php
// /src/includes/admin/views/page-create-form.php

<?php
$active_tab = $_GET['tab'] ?? 'settings';
$react_tabs = ['emails', 'triggers'];
$use_react = in_array($active_tab, $react_tabs);
?>

<?php if ($use_react): ?>
    <div id="sfui-admin-root"
         data-page="form-builder"
         data-tab="<?php echo esc_attr($active_tab); ?>">
    </div>

    <script>
    window.sfuiData = {
        currentPage: 'form-builder',
        activeTab: '<?php echo esc_js($active_tab); ?>',
        formId: <?php echo (int)$_GET['id']; ?>,
        formTitle: '<?php echo esc_js(get_the_title($_GET['id'])); ?>',
        restUrl: '<?php echo esc_url_raw(rest_url('super-forms/v1')); ?>',
        restNonce: '<?php echo wp_create_nonce('wp_rest'); ?>',

        // Forms list for dropdown
        forms: <?php
            $forms = get_posts(['post_type' => 'super_form', 'numberposts' => -1]);
            echo json_encode(array_map(fn($f) => [
                'id' => $f->ID,
                'title' => $f->post_title
            ], $forms));
        ?>,

        // Events and actions for node palette
        events: <?php echo json_encode(SUPER_Automation_Registry::get_registered_events()); ?>,
        actionTypes: <?php echo json_encode(SUPER_Automation_Registry::get_registered_actions()); ?>,

        // Load existing triggers for this form
        <?php if ($active_tab === 'triggers'): ?>
        triggers: <?php echo json_encode(SUPER_Automation_DAL::get_by_form($_GET['id'])); ?>,
        <?php endif; ?>
    };
    </script>
<?php else: ?>
    <!-- Legacy PHP tabs -->
<?php endif; ?>
```

**Create AutomationsTab Component:**
```typescript
// /src/react/admin/components/form-builder/automations-tab/AutomationsTab.tsx

import { VisualBuilder } from './VisualBuilder';

export const AutomationsTab = ({ formId }: { formId: number }) => {
  return (
    <div className="automations-tab h-full">
      <VisualBuilder formId={formId} />
    </div>
  );
};
```

### Phase 22.4: PHP Execution Engine (2 days)

**Create Visual Workflow Executor:**
```php
// /src/includes/class-workflow-executor.php

class SUPER_Workflow_Executor {

    public function execute($automation_id, $context) {
        $automation = SUPER_Automation_DAL::get($automation_id);

        if ($automation['type'] !== 'visual') {
            // Fall back to traditional executor
            return SUPER_Automation_Executor::execute($automation_id, $context);
        }

        $graph = json_decode($automation['workflow_graph'], true);

        if (!$graph || !isset($graph['nodes'], $graph['connections'])) {
            throw new Exception('Invalid workflow graph');
        }

        // Execute workflow graph
        return $this->execute_graph(
            $graph['nodes'],
            $graph['connections'],
            $context
        );
    }

    private function execute_graph($nodes, $connections, $context) {
        // Build adjacency list from connections
        $adjacency = [];
        foreach ($connections as $conn) {
            if (!isset($adjacency[$conn['from']])) {
                $adjacency[$conn['from']] = [];
            }
            $adjacency[$conn['from']][] = [
                'to' => $conn['to'],
                'fromOutput' => $conn['fromOutput'],
                'toInput' => $conn['toInput']
            ];
        }

        // Find trigger node (starting point)
        $trigger_node = null;
        foreach ($nodes as $node) {
            if (strpos($node['type'], 'form.') === 0 ||
                strpos($node['type'], 'entry.') === 0 ||
                strpos($node['type'], 'payment.') === 0) {
                $trigger_node = $node;
                break;
            }
        }

        if (!$trigger_node) {
            throw new Exception('No trigger node found in workflow');
        }

        // Execute nodes starting from trigger
        $this->execute_node_chain($trigger_node['id'], $nodes, $adjacency, $context);
    }

    private function execute_node_chain($node_id, $nodes, $adjacency, $context) {
        $node = $this->find_node($nodes, $node_id);

        if (!$node) {
            return;
        }

        // Skip trigger nodes (already fired)
        if (!in_array($node['category'] ?? 'action', ['action', 'condition'])) {
            // Move to next nodes
            $this->execute_connected_nodes($node_id, $adjacency, $nodes, $context);
            return;
        }

        // Map node type to action class
        $action_type = $this->map_node_to_action($node['type']);

        if (!$action_type) {
            SUPER_Automation_Logger::error('Unknown node type: ' . $node['type']);
            return;
        }

        $action = SUPER_Automation_Registry::get_action($action_type);

        if (!$action) {
            SUPER_Automation_Logger::error('Action not found: ' . $action_type);
            return;
        }

        // Execute action
        $result = $action->execute($node['config'], $context);

        // Handle condition branching
        if ($node['category'] === 'condition') {
            $output_key = $result ? 'true' : 'false';

            // Find connections from this output
            if (isset($adjacency[$node_id])) {
                foreach ($adjacency[$node_id] as $conn) {
                    if ($conn['fromOutput'] === $output_key) {
                        $this->execute_node_chain($conn['to'], $nodes, $adjacency, $context);
                    }
                }
            }
        } else {
            // Execute all connected nodes
            $this->execute_connected_nodes($node_id, $adjacency, $nodes, $context);
        }
    }

    private function execute_connected_nodes($node_id, $adjacency, $nodes, $context) {
        if (!isset($adjacency[$node_id])) {
            return;
        }

        foreach ($adjacency[$node_id] as $conn) {
            $this->execute_node_chain($conn['to'], $nodes, $adjacency, $context);
        }
    }

    private function find_node($nodes, $node_id) {
        foreach ($nodes as $node) {
            if ($node['id'] === $node_id) {
                return $node;
            }
        }
        return null;
    }

    private function map_node_to_action($node_type) {
        // Direct mapping for action nodes
        $direct_actions = [
            'send_email',
            'http_request',
            'webhook',
            'update_entry_status',
            'update_entry_field',
            'delete_entry',
            'create_post',
            'conditional_action',
            'delay_execution',
            'log_message'
        ];

        if (in_array($node_type, $direct_actions)) {
            return $node_type;
        }

        // Map visual node IDs to action types
        $map = [
            'send-email' => 'send_email',
            'http-request' => 'http_request',
            'webhook-call' => 'webhook',
            'update-status' => 'update_entry_status',
            'delete-entry' => 'delete_entry',
            'if-condition' => 'conditional_action',
            'delay' => 'delay_execution',
            'mailpoet-subscribe' => 'mailpoet.add_subscriber',
            'wc-add-cart' => 'woocommerce.add_to_cart'
        ];

        return $map[$node_type] ?? null;
    }
}
```

**Integrate with Automation Executor:**
```php
// /src/includes/class-automation-executor.php (modify)

public static function execute($automation_id, $context) {
    $automation = SUPER_Automation_DAL::get($automation_id);

    // Check if this is a visual workflow
    if ($automation['type'] === 'visual') {
        $executor = new SUPER_Workflow_Executor();
        return $executor->execute($automation_id, $context);
    }

    // Traditional automation execution
    // ... existing code
}
```

### Phase 22.5: Template System (1-2 days)

**Create Super Forms Workflow Templates:**
```typescript
// /src/react/admin/components/form-builder/automations-tab/data/templates.ts

export const WORKFLOW_TEMPLATES = [
  {
    id: 'email-notification',
    name: 'Email Notification',
    description: 'Send email when form is submitted',
    category: 'basic',
    nodes: [
      {
        id: 'node-1',
        type: 'form.submitted',
        position: { x: 100, y: 200 },
        config: { formId: null },
        selected: false,
        zIndex: 1
      },
      {
        id: 'node-2',
        type: 'send_email',
        position: { x: 400, y: 200 },
        config: {
          to: '{email}',
          subject: 'Thank you for your submission',
          body: 'We received your form submission.',
          bodyType: 'html'
        },
        selected: false,
        zIndex: 2
      }
    ],
    connections: [
      {
        id: 'conn-1',
        from: 'node-1',
        fromOutput: 'entry_data',
        to: 'node-2',
        toInput: 'trigger',
        selected: false
      }
    ]
  },

  {
    id: 'conditional-email',
    name: 'Conditional Email',
    description: 'Send different emails based on form data',
    category: 'advanced',
    nodes: [
      {
        id: 'node-1',
        type: 'form.submitted',
        position: { x: 100, y: 200 },
        config: {},
        selected: false,
        zIndex: 1
      },
      {
        id: 'node-2',
        type: 'conditional_action',
        position: { x: 350, y: 200 },
        config: {
          operator: 'AND',
          rules: [
            { field: '{order_total}', operator: '>', value: '100' }
          ]
        },
        selected: false,
        zIndex: 2
      },
      {
        id: 'node-3',
        type: 'send_email',
        position: { x: 600, y: 100 },
        config: {
          to: '{email}',
          subject: 'VIP Order Confirmation',
          body: 'Thank you for your large order!'
        },
        selected: false,
        zIndex: 3
      },
      {
        id: 'node-4',
        type: 'send_email',
        position: { x: 600, y: 300 },
        config: {
          to: '{email}',
          subject: 'Order Confirmation',
          body: 'Thank you for your order.'
        },
        selected: false,
        zIndex: 4
      }
    ],
    connections: [
      {
        id: 'conn-1',
        from: 'node-1',
        fromOutput: 'entry_data',
        to: 'node-2',
        toInput: 'trigger',
        selected: false
      },
      {
        id: 'conn-2',
        from: 'node-2',
        fromOutput: 'true',
        to: 'node-3',
        toInput: 'trigger',
        selected: false
      },
      {
        id: 'conn-3',
        from: 'node-2',
        fromOutput: 'false',
        to: 'node-4',
        toInput: 'trigger',
        selected: false
      }
    ]
  },

  // More templates...
];
```

## Success Criteria

### Core Functionality
- [ ] All ai-automation components ported to TypeScript
- [ ] Visual workflow builder renders in Triggers tab
- [ ] Node palette shows Super Forms events and actions
- [ ] Drag/drop nodes onto canvas
- [ ] Connect nodes with visual edges
- [ ] Configure nodes via properties panel
- [ ] Save workflows to WordPress database
- [ ] Load workflows from database
- [ ] Execute visual workflows via PHP engine

### WordPress Integration
- [ ] React mounts at form builder page level
- [ ] Triggers tab renders visual workflow builder
- [ ] Data persists via WordPress REST API
- [ ] Workflows execute on form submission
- [ ] Automation logs show execution trace
- [ ] Error handling and validation

### User Experience
- [ ] Pan/zoom canvas controls
- [ ] Multi-select nodes
- [ ] Undo/redo support
- [ ] Keyboard shortcuts work
- [ ] Template library accessible
- [ ] Mobile warning/fallback
- [ ] Dark/light theme support

### Technical Requirements
- [ ] Bundle size < 150KB gzipped (target: ~120KB)
- [ ] TypeScript strict mode passes
- [ ] No console errors
- [ ] 60fps node dragging
- [ ] Works in Chrome, Firefox, Safari, Edge

## Migration Checklist

```typescript
// Components to port (11 files)
[ ] useNodeEditor.js → useNodeEditor.ts
[ ] Canvas.jsx → Canvas.tsx
[ ] ConnectionOverlay.jsx → ConnectionOverlay.tsx
[ ] Node.jsx → Node.tsx
[ ] NodePalette.jsx → NodePalette.tsx
[ ] PropertiesPanel.jsx → PropertiesPanel.tsx
[ ] NodeSettingsPanel.jsx → NodeSettingsPanel.tsx
[ ] Toolbar.jsx → Toolbar.tsx
[ ] TemplateModal.jsx → TemplateModal.tsx
[ ] GroupContainer.jsx → GroupContainer.tsx
[ ] nodeTypes.js → superFormsNodeTypes.ts
[ ] templates.js → templates.ts

// New components to create (4 files)
[ ] AutomationsTab.tsx (main tab wrapper - visual mode only)
[ ] VisualBuilder.tsx (canvas wrapper)
[ ] ConditionBuilder.tsx (AND/OR rule builder)
[ ] TagInserter.tsx (tag replacement helper)

// PHP files to create (1 file)
[ ] class-workflow-executor.php

// PHP files to modify (1 file)
[ ] class-automation-executor.php (add visual workflow detection)

// TypeScript definitions (1 file)
[ ] workflow.types.ts (all interfaces)
```

## Bundle Size Analysis

**ai-automation current bundle:**
- JavaScript: 244KB uncompressed → 76KB gzipped
- CSS: 8KB uncompressed → 2KB gzipped
- **Total: 78KB gzipped**

**Super Forms Admin current bundle:**
- JavaScript: ~670KB uncompressed → ~180KB gzipped
- CSS: ~69KB uncompressed → ~15KB gzipped
- **Total: ~195KB gzipped**

**After integration (estimated):**
- JavaScript: ~914KB uncompressed → ~250KB gzipped (+70KB)
- CSS: ~77KB uncompressed → ~17KB gzipped (+2KB)
- **Total: ~267KB gzipped (+37% increase)**

**Optimization opportunities:**
- Lazy load visual builder (load only when Triggers tab active)
- Code splitting per component
- Tree-shaking unused node types
- SVG icon optimization

## Dependencies

- **Required:** Phases 1-6 (triggers foundation, REST API)
- **Required:** Phase 23 (Production-Critical Refinements - MUST be implemented before Phase 22 release)
- **Related:** Phase 20 (Triggers v2 UI - can merge or keep separate)
- **Related:** Phase 21 (Form Settings Migration - shares node types)

**Why Phase 23 is Required:**
Phase 23 addresses four critical production issues that affect Phase 22's architecture:
1. **InnoDB Transaction Safety** - Multi-trigger workflows need transaction support for atomic saves
2. **Variable Security** - Visual builder exposes user to XSS/injection risks via variable replacement
3. **Edge Case Handling** - Multi-trigger nodes require safe config matching (not dangerous fallbacks)
4. **Global Context** - Visual builder needs standard variables ({site_url}, {current_date}, etc.)

See [23-production-critical-refinements.md](23-production-critical-refinements.md) for detailed implementation requirements.

## Notes

- ai-automation uses **no external state libraries** - pure React hooks
- ai-automation uses **no ReactFlow or Rete.js** - custom implementation
- Bundle size is **70% smaller** than ReactFlow equivalent
- All components are **TypeScript-ready** (just need type annotations)
- SVG connections render **faster** than Canvas-based approaches
- Native HTML5 drag/drop is **more reliable** than library wrappers

## Risks & Mitigation

**Risk 1: Bundle Size Bloat**
- **Mitigation**: Lazy load visual builder, code splitting, tree-shaking

**Risk 2: TypeScript Conversion Errors**
- **Mitigation**: Incremental conversion, test each component separately

**Risk 3: WordPress Admin Theme Conflicts**
- **Mitigation**: CSS scoping (already done for Email v2), isolated styles

**Risk 4: Mobile Responsiveness**
- **Mitigation**: Show warning on mobile, disable visual builder, force list view

**Risk 5: Performance with Large Workflows**
- **Mitigation**: Virtual rendering for 100+ nodes, canvas viewport culling

**Risk 6: XSS/Injection via Variable Replacement** 🔴 CRITICAL
- **Impact**: Visual builder exposes users to variable replacement in action configs (email bodies, webhook URLs, etc.)
- **Vulnerability**: Unsanitized {field_name} replacement could inject scripts, SQL, malicious URLs
- **Mitigation**: REQUIRED - Implement Phase 23 context-aware sanitization before release
- **Status**: Blocked by Phase 23 implementation

**Risk 7: Transaction Failures in Multi-Trigger Workflows** 🔴 CRITICAL
- **Impact**: Multi-trigger saves could leave database in inconsistent state if one event insert fails
- **Vulnerability**: MyISAM storage engine silently ignores transactions, leading to data corruption
- **Mitigation**: REQUIRED - Implement Phase 23 InnoDB enforcement and transaction wrapper
- **Status**: Blocked by Phase 23 implementation

**Risk 8: Wrong Node Execution on Config Mismatch** 🔴 CRITICAL
- **Impact**: When webhook URL or schedule doesn't match any trigger node config, system could execute wrong node
- **Vulnerability**: Dangerous fallback to "first node" could trigger unintended actions
- **Mitigation**: REQUIRED - Implement Phase 23 null-return pattern for unmatched configs
- **Status**: Blocked by Phase 23 implementation

## Timeline Estimate

**🔴 PREREQUISITE: Phase 23 must be completed FIRST (6-7 days)**

**Core Refinements (4 days):**
- InnoDB transaction safety
- Variable security (XSS/injection prevention)
- Edge case handling (null-return pattern)
- Global context variables

**Housekeeping Refinements (3 days):**
- REST API security (permission callbacks)
- Log rotation & cleanup system
- Headless user context (webhooks/cron)

**Phase 22 Implementation (after Phase 23):**
- **Phase 22.1**: Port to TypeScript - 3-4 days
- **Phase 22.2**: Map node types - 2-3 days
- **Phase 22.3**: WordPress integration - 2-3 days
- **Phase 22.4**: PHP execution engine - 2 days
- **Phase 22.5**: Template system - 1-2 days
- **Testing & Polish**: 3-4 days

**Phase 22 Total: 13-18 days** (~3 weeks)
**Combined Total (Phase 23 + Phase 22): 19-25 days** (~4-5 weeks)

## Next Steps

1. 🔴 **CRITICAL**: Implement Phase 23 (Production-Critical Refinements) FIRST
   - InnoDB transaction wrapper and enforcement
   - Context-aware variable sanitization system
   - Null-return pattern for unmatched configs
   - Global context variable system
2. Complete Phase 21 (Form Settings Migration) if not already done
3. Start with TypeScript conversion (Phase 22.1)
4. Test each component in isolation
5. Integrate incrementally into Triggers tab
6. Build PHP execution engine in parallel (using Phase 23 security patterns)
7. Polish and optimize before release

## Context Manifest

### Discovered During Implementation
[Date: 2025-11-29 / Visual Builder Architecture Analysis]

During the analysis and porting of the ai-automation visual workflow builder, we discovered critical architectural patterns for SVG-based node connection rendering that weren't documented in the original codebase. These discoveries fundamentally affect how the canvas coordinate system must be structured.

#### SVG Connection Overlay Positioning

**Discovery**: The `ConnectionOverlay` component MUST be placed INSIDE the transformed canvas layer (nodes-layer div), not as a sibling element at the root level.

**Why This Wasn't Obvious**: In the original ai-automation codebase, the overlay placement worked either way because the component was parsing CSS transforms from the DOM to calculate coordinate conversions. However, when integrating into React-based Super Forms admin, we use viewport props passed explicitly rather than DOM parsing.

**The Problem**: When ConnectionOverlay is a sibling to the transformed canvas:
```jsx
// ❌ WRONG - Overlay outside transformed layer
<div className="canvas-root">
  <div className="nodes-layer" style={{ transform: `translate(${x}px, ${y}px) scale(${zoom})` }}>
    {/* nodes render here */}
  </div>
  <ConnectionOverlay /> {/* Gets transform but doesn't inherit coordinate space */}
</div>
```

The SVG overlay receives the transform values via props but doesn't inherit the CSS transform coordinate space. This causes:
- Mouse coordinates to calculate incorrectly during connection preview dragging
- Connection paths to render at wrong screen positions
- Click/hover detection to fail on connection lines

**The Solution**: Place ConnectionOverlay INSIDE the transformed layer:
```jsx
// ✅ CORRECT - Overlay inside transformed layer
<div className="canvas-root">
  <div className="nodes-layer" style={{ transform: `translate(${x}px, ${y}px) scale(${zoom})` }}>
    {/* nodes render here */}
    <ConnectionOverlay /> {/* Inherits parent transform automatically */}
  </div>
</div>
```

This ensures the SVG coordinate system matches the node coordinate system, making mouse-to-canvas coordinate conversion work correctly.

#### Container Dimensions for Percentage-Based SVG

**Discovery**: The nodes-layer container needs `position: absolute; inset: 0` (or explicit width/height) for child SVG elements with percentage-based dimensions to render correctly.

**The Problem**: SVG elements with `width="100%" height="100%"` need a sized container to calculate their dimensions. When the transform container lacks explicit dimensions:
```css
/* ❌ WRONG - No dimensions on transformed container */
.nodes-layer {
  transform: translate(0, 0) scale(1);
  /* Missing: position/inset or width/height */}
```

The SVG calculates 100% of... nothing, collapsing to 0x0 or defaulting to 300x150 (SVG default).

**The Solution**:
```css
/* ✅ CORRECT - Explicit container dimensions */
.nodes-layer {
  position: absolute;
  inset: 0; /* Makes container fill parent */
  transform: translate(0, 0) scale(1);
}
```

This gives the SVG a concrete reference for percentage calculations.

#### Mouse Coordinate Conversion Requirements

**Discovery**: The connection preview dragging requires the viewport prop to be passed explicitly to ConnectionOverlay - you cannot parse CSS transform strings from React inline styles via DOM queries.

**Why This Matters**: The original ai-automation code used DOM queries like:
```javascript
// Works in vanilla React but fragile
const transform = element.style.transform;
const match = transform.match(/translate\(([-\d.]+)px, ([-\d.]+)px\) scale\(([\d.]+)\)/);
```

In Super Forms admin with dynamic viewport state, this is unreliable because:
- React may batch style updates
- Transform string format can vary
- Webpack/build tools may mangle class names
- WordPress admin CSS may interfere

**The Solution**: Pass viewport as explicit prop:
```jsx
<ConnectionOverlay
  viewport={{ x: viewportX, y: viewportY, zoom }}
  nodes={nodes}
  connections={connections}
/>
```

Then use it directly for coordinate conversion:
```javascript
const canvasX = (screenX - viewport.x) / viewport.zoom;
const canvasY = (screenY - viewport.y) / viewport.zoom;
```

#### SVG Overflow for Path Extensions

**Discovery**: SVG container needs `overflow: visible` to allow connection paths that extend beyond immediate container bounds.

**The Problem**: Bezier curves connecting distant nodes can extend outside the SVG's calculated bounding box. With default `overflow: hidden`:
- Paths get clipped mid-curve
- Connection animations disappear at edges
- Visual artifacts appear during panning

**The Solution**:
```css
svg.connection-overlay {
  overflow: visible; /* Allow paths to extend beyond bounds */
  pointer-events: none; /* Let clicks pass through to canvas */
}
```

Path elements themselves should have `pointer-events: stroke` for hover detection:
```jsx
<path
  className="connection-path"
  style={{ pointerEvents: 'stroke' }} /* Only path is clickable */
/>
```

#### Conditional Transitions for Drag Performance

**Discovery**: CSS transitions must be disabled during active drag operations for snappy visual feedback.

**The Pattern**:
```jsx
const nodeStyle = {
  transform: `translate(${x}px, ${y}px)`,
  transition: isDragging ? 'none' : 'transform 0.15s ease-out'
};
```

**Why This Matters**:
- With transitions enabled during drag: Visual lag as CSS interpolates between rapid mouse positions
- Without transitions: Instant feedback, feels like native drag
- Re-enabling after drop: Smooth snap to final grid-aligned position

This also applies to selection rings, group bounds, and connection paths during their respective drag operations.

#### SVG Animation Along Bezier Paths

**Discovery**: SVG `<animateMotion>` with `path` attribute allows animated elements (dots, arrows) to follow the exact bezier curve of connections.

**Example Pattern**:
```jsx
<path id="connection-path-123" d="M 100 200 C 150 200 250 300 300 300" />
<circle r="3" fill="#3b82f6">
  <animateMotion
    dur="2s"
    repeatCount="indefinite"
    path="M 100 200 C 150 200 250 300 300 300"
  />
</circle>
```

This is more performant than JavaScript-based animation and automatically handles zoom/pan transforms.

#### Group Drag Pattern: Phase-Based Updates

**Discovery**: Group drag uses a three-phase pattern (start/move/end) with `groupDragOffset` for visual feedback before committing final positions.

**The Pattern**:
```javascript
// Phase 1: Drag Start - Record initial positions
onGroupDragStart(groupId) {
  group.nodeIds.forEach(nodeId => {
    node.initialPosition = { ...node.position };
  });
}

// Phase 2: Drag Move - Apply offset visually (don't commit to state)
onGroupDragMove(groupId, deltaX, deltaY) {
  nodes.forEach(node => {
    if (group.nodeIds.includes(node.id)) {
      node.groupDragOffset = { x: deltaX, y: deltaY }; // Visual only
    }
  });
}

// Phase 3: Drag End - Commit final positions, clear offsets
onGroupDragEnd(groupId) {
  nodes.forEach(node => {
    if (group.nodeIds.includes(node.id)) {
      node.position.x += node.groupDragOffset.x;
      node.position.y += node.groupDragOffset.y;
      node.groupDragOffset = null; // Clear temporary offset
    }
  });
}
```

**Why This Pattern**:
- Prevents expensive state updates during every mouse move
- Allows smooth visual feedback via CSS transforms
- Enables "cancel drag" functionality (ESC key clears offsets)
- Supports undo/redo by tracking phase boundaries

**Rendering**:
```jsx
const finalX = node.position.x + (node.groupDragOffset?.x || 0);
const finalY = node.position.y + (node.groupDragOffset?.y || 0);
```

### Updated Technical Details

**Required Canvas Structure**:
```jsx
<div className="canvas-root relative w-full h-full overflow-hidden">
  <div
    className="nodes-layer absolute inset-0"
    style={{
      transform: `translate(${viewport.x}px, ${viewport.y}px) scale(${viewport.zoom})`,
      transformOrigin: '0 0'
    }}
  >
    {/* Node components */}
    {nodes.map(node => <Node key={node.id} {...node} />)}

    {/* ConnectionOverlay MUST be inside transformed layer */}
    <ConnectionOverlay
      viewport={viewport}
      nodes={nodes}
      connections={connections}
    />
  </div>
</div>
```

**Connection Preview Mouse Handling**:
```javascript
// Convert screen coordinates to canvas coordinates
const screenToCanvas = (screenX, screenY) => {
  const canvasX = (screenX - viewport.x) / viewport.zoom;
  const canvasY = (screenY - viewport.y) / viewport.zoom;
  return { x: canvasX, y: canvasY };
};

// Use during connection drag
onMouseMove(e) {
  const canvasPos = screenToCanvas(e.clientX, e.clientY);
  setConnectionPreview({
    ...connectionPreview,
    endX: canvasPos.x,
    endY: canvasPos.y
  });
}
```

**Performance Optimization - Conditional Transitions**:
```javascript
// Disable transitions during drag, enable for snap-to-grid
const getNodeStyle = (node, isDragging) => ({
  transform: `translate(${node.position.x}px, ${node.position.y}px)`,
  transition: isDragging ? 'none' : 'transform 0.15s ease-out',
  zIndex: node.zIndex || 1
});
```

These patterns are critical for implementing the visual workflow builder correctly in Super Forms. Future developers should reference these when debugging canvas rendering, connection path issues, or drag performance problems.
