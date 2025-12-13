import React from 'react';
import { Info, Copy, X, Eye, EyeOff } from 'lucide-react';
import type { ResolvedStyles } from '../../../../../lib/styleUtils';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '../../../../../components/ui/tooltip';
import { cn } from '../../../../../lib/utils';

type PositionValue = 'top-left' | 'top-center' | 'top-right' | 'left' | 'center' | 'right' | 'bottom-left' | 'bottom-center' | 'bottom-right';

interface TextInputProps {
  element: {
    type: 'text' | 'email' | 'phone' | 'url' | 'password' | 'number' | 'number-formatted';
    id: string;
    properties?: {
      label?: string;
      placeholder?: string;
      description?: string;
      required?: boolean;
      width?: string | number;
      defaultValue?: string;
      // Position properties
      labelPosition?: PositionValue;
      descriptionPosition?: PositionValue;
      // Prefix/suffix
      prefixText?: string;
      suffixText?: string;
      prefixIcon?: string;
      suffixIcon?: string;
      // Advanced
      maxLength?: number;
      showCharacterCount?: boolean;
      characterCountPosition?: 'inline-end' | 'block-end';
      actionButton?: 'none' | 'copy' | 'clear' | 'toggle-visibility';
      helpTooltip?: string;
    };
  };
  styles: ResolvedStyles;
}

