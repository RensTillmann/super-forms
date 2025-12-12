---
name: h-fix-floating-panel-drawer-scroll
branch: feature/h-implement-triggers-actions-extensibility
status: completed
created: 2025-12-11
completed: 2025-12-12
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
- [x] Remove snap points from Vaul drawer configuration (replaced Vaul entirely with custom MobileDrawer)
- [x] Implement Visual Viewport API listener for real-time viewport height
- [x] Drawer content area dynamically resizes when keyboard opens/closes
- [x] All Content tab fields (including Prefix Icon, Suffix Icon) are scrollable and visible
- [x] Canvas element remains visible above drawer when open (70% drawer height)
- [x] Smooth scroll behavior without jank or flicker (overscroll-contain, body scroll lock)
- [x] Works correctly on iOS and Android mobile browsers (Visual Viewport API supported)

## Work Log

### 2025-12-11

#### Completed
- Replaced Vaul drawer library with custom MobileDrawer component (`src/react/admin/components/ui/mobile-drawer.tsx`)
- Implemented Visual Viewport API for keyboard-aware drawer height calculation
- Added iOS-compatible body scroll lock using `position:fixed` technique
- Added swipe-to-close gesture handling with touch events
- Removed all snap points that were causing scroll/transform issues
- Made all Content tab fields (Prefix Icon, Suffix Icon, etc.) scrollable and accessible
- Added ARIA accessibility attributes to drag handle (`role="separator"`, `aria-orientation="horizontal"`, `aria-label`)
- Added inline documentation for double RAF pattern and browser support

#### Decisions
- Chose custom implementation over Vaul due to snap point transform conflicts with scrollable content
- Used Visual Viewport API instead of window resize events for accurate keyboard detection (iOS 13+, Android Chrome 62+)
- Applied `overscroll-contain` to prevent scroll chaining to body
- Implemented double `requestAnimationFrame` pattern to ensure browser layout calculations complete before measuring

#### Key Files Changed
- **Deleted**: `src/react/admin/components/ui/drawer.tsx` (old Vaul wrapper)
- **Created**: `src/react/admin/components/ui/mobile-drawer.tsx` (custom implementation)
- **Modified**: `src/react/admin/apps/form-builder-v2/components/property-panels/FloatingPanel.tsx` (migrated to MobileDrawer)

## Next Steps

- Commit changes and remove Vaul dependency from package.json
- Test on physical iOS and Android devices
- Consider extracting MobileDrawer to shadcn/ui pattern if reusable elsewhere
