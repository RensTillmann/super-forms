import { create } from 'zustand';
import { v4 as uuidv4 } from 'uuid';
import { useMemo } from 'react';

const useEmailBuilderStore = create((set, get) => ({
  // Canvas state
  elements: [],
  selectedElementId: null,
  isDragging: false,
  draggedElement: null,
  editingTextElementId: null, // Track which text element is currently being edited
  
  // Template state
  currentTemplate: null,
  savedTemplates: [],
  templateCategories: [
    { id: 'welcome', name: 'Welcome/Onboarding', count: 5 },
    { id: 'transactional', name: 'Transactional', count: 5 },
    { id: 'newsletter', name: 'Newsletter', count: 5 },
    { id: 'notification', name: 'Notification', count: 5 },
    { id: 'minimal', name: 'Minimal', count: 3 },
  ],
  
  // Preview state
  previewClient: 'gmail-desktop',
  previewData: {},
  
  // Element types
  elementTypes: {
    emailWrapper: {
      id: 'emailWrapper',
      name: 'Email Wrapper',
      icon: '📧',
      isSystemElement: true,
      canDelete: false,
      defaultProps: {
        backgroundColor: '#f5f5f5', // Light grey default
      }
    },
    emailContainer: {
      id: 'emailContainer', 
      name: 'Email Container',
      icon: '📄',
      isSystemElement: true,
      canDelete: false,
      defaultProps: {
        width: '600px',
        margin: { top: 50, right: 'auto', bottom: 50, left: 'auto' },
        border: { top: 0, right: 0, bottom: 0, left: 0 },
        borderStyle: 'solid',
        borderColor: '#e5e5e5',
        padding: { top: 60, right: 30, bottom: 60, left: 30 },
        backgroundColor: '#ffffff',
        backgroundImage: '',
        backgroundImageId: null,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        backgroundRepeat: 'no-repeat',
        boxShadow: 'none',
        borderRadius: 0,
      }
    },
    section: {
      id: 'section',
      name: 'Section',
      icon: '📦',
      defaultProps: {
        margin: { top: 0, right: 0, bottom: 0, left: 0 },
        border: { top: 0, right: 0, bottom: 0, left: 0 },
        borderStyle: 'solid',
        borderColor: '#000000',
        padding: { top: 20, right: 20, bottom: 20, left: 20 },
        backgroundColor: '#ffffff',
        backgroundImage: '',
        backgroundImageId: null,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        backgroundRepeat: 'no-repeat',
        fullWidth: false,
      }
    },
    columns: {
      id: 'columns',
      name: 'Columns',
      icon: '🏛️',
      defaultProps: {
        columns: 2,
        gap: 20,
        stackOnMobile: true,
      }
    },
    text: {
      id: 'text',
      name: 'Text',
      icon: '📝',
      defaultProps: {
        content: 'Click to edit this text...',
        fontSize: 16,
        fontFamily: 'Arial, sans-serif',
        color: '#333333',
        lineHeight: 1.6,
        align: 'left',
        width: '100%',
        margin: { top: 0, right: 0, bottom: 0, left: 0 },
        padding: { top: 0, right: 0, bottom: 0, left: 0 },
      }
    },
    image: {
      id: 'image',
      name: 'Image',
      icon: '🖼️',
      defaultProps: {
        src: '',
        alt: '',
        width: '100%',
        height: 'auto',
        align: 'center',
        link: '',
      }
    },
    button: {
      id: 'button',
      name: 'Button',
      icon: '🔘',
      defaultProps: {
        text: 'Click Here',
        href: '#',
        backgroundColor: '#0066cc',
        color: '#ffffff',
        fontSize: 16,
        fontWeight: 'bold',
        padding: { top: 12, right: 24, bottom: 12, left: 24 },
        borderRadius: 4,
        align: 'center',
        fullWidth: false,
      }
    },
    divider: {
      id: 'divider',
      name: 'Divider',
      icon: '➖',
      defaultProps: {
        height: 1,
        color: '#dddddd',
        style: 'solid',
        margin: { top: 20, bottom: 20 },
      }
    },
    spacer: {
      id: 'spacer',
      name: 'Spacer',
      icon: '↕️',
      defaultProps: {
        height: 20,
      }
    },
    social: {
      id: 'social',
      name: 'Social Icons',
      icon: '🌐',
      defaultProps: {
        icons: ['facebook', 'twitter', 'instagram', 'linkedin'],
        iconSize: 32,
        iconColor: '#333333',
        spacing: 10,
        align: 'center',
      }
    },
    formData: {
      id: 'formData',
      name: 'Form Data',
      icon: '📊',
      defaultProps: {
        field: '',
        fallback: '',
        format: 'text',
      }
    },
    html: {
      id: 'html',
      name: 'HTML',
      icon: '</>',
      defaultProps: {
        content: '<p>Your HTML content here...</p>',
      }
    },
  },
  
  // Actions
  addElement: (type, parentId = null, position = null) => {
    console.log('🚀 addElement called:', { type, parentId, position });
    
    const { elementTypes } = get();
    const elementType = elementTypes[type];
    
    if (!elementType) {
      console.error('❌ Element type not found:', type);
      return;
    }
    
    // Don't allow manual creation of system elements
    if (elementType.isSystemElement) {
      console.log('⚠️ Skipping system element creation:', type);
      return;
    }
    
    const newElement = {
      id: uuidv4(),
      type,
      props: { ...elementType.defaultProps },
      children: [],
    };
    
    console.log('📝 Created new element:', newElement);
    
    set((state) => {
      console.log('📊 Current state before adding:', state.elements);
      const elements = get().ensureSystemElements(state.elements);
      console.log('📊 State after ensuring system elements:', elements);
      
      if (parentId) {
        console.log('📎 Adding to parent:', parentId);
        const result = {
          elements: addToParent(elements, parentId, newElement, position),
        };
        console.log('📊 Final state after addToParent:', result.elements);
        return result;
      } else {
        console.log('📎 Adding to root - finding email container...');
        // For root additions, add to email container instead
        const findElementByType = (elements, type) => {
          for (const element of elements) {
            if (element.type === type) {
              return element;
            }
            if (element.children && element.children.length > 0) {
              const found = findElementByType(element.children, type);
              if (found) return found;
            }
          }
          return null;
        };
        
        const container = findElementByType(elements, 'emailContainer');
        console.log('📧 Found email container:', container);
        
        if (container) {
          console.log('📎 Adding to email container:', container.id);
          const result = {
            elements: addToParent(elements, container.id, newElement, position),
          };
          console.log('📊 Final state after addToParent to container:', result.elements);
          return result;
        } else {
          console.log('⚠️ No email container found, adding to root');
          // Fallback to root if no container found
          const resultElements = [...elements];
          if (position !== null) {
            resultElements.splice(position, 0, newElement);
          } else {
            resultElements.push(newElement);
          }
          console.log('📊 Final state (root fallback):', resultElements);
          return { elements: resultElements };
        }
      }
    });
    
    console.log('✅ addElement completed, returning:', newElement.id);
    return newElement.id;
  },
  
  updateElement: (id, updates) => {
    set((state) => ({
      elements: updateInTree(state.elements, id, updates),
    }));
  },
  
  deleteElement: (id) => {
    set((state) => ({
      elements: removeFromTree(state.elements, id),
      selectedElementId: state.selectedElementId === id ? null : state.selectedElementId,
    }));
  },
  
  moveElement: (elementId, targetId, position) => {
    set((state) => {
      let elements = [...state.elements];
      
      // Find and remove element
      let element = null;
      elements = removeFromTree(elements, elementId, (removed) => {
        element = removed;
      });
      
      if (!element) return state;
      
      // Don't allow moving system elements
      if (element.type === 'emailWrapper' || element.type === 'emailContainer') {
        console.log('⚠️ Cannot move system elements');
        return state;
      }
      
      // Add to new position
      if (targetId) {
        elements = addToParent(elements, targetId, element, position);
      } else {
        // For root additions, add to email container instead
        const findElementByType = (elements, type) => {
          for (const element of elements) {
            if (element.type === type) {
              return element;
            }
            if (element.children && element.children.length > 0) {
              const found = findElementByType(element.children, type);
              if (found) return found;
            }
          }
          return null;
        };
        
        const container = findElementByType(elements, 'emailContainer');
        if (container) {
          console.log('📎 Moving to email container:', container.id);
          elements = addToParent(elements, container.id, element, position);
        } else {
          console.log('⚠️ No email container found, cannot add element');
          // Don't add to root if no container found
          return state;
        }
      }
      
      return { elements };
    });
  },
  
  selectElement: (id) => {
    console.log('selectElement called with id:', id);
    set({ selectedElementId: id });
    console.log('selectedElementId set to:', id);
  },
  
  clearCanvas: () => {
    set({
      elements: [],
      selectedElementId: null,
    });
  },

  setElements: (elements) => {
    const systemElements = get().ensureSystemElements(elements || []);
    set({ elements: systemElements });
  },
  
  // Ensure system elements (email wrapper and container) are always present
  ensureSystemElements: (elements) => {
    const { elementTypes } = get();
    let result = [...elements];
    
    // Helper function to find element recursively
    const findElementByType = (elements, type) => {
      for (const element of elements) {
        if (element.type === type) {
          return element;
        }
        if (element.children && element.children.length > 0) {
          const found = findElementByType(element.children, type);
          if (found) return found;
        }
      }
      return null;
    };
    
    // Check if email wrapper exists (should be at root level)
    let wrapper = result.find(el => el.type === 'emailWrapper');
    if (!wrapper) {
      wrapper = {
        id: `email-wrapper-${Date.now()}`,
        type: 'emailWrapper',
        props: { ...elementTypes.emailWrapper.defaultProps },
        children: []
      };
      result.unshift(wrapper); // Add at beginning
    }
    
    // Check if email container exists (should be child of wrapper)
    let container = findElementByType([wrapper], 'emailContainer');
    if (!container) {
      container = {
        id: `email-container-${Date.now()}`,
        type: 'emailContainer', 
        props: { ...elementTypes.emailContainer.defaultProps },
        children: []
      };
      wrapper.children.unshift(container); // Add as first child of wrapper
    }
    
    // Move any existing user content to be children of the container
    const userElements = result.filter(el => el.type !== 'emailWrapper');
    if (userElements.length > 0) {
      container.children.push(...userElements);
      result = result.filter(el => el.type === 'emailWrapper');
    }
    
    return result;
  },
  
  // Template actions
  saveAsTemplate: (name, description, category) => {
    const { elements } = get();
    const template = {
      id: uuidv4(),
      name,
      description,
      category,
      elements: JSON.parse(JSON.stringify(elements)),
      createdAt: new Date().toISOString(),
      thumbnail: null, // TODO: Generate thumbnail
    };
    
    set((state) => ({
      savedTemplates: [...state.savedTemplates, template],
    }));
    
    // TODO: Save to backend
    
    return template;
  },
  
  loadTemplate: (templateId) => {
    const { savedTemplates } = get();
    const template = savedTemplates.find(t => t.id === templateId);
    
    if (template) {
      set({
        elements: JSON.parse(JSON.stringify(template.elements)),
        currentTemplate: template,
        selectedElementId: null,
      });
    }
  },
  
  deleteTemplate: (templateId) => {
    set((state) => ({
      savedTemplates: state.savedTemplates.filter(t => t.id !== templateId),
    }));
    
    // TODO: Delete from backend
  },
  
  // HTML generation
  generateHtml: () => {
    const { elements } = get();
    return generateEmailHtml(elements);
  },
  
  // Drag and drop
  startDrag: (elementType) => {
    set({
      isDragging: true,
      draggedElement: elementType,
    });
  },
  
  endDrag: () => {
    set({
      isDragging: false,
      draggedElement: null,
    });
  },
  
  // Text editing state
  setEditingTextElement: (elementId) => {
    set({ editingTextElementId: elementId });
  },
  
  // Move element up or down within its parent
  moveElementUpDown: (elementId, direction) => {
    const state = get();
    const info = findElementParentAndPosition(state.elements, elementId);
    
    if (!info) {
      console.log('Element not found:', elementId);
      return;
    }
    
    const { parent, parentId, position } = info;
    const siblings = parent ? parent.children : state.elements;
    
    // Calculate new position
    const newPosition = direction === 'up' ? position - 1 : position + 1;
    
    // Check bounds
    if (newPosition < 0 || newPosition >= siblings.length) {
      console.log('Cannot move element beyond bounds');
      return;
    }
    
    // Use existing moveElement function
    const targetParentId = parentId || null;
    get().moveElement(elementId, targetParentId, newPosition);
  },
}));

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

