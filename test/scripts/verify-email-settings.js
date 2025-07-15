/**
 * Email Settings Migration Verification Script
 * 
 * This script specifically tests the migration of email settings from legacy format
 * to the new "Emails" tab in the form builder interface.
 */

const { chromium } = require('playwright');

const verifyEmailSettings = async (formId, expectedEmailData) => {
    const results = {
        formId: formId,
        success: false,
        steps: {},
        errors: [],
        emailSettings: {
            adminEmail: {
                enabled: null,
                recipients: null,
                subject: null
            },
            confirmationEmail: {
                enabled: null,
                subject: null,
                body: null
            }
        }
    };

    let browser, page;

    try {
        console.log(`\n📧 Verifying Email Settings Migration for Form ${formId}`);
        console.log('='.repeat(60));

        // Launch browser
        browser = await chromium.launch({ 
            headless: false,
            slowMo: 500 // Slow down for better observation
        });
        const context = await browser.newContext();
        page = await context.newPage();

        // Step 1: Login to WordPress
        console.log('🔐 Step 1: Logging into WordPress...');
        await page.goto('http://localhost:8080/wp-admin');
        await page.waitForLoadState('networkidle');

        const loginForm = await page.locator('#loginform').isVisible();
        if (loginForm) {
            await page.fill('#user_login', 'admin');
            await page.fill('#user_pass', 'admin');
            await page.click('#wp-submit');
            await page.waitForLoadState('networkidle');
        }
        
        const dashboard = await page.locator('#wpadminbar').isVisible();
        if (!dashboard) {
            throw new Error('WordPress login failed');
        }
        console.log('✅ WordPress login successful');
        results.steps.loginSuccess = true;

        // Step 2: Navigate directly to form builder
        console.log(`🏗️ Step 2: Opening form builder for Form ${formId}...`);
        await page.goto(`http://localhost:8080/wp-admin/admin.php?page=super_create_form&id=${formId}`);
        await page.waitForLoadState('networkidle');
        
        // Wait for form builder to load
        await page.waitForSelector('.super-create-form, .super-form-builder', { timeout: 10000 });
        console.log('✅ Form builder loaded');
        results.steps.builderLoaded = true;

        // Step 3: First, count elements to complete pending task
        console.log('📊 Step 3: Counting form elements...');
        const elements = await page.locator('.super-element, .super-form-element, .super-shortcode').all();
        const elementsCount = elements.length;
        console.log(`📝 Found ${elementsCount} form elements in builder`);
        results.elementsFound = elementsCount;

        // Step 4: Locate and click the "Emails" tab
        console.log('📧 Step 4: Navigating to Emails tab...');
        
        // Look for various possible selectors for the Emails tab
        const emailTabSelectors = [
            '.super-tab:has-text("Emails")',
            '.super-tab:has-text("Email")', 
            '.super-settings-tab:has-text("Emails")',
            '.super-settings-tab:has-text("Email")',
            '[data-tab="emails"]',
            '[data-tab="email"]',
            'a:has-text("Emails")',
            'a:has-text("Email")'
        ];

        let emailTab = null;
        for (const selector of emailTabSelectors) {
            emailTab = await page.locator(selector).first();
            if (await emailTab.isVisible()) {
                console.log(`✅ Found Emails tab with selector: ${selector}`);
                break;
            }
        }

        if (!emailTab || !(await emailTab.isVisible())) {
            console.log('🔍 No Emails tab found, checking available tabs...');
            const allTabs = await page.locator('.super-tab, .super-settings-tab, .super-tabs a').all();
            const tabTexts = [];
            for (const tab of allTabs) {
                const text = await tab.textContent();
                if (text) tabTexts.push(text.trim());
            }
            console.log(`📋 Available tabs: ${tabTexts.join(', ')}`);
            throw new Error('Emails tab not found in form builder');
        }

        // Click the Emails tab
        await emailTab.click();
        await page.waitForTimeout(1000);
        console.log('✅ Clicked Emails tab');
        results.steps.emailsTabClicked = true;

        // Step 5: Debug - examine what's in the Emails tab
        console.log('🔍 Step 5: Examining Emails tab content...');
        
        // Wait for tab content to load
        await page.waitForTimeout(2000);
        
        // Try different selectors for the tab content
        const tabContentSelectors = [
            '.super-tab-content[data-tab="emails"] input, .super-tab-content[data-tab="emails"] textarea, .super-tab-content[data-tab="emails"] select',
            '.super-tabs-content .super-tab-content:visible input, .super-tabs-content .super-tab-content:visible textarea, .super-tabs-content .super-tab-content:visible select',
            '.super-settings input, .super-settings textarea, .super-settings select',
            'input[name*="email"], textarea[name*="email"], select[name*="email"]'
        ];

        let emailInputs = [];
        for (const selector of tabContentSelectors) {
            emailInputs = await page.locator(selector).all();
            console.log(`📝 Trying selector "${selector}": Found ${emailInputs.length} elements`);
            if (emailInputs.length > 0) break;
        }
        
        for (let i = 0; i < Math.min(emailInputs.length, 15); i++) { // Show more for debugging
            try {
                const input = emailInputs[i];
                const name = await input.getAttribute('name') || '';
                const id = await input.getAttribute('id') || '';
                const type = await input.getAttribute('type') || '';
                const value = await input.inputValue() || '';
                const placeholder = await input.getAttribute('placeholder') || '';
                console.log(`  ${i+1}. Name: "${name}", ID: "${id}", Type: "${type}", Value: "${value}", Placeholder: "${placeholder}"`);
            } catch (e) {
                console.log(`  ${i+1}. Error reading element: ${e.message}`);
            }
        }

        // Step 6: Verify admin email settings with broader selectors
        console.log('👨‍💼 Step 6: Checking admin email settings...');
        
        // Look for admin email enabled checkbox - broader search
        const adminEmailSelectors = [
            'input[name*="admin_email"]',
            'input[name*="email_admin"]', 
            'input[name*="send_admin"]',
            '[data-setting*="admin_email"]',
            'input[type="checkbox"][name*="email"]'
        ];

        for (const selector of adminEmailSelectors) {
            const element = await page.locator(selector).first();
            if (await element.isVisible()) {
                const name = await element.getAttribute('name');
                const checked = await element.isChecked();
                console.log(`📧 Found admin email checkbox: ${name} = ${checked}`);
                if (name && (name.includes('admin') || name.includes('send'))) {
                    results.emailSettings.adminEmail.enabled = checked;
                    break;
                }
            }
        }

        // Look for admin email recipients - broader search
        const recipientSelectors = [
            'input[name*="admin_email_to"]',
            'input[name*="email_to"]',
            'textarea[name*="admin_email"]',
            'input[name*="to"]',
            '[data-setting*="admin_email_to"]'
        ];

        for (const selector of recipientSelectors) {
            const element = await page.locator(selector).first();
            if (await element.isVisible()) {
                const name = await element.getAttribute('name');
                const value = await element.inputValue();
                console.log(`📧 Found admin email recipients field: ${name} = ${value}`);
                if (value && value.includes('@')) {
                    results.emailSettings.adminEmail.recipients = value;
                    break;
                }
            }
        }

        // Look for admin email subject - broader search
        const subjectSelectors = [
            'input[name*="admin_email_subject"]',
            'input[name*="email_subject"]',
            'input[name*="subject"]',
            '[data-setting*="admin_email_subject"]'
        ];

        for (const selector of subjectSelectors) {
            const element = await page.locator(selector).first();
            if (await element.isVisible()) {
                const name = await element.getAttribute('name');
                const value = await element.inputValue();
                console.log(`📧 Found admin email subject field: ${name} = ${value}`);
                if (name && name.includes('admin') && value) {
                    results.emailSettings.adminEmail.subject = value;
                    break;
                }
            }
        }

        // Step 7: Verify confirmation email settings
        console.log('📬 Step 7: Checking confirmation email settings...');
        
        // Look for confirmation email enabled checkbox - broader search
        const confirmSelectors = [
            'input[name*="confirm"]',
            'input[name*="confirmation"]',
            'input[name*="send_confirm"]',
            '[data-setting*="confirm"]',
            'input[type="checkbox"][name*="confirm"]'
        ];

        for (const selector of confirmSelectors) {
            const element = await page.locator(selector).first();
            if (await element.isVisible()) {
                const name = await element.getAttribute('name');
                const checked = await element.isChecked();
                console.log(`📧 Found confirmation email checkbox: ${name} = ${checked}`);
                if (name && name.includes('confirm')) {
                    results.emailSettings.confirmationEmail.enabled = checked;
                    break;
                }
            }
        }

        // Look for confirmation email subject - broader search
        const confirmSubjectSelectors = [
            'input[name*="confirm_email_subject"]',
            'input[name*="confirmation_subject"]',
            'input[name*="confirm_subject"]',
            '[data-setting*="confirm_email_subject"]'
        ];

        for (const selector of confirmSubjectSelectors) {
            const element = await page.locator(selector).first();
            if (await element.isVisible()) {
                const name = await element.getAttribute('name');
                const value = await element.inputValue();
                console.log(`📧 Found confirmation email subject field: ${name} = ${value}`);
                if (name && name.includes('confirm') && value) {
                    results.emailSettings.confirmationEmail.subject = value;
                    break;
                }
            }
        }

        // Step 8: Compare with expected data
        console.log('🔍 Step 8: Comparing with expected email settings...');
        
        if (expectedEmailData) {
            let settingsMatch = true;
            
            // Check admin email recipients
            if (expectedEmailData.adminEmailTo && results.emailSettings.adminEmail.recipients) {
                const expectedRecipients = expectedEmailData.adminEmailTo.toLowerCase().replace(/\s/g, '');
                const actualRecipients = results.emailSettings.adminEmail.recipients.toLowerCase().replace(/\s/g, '');
                if (expectedRecipients !== actualRecipients) {
                    results.errors.push(`Admin email recipients mismatch. Expected: "${expectedEmailData.adminEmailTo}", Found: "${results.emailSettings.adminEmail.recipients}"`);
                    settingsMatch = false;
                } else {
                    console.log('✅ Admin email recipients match');
                }
            }

            // Check admin email subject
            if (expectedEmailData.adminEmailSubject && results.emailSettings.adminEmail.subject) {
                if (expectedEmailData.adminEmailSubject !== results.emailSettings.adminEmail.subject) {
                    results.errors.push(`Admin email subject mismatch. Expected: "${expectedEmailData.adminEmailSubject}", Found: "${results.emailSettings.adminEmail.subject}"`);
                    settingsMatch = false;
                } else {
                    console.log('✅ Admin email subject matches');
                }
            }

            // Check confirmation email subject
            if (expectedEmailData.confirmEmailSubject && results.emailSettings.confirmationEmail.subject) {
                if (expectedEmailData.confirmEmailSubject !== results.emailSettings.confirmationEmail.subject) {
                    results.errors.push(`Confirmation email subject mismatch. Expected: "${expectedEmailData.confirmEmailSubject}", Found: "${results.emailSettings.confirmationEmail.subject}"`);
                    settingsMatch = false;
                } else {
                    console.log('✅ Confirmation email subject matches');
                }
            }

            results.steps.settingsComparison = settingsMatch;
        }

        // Overall success
        results.success = results.steps.loginSuccess && 
                         results.steps.builderLoaded && 
                         results.steps.emailsTabClicked &&
                         (results.emailSettings.adminEmail.enabled !== null || 
                          results.emailSettings.confirmationEmail.enabled !== null);

        if (results.success) {
            console.log('\n✅ Email settings verification PASSED');
        } else {
            console.log('\n❌ Email settings verification FAILED');
        }

    } catch (error) {
        console.error('❌ Email verification ERROR:', error.message);
        results.errors.push(error.message);
        results.success = false;
    } finally {
        if (browser) {
            await browser.close();
        }
    }

    return results;
};

