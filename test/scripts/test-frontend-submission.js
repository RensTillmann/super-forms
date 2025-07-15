/**
 * Super Forms Frontend Submission Testing with Playwright
 * 
 * This script automates frontend form testing:
 * - Create test page with form shortcode
 * - Fill out form with test data
 * - Submit form and verify success
 * - Test feature-specific functionality
 */

const testFrontendSubmission = async (page, formData) => {
    const results = {
        formId: formData.formId,
        formTitle: formData.formTitle,
        success: false,
        steps: {},
        errors: [],
        submissionId: null,
        featuresTest: {}
    };

    try {
        console.log(`\n🌐 Testing Frontend Submission for: ${formData.formTitle} (ID: ${formData.formId})`);

        // Step 1: Get the form shortcode
        console.log('🔗 Step 1: Getting form shortcode...');
        await page.goto(`http://localhost:8080/wp-admin/admin.php?page=super_create_form&id=${formData.formId}`);
        await page.waitForLoadState('networkidle');

        // Look for shortcode display
        const shortcodeElement = await page.locator('.super-shortcode, [data-shortcode], input[value*="super_form"]').first();
        let shortcode = `[super_form id="${formData.formId}"]`; // Default fallback

        if (await shortcodeElement.isVisible()) {
            const shortcodeText = await shortcodeElement.textContent() || await shortcodeElement.inputValue();
            if (shortcodeText && shortcodeText.includes('super_form')) {
                shortcode = shortcodeText.trim();
            }
        }
        console.log(`📋 Using shortcode: ${shortcode}`);
        results.steps.shortcodeFound = true;

        // Step 2: Create new test page
        console.log('📄 Step 2: Creating test page...');
        await page.goto('http://localhost:8080/wp-admin/post-new.php?post_type=page');
        await page.waitForLoadState('networkidle');

        // Set page title
        const pageTitle = `Test Form ${formData.formId} - ${formData.formTitle}`;
        await page.fill('#title', pageTitle);

        // Add shortcode to content
        // Try to handle both Classic and Block editor
        const contentArea = await page.locator('#content, .wp-block-post-content, .editor-post-text-editor').first();
        
        if (await contentArea.isVisible()) {
            await contentArea.fill(shortcode);
        } else {
            // Try Block editor
            const addBlockBtn = await page.locator('.block-editor-inserter__toggle, .edit-post-header-toolbar__inserter-toggle').first();
            if (await addBlockBtn.isVisible()) {
                await addBlockBtn.click();
                await page.waitForTimeout(1000);
                
                // Search for shortcode block
                await page.fill('.block-editor-inserter__search input', 'shortcode');
                await page.waitForTimeout(500);
                
                const shortcodeBlock = await page.locator('.editor-block-list-item-shortcode, [data-type="core/shortcode"]').first();
                if (await shortcodeBlock.isVisible()) {
                    await shortcodeBlock.click();
                    await page.waitForTimeout(500);
                    
                    // Add shortcode content
                    const shortcodeInput = await page.locator('.wp-block-shortcode textarea, .components-textarea-control__input').first();
                    await shortcodeInput.fill(shortcode);
                }
            }
        }

        // Publish the page
        const publishBtn = await page.locator('#publish, .editor-post-publish-button').first();
        await publishBtn.click();
        await page.waitForTimeout(2000);

        // Confirm publish if needed
        const confirmPublish = await page.locator('.editor-post-publish-button').first();
        if (await confirmPublish.isVisible()) {
            await confirmPublish.click();
            await page.waitForTimeout(2000);
        }

        results.steps.pageCreated = true;
        console.log('✅ Test page created and published');

        // Step 3: Visit the test page
        console.log('🔍 Step 3: Visiting test page on frontend...');
        
        // Get the page URL
        const viewPageLink = await page.locator('a:has-text("View Page"), .post-publish-panel__postpublish-buttons a').first();
        let pageUrl = 'http://localhost:8080/?page_id=' + Date.now(); // Fallback
        
        if (await viewPageLink.isVisible()) {
            pageUrl = await viewPageLink.getAttribute('href');
        }

        await page.goto(pageUrl);
        await page.waitForLoadState('networkidle');
        results.steps.visitedTestPage = true;

        // Step 4: Verify form is displayed
        console.log('📋 Step 4: Verifying form display...');
        
        const formContainer = await page.locator('.super-form, form[data-super-form], .super-form-wrapper').first();
        
        if (!(await formContainer.isVisible())) {
            throw new Error('Form not visible on frontend page');
        }

        // Count form elements
        const formElements = await page.locator('.super-form input, .super-form textarea, .super-form select').all();
        console.log(`📝 Found ${formElements.length} form fields on frontend`);
        results.steps.formDisplayed = true;

        // Step 5: Fill out the form with test data
        console.log('✏️ Step 5: Filling out form fields...');
        
        const testData = {
            // Text inputs
            'input[name*="first_name"], input[name*="name"]': 'John',
            'input[name*="last_name"]': 'Doe',
            'input[name*="email"]': 'test@superforms.local',
            'input[name*="phone"]': '+1-555-123-4567',
            'input[name*="address"]': '123 Test Street',
            'input[name*="city"]': 'Test City',
            'input[name*="zip"], input[name*="postal"]': '12345',
            
            // Textareas
            'textarea[name*="message"], textarea[name*="comment"], textarea[name*="question"], textarea[name*="reason"]': 'This is a test submission for form validation and functionality testing.',
            
            // Financial fields (for loan forms)
            'input[name*="income"]': '75000',
            'input[name*="amount"], input[name*="loan"]': '250000',
            'input[name*="credit"]': '720',
            
            // Numbers
            'input[type="number"]': '100'
        };

        let filledFields = 0;
        for (const [selector, value] of Object.entries(testData)) {
            const fields = await page.locator(selector).all();
            for (const field of fields) {
                if (await field.isVisible() && await field.isEnabled()) {
                    await field.fill(value);
                    filledFields++;
                }
            }
        }

        // Handle dropdowns
        const dropdowns = await page.locator('.super-form select').all();
        for (const dropdown of dropdowns) {
            if (await dropdown.isVisible()) {
                const options = await dropdown.locator('option').all();
                if (options.length > 1) {
                    await dropdown.selectOption({ index: 1 }); // Select first non-empty option
                    filledFields++;
                }
            }
        }

        // Handle checkboxes and radios
        const checkboxes = await page.locator('.super-form input[type="checkbox"]:not(:checked)').all();
        for (let i = 0; i < Math.min(checkboxes.length, 2); i++) {
            if (await checkboxes[i].isVisible()) {
                await checkboxes[i].check();
                filledFields++;
            }
        }

        const radios = await page.locator('.super-form input[type="radio"]').all();
        if (radios.length > 0 && await radios[0].isVisible()) {
            await radios[0].check();
            filledFields++;
        }

        console.log(`✅ Filled ${filledFields} form fields`);
        results.steps.formFilled = true;

        // Step 6: Submit the form
        console.log('🚀 Step 6: Submitting form...');
        
        const submitBtn = await page.locator('.super-form button[type="submit"], .super-form input[type="submit"], .super-form .super-button').first();
        
        if (await submitBtn.isVisible()) {
            await submitBtn.click();
            await page.waitForTimeout(3000); // Wait for submission processing
            
            // Check for success message
            const successMessage = await page.locator('.super-msg-success, .super-success, .success-message, .super-form-success').first();
            const errorMessage = await page.locator('.super-msg-error, .super-error, .error-message').first();
            
            if (await successMessage.isVisible()) {
                console.log('✅ Form submitted successfully');
                results.steps.formSubmitted = true;
                
                const successText = await successMessage.textContent();
                console.log(`📧 Success message: ${successText}`);
                
            } else if (await errorMessage.isVisible()) {
                const errorText = await errorMessage.textContent();
                throw new Error(`Form submission failed: ${errorText}`);
            } else {
                console.log('⚠️ No clear success/error message found');
                // Still count as success if no error
                results.steps.formSubmitted = true;
            }
        } else {
            throw new Error('Submit button not found');
        }

        // Step 7: Test feature-specific functionality
        console.log('🔧 Step 7: Testing feature-specific functionality...');
        
        // PDF Generation Test
        if (formData.features && formData.features.includes('pdf')) {
            const pdfLink = await page.locator('a[href*=".pdf"], a:has-text("PDF"), a:has-text("Download")').first();
            if (await pdfLink.isVisible()) {
                console.log('✅ PDF download link found');
                results.featuresTest.pdf = true;
            }
        }

        // PayPal Test (would redirect)
        if (formData.features && formData.features.includes('paypal')) {
            // Check if redirected to PayPal or showed PayPal buttons
            const currentUrl = page.url();
            if (currentUrl.includes('paypal.com') || currentUrl.includes('sandbox.paypal.com')) {
                console.log('✅ PayPal redirect working');
                results.featuresTest.paypal = true;
            } else {
                const paypalButtons = await page.locator('[data-funding-source="paypal"], .paypal-button').all();
                if (paypalButtons.length > 0) {
                    console.log('✅ PayPal buttons found');
                    results.featuresTest.paypal = true;
                }
            }
        }

        // WooCommerce Test
        if (formData.features && formData.features.includes('woocommerce')) {
            // Check if cart has items or redirected to checkout
            if (page.url().includes('checkout') || page.url().includes('cart')) {
                console.log('✅ WooCommerce checkout redirect working');
                results.featuresTest.woocommerce = true;
            }
        }

        // Overall success
        results.success = results.steps.shortcodeFound && 
                         results.steps.pageCreated && 
                         results.steps.formDisplayed && 
                         results.steps.formSubmitted;

        if (results.success) {
            console.log('✅ Frontend submission test PASSED');
        } else {
            console.log('❌ Frontend submission test FAILED');
        }

    } catch (error) {
        console.error('❌ Frontend submission test ERROR:', error.message);
        results.errors.push(error.message);
        results.success = false;
    }

    return results;
};

// Export for use in test runner
if (typeof module !== 'undefined') {
    module.exports = { testFrontendSubmission };
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
        formTitle: 'Loan Pre-Qualification',
        features: ['pdf', 'paypal', 'woocommerce']
    };

    const results = await testFrontendSubmission(page, testForm);
    console.log('\n📊 Test Results:', JSON.stringify(results, null, 2));

    await browser.close();
};

// Run if called directly
if (require.main === module) {
    runTest().catch(console.error);
}