// Helper function to find element's parent and position
const findElementParentAndPosition = (elements, elementId, parent = null, parentId = null) => {
  for (let i = 0; i < elements.length; i++) {
    const element = elements[i];
    if (element.id === elementId) {
      return { parent, parentId, position: i };
    }
    if (element.children && element.children.length > 0) {
      if (element.type === 'columns') {
        // Handle column structure
        for (const column of element.children) {
          const found = findElementParentAndPosition(column.children || [], elementId, column, column.id);
          if (found) return found;
        }
      } else {
        const found = findElementParentAndPosition(element.children, elementId, element, element.id);
        if (found) return found;
      }
    }
  }
  return null;
};

// Custom hook with optimized selectors
const useEmailBuilder = () => {
  // Get stable function references (these don't change)
  const selectElement = useEmailBuilderStore(state => state.selectElement);
  const updateElement = useEmailBuilderStore(state => state.updateElement);
  const addElement = useEmailBuilderStore(state => state.addElement);
  const deleteElement = useEmailBuilderStore(state => state.deleteElement);
  const moveElement = useEmailBuilderStore(state => state.moveElement);
  const setElements = useEmailBuilderStore(state => state.setElements);
  const clearCanvas = useEmailBuilderStore(state => state.clearCanvas);
  const generateHtml = useEmailBuilderStore(state => state.generateHtml);
  const startDrag = useEmailBuilderStore(state => state.startDrag);
  const endDrag = useEmailBuilderStore(state => state.endDrag);
  const setEditingTextElement = useEmailBuilderStore(state => state.setEditingTextElement);
  const moveElementUpDown = useEmailBuilderStore(state => state.moveElementUpDown);
  
  // Only subscribe to specific state when needed
  const selectedElementId = useEmailBuilderStore(state => state.selectedElementId);
  const isDragging = useEmailBuilderStore(state => state.isDragging);
  const draggedElement = useEmailBuilderStore(state => state.draggedElement);
  const elementTypes = useEmailBuilderStore(state => state.elementTypes);
  const editingTextElementId = useEmailBuilderStore(state => state.editingTextElementId);
  
  // Optimized selector for elements - re-render when structure OR properties change
  const elements = useEmailBuilderStore(state => state.elements);
  
  const selectedElement = useMemo(() => {
    return selectedElementId 
      ? findElement(elements, selectedElementId)
      : null;
  }, [selectedElementId, elements]);
  
  return {
    elements,
    selectedElementId,
    selectedElement,
    isDragging,
    draggedElement,
    elementTypes,
    editingTextElementId,
    selectElement,
    updateElement,
    addElement,
    deleteElement,
    moveElement,
    setElements,
    clearCanvas,
    generateHtml,
    startDrag,
    endDrag,
    setEditingTextElement,
    moveElementUpDown,
  };
};

