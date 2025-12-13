---
name: h-implement-element-properties-tray
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-12-13
---

# Implement Element Properties Tray for Mobile

## Problem/Goal
The current mobile drawer system for editing element properties has scroll issues when focusing input fields - the browser's auto-scroll behavior conflicts with our scroll containers. Instead of fighting this, we should use the same bottom tray pattern that already works well for the elements palette.

When a user taps an element on mobile, show an "element properties tray" that:
- Uses the same UI pattern as the existing ResizableBottomTray (elements palette)
- Overlays on top of the elements tray with higher z-index
- Contains the same property panel content currently shown in the drawer
- Has proper scroll containment for its content

## Success Criteria
- [ ] Element properties tray appears when element is selected on mobile
- [ ] Tray uses same visual style as elements tray (resize handle, collapse behavior)
- [ ] Property panel content renders correctly inside the tray
- [ ] Only one tray visible at a time: elements tray hidden when properties tray is open
- [ ] Canvas remains scrollable when properties tray is open (touch/scroll on canvas area)
- [ ] Scrolling within tray content is contained to tray only
- [ ] Focusing inputs scrolls tray content, not the page
- [ ] Tray can be dismissed (close button or deselecting element)
- [ ] Dismissing properties tray shows elements tray again

## Context Manifest

### How the Current Mobile Property System Works

The Form Builder V2 currently uses a **MobileDrawer** component for displaying element properties on mobile devices. When a user taps an element on the canvas, the following flow occurs:

**Element Selection Flow (Desktop & Mobile):**

When a user clicks/taps an element on the canvas, the `handleSelectElement` callback fires (line 2309 in FormBuilderV2.tsx). This function:

1. Updates the selected element in state via `setSelectedElements([elementId])`
2. Updates breadcrumb tracking via `setSelectedElementId` and `setSelectedElementPath`
3. If not multi-selecting (Ctrl/Cmd click), it sets the `floatingPanel` state with the element ID and position:
   ```javascript
   setFloatingPanel({
     elementId: element.id,
     position: {
       x: rect.left,
       y: rect.bottom + 10
     }
   });
   ```

The `floatingPanel` state is a simple object: `{ elementId: string | null; position: { x: number; y: number } }` (line 1670).

**Mobile Rendering - Always-Mounted MobileDrawer Pattern:**

On mobile (detected via `useIsMobile()` hook which checks `max-width: 639px`), the FloatingPanel component is **always rendered** to prevent unmount/remount flicker (lines 3930-3966). The component determines what element ID to display:

1. First priority: Currently selected element if it exists (`floatingPanel?.elementId`)
2. Second priority: Last selected element (tracked in `lastSelectedElementIdRef`)
3. Third priority: First available element in `items`

The FloatingPanel component receives an `open` prop that controls whether the MobileDrawer is visible:
```javascript
const isOpen = !!(floatingPanel?.elementId && items[floatingPanel.elementId]);
```

**MobileDrawer Component Internals (`/src/react/admin/components/ui/mobile-drawer.tsx`):**

The MobileDrawer is a custom component that replaced Vaul in v6.6.0. Key features:

- **Always-mounted pattern**: Drawer is always in DOM, positioned off-screen with `transform: translateY(100%)` when closed
- **Portal rendering**: Renders to `document.body` via React portal
- **Visual Viewport API**: Adapts height when mobile keyboard opens (70% of visual viewport height by default)
- **Keyboard offset**: Moves drawer up to stay above keyboard when inputs are focused
- **Touch swipe-to-close**: Drag handle at top allows swiping down to dismiss
- **Props**:
  - `open: boolean` - Controls visibility (transform position)
  - `onClose: () => void` - Called when user closes drawer
  - `height?: number` - Fixed height in pixels (defaults to 70% of viewport)
  - `children: ReactNode` - Content to render
  - `className?: string` - Additional classes
- **Z-index**: `z-50` (line 224)
- **No scroll lock**: Background (canvas) remains scrollable when drawer is open (removed in recent update for better UX)

