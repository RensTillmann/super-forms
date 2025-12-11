---
name: h-fix-floating-panel-drawer-scroll
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-12-11
---

# Fix FloatingPanel Mobile Drawer Scroll & Keyboard Handling

## Problem/Goal

The FloatingPanel mobile drawer (using Vaul) has scroll and keyboard issues caused by snap points:

1. **Snap point transform issue**: Vaul uses `translateY` transforms for snap points, which causes content to extend below the viewport. When drawer is at 70% snap point, Vaul adds `translateY(199px)`, pushing content 199px below visible area.

2. **Content unreachable**: Fields like "Prefix Icon" and "Suffix Icon" at the bottom of the Content tab cannot be scrolled to because they're positioned below the viewport.

3. **Keyboard conflicts**: When mobile keyboard opens, viewport height changes but Vaul's snap point calculations don't adapt properly, causing layout issues.

**Root Cause**: Vaul's snap point system wasn't designed for scrollable content at each snap point - it uses viewport-relative transforms that conflict with dynamic content heights.

**Solution**: Remove snap points entirely and use Visual Viewport API for keyboard-aware height calculation.

## Success Criteria
- [ ] Remove snap points from Vaul drawer configuration
- [ ] Implement Visual Viewport API listener for real-time viewport height
- [ ] Drawer content area dynamically resizes when keyboard opens/closes
- [ ] All Content tab fields (including Prefix Icon, Suffix Icon) are scrollable and visible
- [ ] Canvas element remains visible above drawer when open
- [ ] Smooth scroll behavior without jank or flicker
- [ ] Works correctly on iOS and Android mobile browsers

## Context Manifest
<!-- Added by context-gathering agent -->

## Technical Approach

### 1. Remove Snap Points
```tsx
// Before
<Drawer.Root
  snapPoints={[0.4, 0.7]}
  activeSnapPoint={snapPoints[1]}
  ...
>

// After
<Drawer.Root
  // No snapPoints - simple slide-up drawer
  ...
>
```

### 2. Visual Viewport API Integration
```tsx
const [contentHeight, setContentHeight] = useState(0);

useEffect(() => {
  const updateHeight = () => {
    const vv = window.visualViewport;
    const viewportHeight = vv ? vv.height : window.innerHeight;

    // Calculate available height for drawer content
    // Account for header (~50px) and nav (~44px)
    const headerNavHeight = 94;
    const targetDrawerHeight = viewportHeight * 0.7;
    setContentHeight(targetDrawerHeight - headerNavHeight);
  };

  updateHeight();

  // Subscribe to visual viewport changes (keyboard open/close)
  window.visualViewport?.addEventListener('resize', updateHeight);
  window.visualViewport?.addEventListener('scroll', updateHeight);

  return () => {
    window.visualViewport?.removeEventListener('resize', updateHeight);
    window.visualViewport?.removeEventListener('scroll', updateHeight);
  };
}, []);
```

### 3. Dynamic Content Height
```tsx
<div
  ref={contentRef}
  style={{ maxHeight: contentHeight }}
  className="overflow-y-auto overscroll-contain"
>
  {children}
</div>
```

## User Notes
- Stay on current branch: `feature/h-implement-triggers-actions-extensibility`
- Research found that Vaul GitHub issues #579, #543, #321 document these exact problems
- Visual Viewport API is well-supported (iOS 13+, Android Chrome 62+)

## Work Log
<!-- Updated as work progresses -->
- [2025-12-11] Created task after debugging drawer scroll issues
- [2025-12-11] Identified root cause: Vaul snap point transforms
- [2025-12-11] Researched alternatives, decided on snap-point-free approach with Visual Viewport API
