<?php
/**
 * WordPress Gutenberg dependencies for Email Builder v2
 * 
 * DISABLED: This file is commented out as we've simplified the email editor
 * to only use Quill.js and TinyMCE for better email compatibility and UX.
 * WordPress block editors were complex and not optimized for email clients.
 * 
 * @package SUPER_Forms
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// COMMENTED OUT: All WordPress Gutenberg functionality disabled
// Keeping only Quill.js and TinyMCE for simplified email editing

/*

/**
 * Add Gutenberg dependencies to Super Forms scripts
 * 
 * @param array $scripts Existing Super Forms scripts
 * @return array Modified scripts with Gutenberg dependencies
 */
function super_forms_add_gutenberg_scripts($scripts) {
    // Only add Gutenberg scripts on the form creation page
    $screen_id = 'super-forms_page_super_create_form';
    
    // Add WordPress block editor dependencies
    $gutenberg_scripts = array(
        'wp-element' => array(
            'src'     => null, // WordPress core script
            'deps'    => array('wp-element'),
            'version' => get_bloginfo('version'),
            'footer'  => false,
            'screen'  => array($screen_id),
            'method'  => 'enqueue',
        ),
        'wp-blocks' => array(
            'src'     => null, // WordPress core script
            'deps'    => array('wp-element', 'wp-data'),
            'version' => get_bloginfo('version'),
            'footer'  => false,
            'screen'  => array($screen_id),
            'method'  => 'enqueue',
        ),
        'wp-block-editor' => array(
            'src'     => null, // WordPress core script
            'deps'    => array('wp-element', 'wp-blocks', 'wp-data', 'wp-components'),
            'version' => get_bloginfo('version'),
            'footer'  => false,
            'screen'  => array($screen_id),
            'method'  => 'enqueue',
        ),
        'wp-data' => array(
            'src'     => null, // WordPress core script
            'deps'    => array('wp-element'),
            'version' => get_bloginfo('version'),
            'footer'  => false,
            'screen'  => array($screen_id),
            'method'  => 'enqueue',
        ),
        'wp-components' => array(
            'src'     => null, // WordPress core script
            'deps'    => array('wp-element'),
            'version' => get_bloginfo('version'),
            'footer'  => false,
            'screen'  => array($screen_id),
            'method'  => 'enqueue',
        ),
        'wp-editor' => array(
            'src'     => null, // WordPress core script
            'deps'    => array('wp-element', 'wp-blocks', 'wp-block-editor'),
            'version' => get_bloginfo('version'),
            'footer'  => false,
            'screen'  => array($screen_id),
            'method'  => 'enqueue',
        ),
    );
    
    // Merge with existing scripts
    return array_merge($scripts, $gutenberg_scripts);
}

/**
 * Enqueue Gutenberg scripts directly using WordPress functions
 * This is a more direct approach that ensures Gutenberg APIs are available
 */
