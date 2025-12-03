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
        'p-2 rounded transition-all border',
        active 
          ? 'bg-blue-100 text-blue-700 border-blue-300' 
          : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50',
        disabled && 'opacity-50 cursor-not-allowed'
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
      className="w-6 h-6 rounded border border-gray-300 cursor-pointer hover:scale-110 transition-transform"
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
    <div className={clsx('space-y-1', className)}>
      {label && (
        <label className="text-sm font-medium text-gray-700">
          {label}
        </label>
      )}
      
      <div className="border border-gray-300 rounded-md overflow-hidden bg-white">
        {/* Toolbar */}
        <div className="p-2 bg-gray-50 border-b border-gray-200">
          <div className="flex items-center gap-1 flex-wrap">
            {/* Format buttons */}
            <div className="flex gap-1">
              <ToolbarButton
                onClick={() => execCommand('bold')}
                active={isCommandActive('bold')}
                title="Bold (Ctrl+B)"
              >
                <Bold className="w-4 h-4" />
              </ToolbarButton>
              
              <ToolbarButton
                onClick={() => execCommand('italic')}
                active={isCommandActive('italic')}
                title="Italic (Ctrl+I)"
              >
                <Italic className="w-4 h-4" />
              </ToolbarButton>
              
              <ToolbarButton
                onClick={() => execCommand('underline')}
                active={isCommandActive('underline')}
                title="Underline (Ctrl+U)"
              >
                <Underline className="w-4 h-4" />
              </ToolbarButton>
            </div>

            <div className="w-px h-6 bg-gray-300" />

            {/* Alignment buttons */}
            <div className="flex gap-1">
              <ToolbarButton
                onClick={() => execCommand('justifyLeft')}
                active={isCommandActive('justifyLeft')}
                title="Align Left"
              >
                <AlignLeft className="w-4 h-4" />
              </ToolbarButton>
              
              <ToolbarButton
                onClick={() => execCommand('justifyCenter')}
                active={isCommandActive('justifyCenter')}
                title="Align Center"
              >
                <AlignCenter className="w-4 h-4" />
              </ToolbarButton>
              
              <ToolbarButton
                onClick={() => execCommand('justifyRight')}
                active={isCommandActive('justifyRight')}
                title="Align Right"
              >
                <AlignRight className="w-4 h-4" />
              </ToolbarButton>
            </div>

            <div className="w-px h-6 bg-gray-300" />

            {/* List buttons */}
            <div className="flex gap-1">
              <ToolbarButton
                onClick={() => execCommand('insertUnorderedList')}
                active={isCommandActive('insertUnorderedList')}
                title="Bullet List"
              >
                <List className="w-4 h-4" />
              </ToolbarButton>
              
              <ToolbarButton
                onClick={() => execCommand('insertOrderedList')}
                active={isCommandActive('insertOrderedList')}
                title="Numbered List"
              >
                <ListOrdered className="w-4 h-4" />
              </ToolbarButton>
            </div>

            <div className="w-px h-6 bg-gray-300" />

            {/* Link buttons */}
            <div className="flex gap-1">
              <ToolbarButton
                onClick={() => {
                  const url = prompt('Enter URL:');
                  if (url) execCommand('createLink', url);
                }}
                title="Insert Link"
              >
                <Link className="w-4 h-4" />
              </ToolbarButton>
              
              <ToolbarButton
                onClick={() => execCommand('unlink')}
                title="Remove Link"
              >
                <Unlink className="w-4 h-4" />
              </ToolbarButton>
            </div>

            <div className="w-px h-6 bg-gray-300" />

            {/* Color picker */}
            <div className="relative">
              <ToolbarButton
                onClick={() => setShowColorPicker(!showColorPicker)}
                title="Text Color"
              >
                <Palette className="w-4 h-4" />
              </ToolbarButton>
              
              {showColorPicker && (
                <div 
                  ref={colorPickerRef}
                  className="absolute top-full left-0 mt-1 p-3 bg-white border border-gray-300 rounded-md shadow-lg z-10"
                  style={{ width: '240px' }}
                >
                  <div className="grid grid-cols-6 gap-1">
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
          className="p-3 min-h-[150px] text-sm text-gray-900 focus:outline-none"
          style={{ minHeight: height }}
          suppressContentEditableWarning={true}
        />
      </div>
    </div>
  );
}

export default RichTextEditor;