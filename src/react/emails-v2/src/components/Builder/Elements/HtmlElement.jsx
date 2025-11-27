import React, { useState, useRef, useEffect } from 'react';
import { Code, Eye, EyeOff, Check } from 'lucide-react';
import { useEmailBuilderStore } from '../../../hooks/useEmailBuilder';
import clsx from 'clsx';

/**
 * HtmlElement - Raw HTML block for the visual canvas
 *
 * Allows users to add custom HTML code within the visual email builder.
 * Shows a code editor when clicked, with live preview toggle.
 */
function HtmlElement({ element }) {
  const { content = '' } = element.props;
  const [isEditing, setIsEditing] = useState(false);
  const [showPreview, setShowPreview] = useState(true);
  const [localContent, setLocalContent] = useState(content);
  const textareaRef = useRef(null);
  const updateElement = useEmailBuilderStore.getState().updateElement;

  // Sync local content with props
  useEffect(() => {
    if (!isEditing) {
      setLocalContent(content);
    }
  }, [content, isEditing]);

  // Auto-resize textarea
  useEffect(() => {
    if (isEditing && textareaRef.current) {
      textareaRef.current.style.height = 'auto';
      textareaRef.current.style.height = Math.max(100, textareaRef.current.scrollHeight) + 'px';
    }
  }, [localContent, isEditing]);

  const handleClick = (e) => {
    e.stopPropagation();
    if (!isEditing) {
      setIsEditing(true);
      setTimeout(() => textareaRef.current?.focus(), 0);
    }
  };

  const handleSave = () => {
    updateElement(element.id, {
      props: {
        ...element.props,
        content: localContent
      }
    });
    setIsEditing(false);
  };

  const handleKeyDown = (e) => {
    if (e.key === 'Escape') {
      e.preventDefault();
      setLocalContent(content); // Revert
      setIsEditing(false);
    }
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
      e.preventDefault();
      handleSave();
    }
  };

  // Preview mode (not editing)
  if (!isEditing) {
    return (
      <div
        onClick={handleClick}
        className="ev2-relative ev2-cursor-pointer ev2-group ev2-min-h-[40px]"
        data-component="HtmlElement"
        data-element-id={element.id}
      >
        {/* HTML indicator badge */}
        <div className="ev2-absolute ev2-top-1 ev2-right-1 ev2-bg-gray-700 ev2-text-white ev2-text-xs ev2-px-1.5 ev2-py-0.5 ev2-rounded ev2-opacity-60 group-hover:ev2-opacity-100 ev2-flex ev2-items-center ev2-gap-1 ev2-z-10">
          <Code className="ev2-w-3 ev2-h-3" />
          <span>HTML</span>
        </div>

        {/* Render the HTML content */}
        {content ? (
          <div
            className="ev2-prose ev2-max-w-none"
            dangerouslySetInnerHTML={{ __html: content }}
          />
        ) : (
          <div className="ev2-text-gray-400 ev2-text-center ev2-py-4 ev2-border-2 ev2-border-dashed ev2-border-gray-300 ev2-rounded">
            Click to add HTML code
          </div>
        )}

        {/* Edit hint on hover */}
        <div className="ev2-absolute ev2-inset-0 ev2-bg-blue-500/10 ev2-opacity-0 group-hover:ev2-opacity-100 ev2-transition-opacity ev2-flex ev2-items-center ev2-justify-center">
          <span className="ev2-bg-blue-500 ev2-text-white ev2-px-2 ev2-py-1 ev2-rounded ev2-text-sm">
            Click to edit HTML
          </span>
        </div>
      </div>
    );
  }

  // Edit mode
  return (
    <div
      className="ev2-border-2 ev2-border-blue-500 ev2-rounded ev2-overflow-hidden"
      data-component="HtmlElement"
      data-element-id={element.id}
      data-is-editing="true"
    >
      {/* Header */}
      <div className="ev2-bg-gray-800 ev2-px-3 ev2-py-2 ev2-flex ev2-items-center ev2-justify-between">
        <div className="ev2-flex ev2-items-center ev2-gap-2">
          <Code className="ev2-w-4 ev2-h-4 ev2-text-gray-400" />
          <span className="ev2-text-sm ev2-text-gray-300 ev2-font-mono">HTML Block</span>
        </div>
        <div className="ev2-flex ev2-items-center ev2-gap-2">
          <button
            onClick={() => setShowPreview(!showPreview)}
            className={clsx(
              'ev2-flex ev2-items-center ev2-gap-1 ev2-px-2 ev2-py-1 ev2-rounded ev2-text-xs ev2-transition-colors',
              showPreview
                ? 'ev2-bg-gray-600 ev2-text-white'
                : 'ev2-bg-gray-700 ev2-text-gray-300 hover:ev2-bg-gray-600'
            )}
          >
            {showPreview ? <EyeOff className="ev2-w-3 ev2-h-3" /> : <Eye className="ev2-w-3 ev2-h-3" />}
            {showPreview ? 'Hide Preview' : 'Show Preview'}
          </button>
          <button
            onClick={handleSave}
            className="ev2-flex ev2-items-center ev2-gap-1 ev2-bg-green-600 ev2-text-white ev2-px-2 ev2-py-1 ev2-rounded ev2-text-xs hover:ev2-bg-green-700 ev2-transition-colors"
          >
            <Check className="ev2-w-3 ev2-h-3" />
            Save
          </button>
        </div>
      </div>

      {/* Editor and Preview */}
      <div className={clsx('ev2-flex', showPreview ? 'ev2-flex-row' : 'ev2-flex-col')}>
        {/* Code Editor */}
        <div className={clsx('ev2-bg-gray-900', showPreview ? 'ev2-w-1/2' : 'ev2-w-full')}>
          <textarea
            ref={textareaRef}
            value={localContent}
            onChange={(e) => setLocalContent(e.target.value)}
            onKeyDown={handleKeyDown}
            className="ev2-w-full ev2-min-h-[100px] ev2-bg-gray-900 ev2-text-gray-100 ev2-font-mono ev2-text-sm ev2-p-3 ev2-border-0 ev2-outline-none ev2-resize-none"
            placeholder="Enter your HTML code here..."
            spellCheck={false}
          />
        </div>

        {/* Preview */}
        {showPreview && (
          <div className="ev2-w-1/2 ev2-bg-white ev2-border-l ev2-border-gray-700">
            <div className="ev2-bg-gray-100 ev2-px-3 ev2-py-1 ev2-text-xs ev2-text-gray-500 ev2-border-b">
              Preview
            </div>
            <div className="ev2-p-3 ev2-prose ev2-max-w-none ev2-min-h-[100px]">
              {localContent ? (
                <div dangerouslySetInnerHTML={{ __html: localContent }} />
              ) : (
                <span className="ev2-text-gray-400">Preview will appear here...</span>
              )}
            </div>
          </div>
        )}
      </div>

      {/* Footer hint */}
      <div className="ev2-bg-gray-800 ev2-px-3 ev2-py-1 ev2-text-xs ev2-text-gray-500 ev2-text-center">
        Ctrl+Enter to save | ESC to cancel
      </div>
    </div>
  );
}

export default HtmlElement;
