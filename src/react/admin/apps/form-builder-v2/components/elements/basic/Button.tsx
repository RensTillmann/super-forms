import React, { useState, useCallback } from 'react';
import { Loader2, Check, AlertCircle } from 'lucide-react';
import { Button as ShadcnButton } from '../../../../../components/ui/button';
import { cn } from '../../../../../lib/utils';
import type { ResolvedStyles } from '../../../../../lib/styleUtils';
import {
  triggerButtonClick,
  handleFrontendEventResponse,
  type FrontendEventResponse,
} from '../../../../../lib/frontendEvents';

type ButtonActionType =
  | 'submit'
  | 'save_state'
  | 'reset'
  | 'navigate'
  | 'trigger_automation'
  | 'open_overlay'
  | 'toggle_visibility'
  | 'copy_to_clipboard';

type ButtonVariant = 'primary' | 'secondary' | 'outline' | 'ghost' | 'link' | 'destructive';
type ButtonSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl';

interface ButtonElementProps {
  element: {
    type: 'button';
    id: string;
    properties?: {
      // General
      name?: string;
      buttonText?: string;
      actionType?: ButtonActionType;
      eventId?: string;
      // Navigation
      navigateDirection?: 'next' | 'previous' | 'first' | 'last' | 'specific';
      targetStep?: string;
      // Save state
      saveState?: 'draft' | 'pending_review' | 'incomplete';
      // Overlay
      overlayType?: 'modal' | 'dialog' | 'drawer' | 'tray' | 'sheet' | 'popup';
      overlayTitle?: string;
      overlayContent?: string;
      overlaySize?: 'sm' | 'md' | 'lg' | 'xl' | 'fullscreen';
      // Toggle visibility
      targetElements?: string[];
      visibilityAction?: 'toggle' | 'show' | 'hide';
      // Copy to clipboard
      copySource?: 'static' | 'field' | 'template';
      copyContent?: string;
      sourceField?: string;
      // Icon
      icon?: string;
      iconPosition?: 'left' | 'right';
      // Appearance
      variant?: ButtonVariant;
      size?: ButtonSize;
      fullWidth?: boolean;
      alignment?: 'left' | 'center' | 'right';
      // Behavior
      validateBeforeAction?: boolean;
      disabledUntilValid?: boolean;
      confirmBeforeAction?: boolean;
      confirmationMessage?: string;
      // Feedback
      loadingText?: string;
      successText?: string;
      successDuration?: number;
      successMessage?: string;
      errorMessage?: string;
      awaitResponse?: boolean;
      // Testing
      testId?: string;
    };
  };
  styles: ResolvedStyles;
  // Form context (provided by parent)
  formId?: number;
  formData?: Record<string, unknown>;
  sessionKey?: string;
  entryId?: number;
  // Callbacks
  onNavigate?: (direction: string, targetStep?: string) => void;
  onOverlay?: (type: string, title?: string, content?: string) => void;
  onToggleVisibility?: (elementIds: string[], action: string) => void;
  onSubmit?: () => void;
  onReset?: () => void;
  onSaveState?: (state: string) => void;
  onSuccess?: (response: FrontendEventResponse) => void;
  onError?: (error: string) => void;
}

type ButtonState = 'idle' | 'loading' | 'success' | 'error';

const sizeClasses: Record<ButtonSize, string> = {
  xs: 'h-7 px-2 text-xs',
  sm: 'h-8 px-3 text-sm',
  md: 'h-9 px-4 text-sm',
  lg: 'h-10 px-6 text-base',
  xl: 'h-12 px-8 text-lg',
};

const variantMap: Record<ButtonVariant, 'default' | 'secondary' | 'outline' | 'ghost' | 'link' | 'destructive'> = {
  primary: 'default',
  secondary: 'secondary',
  outline: 'outline',
  ghost: 'ghost',
  link: 'link',
  destructive: 'destructive',
};

