import React, { useState } from 'react';
import { ChevronDown, ChevronRight, RotateCcw } from 'lucide-react';
import { cn } from '../../../../../lib/utils';
import { Button } from '../../../../../components/ui/button';

interface CollapsibleSectionProps {
  title: string;
  icon?: React.ReactNode;
  defaultExpanded?: boolean;
  hasOverrides?: boolean;
  overrideCount?: number;
  summary?: string;
  onReset?: () => void;
  children: React.ReactNode;
}

/**
 * Collapsible section for style property groups.
 * Shows a summary when collapsed, full controls when expanded.
 */
export const CollapsibleSection: React.FC<CollapsibleSectionProps> = ({
  title,
  icon,
  defaultExpanded = false,
  hasOverrides = false,
  overrideCount = 0,
  summary,
  onReset,
  children,
}) => {
  const [isExpanded, setIsExpanded] = useState(defaultExpanded);

  return (
    <div className={cn(
      "border border-gray-200 rounded-lg overflow-hidden",
      hasOverrides && "ring-1 ring-orange-200"
    )}>
      {/* Section Header */}
      <button
        type="button"
        onClick={() => setIsExpanded(!isExpanded)}
        className={cn(
          "w-full flex items-center justify-between px-3 py-2.5 text-left",
          "bg-gray-50 hover:bg-gray-100 transition-colors",
          "focus:outline-none focus:ring-2 focus:ring-primary focus:ring-inset"
        )}
      >
        <div className="flex items-center gap-2 min-w-0">
          {isExpanded ? (
            <ChevronDown className="h-4 w-4 text-gray-400 flex-shrink-0" />
          ) : (
            <ChevronRight className="h-4 w-4 text-gray-400 flex-shrink-0" />
          )}
          {icon && <span className="text-gray-500 flex-shrink-0">{icon}</span>}
          <span className="text-xs font-medium text-gray-700 uppercase tracking-wide">
            {title}
          </span>
          {hasOverrides && overrideCount > 0 && (
            <span className="px-1.5 py-0.5 text-[10px] font-medium text-orange-600 bg-orange-50 rounded">
              {overrideCount}
            </span>
          )}
        </div>

        <div className="flex items-center gap-2">
          {/* Summary when collapsed */}
          {!isExpanded && summary && (
            <span className="text-[11px] text-gray-500 truncate max-w-[120px]">
              {summary}
            </span>
          )}
          {/* Reset button when has overrides */}
          {hasOverrides && onReset && (
            <Button
              variant="ghost"
              size="sm"
              onClick={(e) => {
                e.stopPropagation();
                onReset();
              }}
              className="h-6 w-6 p-0 text-gray-400 hover:text-gray-600"
              title="Reset to global"
            >
              <RotateCcw className="h-3 w-3" />
            </Button>
          )}
        </div>
      </button>

      {/* Section Content */}
      {isExpanded && (
        <div className="px-3 py-3 bg-white space-y-3">
          {children}
        </div>
      )}
    </div>
  );
};

export default CollapsibleSection;
