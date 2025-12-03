import React, { useState, useEffect, useRef, useCallback } from 'react';

/**
 * Complete WordPress Block Editor
 * Replicates the full WordPress post/page editing experience
 * with block inserter, inspector sidebar, and all expected functionality
 */
function FullWordPressEditor({ 
  value = '', 
  onChange, 
  placeholder = 'Start writing or type / to insert blocks...',
  className = '',
  lineHeight = 1.6
}) {
  const [blocks, setBlocks] = useState([]);
  const [isReady, setIsReady] = useState(false);
  const [hasError, setHasError] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const editorContainer = useRef(null);
  const rootRef = useRef(null);

  // Helper function to extract clean text content from HTML
  const extractTextContent = (html) => {
    if (!html) return '';
    
    // Clean up nested tags and inline styles
    let cleanContent = html
      // Remove all style attributes first
      .replace(/\s+style="[^"]*"/gi, '')
      // Fix nested paragraph tags - extract inner content
      .replace(/<p[^>]*>\s*<p[^>]*>(.*?)<\/p>\s*<\/p>/gi, '<p>$1</p>')
      // Fix nested heading tags
      .replace(/<h([1-6])[^>]*>\s*<p[^>]*>(.*?)<\/p>\s*<\/h[1-6]>/gi, '<h$1>$2</h$1>')
      // Remove any remaining nested paragraphs
      .replace(/<p[^>]*>\s*<p[^>]*>/gi, '<p>')
      .replace(/<\/p>\s*<\/p>/gi, '</p>')
      // Clean up empty nested tags
      .replace(/<p[^>]*>\s*<\/p>/gi, '')
      // Normalize paragraph tags
      .replace(/<p[^>]*>/gi, '<p>')
      // Normalize heading tags
      .replace(/<h([1-6])[^>]*>/gi, '<h$1>')
      // Normalize formatting tags
      .replace(/<strong[^>]*>/gi, '<strong>')
      .replace(/<em[^>]*>/gi, '<em>')
      .replace(/<br[^>]*\/?>/gi, '<br>')
      // Clean up multiple spaces and newlines
      .replace(/\s+/g, ' ')
      .trim();
    
    // If the result is empty or just whitespace, return empty string
    if (!cleanContent || cleanContent.match(/^<p>\s*<\/p>$/) || cleanContent.match(/^\s*$/)) {
      return '';
    }
    
    return cleanContent;
  };

  // Initialize WordPress editor ONCE on mount
  useEffect(() => {
    const initializeEditor = async () => {
      console.log('🚀 Initializing Complete WordPress Editor...');
      
      try {
        // Check all required WordPress APIs
        const requiredAPIs = [
          'wp',
          'wp.blockEditor',
          'wp.blocks',
          'wp.element',
          'wp.data',
          'wp.components'
        ];

        const missingAPIs = requiredAPIs.filter(api => {
          const parts = api.split('.');
          let obj = window;
          for (const part of parts) {
            if (!obj[part]) return true;
            obj = obj[part];
          }
          return false;
        });

        if (missingAPIs.length > 0) {
          throw new Error(`Missing WordPress APIs: ${missingAPIs.join(', ')}`);
        }

        // Register core blocks if not already registered
        const existingBlocks = window.wp.blocks.getBlockTypes();
        console.log('📦 Checking block registration...', { existingBlockCount: existingBlocks.length });
        
        if (existingBlocks.length === 0) {
          console.log('📦 Registering WordPress core blocks...');
          
          // Try different methods to register core blocks
          if (window.wp.blocks.registerCoreBlocks && typeof window.wp.blocks.registerCoreBlocks === 'function') {
            window.wp.blocks.registerCoreBlocks();
            console.log('✅ Core blocks registered via registerCoreBlocks');
          } else if (window.wp.blockLibrary?.registerCoreBlocks) {
            window.wp.blockLibrary.registerCoreBlocks();
            console.log('✅ Core blocks registered via blockLibrary');
          } else {
            // Manually register essential blocks for email
            console.log('⚠️ No automatic block registration available, registering essential blocks manually');
            
            // Register essential blocks manually if available
            const essentialBlocks = [
              'core/paragraph',
              'core/heading', 
              'core/list',
              'core/quote',
              'core/separator',
              'core/spacer',
              'core/buttons',
              'core/button'
            ];
            
            let registeredCount = 0;
            essentialBlocks.forEach(blockName => {
              try {
                // Check if block is already registered
                if (!window.wp.blocks.getBlockType(blockName)) {
                  // Try to get block definition and register it
                  // This is a fallback - most blocks should be auto-registered
                  console.log(`📝 Block ${blockName} not found, skipping manual registration`);
                } else {
                  registeredCount++;
                }
              } catch (error) {
                console.warn(`⚠️ Could not register block ${blockName}:`, error.message);
              }
            });
            
            console.log(`📊 ${registeredCount} essential blocks already available`);
          }
        } else {
          console.log(`✅ ${existingBlocks.length} blocks already registered`);
        }

        // Initialize interface store for sidebar management (optional)
        try {
          if (window.wp.data.select('core/interface')) {
            // Use modern interface scope to avoid deprecated warnings
            window.wp.data.dispatch('core/interface').enableComplementaryArea(
              'core',
              'edit-post/block'
            );
          }
        } catch (error) {
          console.log('ℹ️ Interface store not available, continuing without sidebar integration');
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
          try {
            if (window.wp.blocks.getBlockType(blockName)) {
              console.log('🚫 Unregistering problematic block:', blockName);
              window.wp.blocks.unregisterBlockType(blockName);
            }
          } catch (error) {
            console.warn('⚠️ Could not unregister block:', blockName, error.message);
          }
        });

        // Parse existing content into blocks with enhanced validation
        let initialBlocks = [];
        if (value && value.trim()) {
          try {
            console.log('🔍 Processing content:', value);
            
            // Clean and validate content before parsing
            let processedValue = value.trim();
            
            // Check if content is already in block format
            if (processedValue.includes('<!-- wp:')) {
              console.log('📦 Content already contains WordPress blocks, parsing...');
              
              try {
                // Clean up any email-safe styles that might be in the block content
                let cleanBlockContent = processedValue
                  // Remove inline styles from HTML elements
                  .replace(/\s+style="[^"]*"/gi, '')
                  // Clean up WordPress block comments that might have been modified
                  .replace(/<!--\s*wp:(\w+(?:\/\w+)?)\s*({[^}]*})?\s*-->/gi, '<!-- wp:$1$2 -->')
                  .replace(/<!--\s*\/wp:(\w+(?:\/\w+)?)\s*-->/gi, '<!-- /wp:$1 -->')
                  // Fix malformed HTML: content outside of tags + extra closing tags
                  .replace(/<p[^>]*>(.*?)<\/p>([^<]*)<\/p>/gi, '<p>$1$2</p>')
                  // Fix nested paragraph tags that create invalid HTML structure
                  .replace(/<p[^>]*>\s*<p[^>]*>(.*?)<\/p>\s*<\/p>/gi, '<p>$1</p>')
                  // Remove any nested paragraph tags inside headings
                  .replace(/<h([1-6])[^>]*>\s*<p[^>]*>(.*?)<\/p>\s*<\/h[1-6]>/gi, '<h$1>$2</h$1>')
                  // Clean up any remaining nested paragraph structures
                  .replace(/<p[^>]*>\s*<p[^>]*>/gi, '<p>')
                  .replace(/<\/p>\s*<\/p>/gi, '</p>')
                  // Fix content outside paragraph tags that got orphaned
                  .replace(/(<p[^>]*>.*?<\/p>)([^<\s]+)(<\/p>)/gi, '<p>$2</p>')
                  // Remove empty paragraphs
                  .replace(/<p[^>]*>\s*<\/p>/gi, '')
                  .trim();
                
                console.log('🧹 Cleaned block content:', cleanBlockContent);
                
                const parsedBlocks = window.wp.blocks.parse(cleanBlockContent);
                console.log('📦 Parsed existing blocks:', parsedBlocks);
                
                // Validate each block and fix any issues
                initialBlocks = parsedBlocks.map(block => {
                  // Check if block type exists and is registered
                  const blockType = window.wp.blocks.getBlockType(block.name);
                  if (!blockType) {
                    console.log(`⚠️ Block type ${block.name} not registered, converting to paragraph`);
                    return window.wp.blocks.createBlock('core/paragraph', { 
                      content: extractTextContent(block.originalContent || block.innerHTML || '')
                    });
                  }
                  
                  // Clean the block content and attributes
                  let cleanAttributes = { ...block.attributes };
                  
                  // For text-based blocks, ensure content is properly structured
                  if (block.name === 'core/paragraph') {
                    // For paragraphs, content should be clean text without wrapping <p> tags
                    if (cleanAttributes.content) {
                      let content = cleanAttributes.content;
                      // Remove any paragraph wrapping from the content attribute
                      content = content.replace(/^<p[^>]*>/, '').replace(/<\/p>$/, '');
                      cleanAttributes.content = content;
                    }
                  } else if (block.name === 'core/heading') {
                    // For headings, content should be clean text without wrapping heading tags
                    if (cleanAttributes.content) {
                      let content = cleanAttributes.content;
                      // Remove any heading wrapping from the content attribute
                      content = content.replace(/^<h[1-6][^>]*>/, '').replace(/<\/h[1-6]>$/, '');
                      cleanAttributes.content = content;
                    }
                  }
                  
                  // Validate block attributes against schema
                  try {
                    const validatedBlock = window.wp.blocks.createBlock(
                      block.name, 
                      cleanAttributes, 
                      block.innerBlocks
                    );
                    console.log(`✅ Validated block: ${block.name}`);
                    return validatedBlock;
                  } catch (validationError) {
                    console.log(`⚠️ Block validation failed for ${block.name}, creating fresh block`);
                    return window.wp.blocks.createBlock('core/paragraph', { 
                      content: extractTextContent(block.originalContent || block.innerHTML || '')
                    });
                  }
                }).filter(block => {
                  // Filter out problematic blocks
                  const isProblematic = [
                    'core/freeform',
                    'core/html',
                    'core/more',
                    'core/nextpage', 
                    'core/classic'
                  ].includes(block.name);
                  
                  if (isProblematic) {
                    console.log('🚫 Filtered out problematic block:', block.name);
                    return false;
                  }
                  return true;
                });
                
              } catch (parseError) {
                console.warn('⚠️ Failed to parse block content:', parseError);
                // Fall back to creating clean paragraph
                const cleanText = extractTextContent(processedValue);
                initialBlocks = [window.wp.blocks.createBlock('core/paragraph', { 
                  content: cleanText
                })];
              }
            } else {
              // Raw HTML content - convert to clean paragraph block
              console.log('📝 Converting raw HTML to paragraph block');
              
              // Clean HTML content for paragraph block using helper function
              const cleanContent = extractTextContent(processedValue);
              
              initialBlocks = [window.wp.blocks.createBlock('core/paragraph', { 
                content: cleanContent || ''
              })];
              console.log('✅ Created clean paragraph block from HTML');
            }
            
            // Ensure we have at least one block
            if (initialBlocks.length === 0) {
              console.log('📝 No valid blocks found, creating empty paragraph');
              initialBlocks = [window.wp.blocks.createBlock('core/paragraph')];
            }
            
            console.log('✅ Final processed blocks:', initialBlocks);
          } catch (error) {
            console.error('❌ Content processing failed:', error);
            initialBlocks = [window.wp.blocks.createBlock('core/paragraph', { 
              content: 'Content processing error. Please try again.'
            })];
          }
        } else {
          // Start with empty paragraph
          console.log('📝 No content provided, creating empty paragraph');
          initialBlocks = [window.wp.blocks.createBlock('core/paragraph')];
        }

        setBlocks(initialBlocks);
        setIsReady(true);
        console.log('✅ Complete WordPress Editor initialized!');

      } catch (error) {
        console.error('❌ Failed to initialize WordPress Editor:', error);
        setErrorMessage(error.message);
        setHasError(true);
        setIsReady(true);
      }
    };

    // Retry initialization
    let retries = 0;
    const maxRetries = 20;

    const tryInitialize = () => {
      retries++;
      
      if (window.wp?.blockEditor && window.wp?.blocks && window.wp?.element && window.wp?.components) {
        initializeEditor();
      } else if (retries < maxRetries) {
        console.log(`⏳ WordPress APIs not ready, retry ${retries}/${maxRetries}...`);
        setTimeout(tryInitialize, 500);
      } else {
        setErrorMessage('WordPress APIs not available after 10 seconds');
        setHasError(true);
        setIsReady(true);
      }
    };

    tryInitialize();
  }, []); // Only initialize once on mount, not on value changes

  // Handle initial content loading separately
  useEffect(() => {
    if (!isReady || !value) return;
    
    // Only update if the current blocks are empty or significantly different
    const currentContent = blocks.length > 0 ? window.wp?.blocks?.serialize(blocks) || '' : '';
    const hasSignificantChange = !currentContent || Math.abs(value.length - currentContent.length) > 50;
    
    if (hasSignificantChange) {
      console.log('🔄 Loading initial content into existing editor...');
      
      try {
        let initialBlocks = [];
        if (value && value.trim()) {
          if (value.includes('<!-- wp:')) {
            // Parse WordPress blocks
            const cleanContent = value.replace(/\s+style="[^"]*"/gi, '');
            initialBlocks = window.wp.blocks.parse(cleanContent);
          } else {
            // Convert HTML to paragraph block
            const cleanContent = extractTextContent(value);
            initialBlocks = [window.wp.blocks.createBlock('core/paragraph', { 
              content: cleanContent || ''
            })];
          }
        } else {
          initialBlocks = [window.wp.blocks.createBlock('core/paragraph')];
        }
        
        setBlocks(initialBlocks);
        console.log('✅ Initial content loaded into editor');
      } catch (error) {
        console.error('❌ Failed to load initial content:', error);
      }
    }
  }, [value, isReady]); // Only when value changes externally or editor becomes ready

  // Stabilize the block change handler using useCallback
  const handleBlocksChange = useCallback((newBlocks) => {
    try {
      console.log('🔄 Processing block changes:', newBlocks);
      console.log('🔄 Previous blocks count:', blocks.length, 'New blocks count:', newBlocks.length);
      
      // Check if blocks were added or removed
      if (newBlocks.length > blocks.length) {
        console.log('➕ Blocks added!');
      } else if (newBlocks.length < blocks.length) {
        console.log('➖ Blocks removed!');
      } else {
        console.log('✏️ Blocks modified!');
      }
      
      // Validate and filter blocks
      const validatedBlocks = newBlocks
        .filter(block => {
          // Filter out problematic blocks
          const isProblematic = [
            'core/freeform',
            'core/html',
            'core/more',
            'core/nextpage',
            'core/classic'
          ].includes(block.name);
          
          if (isProblematic) {
            console.log('🚫 Blocked problematic block from being added:', block.name);
            return false;
          }
          return true;
        })
        .map(block => {
          // Validate block structure
          try {
            const blockType = window.wp.blocks.getBlockType(block.name);
            if (!blockType) {
              console.log(`⚠️ Unknown block type ${block.name}, converting to paragraph`);
              return window.wp.blocks.createBlock('core/paragraph', { 
                content: extractTextContent(block.originalContent || block.innerHTML || '')
              });
            }
            
            // Clean block attributes to remove any invalid data
            let cleanAttributes = { ...block.attributes };
            
            // For text-based blocks, ensure content is clean
            if ((block.name === 'core/paragraph' || block.name === 'core/heading') && cleanAttributes.content) {
              cleanAttributes.content = extractTextContent(cleanAttributes.content);
            }
            
            // Ensure block has valid attributes
            const validatedBlock = window.wp.blocks.createBlock(
              block.name,
              cleanAttributes,
              block.innerBlocks || []
            );
            
            return validatedBlock;
          } catch (validationError) {
            console.warn(`⚠️ Block validation failed for ${block.name}:`, validationError);
            return window.wp.blocks.createBlock('core/paragraph', { 
              content: extractTextContent(block.originalContent || block.innerHTML || '')
            });
          }
        });
      
      // Ensure we always have at least one block
      if (validatedBlocks.length === 0) {
        validatedBlocks.push(window.wp.blocks.createBlock('core/paragraph'));
      }
      
      console.log('✅ Validated blocks:', validatedBlocks);
      setBlocks(validatedBlocks);
      
      if (onChange && window.wp?.blocks) {
        try {
          // Store clean block markup (without email styles) for WordPress compatibility
          const cleanBlockMarkup = window.wp.blocks.serialize(validatedBlocks);
          console.log('📦 Clean block markup:', cleanBlockMarkup);
          
          // For the onChange callback, provide email-safe HTML for email rendering
          const emailSafeHTML = makeEmailSafe(cleanBlockMarkup);
          
          // Store both versions - clean blocks for editor, email-safe for rendering
          const contentData = {
            blocks: cleanBlockMarkup,        // Clean WordPress blocks for editor
            emailHTML: emailSafeHTML        // Email-safe HTML for rendering
          };
          
          // For now, just pass the clean blocks to maintain WordPress compatibility
          // The email-safe transformation will happen at render time
          onChange(cleanBlockMarkup);
          console.log('✅ Stored clean block markup for WordPress compatibility');
        } catch (serializeError) {
          console.error('❌ Failed to serialize blocks:', serializeError);
        }
      }
      
    } catch (error) {
      console.error('❌ Block change handling failed:', error);
      // Fallback to current blocks to prevent breaking
      setBlocks(blocks);
    }
  }, [blocks, onChange]); // Stabilize with necessary dependencies

  // Convert HTML to email-safe format
  const makeEmailSafe = (html) => {
    if (!html) return '';
    
    return html
      // Paragraph styling
      .replace(/<p>/g, `<p style="margin: 0 0 12px 0; line-height: ${lineHeight};">`)
      .replace(/<p\s+class="[^"]*"/g, match => match.replace(/class="[^"]*"/, ''))
      
      // Heading styling
      .replace(/<h([1-6])>/g, '<h$1 style="margin: 0 0 16px 0; font-weight: bold;">')
      .replace(/<h([1-6])\s+class="[^"]*"/g, (match, level) => 
        `<h${level} style="margin: 0 0 16px 0; font-weight: bold;"`)
      
      // List styling
      .replace(/<ul>/g, '<ul style="margin: 0 0 16px 0; padding-left: 20px;">')
      .replace(/<ol>/g, '<ol style="margin: 0 0 16px 0; padding-left: 20px;">')
      .replace(/<li>/g, '<li style="margin-bottom: 8px;">')
      
      // Button styling
      .replace(/<a\s+class="[^"]*wp-block-button__link[^"]*"/g, match => 
        match.replace(/class="[^"]*"/, 'style="display: inline-block; padding: 10px 20px; background-color: #0073aa; color: #ffffff; text-decoration: none; border-radius: 4px;"'))
      
      // Remove WordPress-specific classes
      .replace(/class="[^"]*wp-block[^"]*"/g, '')
      .replace(/class="[^"]*has-[^"]*"/g, '')
      .replace(/\s+class=""\s*/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  };

  // Complete WordPress Editor Component
  const WordPressEditorInterface = () => {
    const editorRef = useRef(null);
    const rootRef = useRef(null);

    useEffect(() => {
      if (!editorRef.current || !window.wp?.element || hasError) return;

      try {
        const { createElement } = window.wp.element;
        
        // Add error boundary for problematic blocks
        const createErrorBoundary = (children) => {
          return createElement(
            'div',
            {
              onError: (error) => {
                if (error.message && error.message.includes('tinymce')) {
                  console.warn('⚠️ TinyMCE-related error caught and handled:', error.message);
                  return true; // Prevent error propagation
                }
                return false;
              }
            },
            children
          );
        };
        const { 
          SlotFillProvider, 
          Popover, 
          Button,
          Panel,
          PanelBody,
          ToolbarGroup,
          ToolbarButton 
        } = window.wp.components;
        const { 
          BlockEditorProvider,
          BlockList,
          BlockInspector,
          Inserter,
          WritingFlow,
          ObserveTyping,
          BlockSelectionClearer,
          MultiSelectScrollIntoView,
          BlockBreadcrumb,
          BlockToolbar
        } = window.wp.blockEditor;

        // Check if interface components are available (optional for complete experience)
        const hasInterface = window.wp.interface && window.wp.interface.InterfaceTemplate;

        // Complete editor settings (like WordPress post editor)
        // Filter out problematic blocks that require TinyMCE or complex dependencies
        const emailSafeBlocks = [
          'core/paragraph',
          'core/heading',
          'core/list',
          'core/image',
          'core/quote',
          'core/separator',
          'core/spacer',
          'core/buttons',
          'core/button',
          'core/group',
          'core/columns',
          'core/column'
          // Removed: core/freeform (Classic block), core/html, core/more
        ];

        const editorSettings = {
          alignWide: true,
          allowedBlockTypes: emailSafeBlocks,
          bodyPlaceholder: placeholder,
          hasFixedToolbar: false,
          focusMode: false,
          codeEditingEnabled: false,
          canLockBlocks: false,
          // Block patterns and categories
          __experimentalBlockPatterns: [],
          __experimentalBlockPatternCategories: [],
          // Inserter configuration
          __experimentalBlockDirectory: false, // Disable block directory to reduce clutter
          __experimentalInsertionPoint: true,
          __experimentalInserterMediaCategories: [],
          // Custom block recovery
          __experimentalBlockInvalidContent: 'recover',
          // Disable features that might cause issues
          __experimentalFeatures: {
            typography: {
              fontFamilies: false,
              fontSizes: false
            },
            color: {
              background: false,
              text: false,
              link: false
            },
            spacing: {
              padding: false,
              margin: false
            },
            border: {
              color: false,
              radius: false,
              style: false,
              width: false
            }
          },
          // Block validation and recovery handlers
          __experimentalCanUserUseUnfilteredHTML: false,
          // Media settings
          mediaUpload: false,
          // Template settings
          templateLock: false,
          // Block editor specific settings
          hasUploadPermissions: false,
          allowedMimeTypes: {},
          maxUploadFileSize: 0
        };

        // Main editor interface
        const editorInterface = createElement(
          SlotFillProvider,
          {},
          [
            // Popover slot for tooltips and modals
            createElement(Popover.Slot, { key: 'popover-slot' }),
            
            // Main editor container
            createElement(
              'div',
              {
                key: 'editor-interface',
                className: 'edit-post-layout__content',
                style: {
                  display: 'flex',
                  flexDirection: 'column',
                  height: '500px',
                  border: '1px solid #ddd',
                  borderRadius: '4px',
                  backgroundColor: '#fff'
                }
              },
              [
                // Editor Header with Inserter
                createElement(
                  'div',
                  {
                    key: 'editor-header',
                    className: 'edit-post-header',
                    style: {
                      padding: '8px 16px',
                      borderBottom: '1px solid #ddd',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '8px',
                      backgroundColor: '#f9f9f9'
                    }
                  },
                  [
                    // Block Inserter Button
                    createElement(Inserter, {
                      key: 'inserter',
                      position: 'bottom right',
                      showInserterHelpPanel: true,
                      __experimentalIsQuick: false,
                      // Provide close handler for modal
                      onToggle: (isOpen) => {
                        console.log('🔧 Inserter toggled:', isOpen);
                      },
                      renderToggle: ({ onToggle, isOpen }) =>
                        createElement(Button, {
                          key: 'inserter-button',
                          onClick: () => {
                            console.log('🔧 Add Block clicked');
                            if (onToggle) {
                              onToggle();
                            }
                          },
                          className: 'edit-post-header-toolbar__inserter-toggle',
                          isPrimary: true,
                          isPressed: isOpen,
                          'aria-expanded': isOpen,
                          style: { marginRight: '8px' }
                        }, '+ Add Block')
                    }),
                    
                    // Block Inspector Toggle
                    createElement(Button, {
                      key: 'inspector-toggle',
                      onClick: () => setSidebarOpen(!sidebarOpen),
                      isPressed: sidebarOpen,
                      icon: 'admin-generic',
                      label: 'Block Settings'
                    }, 'Block Settings')
                  ]
                ),

                // Main content area with optional sidebar
                createElement(
                  'div',
                  {
                    key: 'editor-content',
                    style: {
                      display: 'flex',
                      flex: 1,
                      overflow: 'hidden'
                    }
                  },
                  [
                    // Editor Canvas
                    createElement(
                      'div',
                      {
                        key: 'editor-canvas',
                        className: 'edit-post-layout__canvas',
                        style: {
                          flex: sidebarOpen ? '1' : '1',
                          display: 'flex',
                          flexDirection: 'column',
                          overflow: 'hidden'
                        }
                      },
                      [
                        // Block Editor Provider
                        createElement(
                          BlockEditorProvider,
                          {
                            key: 'block-editor-provider',
                            value: blocks,
                            onInput: (newBlocks) => {
                              console.log('📝 Block input change:', newBlocks);
                              handleBlocksChange(newBlocks);
                            },
                            onChange: (newBlocks) => {
                              console.log('📝 Block change:', newBlocks);
                              handleBlocksChange(newBlocks);
                            },
                            settings: editorSettings,
                            useSubRegistry: false
                          },
                          [
                            // Block Toolbar
                            createElement(
                              'div',
                              {
                                key: 'block-toolbar-container',
                                style: {
                                  position: 'sticky',
                                  top: 0,
                                  background: '#fff',
                                  borderBottom: '1px solid #eee',
                                  padding: '8px 16px',
                                  zIndex: 20
                                }
                              },
                              createElement(BlockToolbar, { key: 'block-toolbar' })
                            ),

                            // Writing area
                            createElement(
                              'div',
                              {
                                key: 'writing-area',
                                className: 'edit-post-layout__canvas-content',
                                style: {
                                  flex: 1,
                                  padding: '16px',
                                  overflow: 'auto',
                                  position: 'relative'
                                }
                              },
                              [
                                createElement(BlockSelectionClearer, { key: 'selection-clearer' }),
                                // Remove MultiSelectScrollIntoView as it's deprecated and built-in
                                createElement(
                                  WritingFlow,
                                  { key: 'writing-flow' },
                                  createElement(
                                    ObserveTyping,
                                    { key: 'observe-typing' },
                                    createElement(BlockList, { key: 'block-list' })
                                  )
                                )
                              ]
                            ),

                            // Block Breadcrumb
                            createElement(
                              'div',
                              {
                                key: 'block-breadcrumb-container',
                                style: {
                                  padding: '8px 16px',
                                  borderTop: '1px solid #eee',
                                  backgroundColor: '#f9f9f9'
                                }
                              },
                              createElement(BlockBreadcrumb, { key: 'block-breadcrumb' })
                            )
                          ]
                        )
                      ]
                    ),

                    // Block Inspector Sidebar
                    sidebarOpen && createElement(
                      'div',
                      {
                        key: 'sidebar',
                        className: 'edit-post-sidebar',
                        style: {
                          width: '280px',
                          borderLeft: '1px solid #ddd',
                          backgroundColor: '#f9f9f9',
                          overflow: 'auto'
                        }
                      },
                      [
                        createElement(
                          'div',
                          {
                            key: 'sidebar-header',
                            style: {
                              padding: '16px',
                              borderBottom: '1px solid #ddd',
                              display: 'flex',
                              justifyContent: 'space-between',
                              alignItems: 'center'
                            }
                          },
                          [
                            createElement('h2', { key: 'sidebar-title', style: { margin: 0, fontSize: '14px' } }, 'Block Settings'),
                            createElement(Button, {
                              key: 'sidebar-close',
                              onClick: () => setSidebarOpen(false),
                              icon: 'no-alt',
                              label: 'Close'
                            })
                          ]
                        ),
                        createElement(
                          'div',
                          {
                            key: 'sidebar-content',
                            style: { padding: '16px' }
                          },
                          createElement(BlockInspector, { key: 'block-inspector' })
                        )
                      ]
                    )
                  ]
                )
              ]
            )
          ]
        );

        // Render using WordPress React
        if (window.wp.element.createRoot) {
          rootRef.current = window.wp.element.createRoot(editorRef.current);
          rootRef.current.render(editorInterface);
        } else if (window.wp.element.render) {
          window.wp.element.render(editorInterface, editorRef.current);
        }

      } catch (error) {
        console.error('❌ Failed to render WordPress Editor Interface:', error);
        setErrorMessage(`Render error: ${error.message}`);
        setHasError(true);
      }
    }, [blocks, sidebarOpen, hasError]);

    // Cleanup
    useEffect(() => {
      return () => {
        try {
          if (rootRef.current) {
            rootRef.current.unmount();
          } else if (editorRef.current && window.wp?.element?.unmountComponentAtNode) {
            window.wp.element.unmountComponentAtNode(editorRef.current);
          }
          
          if (editorRef.current) {
            editorRef.current.innerHTML = '';
          }
        } catch (error) {
          console.error('Cleanup error:', error);
        }
      };
    }, []);

    return <div ref={editorRef} style={{ width: '100%', height: '100%' }} />;
  };

  // Fallback editor
  const FallbackEditor = () => (
    <div style={{ padding: '16px' }}>
      <div style={{
        padding: '12px',
        background: '#f8d7da',
        border: '1px solid #f5c6cb',
        borderRadius: '4px',
        color: '#721c24',
        fontSize: '14px',
        marginBottom: '12px'
      }}>
        ❌ Complete WordPress Editor Error: {errorMessage}
      </div>
      <textarea
        value={typeof value === 'string' ? value : ''}
        onChange={(e) => onChange && onChange(makeEmailSafe(e.target.value))}
        placeholder={placeholder}
        style={{
          width: '100%',
          minHeight: '400px',
          padding: '12px',
          border: '1px solid #ddd',
          borderRadius: '4px',
          fontSize: '14px',
          lineHeight: lineHeight,
          fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
          resize: 'vertical'
        }}
      />
    </div>
  );

  return (
    <div className={`full-wordpress-editor ${className}`}>
      <div style={{ 
        background: '#f9f9f9', 
        border: '1px solid #ddd', 
        borderBottom: isReady && !hasError ? 'none' : '1px solid #ddd', 
        padding: '8px',
        borderRadius: isReady && !hasError ? '3px 3px 0 0' : '3px',
        fontSize: '12px',
        color: '#666',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between'
      }}>
        <span>🎯 Complete WordPress Block Editor</span>
        {!isReady && (
          <span style={{ fontSize: '11px', opacity: 0.7 }}>
            Initializing complete editor interface...
          </span>
        )}
      </div>
      
      {!isReady ? (
        <div style={{
          border: '1px solid #ddd',
          borderTop: 'none',
          borderRadius: '0 0 3px 3px',
          padding: '40px',
          textAlign: 'center',
          background: '#fff',
          color: '#666'
        }}>
          <p>🔄 Loading Complete WordPress Editor...</p>
          <p style={{ fontSize: '12px', marginTop: '8px' }}>
            Initializing block library, inserter, inspector, and all WordPress features...
          </p>
        </div>
      ) : hasError ? (
        <div style={{ 
          border: '1px solid #ddd',
          borderTop: 'none',
          borderRadius: '0 0 3px 3px'
        }}>
          <FallbackEditor />
        </div>
      ) : (
        <div style={{ 
          background: '#fff',
          border: '1px solid #ddd',
          borderTop: 'none',
          borderRadius: '0 0 3px 3px',
          height: '500px'
        }}>
          <WordPressEditorInterface />
        </div>
      )}

      {/* Email HTML Preview (for debugging) */}
      {process.env.NODE_ENV !== 'production' && (
        <details style={{ marginTop: '8px', fontSize: '12px', color: '#666' }}>
          <summary style={{ cursor: 'pointer' }}>Email HTML Preview (Complete Editor)</summary>
          <pre style={{ 
            marginTop: '8px', 
            padding: '8px', 
            background: '#f5f5f5', 
            borderRadius: '3px', 
            fontSize: '11px',
            overflow: 'auto',
            whiteSpace: 'pre-wrap'
          }}>
            {blocks.length > 0 && window.wp?.blocks ? 
              makeEmailSafe(window.wp.blocks.serialize(blocks)) : 
              'No blocks to preview'
            }
          </pre>
        </details>
      )}
    </div>
  );
}

export default FullWordPressEditor;