function super_forms_enqueue_gutenberg_directly() {
    // Debug: Always check what page we're on
    if (function_exists('get_current_screen')) {
        $screen = get_current_screen();
        error_log('Super Forms Gutenberg: Current screen: ' . ($screen ? $screen->id : 'no screen'));
    }
    
    // Check if we're on the right page - be more flexible with the check
    $current_page = isset($_GET['page']) ? $_GET['page'] : '';
    $is_super_forms_page = (
        $current_page === 'super_create_form' || 
        strpos($current_page, 'super_') === 0 ||
        (function_exists('get_current_screen') && get_current_screen() && get_current_screen()->id === 'super-forms_page_super_create_form')
    );
    
    if (!$is_super_forms_page) {
        error_log('Super Forms Gutenberg: Not on Super Forms page, current page: ' . $current_page);
        return;
    }
    
    error_log('Super Forms Gutenberg: Enqueuing scripts on page: ' . $current_page);
    
    // Enqueue WordPress Gutenberg dependencies in correct order
    wp_enqueue_script('wp-polyfill');
    wp_enqueue_script('wp-element');
    wp_enqueue_script('wp-data');
    wp_enqueue_script('wp-components');
    wp_enqueue_script('wp-blocks');
    wp_enqueue_script('wp-block-editor');
    wp_enqueue_script('wp-editor');
    wp_enqueue_script('wp-format-library');
    wp_enqueue_script('wp-rich-text');
    
    // Add wp-interface for complete editor functionality
    wp_enqueue_script('wp-interface');
    
    // Add wp-block-library for core block registration
    wp_enqueue_script('wp-block-library');
    
    // Enqueue TinyMCE for wp.editor.initialize
    wp_enqueue_script('editor');
    wp_enqueue_script('quicktags');
    wp_enqueue_script('wplink');
    wp_enqueue_script('word-count');
    
    // Load TinyMCE initialization script (using modern approach)
    // wp_tiny_mce() is deprecated, so we'll handle TinyMCE through our stub instead
    
    // Add TinyMCE inline config for Classic blocks
    $tinymce_config = "
    window.tinyMCEPreInit = window.tinyMCEPreInit || {};
    window.tinyMCEPreInit.mceInit = window.tinyMCEPreInit.mceInit || {};
    window.tinyMCEPreInit.qtInit = window.tinyMCEPreInit.qtInit || {};
    
    // Provide comprehensive TinyMCE config to prevent Classic block errors
    if (typeof window.tinymce === 'undefined') {
        window.tinymce = {
            majorVersion: '5',
            minorVersion: '10',
            release: '5.10.7',
            init: function() { console.log('TinyMCE stub loaded'); },
            remove: function() {},
            execCommand: function() {},
            get: function() { return null; },
            EditorManager: {
                get: function() { return null; },
                add: function() {},
                remove: function() {},
                execCommand: function() {}
            },
            dom: {
                DOMUtils: function() {
                    return {
                        getAttrib: function() { return ''; },
                        setAttrib: function() {},
                        remove: function() {},
                        select: function() { return []; }
                    };
                }
            },
            util: {
                Tools: {
                    map: function(arr, fn) { return arr.map(fn); },
                    trim: function(str) { return str.trim(); }
                }
            }
        };
    }
    
    // Also stub window.wp.editor if needed
    if (window.wp && !window.wp.editor) {
        window.wp.editor = {
            initialize: function() { console.log('wp.editor.initialize stub'); },
            remove: function() { console.log('wp.editor.remove stub'); }
        };
    }
    ";
    
    wp_add_inline_script('editor', $tinymce_config);
    
    // Include media scripts for full editor functionality
    wp_enqueue_media();
    
    error_log('Super Forms Gutenberg: Enqueued all WordPress editor scripts');
    
    // Enqueue block editor styles for full Gutenberg experience
    wp_enqueue_style('wp-edit-blocks');
    wp_enqueue_style('wp-block-editor');
    wp_enqueue_style('wp-components');
    wp_enqueue_style('wp-block-library');
    wp_enqueue_style('wp-block-library-theme');
    
    // Add custom CSS to make the editor look more like WordPress post editor
    $custom_css = "
    .gutenberg-editor-container .wp-block-editor {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
    }
    
    .gutenberg-editor-container .block-editor-block-list__layout {
        padding: 16px;
    }
    
    .gutenberg-editor-container .wp-block {
        max-width: none !important;
    }
    
    .gutenberg-editor-container .block-editor-inserter {
        z-index: 159900;
    }
    
    .gutenberg-editor-container .components-popover__content {
        z-index: 159901;
    }
    ";
    
    wp_add_inline_style('wp-block-editor', $custom_css);
    
    // Add inline script to verify APIs are loaded
    $verification_script = "
    console.log('🎯 Gutenberg Scripts Enqueued:', {
        wpExists: typeof window.wp !== 'undefined',
        wpElement: typeof window.wp?.element !== 'undefined',
        wpBlocks: typeof window.wp?.blocks !== 'undefined',
        wpData: typeof window.wp?.data !== 'undefined',
        wpBlockEditor: typeof window.wp?.blockEditor !== 'undefined',
        wpComponents: typeof window.wp?.components !== 'undefined',
        wpEditor: typeof window.wp?.editor !== 'undefined',
        wpRichText: typeof window.wp?.richText !== 'undefined',
        wpFormatLibrary: typeof window.wp?.formatLibrary !== 'undefined',
        wpInterface: typeof window.wp?.interface !== 'undefined',
        wpBlockLibrary: typeof window.wp?.blockLibrary !== 'undefined',
        // Check specific block editor components
        BlockEditorProvider: typeof window.wp?.blockEditor?.BlockEditorProvider !== 'undefined',
        BlockList: typeof window.wp?.blockEditor?.BlockList !== 'undefined',
        BlockToolbar: typeof window.wp?.blockEditor?.BlockToolbar !== 'undefined',
        Inserter: typeof window.wp?.blockEditor?.Inserter !== 'undefined',
        // Check interface components
        InterfaceTemplate: typeof window.wp?.interface?.InterfaceTemplate !== 'undefined',
        Sidebar: typeof window.wp?.interface?.Sidebar !== 'undefined',
        // Check block registration functions
        registerCoreBlocks: typeof window.wp?.blocks?.registerCoreBlocks !== 'undefined',
        blockLibraryRegister: typeof window.wp?.blockLibrary?.registerCoreBlocks !== 'undefined',
        // Check TinyMCE availability
        tinymce: typeof window.tinymce !== 'undefined',
        tinyMCEPreInit: typeof window.tinyMCEPreInit !== 'undefined'
    });
    
    // Initialize core blocks if available
    if (window.wp && window.wp.blocks) {
        const existingBlocks = window.wp.blocks.getBlockTypes();
        console.log('🔧 Block registration check...', { count: existingBlocks.length });
        
        if (existingBlocks.length === 0) {
            if (window.wp.blocks.registerCoreBlocks && typeof window.wp.blocks.registerCoreBlocks === 'function') {
                console.log('🔧 Registering core blocks via wp.blocks...');
                window.wp.blocks.registerCoreBlocks();
                console.log('✅ Core blocks registered');
            } else if (window.wp.blockLibrary && window.wp.blockLibrary.registerCoreBlocks) {
                console.log('🔧 Registering core blocks via wp.blockLibrary...');
                window.wp.blockLibrary.registerCoreBlocks();
                console.log('✅ Core blocks registered via blockLibrary');
            } else {
                console.log('⚠️ No block registration function available');
            }
        } else {
            console.log('✅ Blocks already registered:', existingBlocks.length);
        }
        
        // Unregister problematic blocks that cause TinyMCE errors
        const problematicBlocks = [
            'core/freeform',
            'core/html',
            'core/more',
            'core/nextpage',
            'core/classic'
        ];
        
        problematicBlocks.forEach(blockName => {
            if (window.wp.blocks.getBlockType(blockName)) {
                console.log('🚫 Unregistering problematic block:', blockName);
                window.wp.blocks.unregisterBlockType(blockName);
            }
        });
        
        console.log('🔧 Cleaned up problematic blocks');
        
        // Hook into block parsing to prevent Classic blocks from being created
        if (window.wp && window.wp.hooks) {
            window.wp.hooks.addFilter(
                'blocks.getBlockDefaultClassName',
                'super-forms/prevent-classic',
                function(className, blockName) {
                    if (['core/freeform', 'core/classic', 'core/html'].includes(blockName)) {
                        console.log('🚫 Prevented Classic block class:', blockName);
                        return null;
                    }
                    return className;
                }
            );
            
            window.wp.hooks.addFilter(
                'blocks.getSaveElement',
                'super-forms/convert-classic',
                function(element, blockType, attributes) {
                    if (['core/freeform', 'core/classic', 'core/html'].includes(blockType.name)) {
                        console.log('🔄 Converting Classic block to paragraph');
                        // Return a paragraph element instead
                        return window.wp.element.createElement('p', {}, attributes.content || '');
                    }
                    return element;
                }
            );
        }
        
        // Also clean up any existing Classic blocks from the content
        setTimeout(() => {
            problematicBlocks.forEach(blockName => {
                if (window.wp.blocks.getBlockType(blockName)) {
                    console.log('🚫 Second pass: Unregistering', blockName);
                    window.wp.blocks.unregisterBlockType(blockName);
                }
            });
            console.log('🔧 Second cleanup pass completed');
        }, 1000);
    }
    ";
    
    wp_add_inline_script('wp-blocks', $verification_script);
}

