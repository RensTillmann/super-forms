import React, { useState, useEffect } from 'react';
import { DndContext, DragOverlay, closestCenter } from '@dnd-kit/core';
import clsx from 'clsx';
import GmailChrome from './ClientChrome/GmailChrome';
import { Globe } from 'lucide-react';
import ElementPaletteHorizontal from '../Builder/ElementPaletteHorizontal';
import CanvasIntegrated from '../Builder/CanvasIntegrated';
import CapabilityBasedPropertyPanel from '../PropertyPanels/CapabilityBasedPropertyPanel';
import useEmailStore from '../../hooks/useEmailStore';
import useEmailBuilder from '../../hooks/useEmailBuilder';

const EMAIL_CLIENTS = [
  { id: 'desktop', name: 'Desktop', icon: '💻', component: GmailChrome },
  { id: 'mobile', name: 'Mobile', icon: '📱', component: GmailChrome },
];

function EmailClientBuilder() {
  const [selectedClient, setSelectedClient] = useState('desktop');
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
    selectedElementId,
    isDragging,
    draggedElement,
    startDrag,
    endDrag,
    addElement,
    moveElement,
    generateHtml
  } = useEmailBuilder();
  
  const activeEmail = emails.find(e => e.id === activeEmailId);
  const ClientComponent = EMAIL_CLIENTS.find(c => c.id === selectedClient)?.component || GmailChrome;

  // Initialize builder elements from email template
  useEffect(() => {
    if (activeEmail?.template && typeof activeEmail.template === 'object' && activeEmail.template.elements) {
      setElements(activeEmail.template.elements);
    }
  }, [activeEmail?.template, setElements]);

  // Update email template when elements change
  useEffect(() => {
    if (activeEmail && elements.length > 0) {
      const html = generateHtml();
      const timeoutId = setTimeout(() => {
        updateEmailField(activeEmailId, 'template', {
          elements: elements,
          html: html
        });
        // Also update the body field with the generated HTML
        updateEmailField(activeEmailId, 'body', html);
      }, 500); // Debounce updates
      return () => clearTimeout(timeoutId);
    }
  }, [elements, activeEmail, activeEmailId, generateHtml, updateEmailField]);

  const handleDragEnd = (event) => {
    const { active, over } = event;
    
    if (!over) {
      endDrag();
      return;
    }

    const activeData = active.data.current;
    const overData = over.data.current;

    if (activeData.type === 'new-element') {
      // Adding new element from palette
      const position = overData?.position !== undefined ? overData.position : null;
      const parentId = overData?.parentId || null;
      
      addElement(activeData.elementType, parentId, position);
    } else if (activeData.type === 'canvas-element') {
      // Moving existing element
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
    body: elements.length > 0 ? generateHtml() : activeEmail.body
  };

  return (
    <DndContext
      collisionDetection={closestCenter}
      onDragEnd={handleDragEnd}
      onDragStart={handleDragStart}
    >
      <div className="ev2-h-full ev2-flex ev2-flex-col ev2-bg-gray-100">
        {/* Client Selector */}
        <div className="ev2-bg-white ev2-border-b ev2-px-4 ev2-py-2">
          <div className="ev2-flex ev2-items-center ev2-justify-between">
            <div className="ev2-flex ev2-items-center ev2-gap-3">
              <h3 className="ev2-text-sm ev2-font-medium ev2-text-gray-700">Email Builder</h3>
              
              {/* Language Selector */}
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
              {EMAIL_CLIENTS.map((client) => (
                <button
                  key={client.id}
                  onClick={() => setSelectedClient(client.id)}
                  className={clsx(
                    'ev2-px-3 ev2-py-1 ev2-rounded-md ev2-text-sm ev2-transition-colors',
                    selectedClient === client.id
                      ? 'ev2-bg-primary-500 ev2-text-white'
                      : 'ev2-bg-gray-100 ev2-text-gray-600 hover:ev2-bg-gray-200'
                  )}
                  title={client.name}
                >
                  <span className="ev2-mr-1">{client.icon}</span>
                  <span className="ev2-hidden sm:ev2-inline">{client.name}</span>
                </button>
              ))}
            </div>
          </div>
        </div>

        {/* Email Client Preview with Builder */}
        <div className="ev2-flex-1 ev2-overflow-hidden ev2-flex ev2-flex-col">
          <div className="ev2-flex-1 ev2-overflow-auto">
            <ClientComponent 
              email={emailData} 
              isMobile={selectedClient === 'mobile'}
              isBuilder={true}
              renderBody={() => <CanvasIntegrated />}
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