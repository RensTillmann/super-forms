---
name: h-formbuilder-mobile-responsive
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-12-08
---

# FormBuilderV2 Mobile Responsive Design

## Problem/Goal

Make the FormBuilderV2 page fully responsive and mobile-friendly. Currently, the UI overflows on mobile devices, menus/tabs are not scrollable, and several components have hardcoded pixel widths that exceed mobile viewport dimensions.

## Success Criteria
- [ ] No horizontal overflow on mobile viewports (375px)
- [ ] TabBar scrolls horizontally on mobile
- [ ] RightSidebar displays as full-screen overlay on mobile
- [ ] FloatingPanel displays as full-screen on mobile
- [ ] Primary actions (Save/Publish) accessible on mobile
- [ ] Touch targets are >= 44px

## Current Issues
1. **Horizontal Overflow**: UI extends beyond screen width on mobile (375px viewport)
2. **Non-Scrollable Tabs**: TabBar lacks `overflow-x-auto`, ~700px of tabs won't fit
3. **Non-Responsive Toolbar**: TopBar has 15+ buttons with no responsive handling
4. **Fixed-Width Panels**:
   - RightSidebar: 400px hardcoded (> 375px mobile viewport)
   - FloatingPanel: 480px hardcoded with only 16px margin safety
5. **Touch Accessibility**: Hover-dependent UI, mouse-based resize, small touch targets

### Root Cause Analysis

| Component | Current Width | Issue |
|-----------|--------------|-------|
| `TabBar` | ~700px (7 tabs × 100px) | No `overflow-x-auto` |
| `TopBar` | ~600px+ (15+ buttons) | No flex-wrap, no responsive hiding |
| `RightSidebar` | Fixed 400px | Hardcoded `minWidth` > mobile viewport |
| `FloatingPanel` | Fixed 480px | Only 16px margin buffer |

## Implementation Phases

### Phase 1: Quick Fixes (Prevent Overflow)
**Estimated Effort:** 2-3 hours

#### 1.1 TabBar Horizontal Scroll
**File:** `src/react/admin/apps/form-builder-v2/components/TabBar.tsx`

```tsx
// Current (line 93-99):
<div className="flex items-center gap-1 px-4 py-2 bg-muted/50 border-b border-border">

// Change to:
<div className={cn(
  'flex items-center gap-1 px-4 py-2 bg-muted/50 border-b border-border',
  'overflow-x-auto scrollbar-hide scroll-smooth',
  'snap-x snap-mandatory'  // Optional: snap to tabs
)}>
```

Add CSS for scrollbar hiding:
```css
/* In form-builder.css or global styles */
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
```

#### 1.2 RightSidebar Responsive Width
**File:** `src/react/admin/apps/form-builder-v2/components/ui/RightSidebar.tsx`

```tsx
// Current (line 61):
style={{ width: `${width}px`, minWidth: `${width}px` }}

// Change to responsive classes + conditional style:
<div
  className={cn(
    // Mobile: full screen overlay
    'fixed inset-0 z-50',
    // Desktop: sidebar behavior
    'sm:relative sm:inset-auto',
    'flex flex-col bg-background',
    'sm:border-l sm:border-border',
    'sm:h-full',
    // Animation
    'animate-in slide-in-from-bottom sm:slide-in-from-right duration-200',
    className
  )}
  style={{
    // Only apply fixed width on desktop
    ...(typeof window !== 'undefined' && window.innerWidth >= 640
      ? { width: `${width}px`, minWidth: `${width}px` }
      : {})
  }}
>
```

Or simpler approach with Tailwind:
```tsx
className={cn(
  'w-full sm:w-[400px] sm:min-w-[400px]',
  // ... rest of classes
)}
// Remove inline style entirely
```

#### 1.3 FloatingPanel Full-Screen on Mobile
**File:** `src/react/admin/apps/form-builder-v2/components/property-panels/FloatingPanel.tsx`

```tsx
// Current (line 134):
className="fixed z-50 w-[480px] max-w-[calc(100vw-32px)] ..."

// Change to:
className={cn(
  'fixed z-50 bg-white border border-gray-200 rounded-lg shadow-xl',
  'flex flex-col max-h-[600px]',
  // Mobile: full screen with padding
  'inset-4',
  // Desktop: positioned panel
  'sm:inset-auto sm:w-[480px] sm:max-w-[calc(100vw-32px)]'
)}
style={{
  // Only apply position on desktop
  ...(typeof window !== 'undefined' && window.innerWidth >= 640
    ? { left: clampedPosition.left, top: clampedPosition.top }
    : {})
}}
```

