import React, { useState, useMemo } from 'react';
import { Monitor, Tablet, Smartphone, RotateCcw } from 'lucide-react';
import { getElementNodes } from '../../../../../schemas/styles/elementNodes';
import { cn } from '../../../../../lib/utils';
import { NodeType, StyleProperties } from '../../../../../schemas/styles';
import { Button } from '../../../../../components/ui/button';
import {
  TypographySection,
  BackgroundSection,
  BorderSection,
} from '../style-sections';

// Human-readable labels for node types (targets)
const TARGET_LABELS: Record<NodeType, string> = {
  label: 'Label',
  description: 'Desc',
  input: 'Input',
  placeholder: 'Placeholder',
  error: 'Error',
  required: 'Required',
  fieldContainer: 'Wrap',
  heading: 'Heading',
  paragraph: 'Text',
  button: 'Button',
  divider: 'Line',
  optionLabel: 'Option',
  cardContainer: 'Card',
};

// State types for styling
type StyleState = 'normal' | 'hover' | 'focus' | 'active' | 'disabled' | 'error';

const STYLE_STATES: { id: StyleState; label: string }[] = [
  { id: 'normal', label: 'Normal' },
  { id: 'hover', label: 'Hover' },
  { id: 'focus', label: 'Focus' },
  { id: 'active', label: 'Active' },
  { id: 'disabled', label: 'Disabled' },
  { id: 'error', label: 'Error' },
];

// Breakpoint types
type Breakpoint = 'all' | 'desktop' | 'tablet' | 'mobile';

const BREAKPOINTS: { id: Breakpoint; label: string; icon: typeof Monitor }[] = [
  { id: 'all', label: 'All', icon: Monitor },
  { id: 'desktop', label: 'Desktop', icon: Monitor },
  { id: 'tablet', label: 'Tablet', icon: Tablet },
  { id: 'mobile', label: 'Mobile', icon: Smartphone },
];

interface StyleTabProps {
  element: {
    id: string;
    type: string;
    styleOverrides?: Record<string, Partial<StyleProperties>>;
  };
  onOverrideChange: (nodeType: string, property: string, value: unknown) => void;
  onResetToGlobal: (nodeType?: string) => void;
}

/**
 * Style tab with Target → State → Device hierarchy.
 * Professional styling interface inspired by Elementor/Webflow.
 */
