/**
 * Super Forms Builder Testing with Playwright
 * 
 * This script automates the testing of form builder functionality:
 * - Navigate to Super Forms admin
 * - Search for specific form
 * - Open form builder
 * - Verify elements are present
 * - Check settings migration to tabs
 */

const testFormBuilder = async (page, formData) => {
    const results = {
        formId: formData.formId,
        formTitle: formData.formTitle,
        success: false,
        steps: {},
        errors: [],
        elementsFound: 0,
        settingsTabsFound: 0
    };

    try {
        console.log(`\n🔍 Testing Form Builder for: ${formData.formTitle} (ID: ${formData.formId})`);

        // Step 1: Navigate to Super Forms list
        console.log('📋 Step 1: Navigating to Super Forms list...');
        await page.goto('http://localhost:8080/wp-admin/admin.php?page=super_forms');
        await page.waitForLoadState('networkidle');
        results.steps.navigateToForms = true;

        // Step 2: Search for the form
        console.log(`🔎 Step 2: Searching for form "${formData.formTitle}"...`);
        
        // Look for search input or form title in the list
        const searchInput = await page.locator('input[name="s"]').first();
        if (await searchInput.isVisible()) {
            await searchInput.fill(formData.formTitle);
            await page.keyboard.press('Enter');
            await page.waitForTimeout(2000);
        }
        
        // Find the form in the list by title or ID
        const formRow = await page.locator(`tr:has-text("${formData.formTitle}"), tr:has-text("${formData.formId}")`).first();
        
        if (!(await formRow.isVisible())) {
            throw new Error(`Form "${formData.formTitle}" not found in forms list`);
        }
        results.steps.foundForm = true;

        // Step 3: Click Edit to open form builder
        console.log('✏️ Step 3: Opening form builder...');
        const editLink = await formRow.locator('a:has-text("Edit"), .row-title a').first();
        await editLink.click();
        await page.waitForLoadState('networkidle');
        
        // Verify we're on the form builder page
        await page.waitForSelector('.super-create-form', { timeout: 10000 });
        results.steps.openedBuilder = true;

        // Step 4: Click Maximize button to show all elements
        console.log('🔍 Step 4: Maximizing elements view...');
        const maximizeBtn = await page.locator('.super-maximize-elements, .super-elements-maximize, [title*="maximize"], [title*="Maximize"]').first();
        if (await maximizeBtn.isVisible()) {
            await maximizeBtn.click();
            await page.waitForTimeout(1000);
        }
        results.steps.maximizedView = true;

        // Step 5: Count and verify form elements
        console.log('📊 Step 5: Counting form elements...');
        
        // Look for form elements in the builder
        const elements = await page.locator('.super-element, .super-form-element, .super-shortcode').all();
        results.elementsFound = elements.length;
        
        if (results.elementsFound > 0) {
            console.log(`✅ Found ${results.elementsFound} form elements`);
            results.steps.elementsFound = true;
            
            // Check for specific element types
            const elementTypes = [];
            for (const element of elements) {
                const dataTag = await element.getAttribute('data-tag');
                if (dataTag) elementTypes.push(dataTag);
            }
            results.elementTypes = [...new Set(elementTypes)];
            console.log(`📝 Element types found: ${results.elementTypes.join(', ')}`);
        } else {
            console.log('⚠️ No form elements found');
            results.errors.push('No form elements found in builder');
        }

        // Step 6: Check settings tabs migration
        console.log('⚙️ Step 6: Checking settings tabs...');
        
        // Look for the new tab-based settings
        const settingsTabs = await page.locator('.super-tabs .super-tab, .super-settings-tabs .super-tab, .super-tab').all();
        results.settingsTabsFound = settingsTabs.length;
        
        if (results.settingsTabsFound > 0) {
            console.log(`✅ Found ${results.settingsTabsFound} settings tabs`);
            results.steps.settingsTabsFound = true;
            
            // Get tab names
            const tabNames = [];
            for (const tab of settingsTabs) {
                const tabText = await tab.textContent();
                if (tabText) tabNames.push(tabText.trim());
            }
            results.settingsTabNames = tabNames;
            console.log(`📋 Settings tabs: ${tabNames.join(', ')}`);
        } else {
            console.log('⚠️ No settings tabs found');
            results.errors.push('No settings tabs found');
        }

        // Step 7: Test Preview Mode
        console.log('👀 Step 7: Testing Preview mode...');
        const previewBtn = await page.locator('.super-preview, [data-action="preview"], .super-button-preview').first();
        if (await previewBtn.isVisible()) {
            await previewBtn.click();
            await page.waitForTimeout(2000);
            
            // Check if preview opened (new tab/window or modal)
            const previewVisible = await page.locator('.super-preview-modal, .super-form').isVisible();
            if (previewVisible) {
                console.log('✅ Preview mode working');
                results.steps.previewWorking = true;
            }
        } else {
            console.log('⚠️ Preview button not found');
        }

        // Overall success check
        results.success = results.steps.navigateToForms && 
                         results.steps.foundForm && 
                         results.steps.openedBuilder && 
                         results.elementsFound > 0;

        if (results.success) {
            console.log('✅ Form builder test PASSED');
        } else {
            console.log('❌ Form builder test FAILED');
        }

    } catch (error) {
        console.error('❌ Form builder test ERROR:', error.message);
        results.errors.push(error.message);
        results.success = false;
    }

    return results;
};

// Export for use in test runner
if (typeof module !== 'undefined') {
    module.exports = { testFormBuilder };
}

// CLI usage example
const runTest = async () => {
    const { chromium } = require('playwright');
    
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    // Login to WordPress
    await page.goto('http://localhost:8080/wp-admin');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'admin');
    await page.click('#wp-submit');
    await page.waitForLoadState('networkidle');

    // Test specific form
    const testForm = {
        formId: '71883',
        formTitle: 'Loan Pre-Qualification'
    };

    const results = await testFormBuilder(page, testForm);
    console.log('\n📊 Test Results:', JSON.stringify(results, null, 2));

    await browser.close();
};

// Run if called directly
if (require.main === module) {
    runTest().catch(console.error);
}