import React, { useRef, useEffect, useState, useLayoutEffect } from 'react';

/**
 * WordPress Gutenberg Block Editor Integration
 * Provides full WordPress block editor experience for email content creation
 */
function EmailGutenbergEditor({ 
  value = '', 
  onChange, 
  placeholder = 'Start writing or type / to insert blocks...',
  className = '',
  lineHeight = 1.6
}) {
  const [editorType, setEditorType] = useState(null);
  const [isReady, setIsReady] = useState(false);
  const [editorContent, setEditorContent] = useState(value);

  // Detect available editor type with priority for full Gutenberg
  useEffect(() => {
    const detectEditorType = async () => {
      // Debug what's actually available
      console.log('🔍 WordPress API Detection:', {
        wpExists: typeof window.wp !== 'undefined',
        wpKeys: window.wp ? Object.keys(window.wp) : 'wp not found',
        wpElement: typeof window.wp?.element !== 'undefined',
        wpBlocks: typeof window.wp?.blocks !== 'undefined',
        wpBlockEditor: typeof window.wp?.blockEditor !== 'undefined',
        wpData: typeof window.wp?.data !== 'undefined',
        wpComponents: typeof window.wp?.components !== 'undefined',
        wpEditor: typeof window.wp?.editor !== 'undefined'
      });

      // Wait for WordPress scripts to load
      let retries = 0;
      const maxRetries = 20; // 10 seconds total
      
      const checkWordPress = () => {
        retries++;
        
        // Check for full WordPress block editor (Gutenberg)
        if (window.wp && window.wp.blockEditor && window.wp.blocks && window.wp.element) {
          console.log('✅ Full WordPress Gutenberg block editor available');
          setEditorType('gutenberg');
          setIsReady(true);
          return;
        } 
        
        if (window.wp && window.wp.editor && typeof window.wp.editor.initialize === 'function') {
          console.log('✅ WordPress TinyMCE editor available');
          setEditorType('tinymce');
          setIsReady(true);
          return;
        } 
        
        if (window.wp && window.wp.blocks) {
          console.log('✅ WordPress blocks available, using simplified block editor');
          setEditorType('blocks');
          setIsReady(true);
          return;
        }
        
        // If we haven't found WordPress APIs and haven't exceeded retries, try again
        if (retries < maxRetries) {
          console.log(`⏳ WordPress APIs not ready yet, retry ${retries}/${maxRetries}...`);
          setTimeout(checkWordPress, 500);
          return;
        }
        
        // Fallback to simple editor
        console.log('ℹ️ WordPress APIs not available, using enhanced text editor');
        setEditorType('simple');
        setIsReady(true);
      };

      checkWordPress();
    };

    detectEditorType();
  }, []);

  // Make HTML email-safe
  const makeEmailSafe = (html) => {
    let emailHTML = html;
    
    // Convert to inline styles for email compatibility
    emailHTML = emailHTML
      // Paragraph styling
      .replace(/<p>/g, `<p style="margin: 0 0 12px 0; line-height: ${lineHeight};">`)
      .replace(/<p\s+style="([^"]*)">/g, (match, styles) => {
        // Merge existing styles with email-safe styles
        const hasMargin = styles.includes('margin');
        const hasLineHeight = styles.includes('line-height');
        let newStyles = styles;
        if (!hasMargin) newStyles += '; margin: 0 0 12px 0';
        if (!hasLineHeight) newStyles += `; line-height: ${lineHeight}`;
        return `<p style="${newStyles}">`;
      })
      
      // Heading styling
      .replace(/<h([1-6])>/g, '<h$1 style="margin: 0 0 16px 0; font-weight: bold;">')
      .replace(/<h([1-6])\s+style="([^"]*)">/g, (match, level, styles) => {
        const hasMargin = styles.includes('margin');
        const hasFontWeight = styles.includes('font-weight');
        let newStyles = styles;
        if (!hasMargin) newStyles += '; margin: 0 0 16px 0';
        if (!hasFontWeight) newStyles += '; font-weight: bold';
        return `<h${level} style="${newStyles}">`;
      })
      
      // List styling
      .replace(/<ul>/g, '<ul style="margin: 0 0 16px 0; padding-left: 20px;">')
      .replace(/<ol>/g, '<ol style="margin: 0 0 16px 0; padding-left: 20px;">')
      .replace(/<li>/g, '<li style="margin-bottom: 8px;">')
      
      // Image styling
      .replace(/<img([^>]*?)>/g, (match, attrs) => {
        if (!attrs.includes('style=')) {
          return `<img${attrs} style="max-width: 100%; height: auto;" />`;
        }
        return match;
      })
      
      // Figure styling
      .replace(/<figure>/g, '<figure style="margin: 0 0 16px 0;">')
      .replace(/<figcaption>/g, '<figcaption style="font-size: 14px; color: #666; margin-top: 8px; text-align: center;">')
      
      // Clean up extra spaces
      .replace(/\s+/g, ' ')
      .trim();
    
    return emailHTML;
  };

  // Handle editor content changes
  const handleEditorChange = (content) => {
    setEditorContent(content);
    
    // Convert to email-safe HTML
    const emailSafeHTML = makeEmailSafe(content);
    
    if (onChange) {
      onChange(emailSafeHTML);
    }
  };

  // React-managed editor components - no manual DOM manipulation
  
  // Native WordPress Gutenberg Block Editor Component
  const GutenbergBlockEditor = () => {
    const editorRef = useRef(null);
    const [isGutenbergReady, setIsGutenbergReady] = useState(false);

    useLayoutEffect(() => {
      let isMounted = true;
      let editorInstance = null;

      const initializeNativeGutenberg = async () => {
        if (!editorRef.current || !window.wp) return;

        try {
          console.log('🔧 Initializing native WordPress block editor...');
          console.log('🔍 Available WordPress APIs:', {
            'wp': !!window.wp,
            'wp.editor': !!window.wp?.editor,
            'wp.editor.initialize': !!window.wp?.editor?.initialize,
            'wp.blockEditor': !!window.wp?.blockEditor,
            'wp.blocks': !!window.wp?.blocks,
            'wp.element': !!window.wp?.element,
            'tinymce': !!window.tinymce
          });
          
          // Clear any existing content
          editorRef.current.innerHTML = '';
          
          // Create a unique editor ID
          const editorId = `wp-gutenberg-editor-${Date.now()}`;
          
          // Create the editor textarea
          const textarea = document.createElement('textarea');
          textarea.id = editorId;
          textarea.className = 'wp-editor-area';
          textarea.style.width = '100%';
          textarea.style.minHeight = '300px';
          textarea.value = value || '';
          
          // Create editor wrapper div
          const editorWrapper = document.createElement('div');
          editorWrapper.className = 'wp-editor-wrap';
          editorWrapper.appendChild(textarea);
          editorRef.current.appendChild(editorWrapper);

          // Initialize WordPress block editor
          if (window.wp.editor && window.wp.editor.initialize) {
            console.log('🎯 Using wp.editor.initialize...');
            
            try {
              window.wp.editor.initialize(editorId, {
                tinymce: {
                  wpautop: true,
                  plugins: 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview',
                  toolbar1: 'bold,italic,strikethrough,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,wp_adv',
                  toolbar2: 'formatselect,underline,alignjustify,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                  setup: function(editor) {
                    editor.on('change input keyup', function() {
                      if (isMounted) {
                        const content = editor.getContent();
                        const emailSafeHTML = makeEmailSafe(content);
                        handleEditorChange(emailSafeHTML);
                      }
                    });
                  }
                },
                quicktags: {
                  buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close'
                },
                mediaButtons: false
              });
              console.log('✅ wp.editor.initialize completed successfully');
            } catch (error) {
              console.error('❌ wp.editor.initialize failed:', error);
              throw error;
            }
            
          } else if (window.wp.blockEditor && window.wp.blocks) {
            console.log('🎯 Using native WordPress block editor APIs...');
            
            // Parse existing content into blocks
            let initialBlocks = [];
            if (value && value.trim()) {
              try {
                initialBlocks = window.wp.blocks.parse(value);
              } catch (error) {
                console.warn('Failed to parse content, creating paragraph block');
                initialBlocks = [window.wp.blocks.createBlock('core/paragraph', { content: value })];
              }
            } else {
              initialBlocks = [window.wp.blocks.createBlock('core/paragraph')];
            }

            // Use WordPress's own block editor initialization
            const { createElement } = window.wp.element;
            const { render } = window.wp.element;
            const { select, dispatch } = window.wp.data;
            
            // Create block editor store
            const registry = window.wp.data.createRegistry();
            
            // Create the editor
            const BlockEditorComponent = createElement(
              window.wp.blockEditor.BlockEditorProvider,
              {
                value: initialBlocks,
                onChange: (blocks) => {
                  if (isMounted) {
                    const serializedHTML = window.wp.blocks.serialize(blocks);
                    const emailSafeHTML = makeEmailSafe(serializedHTML);
                    handleEditorChange(emailSafeHTML);
                  }
                },
                settings: {
                  hasFixedToolbar: false,
                  focusMode: false,
                  hasInlineToolbar: true,
                  allowedBlockTypes: [
                    'core/paragraph',
                    'core/heading',
                    'core/list',
                    'core/quote',
                    'core/image',
                    'core/separator',
                    'core/spacer',
                    'core/buttons',
                    'core/button'
                  ]
                }
              },
              [
                // Block toolbar
                createElement(window.wp.blockEditor.BlockToolbar, { key: 'toolbar' }),
                // Main editor
                createElement(
                  'div',
                  { 
                    key: 'editor-content',
                    className: 'editor-content',
                    style: { minHeight: '300px', padding: '16px' }
                  },
                  [
                    createElement(window.wp.blockEditor.WritingFlow, { key: 'writing-flow' }, [
                      createElement(window.wp.blockEditor.ObserveTyping, { key: 'observe-typing' }, [
                        createElement(window.wp.blockEditor.BlockList, { key: 'block-list' })
                      ])
                    ]),
                    createElement(window.wp.blockEditor.Inserter, { 
                      key: 'inserter',
                      rootClientId: null,
                      isAppender: true
                    })
                  ]
                )
              ]
            );

            // Render the editor
            render(BlockEditorComponent, editorRef.current);
          }
          
          if (isMounted) {
            setIsGutenbergReady(true);
            console.log('✅ Native WordPress block editor initialized successfully');
          }

        } catch (error) {
          console.error('❌ Failed to initialize native Gutenberg editor:', error);
          console.error('Error details:', error.message, error.stack);
          
          // Fallback to simple but functional textarea
          if (isMounted && editorRef.current) {
            console.log('🔄 Falling back to simple textarea editor');
            
            const fallbackHTML = `
              <div style="padding: 16px; background: #fff;">
                <div style="margin-bottom: 10px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; font-size: 12px; color: #856404;">
                  ⚠️ WordPress block editor not available. Using fallback editor.
                </div>
                <textarea 
                  id="fallback-${Date.now()}"
                  style="width: 100%; min-height: 250px; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; line-height: 1.6; resize: vertical;" 
                  placeholder="${placeholder}"
                >${value || ''}</textarea>
              </div>
            `;
            
            editorRef.current.innerHTML = fallbackHTML;
            
            // Get the textarea and add event listener
            const textarea = editorRef.current.querySelector('textarea');
            if (textarea) {
              textarea.addEventListener('input', (e) => {
                if (isMounted) {
                  const emailSafeHTML = makeEmailSafe(e.target.value);
                  handleEditorChange(emailSafeHTML);
                }
              });
            }
            
            setIsGutenbergReady(true);
          }
        }
      };

      // Initialize after a short delay
      const timeoutId = setTimeout(initializeNativeGutenberg, 200);

      return () => {
        isMounted = false;
        clearTimeout(timeoutId);
        
        if (editorInstance) {
          try {
            if (window.wp.editor && window.wp.editor.remove) {
              window.wp.editor.remove(editorInstance);
            }
          } catch (error) {
            console.error('Editor cleanup error:', error);
          }
        }
        
        if (editorRef.current) {
          try {
            const { element } = window.wp || {};
            if (element && element.unmountComponentAtNode) {
              element.unmountComponentAtNode(editorRef.current);
            }
          } catch (error) {
            console.error('React cleanup error:', error);
          }
        }
      };
    }, [value]);

    return (
      <div className="native-gutenberg-editor-wrapper">
        <div style={{ 
          background: '#f9f9f9', 
          border: '1px solid #ddd', 
          borderBottom: isGutenbergReady ? 'none' : '1px solid #ddd', 
          padding: '8px',
          borderRadius: isGutenbergReady ? '3px 3px 0 0' : '3px',
          fontSize: '12px',
          color: '#666',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between'
        }}>
          <span>🎯 Native WordPress Block Editor</span>
          {!isGutenbergReady && (
            <span style={{ fontSize: '11px', opacity: 0.7 }}>
              Initializing native editor...
            </span>
          )}
        </div>
        
        {!isGutenbergReady ? (
          <div style={{
            border: '1px solid #ddd',
            borderTop: 'none',
            borderRadius: '0 0 3px 3px',
            padding: '40px',
            textAlign: 'center',
            background: '#fff',
            color: '#666'
          }}>
            <p>🔄 Loading Native WordPress Block Editor...</p>
            <p style={{ fontSize: '12px', marginTop: '8px' }}>
              Initializing wp.editor or wp.blockEditor...
            </p>
          </div>
        ) : (
          <div 
            ref={editorRef} 
            className="native-gutenberg-editor-container"
            style={{ 
              background: '#fff',
              minHeight: '300px',
              border: '1px solid #ddd',
              borderTop: 'none',
              borderRadius: '0 0 3px 3px'
            }}
          />
        )}
      </div>
    );
  };
  
  // TinyMCE Editor Component
  const TinyMCEEditor = () => {
    const textareaRef = useRef(null);
    const editorRef = useRef(null);
    
    useLayoutEffect(() => {
      let isMounted = true;
      
      if (textareaRef.current && window.wp && window.wp.editor) {
        const editorId = `tinymce-${Date.now()}`;
        textareaRef.current.id = editorId;
        
        try {
          window.wp.editor.initialize(editorId, {
            tinymce: {
              plugins: 'lists,paste,tabfocus,wordpress,wpautoresize',
              toolbar1: 'bold,italic,bullist,numlist,link,unlink,wp_more,spellchecker',
              setup: function(editor) {
                editorRef.current = editor;
                editor.on('init', function() {
                  if (isMounted) {
                    console.log('✅ TinyMCE initialized');
                    editor.setContent(value || '');
                  }
                });
                
                editor.on('change input keyup', function() {
                  if (isMounted) {
                    const content = editor.getContent();
                    handleEditorChange(content);
                  }
                });
              }
            },
            quicktags: false,
            mediaButtons: false
          });
        } catch (error) {
          console.error('TinyMCE initialization failed:', error);
        }
      }
      
      return () => {
        isMounted = false;
        if (editorRef.current) {
          try {
            editorRef.current.remove();
          } catch (error) {
            console.error('TinyMCE cleanup error:', error);
          }
        }
      };
    }, []);
    
    return (
      <div className="tinymce-editor-wrapper">
        <div style={{ 
          background: '#f9f9f9', 
          border: '1px solid #ddd', 
          borderBottom: 'none', 
          padding: '8px',
          borderRadius: '3px 3px 0 0',
          fontSize: '12px',
          color: '#666'
        }}>
          ✨ WordPress TinyMCE Editor
        </div>
        <textarea
          ref={textareaRef}
          className="wp-editor-area"
          style={{
            width: '100%',
            minHeight: '200px',
            border: '1px solid #ddd',
            borderRadius: '0 0 3px 3px',
            padding: '10px',
            fontSize: '13px',
            lineHeight: '19px'
          }}
          defaultValue={value}
          placeholder={placeholder}
        />
      </div>
    );
  };
  
  // Simple Text Editor Component
  const SimpleTextEditor = () => {
    const textareaRef = useRef(null);
    
    const handleTextChange = (e) => {
      handleEditorChange(e.target.value);
    };
    
    return (
      <div className="simple-editor-wrapper">
        <div style={{ 
          background: '#f9f9f9', 
          border: '1px solid #ddd', 
          borderBottom: 'none', 
          padding: '5px 8px',
          borderRadius: '3px 3px 0 0',
          fontSize: '12px',
          color: '#666'
        }}>
          <span style={{ marginRight: '10px' }}>📝 Enhanced Text Editor</span>
          <span style={{ fontSize: '11px', opacity: 0.7 }}>
            HTML supported
          </span>
        </div>
        <textarea
          ref={textareaRef}
          style={{
            width: '100%',
            minHeight: '200px',
            border: '1px solid #ddd',
            borderRadius: '0 0 3px 3px',
            padding: '10px',
            fontSize: '13px',
            lineHeight: '19px',
            fontFamily: 'Consolas, Monaco, monospace',
            resize: 'vertical'
          }}
          value={editorContent}
          onChange={handleTextChange}
          placeholder={placeholder}
          spellCheck={true}
        />
      </div>
    );
  };
  
  // Block Editor Component
  const BlockEditor = () => {
    const [blocks, setBlocks] = useState([]);
    const [showBlockInserter, setShowBlockInserter] = useState(false);
    
    const insertBlock = (blockType) => {
      const newBlock = {
        id: `block-${Date.now()}-${Math.random()}`,
        type: blockType,
        content: blockType === 'heading' ? 'New Heading' : 'New paragraph content'
      };
      setBlocks([...blocks, newBlock]);
      setShowBlockInserter(false);
      
      // Convert blocks to HTML
      const html = blocks.concat(newBlock).map(block => 
        block.type === 'heading' 
          ? `<h2>${block.content}</h2>` 
          : `<p>${block.content}</p>`
      ).join('');
      handleEditorChange(html);
    };
    
    const removeBlock = (blockId) => {
      const newBlocks = blocks.filter(block => block.id !== blockId);
      setBlocks(newBlocks);
      
      const html = newBlocks.map(block => 
        block.type === 'heading' 
          ? `<h2>${block.content}</h2>` 
          : `<p>${block.content}</p>`
      ).join('');
      handleEditorChange(html);
    };
    
    return (
      <div className="block-editor-wrapper">
        <div style={{ 
          background: '#f9f9f9', 
          border: '1px solid #ddd', 
          borderBottom: 'none', 
          padding: '8px',
          borderRadius: '3px 3px 0 0',
          fontSize: '12px',
          color: '#666',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between'
        }}>
          <span>🧩 Block Editor</span>
          <button
            onClick={() => setShowBlockInserter(!showBlockInserter)}
            style={{
              background: '#0073aa',
              color: 'white',
              border: 'none',
              borderRadius: '3px',
              padding: '4px 8px',
              fontSize: '11px',
              cursor: 'pointer'
            }}
          >
            + Add Block
          </button>
        </div>
        
        <div style={{
          border: '1px solid #ddd',
          borderRadius: '0 0 3px 3px',
          minHeight: '200px',
          background: '#fff'
        }}>
          {showBlockInserter && (
            <div style={{
              background: '#f0f0f0',
              padding: '10px',
              borderBottom: '1px solid #ddd'
            }}>
              <button 
                onClick={() => insertBlock('paragraph')}
                style={{ marginRight: '5px', padding: '5px 10px', cursor: 'pointer' }}
              >
                📄 Paragraph
              </button>
              <button 
                onClick={() => insertBlock('heading')}
                style={{ padding: '5px 10px', cursor: 'pointer' }}
              >
                📝 Heading
              </button>
            </div>
          )}
          
          <div style={{ padding: '10px' }}>
            {blocks.length === 0 ? (
              <div style={{ color: '#666', fontStyle: 'italic', padding: '20px', textAlign: 'center' }}>
                Click "+ Add Block" to start creating content
              </div>
            ) : (
              blocks.map(block => (
                <div 
                  key={block.id} 
                  style={{
                    border: '1px solid #e0e0e0',
                    borderRadius: '3px',
                    padding: '10px',
                    marginBottom: '10px',
                    background: '#fafafa'
                  }}
                >
                  <div style={{ 
                    display: 'flex', 
                    justifyContent: 'space-between', 
                    alignItems: 'center', 
                    marginBottom: '5px' 
                  }}>
                    <span style={{ fontSize: '11px', color: '#666', textTransform: 'capitalize' }}>
                      {block.type}
                    </span>
                    <button
                      onClick={() => removeBlock(block.id)}
                      style={{
                        background: 'none',
                        border: 'none',
                        color: '#d63638',
                        cursor: 'pointer',
                        fontSize: '11px'
                      }}
                    >
                      Remove
                    </button>
                  </div>
                  <div>
                    {block.type === 'heading' ? (
                      <h3 style={{ margin: 0 }}>{block.content}</h3>
                    ) : (
                      <p style={{ margin: 0 }}>{block.content}</p>
                    )}
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      </div>
    );
  };

  // Handle content changes when lineHeight prop changes
  useEffect(() => {
    if (editorContent && onChange) {
      // Re-convert content with new line height
      const emailSafeHTML = makeEmailSafe(editorContent);
      onChange(emailSafeHTML);
    }
  }, [lineHeight]);

  // State-based rendering - React controls all DOM structure
  return (
    <div className={`email-gutenberg-editor ${className}`}>
      {!isReady ? (
        <div style={{ padding: '40px', textAlign: 'center', border: '1px solid #ddd', borderRadius: '4px' }}>
          <p style={{ color: '#666', margin: 0 }}>
            🔍 Detecting available editor...
          </p>
        </div>
      ) : (
        <>
          {/* Render appropriate editor based on detected type */}
          {editorType === 'gutenberg' && <GutenbergBlockEditor />}
          {editorType === 'tinymce' && <TinyMCEEditor />}
          {editorType === 'simple' && <SimpleTextEditor />}
          {editorType === 'blocks' && <BlockEditor />}
          
          {/* Email HTML Preview (for debugging) */}
          {process.env.NODE_ENV !== 'production' && (
            <details className="ev2-mt-2 ev2-text-xs ev2-text-gray-500">
              <summary className="ev2-cursor-pointer">Email HTML Preview ({editorType})</summary>
              <pre className="ev2-mt-2 ev2-p-2 ev2-bg-gray-100 ev2-rounded ev2-text-xs ev2-overflow-auto">
                {makeEmailSafe(editorContent)}
              </pre>
            </details>
          )}
        </>
      )}
    </div>
  );
}

export default EmailGutenbergEditor;