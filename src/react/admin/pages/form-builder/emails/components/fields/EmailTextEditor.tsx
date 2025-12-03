import React, { useRef, useEffect, useState } from 'react';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css';

/**
 * Email-optimized Rich Text Editor using Quill.js
 * Outputs clean, email-friendly HTML with inline styles
 */
function EmailTextEditor({ 
  value = '', 
  onChange, 
  placeholder = 'Enter your text here...',
  className = '',
  lineHeight = 1.6
}) {
  const quillRef = useRef(null);
  const debounceTimeoutRef = useRef(null);
  const [editorContent, setEditorContent] = useState(value);
  const isUserTyping = useRef(false);

  // Email-safe Quill configuration
  const modules = {
    toolbar: [
      // Basic formatting - email safe
      ['bold', 'italic', 'underline'],
      
      // Text styling
      [{ 'color': [] }, { 'background': [] }],
      
      // Alignment
      [{ 'align': [] }],
      
      // Lists - email compatible
      [{ 'list': 'ordered'}, { 'list': 'bullet' }],
      
      // Links
      ['link'],
      
      // Clear formatting
      ['clean']
    ],
    clipboard: {
      // Strip all formatting when pasting
      matchVisual: false,
    }
  };

  const formats = [
    'bold', 'italic', 'underline',
    'color', 'background',
    'align',
    'list', 'bullet',
    'link'
  ];

  // Convert Quill Delta/HTML to email-safe HTML
  const convertToEmailHTML = (html) => {
    if (!html || html === '<p><br></p>') return '';
    
    // Remove Quill's default classes and convert to inline styles
    let emailHTML = html;
    
    // Replace Quill classes with inline styles
    emailHTML = emailHTML
      // Bold
      .replace(/<strong>/g, '<span style="font-weight: bold;">')
      .replace(/<\/strong>/g, '</span>')
      
      // Italic  
      .replace(/<em>/g, '<span style="font-style: italic;">')
      .replace(/<\/em>/g, '</span>')
      
      // Underline
      .replace(/<u>/g, '<span style="text-decoration: underline;">')
      .replace(/<\/u>/g, '</span>')
      
      // Handle empty paragraphs first - convert <p><br></p> to proper empty paragraph without margins
      .replace(/<p><br><\/p>/g, `<p style="margin: 0; line-height: ${lineHeight};">&nbsp;</p>`)
      
      // Remove completely empty paragraphs only
      .replace(/<p><\/p>/g, '')
      
      // Handle Quill alignment classes - convert to inline styles with margin and line-height
      .replace(/<p class="ql-align-center"/g, `<p style="margin: 0; line-height: ${lineHeight}; text-align: center;"`)
      .replace(/<p class="ql-align-right"/g, `<p style="margin: 0; line-height: ${lineHeight}; text-align: right;"`)
      .replace(/<p class="ql-align-justify"/g, `<p style="margin: 0; line-height: ${lineHeight}; text-align: justify;"`)
      .replace(/<p class="ql-align-left"/g, `<p style="margin: 0; line-height: ${lineHeight}; text-align: left;"`)
      
      // Ensure all other paragraphs have no margins - just line height
      .replace(/<p>/g, `<p style="margin: 0; line-height: ${lineHeight};">`);
    
    emailHTML = emailHTML
      
      // Lists - make email-compatible
      .replace(/<ul>/g, '<ul style="margin: 0 0 16px 0; padding-left: 20px;">')
      .replace(/<ol>/g, '<ol style="margin: 0 0 16px 0; padding-left: 20px;">')
      .replace(/<li>/g, '<li style="margin-bottom: 8px;">')
      
      // Links - ensure email safety
      .replace(/<a href="/g, '<a style="color: #0066cc; text-decoration: underline;" href="')
      
      // Remove any remaining Quill classes
      .replace(/class="[^"]*"/g, '')
      
      // Clean up extra spaces
      .replace(/\s+/g, ' ')
      .trim();
    
    return emailHTML;
  };

  // Handle content changes with immediate updates
  const handleChange = (content, delta, source, editor) => {
    // Track that user is actively typing
    isUserTyping.current = true;
    
    // Update local state for UI consistency
    setEditorContent(content);
    
    // Clear any existing timeout
    if (debounceTimeoutRef.current) {
      clearTimeout(debounceTimeoutRef.current);
    }
    
    // Convert to email-safe HTML and send immediately to parent
    const emailSafeHTML = convertToEmailHTML(content);
    
    if (onChange) {
      onChange(emailSafeHTML);
    }
    
    // Mark typing as finished after a short delay
    debounceTimeoutRef.current = setTimeout(() => {
      isUserTyping.current = false;
    }, 100);
  };

  // Initialize content - but don't override when user is typing
  useEffect(() => {
    if (value !== editorContent && !isUserTyping.current) {
      setEditorContent(value);
    }
  }, [value, editorContent]);

  // Re-convert and update when lineHeight changes
  useEffect(() => {
    if (editorContent && onChange && !isUserTyping.current) {
      const emailSafeHTML = convertToEmailHTML(editorContent);
      onChange(emailSafeHTML);
    }
  }, [lineHeight]); // Only trigger when lineHeight changes

  // Cleanup timeout on unmount
  useEffect(() => {
    return () => {
      if (debounceTimeoutRef.current) {
        clearTimeout(debounceTimeoutRef.current);
      }
    };
  }, []);

  return (
    <div className={`email-text-editor ${className}`}>
      <ReactQuill
        ref={quillRef}
        theme="snow"
        value={editorContent}
        onChange={handleChange}
        placeholder={placeholder}
        modules={modules}
        formats={formats}
        style={{
          backgroundColor: 'white',
          borderRadius: '6px',
          fontSize: '14px'
        }}
        className="border border-gray-300"
      />
      
      {/* Email HTML Preview (for debugging) */}
      {process.env.NODE_ENV !== 'production' && (
        <details className="mt-2 text-xs text-gray-500">
          <summary className="cursor-pointer">Email HTML Preview</summary>
          <pre className="mt-2 p-2 bg-gray-100 rounded text-xs overflow-auto">
            {convertToEmailHTML(editorContent)}
          </pre>
        </details>
      )}
    </div>
  );
}

export default EmailTextEditor;