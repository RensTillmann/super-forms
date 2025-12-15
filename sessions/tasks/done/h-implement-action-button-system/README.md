---
name: h-implement-action-button-system
branch: feature/h-implement-triggers-actions-extensibility
status: completed
created: 2025-12-15
---

# Action Button System

## Problem/Goal

Create a comprehensive Action Button element system that goes beyond simple form submission. Users need the ability to:

1. **Add multiple buttons to a form**, each with unique behavior
2. **Fire custom events** that automation nodes can listen to (not just `form.submitted`)
3. **Connect button clicks to specific automation flows** - enabling complex workflows triggered from the frontend
4. **Support diverse use cases** like PDF generation, file downloads, draft saves, payment processing, wizard navigation, and more

This transforms buttons from simple "submit" controls into **automation triggers** that can execute any workflow the user configures.

### Core Architecture Concept

```
[Button Click] → [Custom Event: "button.{button_id}.clicked"] → [Automation Flow]
```

Each button can:
- Define its own trigger event ID
- Connect to any compatible automation nodes
- Execute independently of form submission
- Support loading states, confirmations, and result handling

## Success Criteria

- [x] Research completed: Documented 10+ button use case epics with schema implications
- [x] Button element schema defined with Zod validation
- [x] MCP tools created for LLM to add/configure buttons
- [x] Custom event system implemented (`button.{id}.clicked` events)
- [x] Automation trigger node updated to support button event binding
- [x] ElementRenderer renders Button component with all variants
- [x] At least 3 new automation node types created (file-gen, temp-access, etc.)
- [x] Frontend can show button loading/success/error states from automation results
- [x] Documentation and examples for common patterns

## Subtasks

| # | File | Status | Description |
|---|------|--------|-------------|
| 00 | [00-research-button-epics.md](./00-research-button-epics.md) | completed | Research button patterns and document epics |
| 01 | [01-schema-zod-mcp-foundation.md](./01-schema-zod-mcp-foundation.md) | completed | Button schema, Zod validation, MCP tools |
| 02a | [02a-frontend-event-trigger-system.md](./02a-frontend-event-trigger-system.md) | completed | Unified frontend event trigger system (PHP + TypeScript) |
| 02b | [02b-button-event-implementation.md](./02b-button-event-implementation.md) | completed | Button-specific event registration and React component integration |
| 03 | [03-automation-button-binding.md](./03-automation-button-binding.md) | completed | UI to connect buttons to automations |
| 04 | [04-built-in-node-types.md](./04-built-in-node-types.md) | completed | New nodes: generate_file, temp_access, calculate, validate |
| 05 | [05-documentation-examples.md](./05-documentation-examples.md) | completed | Documentation and usage examples |

### Subtask Dependencies

```
01-schema-zod-mcp ──┬──► 02a-frontend-event-system ──► 02b-button-event ──┬──► 03-automation-binding
                    │                                                      │
                    │                                                      └──► 04-built-in-nodes
                    │
                    └──► (frontend rendering - future subtask)
```

### Shared Interfaces

All subtasks share these key interfaces defined in 02a-frontend-event-trigger-system.md:

1. **Event Pattern**: `button.{event_id}.clicked`
2. **Context Schema**: `form_id`, `button_id`, `event_name`, `form_data`, `session_key`, etc.
3. **Response Format**: `{ success, data: { file_url?, access_url?, result?, message? }, error? }`

## Epics Directory

Research outputs documenting specific use cases:
- `epics/` - Contains individual epic files discovered during research

## Context Manifest

### How Element Schemas Work: The Property Type System

**Current Architecture - Schema-First Form Builder**

When a user adds a field to a form, the entire system is driven by TypeScript schemas that define what properties that element supports. The element schema system lives in `/src/react/admin/schemas/` and uses Zod for validation at import-time (fail-fast approach).

**Element Registration Flow:**

