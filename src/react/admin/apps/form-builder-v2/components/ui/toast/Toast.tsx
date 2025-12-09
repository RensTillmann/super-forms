import React from 'react';
import { CheckCircle, AlertCircle, HelpCircle, X } from 'lucide-react';
import { ToastProps, ToastType } from '../types/toast.types';
import { Button } from '../../../../../components/ui/button';

const toastIcons: Record<ToastType, React.ReactNode> = {
  success: <CheckCircle size={18} />,
  error: <AlertCircle size={18} />,
  warning: <AlertCircle size={18} />,
  info: <HelpCircle size={18} />
};

export const Toast: React.FC<ToastProps> = ({ 
  id, 
  type, 
  message, 
  visible, 
  hiding, 
  onClose 
}) => {
  const handleClose = (e: React.MouseEvent) => {
    e.stopPropagation();
    onClose(id);
  };

  return (
    <div 
      className={`toast toast-${type} ${hiding ? 'toast-hiding' : visible ? 'toast-visible' : ''}`}
      role="alert"
      aria-live="polite"
    >
      <div className="toast-icon">
        {toastIcons[type]}
      </div>
      <div className="toast-message">
        {message}
      </div>
      <Button
        variant="ghost"
        size="icon"
        className="toast-close h-6 w-6"
        onClick={handleClose}
        title="Close"
        aria-label="Close notification"
        data-testid="toast-close-button"
      >
        <X size={14} />
      </Button>
    </div>
  );
};