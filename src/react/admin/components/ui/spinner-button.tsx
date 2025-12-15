import React from 'react';
import { Button } from './button';
import { Spinner } from './spinner';
import { cn } from '@/lib/utils';

interface SpinnerButtonProps {
  children?: React.ReactNode;
  variant?: 'outline' | 'default' | 'ghost' | 'secondary' | 'destructive' | 'link';
  size?: 'default' | 'sm' | 'lg' | 'icon';
  className?: string;
}

/**
 * Button component with a spinner, styled using shadcn/ui patterns.
 * Uses Button's variant system for styling - default variant gives primary background.
 */
export const SpinnerButton: React.FC<SpinnerButtonProps> = ({
  children = 'Loading...',
  variant = 'default',
  size = 'sm',
  className,
}) => {
  return (
    <Button
      variant={variant}
      size={size}
      disabled
      className={cn('disabled:opacity-100', className)}
      data-testid="spinner-button"
    >
      <Spinner />
      {children}
    </Button>
  );
};