// Test specific form with expected email data
const testForm71883 = async () => {
    // Expected email data from the original form import
    const expectedEmailData = {
        adminEmailTo: 'wisconsinhardmoney@gmail.com, info.wisconsinhardmoney@gmail.com, michelle.wisconsinhardmoney@gmail.com',
        adminEmailSubject: 'Loan Pre-Qualification',
        confirmEmailSubject: 'Thank you!'
    };

    const results = await verifyEmailSettings('209', expectedEmailData); // Form was imported as post ID 209
    
    console.log('\n📊 EMAIL VERIFICATION RESULTS');
    console.log('='.repeat(50));
    console.log(`Form ID: ${results.formId}`);
    console.log(`Success: ${results.success}`);
    console.log(`Elements Found: ${results.elementsFound}`);
    console.log('\nEmail Settings Found:');
    console.log(`  Admin Email Enabled: ${results.emailSettings.adminEmail.enabled}`);
    console.log(`  Admin Email Recipients: ${results.emailSettings.adminEmail.recipients}`);
    console.log(`  Admin Email Subject: ${results.emailSettings.adminEmail.subject}`);
    console.log(`  Confirmation Email Enabled: ${results.emailSettings.confirmationEmail.enabled}`);
    console.log(`  Confirmation Email Subject: ${results.emailSettings.confirmationEmail.subject}`);
    
    if (results.errors.length > 0) {
        console.log('\nErrors:');
        results.errors.forEach(error => console.log(`  - ${error}`));
    }

    return results;
};

// Run if called directly
if (require.main === module) {
    testForm71883().catch(console.error);
}

module.exports = { verifyEmailSettings };