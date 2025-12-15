/**
 * ButtonAutomationPanel Component
 *
 * Displays linked automations for a button element that uses 'trigger_automation' action type.
 * Shows warning if no automations are configured and provides a "Create Automation" button.
 */

import React, { useState, useEffect, useCallback } from 'react';
import { AlertTriangle, Zap, ExternalLink, RefreshCw, Plus } from 'lucide-react';
import { Button } from '../../../../../components/ui/button';
import { Badge } from '../../../../../components/ui/badge';
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
  workflow_type: 'visual' | 'code';
}

interface ButtonAutomationPanelProps {
  element: {
    id: string;
    properties?: {
      name?: string;
      eventId?: string;
      actionType?: string;
    };
  };
  formId: number;
  onCreateAutomation?: (buttonId: string, eventId: string) => void;
}

export const ButtonAutomationPanel: React.FC<ButtonAutomationPanelProps> = ({
  element,
  formId,
  onCreateAutomation,
}) => {
  const [automations, setAutomations] = useState<Automation[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const { properties = {} } = element;
  const { actionType, eventId } = properties;

  // Only show panel for trigger_automation action type
  if (actionType !== 'trigger_automation') {
    return null;
  }

  // Build the event pattern to search for
  const getEventPattern = useCallback(() => {
    if (!eventId) return null;
    return `button.${eventId}.clicked`;
  }, [eventId]);

  // Fetch linked automations
  const fetchAutomations = useCallback(async () => {
    const eventPattern = getEventPattern();
    if (!eventPattern) {
      setAutomations([]);
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const result = await wp.apiFetch<Automation[]>({
        path: `/super-forms/v1/automations?trigger_event=${encodeURIComponent(eventPattern)}&form_id=${formId}`,
        method: 'GET',
      });

      setAutomations(result || []);
    } catch (err) {
      console.error('[ButtonAutomationPanel] Error fetching automations:', err);
      setError('Failed to load automations');
      setAutomations([]);
    } finally {
      setLoading(false);
    }
  }, [getEventPattern, formId]);

  // Fetch automations on mount and when eventId changes
  useEffect(() => {
    fetchAutomations();
  }, [fetchAutomations]);

  // Handle create automation click
  const handleCreateAutomation = useCallback(() => {
    if (eventId && onCreateAutomation) {
      onCreateAutomation(element.id, eventId);
    }
  }, [element.id, eventId, onCreateAutomation]);

  // Handle open automation in builder
  const handleOpenAutomation = useCallback((automationId: number) => {
    // Navigate to automation builder with this automation selected
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('automation_id', automationId.toString());
    currentUrl.searchParams.set('tab', 'automations');
    window.location.href = currentUrl.toString();
  }, []);

  const eventPattern = getEventPattern();

  return (
    <div
      className="space-y-3 rounded-lg border border-border bg-muted/30 p-3"
      data-testid="button-automation-panel"
    >
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <Zap className="h-4 w-4 text-amber-500" />
          <span className="text-sm font-medium">Linked Automations</span>
        </div>
        <Button
          variant="ghost"
          size="sm"
          onClick={fetchAutomations}
          disabled={loading}
          className="h-7 w-7 p-0"
          data-testid="refresh-automations-btn"
        >
          <RefreshCw className={cn('h-3 w-3', loading && 'animate-spin')} />
        </Button>
      </div>

      {/* Event ID display */}
      {eventId ? (
        <div className="text-xs text-muted-foreground">
          Event: <code className="rounded bg-muted px-1 py-0.5">{eventPattern}</code>
        </div>
      ) : (
        <div className="flex items-center gap-2 rounded-md bg-amber-500/10 p-2 text-xs text-amber-600">
          <AlertTriangle className="h-3 w-3 shrink-0" />
          <span>Set an Event ID to connect automations</span>
        </div>
      )}

      {/* Error state */}
      {error && (
        <div className="text-xs text-destructive">{error}</div>
      )}

      {/* Loading state */}
      {loading && (
        <div className="flex items-center justify-center py-2">
          <RefreshCw className="h-4 w-4 animate-spin text-muted-foreground" />
        </div>
      )}

      {/* Automations list */}
      {!loading && eventId && automations.length > 0 && (
        <div className="space-y-1.5">
          {automations.map((automation) => (
            <div
              key={automation.id}
              className="flex items-center justify-between rounded-md bg-background p-2 text-sm"
              data-testid={`automation-item-${automation.id}`}
            >
              <div className="flex items-center gap-2 truncate">
                <span className="truncate">{automation.name}</span>
                {!automation.enabled && (
                  <Badge variant="outline" className="text-[10px] px-1 py-0">
                    Disabled
                  </Badge>
                )}
              </div>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => handleOpenAutomation(automation.id)}
                className="h-6 w-6 p-0 shrink-0"
                title="Open in automation builder"
                data-testid={`open-automation-${automation.id}`}
              >
                <ExternalLink className="h-3 w-3" />
              </Button>
            </div>
          ))}
        </div>
      )}

      {/* No automations warning */}
      {!loading && eventId && automations.length === 0 && (
        <div
          className="flex items-start gap-2 rounded-md bg-amber-500/10 p-2 text-xs text-amber-600"
          data-testid="no-automations-warning"
        >
          <AlertTriangle className="h-3 w-3 shrink-0 mt-0.5" />
          <div>
            <p className="font-medium">No automations linked</p>
            <p className="text-amber-600/80">
              This button will trigger the event but no actions are configured to respond.
            </p>
          </div>
        </div>
      )}

      {/* Create automation button */}
      {eventId && onCreateAutomation && (
        <Button
          variant="outline"
          size="sm"
          onClick={handleCreateAutomation}
          className="w-full"
          data-testid="create-automation-btn"
        >
          <Plus className="mr-2 h-3 w-3" />
          Create Automation
        </Button>
      )}
    </div>
  );
};

export default ButtonAutomationPanel;
