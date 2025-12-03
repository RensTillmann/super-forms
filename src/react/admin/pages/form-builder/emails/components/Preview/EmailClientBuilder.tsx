import { useState, useEffect, useCallback } from 'react';
import { DndContext, DragOverlay, closestCenter } from '@dnd-kit/core';
import clsx from 'clsx';
import GmailChrome from './ClientChrome/GmailChrome';
import { Globe, Monitor, Smartphone, Pencil } from 'lucide-react';
import CustomButton from '@/components/ui/CustomButton';
import ElementPaletteHorizontal from '../Builder/ElementPaletteHorizontal';
import CanvasIntegrated from '../Builder/CanvasIntegrated';
import TestEmailBar from '../TestEmailBar';
import InlineEditableField from '../shared/InlineEditableField';
import useEmailStore from '../../hooks/useEmailStore';
import useEmailBuilder from '../../hooks/useEmailBuilder';
import { v4 as uuidv4 } from 'uuid';

const EMAIL_CLIENTS = [
  { id: 'desktop', name: 'Desktop', icon: Monitor, component: GmailChrome },
  { id: 'mobile', name: 'Mobile', icon: Smartphone, component: GmailChrome },
];

/**
 * Inline HTML Editor for HTML mode (inside GmailChrome body area)
 */
function InlineHtmlEditor({ value, onChange }) {
  const [localValue, setLocalValue] = useState(value || '');

  useEffect(() => {
    setLocalValue(value || '');
  }, [value]);

  const handleChange = (e) => {
    const newValue = e.target.value;
    setLocalValue(newValue);
    // Debounce
    clearTimeout(window.htmlEditorTimeout);
    window.htmlEditorTimeout = setTimeout(() => {
      onChange(newValue);
    }, 300);
  };

  return (
    <div className="min-h-[300px] bg-gray-900 rounded overflow-hidden">
      <div className="bg-gray-800 px-3 py-2 text-xs text-gray-400 border-b border-gray-700 font-mono">
        HTML Source
      </div>
      <textarea
        value={localValue}
        onChange={handleChange}
        className="w-full min-h-[300px] bg-gray-900 text-gray-100 font-mono text-sm p-4 border-0 outline-none resize-y"
        placeholder="Enter your HTML email content here..."
        spellCheck={false}
      />
    </div>
  );
}