#### 1.4 TopBar Responsive Hiding
**File:** `src/react/admin/apps/form-builder-v2/components/TopBar.tsx`

```tsx
// Hide secondary toolbar groups on mobile
// Current structure (lines 191-247):
<div className="flex items-center gap-2">
  {/* History Group - hide on mobile */}
  <div className="hidden sm:flex items-center gap-1 pr-2 border-r border-border">
    ...
  </div>

  {/* Canvas Group - hide on mobile */}
  <div className="hidden md:flex items-center gap-1 pr-2 border-r border-border">
    ...
  </div>

  {/* Panels Group - hide on mobile */}
  <div className="hidden lg:flex items-center gap-1 pr-2 border-r border-border">
    ...
  </div>

  {/* Primary Group - always visible, icons only on mobile */}
  <div className="flex items-center gap-2">
    <button ...>
      <Eye className="w-4 h-4" />
      <span className="hidden sm:inline">Preview</span>
    </button>
    ...
  </div>
</div>
```

---

### Phase 2: Mobile-Optimized UX
**Estimated Effort:** 6-8 hours

#### 2.1 Mobile Hamburger Menu
Create a mobile menu component that contains all hidden toolbar actions.

**New File:** `src/react/admin/apps/form-builder-v2/components/MobileMenu.tsx`

```tsx
import { useState } from 'react';
import { Menu, X } from 'lucide-react';
import { Drawer } from 'vaul';

interface MobileMenuProps {
  onUndo: () => void;
  onRedo: () => void;
  canUndo: boolean;
  canRedo: boolean;
  // ... other actions
}

export function MobileMenu(props: MobileMenuProps) {
  return (
    <Drawer.Root>
      <Drawer.Trigger asChild>
        <button className="sm:hidden p-2 rounded-md hover:bg-muted">
          <Menu className="w-5 h-5" />
        </button>
      </Drawer.Trigger>
      <Drawer.Portal>
        <Drawer.Overlay className="fixed inset-0 bg-black/40" />
        <Drawer.Content className="fixed bottom-0 left-0 right-0 bg-background rounded-t-xl">
          <Drawer.Handle className="mx-auto w-12 h-1.5 bg-muted rounded-full mt-4" />
          <div className="p-4 space-y-4">
            {/* Menu items */}
          </div>
        </Drawer.Content>
      </Drawer.Portal>
    </Drawer.Root>
  );
}
```

#### 2.2 Bottom Action Bar
Fixed bottom bar for primary actions on mobile.

**New File:** `src/react/admin/apps/form-builder-v2/components/MobileActionBar.tsx`

```tsx
import { Eye, Save, Send } from 'lucide-react';

interface MobileActionBarProps {
  onPreview: () => void;
  onSave: () => void;
  onPublish: () => void;
  isSaving?: boolean;
}

export function MobileActionBar({ onPreview, onSave, onPublish, isSaving }: MobileActionBarProps) {
  return (
    <div className="fixed bottom-0 left-0 right-0 sm:hidden z-40 bg-background border-t border-border p-3">
      <div className="flex items-center justify-around gap-2">
        <button
          onClick={onPreview}
          className="flex-1 flex flex-col items-center gap-1 py-2 rounded-md hover:bg-muted"
        >
          <Eye className="w-5 h-5" />
          <span className="text-xs">Preview</span>
        </button>
        <button
          onClick={onSave}
          className="flex-1 flex flex-col items-center gap-1 py-2 rounded-md bg-primary text-primary-foreground"
        >
          <Save className="w-5 h-5" />
          <span className="text-xs">{isSaving ? 'Saving...' : 'Save'}</span>
        </button>
        <button
          onClick={onPublish}
          className="flex-1 flex flex-col items-center gap-1 py-2 rounded-md bg-green-600 text-white"
        >
          <Send className="w-5 h-5" />
          <span className="text-xs">Publish</span>
        </button>
      </div>
    </div>
  );
}
```

#### 2.3 Icons-Only TabBar on Mobile

