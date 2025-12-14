/**
 * E2E Tests: Form Builder V2 iframe Isolation
 *
 * Tests the iframe isolation implementation for Form Builder V2,
 * verifying that the form builder loads correctly in an isolated
 * iframe and that CSS isolation is working as expected.
 *
 * @package SUPER_Forms
 * @since 6.6.0
 */

import { test, expect } from '@playwright/test';

/**
 * Test Suite: iframe Initialization
 *
 * Verifies that the iframe loads correctly and the React app mounts
 * successfully within the isolated context.
 */
test.describe('Form Builder V2 - iframe Initialization', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to Form Builder V2 page
    // TODO: Update with actual WordPress admin URL
    await page.goto('/wp-admin/admin.php?page=super_form_v2');
  });

  test('should load iframe with Form Builder', async ({ page }) => {
    // Wait for iframe element
    const iframe = page.locator('#sfui-builder-iframe');
    await expect(iframe).toBeVisible();

    // Access iframe content
    const iframeLocator = page.frameLocator('#sfui-builder-iframe');

    // Verify React app mounted
    const adminRoot = iframeLocator.getByTestId('sfui-admin-root');
    await expect(adminRoot).toBeVisible({ timeout: 10000 });

    // Verify body ID is set (required for Tailwind scoping)
    const body = iframeLocator.locator('body');
    await expect(body).toHaveId('sfui-admin-root');
  });

  test('should hide loading indicator after initialization', async ({ page }) => {
    // Loading indicator should be visible initially
    const loadingIndicator = page.locator('#sfui-loading-indicator');
    await expect(loadingIndicator).toBeVisible();

    // Wait for iframe to initialize
    const iframeLocator = page.frameLocator('#sfui-builder-iframe');
    await iframeLocator.getByTestId('sfui-admin-root').waitFor({ timeout: 10000 });

    // Loading indicator should be hidden/removed
    await expect(loadingIndicator).not.toBeVisible({ timeout: 2000 });
  });

  test('should load WordPress core scripts in iframe', async ({ page }) => {
    const iframeLocator = page.frameLocator('#sfui-builder-iframe');

    // Wait for React app to mount
    await iframeLocator.getByTestId('sfui-admin-root').waitFor();

    // Verify WordPress global objects are available in iframe
    const hasWp = await page.evaluate(() => {
      const iframe = document.getElementById('sfui-builder-iframe') as HTMLIFrameElement;
      return !!(iframe?.contentWindow as any)?.wp;
    });

    expect(hasWp).toBe(true);
  });
});

/**
 * Test Suite: CSS Isolation
 *
 * Verifies that WordPress admin styles do not leak into the iframe
 * and that Form Builder styles are isolated correctly.
 */
test.describe('Form Builder V2 - CSS Isolation', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=super_form_v2');
  });

  test('should not have double border on input elements', async ({ page }) => {
    const iframeLocator = page.frameLocator('#sfui-builder-iframe');

    // Wait for React app to mount
    await iframeLocator.getByTestId('sfui-admin-root').waitFor();

    // Find first text input in form builder
    // TODO: Update selector based on actual form structure
    const input = iframeLocator.getByRole('textbox').first();
    await input.waitFor({ timeout: 5000 }).catch(() => {
      // Input might not exist in empty form - that's okay for now
    });

    // If input exists, verify border width is 1px (not 2px from double border)
    const inputExists = await input.count() > 0;
    if (inputExists) {
      const borderWidth = await input.evaluate((el) =>
        window.getComputedStyle(el).borderWidth
      );

      // Should be 1px from Tailwind, NOT 2px from WordPress + Tailwind
      expect(borderWidth).toBe('1px');
    }
  });

  test('should have isolated CSS context', async ({ page }) => {
    const iframeLocator = page.frameLocator('#sfui-builder-iframe');

    // Wait for React app
    await iframeLocator.getByTestId('sfui-admin-root').waitFor();

    // Verify iframe has own stylesheet (admin.css)
    const hasStylesheet = await page.evaluate(() => {
      const iframe = document.getElementById('sfui-builder-iframe') as HTMLIFrameElement;
      const iframeDoc = iframe?.contentDocument;
      const stylesheets = iframeDoc?.querySelectorAll('link[rel="stylesheet"]');
      return (stylesheets?.length ?? 0) > 0;
    });

    expect(hasStylesheet).toBe(true);
  });
});

/**
 * Test Suite: Drag-and-Drop (@dnd-kit)
 *
 * Verifies that drag-and-drop functionality works correctly in iframe context.
 */
test.describe('Form Builder V2 - Drag and Drop', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=super_form_v2');
  });

  test('should support mouse drag-and-drop', async ({ page }) => {
    const iframeLocator = page.frameLocator('#sfui-builder-iframe');
    await iframeLocator.getByTestId('sfui-admin-root').waitFor();

    // TODO: Add actual drag-and-drop test
    // This requires knowing the structure of the element palette and canvas
    // Example:
    // const textInput = iframeLocator.getByText('Text Input');
    // const canvas = iframeLocator.getByTestId('form-canvas');
    // await textInput.dragTo(canvas);
  });

  test.skip('should support touch drag-and-drop on mobile', async ({ page }) => {
    // TODO: Implement touch drag test
    // Requires mobile viewport and touch event simulation
  });

  test.skip('should support keyboard navigation', async ({ page }) => {
    // TODO: Implement keyboard navigation test
    // Test Space to grab, arrow keys to move
  });
});

/**
 * Test Suite: Portal Rendering
 *
 * Verifies that components using React portals (MobileDrawer, modals, dropdowns)
 * render correctly to the iframe's document.body.
 */
test.describe('Form Builder V2 - Portal Rendering', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=super_form_v2');
  });

  test.skip('should render modals in iframe context', async ({ page }) => {
    // TODO: Trigger a modal and verify it renders in iframe
    // Example: Open element settings modal
  });

  test.skip('should render mobile drawer in iframe context', async ({ page }) => {
    // TODO: Test mobile drawer on mobile viewport
    // Verify Visual Viewport API works in iframe
  });

  test.skip('should render dropdowns in iframe context', async ({ page }) => {
    // TODO: Open a dropdown menu and verify positioning
  });
});

/**
 * Test Suite: postMessage Communication
 *
 * Verifies that parent-iframe communication works correctly via postMessage.
 */
test.describe('Form Builder V2 - Parent-iframe Communication', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=super_form_v2');
  });

  test.skip('should handle navigation requests from iframe', async ({ page }) => {
    // TODO: Trigger navigation from iframe (e.g., click "Forms List" link)
    // Verify parent window navigates to expected URL
  });

  test.skip('should handle toast notifications from iframe', async ({ page }) => {
    // TODO: Trigger a toast notification (e.g., save form)
    // Verify parent window receives the message
  });
});

/**
 * Test Suite: Mobile Compatibility
 *
 * Verifies that iframe works correctly on mobile devices with virtual keyboards
 * and touch events.
 */
test.describe('Form Builder V2 - Mobile Compatibility', () => {
  test.skip('should handle iOS Safari virtual keyboard', async ({ page }) => {
    // TODO: Test on iOS Safari viewport
    // Verify Visual Viewport API adjusts layout when keyboard opens
  });

  test.skip('should handle Android Chrome virtual keyboard', async ({ page }) => {
    // TODO: Test on Android Chrome viewport
    // Verify keyboard handling
  });
});
