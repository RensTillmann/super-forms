import React from 'react';
import clsx from 'clsx';

type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger' | 'success' | 'link' | 'pill' | 'unstyled' | 'outline' | 'outlineActive';
type ButtonSize = 'sm' | 'md' | 'lg';

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
  children?: React.ReactNode;
}

/**
 * Reusable Button component with variants
 *
 * Base styles include reset utilities (border-0, bg-transparent, cursor-pointer)
 * since we don't use Tailwind preflight globally.
 */
export default function CustomButton({
  variant = 'primary',
  size = 'md',
  disabled = false,
  className,
  children,
  ...props
}: ButtonProps) {
  // Base reset styles (replaces preflight for buttons)
  const baseStyles = [
    'inline-flex items-center justify-center gap-x-2',
    'bg-transparent cursor-pointer',
    'font-medium',
    'transition-colors duration-200',
    'focus:outline-none focus:ring-2 focus:ring-offset-2',
    disabled && 'opacity-50 cursor-not-allowed pointer-events-none',
  ];

  // Size variants
  const sizeStyles: Record<ButtonSize, string> = {
    sm: 'px-2.5 py-1.5 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-5 py-2.5 text-base',
  };

  // Visual variants - using explicit colors to override WordPress defaults
  const variantStyles: Record<ButtonVariant, string[]> = {
    primary: [
      'rounded-lg border-0 bg-primary-600 text-white shadow-sm',
      'hover:bg-primary-700 hover:text-white',
      'focus:ring-primary-500',
    ],
    secondary: [
      'rounded-lg bg-white text-gray-700 shadow-sm',
      'border border-gray-300',
      'hover:bg-gray-50 hover:text-gray-800',
      'focus:ring-gray-500',
    ],
    ghost: [
      'rounded-lg border-0 text-gray-600',
      'hover:bg-gray-100 hover:text-gray-900',
      'focus:ring-gray-500',
    ],
    danger: [
      'rounded-lg border-0 bg-red-600 text-white shadow-sm',
      'hover:bg-red-700 hover:text-white',
      'focus:ring-red-500',
    ],
    success: [
      'rounded-lg border-0 bg-green-600 text-white shadow-sm',
      'hover:bg-green-700 hover:text-white',
      'focus:ring-green-500',
    ],
    link: [
      'border-0 text-primary-600',
      'hover:text-primary-700',
      'focus:ring-0',
    ],
    pill: [
      'rounded-full bg-white text-gray-700 shadow-sm',
      'border border-gray-300',
      'hover:bg-gray-50 hover:text-gray-800',
      'focus:ring-gray-500',
    ],
    unstyled: [
      // Just the reset, no visual styles - build your own with className
      'border-0',
    ],
    outline: [
      'bg-transparent text-gray-500 rounded-md',
      'border border-gray-300',
      'hover:border-primary-600 hover:text-primary-600',
      'focus:ring-primary-500',
    ],
    outlineActive: [
      'bg-transparent text-primary-600 rounded-md',
      'border border-primary-600',
      'hover:border-primary-700 hover:text-primary-700',
      'focus:ring-primary-500',
    ],
  };

  return (
    <button
      type="button"
      disabled={disabled}
      className={clsx(
        baseStyles,
        sizeStyles[size],
        variantStyles[variant],
        className
      )}
      {...props}
    >
      {children}
    </button>
  );
}

type IconButtonVariant = 'ghost' | 'danger' | 'primary';

interface IconButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: IconButtonVariant;
  size?: ButtonSize;
  children?: React.ReactNode;
}

/**
 * Icon-only button variant
 * Smaller padding, no text expected
 */
export function IconButton({
  variant = 'ghost',
  size = 'md',
  disabled = false,
  className,
  children,
  ...props
}: IconButtonProps) {
  const baseStyles = [
    'inline-flex items-center justify-center',
    'border-0 bg-transparent cursor-pointer',
    'rounded-lg',
    'transition-colors duration-200',
    'focus:outline-none',
    disabled && 'opacity-50 cursor-not-allowed pointer-events-none',
  ];

  const sizeStyles: Record<ButtonSize, string> = {
    sm: 'p-1',
    md: 'p-1.5',
    lg: 'p-2',
  };

  const variantStyles: Record<IconButtonVariant, string> = {
    ghost: 'text-gray-500 hover:text-gray-700 hover:bg-gray-100',
    danger: 'text-red-500 hover:text-red-700 hover:bg-red-50',
    primary: 'text-primary-500 hover:text-primary-700 hover:bg-primary-50',
  };

  return (
    <button
      type="button"
      disabled={disabled}
      className={clsx(
        baseStyles,
        sizeStyles[size],
        variantStyles[variant],
        className
      )}
      {...props}
    >
      {children}
    </button>
  );
}
