---
name: m-fix-mobile-keyboard-scroll
branch: feature/h-implement-triggers-actions-extensibility
status: in-progress
created: 2025-12-21
---

# Fix Mobile Keyboard Scroll Into View

## Problem/Goal
When mobile keyboard opens on iOS Safari, the selected form element should remain visible and the properties tray should stick to the top of the keyboard. Currently:
1. The properties tray is NOT sticking to the top of the keyboard
2. The form element becomes invisible when keyboard opens

## Success Criteria
- [ ] Properties tray sticks to top of keyboard when keyboard opens
- [ ] Selected form element remains visible when keyboard opens
- [ ] Works on various iOS device screen sizes
- [ ] Debug logging removed after fix confirmed

## Context Manifest

### Key Files
- `/src/react/admin/apps/form-builder-v2/hooks/useKeyboardState.ts` - Keyboard detection state machine
- `/src/react/admin/apps/form-builder-v2/hooks/useScrollIntoView.ts` - Scroll/transform logic
- `/src/react/admin/apps/form-builder-v2/hooks/useElementVisibility.ts` - Visibility detection
- `/src/react/admin/apps/form-builder-v2/components/ui/mobile/PropertiesBottomTray.tsx` - Tray component

### iOS Safari Viewport Model
- **Layout viewport**: Full iframe size (e.g., 753px), doesn't shrink with keyboard
- **Visual viewport**: What user actually sees, shrinks when keyboard opens
- **visualViewport.offsetTop**: How much visual viewport shifted from layout viewport origin
- **visualViewport.height**: Height of visible area (shrinks with keyboard)
- Key insight: `getBoundingClientRect()` returns coords relative to LAYOUT viewport, not visual viewport

### Current State
Debug logging enabled in all three hooks.

**What's Working:**
- Keyboard state machine transitions: idle → opening → open
- `onKeyboardOpen` callback fires correctly
- Debounce prevents compound transforms
- Visible area calculation (visibleTop=0, visibleBottom=trayTop)

**What's NOT Working:**

1. **Tray Positioning** - Uses `transform: translateY(-${keyboard.offset}px)` but NOT sticking to keyboard top
   - Current: `keyboardHeight = parentInnerHeight - visualViewport.height`
   - Observed: offset 292-340px (fluctuating)
   - Hypothesis: May not account for iOS Safari toolbar/safe-area

2. **Element Visibility** - Code says "visible" but element NOT actually visible
   - visibleTop=0, visibleBottom=trayTop
   - Hypothesis: `parentViewportOffset` DOES affect what's visible, needs to be factored back differently

## User Notes
- Test URL: `https://f4d.nl/dev/wp-admin/admin.php?page=super_form_v2`
- Login token in CLAUDE.md
- Test on iOS Safari with mobile keyboard

## Work Log
- [2025-12-21] Initial debugging session:
  - Fixed keyboard state machine (useEffect dependency on `state` killed rAF loop)
  - Fixed offset calculation formula mismatch between hooks
  - Added debounce to prevent compound transforms
  - Changed tray to use keyboard HEIGHT instead of offsetTop
  - Still not working - tray position and visibility issues remain
