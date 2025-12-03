import React, { useState, useEffect, useRef } from 'react';
import { Code, Eye, EyeOff } from 'lucide-react';
import clsx from 'clsx';

/**
 * Raw HTML Editor for emails in HTML mode
 *
 * This component provides a code editor for emails that use raw HTML instead
 * of the visual builder. This includes migrated emails and any emails where
 * the user prefers to write HTML directly.
 *
 * Features:
 * - Syntax-highlighted HTML editing (basic)
 * - Live preview toggle
 * - Info banner about HTML mode
 * - Option to convert to visual builder (future)
 */
function RawHtmlEditor({ value, onChange, isMobile = false }) {
  const [showPreview, setShowPreview] = useState(true);
  const [localValue, setLocalValue] = useState(value || '');
  const textareaRef = useRef(null);
  const debounceRef = useRef(null);

  // Sync local value with prop
  useEffect(() => {
    setLocalValue(value || '');
  }, [value]);

  // Debounced update to parent
  const handleChange = (newValue) => {
    setLocalValue(newValue);

    // Clear existing debounce
    if (debounceRef.current) {
      clearTimeout(debounceRef.current);
    }

    // Debounce the onChange call
    debounceRef.current = setTimeout(() => {
      onChange(newValue);
    }, 500);
  };

  // Cleanup debounce on unmount
  useEffect(() => {
    return () => {
      if (debounceRef.current) {
        clearTimeout(debounceRef.current);
      }
    };
  }, []);

  // Auto-resize textarea
  useEffect(() => {
    if (textareaRef.current) {
      textareaRef.current.style.height = 'auto';
      textareaRef.current.style.height = Math.max(300, textareaRef.current.scrollHeight) + 'px';
    }
  }, [localValue]);

  return (
    <div className="flex flex-col h-full">
      {/* Header with mode indicator and toggle */}
      <div className="bg-blue-50 border-b border-blue-200 px-4 py-2">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Code className="w-4 h-4 text-blue-600" />
            <span className="text-sm text-blue-800 font-medium">
              HTML Mode
            </span>
            <span className="text-xs text-blue-600">
              — Edit raw HTML directly
            </span>
          </div>
          <button
            onClick={() => setShowPreview(!showPreview)}
            className={clsx(
              'flex items-center gap-1 px-2 py-1 rounded text-sm transition-colors',
              showPreview
                ? 'bg-blue-200 text-blue-800'
                : 'bg-blue-100 text-blue-700 hover:bg-blue-200'
            )}
          >
            {showPreview ? (
              <>
                <EyeOff className="w-3.5 h-3.5" />
                <span>Hide Preview</span>
              </>
            ) : (
              <>
                <Eye className="w-3.5 h-3.5" />
                <span>Show Preview</span>
              </>
            )}
          </button>
        </div>
      </div>

      {/* Editor and Preview Split View */}
      <div className={clsx(
        'flex-1 flex overflow-hidden',
        showPreview ? 'flex-row' : 'flex-col'
      )}>
        {/* Code Editor */}
        <div className={clsx(
          'flex flex-col bg-gray-900',
          showPreview ? 'w-1/2 border-r border-gray-700' : 'w-full'
        )}>
          <div className="flex items-center gap-2 px-3 py-2 bg-gray-800 border-b border-gray-700">
            <Code className="w-4 h-4 text-gray-400" />
            <span className="text-sm text-gray-300 font-mono">HTML Source</span>
          </div>
          <div className="flex-1 overflow-auto p-2">
            <textarea
              ref={textareaRef}
              value={localValue}
              onChange={(e) => handleChange(e.target.value)}
              className="w-full h-full min-h-[300px] bg-gray-900 text-gray-100 font-mono text-sm p-2 border-0 outline-none resize-none"
              placeholder="Enter your HTML email content here..."
              spellCheck={false}
            />
          </div>
        </div>

        {/* Live Preview */}
        {showPreview && (
          <div className="w-1/2 flex flex-col bg-white">
            <div className="flex items-center gap-2 px-3 py-2 bg-gray-100 border-b border-gray-200">
              <Eye className="w-4 h-4 text-gray-500" />
              <span className="text-sm text-gray-600">Preview</span>
            </div>
            <div className={clsx(
              'flex-1 overflow-auto p-4',
              isMobile && 'max-w-[375px] mx-auto'
            )}>
              <div
                className="prose max-w-none"
                dangerouslySetInnerHTML={{ __html: localValue }}
              />
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export default RawHtmlEditor;
