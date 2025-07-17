import { create } from 'zustand';
import { v4 as uuidv4 } from 'uuid';

const useEmailBuilderStore = create((set, get) => ({
  // Canvas state
  elements: [],
  selectedElementId: null,
  isDragging: false,
  draggedElement: null,
  
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
        content: '<p>Enter your text here...</p>',
        fontSize: 14,
        fontFamily: 'Arial, sans-serif',
        color: '#333333',
        lineHeight: 1.6,
        align: 'left',
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
  },
  
  // Actions
  addElement: (type, parentId = null, position = null) => {
    const { elementTypes } = get();
    const elementType = elementTypes[type];
    
    if (!elementType) return;
    
    const newElement = {
      id: uuidv4(),
      type,
      props: { ...elementType.defaultProps },
      children: [],
    };
    
    set((state) => {
      if (parentId) {
        // Add to parent's children
        return {
          elements: addToParent(state.elements, parentId, newElement, position),
        };
      } else {
        // Add to root
        const elements = [...state.elements];
        if (position !== null) {
          elements.splice(position, 0, newElement);
        } else {
          elements.push(newElement);
        }
        return { elements };
      }
    });
    
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
      
      // Add to new position
      if (targetId) {
        elements = addToParent(elements, targetId, element, position);
      } else {
        // Add to root at position
        elements.splice(position, 0, element);
      }
      
      return { elements };
    });
  },
  
  selectElement: (id) => {
    set({ selectedElementId: id });
  },
  
  clearCanvas: () => {
    set({
      elements: [],
      selectedElementId: null,
    });
  },

  setElements: (elements) => {
    set({ elements: elements || [] });
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

// Custom hook with computed values
const useEmailBuilder = () => {
  const store = useEmailBuilderStore();
  
  const selectedElement = store.selectedElementId 
    ? findElement(store.elements, store.selectedElementId)
    : null;
  
  return {
    ...store,
    selectedElement,
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

export default useEmailBuilder;

// Helper functions
function addToParent(elements, parentId, newElement, position) {
  // Handle column drops - parentId format: "parentElementId-col-columnIndex"
  if (parentId && parentId.includes('-col-')) {
    const [actualParentId, , columnIndex] = parentId.split('-');
    const colIndex = parseInt(columnIndex);
    
    return elements.map(element => {
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
  return elements.map(element => {
    if (element.id === parentId) {
      const children = [...element.children];
      if (position !== null && position !== undefined) {
        children.splice(position, 0, newElement);
      } else {
        children.push(newElement);
      }
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

function updateInTree(elements, id, updates) {
  return elements.map(element => {
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
          children: element.children.map(column => ({
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

function removeFromTree(elements, id, callback) {
  return elements.filter(element => {
    if (element.id === id) {
      if (callback) callback(element);
      return false;
    }
    if (element.children && element.children.length > 0) {
      if (element.type === 'columns') {
        // Handle column structure
        element.children = element.children.map(column => ({
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
function generateEmailHtml(elements) {
  const renderElement = (element) => {
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