1. **Schema Definition** (`/src/react/admin/schemas/elements/text.ts`):
   - Each element type registers itself via `registerElement()` with a complete schema
   - Schema defines: type, name, description, category, icon, container config, and most importantly - properties
   - Properties are organized into 5 categories: `general`, `validation`, `appearance`, `advanced`, `conditions`
   - Example from TextInput:
     ```typescript
     export const TextElementSchema = registerElement({
       type: 'text',
       name: 'Text Input',
       category: 'basic',
       properties: withBaseProperties({
         general: {
           placeholder: { type: 'string', label: 'Placeholder', translatable: true, supportsTags: true },
           defaultValue: { type: 'string', label: 'Default Value', supportsTags: true },
           prefixText: { type: 'string', label: 'Prefix Text' },
           prefixIcon: { type: 'icon', label: 'Prefix Icon' }
         },
         validation: {
           required: { type: 'boolean', label: 'Required', default: false },
           minLength: { type: 'number', label: 'Minimum Length', min: 0 }
         }
       })
     });
     ```

2. **Base Properties Inheritance**:
   - Every element automatically gets base properties via `withBaseProperties()` helper
   - Base properties include: `name`, `label`, `description`, `width`, `hideLabel`, `cssClass`, `conditionalLogic`
   - These merge with element-specific properties

3. **27 Property Types** (`/src/react/admin/schemas/core/types.ts`):
   - Primitives: `string`, `number`, `boolean`
   - Selection: `select`, `multi_select`
   - Visual: `color`, `icon`, `position_picker`
   - Complex: `array`, `object`
   - Form-specific: `conditional_rules`, `columns_config`, `items_config`, `rich_text`, `code`
   - Date/Time: `date`, `time`, `datetime`
   - Media: `file`, `image`
   - Advanced: `key_value`, `repeater_config`, `step_config`, `email_template`, `calculation`, `tag_input`

4. **Property Rendering**:
   - Property panel automatically renders based on property type
   - `position_picker` type controls layout positioning (e.g., labelPosition, descriptionPosition)
   - Properties can have `conditions` array to show/hide based on other property values
   - Properties can have `targets` array indicating which style nodes they apply to (used in StyleTab)

5. **Schema Registry** (`/src/react/admin/schemas/core/registry.ts`):
   - Central in-memory Map storing all registered element schemas
   - Functions: `getElementSchema()`, `getAllElementTypes()`, `getElementsByCategory()`
   - Includes validation helper: `validateElementData()` for runtime checking

**For Button Element Implementation:**

The Button element will need:
- `action_type` property (select): "submit" | "button" | "reset"
- `event_id` property (string): Custom event identifier for automation binding (e.g., "generate_pdf")
- `button_text` property (string, translatable, supportsTags)
- `variant` property (select): "primary" | "secondary" | "outline" | "ghost"
- `size` property (select): "sm" | "md" | "lg"
- `loading_text` property (string): Text shown during automation execution
- `success_message` property (string, supportsTags): Message shown on success
- `error_message` property (string, supportsTags): Message shown on error
- `disabled_when` property (conditional_rules): Conditions to disable button
- Icon properties: `icon`, `iconPosition` (left/right)

### How Automation System Events Flow: From Button Click to Action Execution

**Event Firing Architecture**

When form events occur (submission, button clicks, file uploads), the system fires automation events that trigger configured workflows. The flow goes: Frontend Event → PHP Event Fire → Automation Resolution → Action Execution.

**Current Event System** (`SUPER_Automation_Executor::fire_event()`):

1. **Event Registration** (`/src/includes/automations/class-automation-registry.php`):
   - Registry pre-loads 60+ built-in events during `init` hook
   - Event structure:
     ```php
     $this->register_event('form.submitted', [
       'label' => 'Form Submitted',
       'description' => 'Form successfully submitted',
       'category' => 'form_lifecycle',
       'available_context' => ['form_id', 'entry_id', 'session_id', 'form_data'],
       'required_context' => ['form_id'],
       'compatible_actions' => ['send_email', 'webhook', 'create_post'],
       'phase' => 1
     ]);
     ```
   - Each event declares what context data it provides and which actions it supports
   - Categories: `form_lifecycle`, `entry_management`, `file_management`, `payment`, `subscription`, `session_lifecycle`

2. **Event Firing Flow** (`/src/includes/class-ajax.php` line 2945+):
   - Form submission triggers multiple events in sequence:
     ```php
     // Pre-submission firewall
     SUPER_Automation_Executor::fire_event('form.before_submit', $context);

     // Spam detection
     if ($spam_detected) {
       SUPER_Automation_Executor::fire_event('form.spam_detected', $context);
     }

     // Entry creation
     SUPER_Automation_Executor::fire_event('entry.created', ['entry_id' => $entry_id, ...]);
     SUPER_Automation_Executor::fire_event('entry.saved', $context);

     // Final event
     SUPER_Automation_Executor::fire_event('form.submitted', $context);
     ```
   - Context contains: `form_id`, `entry_id`, `form_data`, `session_key`, `user_id`, `timestamp`