```tsx
// TabBar.tsx - TabButton component
function TabButton({ tab, isActive, onClick }: TabButtonProps) {
  const Icon = getTabIcon(tab.icon);

  return (
    <button
      className={cn(
        'relative flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md',
        'snap-start',  // For scroll snapping
        'whitespace-nowrap',  // Prevent text wrap
        // Responsive padding
        'px-2 sm:px-3',
        // ... existing active styles
      )}
      onClick={onClick}
      title={tab.description}
    >
      <Icon className="w-4 h-4 shrink-0" />
      {/* Hide text on mobile */}
      <span className="hidden sm:inline">{tab.label}</span>
    </button>
  );
}
```

#### 2.4 Bottom Sheet for Property Editing
Replace FloatingPanel with Vaul drawer on mobile.

```tsx
// FloatingPanel.tsx - wrap content conditionally
import { Drawer } from 'vaul';
import { useMediaQuery } from '../../../../hooks/useMediaQuery';

export function FloatingPanel({ element, position, onClose, ... }: FloatingPanelProps) {
  const isMobile = useMediaQuery('(max-width: 639px)');

  const content = (
    <>
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b ...">
        ...
      </div>
      {/* Content */}
      <div className="flex-1 overflow-y-auto p-4">
        ...
      </div>
    </>
  );

  if (isMobile) {
    return (
      <Drawer.Root open={true} onOpenChange={(open) => !open && onClose()}>
        <Drawer.Portal>
          <Drawer.Overlay className="fixed inset-0 bg-black/40 z-50" />
          <Drawer.Content className="fixed bottom-0 left-0 right-0 z-50 bg-background rounded-t-xl max-h-[85vh] flex flex-col">
            <Drawer.Handle className="mx-auto w-12 h-1.5 bg-muted rounded-full mt-4 mb-2" />
            {content}
          </Drawer.Content>
        </Drawer.Portal>
      </Drawer.Root>
    );
  }

  // Desktop: existing positioned panel
  return (
    <div ref={panelRef} className="fixed z-50 w-[480px] ..." style={{ left, top }}>
      {content}
    </div>
  );
}
```

---

### Phase 3: Mobile-First Redesign (Future)
**Estimated Effort:** 2-4 weeks

This phase involves more significant architectural changes:

1. **Responsive Hook System**
   - Create `useBreakpoint()` hook for consistent breakpoint detection
   - Centralize responsive state management

2. **Mode Switching**
   - "Preview Mode" vs "Edit Mode" on mobile
   - Reduce UI complexity in preview mode

3. **Touch Gestures**
   - Swipe between tabs using react-swipeable-views
   - Drag-to-reorder elements with touch
   - Pinch-to-zoom on canvas

4. **Canvas Adaptation**
   - Touch-friendly element selection
   - Floating mini-toolbar near selected element
   - Full-screen canvas mode

---

## Recommended Libraries

### Vaul (Bottom Sheets/Drawers)
**Install:** `npm install vaul`

Unstyled drawer component for React. Ideal for mobile property panels and menus.

```tsx
import { Drawer } from 'vaul';

<Drawer.Root>
  <Drawer.Trigger>Open</Drawer.Trigger>
  <Drawer.Portal>
    <Drawer.Overlay className="fixed inset-0 bg-black/40" />
    <Drawer.Content className="fixed bottom-0 left-0 right-0 bg-white rounded-t-xl">
      <Drawer.Handle />
      <Drawer.Title>Title</Drawer.Title>
      {/* Content */}
    </Drawer.Content>
  </Drawer.Portal>
</Drawer.Root>
```

**Features:**
- Snap points for partial open states
- Nested drawers support
- Direction prop (left, right, top, bottom)
- Controlled/uncontrolled modes
- Works with Radix UI primitives

### react-swipeable-views (Tab Swiping)
**Install:** `npm install react-swipeable-views react-swipeable-views-utils`

For swipeable tab content on mobile.

```tsx
import SwipeableViews from 'react-swipeable-views';
import { bindKeyboard } from 'react-swipeable-views-utils';

const BindKeyboardSwipeableViews = bindKeyboard(SwipeableViews);

<BindKeyboardSwipeableViews index={activeTabIndex} onChangeIndex={setActiveTabIndex}>
  <div>Tab 1 content</div>
  <div>Tab 2 content</div>
  <div>Tab 3 content</div>
</BindKeyboardSwipeableViews>
```

**Features:**
- Touch-optimized swipe gestures
- Keyboard navigation HOC
- Auto-play HOC for carousels
- Virtualize HOC for large lists
- React Native support (experimental)

### Alternative: Swiper
**Install:** `npm install swiper`

More feature-rich but larger bundle. Good for complex carousels.

