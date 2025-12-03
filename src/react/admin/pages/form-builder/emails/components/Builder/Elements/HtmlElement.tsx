import { useState, useRef, useEffect } from 'react';
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
        className="relative cursor-pointer group min-h-[40px]"
        data-component="HtmlElement"
        data-element-id={element.id}
      >
        {/* HTML indicator badge */}
        <div className="absolute top-1 right-1 bg-gray-700 text-white text-xs px-1.5 py-0.5 rounded opacity-60 group-hover:opacity-100 flex items-center gap-1 z-10">
          <Code className="w-3 h-3" />
          <span>HTML</span>
        </div>

        {/* Render the HTML content */}
        {content ? (
          <div
            className="prose max-w-none"
            dangerouslySetInnerHTML={{ __html: content }}
          />
        ) : (
          <div className="text-gray-400 text-center py-4 border-2 border-dashed border-gray-300 rounded">
            Click to add HTML code
          </div>
        )}

        {/* Edit hint on hover */}
        <div className="absolute inset-0 bg-blue-500/10 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
          <span className="bg-blue-500 text-white px-2 py-1 rounded text-sm">
            Click to edit HTML
          </span>
        </div>
      </div>
    );
  }

  // Edit mode
  return (
    <div
      className="border-2 border-blue-500 rounded overflow-hidden"
      data-component="HtmlElement"
      data-element-id={element.id}
      data-is-editing="true"
    >
      {/* Header */}
      <div className="bg-gray-800 px-3 py-2 flex items-center justify-between">
        <div className="flex items-center gap-2">
          <Code className="w-4 h-4 text-gray-400" />
          <span className="text-sm text-gray-300 font-mono">HTML Block</span>
        </div>
        <div className="flex items-center gap-2">
          <button
            onClick={() => setShowPreview(!showPreview)}
            className={clsx(
              'flex items-center gap-1 px-2 py-1 rounded text-xs transition-colors',
              showPreview
                ? 'bg-gray-600 text-white'
                : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
            )}
          >
            {showPreview ? <EyeOff className="w-3 h-3" /> : <Eye className="w-3 h-3" />}
            {showPreview ? 'Hide Preview' : 'Show Preview'}
          </button>
          <button
            onClick={handleSave}
            className="flex items-center gap-1 bg-green-600 text-white px-2 py-1 rounded text-xs hover:bg-green-700 transition-colors"
          >
            <Check className="w-3 h-3" />
            Save
          </button>
        </div>
      </div>

      {/* Editor and Preview */}
      <div className={clsx('flex', showPreview ? 'flex-row' : 'flex-col')}>
        {/* Code Editor */}
        <div className={clsx('bg-gray-900', showPreview ? 'w-1/2' : 'w-full')}>
          <textarea
            ref={textareaRef}
            value={localContent}
            onChange={(e) => setLocalContent(e.target.value)}
            onKeyDown={handleKeyDown}
            className="w-full min-h-[100px] bg-gray-900 text-gray-100 font-mono text-sm p-3 border-0 outline-none resize-none"
            placeholder="Enter your HTML code here..."
            spellCheck={false}
          />
        </div>

        {/* Preview */}
        {showPreview && (
          <div className="w-1/2 bg-white border-l border-gray-700">
            <div className="bg-gray-100 px-3 py-1 text-xs text-gray-500 border-b">
              Preview
            </div>
            <div className="p-3 prose max-w-none min-h-[100px]">
              {localContent ? (
                <div dangerouslySetInnerHTML={{ __html: localContent }} />
              ) : (
                <span className="text-gray-400">Preview will appear here...</span>
              )}
            </div>
          </div>
        )}
      </div>

      {/* Footer hint */}
      <div className="bg-gray-800 px-3 py-1 text-xs text-gray-500 text-center">
        Ctrl+Enter to save | ESC to cancel
      </div>
    </div>
  );
}

export default HtmlElement;