**FloatingPanel Component Structure (`/src/react/admin/apps/form-builder-v2/components/property-panels/FloatingPanel.tsx`):**

The FloatingPanel is the property editor that renders either as a floating panel (desktop) or inside MobileDrawer (mobile). On mobile (lines 350-376):

1. Wraps all content in MobileDrawer component
2. Renders PanelHeader with element name, delete, and close buttons
3. Renders 6-tab navigation: Content, Style, Behavior, Code, Templates, AI
4. Renders tab content panels with property controls
5. Uses `overflow-y-auto` on the content area for internal scrolling

The component subscribes to element data from `useElementsStore` to get fresh data without prop drilling.

**Desktop vs Mobile Rendering:**

- **Desktop**: FloatingPanel renders as a fixed positioned panel (`z-50`) near the clicked element
- **Mobile**: FloatingPanel content wraps in MobileDrawer which handles the slide-up animation and backdrop

### How the Elements Tray (ResizableBottomTray) Works

**ResizableBottomTray Component (`/src/react/admin/apps/form-builder-v2/components/ui/overlays/ResizableBottomTray.tsx`):**

This component displays the element palette at the bottom of the screen. Key architecture:

**Props (defined in `overlay.types.ts` lines 54-62):**
```typescript
{
  isCollapsed: boolean;           // Whether tray is minimized
  onToggleCollapse: () => void;   // Callback when collapse button clicked
  children: ReactNode;            // Tray content (element palette)
  isMobile?: boolean;             // Mobile mode flag
  minHeight?: number;             // Default: 190px
  maxHeight?: number;             // Default: 500px
  defaultHeight?: number;         // Default: 220px
  onHeightChange?: (height: number) => void; // Notify parent of height changes
}
```

**Visual Structure:**
- Fixed position at bottom of screen: `fixed bottom-0 right-0`
- Z-index: `z-[50]` (same as MobileDrawer and FloatingPanel)
- Left edge respects WordPress admin sidebar width via `useWPAdminSidebar()` hook
- Collapsed height: 40px (just shows collapse button)
- Expanded height: User-resizable via drag handle (desktop only)

**Resize Behavior (Desktop Only):**
- Drag handle at top with grip icon (lines 152-168)
- Mouse drag handlers track `startY` and `startHeight` refs
- Updates height in state, clamped between `minHeight` and `maxHeight`
- Window resize listener adjusts height if viewport shrinks
- Notifies parent via `onHeightChange` callback

**Mobile Behavior:**
- No resize handle shown (`!isMobile` check on line 153)
- Uses auto height with `max-h-[50vh]` constraint
- Still has collapse button for showing/hiding

**Collapse Button:**
- Positioned absolutely at `-top-4` (above the tray border)
- Centered horizontally with `left-1/2 -translate-x-1/2`
- Rounded tab shape: `rounded-t-lg rounded-b-none`
- Chevron icon: Up when collapsed, Down when expanded
- Z-index: `z-[1]` relative to tray

**Integration in FormBuilderV2 (lines 3760-3926):**

The tray state is managed at the FormBuilderV2 level:
```javascript
const [isTrayCollapsed, setIsTrayCollapsed] = useState(false);
const [trayHeight, setTrayHeight] = useState(250);

// Callback to track height changes
const handleTrayHeightChange = useCallback((newHeight: number) => {
  setTrayHeight(newHeight);
  // Update canvas bottom padding to prevent overlap
  const paddingBuffer = 100;
  const effectiveFooterHeight = isTrayCollapsed ? 40 : newHeight;
  setCanvasBottomPadding(effectiveFooterHeight + paddingBuffer);
}, [isTrayCollapsed]);
```

The canvas has dynamic bottom padding to prevent elements from being hidden behind the tray.

### Element Selection State Management

**useBuilderStore (`/src/react/admin/apps/form-builder-v2/store/useBuilderStore.ts`):**

This Zustand store manages drag-and-drop and selection state:

