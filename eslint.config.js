const js = require("@eslint/js");
const globals = require("globals");

module.exports = [
    js.configs.recommended,
    {
        ignores: [
            // Dependencies
            "node_modules/**",
            "vendor/**",
            // Build output
            "dist/**",
            "build/**",
            // WordPress core
            "wordpress/**",
            "wp-content/**",
            // Third-party libraries
            "src/includes/lib/**",
            "src/assets/js/lib/**",
            "src/assets/js/vendor/**",
            // Third-party frontend libraries
            "src/assets/js/frontend/int-phone-utils.js",
            "src/assets/js/frontend/int-phone.js",
            "src/assets/js/frontend/spectrum.js",
            "src/assets/js/frontend/carousel.js",
            "src/assets/js/frontend/date-format.js",
            "src/assets/js/frontend/masked-input.js",
            "src/assets/js/frontend/masked-currency.js",
            "src/assets/js/frontend/timepicker.js",
            "src/assets/js/frontend/iban-check.js",
            // Third-party backend libraries
            "src/assets/js/backend/jquery-pep.js",
            "src/assets/js/backend/simpleslider.js",
            "src/assets/js/backend/tooltips.js",
            "src/assets/js/backend/hints.js",
            // Minified files
            "**/*.min.js",
            // Generated files
            "src/assets/js/backend/emails-v2.js",
            "src/assets/js/backend/emails-v2.css",
            "src/assets/js/react/emails-v2.js",
            // Test files
            "**/*.test.js",
            "**/*.spec.js",
            // Temporary files
            "**/*.swp",
            "**/*.swo",
            "**/*~",
            "**/.DS_Store",
            // IDE
            ".vscode/**",
            ".idea/**",
            // Documentation
            "docs/**"
        ]
    },
    {
        languageOptions: {
            ecmaVersion: 2020,
            sourceType: "script",
            globals: {
                ...globals.browser,
                ...globals.jquery,
                // WordPress/Plugin specific globals
                jQuery: "readonly",
                $: "readonly",
                SUPER: "readonly",
                wp: "readonly",
                ajaxurl: "readonly",
                super_common_i18n: "readonly",
                // AMD/UMD globals (for third-party libraries)
                define: "readonly",
                module: "readonly",
                require: "readonly",
                exports: "readonly",
                // ES6 globals
                Atomics: "readonly",
                SharedArrayBuffer: "readonly",
            },
            parserOptions: {
                ecmaFeatures: {
                    jsx: true
                }
            }
        },
        rules: {
            // Warn on variable redeclaration (legacy code patterns)
            "no-redeclare": ["warn", { "builtinGlobals": false }],
            // Warn on empty blocks
            "no-empty": "warn",
            // Allow unused variables that start with underscore
            "no-unused-vars": ["error", {
                "argsIgnorePattern": "^_",
                "varsIgnorePattern": "^_",
                "caughtErrorsIgnorePattern": "^_"
            }]
        }
    }
];