3. **Automation Resolution** (`/src/includes/automations/class-automation-manager.php`):
   - `resolve_automations_for_event($event_id, $context)` finds matching automations
   - Filters by: event type, form_id scope, enabled status
   - Returns array of automation records to execute

4. **Execution Engine** (`/src/includes/automations/class-automation-executor.php`):
   - Two workflow types: **visual** (node graph) vs **code** (action list)
   - Visual workflows delegated to `SUPER_Workflow_Executor::execute()`
   - Code workflows execute actions sequentially via `execute_action()`
   - Each action gets: `$context` (event data) and `$config` (action settings)

5. **Action Execution** (`execute_action()` method):
   ```php
   // Get action instance from registry
   $instance = $registry->get_action_instance($action['action_type']);

   // Execute with context and config
   $result = $instance->execute($context, $config);

   // Log to database
   SUPER_Automation_DAL::log_execution([
     'automation_id' => $automation_id,
     'action_id' => $action_id,
     'status' => 'success',
     'execution_time_ms' => $time
   ]);
   ```

6. **Sync vs Async Execution**:
   - Sync-only actions (affect form flow): `abort_submission`, `stop_execution`, `redirect_user`, `set_variable`
   - Async-preferred actions (external/slow): `webhook`, `send_email`, `create_post`, `http_request`
   - Async actions queued via Action Scheduler for background execution
   - Mode determined by `get_action_execution_mode()` - checks action type, config, and context

**For Button Events:**

Button clicks will fire custom events with pattern: `button.{button_id}.clicked` where:
- `button_id` comes from element's `name` property or auto-generated ID
- Context will include: `form_id`, `button_id`, `form_data`, `user_id`, `session_key`
- Frontend JavaScript will POST to new endpoint: `wp_ajax_super_trigger_button_action`
- PHP handler validates form ownership, fires event, returns execution result to frontend
- Frontend shows loading state while automation runs, displays success/error from result

### How Actions Are Structured: Building New Automation Nodes

**Action Base Class Architecture**

All automation actions extend `SUPER_Action_Base` (`/src/includes/automations/class-action-base.php`) which provides:

**Required Abstract Methods:**
```php
abstract public function get_id();        // Unique identifier (e.g., 'send_email')
abstract public function get_label();     // Display name for UI
abstract public function execute($context, $config); // Core action logic
```

**Optional Methods with Defaults:**
```php
public function get_category()            // 'general', 'notification', etc.
public function get_description()         // Help text for UI
public function get_settings_schema()     // Array of config field definitions
public function supports_async()          // true/false, most actions can run async
public function get_execution_mode()      // 'sync', 'async', or 'auto'
public function get_retry_config()        // Max retries, delays, exponential backoff
```

**Example: Send Email Action** (`/src/includes/automations/actions/class-action-send-email.php`):

1. **Settings Schema** (defines UI fields):
   ```php
   public function get_settings_schema() {
     return [
       ['name' => 'to', 'label' => 'To', 'type' => 'text', 'required' => true],
       ['name' => 'subject', 'label' => 'Subject', 'type' => 'text', 'required' => true],
       ['name' => 'body', 'label' => 'Body', 'type' => 'wysiwyg', 'required' => true],
       ['name' => 'include_entry_data', 'label' => 'Include Entry Data', 'type' => 'toggle']
     ];
   }
   ```
   - Field types: `text`, `textarea`, `wysiwyg`, `email`, `url`, `number`, `toggle`, `select`, `radio`
   - Each field can have: `required`, `default`, `placeholder`, `description`

2. **Variable Replacement** (safe context substitution):
   ```php
   // Base class provides replace_variables() with sanitization
   $to = $this->replace_variables($config['to'], $context, [
     'sanitize' => 'email',      // email, html, text, url, sql, attribute, none
     'allow_html' => false,
     'missing_behavior' => 'error'  // error, empty, keep
   ]);
   ```
   - Tags like `{form_data.email}` or `{entry_id}` replaced from context
   - Automatic sanitization based on output context (prevents XSS, SQL injection)
   - Missing variable tracking via `$options['missing_variables']` array

