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
    <div className="ev2-flex ev2-flex-col ev2-h-full">
      {/* Header with mode indicator and toggle */}
      <div className="ev2-bg-blue-50 ev2-border-b ev2-border-blue-200 ev2-px-4 ev2-py-2">
        <div className="ev2-flex ev2-items-center ev2-justify-between">
          <div className="ev2-flex ev2-items-center ev2-gap-2">
            <Code className="ev2-w-4 ev2-h-4 ev2-text-blue-600" />
            <span className="ev2-text-sm ev2-text-blue-800 ev2-font-medium">
              HTML Mode
            </span>
            <span className="ev2-text-xs ev2-text-blue-600">
              — Edit raw HTML directly
            </span>
          </div>
          <button
            onClick={() => setShowPreview(!showPreview)}
            className={clsx(
              'ev2-flex ev2-items-center ev2-gap-1 ev2-px-2 ev2-py-1 ev2-rounded ev2-text-sm ev2-transition-colors',
              showPreview
                ? 'ev2-bg-blue-200 ev2-text-blue-800'
                : 'ev2-bg-blue-100 ev2-text-blue-700 hover:ev2-bg-blue-200'
            )}
          >
            {showPreview ? (
              <>
                <EyeOff className="ev2-w-3.5 ev2-h-3.5" />
                <span>Hide Preview</span>
              </>
            ) : (
              <>
                <Eye className="ev2-w-3.5 ev2-h-3.5" />
                <span>Show Preview</span>
              </>
            )}
          </button>
        </div>
      </div>

      {/* Editor and Preview Split View */}
      <div className={clsx(
        'ev2-flex-1 ev2-flex ev2-overflow-hidden',
        showPreview ? 'ev2-flex-row' : 'ev2-flex-col'
      )}>
        {/* Code Editor */}
        <div className={clsx(
          'ev2-flex ev2-flex-col ev2-bg-gray-900',
          showPreview ? 'ev2-w-1/2 ev2-border-r ev2-border-gray-700' : 'ev2-w-full'
        )}>
          <div className="ev2-flex ev2-items-center ev2-gap-2 ev2-px-3 ev2-py-2 ev2-bg-gray-800 ev2-border-b ev2-border-gray-700">
            <Code className="ev2-w-4 ev2-h-4 ev2-text-gray-400" />
            <span className="ev2-text-sm ev2-text-gray-300 ev2-font-mono">HTML Source</span>
          </div>
          <div className="ev2-flex-1 ev2-overflow-auto ev2-p-2">
            <textarea
              ref={textareaRef}
              value={localValue}
              onChange={(e) => handleChange(e.target.value)}
              className="ev2-w-full ev2-h-full ev2-min-h-[300px] ev2-bg-gray-900 ev2-text-gray-100 ev2-font-mono ev2-text-sm ev2-p-2 ev2-border-0 ev2-outline-none ev2-resize-none"
              placeholder="Enter your HTML email content here..."
              spellCheck={false}
            />
          </div>
        </div>

        {/* Live Preview */}
        {showPreview && (
          <div className="ev2-w-1/2 ev2-flex ev2-flex-col ev2-bg-white">
            <div className="ev2-flex ev2-items-center ev2-gap-2 ev2-px-3 ev2-py-2 ev2-bg-gray-100 ev2-border-b ev2-border-gray-200">
              <Eye className="ev2-w-4 ev2-h-4 ev2-text-gray-500" />
              <span className="ev2-text-sm ev2-text-gray-600">Preview</span>
            </div>
            <div className={clsx(
              'ev2-flex-1 ev2-overflow-auto ev2-p-4',
              isMobile && 'ev2-max-w-[375px] ev2-mx-auto'
            )}>
              <div
                className="ev2-prose ev2-max-w-none"
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
