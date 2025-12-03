import React, { useRef, useEffect, useState } from 'react';

/**
 * Simple WordPress Editor Component
 * Uses WordPress's built-in editor functionality
 */
function SimpleWordPressEditor({ 
  value = '', 
  onChange, 
  placeholder = 'Start writing...',
  className = '',
  lineHeight = 1.6
}) {
  const editorRef = useRef(null);
  const [isReady, setIsReady] = useState(false);
  const [editorId] = useState(`wp-editor-${Date.now()}`);

  // Make HTML email-safe
  const makeEmailSafe = (html) => {
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
      
      // Heading styling
      .replace(/<h([1-6])>/g, '<h$1 style="margin: 0 0 16px 0; font-weight: bold;">')
      .replace(/<h([1-6])\s+style="([^"]*)"/g, (match, level, styles) => {
        const hasMargin = styles.includes('margin');
        const hasFontWeight = styles.includes('font-weight');
        let newStyles = styles;
        if (!hasMargin) newStyles += '; margin: 0 0 16px 0';
        if (!hasFontWeight) newStyles += '; font-weight: bold';
        return `<h${level} style="${newStyles}"`;
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
      
      // Clean up extra spaces
      .replace(/\s+/g, ' ')
      .trim();
    
    return emailHTML;
  };

  useEffect(() => {
    let isMounted = true;

    const initializeEditor = () => {
      if (!editorRef.current) return;

      try {
        console.log('🔧 Initializing Simple WordPress Editor...');
        console.log('🔍 WordPress APIs available:', {
          wp: !!window.wp,
          tinymce: !!window.tinymce,
          QTags: !!window.QTags,
          wpActiveEditor: !!window.wpActiveEditor
        });

        // Create the editor HTML structure
        const editorHTML = `
          <div class="wp-editor-wrap">
            <div class="wp-editor-tools">
              <div class="wp-editor-tabs">
                <button type="button" id="${editorId}-tmce" class="wp-switch-editor switch-tmce" data-mode="tmce">Visual</button>
                <button type="button" id="${editorId}-html" class="wp-switch-editor switch-html" data-mode="html">Text</button>
              </div>
            </div>
            <div class="wp-editor-container">
              <textarea 
                id="${editorId}" 
                class="wp-editor-area" 
                style="width: 100%; min-height: 250px; border: 1px solid #ddd; padding: 12px; font-family: Consolas, Monaco, monospace; font-size: 13px; line-height: 1.6;"
                placeholder="${placeholder}"
              >${value || ''}</textarea>
            </div>
          </div>
        `;

        editorRef.current.innerHTML = editorHTML;

        // Get the textarea
        const textarea = document.getElementById(editorId);
        if (!textarea) {
          throw new Error('Failed to create textarea');
        }

        // Add event listeners for content changes
        textarea.addEventListener('input', (e) => {
          if (isMounted && onChange) {
            const content = e.target.value;
            const emailSafeHTML = makeEmailSafe(content);
            onChange(emailSafeHTML);
          }
        });

        // Try to initialize WordPress editor if available
        if (window.wp && window.wp.editor && window.wp.editor.initialize) {
          console.log('🎯 Initializing WordPress editor...');
          
          window.wp.editor.initialize(editorId, {
            tinymce: {
              wpautop: true,
              plugins: 'lists,paste,tabfocus,wordpress,wpautoresize,wpeditimage,wplink',
              toolbar1: 'bold,italic,bullist,numlist,link,unlink,blockquote,alignleft,aligncenter,alignright',
              toolbar2: 'formatselect,underline,strikethrough,forecolor,undo,redo',
              setup: function(editor) {
                editor.on('change input keyup', function() {
                  if (isMounted && onChange) {
                    const content = editor.getContent();
                    const emailSafeHTML = makeEmailSafe(content);
                    onChange(emailSafeHTML);
                  }
                });
              }
            },
            quicktags: {
              buttons: 'strong,em,link,block,del,ins,ul,ol,li,code'
            },
            mediaButtons: false
          });

          console.log('✅ WordPress editor initialized successfully');
        } else {
          console.log('ℹ️ WordPress editor not available, using textarea');
        }

        if (isMounted) {
          setIsReady(true);
        }

      } catch (error) {
        console.error('❌ Editor initialization failed:', error);
        
        // Fallback to basic textarea
        if (isMounted && editorRef.current) {
          editorRef.current.innerHTML = `
            <div style="padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; font-size: 12px; color: #856404; margin-bottom: 8px;">
              ⚠️ Using basic editor (WordPress editor not available)
            </div>
            <textarea 
              id="${editorId}-fallback"
              style="width: 100%; min-height: 250px; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; line-height: 1.6; resize: vertical;" 
              placeholder="${placeholder}"
            >${value || ''}</textarea>
          `;
          
          const fallbackTextarea = document.getElementById(`${editorId}-fallback`);
          if (fallbackTextarea) {
            fallbackTextarea.addEventListener('input', (e) => {
              if (isMounted && onChange) {
                const emailSafeHTML = makeEmailSafe(e.target.value);
                onChange(emailSafeHTML);
              }
            });
          }
          
          setIsReady(true);
        }
      }
    };

    // Initialize after a short delay to ensure WordPress scripts are loaded
    const timeoutId = setTimeout(initializeEditor, 300);

    return () => {
      isMounted = false;
      clearTimeout(timeoutId);
      
      // Clean up WordPress editor
      if (window.wp && window.wp.editor && window.wp.editor.remove) {
        try {
          window.wp.editor.remove(editorId);
        } catch (error) {
          console.error('Editor cleanup error:', error);
        }
      }
    };
  }, [editorId, placeholder, value, onChange, lineHeight]);

  return (
    <div className={`simple-wordpress-editor ${className}`}>
      <div style={{ 
        background: '#f9f9f9', 
        border: '1px solid #ddd', 
        borderBottom: isReady ? 'none' : '1px solid #ddd', 
        padding: '8px',
        borderRadius: isReady ? '3px 3px 0 0' : '3px',
        fontSize: '12px',
        color: '#666'
      }}>
        ⚡ WordPress Editor (Simplified)
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
          <p>🔄 Loading WordPress Editor...</p>
        </div>
      ) : (
        <div 
          ref={editorRef} 
          className="wordpress-editor-container"
          style={{ 
            background: '#fff',
            border: '1px solid #ddd',
            borderTop: 'none',
            borderRadius: '0 0 3px 3px'
          }}
        />
      )}
    </div>
  );
}

export default SimpleWordPressEditor;