3. **Context Validation**:
   ```php
   $validation = $this->validate_context(['entry_id', 'form_data'], $context);
   if (!$validation['valid']) {
     return new WP_Error('missing_context', 'Missing: ' . implode(', ', $validation['missing']));
   }
   ```

4. **Return Format**:
   - Success: `return ['success' => true, 'data' => $result]`
   - Error: `return new WP_Error('error_code', 'Error message')`
   - Stop workflow: `return ['success' => false, 'stop_execution' => true]`

**Action Registration** (`/src/includes/automations/class-automation-registry.php`):
```php
// In load_builtin_actions()
$this->register_action('send_email', 'SUPER_Action_Send_Email');
$this->register_action('webhook', 'SUPER_Action_Webhook');
$this->register_action('generate_pdf', 'SUPER_Action_Generate_PDF');  // Future action
```
- Maps action ID to PHP class name
- Class autoloaded from `/src/includes/automations/actions/class-action-{slug}.php`

**For New Button Action Types:**

Three new actions needed:

1. **Generate File Action** (`SUPER_Action_Generate_File`):
   - Settings: template (HTML/Markdown), output_format (PDF/DOCX/CSV), filename pattern
   - Uses libraries: TCPDF for PDF, PhpSpreadsheet for Excel/CSV
   - Returns: `['file_url' => $url, 'attachment_id' => $id]`

2. **Temporary Access Action** (`SUPER_Action_Temp_Access`):
   - Settings: expiry_duration, access_type (view_entry, download_file, edit_entry)
   - Generates: unique token, stores in `wp_superforms_temp_access` table
   - Returns: `['access_url' => $url, 'token' => $token, 'expires_at' => $timestamp]`

3. **SQL Query Action** (`SUPER_Action_SQL_Query`):
   - Settings: query_template (with {tags}), connection (default or custom)
   - Security: whitelist of allowed operations (SELECT only by default)
   - Uses: wpdb prepared statements for safety
   - Returns: `['rows' => $results, 'row_count' => $count]`

### How Elements Render: From Schema to Visual Component

**Element Rendering Pipeline**

Elements on the canvas are rendered through a React component hierarchy that applies both global theme styles and element-specific overrides.

**Rendering Flow** (`/src/react/admin/apps/form-builder-v2/components/elements/ElementRenderer.tsx`):

1. **Style Resolution** (happens first):
   ```typescript
   // useResolvedStyle hook merges global + overrides
   const labelStyle = useResolvedStyle(element.id, 'label');
   const inputStyle = useResolvedStyle(element.id, 'input');
   const buttonStyle = useResolvedStyle(element.id, 'button');

   // Convert to CSS and memoize
   const resolvedStyles = useMemo(() => ({
     label: stylesToCSS(labelStyle),
     input: mergeWithElementProps(stylesToCSS(inputStyle), element.properties),
     button: stylesToCSS(buttonStyle)
   }), [labelStyle, inputStyle, buttonStyle]);
   ```

2. **Element Selection** (lazy loaded):
   ```typescript
   const renderElement = () => {
     switch (element.type) {
       case 'text':
       case 'email':
         return <TextInput element={element} styles={resolvedStyles} />;
       case 'textarea':
         return <TextArea element={element} styles={resolvedStyles} />;
       case 'button':  // Future implementation
         return <Button element={element} styles={resolvedStyles} />;
     }
   };
   ```

3. **Component Structure** (example: TextInput):
   ```typescript
   // Each element component receives:
   interface TextInputProps {
     element: {
       type: string;
       id: string;
       properties: Record<string, unknown>;  // From schema
       styleOverrides?: Record<string, Record<string, unknown>>;
     };
     styles: ResolvedStyles;  // Pre-computed CSS
   }
   ```

4. **Node-Based Styling**:
   - Elements contain multiple "nodes" (label, input, error, description, etc.)
   - Each node can be styled independently
   - Style capabilities defined in `NODE_STYLE_CAPABILITIES` (`/src/react/admin/schemas/styles/capabilities.ts`)
   - Example for button node:
     ```typescript
     button: {
       layout: true,      // margin, padding, width, height
       typography: true,  // font family, size, weight, color
       background: true,  // backgroundColor, gradient
       border: true,      // borderWidth, borderColor, borderRadius
       effects: true,     // boxShadow, opacity, transform
       states: true       // hover, focus, active, disabled
     }
     ```

