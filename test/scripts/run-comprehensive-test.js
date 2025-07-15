/**
 * Comprehensive Super Forms Testing Runner
 * 
 * This script orchestrates the complete testing process:
 * 1. Import all 197 forms
 * 2. Test form builder functionality  
 * 3. Test frontend submission
 * 4. Generate comprehensive report
 */

const { chromium } = require('playwright');
const fs = require('fs').promises;
const path = require('path');

// Import test modules
const { testFormBuilder } = require('./test-form-builder.js');
const { testFrontendSubmission } = require('./test-frontend-submission.js');

// Priority forms for testing (from analysis)
const priorityForms = [
    { formId: '71883', formTitle: 'Loan Pre-Qualification', features: ['pdf', 'listings', 'woocommerce', 'paypal'] },
    { formId: '71550', formTitle: '2024 Hellenic Academy Registration', features: ['pdf', 'listings', 'woocommerce'] },
    { formId: '40015', formTitle: 'PayPal – Cart Checkout', features: ['listings', 'woocommerce', 'paypal'] },
    { formId: '3376', formTitle: 'All Elements', features: ['listings', 'woocommerce', 'file_upload'] },
    { formId: '67520', formTitle: 'Formulaire d\'inscription pour tous', features: ['pdf', 'listings', 'woocommerce', 'translation'] },
    { formId: '67532', formTitle: 'Form Name', features: ['listings', 'woocommerce', 'file_upload'] },
    { formId: '69712', formTitle: 'Group Roster FDF 2024', features: ['pdf', 'listings', 'woocommerce'] }
];

class SuperFormsTestRunner {
    constructor() {
        this.browser = null;
        this.context = null;
        this.page = null;
        this.results = {
            timestamp: new Date().toISOString(),
            importResults: null,
            formTests: [],
            summary: {
                totalForms: 0,
                passedForms: 0,
                failedForms: 0,
                builderTestsPassed: 0,
                frontendTestsPassed: 0
            }
        };
    }

    async setup() {
        console.log('🚀 Starting Super Forms Comprehensive Testing');
        console.log('=' * 50);

        // Launch browser
        this.browser = await chromium.launch({ 
            headless: false,
            slowMo: 100 // Slow down for debugging
        });
        this.context = await this.browser.newContext();
        this.page = await this.context.newPage();

        // Login to WordPress
        await this.loginToWordPress();
    }

    async loginToWordPress() {
        console.log('🔐 Logging into WordPress...');
        await this.page.goto('http://localhost:8080/wp-admin');
        await this.page.waitForLoadState('networkidle');

        // Check if already logged in
        const loginForm = await this.page.locator('#loginform').isVisible();
        if (loginForm) {
            await this.page.fill('#user_login', 'admin');
            await this.page.fill('#user_pass', 'admin');
            await this.page.click('#wp-submit');
            await this.page.waitForLoadState('networkidle');
        }

        // Verify login success
        const dashboard = await this.page.locator('#wpadminbar, .wp-admin').isVisible();
        if (!dashboard) {
            throw new Error('WordPress login failed');
        }
        console.log('✅ WordPress login successful');
    }

