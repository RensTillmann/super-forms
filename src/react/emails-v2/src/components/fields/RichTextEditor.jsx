import React, { useState, useRef, useEffect, useCallback } from 'react';
import clsx from 'clsx';
import { 
  Bold, 
  Italic, 
  Underline, 
  AlignLeft, 
  AlignCenter, 
  AlignRight, 
  Link, 
  Unlink,
  List,
  ListOrdered,
  Palette
} from 'lucide-react';

function RichTextEditor({ 
  label, 
  value, 
  onChange, 
  height = 200,
  className = ''
}) {
  const [showColorPicker, setShowColorPicker] = useState(false);
  const editorRef = useRef(null);
  const colorPickerRef = useRef(null);
  const isInitialized = useRef(false);

  // Initialize content only once
  useEffect(() => {
    if (editorRef.current && !isInitialized.current) {
      editorRef.current.innerHTML = value || '';
      isInitialized.current = true;
    }
  }, []);

  // Handle clicks outside color picker
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (colorPickerRef.current && !colorPickerRef.current.contains(event.target)) {
        setShowColorPicker(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleInput = useCallback(() => {
    if (editorRef.current && onChange) {
      const currentContent = editorRef.current.innerHTML;
      onChange(currentContent);
    }
  }, [onChange]);

  const execCommand = useCallback((command, value = null) => {
    if (editorRef.current) {
      editorRef.current.focus();
      document.execCommand(command, false, value);
      handleInput();
    }
  }, [handleInput]);

  const handleKeyDown = useCallback((e) => {
    if (e.ctrlKey || e.metaKey) {
      switch (e.key) {
        case 'b':
          e.preventDefault();
          execCommand('bold');
          break;
        case 'i':
          e.preventDefault();
          execCommand('italic');
          break;
        case 'u':
          e.preventDefault();
          execCommand('underline');
          break;
      }
    }
  }, [execCommand]);

  const isCommandActive = useCallback((command) => {
    try {
      return document.queryCommandState(command);
    } catch (e) {
      return false;
    }
  }, []);

  const ToolbarButton = ({ onClick, active, title, children, disabled = false }) => (
    <button
      type="button"
      onClick={(e) => {
        e.preventDefault();
        e.stopPropagation();
        if (!disabled) {
          onClick();
        }
      }}
      disabled={disabled}
      className={clsx(
        'ev2-p-2 ev2-rounded ev2-transition-all ev2-border',
        active 
          ? 'ev2-bg-blue-100 ev2-text-blue-700 ev2-border-blue-300' 
          : 'ev2-bg-white ev2-text-gray-600 ev2-border-gray-300 hover:ev2-bg-gray-50',
        disabled && 'ev2-opacity-50 ev2-cursor-not-allowed'
      )}
      title={title}
    >
      {children}
    </button>
  );

  const ColorButton = ({ color, onClick }) => (
    <button
      type="button"
      onClick={(e) => {
        e.preventDefault();
        e.stopPropagation();
        onClick(color);
      }}
      className="ev2-w-6 ev2-h-6 ev2-rounded ev2-border ev2-border-gray-300 ev2-cursor-pointer hover:ev2-scale-110 ev2-transition-transform"
      style={{ backgroundColor: color }}
      title={color}
    />
  );

  const commonColors = [
    '#000000', '#333333', '#666666', '#999999', '#CCCCCC', '#FFFFFF',
    '#FF0000', '#FF6600', '#FF9900', '#FFCC00', '#FFFF00', '#CCFF00',
    '#99FF00', '#66FF00', '#33FF00', '#00FF00', '#00FF33', '#00FF66',
    '#00FF99', '#00FFCC', '#00FFFF', '#00CCFF', '#0099FF', '#0066FF',
    '#0033FF', '#0000FF', '#3300FF', '#6600FF', '#9900FF', '#CC00FF',
    '#FF00FF', '#FF00CC', '#FF0099', '#FF0066', '#FF0033'
  ];

  return (
    <div className={clsx('ev2-space-y-1', className)}>
      {label && (
        <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700">
          {label}
        </label>
      )}
      
      <div className="ev2-border ev2-border-gray-300 ev2-rounded-md ev2-overflow-hidden ev2-bg-white">
        {/* Toolbar */}
        <div className="ev2-p-2 ev2-bg-gray-50 ev2-border-b ev2-border-gray-200">
          <div className="ev2-flex ev2-items-center ev2-gap-1 ev2-flex-wrap">
            {/* Format buttons */}
            <div className="ev2-flex ev2-gap-1">
              <ToolbarButton
                onClick={() => execCommand('bold')}
                active={isCommandActive('bold')}
                title="Bold (Ctrl+B)"
              >
                <Bold className="ev2-w-4 ev2-h-4" />
              </ToolbarButton>
              
              <ToolbarButton
                onClick={() => execCommand('italic')}
                active={isCommandActive('italic')}
                title="Italic (Ctrl+I)"
              >
                <Italic className="ev2-w-4 ev2-h-4" />
              </ToolbarButton>
              
              <ToolbarButton
                onClick={() => execCommand('underline')}
                active={isCommandActive('underline')}
                title="Underline (Ctrl+U)"
              >
                <Underline className="ev2-w-4 ev2-h-4" />
              </ToolbarButton>
            </div>

            <div className="ev2-w-px ev2-h-6 ev2-bg-gray-300" />

            {/* Alignment buttons */}
            <div className="ev2-flex ev2-gap-1">
              <ToolbarButton
                onClick={() => execCommand('justifyLeft')}
                active={isCommandActive('justifyLeft')}
                title="Align Left"
              >
                <AlignLeft className="ev2-w-4 ev2-h-4" />
              </ToolbarButton>
              
              <ToolbarButton
                onClick={() => execCommand('justifyCenter')}
                active={isCommandActive('justifyCenter')}
                title="Align Center"
              >
                <AlignCenter className="ev2-w-4 ev2-h-4" />
              </ToolbarButton>
              
              <ToolbarButton
                onClick={() => execCommand('justifyRight')}
                active={isCommandActive('justifyRight')}
                title="Align Right"
              >
                <AlignRight className="ev2-w-4 ev2-h-4" />
              </ToolbarButton>
            </div>

            <div className="ev2-w-px ev2-h-6 ev2-bg-gray-300" />

            {/* List buttons */}
            <div className="ev2-flex ev2-gap-1">
              <ToolbarButton
                onClick={() => execCommand('insertUnorderedList')}
                active={isCommandActive('insertUnorderedList')}
                title="Bullet List"
              >
                <List className="ev2-w-4 ev2-h-4" />
              </ToolbarButton>
              
              <ToolbarButton
                onClick={() => execCommand('insertOrderedList')}
                active={isCommandActive('insertOrderedList')}
                title="Numbered List"
              >
                <ListOrdered className="ev2-w-4 ev2-h-4" />
              </ToolbarButton>
            </div>

            <div className="ev2-w-px ev2-h-6 ev2-bg-gray-300" />

            {/* Link buttons */}
            <div className="ev2-flex ev2-gap-1">
              <ToolbarButton
                onClick={() => {
                  const url = prompt('Enter URL:');
                  if (url) execCommand('createLink', url);
                }}
                title="Insert Link"
              >
                <Link className="ev2-w-4 ev2-h-4" />
              </ToolbarButton>
              
              <ToolbarButton
                onClick={() => execCommand('unlink')}
                title="Remove Link"
              >
                <Unlink className="ev2-w-4 ev2-h-4" />
              </ToolbarButton>
            </div>

            <div className="ev2-w-px ev2-h-6 ev2-bg-gray-300" />

            {/* Color picker */}
            <div className="ev2-relative">
              <ToolbarButton
                onClick={() => setShowColorPicker(!showColorPicker)}
                title="Text Color"
              >
                <Palette className="ev2-w-4 ev2-h-4" />
              </ToolbarButton>
              
              {showColorPicker && (
                <div 
                  ref={colorPickerRef}
                  className="ev2-absolute ev2-top-full ev2-left-0 ev2-mt-1 ev2-p-3 ev2-bg-white ev2-border ev2-border-gray-300 ev2-rounded-md ev2-shadow-lg ev2-z-10"
                  style={{ width: '240px' }}
                >
                  <div className="ev2-grid ev2-grid-cols-6 ev2-gap-1">
                    {commonColors.map((color) => (
                      <ColorButton
                        key={color}
                        color={color}
                        onClick={(color) => {
                          execCommand('foreColor', color);
                          setShowColorPicker(false);
                        }}
                      />
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Editor */}
        <div
          ref={editorRef}
          contentEditable
          onInput={handleInput}
          onKeyDown={handleKeyDown}
          className="ev2-p-3 ev2-min-h-[150px] ev2-text-sm ev2-text-gray-900 focus:ev2-outline-none"
          style={{ minHeight: height }}
          suppressContentEditableWarning={true}
        />
      </div>
    </div>
  );
}

export default RichTextEditor;