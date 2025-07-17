import React, { useEffect, useState } from 'react';
import { DndContext, DragOverlay, closestCenter } from '@dnd-kit/core';
import useEmailBuilder from '../../hooks/useEmailBuilder';
import useEmailStore from '../../hooks/useEmailStore';
import ElementPaletteHorizontal from './ElementPaletteHorizontal';
import CapabilityBasedPropertyPanel from '../PropertyPanels/CapabilityBasedPropertyPanel';
import CanvasIntegrated from './CanvasIntegrated';
import GmailChrome from '../Preview/ClientChrome/GmailChrome';
import OutlookChrome from '../Preview/ClientChrome/OutlookChrome';
import AppleMailChrome from '../Preview/ClientChrome/AppleMailChrome';
import { ChevronLeft, ChevronRight, Monitor, Smartphone, X } from 'lucide-react';

const EMAIL_CLIENTS = [
  { id: 'gmail-desktop', name: 'Gmail', icon: '📧', component: GmailChrome },
  { id: 'outlook-desktop', name: 'Outlook', icon: '📨', component: OutlookChrome },
  { id: 'apple-mail', name: 'Apple Mail', icon: '🍎', component: AppleMailChrome },
];

function EmailBuilderIntegrated({ email, onChange, onClose }) {
  const [selectedClient, setSelectedClient] = useState('gmail-desktop');
  const [isMobile, setIsMobile] = useState(false);
  const { 
    selectedElementId, 
    setSelectedElementId,
    setElements, 
    elements,
    isDragging,
    draggedElement,
    startDrag,
    endDrag,
    addElement,
    moveElement,
    generateHtml
  } = useEmailBuilder();

  const { i18n } = useEmailStore();

  // Initialize elements from email template if it exists
  useEffect(() => {
    if (email?.template && typeof email.template === 'object' && email.template.elements) {
      setElements(email.template.elements);
    }
  }, [email?.template, setElements]);

  // Handle changes from the builder
  useEffect(() => {
    if (onChange && elements.length > 0) {
      const html = generateHtml();
      const timeoutId = setTimeout(() => {
        onChange({
          elements: elements,
          html: html
        });
      }, 0);
      return () => clearTimeout(timeoutId);
    }
  }, [elements, generateHtml, onChange]);

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

  const ClientComponent = EMAIL_CLIENTS.find(c => c.id === selectedClient)?.component || GmailChrome;

  // Create email data for the preview
  const emailData = {
    ...email,
    body: elements.length > 0 ? generateHtml() : email.body
  };

  return (
    <DndContext
      collisionDetection={closestCenter}
      onDragEnd={handleDragEnd}
      onDragStart={handleDragStart}
    >
      <div className="ev2-flex ev2-h-screen ev2-flex-col ev2-bg-gray-50">
        {/* Main Content Area */}
        <div className="ev2-flex-1 ev2-flex ev2-overflow-hidden">
          {/* Email Client Preview with Builder */}
          <div className="ev2-flex-1 ev2-flex ev2-flex-col">
            {/* Client Selector */}
            <div className="ev2-bg-white ev2-border-b ev2-px-4 ev2-py-2">
              <div className="ev2-flex ev2-items-center ev2-justify-between">
                <div className="ev2-flex ev2-items-center ev2-gap-2">
                  {EMAIL_CLIENTS.map((client) => (
                    <button
                      key={client.id}
                      onClick={() => setSelectedClient(client.id)}
                      className={`ev2-px-3 ev2-py-1.5 ev2-rounded-md ev2-text-sm ev2-font-medium ev2-transition-colors ${
                        selectedClient === client.id
                          ? 'ev2-bg-blue-100 ev2-text-blue-700'
                          : 'ev2-text-gray-600 hover:ev2-bg-gray-100'
                      }`}
                    >
                      {client.name}
                    </button>
                  ))}
                </div>
                <div className="ev2-flex ev2-items-center ev2-gap-2">
                  <button
                    onClick={() => setIsMobile(false)}
                    className={`ev2-p-1.5 ev2-rounded ${
                      !isMobile ? 'ev2-bg-gray-200' : 'hover:ev2-bg-gray-100'
                    }`}
                    title="Desktop view"
                  >
                    <Monitor className="ev2-w-4 ev2-h-4" />
                  </button>
                  <button
                    onClick={() => setIsMobile(true)}
                    className={`ev2-p-1.5 ev2-rounded ${
                      isMobile ? 'ev2-bg-gray-200' : 'hover:ev2-bg-gray-100'
                    }`}
                    title="Mobile view"
                  >
                    <Smartphone className="ev2-w-4 ev2-h-4" />
                  </button>
                  <div className="ev2-ml-4 ev2-h-6 ev2-w-px ev2-bg-gray-300"></div>
                  {onClose && (
                    <button
                      onClick={onClose}
                      className="ev2-ml-4 ev2-p-1.5 ev2-rounded hover:ev2-bg-gray-100 ev2-text-gray-600"
                      title="Close template builder"
                    >
                      <X className="ev2-w-5 ev2-h-5" />
                    </button>
                  )}
                </div>
              </div>
            </div>

            {/* Email Client Chrome with Builder Canvas */}
            <div className="ev2-flex-1 ev2-overflow-auto ev2-p-4">
              <div className={isMobile ? 'ev2-max-w-sm ev2-mx-auto' : 'ev2-max-w-4xl ev2-mx-auto'}>
                <ClientComponent 
                  email={emailData} 
                  i18n={i18n} 
                  isMobile={isMobile}
                  isBuilder={true}
                  renderBody={() => <CanvasIntegrated />}
                />
              </div>
            </div>
          </div>

          {/* Properties Panel */}
          {selectedElementId && (
            <CapabilityBasedPropertyPanel
              element={elements.find(el => el.id === selectedElementId)}
              onElementUpdate={(id, property, value) => {
                // TODO: Implement updateElement in useEmailBuilder
                console.log('Update element:', id, property, value);
              }}
              onClose={() => setSelectedElementId(null)}
            />
          )}
        </div>

        {/* Bottom Element Palette */}
        <div className="ev2-bg-white ev2-border-t">
          <ElementPaletteHorizontal />
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

export default EmailBuilderIntegrated;