function EmailClientBuilder() {
  const [selectedClient, setSelectedClient] = useState('desktop');
  const [confirmDialog, setConfirmDialog] = useState(null);
  const [showTestEmail, setShowTestEmail] = useState(false);

  const {
    activeEmailId,
    emails,
    updateEmailField,
    activeLanguage,
    translations,
    setActiveLanguage
  } = useEmailStore();

  const {
    elements,
    setElements,
    isDragging,
    draggedElement,
    startDrag,
    endDrag,
    addElement,
    moveElement,
    generateHtml,
    elementTypes
  } = useEmailBuilder();

  const activeEmail = emails.find(e => e.id === activeEmailId);
  const ClientComponent = EMAIL_CLIENTS.find(c => c.id === selectedClient)?.component || GmailChrome;

  // Check if this email uses HTML mode
  const isHtmlMode = activeEmail?.body_type === 'html';

  // Initialize builder elements from email template
  useEffect(() => {
    if (activeEmail && !isHtmlMode) {
      if (activeEmail.template && typeof activeEmail.template === 'object' && activeEmail.template.elements) {
        setElements(activeEmail.template.elements);
      } else {
        setElements([]);
      }
    }
  }, [activeEmail, setElements, isHtmlMode]);

  // Update email template when elements change (only in visual mode)
  useEffect(() => {
    if (activeEmail && !isHtmlMode && elements.length > 0) {
      const html = generateHtml();
      const timeoutId = setTimeout(() => {
        updateEmailField(activeEmailId, 'template', {
          elements: elements,
          html: html
        });
        updateEmailField(activeEmailId, 'body', html);
      }, 500);
      return () => clearTimeout(timeoutId);
    }
  }, [elements, activeEmail, activeEmailId, generateHtml, updateEmailField, isHtmlMode]);

  /**
   * Handle mode change with confirmation and conversion
   */
  const handleModeChange = useCallback((newMode) => {
    if (newMode === 'html' && !isHtmlMode) {
      // Visual → HTML
      setConfirmDialog({
        title: 'Switch to HTML Mode',
        message: 'Your visual elements will be converted to raw HTML code. You can edit the HTML directly.',
        confirmText: 'Switch to HTML',
        onConfirm: () => {
          // Convert visual elements to HTML
          const html = generateHtml();
          updateEmailField(activeEmailId, 'body', html);
          updateEmailField(activeEmailId, 'body_type', 'html');
          setConfirmDialog(null);
        },
        onCancel: () => setConfirmDialog(null)
      });
    } else if (newMode === 'visual' && isHtmlMode) {
      // HTML → Visual
      setConfirmDialog({
        title: 'Switch to Visual Mode',
        message: 'Your HTML content will be placed in an editable HTML block within the visual builder.',
        confirmText: 'Switch to Visual',
        onConfirm: () => {
          // Create an HTML element containing the current body content
          const htmlElement = {
            id: uuidv4(),
            type: 'html',
            props: {
              content: activeEmail.body || ''
            },
            children: []
          };

          // Create a new elements array with just the HTML block
          // The ensureSystemElements will wrap it properly
          const newElements = [htmlElement];

          // Update the email
          updateEmailField(activeEmailId, 'body_type', 'visual');
          updateEmailField(activeEmailId, 'template', {
            elements: newElements,
            html: activeEmail.body || ''
          });

          // Set the elements in the builder
          setElements(newElements);

          setConfirmDialog(null);
        },
        onCancel: () => setConfirmDialog(null)
      });
    }
  }, [isHtmlMode, activeEmailId, activeEmail, generateHtml, updateEmailField, setElements]);

  const handleDragEnd = (event) => {
    const { active, over } = event;

    if (!over) {
      endDrag();
      return;
    }

    const activeData = active.data.current;
    const overData = over.data.current;

    if (activeData.type === 'new-element') {
      const position = overData?.position !== undefined ? overData.position : null;
      const parentId = overData?.parentId || null;
      addElement(activeData.elementType, parentId, position);
    } else if (activeData.type === 'canvas-element') {
      if (active.id !== over.id) {
        const position = overData?.position !== undefined ? overData.position : null;
        const parentId = overData?.parentId || null;
        moveElement(active.id, parentId, position);
      }
    }

    endDrag();
  };

  const handleDragStart = (event) => {
    const { active } = event;
    const activeData = active.data.current;

    if (activeData.type === 'new-element') {
      const elementType = useEmailBuilder.getState().elementTypes[activeData.elementType];
      startDrag(elementType);
    }
  };

  if (!activeEmail) {
    return (
      <div className="h-full flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
            />
          </svg>
          <p className="mt-2 text-gray-500">Select an email to preview</p>
        </div>
      </div>
    );
  }

  // Create email data for preview with builder content
  const emailData = {
    ...activeEmail,
    body: isHtmlMode ? activeEmail.body : (elements.length > 0 ? generateHtml() : activeEmail.body)
  };

  // Header component
  const HeaderComponent = (
    <div data-testid="email-builder-header" className="bg-white border-b border-gray-200 px-4 py-2 shadow-sm">
      <div className="flex items-center justify-between">
        <div data-testid="header-left" className="flex items-center gap-3">
          <div data-testid="email-title-section" className="flex items-center gap-2">
            <InlineEditableField
              data-testid="email-description-field"
              value={activeEmail.description}
              onChange={(newValue) => updateEmailField(activeEmailId, 'description', newValue)}
              placeholder="Untitled Email"
              className="text-sm font-medium text-gray-700"
              showEditIcon={true}
            />
            <span data-testid="mode-badge" className="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded">
              {isHtmlMode ? 'HTML' : 'Visual'}
            </span>
          </div>

          {translations && Object.keys(translations).length > 0 && (
            <div data-testid="language-selector" className="flex items-center gap-2">
              <Globe className="w-4 h-4 text-gray-500" />
              <select
                data-testid="language-dropdown"
                value={activeLanguage}
                onChange={(e) => setActiveLanguage(e.target.value)}
                className="text-sm border border-gray-300 rounded px-2 py-1 bg-white"
              >
                <option value="default">Default</option>
                {Object.entries(translations).map(([code, lang]) => (
                  <option key={code} value={code}>
                    {lang.name || code}
                  </option>
                ))}
              </select>
            </div>
          )}
        </div>

        <div data-testid="viewport-toggle" className="flex gap-2">
          {EMAIL_CLIENTS.map((client) => (
            <CustomButton
              key={client.id}
              data-testid={'viewport-' + client.id + '-btn'}
              variant="unstyled"
              size="sm"
              onClick={() => setSelectedClient(client.id)}
              className={clsx(
                'hover:text-primary-600',
                selectedClient === client.id ? 'text-primary-600' : 'text-gray-500'
              )}
            >
              <client.icon className="w-4 h-4" />
              <span>{client.name}</span>
            </CustomButton>
          ))}
        </div>
      </div>
    </div>
  );

  // Confirmation Dialog - Preline UI style modal
  const ConfirmDialogComponent = confirmDialog && (
    <div
      className="fixed inset-0 z-[80] flex items-center justify-center overflow-x-hidden overflow-y-auto bg-black/50"
    >
      <div className="bg-white border border-gray-200 shadow-lg rounded-xl max-w-lg w-full mx-4">
        {/* Header */}
        <div className="flex justify-between items-center py-3 px-4 border-b border-gray-200">
          <h3 className="font-bold text-gray-800">
            {confirmDialog.title}
          </h3>
        </div>

        {/* Body */}
        <div className="p-4">
          <p className="text-gray-600">{confirmDialog.message}</p>
        </div>

        {/* Footer */}
        <div className="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200">
          <button
            type="button"
            onClick={confirmDialog.onCancel}
            className="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50"
          >
            Cancel
          </button>
          <button
            type="button"
            onClick={confirmDialog.onConfirm}
            className="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700"
          >
            {confirmDialog.confirmText}
          </button>
        </div>
      </div>
    </div>
  );

  // Render body based on mode
  const renderBody = () => {
    if (isHtmlMode) {
      return (
        <InlineHtmlEditor
          value={activeEmail.body}
          onChange={(html) => updateEmailField(activeEmailId, 'body', html)}
        />
      );
    }
    return <CanvasIntegrated isMobile={selectedClient === 'mobile'} />;
  };

  // In HTML mode, we don't need the DndContext
  if (isHtmlMode) {
    return (
      <div data-testid="email-builder-html" className="h-full flex flex-col bg-gray-100">
        {HeaderComponent}
        {ConfirmDialogComponent}

        <div className="flex-1 overflow-hidden flex flex-col">
          <div className="flex-1 overflow-auto">
            <ClientComponent
              email={emailData}
              isMobile={selectedClient === 'mobile'}
              isBuilder={true}
              renderBody={renderBody}
              updateEmailField={updateEmailField}
              activeEmailId={activeEmailId}
              isHtmlMode={isHtmlMode}
              onModeChange={handleModeChange}
              onShowTestEmail={() => setShowTestEmail(true)}
            />
          </div>

          {/* Test Email Bar - shown when Reply/Forward clicked */}
          {showTestEmail && <TestEmailBar />}
        </div>
      </div>
    );
  }

  // Visual Builder Mode - with drag context
  return (
    <DndContext
      collisionDetection={closestCenter}
      onDragEnd={handleDragEnd}
      onDragStart={handleDragStart}
    >
      <div data-testid="email-builder-visual" className="h-full flex flex-col bg-gray-100">
        {HeaderComponent}
        {ConfirmDialogComponent}

        <div className="flex-1 overflow-hidden flex flex-col">
          <div className="flex-1 overflow-auto">
            <ClientComponent
              email={emailData}
              isMobile={selectedClient === 'mobile'}
              isBuilder={true}
              renderBody={renderBody}
              updateEmailField={updateEmailField}
              activeEmailId={activeEmailId}
              isHtmlMode={isHtmlMode}
              onModeChange={handleModeChange}
              onShowTestEmail={() => setShowTestEmail(true)}
            />
          </div>

          {/* Test Email Bar - shown when Reply/Forward clicked */}
          {showTestEmail && <TestEmailBar />}

          {/* Element Palette at the bottom */}
          <div data-testid="element-palette" className="bg-white border-t">
            <ElementPaletteHorizontal />
          </div>
        </div>
      </div>

      {/* Drag Overlay */}
      <DragOverlay>
        {draggedElement && (
          <div className="bg-white rounded shadow-lg p-3 opacity-90">
            <div className="flex items-center gap-2">
              <span className="text-lg">{draggedElement.icon}</span>
              <span className="text-sm font-medium">{draggedElement.name}</span>
            </div>
          </div>
        )}
      </DragOverlay>
    </DndContext>
  );
}

export default EmailClientBuilder;
