import React, { useEffect, useState } from 'react';
import { DndContext, DragOverlay, closestCenter } from '@dnd-kit/core';
import useEmailBuilder from '../hooks/useEmailBuilder';
import useEmailStore from '../hooks/useEmailStore';
import ElementPaletteHorizontal from './ElementPaletteHorizontal';
import OptimizedPropertyPanel from '../PropertyPanels/OptimizedPropertyPanel';
import CanvasIntegrated from './CanvasIntegrated';
import GmailChrome from '../Preview/ClientChrome/GmailChrome';
import OutlookChrome from '../Preview/ClientChrome/OutlookChrome';
import AppleMailChrome from '../Preview/ClientChrome/AppleMailChrome';
import { ChevronLeft, ChevronRight, Monitor, Smartphone, X } from 'lucide-react';

// Helper function to find element in tree
const findElement = (elements, id) => {
  for (const element of elements) {
    if (element.id === id) {
      return element;
    }
    if (element.children && element.children.length > 0) {
      if (element.type === 'columns') {
        // Handle column structure
        for (const column of element.children) {
          const found = findElement(column.children || [], id);
          if (found) return found;
        }
      } else {
        const found = findElement(element.children, id);
        if (found) return found;
      }
    }
  }
  return null;
};

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
    selectElement,
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

    console.log('🎯 Drag End Event:', {
      activeId: active.id,
      overId: over.id,
      activeData,
      overData
    });

    if (activeData.type === 'new-element') {
      // Adding new element from palette
      let position = overData?.position !== undefined ? overData.position : null;
      let parentId = overData?.parentId || null;
      
      // Handle email container drop zones
      if (overData?.type === 'email-container-drop' || overData?.type === 'email-container-empty-drop') {
        parentId = overData.parentId;
        position = 0; // Always add at the beginning for container drops
        console.log('📧 Email container drop detected:', { parentId, position });
      }
      
      console.log('➕ Adding element:', { 
        elementType: activeData.elementType, 
        parentId, 
        position 
      });
      
      addElement(activeData.elementType, parentId, position);
    } else if (activeData.type === 'canvas-element') {
      // Moving existing element
      if (active.id !== over.id) {
        let position = overData?.position !== undefined ? overData.position : null;
        let parentId = overData?.parentId || null;
        
        // Handle email container drop zones for moves too
        if (overData?.type === 'email-container-drop' || overData?.type === 'email-container-empty-drop') {
          parentId = overData.parentId;
          position = 0;
        }
        
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
      <div className="flex h-screen flex-col bg-gray-50">
        {/* Main Content Area */}
        <div className="flex-1 flex overflow-hidden">
          {/* Email Client Preview with Builder */}
          <div className="flex-1 flex flex-col">
            {/* Client Selector */}
            <div className="bg-white border-b px-4 py-2">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  {EMAIL_CLIENTS.map((client) => (
                    <button
                      key={client.id}
                      onClick={() => setSelectedClient(client.id)}
                      className={`px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${
                        selectedClient === client.id
                          ? 'bg-blue-100 text-blue-700'
                          : 'text-gray-600 hover:bg-gray-100'
                      }`}
                    >
                      {client.name}
                    </button>
                  ))}
                </div>
                <div className="flex items-center gap-2">
                  <button
                    onClick={() => setIsMobile(false)}
                    className={`p-1.5 rounded ${
                      !isMobile ? 'bg-gray-200' : 'hover:bg-gray-100'
                    }`}
                    title="Desktop view"
                  >
                    <Monitor className="w-4 h-4" />
                  </button>
                  <button
                    onClick={() => setIsMobile(true)}
                    className={`p-1.5 rounded ${
                      isMobile ? 'bg-gray-200' : 'hover:bg-gray-100'
                    }`}
                    title="Mobile view"
                  >
                    <Smartphone className="w-4 h-4" />
                  </button>
                  <div className="ml-4 h-6 w-px bg-gray-300"></div>
                  {onClose && (
                    <button
                      onClick={onClose}
                      className="ml-4 p-1.5 rounded hover:bg-gray-100 text-gray-600"
                      title="Close template builder"
                    >
                      <X className="w-5 h-5" />
                    </button>
                  )}
                </div>
              </div>
            </div>

            {/* Email Client Chrome with Builder Canvas */}
            <div className="flex-1 overflow-auto p-4">
              <div className={isMobile ? 'max-w-sm mx-auto' : 'max-w-4xl mx-auto'}>
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

          {/* Properties Panel - Don't show for email wrapper (background-only element) */}
          {selectedElementId && (
            (() => {
              const selectedElement = findElement(elements, selectedElementId);
              // Don't show property panel for email wrapper - it only uses the color picker
              if (selectedElement?.type === 'emailWrapper') {
                return null;
              }
              return (
                <OptimizedPropertyPanel
                  elementId={selectedElementId}
                  onClose={() => selectElement(null)}
                />
              );
            })()
          )}
        </div>

        {/* Bottom Element Palette */}
        <div className="bg-white border-t">
          <ElementPaletteHorizontal />
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

export default EmailBuilderIntegrated;