    async importAllForms() {
        console.log('\n📥 Phase 1: Importing all 197 forms...');
        
        try {
            // Execute the PHP import script via WordPress
            await this.page.goto('http://localhost:8080/wp-admin/admin.php?page=super_forms');
            
            // Use browser console to run PHP script (alternative approach)
            const importResults = await this.page.evaluate(async () => {
                const response = await fetch('/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'super_import_all_forms' // Would need to be implemented
                    })
                });
                return await response.json();
            });

            this.results.importResults = importResults;
            console.log(`✅ Import completed: ${importResults.successful_imports}/${importResults.total_forms} forms imported`);
            
        } catch (error) {
            console.error('❌ Import failed:', error.message);
            this.results.importResults = { error: error.message };
        }
    }

    async testForm(formData) {
        console.log(`\n🧪 Testing Form: ${formData.formTitle} (ID: ${formData.formId})`);
        console.log('-' * 40);

        const formResult = {
            formId: formData.formId,
            formTitle: formData.formTitle,
            features: formData.features,
            builderTest: null,
            frontendTest: null,
            success: false,
            startTime: new Date().toISOString()
        };

        try {
            // Test 1: Form Builder
            console.log('🔧 Running Form Builder Test...');
            formResult.builderTest = await testFormBuilder(this.page, formData);
            
            if (formResult.builderTest.success) {
                this.results.summary.builderTestsPassed++;
                console.log('✅ Builder test passed');
            } else {
                console.log('❌ Builder test failed');
            }

            // Test 2: Frontend Submission (only if builder test passed)
            if (formResult.builderTest.success) {
                console.log('🌐 Running Frontend Submission Test...');
                formResult.frontendTest = await testFrontendSubmission(this.page, formData);
                
                if (formResult.frontendTest.success) {
                    this.results.summary.frontendTestsPassed++;
                    console.log('✅ Frontend test passed');
                } else {
                    console.log('❌ Frontend test failed');
                }
            } else {
                console.log('⏭️ Skipping frontend test due to builder test failure');
            }

            // Overall form success
            formResult.success = formResult.builderTest.success && 
                               (formResult.frontendTest ? formResult.frontendTest.success : false);

            if (formResult.success) {
                this.results.summary.passedForms++;
                console.log('🎉 Overall form test PASSED');
            } else {
                this.results.summary.failedForms++;
                console.log('💥 Overall form test FAILED');
            }

        } catch (error) {
            console.error('💣 Form test ERROR:', error.message);
            formResult.error = error.message;
            this.results.summary.failedForms++;
        }

        formResult.endTime = new Date().toISOString();
        this.results.formTests.push(formResult);
        
        return formResult;
    }

    async runAllTests() {
        await this.setup();

        // Phase 1: Import (commented out for now - can run manually)
        // await this.importAllForms();

        // Phase 2-6: Test each priority form
        console.log('\n🎯 Phase 2-6: Testing Priority Forms...');
        this.results.summary.totalForms = priorityForms.length;

        for (const formData of priorityForms) {
            await this.testForm(formData);
            
            // Brief pause between forms
            await this.page.waitForTimeout(2000);
        }

        await this.generateReport();
        await this.cleanup();
    }

    async generateReport() {
        console.log('\n📊 Generating Test Report...');
        
        const reportPath = path.join(__dirname, '..', 'test-results.json');
        const htmlReportPath = path.join(__dirname, '..', 'test-report.html');

        // Save JSON results
        await fs.writeFile(reportPath, JSON.stringify(this.results, null, 2));

        // Generate HTML report
        const htmlReport = this.generateHTMLReport();
        await fs.writeFile(htmlReportPath, htmlReport);

        console.log(`📄 Reports generated:`);
        console.log(`   JSON: ${reportPath}`);
        console.log(`   HTML: ${htmlReportPath}`);

        // Print summary
        console.log('\n📈 TEST SUMMARY');
        console.log('=' * 30);
        console.log(`Total Forms Tested: ${this.results.summary.totalForms}`);
        console.log(`Forms Passed: ${this.results.summary.passedForms}`);
        console.log(`Forms Failed: ${this.results.summary.failedForms}`);
        console.log(`Builder Tests Passed: ${this.results.summary.builderTestsPassed}`);
        console.log(`Frontend Tests Passed: ${this.results.summary.frontendTestsPassed}`);
        console.log(`Success Rate: ${((this.results.summary.passedForms / this.results.summary.totalForms) * 100).toFixed(1)}%`);
    }

    generateHTMLReport() {
        const results = this.results;
        return `
<!DOCTYPE html>
<html>
<head>
    <title>Super Forms Test Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .summary { background: #f0f0f0; padding: 20px; border-radius: 5px; }
        .form-result { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { border-left: 5px solid #4CAF50; }
        .failure { border-left: 5px solid #f44336; }
        .test-step { margin: 5px 0; }
        .pass { color: #4CAF50; }
        .fail { color: #f44336; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🧪 Super Forms Legacy Compatibility Test Report</h1>
    
    <div class="summary">
        <h2>📊 Summary</h2>
        <p><strong>Generated:</strong> ${results.timestamp}</p>
        <p><strong>Total Forms:</strong> ${results.summary.totalForms}</p>
        <p><strong>Forms Passed:</strong> ${results.summary.passedForms}</p>
        <p><strong>Forms Failed:</strong> ${results.summary.failedForms}</p>
        <p><strong>Success Rate:</strong> ${((results.summary.passedForms / results.summary.totalForms) * 100).toFixed(1)}%</p>
    </div>

    <h2>📋 Detailed Results</h2>
    ${results.formTests.map(form => `
        <div class="form-result ${form.success ? 'success' : 'failure'}">
            <h3>${form.formTitle} (ID: ${form.formId})</h3>
            <p><strong>Features:</strong> ${form.features.join(', ')}</p>
            <p><strong>Overall Result:</strong> <span class="${form.success ? 'pass' : 'fail'}">${form.success ? 'PASS' : 'FAIL'}</span></p>
            
            ${form.builderTest ? `
                <h4>🔧 Builder Test</h4>
                <p>Result: <span class="${form.builderTest.success ? 'pass' : 'fail'}">${form.builderTest.success ? 'PASS' : 'FAIL'}</span></p>
                <p>Elements Found: ${form.builderTest.elementsFound}</p>
                <p>Settings Tabs: ${form.builderTest.settingsTabsFound}</p>
                ${form.builderTest.errors.length > 0 ? `<p>Errors: ${form.builderTest.errors.join(', ')}</p>` : ''}
            ` : ''}
            
            ${form.frontendTest ? `
                <h4>🌐 Frontend Test</h4>
                <p>Result: <span class="${form.frontendTest.success ? 'pass' : 'fail'}">${form.frontendTest.success ? 'PASS' : 'FAIL'}</span></p>
                ${form.frontendTest.errors.length > 0 ? `<p>Errors: ${form.frontendTest.errors.join(', ')}</p>` : ''}
            ` : ''}
        </div>
    `).join('')}

</body>
</html>`;
    }

    async cleanup() {
        if (this.browser) {
            await this.browser.close();
        }
        console.log('\n✅ Testing completed successfully!');
    }
}

// Run the comprehensive test
const runner = new SuperFormsTestRunner();
runner.runAllTests().catch(console.error);

module.exports = { SuperFormsTestRunner };