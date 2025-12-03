import React, { createContext, useContext, useState, useCallback, useRef } from 'react';
import { v4 as uuidv4 } from 'uuid';
import { ToastMessage, ToastType, ToastContextValue, ToastProviderProps } from '../types/toast.types';
import { Toast } from './Toast';

const ToastContext = createContext<ToastContextValue | undefined>(undefined);

export const ToastProvider: React.FC<ToastProviderProps> = ({ 
  children, 
  maxToasts = 5,
  defaultDuration = 5000,
  position = 'top-right'
}) => {
  const [toasts, setToasts] = useState<ToastMessage[]>([]);
  const timeoutRefs = useRef<Map<string, NodeJS.Timeout>>(new Map());

  const removeToast = useCallback((id: string) => {
    // Clear any existing timeout
    const timeout = timeoutRefs.current.get(id);
    if (timeout) {
      clearTimeout(timeout);
      timeoutRefs.current.delete(id);
    }

    // Mark toast as hiding
    setToasts(current => 
      current.map(toast => 
        toast.id === id ? { ...toast, hiding: true } : toast
      )
    );

    // Remove after animation
    setTimeout(() => {
      setToasts(current => current.filter(toast => toast.id !== id));
    }, 300);
  }, []);

  const addToast = useCallback((message: string, type: ToastType = 'info', duration?: number) => {
    const id = uuidv4();
    const toastDuration = duration ?? defaultDuration;
    
    const newToast: ToastMessage = {
      id,
      type,
      message,
      duration: toastDuration,
      visible: false,
      hiding: false
    };

    setToasts(current => {
      // Limit number of toasts
      const updatedToasts = [...current, newToast];
      if (updatedToasts.length > maxToasts) {
        const toRemove = updatedToasts[0];
        removeToast(toRemove.id);
        return updatedToasts.slice(1);
      }
      return updatedToasts;
    });

    // Make toast visible after a brief delay for animation
    setTimeout(() => {
      setToasts(current => 
        current.map(toast => 
          toast.id === id ? { ...toast, visible: true } : toast
        )
      );
    }, 10);

    // Auto-dismiss if duration is set
    if (toastDuration > 0) {
      const timeout = setTimeout(() => {
        removeToast(id);
      }, toastDuration);
      timeoutRefs.current.set(id, timeout);
    }
  }, [defaultDuration, maxToasts, removeToast]);

  const clearAllToasts = useCallback(() => {
    // Clear all timeouts
    timeoutRefs.current.forEach(timeout => clearTimeout(timeout));
    timeoutRefs.current.clear();
    
    // Hide all toasts
    setToasts(current => 
      current.map(toast => ({ ...toast, hiding: true }))
    );

    // Remove all after animation
    setTimeout(() => {
      setToasts([]);
    }, 300);
  }, []);

  const contextValue: ToastContextValue = {
    toasts,
    addToast,
    removeToast,
    clearAllToasts
  };

  const positionClasses = {
    'top-right': 'toast-container-top-right',
    'top-left': 'toast-container-top-left',
    'bottom-right': 'toast-container-bottom-right',
    'bottom-left': 'toast-container-bottom-left',
    'top-center': 'toast-container-top-center',
    'bottom-center': 'toast-container-bottom-center'
  };

  return (
    <ToastContext.Provider value={contextValue}>
      {children}
      <div className={`toast-container ${positionClasses[position]}`}>
        {toasts.map(toast => (
          <Toast
            key={toast.id}
            id={toast.id}
            type={toast.type}
            message={toast.message}
            visible={toast.visible || false}
            hiding={toast.hiding}
            onClose={removeToast}
          />
        ))}
      </div>
    </ToastContext.Provider>
  );
};

export const useToast = (): ToastContextValue => {
  const context = useContext(ToastContext);
  if (!context) {
    throw new Error('useToast must be used within a ToastProvider');
  }
  return context;
};