import React, { useRef, useEffect, useState } from 'react';

/**
 * Email-optimized TinyMCE Editor with WordPress Media Library Integration
 * Outputs clean, email-friendly HTML with inline styles
 */
function EmailTinyMCEEditor({ 
  value = '', 
  onChange, 
  placeholder = 'Enter your text here...',
  className = '',
  lineHeight = 1.6
}) {
  const editorRef = useRef(null);
  const containerRef = useRef(null);
  const [editorContent, setEditorContent] = useState(value);
  const [isInitialized, setIsInitialized] = useState(false);
  const isUserTyping = useRef(false);
  const editorId = useRef(`email-tinymce-${Date.now()}`);

  // Convert TinyMCE HTML to email-safe HTML
  const convertToEmailHTML = (html) => {
    if (!html || html === '<p></p>' || html === '<p><br></p>') return '';
    
    let emailHTML = html;
    
    // Replace TinyMCE elements with email-safe inline styles
    emailHTML = emailHTML
      // Handle strong/bold tags
      .replace(/<strong>/g, '<span style="font-weight: bold;">')
      .replace(/<\/strong>/g, '</span>')
      
      // Handle em/italic tags  
      .replace(/<em>/g, '<span style="font-style: italic;">')
      .replace(/<\/em>/g, '</span>')
      
      // Handle underline
      .replace(/<u>/g, '<span style="text-decoration: underline;">')
      .replace(/<\/u>/g, '</span>')
      
      // Handle TinyMCE alignment styles - convert to inline
      .replace(/<p style="text-align:\s*center;?"([^>]*)>/g, `<p style="margin: 0; line-height: ${lineHeight}; text-align: center;"$1>`)
      .replace(/<p style="text-align:\s*right;?"([^>]*)>/g, `<p style="margin: 0; line-height: ${lineHeight}; text-align: right;"$1>`)
      .replace(/<p style="text-align:\s*justify;?"([^>]*)>/g, `<p style="margin: 0; line-height: ${lineHeight}; text-align: justify;"$1>`)
      .replace(/<p style="text-align:\s*left;?"([^>]*)>/g, `<p style="margin: 0; line-height: ${lineHeight}; text-align: left;"$1>`)
      
      // Handle empty paragraphs - convert <p><br></p> to proper empty paragraph
      .replace(/<p><br\s*\/?><\/p>/g, `<p style="margin: 0; line-height: ${lineHeight};">&nbsp;</p>`)
      .replace(/<p><br><\/p>/g, `<p style="margin: 0; line-height: ${lineHeight};">&nbsp;</p>`)
      
      // Remove completely empty paragraphs
      .replace(/<p><\/p>/g, '')
      
      // Ensure all other paragraphs have proper email styling
      .replace(/<p>/g, `<p style="margin: 0; line-height: ${lineHeight};">`)
      .replace(/<p\s+>/g, `<p style="margin: 0; line-height: ${lineHeight};">`)
      
      // Handle lists - make email-compatible
      .replace(/<ul>/g, '<ul style="margin: 0 0 16px 0; padding-left: 20px;">')
      .replace(/<ol>/g, '<ol style="margin: 0 0 16px 0; padding-left: 20px;">')
      .replace(/<li>/g, '<li style="margin-bottom: 8px;">')
      
      // Handle links - ensure email safety
      .replace(/<a\s+href="/g, '<a style="color: #0066cc; text-decoration: underline;" href="')
      
      // Clean up any remaining style attributes that might conflict
      .replace(/style="([^"]*);?\s*"/g, (match, styles) => {
        // Clean up style attribute formatting
        const cleanStyles = styles.trim().replace(/;+$/, '');
        return `style="${cleanStyles};"`;
      })
      
      // Remove any TinyMCE specific classes
      .replace(/class="[^"]*"/g, '')
      
      // Clean up extra spaces
      .replace(/\s+/g, ' ')
      .trim();
    
    return emailHTML;
  };

  // Initialize TinyMCE editor using Super Forms approach
  useEffect(() => {
    // Debug TinyMCE availability (like Super Forms does)
    console.log('🔍 TinyMCE Debug:', {
      windowExists: typeof window !== 'undefined',
      tinymceExists: !!window.tinymce,
      tinymceVersion: window.tinymce ? window.tinymce.majorVersion : 'not found',
      superExists: !!(window.SUPER && window.SUPER.initTinyMCE),
    });
    
    // Check if TinyMCE is available (same check as Super Forms)
    if (typeof window !== 'undefined' && window.tinymce) {
      const initEditor = () => {
        // Remove existing editor if present (like Super Forms does)
        if (window.tinymce.get(editorId.current)) {
          window.tinymce.remove(`#${editorId.current}`);
        }

        // Initialize TinyMCE using Super Forms configuration
        window.tinymce.init({
          selector: `#${editorId.current}`,
          
          // Copy configuration from Super Forms SUPER.initTinyMCE
          toolbar_mode: 'scrolling',
          contextmenu: false,
          plugins: [
            'advlist anchor charmap code fullscreen hr image importcss link lists media paste preview searchreplace table visualblocks'
          ],
          fontsize_formats: '8pt 9pt 10pt 11pt 12pt 13pt 14pt 16pt 18pt 20pt 22pt 24pt 36pt 48pt',
          menubar: 'edit view insert format',
          toolbar1: 'bold italic forecolor backcolor alignleft aligncenter alignright alignjustify outdent indent',
          toolbar2: 'numlist bullist image link media table code preview fullscreen',
          content_style: `body {margin:5px 10px 5px 10px; color:#2c3338; font-family:Helvetica,Arial,sans-serif; font-size:12px; line-height: ${lineHeight}}`,
          height: 200,
          
          setup: (editor) => {
            // Store editor reference
            editorRef.current = editor;
            
            // Handle initialization (like Super Forms)
            editor.on('init', function() {
              console.log('TinyMCE editor initialized');
              setIsInitialized(true);
              
              // Set initial content if provided
              if (value) {
                editor.setContent(value);
                setEditorContent(value);
                console.log('Set initial content from props');
              }
            });
            
            // Handle content changes (like Super Forms)
            editor.on('Change', function(e) {
              console.log('TinyMCE content changed');
              isUserTyping.current = true;
              
              const content = editor.getContent();
              setEditorContent(content);
              
              // Convert to email-safe HTML and notify parent
              const emailSafeHTML = convertToEmailHTML(content);
              if (onChange) {
                onChange(emailSafeHTML);
              }
              
              // Mark typing as finished
              setTimeout(() => {
                isUserTyping.current = false;
              }, 100);
            });

            // Handle before content set (like Super Forms)
            editor.on('BeforeSetContent', function(e) {
              console.log('BeforeSetContent - Original content:', e.content);
              // Replace encoded placeholders (like Super Forms does)
              e.content = e.content.replace(/%7B(.+?)%7D/g, '{$1}');
              console.log('BeforeSetContent - Modified content:', e.content);
            });
          }
        });
      };

      // Small delay to ensure DOM is ready (like Super Forms)
      setTimeout(initEditor, 100);
    } else {
      // TinyMCE not available
      console.log('❌ TinyMCE not found - check if scripts are loaded');
    }

    // Cleanup on unmount
    return () => {
      if (window.tinymce && window.tinymce.get(editorId.current)) {
        window.tinymce.remove(`#${editorId.current}`);
      }
    };
  }, []); // Only run once on mount

  // Update content when value prop changes (but not when user is typing)
  useEffect(() => {
    if (value !== editorContent && !isUserTyping.current && editorRef.current) {
      editorRef.current.setContent(value);
      setEditorContent(value);
    }
  }, [value, editorContent]);

  // Re-convert content when lineHeight changes
  useEffect(() => {
    if (editorContent && onChange && !isUserTyping.current) {
      const emailSafeHTML = convertToEmailHTML(editorContent);
      onChange(emailSafeHTML);
    }
  }, [lineHeight]);

  return (
    <div className={`email-tinymce-editor ${className}`}>
      {/* TinyMCE Editor Container */}
      <div ref={containerRef}>
        <textarea
          id={editorId.current}
          className="wp-editor-area"
          placeholder={placeholder}
          defaultValue={value}
        />
      </div>
      
      {/* Initialization Status */}
      {!isInitialized && (
        <div className="ev2-text-center ev2-p-4 ev2-text-gray-500 ev2-text-sm">
          Initializing WordPress Editor...
        </div>
      )}

      {/* Email HTML Preview (for debugging) */}
      {process.env.NODE_ENV !== 'production' && (
        <details className="ev2-mt-2 ev2-text-xs ev2-text-gray-500">
          <summary className="ev2-cursor-pointer">Email HTML Preview (TinyMCE)</summary>
          <pre className="ev2-mt-2 ev2-p-2 ev2-bg-gray-100 ev2-rounded ev2-text-xs ev2-overflow-auto">
            {convertToEmailHTML(editorContent)}
          </pre>
        </details>
      )}
      
      {/* Fallback message if TinyMCE not available */}
      {typeof window !== 'undefined' && !window.tinymce && (
        <div className="ev2-p-4 ev2-bg-yellow-50 ev2-border ev2-border-yellow-200 ev2-rounded ev2-text-sm">
          <p className="ev2-text-yellow-800">
            <strong>TinyMCE not loaded.</strong> Please ensure TinyMCE scripts are loaded.
          </p>
          <p className="ev2-text-yellow-700 ev2-mt-2">
            Switch to <strong>Quill.js editor</strong> above for full rich text functionality.
          </p>
          <div className="ev2-mt-2 ev2-text-xs ev2-text-yellow-600">
            <p><strong>Debug info:</strong> Check console for WordPress globals detection.</p>
          </div>
        </div>
      )}
    </div>
  );
}

export default EmailTinyMCEEditor;