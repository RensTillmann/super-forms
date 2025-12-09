import React, { useEffect, useRef } from 'react';
import { X } from 'lucide-react';
import { BasePanelProps } from '../types/panel.types';
import { Button } from '../../../../../components/ui/button';

export const BasePanel: React.FC<BasePanelProps & { children: React.ReactNode }> = ({
  isOpen,
  onClose,
  title,
  className = '',
  position = 'right',
  size = 'md',
  children
}) => {
  const panelRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isOpen) {
        onClose();
      }
    };

    const handleClickOutside = (e: MouseEvent) => {
      if (panelRef.current && !panelRef.current.contains(e.target as Node)) {
        onClose();
      }
    };

    if (isOpen) {
      document.addEventListener('keydown', handleEscape);
      document.addEventListener('mousedown', handleClickOutside);
      document.body.style.overflow = 'hidden';
    }

    return () => {
      document.removeEventListener('keydown', handleEscape);
      document.removeEventListener('mousedown', handleClickOutside);
      document.body.style.overflow = '';
    };
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  const sizeClasses = {
    sm: 'panel-sm',
    md: 'panel-md',
    lg: 'panel-lg',
    xl: 'panel-xl',
    full: 'panel-full'
  };

  const positionClasses = {
    left: 'panel-left',
    right: 'panel-right',
    center: 'panel-center'
  };

  return (
    <>
      <div className="panel-overlay" aria-hidden="true" />
      <div 
        ref={panelRef}
        className={`panel ${sizeClasses[size]} ${positionClasses[position]} ${className}`}
        role="dialog"
        aria-modal="true"
        aria-labelledby={title ? 'panel-title' : undefined}
      >
        {title && (
          <div className="panel-header">
            <h3 id="panel-title">{title}</h3>
            <Button
              variant="ghost"
              size="icon"
              onClick={onClose}
              className="h-8 w-8"
              aria-label="Close panel"
              data-testid="panel-close-button"
            >
              <X size={16} />
            </Button>
          </div>
        )}
        <div className="panel-body">
          {children}
        </div>
      </div>
    </>
  );
};