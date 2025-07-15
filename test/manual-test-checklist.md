# Super Forms Manual Test Checklist

## Pre-Test Setup
- [ ] Changes synced to webserver via `sync-to-webserver.sh`
- [ ] Browser cache cleared (Ctrl+Shift+R)
- [ ] Developer console open (F12)

## Test 1: JavaScript Syntax Error Fix
**Objective**: Verify the css-plugin.js syntax error is fixed

1. [ ] Navigate to any form using the Popups add-on
2. [ ] Open browser console
3. [ ] Check for error: `Uncaught SyntaxError: Unexpected token '='`
4. [ ] **Expected**: No syntax errors in console

## Test 2: Infinite Loop Fix
**Objective**: Verify conditional logic doesn't cause infinite loops

1. [ ] Open a form with conditional logic in the builder
2. [ ] Switch between tabs in the settings panel
3. [ ] Monitor console for repeated messages
4. [ ] **Expected**: 
   - No repeated "Looking for nested field" messages
   - No browser freeze
   - Smooth tab transitions

## Test 3: Performance Improvements
**Objective**: Verify debouncing and optimization work

1. [ ] Open a complex form with many fields
2. [ ] Rapidly switch between tabs
3. [ ] Change field values quickly
4. [ ] **Expected**:
   - No lag or stuttering
   - Console shows debounced updates (not instant)
   - Form loads in < 3 seconds

## Test 4: Conditional Logic
**Objective**: Verify conditional logic evaluates correctly

1. [ ] Create/edit a form with conditional fields
2. [ ] Set up conditions like "Show field B when field A = X"
3. [ ] Test the conditions by changing field values
4. [ ] **Expected**:
   - Fields show/hide correctly
   - No console errors
   - Smooth transitions

## Test 5: Email Settings (if applicable)
**Objective**: Verify email settings migration

1. [ ] Open form 69852 (or any form with email reminders)
2. [ ] Check if email settings appear in triggers
3. [ ] **Expected**: Email settings migrated properly

## Console Output Verification

### Good Console Output
```
✓ No errors
✓ Occasional debounced updates
✓ Clean loading messages
```

### Bad Console Output
```
✗ Uncaught SyntaxError
✗ Maximum call stack exceeded
✗ Repeated "Looking for nested field" messages
✗ Performance warnings
```

## Results Recording

For each test, record:
- **Test Name**: _________________
- **Pass/Fail**: _________________
- **Console Errors**: _________________
- **Performance Notes**: _________________
- **Additional Comments**: _________________

## Final Verification

- [ ] All syntax errors resolved
- [ ] No infinite loops detected
- [ ] Performance acceptable
- [ ] Conditional logic working
- [ ] No regression issues

## Sign-off
- **Tester**: _________________
- **Date**: _________________
- **Overall Result**: Pass / Fail
- **Ready for Production**: Yes / No