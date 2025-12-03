import React, { useState, useEffect, useRef } from 'react';

/**
 * Full WordPress Block Editor Component
 * Uses @wordpress/block-editor with BlockEditorProvider for complete Gutenberg experience
 */
function WordPressBlockEditor({ 
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
  const editorRef = useRef(null);

  // Check WordPress APIs and initialize
  useEffect(() => {
    const initializeBlockEditor = () => {
      console.log('🔧 Initializing WordPress Block Editor...');
      console.log('🔍 Available WordPress APIs:', {
        wp: !!window.wp,
        blockEditor: !!window.wp?.blockEditor,
        blocks: !!window.wp?.blocks,
        element: !!window.wp?.element,
        data: !!window.wp?.data,
        components: !!window.wp?.components,
        BlockEditorProvider: !!window.wp?.blockEditor?.BlockEditorProvider,
        BlockList: !!window.wp?.blockEditor?.BlockList,
        WritingFlow: !!window.wp?.blockEditor?.WritingFlow,
        ObserveTyping: !!window.wp?.blockEditor?.ObserveTyping,
        BlockToolbar: !!window.wp?.blockEditor?.BlockToolbar,
        Inserter: !!window.wp?.blockEditor?.Inserter
      });

      // Check if all required WordPress APIs are available
      const requiredAPIs = [
        'wp',
        'wp.blockEditor',
        'wp.blocks', 
        'wp.element',
        'wp.data'
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
        const error = `Missing WordPress APIs: ${missingAPIs.join(', ')}`;
        console.error('❌', error);
        setErrorMessage(error);
        setHasError(true);
        setIsReady(true);
        return;
      }

      try {
        // Parse existing content into blocks
        let initialBlocks = [];
        if (value && value.trim()) {
          try {
            initialBlocks = window.wp.blocks.parse(value);
            console.log('✅ Parsed existing content into blocks:', initialBlocks);
          } catch (error) {
            console.warn('⚠️ Failed to parse existing content, creating paragraph block');
            initialBlocks = [window.wp.blocks.createBlock('core/paragraph', { content: value })];
          }
        } else {
          initialBlocks = [window.wp.blocks.createBlock('core/paragraph')];
        }

        setBlocks(initialBlocks);
        setIsReady(true);
        console.log('✅ WordPress Block Editor initialized successfully');

      } catch (error) {
        console.error('❌ Failed to initialize WordPress Block Editor:', error);
        setErrorMessage(error.message);
        setHasError(true);
        setIsReady(true);
      }
    };

    // Try to initialize, with retries
    let retries = 0;
    const maxRetries = 20; // 10 seconds

    const tryInitialize = () => {
      retries++;
      
      if (window.wp?.blockEditor && window.wp?.blocks && window.wp?.element) {
        initializeBlockEditor();
      } else if (retries < maxRetries) {
        console.log(`⏳ WordPress APIs not ready, retry ${retries}/${maxRetries}...`);
        setTimeout(tryInitialize, 500);
      } else {
        console.error('❌ WordPress APIs not available after 10 seconds');
        setErrorMessage('WordPress Block Editor APIs not available');
        setHasError(true);
        setIsReady(true);
      }
    };

    tryInitialize();
  }, [value]);

  // Handle block changes
  const handleBlocksChange = (newBlocks) => {
    setBlocks(newBlocks);
    
    if (onChange && window.wp?.blocks) {
      try {
        // Serialize blocks to HTML
        const serializedHTML = window.wp.blocks.serialize(newBlocks);
        const emailSafeHTML = makeEmailSafe(serializedHTML);
        onChange(emailSafeHTML);
      } catch (error) {
        console.error('❌ Failed to serialize blocks:', error);
      }
    }
  };

  // Make HTML email-safe
  const makeEmailSafe = (html) => {
    if (!html) return '';
    
    let emailHTML = html;
    
    // Convert to inline styles for email compatibility
    emailHTML = emailHTML
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
      
      // Remove Gutenberg-specific classes
      .replace(/class="[^"]*wp-block[^"]*"/g, '')
      .replace(/class="[^"]*has-[^"]*"/g, '')
      
      // Strong/Bold styling
      .replace(/<strong>/g, '<strong style="font-weight: bold;">')
      .replace(/<b>/g, '<b style="font-weight: bold;">')
      
      // Emphasis/Italic styling
      .replace(/<em>/g, '<em style="font-style: italic;">')
      .replace(/<i>/g, '<i style="font-style: italic;">')
      
      // Clean up extra spaces and empty class attributes
      .replace(/\s+class=""\s*/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
    
    return emailHTML;
  };

  // WordPress Block Editor Component
  const WordPressBlockEditorComponent = () => {
    const editorContainer = useRef(null);
    const rootRef = useRef(null);

    useEffect(() => {
      if (!editorContainer.current || !window.wp?.element || hasError) return;

      try {
        const { createElement } = window.wp.element;
        const { BlockEditorProvider, BlockList, WritingFlow, ObserveTyping, BlockToolbar } = window.wp.blockEditor;

        // Create editor settings
        const settings = {
          hasFixedToolbar: false,
          focusMode: false,
          hasInlineToolbar: true,
          // Restrict to email-friendly blocks
          allowedBlockTypes: [
            'core/paragraph',
            'core/heading',
            'core/list',
            'core/quote',
            'core/separator',
            'core/spacer',
            'core/buttons',
            'core/button'
          ],
          bodyPlaceholder: placeholder
        };

        // Create the block editor
        const editor = createElement(
          BlockEditorProvider,
          {
            value: blocks,
            onInput: handleBlocksChange,
            onChange: handleBlocksChange,
            settings: settings
          },
          [
            // Block Toolbar
            createElement(
              'div',
              {
                key: 'toolbar',
                style: {
                  position: 'sticky',
                  top: 0,
                  background: '#fff',
                  borderBottom: '1px solid #ddd',
                  padding: '8px',
                  zIndex: 10
                }
              },
              createElement(BlockToolbar)
            ),
            // Main editor content
            createElement(
              'div',
              {
                key: 'editor-content',
                style: {
                  padding: '16px',
                  minHeight: '200px',
                  background: '#fff'
                }
              },
              createElement(
                WritingFlow,
                {},
                createElement(
                  ObserveTyping,
                  {},
                  createElement(BlockList)
                )
              )
            )
          ]
        );

        // Render the editor using React 18 compatible method
        if (window.wp.element.createRoot) {
          // React 18 way
          rootRef.current = window.wp.element.createRoot(editorContainer.current);
          rootRef.current.render(editor);
        } else if (window.wp.element.render) {
          // Fallback to legacy render
          window.wp.element.render(editor, editorContainer.current);
        } else {
          console.error('❌ No WordPress render method available');
        }

      } catch (error) {
        console.error('❌ Failed to render WordPress Block Editor:', error);
        setErrorMessage(`Render error: ${error.message}`);
        setHasError(true);
      }
    }, [blocks, hasError]);

    // Cleanup
    useEffect(() => {
      return () => {
        try {
          // React 18 cleanup
          if (rootRef.current) {
            rootRef.current.unmount();
          } else if (editorContainer.current && window.wp?.element?.unmountComponentAtNode) {
            window.wp.element.unmountComponentAtNode(editorContainer.current);
          }
          
          // Clear the container
          if (editorContainer.current) {
            editorContainer.current.innerHTML = '';
          }
        } catch (error) {
          console.error('Cleanup error:', error);
        }
      };
    }, []);

    return <div ref={editorContainer} />;
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
        ❌ WordPress Block Editor Error: {errorMessage}
      </div>
      <textarea
        value={typeof value === 'string' ? value : ''}
        onChange={(e) => onChange && onChange(makeEmailSafe(e.target.value))}
        placeholder={placeholder}
        style={{
          width: '100%',
          minHeight: '200px',
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
    <div className={`wordpress-block-editor ${className}`}>
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
        <span>🧩 WordPress Block Editor (Full)</span>
        {!isReady && (
          <span style={{ fontSize: '11px', opacity: 0.7 }}>
            Initializing...
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
          <p>🔄 Loading WordPress Block Editor...</p>
          <p style={{ fontSize: '12px', marginTop: '8px' }}>
            Initializing BlockEditorProvider and WordPress components...
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
        <div 
          ref={editorRef}
          style={{ 
            background: '#fff',
            border: '1px solid #ddd',
            borderTop: 'none',
            borderRadius: '0 0 3px 3px'
          }}
        >
          <WordPressBlockEditorComponent />
        </div>
      )}

      {/* Email HTML Preview (for debugging) */}
      {process.env.NODE_ENV !== 'production' && (
        <details style={{ marginTop: '8px', fontSize: '12px', color: '#666' }}>
          <summary style={{ cursor: 'pointer' }}>Email HTML Preview (Block Editor)</summary>
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

export default WordPressBlockEditor;