const { chromium } = require('playwright');

async function testForm8() {
    console.log('=== TESTING FIXED FORM 8 ===\n');
    
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newContext().then(c => c.newPage());
    
    // Collect errors
    const errors = [];
    let loadTime = 0;
    
    page.on('console', msg => {
        if (msg.type() === 'error') {
            errors.push(msg.text());
            console.log('❌ Console Error:', msg.text());
        }
    });
    
    page.on('pageerror', error => {
        errors.push(error.toString());
        console.log('❌ Page Error:', error.toString());
    });
    
    try {
        // Login
        console.log('1. Logging into WordPress...');
        await page.goto('http://localhost:8080/wp-login.php');
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'admin');
        await page.click('#wp-submit');
        await page.waitForURL('**/wp-admin/**', { timeout: 10000 });
        console.log('   ✓ Logged in successfully\n');
        
        // Load Form 8
        console.log('2. Loading Form 8 in builder...');
        const startTime = Date.now();
        
        await page.goto('http://localhost:8080/wp-admin/admin.php?page=super_create_form&id=8', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        loadTime = Date.now() - startTime;
        console.log(`   ✓ Page loaded in ${loadTime}ms\n`);
        
        // Wait for form builder elements
        console.log('3. Checking form builder elements...');
        
        // Check if key elements exist
        const hasBuilder = await page.locator('.super-create-form').count() > 0;
        const hasSettings = await page.locator('.super-tabs-content').count() > 0;
        const hasPreview = await page.locator('.super-preview-elements').count() > 0;
        
        console.log(`   Form builder present: ${hasBuilder ? '✓' : '✗'}`);
        console.log(`   Settings tabs present: ${hasSettings ? '✓' : '✗'}`);
        console.log(`   Preview area present: ${hasPreview ? '✓' : '✗'}\n`);
        
        // Check for specific errors
        const syntaxError = errors.find(e => e.includes("Unexpected token '='"));
        const infiniteLoop = errors.find(e => e.includes('Looking for nested field'));
        const stackOverflow = errors.find(e => e.includes('Maximum call stack'));
        
        console.log('4. JavaScript Error Analysis:');
        console.log(`   Syntax error (Unexpected token): ${syntaxError ? '❌ FOUND' : '✅ NOT FOUND'}`);
        console.log(`   Infinite loop messages: ${infiniteLoop ? '❌ FOUND' : '✅ NOT FOUND'}`);
        console.log(`   Stack overflow: ${stackOverflow ? '❌ FOUND' : '✅ NOT FOUND'}`);
        console.log(`   Total errors: ${errors.length}\n`);
        
        // Test tab switching if builder loaded
        if (hasSettings) {
            console.log('5. Testing tab switching (conditional logic trigger)...');
            const tabs = await page.locator('.super-tabs > li').count();
            console.log(`   Found ${tabs} tabs`);
            
            if (tabs > 0) {
                // Click first few tabs
                for (let i = 0; i < Math.min(3, tabs); i++) {
                    await page.locator('.super-tabs > li').nth(i).click();
                    await page.waitForTimeout(500);
                }
                console.log('   ✓ Tab switching completed without errors\n');
            }
        }
        
        // Final verdict
        console.log('=== TEST RESULTS ===');
        if (errors.length === 0 && loadTime < 10000) {
            console.log('✅ SUCCESS: Form 8 loads without JavaScript errors!');
            console.log('✅ The syntax error has been fixed');
            console.log('✅ No infinite loops detected');
            console.log('✅ Performance is acceptable');
        } else if (errors.length > 0) {
            console.log('❌ FAILED: JavaScript errors detected');
            console.log('\nErrors found:');
            errors.slice(0, 5).forEach((e, i) => {
                console.log(`${i + 1}. ${e}`);
            });
        } else if (loadTime >= 10000) {
            console.log('⚠️  WARNING: Form loads but performance is slow');
        }
        
    } catch (error) {
        console.error('❌ Test failed with exception:', error.message);
        if (error.message.includes('timeout')) {
            console.error('   The page is still hanging - server-side issue persists');
        }
    } finally {
        await browser.close();
    }
    
    console.log('\nTest completed.');
}

testForm8().catch(console.error);