```typescript
interface BuilderState {
  draggedElement: string | null;      // Currently dragged element ID
  hoveredElement: string | null;      // Currently hovered element ID
  selectedElements: string[];         // Array of selected element IDs
  dropZoneActive: boolean;            // Whether drop zones are active
  dropTarget: { id: string; position: 'before' | 'after' | 'inside' } | null;
}

// Actions
setSelectedElements(elementIds: string[]): void
addSelectedElement(elementId: string): void
removeSelectedElement(elementId: string): void
clearSelection(): void
```

**useElementsStore:**

This store (referenced but not fully read) manages the actual element data (`items: Record<string, FormElement>`). The FloatingPanel subscribes to specific elements:

```javascript
const element = useElementsStore((s) => s.items[elementId]);
```

This subscription pattern ensures the panel only re-renders when the specific element changes, not when any element in the form changes.

**Local State in FormBuilderV2:**

Beyond the Zustand stores, FormBuilderV2 maintains local state for UI concerns:
- `selectedElements: string[]` - Currently selected element IDs
- `multiSelectElements: string[]` - Elements selected via Ctrl+click
- `floatingPanel: { elementId: string | null; position: { x: y: } } | null` - Panel visibility and position
- `selectedElementId: string | null` - For breadcrumb tracking
- `selectedElementPath: string[]` - Path to selected element (for nested containers)

### Body Scroll Lock Implementation

**FormBuilderV2 Mount Effect (lines 1436-1465):**

On component mount, the form builder locks the entire page scroll to prevent browser auto-scroll conflicts:

```javascript
useEffect(() => {
  const originalOverflow = document.body.style.overflow;
  const originalPosition = document.body.style.position;
  const originalWidth = document.body.style.width;
  const originalTop = document.body.style.top;
  const scrollY = window.scrollY;

  // Lock body scroll
  document.body.style.overflow = 'hidden';
  document.body.style.position = 'fixed';
  document.body.style.width = '100%';
  document.body.style.top = `-${scrollY}px`;

  return () => {
    // Restore original styles on unmount
    document.body.style.overflow = originalOverflow;
    document.body.style.position = originalPosition;
    document.body.style.width = originalWidth;
    document.body.style.top = originalTop;
    // Restore scroll position
    window.scrollTo(0, scrollY);
  };
}, []);
```

This means:
- The entire page body is locked from scrolling
- The canvas area manages its own scroll independently
- The MobileDrawer manages its own internal scroll
- The ResizableBottomTray content manages its own scroll

This architecture is WHY the new properties tray pattern will work - each scroll container is independent, and browser auto-scroll for input focus only affects the container the input is within.

### Current Mobile Drawer Rendering Pattern

**Mobile-Specific FloatingPanel Pattern (lines 3930-3966):**

The current implementation on mobile uses this pattern:

1. **Always render** FloatingPanel (even when no element selected)
2. FloatingPanel internally wraps everything in MobileDrawer
3. MobileDrawer's `open` prop controls visibility (not mounting)
4. When `open={false}`, drawer slides off-screen with `translateY(100%)`
5. This prevents the "flash" that would occur with mount/unmount cycles

**Content Inside MobileDrawer:**

The FloatingPanel renders these sections inside the drawer:
1. **PanelHeader** - Element name, delete button, close button (lines 359-367)
2. **Tabs wrapper** - The shadcn/ui Tabs component with `flex flex-col flex-1 min-h-0` (line 369)
3. **TabNavigation** - The 6 tab buttons (Content, Style, Behavior, Code, Templates, AI)
4. **TabContentPanels** - The scrollable content area that renders property controls

On mobile, the TabContentPanels component (lines 152-169) uses a plain `div` with `overflow-y-auto` instead of ScrollArea to prevent nested scroll containers.

### Z-Index Layering Strategy

Current z-index usage in the system:

- **ResizableBottomTray**: `z-[50]` (elements palette)
- **MobileDrawer**: `z-50` (property panel drawer)
- **FloatingPanel (desktop)**: `z-50` (desktop floating panel)
- **Collapse button**: `z-[1]` (relative to tray, so effectively z-51)

