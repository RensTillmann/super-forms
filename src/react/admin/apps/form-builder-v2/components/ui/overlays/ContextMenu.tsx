import React, { useEffect, useRef, useState } from 'react';
import { 
  Edit3, Copy, Scissors, Trash2, ArrowUp, ArrowDown, 
  ChevronRight, MoreHorizontal 
} from 'lucide-react';
import { ContextMenuProps, ContextMenuItem } from '../types/overlay.types';

const defaultMenuItems: ContextMenuItem[] = [
  { id: 'edit', label: 'Edit Properties', icon: <Edit3 size={16} />, shortcut: '⌘E', action: 'edit' },
  { id: 'duplicate', label: 'Duplicate', icon: <Copy size={16} />, shortcut: '⌘D', action: 'duplicate' },
  { id: 'copy', label: 'Copy', icon: <Copy size={16} />, shortcut: '⌘C', action: 'copy' },
  { id: 'cut', label: 'Cut', icon: <Scissors size={16} />, shortcut: '⌘X', action: 'cut' },
  { id: 'divider1', divider: true },
  { id: 'move-up', label: 'Move Up', icon: <ArrowUp size={16} />, action: 'move-up' },
  { id: 'move-down', label: 'Move Down', icon: <ArrowDown size={16} />, action: 'move-down' },
  { id: 'divider2', divider: true },
  { id: 'delete', label: 'Delete', icon: <Trash2 size={16} />, shortcut: '⌫', action: 'delete', danger: true }
];

export const ContextMenu: React.FC<ContextMenuProps> = ({ 
  x, 
  y, 
  onClose, 
  onAction,
  items = defaultMenuItems
}) => {
  const menuRef = useRef<HTMLDivElement>(null);
  const [position, setPosition] = useState({ x, y });
  const [activeSubmenu, setActiveSubmenu] = useState<string | null>(null);

  useEffect(() => {
    const handleClick = (e: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        onClose();
      }
    };

    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        onClose();
      }
    };

    const handleScroll = () => onClose();

    // Adjust position if menu would go off-screen
    if (menuRef.current) {
      const rect = menuRef.current.getBoundingClientRect();
      const newPosition = { ...position };

      if (rect.right > window.innerWidth) {
        newPosition.x = window.innerWidth - rect.width - 10;
      }
      if (rect.bottom > window.innerHeight) {
        newPosition.y = window.innerHeight - rect.height - 10;
      }

      if (newPosition.x !== position.x || newPosition.y !== position.y) {
        setPosition(newPosition);
      }
    }

    document.addEventListener('click', handleClick);
    document.addEventListener('keydown', handleEscape);
    document.addEventListener('scroll', handleScroll, true);

    return () => {
      document.removeEventListener('click', handleClick);
      document.removeEventListener('keydown', handleEscape);
      document.removeEventListener('scroll', handleScroll, true);
    };
  }, [onClose, position, x, y]);

  const handleItemClick = (item: ContextMenuItem) => {
    if (item.disabled || item.divider) return;
    
    if (item.children) {
      setActiveSubmenu(item.id === activeSubmenu ? null : item.id);
    } else if (item.action) {
      onAction(item.action);
      onClose();
    }
  };

  const renderMenuItem = (item: ContextMenuItem) => {
    if (item.divider) {
      return <div key={item.id} className="context-menu-divider" />;
    }

    const hasSubmenu = item.children && item.children.length > 0;

    return (
      <div
        key={item.id}
        className={`context-menu-item ${item.danger ? 'context-menu-item-danger' : ''} 
                   ${item.disabled ? 'context-menu-item-disabled' : ''}`}
        onClick={() => handleItemClick(item)}
        onMouseEnter={() => hasSubmenu && setActiveSubmenu(item.id)}
      >
        {item.icon && <span className="context-menu-icon">{item.icon}</span>}
        <span className="context-menu-label">{item.label}</span>
        {item.shortcut && (
          <span className="context-menu-shortcut">{item.shortcut}</span>
        )}
        {hasSubmenu && (
          <ChevronRight size={14} className="context-menu-submenu-icon" />
        )}
        
        {hasSubmenu && activeSubmenu === item.id && (
          <div className="context-submenu">
            {item.children!.map(renderMenuItem)}
          </div>
        )}
      </div>
    );
  };

  return (
    <div
      ref={menuRef}
      className="context-menu"
      style={{ left: position.x, top: position.y }}
      role="menu"
      onClick={(e) => e.stopPropagation()}
    >
      {items.map(renderMenuItem)}
    </div>
  );
};