```tsx
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';

<Swiper
  modules={[Navigation, Pagination]}
  pagination={{ clickable: true }}
  navigation
>
  <SwiperSlide>Slide 1</SwiperSlide>
  <SwiperSlide>Slide 2</SwiperSlide>
</Swiper>
```

---

## Breakpoint Strategy

Using Tailwind's default breakpoints:

| Breakpoint | Width | UI Behavior |
|------------|-------|-------------|
| Default (mobile) | < 640px | Hamburger menu, bottom action bar, full-screen panels |
| `sm` | >= 640px | Icons + text tabs, sidebar panels return |
| `md` | >= 768px | Most toolbar buttons visible |
| `lg` | >= 1024px | Full desktop layout |
| `xl` | >= 1280px | Extra space utilization |

---

## Helper Hook: useMediaQuery

**File:** `src/react/admin/hooks/useMediaQuery.ts`

```tsx
import { useState, useEffect } from 'react';

export function useMediaQuery(query: string): boolean {
  const [matches, setMatches] = useState(false);

  useEffect(() => {
    const media = window.matchMedia(query);
    setMatches(media.matches);

    const listener = (e: MediaQueryListEvent) => setMatches(e.matches);
    media.addEventListener('change', listener);
    return () => media.removeEventListener('change', listener);
  }, [query]);

  return matches;
}

// Convenience hooks
export function useIsMobile() {
  return useMediaQuery('(max-width: 639px)');
}

export function useIsTablet() {
  return useMediaQuery('(min-width: 640px) and (max-width: 1023px)');
}

export function useIsDesktop() {
  return useMediaQuery('(min-width: 1024px)');
}
```

---

## Testing Checklist

### Phase 1 Verification
- [ ] TabBar scrolls horizontally on mobile (375px)
- [ ] No horizontal overflow on any viewport
- [ ] RightSidebar is full-screen on mobile
- [ ] FloatingPanel is full-screen on mobile
- [ ] Primary actions (Save/Publish) visible on mobile

### Phase 2 Verification
- [ ] Hamburger menu opens correctly
- [ ] Bottom action bar appears on mobile only
- [ ] Vaul drawers animate smoothly
- [ ] Touch targets are >= 44px
- [ ] All actions accessible on mobile

### Device Testing
- [ ] iPhone SE (375x667)
- [ ] iPhone 14 Pro (393x852)
- [ ] iPad Mini (768x1024)
- [ ] iPad Pro (1024x1366)
- [ ] Desktop (1920x1080)

---

## Files to Modify

### Phase 1
1. `src/react/admin/apps/form-builder-v2/components/TabBar.tsx`
2. `src/react/admin/apps/form-builder-v2/components/TopBar.tsx`
3. `src/react/admin/apps/form-builder-v2/components/ui/RightSidebar.tsx`
4. `src/react/admin/apps/form-builder-v2/components/property-panels/FloatingPanel.tsx`
5. `src/react/admin/apps/form-builder-v2/styles/form-builder.css`

### Phase 2
6. `src/react/admin/apps/form-builder-v2/components/MobileMenu.tsx` (new)
7. `src/react/admin/apps/form-builder-v2/components/MobileActionBar.tsx` (new)
8. `src/react/admin/hooks/useMediaQuery.ts` (new)
9. `src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx`

---

## Dependencies to Add

```bash
# Phase 2
npm install vaul

# Optional for Phase 3
npm install react-swipeable-views react-swipeable-views-utils
# OR
npm install swiper
```

---

## Context Manifest

### Key Decisions
- Using Tailwind responsive prefixes (`sm:`, `md:`, `lg:`) for breakpoints
- Vaul chosen for bottom sheets (lightweight, unstyled, Radix-compatible)
- Mobile-first approach: hide on mobile, show on desktop

### Patterns Established
- `useMediaQuery` hook for JavaScript-based responsive logic
- Full-screen modals on mobile instead of positioned panels
- Bottom action bar pattern for primary mobile actions

### Related Files
- TabBar: `src/react/admin/apps/form-builder-v2/components/TabBar.tsx`
- TopBar: `src/react/admin/apps/form-builder-v2/components/TopBar.tsx`
- RightSidebar: `src/react/admin/apps/form-builder-v2/components/ui/RightSidebar.tsx`
- FloatingPanel: `src/react/admin/apps/form-builder-v2/components/property-panels/FloatingPanel.tsx`

---

## Work Log

*(To be filled during implementation)*