For the new properties tray to overlay the elements tray, it needs a **higher z-index** than `z-[50]`. Suggested: `z-[60]` or `z-[55]` to clearly sit above.

### Key Implementation Insights

**Why a Bottom Tray Will Work Better Than MobileDrawer:**

1. **Scroll containment**: The ResizableBottomTray pattern already handles scroll properly - content area scrolls, tray position is fixed
2. **No Visual Viewport complications**: Unlike MobileDrawer which needs to track keyboard and adjust position, a bottom tray can have simpler height behavior
3. **Consistent with elements tray**: Same UI pattern, less cognitive load for users
4. **No backdrop**: Elements tray doesn't use a backdrop, properties tray shouldn't need one either if it overlays cleanly

**What Content to Render:**

The properties tray should render the **exact same content** currently inside the MobileDrawer in FloatingPanel. Specifically:
- `<PanelHeader>` component (with element name, delete, close)
- `<Tabs>` wrapper
- `<TabNavigation>` (6 tab buttons)
- `<TabContentPanels>` (scrollable property controls)

This can be extracted or the FloatingPanel component can be refactored to support a third rendering mode: desktop floating, mobile drawer (legacy), and mobile tray (new).

**Show/Hide Logic:**

- **Show properties tray** when: `floatingPanel?.elementId` is truthy (element selected)
- **Hide elements tray** when: Properties tray is visible
- **Show elements tray** when: Properties tray is dismissed (user closes it or deselects element)

This is a simple toggle based on `floatingPanel` state that already exists.

**Close Interactions:**

The properties tray should close when:
1. User clicks the X button in PanelHeader
2. User deselects the element (clicks canvas background)
3. User deletes the element
4. User presses Escape key (already handled by FloatingPanel)

All of these already call `setFloatingPanel(null)`, so the existing close logic will work.

### File Locations for Implementation

**Component to Create/Modify:**

1. **ResizableBottomTray** - `/src/react/admin/apps/form-builder-v2/components/ui/overlays/ResizableBottomTray.tsx`
   - Clone this as starting point for properties tray, OR
   - Refactor to accept a `variant` prop for "elements" vs "properties" mode

2. **FloatingPanel** - `/src/react/admin/apps/form-builder-v2/components/property-panels/FloatingPanel.tsx`
   - Modify mobile rendering branch (lines 350-376) to use new tray instead of MobileDrawer
   - Or extract the content rendering to a shared component used by both drawer and tray

3. **FormBuilderV2** - `/src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx`
   - Add rendering logic for properties tray on mobile (around line 3760 where elements tray is rendered)
   - Add conditional logic to hide elements tray when properties tray is open
   - Wire up state and callbacks

**Types to Update:**

- `/src/react/admin/apps/form-builder-v2/components/ui/types/overlay.types.ts`
  - Add interface for properties tray props (likely very similar to ResizableBottomTrayProps)

**Hooks Used:**

- `useIsMobile()` - `/src/react/admin/hooks/useMediaQuery.ts` (line 26)
  - Returns `true` when viewport is `max-width: 639px`
  - Already used throughout FormBuilderV2 for mobile-specific rendering

### Technical Reference Details

#### ResizableBottomTray Rendering Logic

```typescript
// Desktop mode: resizable with drag handle
{!isCollapsed && !isMobile && (
  <div className="h-4 bg-muted border-b cursor-ns-resize" onMouseDown={handleResizeStart}>
    <GripHorizontal size={16} />
  </div>
)}

// Collapse button (both mobile and desktop)
<Button
  className="absolute -top-4 left-1/2 -translate-x-1/2 w-12 h-4 rounded-t-lg"
  onClick={onToggleCollapse}
>
  {isCollapsed ? <ChevronUp size={12} /> : <ChevronDown size={12} />}
</Button>

// Content (when not collapsed)
{!isCollapsed && children}
```

#### FloatingPanel Mobile Content Structure

