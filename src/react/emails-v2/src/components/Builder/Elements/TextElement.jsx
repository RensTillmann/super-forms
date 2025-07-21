import React, { useState, useRef, useEffect } from 'react';
import FloatingToolbar from './FloatingToolbar';
import { useEmailBuilderStore } from '../../../hooks/useEmailBuilder';
import useEmailBuilder from '../../../hooks/useEmailBuilder';

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
  const deleteElement = useEmailBuilderStore.getState().deleteElement;
  const selectElement = useEmailBuilderStore.getState().selectElement;
  const { setEditingTextElement, moveElementUpDown, isDragging } = useEmailBuilder();

  // Set initial content when not editing
  useEffect(() => {
    if (!isEditing && contentRef.current) {
      // Only update if content has actually changed to prevent cursor jumping
      if (contentRef.current.innerHTML !== (content || '')) {
        contentRef.current.innerHTML = content || '';
      }
    }
  }, [content, isEditing]);

  // Handle click to start editing
  const handleClick = (e) => {
    e.stopPropagation();
    if (!isEditing) {
      // Select the element to show properties
      selectElement(element.id);
      
      setIsEditing(true);
      setEditingTextElement(element.id); // Track that this element is being edited
      
      // Set the content before editing
      if (contentRef.current) {
        contentRef.current.innerHTML = content || '';
      }
      
      // Focus the content editable div
      setTimeout(() => {
        if (contentRef.current) {
          contentRef.current.focus();
          
          // Place cursor at the end
          const selection = window.getSelection();
          const range = document.createRange();
          range.selectNodeContents(contentRef.current);
          range.collapse(false);
          selection.removeAllRanges();
          selection.addRange(range);
          
          // Show toolbar
          showFloatingToolbar();
        }
      }, 0);
    }
  };

  // Handle click outside to finish editing
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (isEditing && contentRef.current && !contentRef.current.contains(event.target)) {
        // Check if the click is on the floating toolbar
        const toolbar = document.querySelector('.ev2-fixed.ev2-z-50'); // Floating toolbar selector
        if (toolbar && toolbar.contains(event.target)) {
          return; // Don't finish editing if clicking on toolbar
        }
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
    
    // Keep toolbar visible during entire editing session
    // Only update position if needed
    showFloatingToolbar();
  };
  
  // Re-show toolbar when drag ends if still editing
  useEffect(() => {
    if (!isDragging && isEditing && contentRef.current) {
      // Re-show toolbar after drag ends
      showFloatingToolbar();
    }
  }, [isDragging]);
  
  // Update toolbar position on scroll and resize
  useEffect(() => {
    if (!isEditing || !showToolbar) return;
    
    const updateToolbarPosition = () => {
      if (contentRef.current) {
        const rect = contentRef.current.getBoundingClientRect();
        const toolbarHeight = 50; // Approximate toolbar height including padding
        setToolbarPosition({
          x: rect.left + rect.width / 2,
          y: rect.top - toolbarHeight - 20 // Position toolbar height + 20px above element
        });
      }
    };
    
    // Listen to scroll events on window and all scrollable ancestors
    const scrollableElements = [];
    let element = contentRef.current;
    
    while (element) {
      // Check if element is scrollable
      const style = window.getComputedStyle(element);
      if (
        element.scrollHeight > element.clientHeight ||
        style.overflowY === 'scroll' ||
        style.overflowY === 'auto' ||
        style.overflow === 'scroll' ||
        style.overflow === 'auto'
      ) {
        scrollableElements.push(element);
        element.addEventListener('scroll', updateToolbarPosition, { passive: true });
      }
      element = element.parentElement;
    }
    
    // Also listen to window scroll and resize
    window.addEventListener('scroll', updateToolbarPosition, { passive: true });
    window.addEventListener('resize', updateToolbarPosition, { passive: true });
    
    // Initial position update
    updateToolbarPosition();
    
    // Cleanup
    return () => {
      scrollableElements.forEach(el => {
        el.removeEventListener('scroll', updateToolbarPosition);
      });
      window.removeEventListener('scroll', updateToolbarPosition);
      window.removeEventListener('resize', updateToolbarPosition);
    };
  }, [isEditing, showToolbar]);

  // Show floating toolbar
  const showFloatingToolbar = () => {
    if (contentRef.current) {
      const rect = contentRef.current.getBoundingClientRect();
      const toolbarHeight = 50; // Approximate toolbar height including padding
      
      setToolbarPosition({
        x: rect.left + rect.width / 2,
        y: rect.top - toolbarHeight - 20 // Position toolbar height + 20px above element
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
    setEditingTextElement(null); // Clear editing state
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
    if (command === 'createLink') {
      const url = prompt('Enter URL:');
      if (url) {
        document.execCommand('createLink', false, url);
      }
    } else {
      document.execCommand(command, false, null);
    }
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

  // Handle element controls
  const handleDelete = () => {
    finishEditing();
    deleteElement(element.id);
  };
  
  const handleMoveUp = () => {
    moveElementUpDown(element.id, 'up');
  };
  
  const handleMoveDown = () => {
    moveElementUpDown(element.id, 'down');
  };
  
  const handleStartDrag = (e) => {
    // We need to trigger the drag on the parent SortableElement
    // First, finish editing and hide toolbar
    finishEditing();
    
    // Find the parent sortable element and trigger its drag
    const sortableElement = e.target.closest('[data-component="SortableElement"]');
    if (sortableElement) {
      // Find the drag handle in the sortable element
      const dragHandle = sortableElement.querySelector('[data-drag-handle="true"]');
      if (dragHandle) {
        // Simulate mousedown on the drag handle
        const mouseDownEvent = new MouseEvent('mousedown', {
          bubbles: true,
          cancelable: true,
          view: window,
          clientX: e.clientX,
          clientY: e.clientY,
        });
        dragHandle.dispatchEvent(mouseDownEvent);
      }
    }
  };
  
  const handleSave = () => {
    // Save changes and exit edit mode
    finishEditing();
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
          minHeight: '1em', // Ensure minimum height even when empty
        }}
        contentEditable={isEditing}
        suppressContentEditableWarning={true}
        onClick={handleClick}
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

      {/* Floating Toolbar - Hide when dragging */}
      <FloatingToolbar
        position={toolbarPosition}
        isVisible={showToolbar && isEditing && !isDragging}
        onFormatText={handleFormatText}
        onAlignText={handleAlignText}
        onFontSize={handleFontSize}
        onTextColor={handleTextColor}
        onDelete={handleDelete}
        onMoveUp={handleMoveUp}
        onMoveDown={handleMoveDown}
        onStartDrag={handleStartDrag}
        onSave={handleSave}
        currentAlign={align}
        currentFontSize={fontSize}
        currentColor={color}
        showElementControls={true}
      />

      {/* Editing instructions */}
      {isEditing && (
        <div className="ev2-absolute ev2-bottom-[-30px] ev2-left-0 ev2-right-0 ev2-text-xs ev2-text-gray-500 ev2-text-center ev2-bg-white ev2-px-2 ev2-py-1 ev2-rounded ev2-shadow-sm">
          Press Ctrl+Enter or click outside to finish • ESC to cancel
        </div>
      )}
    </>
  );
}

export default TextElement;