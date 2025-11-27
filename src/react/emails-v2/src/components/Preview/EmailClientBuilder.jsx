import React, { useState, useEffect, useCallback } from 'react';
import { DndContext, DragOverlay, closestCenter } from '@dnd-kit/core';
import clsx from 'clsx';
import GmailChrome from './ClientChrome/GmailChrome';
import { Globe, Monitor, Smartphone } from 'lucide-react';
import ElementPaletteHorizontal from '../Builder/ElementPaletteHorizontal';
import CanvasIntegrated from '../Builder/CanvasIntegrated';
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
    <div className="ev2-min-h-[300px] ev2-bg-gray-900 ev2-rounded ev2-overflow-hidden">
      <div className="ev2-bg-gray-800 ev2-px-3 ev2-py-2 ev2-text-xs ev2-text-gray-400 ev2-border-b ev2-border-gray-700 ev2-font-mono">
        HTML Source
      </div>
      <textarea
        value={localValue}
        onChange={handleChange}
        className="ev2-w-full ev2-min-h-[300px] ev2-bg-gray-900 ev2-text-gray-100 ev2-font-mono ev2-text-sm ev2-p-4 ev2-border-0 ev2-outline-none ev2-resize-y"
        placeholder="Enter your HTML email content here..."
        spellCheck={false}
      />
    </div>
  );
}

function EmailClientBuilder() {
  const [selectedClient, setSelectedClient] = useState('desktop');
  const [confirmDialog, setConfirmDialog] = useState(null);

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
      <div className="ev2-h-full ev2-flex ev2-items-center ev2-justify-center ev2-bg-gray-50">
        <div className="ev2-text-center">
          <svg className="ev2-mx-auto ev2-h-12 ev2-w-12 ev2-text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
            />
          </svg>
          <p className="ev2-mt-2 ev2-text-gray-500">Select an email to preview</p>
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
    <div className="ev2-bg-white ev2-border-b ev2-px-4 ev2-py-2">
      <div className="ev2-flex ev2-items-center ev2-justify-between">
        <div className="ev2-flex ev2-items-center ev2-gap-3">
          <h3 className="ev2-text-sm ev2-font-medium ev2-text-gray-700">
            {isHtmlMode ? 'Email Editor' : 'Email Builder'}
          </h3>

          {translations && Object.keys(translations).length > 0 && (
            <div className="ev2-flex ev2-items-center ev2-gap-2">
              <Globe className="ev2-w-4 ev2-h-4 ev2-text-gray-500" />
              <select
                value={activeLanguage}
                onChange={(e) => setActiveLanguage(e.target.value)}
                className="ev2-text-sm ev2-border ev2-border-gray-300 ev2-rounded ev2-px-2 ev2-py-1 ev2-bg-white"
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

        <div className="ev2-flex ev2-gap-1">
          {EMAIL_CLIENTS.map((client) => {
            const IconComponent = client.icon;
            return (
              <button
                key={client.id}
                onClick={() => setSelectedClient(client.id)}
                className={clsx(
                  'ev2-px-3 ev2-py-1 ev2-rounded-md ev2-text-sm ev2-transition-colors ev2-flex ev2-items-center ev2-gap-1',
                  selectedClient === client.id
                    ? 'ev2-bg-primary-500 ev2-text-white'
                    : 'ev2-bg-gray-100 ev2-text-gray-600 hover:ev2-bg-gray-200'
                )}
                title={client.name}
              >
                <IconComponent className="ev2-w-4 ev2-h-4" />
                <span className="ev2-hidden sm:ev2-inline">{client.name}</span>
              </button>
            );
          })}
        </div>
      </div>
    </div>
  );

  // Confirmation Dialog
  const ConfirmDialogComponent = confirmDialog && (
    <div className="ev2-fixed ev2-inset-0 ev2-bg-black/50 ev2-flex ev2-items-center ev2-justify-center ev2-z-50">
      <div className="ev2-bg-white ev2-rounded-lg ev2-shadow-xl ev2-max-w-md ev2-w-full ev2-mx-4">
        <div className="ev2-px-6 ev2-py-4 ev2-border-b">
          <h3 className="ev2-text-lg ev2-font-semibold ev2-text-gray-900">{confirmDialog.title}</h3>
        </div>
        <div className="ev2-px-6 ev2-py-4">
          <p className="ev2-text-gray-600">{confirmDialog.message}</p>
        </div>
        <div className="ev2-px-6 ev2-py-4 ev2-bg-gray-50 ev2-flex ev2-justify-end ev2-gap-3 ev2-rounded-b-lg">
          <button
            onClick={confirmDialog.onCancel}
            className="ev2-px-4 ev2-py-2 ev2-text-gray-700 ev2-bg-gray-200 ev2-rounded-md hover:ev2-bg-gray-300 ev2-transition-colors"
          >
            Cancel
          </button>
          <button
            onClick={confirmDialog.onConfirm}
            className="ev2-px-4 ev2-py-2 ev2-text-white ev2-bg-blue-600 ev2-rounded-md hover:ev2-bg-blue-700 ev2-transition-colors"
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
      <div className="ev2-h-full ev2-flex ev2-flex-col ev2-bg-gray-100">
        {HeaderComponent}
        {ConfirmDialogComponent}

        <div className="ev2-flex-1 ev2-overflow-hidden ev2-flex ev2-flex-col">
          <div className="ev2-flex-1 ev2-overflow-auto">
            <ClientComponent
              email={emailData}
              isMobile={selectedClient === 'mobile'}
              isBuilder={true}
              renderBody={renderBody}
              updateEmailField={updateEmailField}
              activeEmailId={activeEmailId}
              isHtmlMode={isHtmlMode}
              onModeChange={handleModeChange}
            />
          </div>
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
      <div className="ev2-h-full ev2-flex ev2-flex-col ev2-bg-gray-100">
        {HeaderComponent}
        {ConfirmDialogComponent}

        <div className="ev2-flex-1 ev2-overflow-hidden ev2-flex ev2-flex-col">
          <div className="ev2-flex-1 ev2-overflow-auto">
            <ClientComponent
              email={emailData}
              isMobile={selectedClient === 'mobile'}
              isBuilder={true}
              renderBody={renderBody}
              updateEmailField={updateEmailField}
              activeEmailId={activeEmailId}
              isHtmlMode={isHtmlMode}
              onModeChange={handleModeChange}
            />
          </div>

          {/* Element Palette at the bottom */}
          <div className="ev2-bg-white ev2-border-t">
            <ElementPaletteHorizontal />
          </div>
        </div>
      </div>

      {/* Drag Overlay */}
      <DragOverlay>
        {draggedElement && (
          <div className="ev2-bg-white ev2-rounded ev2-shadow-lg ev2-p-3 ev2-opacity-90">
            <div className="ev2-flex ev2-items-center ev2-gap-2">
              <span className="ev2-text-lg">{draggedElement.icon}</span>
              <span className="ev2-text-sm ev2-font-medium">{draggedElement.name}</span>
            </div>
          </div>
        )}
      </DragOverlay>
    </DndContext>
  );
}

export default EmailClientBuilder;