```typescript
<MobileDrawer open={open} onClose={onClose}>
  <PanelHeader
    displayName={displayName}
    hasSchema={hasSchema}
    IconComponent={IconComponent}
    onDelete={onDelete}
    onClose={onClose}
    isMobile={true}
  />
  <Tabs value={activeTab} onValueChange={setActiveTab} className="flex flex-col flex-1 min-h-0">
    <TabNavigation />
    <TabContentPanels isMobile={isMobile}>
      <div className="p-4">
        <TabsContent value="content">
          <ContentTab element={element} onPropertyChange={onPropertyChange} />
        </TabsContent>
        {/* ... other tabs */}
      </div>
    </TabContentPanels>
  </Tabs>
</MobileDrawer>
```

#### Element Click Handler Signature

```typescript
const handleSelectElement = useCallback((elementId: string, event?: React.MouseEvent) => {
  // Multi-select check
  const isMultiSelect = event?.ctrlKey || event?.metaKey;

  // Update selection state
  if (!isMultiSelect) {
    setSelectedElements([elementId]);
    setFloatingPanel({
      elementId: element.id,
      position: { x: rect.left, y: rect.bottom + 10 }
    });
  }
}, [items]);
```

This callback is passed down to SortableElement components via the `onSelect` prop (line 51 in SortableElement.tsx).

#### State Management Pattern

```typescript
// FormBuilderV2.tsx - State declarations
const [floatingPanel, setFloatingPanel] = useState<{
  elementId: string | null;
  position: { x: number; y: number };
} | null>(null);

const [isTrayCollapsed, setIsTrayCollapsed] = useState(false);
const isMobile = useIsMobile();

// Conditional rendering logic
{isMobile && floatingPanel?.elementId && (
  <PropertiesTray
    elementId={floatingPanel.elementId}
    isCollapsed={false}
    onClose={() => setFloatingPanel(null)}
    onToggleCollapse={() => {/* Optional: allow collapsing properties tray */}}
  >
    {/* Property panel content */}
  </PropertiesTray>
)}

{!isMobile || !floatingPanel?.elementId ? (
  <ResizableBottomTray
    isCollapsed={isTrayCollapsed}
    onToggleCollapse={() => setIsTrayCollapsed(!isTrayCollapsed)}
  >
    {/* Element palette content */}
  </ResizableBottomTray>
) : null}
```

### Implementation Strategy Recommendation

**Approach A: Clone and Customize ResizableBottomTray**

Create a new `PropertiesBottomTray` component that:
1. Copies the ResizableBottomTray structure (fixed bottom positioning, collapse button)
2. Removes resize functionality (not needed for properties - can be fixed height or auto)
3. Uses higher z-index (`z-[60]`)
4. Accepts `elementId` prop instead of generic children
5. Internally renders the FloatingPanel content (PanelHeader + Tabs)

**Approach B: Refactor FloatingPanel to Support Tray Mode**

Modify FloatingPanel to accept a `mode` prop:
1. `mode="floating"` - Desktop floating panel (current)
2. `mode="drawer"` - Mobile drawer (current)
3. `mode="tray"` - Mobile bottom tray (new)

When `mode="tray"`, wrap the content in a tray container instead of MobileDrawer.

**Approach C: Generic Bottom Tray Component**

Extract the common tray pattern into a generic `BottomTray` component that both elements palette and properties use. Props:
- `variant: "elements" | "properties"`
- `isCollapsed: boolean`
- `onToggleCollapse: () => void`
- `zIndex?: number` (default based on variant)
- `children: ReactNode`

This provides maximum reusability and consistency.

**Recommended: Approach A** - Simplest to implement, least risk of breaking existing functionality, clear separation of concerns.

## User Notes
- Stay on current branch `feature/h-implement-triggers-actions-extensibility`
- Body scroll lock is already implemented in FormBuilderV2
- Clone/adapt the existing ResizableBottomTray component pattern
- The drawer system (MobileDrawer) remains in codebase but won't be used for element properties on mobile

## Work Log
<!-- Updated as work progresses -->
- [2025-12-13] Task created