export const StyleTab: React.FC<StyleTabProps> = ({
  element,
  onOverrideChange,
  onResetToGlobal,
}) => {
  // Get available targets for this element type
  const availableTargets = useMemo(() => getElementNodes(element.type), [element.type]);

  // Selection state
  const [selectedTarget, setSelectedTarget] = useState<NodeType | null>(
    availableTargets[0] || null
  );
  const [selectedState, setSelectedState] = useState<StyleState>('normal');
  const [selectedBreakpoint, setSelectedBreakpoint] = useState<Breakpoint>('all');
  const [showDeviceOptions, setShowDeviceOptions] = useState(false);

  // Calculate override counts
  const targetOverrideCounts = useMemo(() => {
    const counts: Record<string, number> = {};
    for (const target of availableTargets) {
      const nodeOverrides = element.styleOverrides?.[target];
      counts[target] = nodeOverrides ? Object.keys(nodeOverrides).length : 0;
    }
    return counts;
  }, [element.styleOverrides, availableTargets]);

  const totalOverrides = Object.values(targetOverrideCounts).reduce((sum, count) => sum + count, 0);

  // If no targets available, show message
  if (availableTargets.length === 0) {
    return (
      <div className="p-4 text-center text-gray-500 text-sm">
        This element has no customizable style targets.
      </div>
    );
  }

  return (
    <div className="style-tab flex flex-col h-full">
      {/* Target Selector - horizontal scrollable chips */}
      <div className="px-4 pt-2 pb-3 border-b border-gray-100">
        <div className="flex items-center justify-between mb-2">
          <span className="text-[10px] font-medium text-gray-400 uppercase tracking-wider">
            Target
          </span>
          {totalOverrides > 0 && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => onResetToGlobal()}
              className="h-6 px-2 text-[10px] text-gray-500 hover:text-gray-700"
            >
              <RotateCcw className="h-3 w-3 mr-1" />
              Reset all
            </Button>
          )}
        </div>
        <div className="w-full overflow-x-auto scrollbar-hide">
          <div className="flex gap-1.5 pb-1">
            {availableTargets.map((target) => {
              const hasOverrides = targetOverrideCounts[target] > 0;
              const isSelected = selectedTarget === target;

              return (
                <button
                  key={target}
                  onClick={() => setSelectedTarget(target)}
                  className={cn(
                    "inline-flex items-center px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors",
                    "focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1",
                    "min-h-[32px] touch-manipulation flex-shrink-0",
                    isSelected
                      ? "bg-primary text-primary-foreground shadow-sm"
                      : "bg-gray-100 text-gray-700 hover:bg-gray-200",
                    hasOverrides && !isSelected && "ring-1 ring-orange-300"
                  )}
                >
                  {TARGET_LABELS[target] || target}
                  {hasOverrides && (
                    <span className={cn(
                      "ml-1 w-1.5 h-1.5 rounded-full",
                      isSelected ? "bg-white/70" : "bg-orange-400"
                    )} />
                  )}
                </button>
              );
            })}
          </div>
        </div>
      </div>

      {/* State & Device Selectors */}
      {selectedTarget && (
        <div className="px-4 py-2 border-b border-gray-100 bg-gray-50/50">
          {/* State Selector */}
          <div className="mb-2">
            <span className="text-[10px] font-medium text-gray-400 uppercase tracking-wider block mb-1.5">
              State
            </span>
            <div className="flex flex-wrap gap-1">
              {STYLE_STATES.map((state) => {
                // Only show states that make sense for this target
                // E.g., label typically doesn't have hover states in forms
                const isInteractive = ['input', 'button', 'optionLabel'].includes(selectedTarget);
                if (!isInteractive && state.id !== 'normal' && state.id !== 'error') {
                  return null;
                }

                const isSelected = selectedState === state.id;

                return (
                  <button
                    key={state.id}
                    onClick={() => setSelectedState(state.id)}
                    className={cn(
                      "px-2 py-1 rounded text-[11px] font-medium transition-colors",
                      "focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1",
                      "min-h-[28px] touch-manipulation",
                      isSelected
                        ? "bg-gray-800 text-white"
                        : "bg-white text-gray-600 border border-gray-200 hover:border-gray-300"
                    )}
                  >
                    {state.label}
                  </button>
                );
              })}
            </div>
          </div>

          {/* Device Selector - collapsible by default */}
          <div>
            <button
              onClick={() => setShowDeviceOptions(!showDeviceOptions)}
              className="text-[10px] font-medium text-gray-400 uppercase tracking-wider flex items-center gap-1 hover:text-gray-600"
            >
              <span>{showDeviceOptions ? '▼' : '▶'}</span>
              Device
              {selectedBreakpoint !== 'all' && (
                <span className="ml-1 px-1.5 py-0.5 bg-blue-100 text-blue-600 rounded text-[9px] normal-case">
                  {BREAKPOINTS.find(b => b.id === selectedBreakpoint)?.label}
                </span>
              )}
            </button>

            {showDeviceOptions && (
              <div className="flex gap-1 mt-1.5">
                {BREAKPOINTS.map((bp) => {
                  const isSelected = selectedBreakpoint === bp.id;
                  const Icon = bp.icon;

                  return (
                    <button
                      key={bp.id}
                      onClick={() => setSelectedBreakpoint(bp.id)}
                      className={cn(
                        "flex items-center gap-1 px-2 py-1 rounded text-[11px] font-medium transition-colors",
                        "focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1",
                        "min-h-[28px] touch-manipulation",
                        isSelected
                          ? "bg-blue-600 text-white"
                          : "bg-white text-gray-600 border border-gray-200 hover:border-gray-300"
                      )}
                      title={bp.label}
                    >
                      <Icon className="h-3 w-3" />
                      <span className="hidden sm:inline">{bp.label}</span>
                    </button>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Style Properties Editor */}
      <div className="flex-1 overflow-y-auto p-4">
        {selectedTarget ? (
          <div>
            {/* Current context indicator */}
            <div className="mb-3 flex items-center gap-2 text-xs text-gray-500">
              <span className="font-medium text-gray-700">
                {TARGET_LABELS[selectedTarget]}
              </span>
              <span>·</span>
              <span>{STYLE_STATES.find(s => s.id === selectedState)?.label}</span>
              {selectedBreakpoint !== 'all' && (
                <>
                  <span>·</span>
                  <span>{BREAKPOINTS.find(b => b.id === selectedBreakpoint)?.label}</span>
                </>
              )}
            </div>

            {/* Collapsible Style Sections */}
            <div className="space-y-2">
              <TypographySection
                elementId={element.id}
                nodeType={selectedTarget}
                onOverrideChange={(property, value) =>
                  onOverrideChange(selectedTarget, property as string, value)
                }
                onRemoveOverride={(property) =>
                  onOverrideChange(selectedTarget, property as string, undefined)
                }
                defaultExpanded={true}
              />
              <BackgroundSection
                elementId={element.id}
                nodeType={selectedTarget}
                onOverrideChange={(property, value) =>
                  onOverrideChange(selectedTarget, property as string, value)
                }
                onRemoveOverride={(property) =>
                  onOverrideChange(selectedTarget, property as string, undefined)
                }
              />
              <BorderSection
                elementId={element.id}
                nodeType={selectedTarget}
                onOverrideChange={(property, value) =>
                  onOverrideChange(selectedTarget, property as string, value)
                }
                onRemoveOverride={(property) =>
                  onOverrideChange(selectedTarget, property as string, undefined)
                }
              />
            </div>

            {/* Reset button for this target */}
            {targetOverrideCounts[selectedTarget] > 0 && (
              <div className="mt-4 pt-3 border-t border-gray-100">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => onResetToGlobal(selectedTarget)}
                  className="w-full text-xs text-gray-600"
                >
                  <RotateCcw className="h-3 w-3 mr-2" />
                  Reset {TARGET_LABELS[selectedTarget]} to global styles
                </Button>
              </div>
            )}
          </div>
        ) : (
          <p className="text-xs text-gray-500 italic text-center py-8">
            Select a target above to customize its styles.
          </p>
        )}
      </div>
    </div>
  );
};

export default StyleTab;