5. **Style Registry** (`/src/react/admin/schemas/styles/registry.ts`):
   - Global theme styles stored in memory via subscription system
   - `styleRegistry.setGlobalStyle(nodeType, updates)` updates theme
   - `useGlobalStyles()` hook subscribes to changes, triggers re-render
   - Element overrides stored in `element.styleOverrides[nodeType]`

**For Button Component:**

The Button element will render as:
```typescript
<Button element={element} styles={resolvedStyles} onAction={handleButtonAction} />
```

Where the component internally handles:
- Loading states (spinner + loadingText)
- Success/error states (toast notifications)
- Event firing (calls automation endpoint)
- Result display (from automation execution)

Button styling will support all node capabilities:
- Base button node: full styling (typography, background, border, effects, states)
- Icon node: if `icon` property set
- Loading spinner node: shown during execution

### How MCP Tools Work: LLM Integration Pattern

**MCP (Model Context Protocol) Integration**

The codebase has a working MCP system for style manipulation that provides a blueprint for button/automation MCP tools.

**Current MCP Architecture** (`/src/react/admin/mcp/`):

1. **Schema Definition** (`schemas/styleActionSchema.ts`):
   - Uses Zod discriminated unions for type-safe actions
   - Each action is a distinct object type with required fields
   - Example:
     ```typescript
     const SetGlobalPropertyAction = z.object({
       action: z.literal('setGlobalProperty'),
       nodeType: NodeTypeSchema,
       property: z.string(),
       value: z.unknown()
     });

     export const StyleActionSchema = z.discriminatedUnion('action', [
       SetGlobalPropertyAction,
       GetGlobalStyleAction,
       ApplyThemeAction,
       // ... 15+ action types
     ]);
     ```

2. **Handler Implementation** (`handlers/styleActions.ts`):
   - Single async function handles all action types
   - Validates input, executes action, returns standardized response
   - Pattern:
     ```typescript
     export async function handleStyleAction(rawAction: unknown): Promise<StyleActionResponse> {
       const parseResult = StyleActionSchema.safeParse(rawAction);
       if (!parseResult.success) {
         return { success: false, error: parseResult.error.message };
       }

       const action = parseResult.data;

       switch (action.action) {
         case 'setGlobalProperty':
           styleRegistry.setGlobalProperty(action.nodeType, action.property, action.value);
           return { success: true, data: { updated: true } };

         case 'applyTheme':
           const theme = await wp.apiFetch({ path: `/super-forms/v1/themes/${action.themeId}` });
           styleRegistry.importStyles(JSON.stringify(theme.styles));
           return { success: true, data: { themeName: theme.name } };
       }
     }
     ```

3. **Tool Definition** (for LLM consumption):
   ```typescript
   export const styleToolDefinition = {
     name: 'form_styles',
     description: `Manage form element styles with global themes...`,
     inputSchema: StyleActionSchema
   };
   ```
   - Name used by LLM to invoke tool
   - Description provides usage examples and concepts
   - inputSchema enables auto-validation

4. **Integration with WordPress**:
   - Handler uses `wp.apiFetch()` for REST API calls
   - Declared type: `declare const wp: { apiFetch: <T>(options) => Promise<T> }`
   - Authentication automatic via WordPress cookies
   - Example API call:
     ```typescript
     const themes = await wp.apiFetch<Theme[]>({
       path: '/super-forms/v1/themes?include_stubs=true',
       method: 'GET'
     });
     ```

**For Button MCP Tools:**

Create `/src/react/admin/mcp/handlers/buttonActions.ts` with schema covering:

1. **addButton** - Add button element to form
   ```typescript
   { action: 'addButton', formId: 123, buttonText: 'Generate PDF', eventId: 'generate_pdf', ... }
   ```

2. **configureButton** - Update button properties
   ```typescript
   { action: 'configureButton', elementId: 'btn-123', updates: { variant: 'primary', icon: 'download' } }
   ```

3. **bindButtonToAutomation** - Connect button to automation flow
   ```typescript
   { action: 'bindButtonToAutomation', buttonId: 'btn-123', automationId: 456 }
   ```