export const Button: React.FC<ButtonElementProps> = ({
  element,
  styles,
  formId,
  formData = {},
  sessionKey,
  entryId,
  onNavigate,
  onOverlay,
  onToggleVisibility,
  onSubmit,
  onReset,
  onSaveState,
  onSuccess,
  onError,
}) => {
  const { properties = {} } = element;
  const {
    name,
    buttonText = 'Submit',
    actionType = 'submit',
    eventId,
    navigateDirection,
    targetStep,
    saveState,
    overlayType,
    overlayTitle,
    overlayContent,
    targetElements,
    visibilityAction,
    copySource,
    copyContent,
    sourceField,
    // icon and iconPosition reserved for future dynamic icon support
    variant = 'primary',
    size = 'md',
    fullWidth = false,
    alignment = 'left',
    confirmBeforeAction = false,
    confirmationMessage = 'Are you sure?',
    loadingText,
    successText,
    successDuration = 2000,
    successMessage,
    errorMessage,
    awaitResponse = true,
    testId,
  } = properties;

  const [buttonState, setButtonState] = useState<ButtonState>('idle');

  // Get current display text based on state
  const getDisplayText = useCallback(() => {
    switch (buttonState) {
      case 'loading':
        return loadingText || buttonText;
      case 'success':
        return successText || buttonText;
      default:
        return buttonText;
    }
  }, [buttonState, buttonText, loadingText, successText]);

  // Handle automation trigger
  const handleAutomationTrigger = useCallback(async () => {
    if (!eventId) {
      onError?.('No event ID configured for this button');
      return;
    }

    if (!formId) {
      onError?.('Form ID not available');
      return;
    }

    setButtonState('loading');

    try {
      const response = await triggerButtonClick({
        formId,
        buttonId: element.id,
        eventId,
        buttonName: name,
        formData,
        sessionKey,
        entryId,
      });

      if (response.success) {
        setButtonState('success');

        // Handle response actions (file download, redirect, etc.)
        handleFrontendEventResponse(response, {
          onMessage: (msg) => {
            // Use custom success message if provided, otherwise use response message
            const displayMessage = successMessage || msg;
            if (displayMessage) {
              // In a real implementation, this would show a toast
              console.log('[Button] Success:', displayMessage);
            }
          },
          onFileDownload: (url) => {
            window.open(url, '_blank');
          },
          onRedirect: (url) => {
            window.location.href = url;
          },
          onAccessUrl: (url) => {
            // Copy to clipboard and show notification
            navigator.clipboard.writeText(url).then(() => {
              console.log('[Button] Access URL copied:', url);
            });
          },
        });

        onSuccess?.(response);

        // Reset after success duration
        if (successText) {
          setTimeout(() => {
            setButtonState('idle');
          }, successDuration);
        } else {
          setButtonState('idle');
        }
      } else {
        setButtonState('error');
        const errMsg = response.error?.message || errorMessage || 'Action failed';
        onError?.(errMsg);
        console.error('[Button] Error:', errMsg);

        // Reset after a delay
        setTimeout(() => {
          setButtonState('idle');
        }, 3000);
      }
    } catch (err) {
      setButtonState('error');
      const errMsg = err instanceof Error ? err.message : 'Network error';
      onError?.(errMsg);
      console.error('[Button] Exception:', errMsg);

      setTimeout(() => {
        setButtonState('idle');
      }, 3000);
    }
  }, [
    eventId,
    formId,
    element.id,
    name,
    formData,
    sessionKey,
    entryId,
    successMessage,
    successText,
    successDuration,
    errorMessage,
    onSuccess,
    onError,
  ]);

  // Handle copy to clipboard
  const handleCopyToClipboard = useCallback(async () => {
    let content = '';

    if (copySource === 'field' && sourceField) {
      content = String(formData[sourceField] || '');
    } else if (copySource === 'template' || copySource === 'static') {
      content = copyContent || '';
      // TODO: Replace {tags} in template with actual values
    }

    try {
      await navigator.clipboard.writeText(content);
      setButtonState('success');
      setTimeout(() => setButtonState('idle'), successDuration);
    } catch (err) {
      setButtonState('error');
      onError?.('Failed to copy to clipboard');
      setTimeout(() => setButtonState('idle'), 3000);
    }
  }, [copySource, sourceField, copyContent, formData, successDuration, onError]);

  // Main click handler
  const handleClick = useCallback(async () => {
    // Confirmation dialog
    if (confirmBeforeAction) {
      const confirmed = window.confirm(confirmationMessage);
      if (!confirmed) return;
    }

    switch (actionType) {
      case 'submit':
        onSubmit?.();
        break;

      case 'reset':
        onReset?.();
        break;

      case 'save_state':
        if (saveState) {
          onSaveState?.(saveState);
        }
        break;

      case 'navigate':
        if (navigateDirection) {
          onNavigate?.(navigateDirection, targetStep);
        }
        break;

      case 'trigger_automation':
        if (awaitResponse) {
          await handleAutomationTrigger();
        } else {
          // Fire and forget
          handleAutomationTrigger();
        }
        break;

      case 'open_overlay':
        if (overlayType) {
          onOverlay?.(overlayType, overlayTitle, overlayContent);
        }
        break;

      case 'toggle_visibility':
        if (targetElements && visibilityAction) {
          onToggleVisibility?.(targetElements, visibilityAction);
        }
        break;

      case 'copy_to_clipboard':
        await handleCopyToClipboard();
        break;
    }
  }, [
    actionType,
    confirmBeforeAction,
    confirmationMessage,
    saveState,
    navigateDirection,
    targetStep,
    awaitResponse,
    overlayType,
    overlayTitle,
    overlayContent,
    targetElements,
    visibilityAction,
    handleAutomationTrigger,
    handleCopyToClipboard,
    onSubmit,
    onReset,
    onSaveState,
    onNavigate,
    onOverlay,
    onToggleVisibility,
  ]);

  // Render state icon
  const renderStateIcon = () => {
    switch (buttonState) {
      case 'loading':
        return <Loader2 className="mr-2 h-4 w-4 animate-spin" data-testid="button-loader" />;
      case 'success':
        return <Check className="mr-2 h-4 w-4" data-testid="button-success-icon" />;
      case 'error':
        return <AlertCircle className="mr-2 h-4 w-4" data-testid="button-error-icon" />;
      default:
        return null;
    }
  };

  // Alignment wrapper
  const alignmentClasses = !fullWidth
    ? {
        left: 'justify-start',
        center: 'justify-center',
        right: 'justify-end',
      }[alignment]
    : '';

  return (
    <div
      className={cn('flex', alignmentClasses)}
      style={styles.wrapper}
      data-element-id={element.id}
      data-element-type="button"
    >
      <ShadcnButton
        type={actionType === 'submit' ? 'submit' : 'button'}
        variant={variantMap[variant]}
        className={cn(
          sizeClasses[size],
          fullWidth && 'w-full',
          buttonState === 'loading' && 'cursor-wait',
          buttonState === 'success' && 'bg-green-600 hover:bg-green-600',
          buttonState === 'error' && 'bg-red-600 hover:bg-red-600'
        )}
        style={styles.button}
        disabled={buttonState === 'loading'}
        onClick={handleClick}
        data-testid={testId || `button-${element.id}`}
        data-button-state={buttonState}
        data-action-type={actionType}
      >
        {renderStateIcon()}
        <span>{getDisplayText()}</span>
      </ShadcnButton>
    </div>
  );
};

export default Button;
