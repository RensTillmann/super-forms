import React from 'react';
import { CheckCircle, AlertCircle, HelpCircle, X } from 'lucide-react';
import { ToastProps, ToastType } from '../types/toast.types';

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
      <button
        className="toast-close"
        onClick={handleClose}
        title="Close"
        aria-label="Close notification"
      >
        <X size={14} />
      </button>
    </div>
  );
};