// Add getState method for components that need it
useEmailBuilder.getState = () => {
  const state = useEmailBuilderStore.getState();
  const selectedElement = state.selectedElementId 
    ? findElement(state.elements, state.selectedElementId)
    : null;
  
  return {
    ...state,
    selectedElement,
  };
};

// Optimized hook for PropertyPanel - only subscribes to selected element
export const useSelectedElement = () => {
  const selectedElementId = useEmailBuilderStore(state => state.selectedElementId);
  const updateElement = useEmailBuilderStore(state => state.updateElement);
  const selectElement = useEmailBuilderStore(state => state.selectElement);
  
  // Only get the selected element from store - SIMPLIFIED to avoid comparison issues
  const element = useEmailBuilderStore(state => {
    console.log('useSelectedElement selector called');
    if (!selectedElementId) return null;
    const found = findElementById(state.elements, selectedElementId);
    console.log('Found element in selector:', found?.id, found?.type);
    return found;
  });
  
  return {
    element,
    selectedElementId,
    updateElement,
    selectElement
  };
};

// Helper function to find element by ID in the store
function findElementById(elements, id) {
  for (const element of elements) {
    if (element.id === id) {
      return element;
    }
    if (element.children && element.children.length > 0) {
      if (element.type === 'columns') {
        for (const column of element.children) {
          const found = findElementById(column.children || [], id);
          if (found) return found;
        }
      } else {
        const found = findElementById(element.children, id);
        if (found) return found;
      }
    }
  }
  return null;
}

