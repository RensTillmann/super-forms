/**
 * ButtonAutomationIndicator Component
 *
 * Small badge overlay on button elements showing the number of linked automations.
 * Shows tooltip with automation names on hover.
 */

import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { Zap } from 'lucide-react';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '../../../../../components/ui/tooltip';
import { cn } from '../../../../../lib/utils';

// WordPress REST API global
declare const wp: {
  apiFetch: <T>(options: {
    path: string;
    method?: string;
    data?: unknown;
  }) => Promise<T>;
};

interface Automation {
  id: number;
  name: string;
  enabled: number;
}

interface ButtonAutomationIndicatorProps {
  elementId: string;
  eventId?: string;
  formId: number;
  className?: string;
}

export const ButtonAutomationIndicator: React.FC<ButtonAutomationIndicatorProps> = ({
  elementId,
  eventId,
  formId,
  className,
}) => {
  const [automations, setAutomations] = useState<Automation[]>([]);
  const [loading, setLoading] = useState(false);

  // Build the event pattern
  const eventPattern = useMemo(() => {
    if (!eventId) return null;
    return `button.${eventId}.clicked`;
  }, [eventId]);

  // Fetch linked automations
  const fetchAutomations = useCallback(async () => {
    if (!eventPattern) {
      setAutomations([]);
      return;
    }

    setLoading(true);

    try {
      const result = await wp.apiFetch<Automation[]>({
        path: `/super-forms/v1/automations?trigger_event=${encodeURIComponent(eventPattern)}&form_id=${formId}`,
        method: 'GET',
      });

      setAutomations(result || []);
    } catch (err) {
      console.error('[ButtonAutomationIndicator] Error:', err);
      setAutomations([]);
    } finally {
      setLoading(false);
    }
  }, [eventPattern, formId]);

  // Fetch on mount and when eventId changes
  useEffect(() => {
    fetchAutomations();
  }, [fetchAutomations]);

  // Don't render if no eventId or loading
  if (!eventId || loading) {
    return null;
  }

  const count = automations.length;
  const enabledCount = automations.filter(a => a.enabled).length;

  // Don't show if no automations
  if (count === 0) {
    return null;
  }

  const tooltipContent = (
    <div className="space-y-1 text-xs">
      <div className="font-medium">
        {count} automation{count !== 1 ? 's' : ''} linked
      </div>
      <div className="space-y-0.5">
        {automations.map((automation) => (
          <div
            key={automation.id}
            className={cn(
              'flex items-center gap-1',
              !automation.enabled && 'text-muted-foreground line-through'
            )}
          >
            <Zap className="h-2.5 w-2.5" />
            <span className="truncate max-w-[150px]">{automation.name}</span>
          </div>
        ))}
      </div>
      {enabledCount < count && (
        <div className="text-muted-foreground text-[10px] pt-1 border-t border-border">
          {count - enabledCount} disabled
        </div>
      )}
    </div>
  );

  return (
    <TooltipProvider>
      <Tooltip>
        <TooltipTrigger asChild>
          <div
            className={cn(
              'absolute -top-1.5 -right-1.5 z-10',
              'flex items-center justify-center',
              'h-5 min-w-5 px-1 rounded-full',
              'bg-amber-500 text-white',
              'text-[10px] font-bold',
              'shadow-sm cursor-pointer',
              'transition-transform hover:scale-110',
              className
            )}
            data-testid={`automation-indicator-${elementId}`}
          >
            <Zap className="h-2.5 w-2.5 mr-0.5" />
            {count}
          </div>
        </TooltipTrigger>
        <TooltipContent side="top" className="max-w-[200px]">
          {tooltipContent}
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  );
};

export default ButtonAutomationIndicator;
