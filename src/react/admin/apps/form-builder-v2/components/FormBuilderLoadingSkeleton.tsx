import React from 'react';
import { Skeleton } from '../../../components/ui/skeleton';
import { SpinnerButton } from '../../../components/ui/spinner-button';
import { useIsMobile } from '../../../hooks/useMediaQuery';

/**
 * Loading skeleton for Form Builder V2
 * Shows structural placeholders while the app initializes
 */
export const FormBuilderLoadingSkeleton: React.FC = () => {
  const isMobile = useIsMobile();

  return (
    <div className="relative flex flex-col h-screen bg-gray-100" data-testid="form-builder-loading-skeleton">
      {/* TopBar Skeleton */}
      <div className="flex items-center justify-between px-4 py-2 border-b border-border bg-white">
        {/* Left side - Logo + Form name */}
        <div className="flex items-center gap-3">
          <Skeleton className="h-8 w-8 rounded" />
          <Skeleton className="h-6 w-32" />
        </div>

        {/* Right side - Actions */}
        <div className="flex items-center gap-2">
          <Skeleton className="h-9 w-20" />
          <Skeleton className="h-9 w-24" />
        </div>
      </div>

      {/* TabBar Skeleton - matches 8 actual tabs: Builder, Emails, Settings, Entries, Automation, Style, Themes, Integrations */}
      <div
        className="flex items-center gap-1 px-4 py-2 border-b border-border bg-white"
        data-testid="tabbar-skeleton"
      >
        <Skeleton className="h-8 w-20" data-testid="tab-skeleton-1" />
        <Skeleton className="h-8 w-18" data-testid="tab-skeleton-2" />
        <Skeleton className="h-8 w-20" data-testid="tab-skeleton-3" />
        <Skeleton className="h-8 w-18" data-testid="tab-skeleton-4" />
        <Skeleton className="h-8 w-24" data-testid="tab-skeleton-5" />
        <Skeleton className="h-8 w-16" data-testid="tab-skeleton-6" />
        <Skeleton className="h-8 w-20" data-testid="tab-skeleton-7" />
        <Skeleton className="h-8 w-24" data-testid="tab-skeleton-8" />
      </div>

      {/* Main Content Area */}
      <div
        className={isMobile
          ? "flex-1 flex items-start justify-center bg-muted/50 p-4 overflow-auto"
          : "flex-1 flex items-center justify-center bg-gray-50 p-4 md:p-8 overflow-auto"
        }
        data-testid="canvas-skeleton-area"
      >
        <div className="w-full max-w-2xl bg-white rounded-xl p-6 space-y-3" data-testid="canvas-form-wrapper-skeleton">
          <div className="flex gap-3">
            <Skeleton className="h-10 flex-1" data-testid="canvas-skeleton-1" />
            <Skeleton className="h-10 flex-1" data-testid="canvas-skeleton-2" />
          </div>
          <Skeleton className="h-30 w-full" data-testid="canvas-skeleton-3" />
        </div>
      </div>

      {/* Elements Tray Skeleton (bottom) */}
      <div className="border-t border-border bg-white p-4 space-y-3" data-testid="elements-tray-skeleton">
        {/* Category buttons */}
        <div className="flex gap-2">
          <Skeleton className="h-8 w-20" />
          <Skeleton className="h-8 w-24" />
          <Skeleton className="h-8 w-20" />
          <Skeleton className="h-8 w-28" />
          <Skeleton className="h-8 w-20" />
        </div>

        {/* Element squares - single row, 3 columns */}
        <div
          className="flex gap-2"
          data-testid="elements-grid-skeleton"
        >
          {Array.from({ length: 3 }).map((_, i) => (
            <Skeleton
              key={i}
              className="h-16 flex-1"
              data-testid={`element-skeleton-${i + 1}`}
            />
          ))}
        </div>
      </div>

      {/* Spinner Overlay - highest z-index, centered on top */}
      <div
        className="fixed inset-0 flex items-center justify-center z-10 pointer-events-none"
        data-testid="skeleton-spinner-overlay"
      >
        <SpinnerButton>Loading Form Builder...</SpinnerButton>
      </div>
    </div>
  );
};