export { useEmailBuilderStore };
export default useEmailBuilder;

// Helper functions
function addToParent(elements: any, parentId: any, newElement: any, position: any) {
  console.log('🔧 addToParent called:', { parentId, newElement, position });
  console.log('🔧 Elements structure:', elements);
  
  // Handle column drops - parentId format: "parentElementId-col-columnIndex"
  if (parentId && parentId.includes('-col-')) {
    const [actualParentId, , columnIndex] = parentId.split('-');
    const colIndex = parseInt(columnIndex);
    
    return elements.map((element: any) => {
      if (element.id === actualParentId && element.type === 'columns') {
        const children = [...(element.children || [])];
        
        // Ensure we have enough column containers
        while (children.length <= colIndex) {
          children.push({ type: 'column', children: [] });
        }
        
        // Add to the specific column's children
        const columnChildren = [...(children[colIndex]?.children || [])];
        if (position !== null && position !== undefined) {
          columnChildren.splice(position, 0, newElement);
        } else {
          columnChildren.push(newElement);
        }
        
        children[colIndex] = { 
          type: 'column', 
          children: columnChildren 
        };
        
        return { ...element, children };
      }
      if (element.children && element.children.length > 0) {
        return {
          ...element,
          children: addToParent(element.children, parentId, newElement, position),
        };
      }
      return element;
    });
  }
  
  // Regular parent handling
  return elements.map((element: any) => {
    if (element.id === parentId) {
      console.log('🎯 Found target parent element:', element);
      const children = [...element.children];
      if (position !== null && position !== undefined) {
        children.splice(position, 0, newElement);
        console.log('📝 Added at position', position, '- new children:', children);
      } else {
        children.push(newElement);
        console.log('📝 Added at end - new children:', children);
      }
      const result = { ...element, children };
      console.log('✅ Updated parent element:', result);
      return result;
    }
    if (element.children && element.children.length > 0) {
      return {
        ...element,
        children: addToParent(element.children, parentId, newElement, position),
      };
    }
    return element;
  });
}