export const TextInput: React.FC<TextInputProps> = ({ element, styles }) => {
  const { properties = {} } = element;
  const {
    label,
    description,
    required,
    defaultValue,
    labelPosition = 'top-left',
    descriptionPosition = 'bottom-left',
    prefixText,
    suffixText,
    prefixIcon,
    suffixIcon,
    maxLength,
    showCharacterCount,
    characterCountPosition = 'block-end',
    actionButton = 'none',
    helpTooltip,
  } = properties;

  const getInputType = () => {
    switch (element.type) {
      case 'password': return 'password';
      case 'number':
      case 'number-formatted': return 'number';
      case 'email': return 'email';
      case 'phone': return 'tel';
      case 'url': return 'url';
      default: return 'text';
    }
  };

  const getPlaceholder = () => {
    if (properties.placeholder) return properties.placeholder;
    switch (element.type) {
      case 'password': return 'Enter password';
      case 'email': return 'Enter email address';
      case 'phone': return 'Enter phone number';
      case 'url': return 'Enter URL';
      case 'number':
      case 'number-formatted': return 'Enter number';
      default: return 'Enter text';
    }
  };

  const getTextAlign = (position: PositionValue): string => {
    if (position.includes('left')) return 'text-left';
    if (position.includes('center')) return 'text-center';
    if (position.includes('right')) return 'text-right';
    return 'text-left';
  };

  const isInlineLabel = labelPosition === 'left' || labelPosition === 'right';
  const charCount = (defaultValue || '').length;

  // Render label with optional help tooltip
  const renderLabel = () => {
    if (!label || labelPosition === 'center') return null;
    return (
      <label
        style={styles.label}
        className={cn('block mb-1', getTextAlign(labelPosition))}
        data-testid={`text-label-${element.id}`}
      >
        {label}
        {required && <span style={styles.required} className="ml-1">*</span>}
        {helpTooltip && (
          <Tooltip>
            <TooltipTrigger asChild>
              <Info className="inline-block w-3.5 h-3.5 ml-1 text-muted-foreground cursor-help" data-testid={`text-help-${element.id}`} />
            </TooltipTrigger>
            <TooltipContent><p>{helpTooltip}</p></TooltipContent>
          </Tooltip>
        )}
      </label>
    );
  };

  // Render description
  const renderDescription = () => {
    if (!description || descriptionPosition === 'center') return null;
    return (
      <p
        style={styles.description}
        className={cn('text-sm text-muted-foreground', getTextAlign(descriptionPosition))}
        data-testid={`text-description-${element.id}`}
      >
        {description}
      </p>
    );
  };

  // Render action button icon
  const renderActionButton = () => {
    if (actionButton === 'none') return null;
    const iconClass = 'w-4 h-4 text-muted-foreground';
    return (
      <button
        type="button"
        className="px-2 flex items-center border-l border-border bg-muted/50 opacity-50 cursor-not-allowed"
        disabled
        aria-label={actionButton === 'copy' ? 'Copy to clipboard' : actionButton === 'clear' ? 'Clear input' : 'Toggle password visibility'}
        data-testid={`text-action-${element.id}`}
      >
        {actionButton === 'copy' && <Copy className={iconClass} />}
        {actionButton === 'clear' && <X className={iconClass} />}
        {actionButton === 'toggle-visibility' && <Eye className={iconClass} />}
      </button>
    );
  };

  // Render character counter
  const renderCharCounter = () => {
    if (!showCharacterCount || !maxLength) return null;
    const countText = `${charCount} / ${maxLength}`;
    if (characterCountPosition === 'inline-end') return null; // Handled inside input group
    return (
      <div className="text-xs text-muted-foreground text-right mt-1" data-testid={`text-counter-${element.id}`}>
        {countText}
      </div>
    );
  };

  // Main input with addons
  const renderInputGroup = () => {
    const hasPrefix = prefixText || prefixIcon;
    const hasPostfix = suffixText || suffixIcon || actionButton !== 'none' ||
      (showCharacterCount && characterCountPosition === 'inline-end');

    return (
      <div
        role="group"
        aria-label={label || 'Input field'}
        className={cn('flex border rounded-md overflow-hidden', hasPrefix || hasPostfix ? 'items-stretch' : '')}
        data-testid={`text-input-group-${element.id}`}
      >
        {/* Prefix - icon placeholder shown as bullet, actual icon rendering TBD */}
        {hasPrefix && (
          <div className="flex items-center px-2 bg-muted/50 border-r border-border text-sm text-muted-foreground" data-testid={`text-prefix-${element.id}`}>
            {prefixIcon && <span className="mr-1 w-4 h-4 inline-flex items-center justify-center text-xs">•</span>}
            {prefixText}
          </div>
        )}

        {/* Input */}
        <input
          type={getInputType()}
          placeholder={getPlaceholder()}
          value={defaultValue || ''}
          disabled
          readOnly
          style={styles.input}
          className="flex-1 px-3 py-2 border-0 outline-none bg-transparent pointer-events-none"
          data-testid={`text-input-${element.id}`}
        />

        {/* Inline character count (uses defaultValue since input is disabled in canvas preview) */}
        {showCharacterCount && maxLength && characterCountPosition === 'inline-end' && (
          <div className="flex items-center px-2 text-xs text-muted-foreground" data-testid={`text-counter-inline-${element.id}`}>
            {charCount}/{maxLength}
          </div>
        )}

        {/* Suffix - icon placeholder shown as bullet, actual icon rendering TBD */}
        {(suffixText || suffixIcon) && (
          <div className="flex items-center px-2 bg-muted/50 border-l border-border text-sm text-muted-foreground" data-testid={`text-suffix-${element.id}`}>
            {suffixText}
            {suffixIcon && <span className="ml-1 w-4 h-4 inline-flex items-center justify-center text-xs">•</span>}
          </div>
        )}

        {/* Action button */}
        {renderActionButton()}
      </div>
    );
  };

  // Layout based on label position
  if (isInlineLabel) {
    return (
      <TooltipProvider>
        <div className={cn('flex items-center gap-3', labelPosition === 'right' && 'flex-row-reverse')} data-testid={`text-field-${element.id}`}>
          <div className="w-1/3 shrink-0">{renderLabel()}</div>
          <div className="flex-1">
            {descriptionPosition.startsWith('top') && renderDescription()}
            {renderInputGroup()}
            {descriptionPosition.startsWith('bottom') && renderDescription()}
            {renderCharCounter()}
          </div>
        </div>
      </TooltipProvider>
    );
  }

  // Standard vertical layout
  return (
    <TooltipProvider>
      <div data-testid={`text-field-${element.id}`}>
        {labelPosition.startsWith('top') && renderLabel()}
        {descriptionPosition.startsWith('top') && renderDescription()}

        {renderInputGroup()}

        {descriptionPosition.startsWith('bottom') && renderDescription()}
        {labelPosition.startsWith('bottom') && renderLabel()}
        {renderCharCounter()}
      </div>
    </TooltipProvider>
  );
};

export default TextInput;
