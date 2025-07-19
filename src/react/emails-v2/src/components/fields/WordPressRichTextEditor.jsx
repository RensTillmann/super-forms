import React, { useState, useEffect } from 'react';

/**
 * WordPress RichText Editor Component
 * Uses @wordpress/block-editor RichText component for proper Gutenberg integration
 */
function WordPressRichTextEditor({ 
  value = '', 
  onChange, 
  placeholder = 'Start writing...',
  className = '',
  lineHeight = 1.6
}) {
  const [content, setContent] = useState(value);
  const [isWPReady, setIsWPReady] = useState(false);
  const [RichTextComponent, setRichTextComponent] = useState(null);

  // Check for WordPress RichText component availability
  useEffect(() => {
    const checkWordPressAPIs = () => {
      console.log('🔍 Checking WordPress RichText APIs:', {
        wp: !!window.wp,
        blockEditor: !!window.wp?.blockEditor,
        richText: !!window.wp?.blockEditor?.RichText,
        element: !!window.wp?.element,
        data: !!window.wp?.data
      });

      if (window.wp?.blockEditor?.RichText && window.wp?.element) {
        console.log('✅ WordPress RichText component available');
        setRichTextComponent(() => window.wp.blockEditor.RichText);
        setIsWPReady(true);
        return true;
      }
      return false;
    };

    // Try immediately
    if (checkWordPressAPIs()) {
      return;
    }

    // If not ready, retry for up to 10 seconds
    let retries = 0;
    const maxRetries = 20;
    
    const retryCheck = () => {
      retries++;
      if (checkWordPressAPIs()) {
        return;
      }
      
      if (retries < maxRetries) {
        console.log(`⏳ WordPress RichText not ready, retry ${retries}/${maxRetries}...`);
        setTimeout(retryCheck, 500);
      } else {
        console.warn('❌ WordPress RichText component not available after 10 seconds');
        setIsWPReady(true); // Show fallback
      }
    };

    setTimeout(retryCheck, 100);
  }, []);

  // Make HTML email-safe
  const makeEmailSafe = (html) => {
    if (!html) return '';
    
    let emailHTML = html;
    
    // Convert to inline styles for email compatibility
    emailHTML = emailHTML
      // Paragraph styling
      .replace(/<p>/g, `<p style="margin: 0 0 12px 0; line-height: ${lineHeight};">`)
      .replace(/<p\s+style="([^"]*)"/g, (match, styles) => {
        const hasMargin = styles.includes('margin');
        const hasLineHeight = styles.includes('line-height');
        let newStyles = styles;
        if (!hasMargin) newStyles += '; margin: 0 0 12px 0';
        if (!hasLineHeight) newStyles += `; line-height: ${lineHeight}`;
        return `<p style="${newStyles}"`;
      })
      
      // Strong/Bold styling
      .replace(/<strong>/g, '<strong style="font-weight: bold;">')
      .replace(/<b>/g, '<b style="font-weight: bold;">')
      
      // Emphasis/Italic styling
      .replace(/<em>/g, '<em style="font-style: italic;">')
      .replace(/<i>/g, '<i style="font-style: italic;">')
      
      // Link styling
      .replace(/<a\s+/g, '<a style="color: #1e73be; text-decoration: underline;" ')
      
      // List styling
      .replace(/<ul>/g, '<ul style="margin: 0 0 16px 0; padding-left: 20px;">')
      .replace(/<ol>/g, '<ol style="margin: 0 0 16px 0; padding-left: 20px;">')
      .replace(/<li>/g, '<li style="margin-bottom: 4px;">')
      
      // Clean up extra spaces
      .replace(/\s+/g, ' ')
      .trim();
    
    return emailHTML;
  };

  // Handle content changes
  const handleContentChange = (newContent) => {
    setContent(newContent);
    
    if (onChange) {
      const emailSafeHTML = makeEmailSafe(newContent);
      onChange(emailSafeHTML);
    }
  };


  // Fallback textarea editor
  const FallbackEditor = () => (
    <div>
      <div style={{
        padding: '8px',
        background: '#fff3cd',
        border: '1px solid #ffeaa7',
        borderRadius: '4px',
        fontSize: '12px',
        color: '#856404',
        marginBottom: '8px'
      }}>
        ⚠️ Using fallback editor (WordPress RichText not available)
      </div>
      <textarea
        value={content}
        onChange={(e) => handleContentChange(e.target.value)}
        placeholder={placeholder}
        style={{
          width: '100%',
          minHeight: '150px',
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

  // Simple React wrapper that creates a container for WordPress RichText
  const RichTextWrapper = () => {
    const containerRef = React.useRef(null);
    const rootRef = React.useRef(null);
    const [hasRendered, setHasRendered] = useState(false);

    useEffect(() => {
      if (containerRef.current && RichTextComponent && window.wp?.element && !hasRendered) {
        try {
          console.log('🔧 Rendering WordPress RichText component...');
          
          // Create WordPress RichText element using wp.element
          const { createElement } = window.wp.element;
          
          const richTextElement = createElement(RichTextComponent, {
            tagName: 'div',
            value: content,
            onChange: handleContentChange,
            placeholder: placeholder,
            allowedFormats: [
              'core/bold',
              'core/italic', 
              'core/link',
              'core/strikethrough'
            ],
            style: {
              padding: '12px',
              minHeight: '150px',
              fontSize: '14px',
              lineHeight: lineHeight,
              fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
              border: 'none',
              outline: 'none'
            }
          });

          // Render using WordPress's React
          if (window.wp.element.createRoot) {
            // React 18 way
            rootRef.current = window.wp.element.createRoot(containerRef.current);
            rootRef.current.render(richTextElement);
          } else if (window.wp.element.render) {
            // Fallback to legacy render
            window.wp.element.render(richTextElement, containerRef.current);
          }
          
          setHasRendered(true);
          console.log('✅ WordPress RichText rendered successfully');

        } catch (error) {
          console.error('❌ Failed to render WordPress RichText:', error);
        }
      }
    }, [RichTextComponent, content, hasRendered]);

    // Cleanup
    useEffect(() => {
      return () => {
        try {
          // React 18 cleanup
          if (rootRef.current) {
            rootRef.current.unmount();
          } else if (containerRef.current && window.wp?.element?.unmountComponentAtNode) {
            window.wp.element.unmountComponentAtNode(containerRef.current);
          }
          
          // Clear the container
          if (containerRef.current) {
            containerRef.current.innerHTML = '';
          }
        } catch (error) {
          console.error('RichText cleanup error:', error);
        }
      };
    }, []);

    if (!RichTextComponent) {
      return <FallbackEditor />;
    }

    return (
      <div
        style={{
          border: '1px solid #ddd',
          borderRadius: '4px',
          minHeight: '150px',
          background: '#fff'
        }}
      >
        <div ref={containerRef} />
      </div>
    );
  };

  return (
    <div className={`wordpress-richtext-editor ${className}`}>
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
        <span>📝 WordPress RichText Editor</span>
        {!isWPReady && (
          <span style={{ fontSize: '11px', opacity: 0.7 }}>
            Loading...
          </span>
        )}
      </div>
      
      {!isWPReady ? (
        <div style={{
          border: '1px solid #ddd',
          borderTop: 'none',
          borderRadius: '0 0 3px 3px',
          padding: '40px',
          textAlign: 'center',
          background: '#fff',
          color: '#666'
        }}>
          <p>🔄 Loading WordPress RichText...</p>
          <p style={{ fontSize: '12px', marginTop: '8px' }}>
            Checking wp.blockEditor.RichText availability...
          </p>
        </div>
      ) : RichTextComponent ? (
        <div style={{ 
          borderLeft: '1px solid #ddd',
          borderRight: '1px solid #ddd',
          borderBottom: '1px solid #ddd',
          borderRadius: '0 0 3px 3px'
        }}>
          <RichTextWrapper />
        </div>
      ) : (
        <div style={{ 
          borderLeft: '1px solid #ddd',
          borderRight: '1px solid #ddd',
          borderBottom: '1px solid #ddd',
          borderRadius: '0 0 3px 3px',
          padding: '8px'
        }}>
          <FallbackEditor />
        </div>
      )}

      {/* Email HTML Preview (for debugging) */}
      {process.env.NODE_ENV !== 'production' && (
        <details style={{ marginTop: '8px', fontSize: '12px', color: '#666' }}>
          <summary style={{ cursor: 'pointer' }}>Email HTML Preview (RichText)</summary>
          <pre style={{ 
            marginTop: '8px', 
            padding: '8px', 
            background: '#f5f5f5', 
            borderRadius: '3px', 
            fontSize: '11px',
            overflow: 'auto',
            whiteSpace: 'pre-wrap'
          }}>
            {makeEmailSafe(content)}
          </pre>
        </details>
      )}
    </div>
  );
}

export default WordPressRichTextEditor;