function updateInTree(elements: any, id: any, updates: any) {
  return elements.map((element: any) => {
    if (element.id === id) {
      if (typeof updates === 'function') {
        return updates(element);
      }
      return { ...element, ...updates };
    }
    if (element.children && element.children.length > 0) {
      if (element.type === 'columns') {
        // Handle column structure
        return {
          ...element,
          children: element.children.map((column: any) => ({
            ...column,
            children: updateInTree(column.children || [], id, updates)
          }))
        };
      } else {
        return {
          ...element,
          children: updateInTree(element.children, id, updates),
        };
      }
    }
    return element;
  });
}

function removeFromTree(elements: any, id: any, callback: any) {
  return elements.filter((element: any) => {
    if (element.id === id) {
      if (callback) callback(element);
      return false;
    }
    if (element.children && element.children.length > 0) {
      if (element.type === 'columns') {
        // Handle column structure
        element.children = element.children.map((column: any) => ({
          ...column,
          children: removeFromTree(column.children || [], id, callback)
        }));
      } else {
        element.children = removeFromTree(element.children, id, callback);
      }
    }
    return true;
  });
}

// Email HTML generator (basic implementation)
function generateEmailHtml(elements: any) {
  const renderElement = (element: any): string => {
    switch (element.type) {
      case 'section':
        const margin = element.props.margin || { top: 0, right: 0, bottom: 0, left: 0 };
        const border = element.props.border || { top: 0, right: 0, bottom: 0, left: 0 };
        const borderStyle = element.props.borderStyle || 'solid';
        const borderColor = element.props.borderColor || '#000000';
        
        const marginStyle = margin.top || margin.bottom ? 
          `<tr><td style="padding: ${margin.top}px ${margin.right}px ${margin.bottom}px ${margin.left}px;"></td></tr>` : '';
        
        const bgImageStyle = element.props.backgroundImage ? 
          `background-image: url('${element.props.backgroundImage}'); background-size: ${element.props.backgroundSize || 'cover'}; background-position: ${element.props.backgroundPosition || 'center'}; background-repeat: ${element.props.backgroundRepeat || 'no-repeat'};` : '';
        
        const hasBorder = border.top > 0 || border.right > 0 || border.bottom > 0 || border.left > 0;
        const borderStyles = hasBorder ? 
          `border-top: ${border.top}px ${borderStyle} ${borderColor}; border-right: ${border.right}px ${borderStyle} ${borderColor}; border-bottom: ${border.bottom}px ${borderStyle} ${borderColor}; border-left: ${border.left}px ${borderStyle} ${borderColor};` : '';
        
        return `${marginStyle}<tr><td style="padding: ${element.props.padding.top}px ${element.props.padding.right}px ${element.props.padding.bottom}px ${element.props.padding.left}px; background-color: ${element.props.backgroundColor}; ${bgImageStyle} ${borderStyles}">
          ${element.children.map(renderElement).join('')}
        </td></tr>`;
        
      case 'text':
        return `<div style="font-size: ${element.props.fontSize}px; color: ${element.props.color}; text-align: ${element.props.align}; line-height: ${element.props.lineHeight};">
          ${element.props.content}
        </div>`;
        
      case 'button':
        return `<div style="text-align: ${element.props.align};">
          <a href="${element.props.href}" style="display: inline-block; background-color: ${element.props.backgroundColor}; color: ${element.props.color}; padding: ${element.props.padding.top}px ${element.props.padding.right}px ${element.props.padding.bottom}px ${element.props.padding.left}px; text-decoration: none; border-radius: ${element.props.borderRadius}px; font-size: ${element.props.fontSize}px; font-weight: ${element.props.fontWeight};">
            ${element.props.text}
          </a>
        </div>`;
        
      case 'html':
        // Raw HTML element - output content directly
        return element.props.content || '';

      // Add more element types...

      default:
        return '';
    }
  };
  
  const content = elements.map(renderElement).join('');
  
  return `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Email</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4;">
  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%;">
    <tr>
      <td align="center">
        <!--[if mso]>
        <table role="presentation" width="600" cellpadding="0" cellspacing="0">
        <tr>
        <td>
        <![endif]-->
        
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
          ${content}
        </table>
        
        <!--[if mso]>
        </td>
        </tr>
        </table>
        <![endif]-->
      </td>
    </tr>
  </table>
</body>
</html>`;
}