// Hook into Super Forms script enqueue system
add_filter('super_enqueue_scripts', 'super_forms_add_gutenberg_scripts');

// Also hook directly into admin_enqueue_scripts for more reliable loading
add_action('admin_enqueue_scripts', 'super_forms_enqueue_gutenberg_directly', 5);

// Hook into admin_init to ensure WordPress APIs are loaded early
add_action('admin_init', 'super_forms_ensure_gutenberg_apis');

/**
 * Ensure WordPress Gutenberg APIs are available early
 */
function super_forms_ensure_gutenberg_apis() {
    // Check if we're on a Super Forms page
    $current_page = isset($_GET['page']) ? $_GET['page'] : '';
    if (strpos($current_page, 'super_') !== 0) {
        return;
    }
    
    // Force load WordPress block editor
    if (function_exists('wp_enqueue_script')) {
        wp_enqueue_script('wp-element');
        wp_enqueue_script('wp-data');
        wp_enqueue_script('wp-blocks');
        wp_enqueue_script('wp-block-editor');
        wp_enqueue_script('wp-components');
        wp_enqueue_script('wp-editor');
        
        // Also enqueue classic editor scripts for wp.editor.initialize
        wp_enqueue_script('editor');
        wp_enqueue_script('quicktags');
        wp_enqueue_script('wplink');
        
        error_log('Super Forms Gutenberg: Force enqueued WordPress scripts in admin_init');
    }
}

/**
 * Add Gutenberg block library initialization
 * This ensures core blocks are registered and available
 */
function super_forms_init_gutenberg_blocks() {
    if (!function_exists('get_current_screen')) {
        return;
    }
    
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'super-forms_page_super_create_form') {
        return;
    }
    
    // Initialize core blocks
    if (function_exists('wp_common_block_scripts_and_styles')) {
        wp_common_block_scripts_and_styles();
    }
    
    // Enqueue block library
    if (function_exists('wp_enqueue_block_library_theme_assets')) {
        wp_enqueue_block_library_theme_assets();
    }
}

// Initialize blocks on admin pages
add_action('admin_enqueue_scripts', 'super_forms_init_gutenberg_blocks', 5);

// End of commented out WordPress Gutenberg functionality - All functionality disabled