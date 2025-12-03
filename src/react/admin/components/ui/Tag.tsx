import React from 'react';
import { X } from 'lucide-react';

type TagVariant = 'default' | 'email' | 'field' | 'success' | 'warning' | 'error';

interface TagProps extends React.HTMLAttributes<HTMLSpanElement> {
  children: React.ReactNode;
  onRemove?: () => void;
  variant?: TagVariant;
}

interface VariantStyle {
  background: string;
  color: string;
  border: string;
}

/**
 * Reusable Tag/Pill component
 */
export default function Tag({
  children,
  onRemove,
  variant = 'default',
  className,
  ...props
}: TagProps) {
  // Variant styles using inline styles for reliability
  const variantStyles: Record<TagVariant, VariantStyle> = {
    default: {
      background: '#f3f4f6',
      color: '#374151',
      border: '1px solid #e5e7eb',
    },
    email: {
      background: '#dbeafe',
      color: '#1d4ed8',
      border: '1px solid #bfdbfe',
    },
    field: {
      background: '#f3e8ff',
      color: '#7c3aed',
      border: '1px solid #e9d5ff',
    },
    success: {
      background: '#dcfce7',
      color: '#16a34a',
      border: '1px solid #bbf7d0',
    },
    warning: {
      background: '#fef3c7',
      color: '#d97706',
      border: '1px solid #fde68a',
    },
    error: {
      background: '#fee2e2',
      color: '#dc2626',
      border: '1px solid #fecaca',
    },
  };

  const baseStyles: React.CSSProperties = {
    display: 'inline-flex',
    alignItems: 'center',
    gap: '4px',
    padding: '2px 8px',
    borderRadius: '9999px',
    fontSize: '12px',
    fontWeight: 500,
    lineHeight: '1.4',
    ...variantStyles[variant],
  };

  const removeButtonStyles: React.CSSProperties = {
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    width: '16px',
    height: '16px',
    padding: 0,
    margin: 0,
    border: 'none',
    background: 'transparent',
    cursor: 'pointer',
    borderRadius: '50%',
    color: 'inherit',
    opacity: 0.6,
    transition: 'opacity 0.15s, color 0.15s',
  };

  const handleMouseEnter = (e: React.MouseEvent<HTMLButtonElement>) => {
    e.currentTarget.style.opacity = '1';
    e.currentTarget.style.color = '#dc2626';
  };

  const handleMouseLeave = (e: React.MouseEvent<HTMLButtonElement>) => {
    e.currentTarget.style.opacity = '0.6';
    e.currentTarget.style.color = 'inherit';
  };

  return (
    <span style={baseStyles} className={className} {...props}>
      <span>{children}</span>
      {onRemove && (
        <button
          type="button"
          onClick={onRemove}
          title="Remove"
          style={removeButtonStyles}
          onMouseEnter={handleMouseEnter}
          onMouseLeave={handleMouseLeave}
        >
          <X size={12} strokeWidth={2.5} />
        </button>
      )}
    </span>
  );
}
