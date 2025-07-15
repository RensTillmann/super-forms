const { chromium } = require('playwright');

async function quickTest() {
    console.log('=== QUICK JAVASCRIPT ERROR TEST ===\n');
    
    const browser = await chromium.launch({ 
        headless: false, // Show browser for manual inspection
        devtools: true   // Open devtools automatically
    });
    
    const page = await browser.newContext().then(c => c.newPage());
    
    // Collect console errors
    const errors = [];
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
        console.log('Logging in...');
        await page.goto('http://localhost:8080/wp-login.php');
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'admin');
        await page.click('#wp-submit');
        await page.waitForURL('**/wp-admin/**', { timeout: 10000 });
        
        // Test Form 8
        console.log('\nTesting Form 8 (the problematic one)...');
        await page.goto('http://localhost:8080/wp-admin/admin.php?page=super_create_form&id=8');
        
        // Wait a bit for any JavaScript to execute
        await page.waitForTimeout(3000);
        
        // Check for the specific error we fixed
        const hasOldError = errors.some(e => e.includes("Unexpected token '='"));
        const hasInfiniteLoop = errors.some(e => e.includes('Looking for nested field'));
        
        console.log('\n=== RESULTS ===');
        console.log(`Total errors found: ${errors.length}`);
        console.log(`Has syntax error (Unexpected token): ${hasOldError ? '❌ YES' : '✅ NO'}`);
        console.log(`Has infinite loop messages: ${hasInfiniteLoop ? '❌ YES' : '✅ NO'}`);
        
        if (errors.length > 0) {
            console.log('\nErrors detected:');
            errors.forEach((e, i) => console.log(`${i + 1}. ${e}`));
        } else {
            console.log('\n✅ No JavaScript errors detected!');
        }
        
        console.log('\n👀 Browser window is open - check the console manually');
        console.log('Press Ctrl+C to close when done inspecting...\n');
        
        // Keep browser open for manual inspection
        await new Promise(() => {});
        
    } catch (error) {
        console.error('Test failed:', error.message);
    }
}

quickTest().catch(console.error);