4. **createButtonAutomation** - Create complete button + automation in one call
   ```typescript
   {
     action: 'createButtonAutomation',
     buttonText: 'Download Report',
     eventId: 'download_report',
     actions: [
       { type: 'generate_pdf', config: { template: 'entry_summary' } },
       { type: 'send_email', config: { to: '{user_email}', attachment: '{generated_file}' } }
     ]
   }
   ```

### Critical File Locations

**Schema System:**
- Element schemas: `/src/react/admin/schemas/elements/` (add `button.ts` here)
- Core types: `/src/react/admin/schemas/core/types.ts` (27 property types)
- Registry: `/src/react/admin/schemas/core/registry.ts` (element registration)
- Style capabilities: `/src/react/admin/schemas/styles/capabilities.ts`

**Automation System:**
- Registry: `/src/includes/automations/class-automation-registry.php` (event/action registration)
- Executor: `/src/includes/automations/class-automation-executor.php` (event firing, action execution)
- Action base: `/src/includes/automations/class-action-base.php` (extend for new actions)
- Actions directory: `/src/includes/automations/actions/` (add new action classes here)
- DAL: `/src/includes/automations/class-automation-dal.php` (database operations)

**Frontend Components:**
- ElementRenderer: `/src/react/admin/apps/form-builder-v2/components/elements/ElementRenderer.tsx`
- Elements: `/src/react/admin/apps/form-builder-v2/components/elements/basic/` (add Button.tsx)
- Property panels: `/src/react/admin/apps/form-builder-v2/components/property-panels/`

**MCP Integration:**
- MCP schemas: `/src/react/admin/mcp/schemas/` (add `buttonActionSchema.ts`)
- MCP handlers: `/src/react/admin/mcp/handlers/` (add `buttonActions.ts`)
- MCP index: `/src/react/admin/mcp/index.ts` (export new tools)

**Form Submission Flow:**
- Main handler: `/src/includes/class-ajax.php` (submit_form() method, line 2847+)
- Event firing: Lines 2856 (validation_failed), 2945 (spam_detected), 4796 (entry.created), 5670 (form.submitted)
- Session management: `/src/includes/class-session-dal.php`

**Database Tables:**
- Automations: `wp_superforms_automations` (automation definitions)
- Actions: `wp_superforms_automation_actions` (action configs)
- Logs: `wp_superforms_automation_logs` (execution history)
- States: `wp_superforms_automation_states` (workflow state)
- Sessions: `wp_superforms_sessions` (progressive save data)

### Key Patterns & Conventions

**Element Schema Pattern:**
- Use `withBaseProperties()` to inherit standard fields
- Group properties by category (general, validation, appearance, advanced, conditions)
- Mark translatable strings with `translatable: true`
- Mark tag-supporting fields with `supportsTags: true`
- Use `conditions` array to show/hide properties based on others

**Action Implementation Pattern:**
- Extend `SUPER_Action_Base`
- Use `replace_variables()` for tag substitution with proper sanitization
- Use `validate_context()` to check required context fields
- Return `WP_Error` for failures, `['success' => true]` for success
- Set `supports_async()` to true for external/slow operations

**MCP Tool Pattern:**
- Define discriminated union schema with Zod
- Single handler function with switch statement
- Return `{ success: boolean, data?: unknown, error?: string }`
- Use `wp.apiFetch()` for WordPress REST API calls
- Provide verbose description with usage examples

**Frontend Event Handling:**
- Button clicks POST to `wp_ajax_super_trigger_button_action`
- Include: `form_id`, `button_id`, `form_data`, `nonce`
- Show loading state immediately
- Handle success (show message, download file, etc.)
- Handle errors (show error toast)

### Frontend Event Trigger System Implementation

**Architecture (Subtasks 02a & 02b):**

The frontend event trigger system enables any frontend interaction (button clicks, field changes, etc.) to fire automation workflows without full form submission.

**PHP Backend** (`/src/includes/class-frontend-event-trigger.php`):
- Unified AJAX handler: `wp_ajax_super_trigger_frontend_event` and `wp_ajax_nopriv_super_trigger_frontend_event`
- Verifies nonce, validates form ownership
- Fires automation event via `SUPER_Automation_Executor::fire_event()`
- Returns execution results to frontend (success/error, file URLs, messages, etc.)
- Initialized in `/src/super-forms.php` line 293-295

