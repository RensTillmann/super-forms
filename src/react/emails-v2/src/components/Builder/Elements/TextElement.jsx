import React, { useState, useRef, useEffect } from 'react';
import FloatingToolbar from './FloatingToolbar';
import { useEmailBuilderStore } from '../../../hooks/useEmailBuilder';

function TextElement({ element }) {
  const { 
    content, 
    fontSize = 16, 
    fontFamily = 'Arial, sans-serif', 
    color = '#000000', 
    lineHeight = 1.6, 
    align = 'left',
    width = '100%',
    margin = { top: 0, right: 0, bottom: 16, left: 0 },
    padding = { top: 0, right: 0, bottom: 0, left: 0 }
  } = element.props;

  const [isEditing, setIsEditing] = useState(false);
  const [toolbarPosition, setToolbarPosition] = useState(null);
  const [showToolbar, setShowToolbar] = useState(false);
  const contentRef = useRef(null);
  const updateElement = useEmailBuilderStore.getState().updateElement;

  // Handle double-click to start editing
  const handleDoubleClick = (e) => {
    e.stopPropagation();
    setIsEditing(true);
    
    // Focus the content editable div
    setTimeout(() => {
      if (contentRef.current) {
        contentRef.current.focus();
        
        // Select all text
        const range = document.createRange();
        const selection = window.getSelection();
        range.selectNodeContents(contentRef.current);
        selection.removeAllRanges();
        selection.addRange(range);
        
        // Show toolbar
        showFloatingToolbar();
      }
    }, 0);
  };

  // Handle click outside to finish editing
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (isEditing && contentRef.current && !contentRef.current.contains(event.target)) {
        finishEditing();
      }
    };

    if (isEditing) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isEditing]);

  // Handle text selection to show/hide toolbar
  const handleTextSelection = () => {
    if (!isEditing) return;
    
    const selection = window.getSelection();
    if (selection.rangeCount > 0 && !selection.isCollapsed) {
      showFloatingToolbar();
    } else {
      setShowToolbar(false);
    }
  };

  // Show floating toolbar
  const showFloatingToolbar = () => {
    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
      const range = selection.getRangeAt(0);
      const rect = range.getBoundingClientRect();
      
      setToolbarPosition({
        x: rect.left + rect.width / 2,
        y: rect.top
      });
      setShowToolbar(true);
    }
  };

  // Finish editing and save content
  const finishEditing = () => {
    if (contentRef.current) {
      const newContent = contentRef.current.innerHTML;
      
      // Update the element content
      updateElement(element.id, {
        props: {
          ...element.props,
          content: newContent
        }
      });
    }
    
    setIsEditing(false);
    setShowToolbar(false);
    setToolbarPosition(null);
  };

  // Handle keyboard shortcuts
  const handleKeyDown = (e) => {
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
      e.preventDefault();
      finishEditing();
    }
    
    if (e.key === 'Escape') {
      e.preventDefault();
      finishEditing();
    }
  };

  // Format text using document.execCommand
  const handleFormatText = (command) => {
    document.execCommand(command, false, null);
    contentRef.current?.focus();
  };

  // Handle text alignment
  const handleAlignText = (alignment) => {
    updateElement(element.id, {
      props: {
        ...element.props,
        align: alignment
      }
    });
  };

  // Handle font size change
  const handleFontSize = (size) => {
    updateElement(element.id, {
      props: {
        ...element.props,
        fontSize: size
      }
    });
  };

  // Handle text color change
  const handleTextColor = (newColor) => {
    updateElement(element.id, {
      props: {
        ...element.props,
        color: newColor
      }
    });
  };

  // Handle content input
  const handleInput = (e) => {
    // Debounce updates to avoid too many re-renders
    const newContent = e.target.innerHTML;
    clearTimeout(window.textUpdateTimeout);
    window.textUpdateTimeout = setTimeout(() => {
      updateElement(element.id, {
        props: {
          ...element.props,
          content: newContent
        }
      });
    }, 300);
  };

  return (
    <>
      <div
        ref={contentRef}
        className={`ev2-min-h-[20px] element-content ${isEditing ? 'ev2-outline-2 ev2-outline-blue-500 ev2-outline-dashed' : ''}`}
        style={{
          fontSize: `${fontSize}px`,
          fontFamily: fontFamily,
          color: color,
          lineHeight: lineHeight,
          textAlign: align,
          width: width,
          marginTop: `${margin.top}px`,
          marginRight: `${margin.right}px`,
          marginBottom: `${margin.bottom}px`,
          marginLeft: `${margin.left}px`,
          paddingTop: `${padding.top}px`,
          paddingRight: `${padding.right}px`,
          paddingBottom: `${padding.bottom}px`,
          paddingLeft: `${padding.left}px`,
          cursor: isEditing ? 'text' : 'pointer',
        }}
        contentEditable={isEditing}
        suppressContentEditableWarning={true}
        dangerouslySetInnerHTML={!isEditing ? { __html: content } : undefined}
        onDoubleClick={handleDoubleClick}
        onKeyDown={isEditing ? handleKeyDown : undefined}
        onInput={isEditing ? handleInput : undefined}
        onMouseUp={isEditing ? handleTextSelection : undefined}
        onKeyUp={isEditing ? handleTextSelection : undefined}
        {...({
          // Always visible identification
          'data-component': 'TextElement',
          'data-element-type': 'text',
          'data-element-id': element.id,
          'data-is-editing': isEditing ? 'true' : 'false',
          
          // Development debugging attributes
          ...(!process.env.NODE_ENV || process.env.NODE_ENV !== 'production') && {
            'data-debug-content-length': content?.length || 0,
            'data-debug-font-size': fontSize,
            'data-debug-font-family': fontFamily,
            'data-debug-color': color,
            'data-debug-align': align
          }
        })}
      />

      {/* Floating Toolbar */}
      <FloatingToolbar
        position={toolbarPosition}
        isVisible={showToolbar && isEditing}
        onFormatText={handleFormatText}
        onAlignText={handleAlignText}
        onFontSize={handleFontSize}
        onTextColor={handleTextColor}
        currentAlign={align}
        currentFontSize={fontSize}
        currentColor={color}
      />

      {/* Editing instructions */}
      {isEditing && (
        <div className="ev2-absolute ev2-bottom-[-30px] ev2-left-0 ev2-right-0 ev2-text-xs ev2-text-gray-500 ev2-text-center ev2-bg-white ev2-px-2 ev2-py-1 ev2-rounded ev2-shadow-sm">
          Double-click to edit • Press Ctrl+Enter or click outside to finish • ESC to cancel
        </div>
      )}
    </>
  );
}

export default TextElement;