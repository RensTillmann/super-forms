const { chromium } = require('playwright');

async function testFormBuilder(formId) {
    const browser = await chromium.launch({ headless: false }); // Set to true for headless
    const context = await browser.newContext();
    const page = await context.newPage();
    
    const results = {
        formId: formId,
        timestamp: new Date().toISOString(),
        loginSuccess: false,
        builderPageLoads: false,
        frontendPageLoads: false,
        errors: [],
        warnings: [],
        consoleErrors: [],
        networkErrors: []
    };
    
    try {
        // Listen for console errors
        page.on('console', msg => {
            if (msg.type() === 'error') {
                results.consoleErrors.push({
                    text: msg.text(),
                    location: msg.location()
                });
                console.log(`❌ Console Error: ${msg.text()}`);
            }
            if (msg.type() === 'warning') {
                results.warnings.push(msg.text());
                console.log(`⚠️ Console Warning: ${msg.text()}`);
            }
        });
        
        // Listen for network failures
        page.on('response', response => {
            if (response.status() >= 400) {
                results.networkErrors.push({
                    url: response.url(),
                    status: response.status(),
                    statusText: response.statusText()
                });
                console.log(`🌐 Network Error: ${response.status()} ${response.url()}`);
            }
        });
        
        console.log('🚀 Starting automated form testing...');
        
        // Step 1: Login to WordPress admin
        console.log('📝 Logging into WordPress admin...');
        await page.goto('http://localhost:8080/wp-admin/');
        
        // Check if already logged in or need to login
        const needLogin = await page.locator('#loginform').isVisible().catch(() => false);
        
        if (needLogin) {
            await page.fill('#user_login', 'admin');
            await page.fill('#user_pass', 'admin');
            await page.click('#wp-submit');
            await page.waitForNavigation();
        }
        
        // Check if login was successful
        const isLoggedIn = await page.locator('#wpadminbar').isVisible().catch(() => false);
        if (isLoggedIn) {
            results.loginSuccess = true;
            console.log('✅ Login successful');
        } else {
            results.errors.push('Login failed');
            console.log('❌ Login failed');
            return results;
        }
        
        // Step 2: Test Form Builder Page
        console.log(`🔧 Testing form builder page for form ID: ${formId}...`);
        const builderUrl = `http://localhost:8080/wp-admin/admin.php?page=super_create_form&id=${formId}`;
        
        try {
            await page.goto(builderUrl, { waitUntil: 'networkidle', timeout: 10000 });
            
            // Check for WordPress fatal error
            const fatalError = await page.locator('.wp-die-message').isVisible().catch(() => false);
            if (fatalError) {
                const errorText = await page.locator('.wp-die-message').textContent();
                results.errors.push(`WordPress fatal error: ${errorText}`);
                console.log('💥 Fatal error detected on builder page');
                return results;
            }
            
            // Check if form builder elements are present
            const hasFormBuilder = await page.locator('.super-form-builder, #super-form-builder, .super-element').first().isVisible({ timeout: 5000 }).catch(() => false);
            const hasSettingsTab = await page.locator('[data-tab="form_settings"], .super-settings-tabs, .settings-tab').first().isVisible({ timeout: 3000 }).catch(() => false);
            
            if (hasFormBuilder) {
                results.builderPageLoads = true;
                console.log('✅ Form builder elements detected');
                
                if (hasSettingsTab) {
                    console.log('✅ Settings tabs detected');
                } else {
                    results.warnings.push('Settings tabs not found - may be using old UI');
                    console.log('⚠️ Settings tabs not found');
                }
            } else {
                results.errors.push('Form builder elements not found');
                console.log('❌ Form builder elements not found');
            }
            
        } catch (error) {
            results.errors.push(`Builder page error: ${error.message}`);
            console.log(`❌ Builder page error: ${error.message}`);
        }
        
        // Step 3: Test Frontend Page
        console.log('🌐 Testing frontend form page...');
        const frontendUrl = `http://localhost:8080/test-${formId}/`; // Assuming page was created
        
        try {
            await page.goto(frontendUrl, { waitUntil: 'networkidle', timeout: 10000 });
            
            // Check for form on frontend
            const hasForm = await page.locator('.super-form, form[id*="super"]').isVisible({ timeout: 5000 }).catch(() => false);
            
            if (hasForm) {
                results.frontendPageLoads = true;
                console.log('✅ Frontend form detected');
            } else {
                results.warnings.push('Frontend form not found');
                console.log('⚠️ Frontend form not found');
            }
            
        } catch (error) {
            results.warnings.push(`Frontend page error: ${error.message}`);
            console.log(`⚠️ Frontend page error: ${error.message}`);
        }
        
        // Step 4: Take screenshots if there are errors
        if (results.errors.length > 0 || results.consoleErrors.length > 0) {
            await page.screenshot({ path: `/tmp/form-${formId}-error.png`, fullPage: true });
            console.log(`📸 Error screenshot saved: /tmp/form-${formId}-error.png`);
        }
        
    } catch (error) {
        results.errors.push(`General error: ${error.message}`);
        console.log(`💥 General error: ${error.message}`);
    } finally {
        await browser.close();
    }
    
    return results;
}

// Main execution
async function main() {
    const formId = process.argv[2] || '5';
    console.log(`🧪 Testing form ID: ${formId}`);
    
    const results = await testFormBuilder(formId);
    
    console.log('\n📊 Test Results Summary:');
    console.log(`Login Success: ${results.loginSuccess ? '✅' : '❌'}`);
    console.log(`Builder Page Loads: ${results.builderPageLoads ? '✅' : '❌'}`);
    console.log(`Frontend Page Loads: ${results.frontendPageLoads ? '✅' : '❌'}`);
    console.log(`Console Errors: ${results.consoleErrors.length}`);
    console.log(`Network Errors: ${results.networkErrors.length}`);
    console.log(`General Errors: ${results.errors.length}`);
    
    if (results.errors.length > 0) {
        console.log('\n❌ Errors:');
        results.errors.forEach(error => console.log(`  - ${error}`));
    }
    
    if (results.consoleErrors.length > 0) {
        console.log('\n🔍 Console Errors:');
        results.consoleErrors.forEach(error => console.log(`  - ${error.text}`));
    }
    
    // Save results to file
    const fs = require('fs');
    fs.writeFileSync(`/tmp/form-${formId}-test-results.json`, JSON.stringify(results, null, 2));
    console.log(`\n💾 Results saved to: /tmp/form-${formId}-test-results.json`);
}

main().catch(console.error);