**TypeScript Frontend** (`/src/react/admin/lib/frontendEvents.ts`):
- Generic `triggerFrontendEvent<T>(eventName, context)` function
- Typed wrapper functions: `triggerButtonClick()`, `triggerFieldChange()`, `triggerCustomEvent()`
- Type-safe context objects using generics
- Double cast through `unknown` required for complex type conversions
- Nonce provided via `window.super_common_i18n.frontend_event_nonce`

**Event Registration:**
Button events registered in `/src/includes/automations/class-automation-registry.php`:
```php
$this->register_event('button.*.clicked', [
  'label' => 'Button Clicked',
  'description' => 'Any button element clicked',
  'category' => 'interaction',
  'available_context' => ['form_id', 'button_id', 'event_name', 'form_data', 'session_key'],
  'required_context' => ['form_id', 'button_id']
]);
```

**Button React Component** (`/src/react/admin/apps/form-builder-v2/components/elements/basic/Button.tsx`):
- State management: `idle`, `loading`, `success`, `error`
- Icon rendering based on state (no dynamic icon lookup)
- Calls `triggerButtonClick()` on click
- Shows loading spinner during execution
- Displays success/error messages from automation result
- Integrated into ElementRenderer

**Style System Extension:**
- Added `wrapper` node type to `ResolvedStyles` interface (`/src/react/admin/lib/styleUtils.ts`)
- Added `wrapper` to NodeType schema (`/src/react/admin/schemas/styles/types.ts`)
- Enables independent styling of button wrapper container

### Security Considerations

**Action Permissions:**
- Check `current_user_can()` before executing sensitive actions
- SQL Query action must whitelist allowed operations
- File generation should validate templates (no PHP execution)
- Temp access tokens must be cryptographically random (use `wp_generate_password(32, true, true)`)

**CSRF Protection:**
- Button actions require valid nonce
- Form ownership validated (user must be admin or form creator)
- Cross-origin requests blocked via `SUPER_Common::verifyCSRF()`

**Variable Replacement Safety:**
- Always use `replace_variables()` with appropriate sanitization
- Never trust user input in file paths or SQL queries
- Validate email addresses before sending
- Escape HTML output unless explicitly allowing rich content

## User Notes

- Buttons should leverage shadcn Button component
- Must support multiple buttons per form with independent behaviors
- Each button needs unique automation trigger binding
- Consider wizard/multi-step forms where buttons control navigation
- PDF generation, file downloads, temp access tokens are key use cases
- Schema must be MCP-friendly for LLM integration

## Work Log

### 2025-12-15

#### Completed
- Subtask 00: Research completed with 14 button use case epics documented
- Subtask 01: Button element schema, Zod validation, and MCP tools implemented
- Subtask 02a: Unified frontend event trigger system (PHP + TypeScript)
  - Created `SUPER_Frontend_Event_Trigger` class with AJAX handler
  - Added `triggerFrontendEvent()` TypeScript function with typed wrappers
  - Integrated nonce into `super_common_i18n` localization
- Subtask 02b: Button event implementation
  - Registered `button.*.clicked` event in automation registry
  - Created full Button React component with loading/success/error states
  - Added Button to ElementRenderer
  - Extended ResolvedStyles interface with `wrapper` node type
- Subtask 03: Automation button binding UI (completed in previous session, verified in this session)
  - REST API filtering by trigger_event pattern and form_id
  - `ButtonAutomationPanel` component showing linked automations
  - `ButtonAutomationIndicator` canvas badge with automation count
  - Deep links to automation editor from button properties
  - Verified build passes with no TypeScript errors

#### Decisions
- Unified frontend event system supports all future frontend-triggered automation events
- Button component uses state-based icon rendering (no dynamic icon lookup)
- Wrapper styles handled as separate node type for flexible layout control
- Used React Query for fetching linked automations with proper caching
- Badge positioned as absolute overlay with lightning bolt icon for visual prominence

#### Discovered
- Type conversion required double cast through `unknown` in TypeScript for event context
- `BoxSpacing` type doesn't exist, corrected to `Spacing` type import
- Build passes successfully with all type validations
- window.sfuiData TypeScript declaration lives in workflow.types.ts (required property)
- REST API uses query parameters for filtering: `/automations?trigger_event={pattern}&form_id={id}`

#### Next Steps
- Subtask 04: Implement built-in automation node types (generate_file, temp_access, calculate, validate)
