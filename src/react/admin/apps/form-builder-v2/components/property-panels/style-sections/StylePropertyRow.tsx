import React from 'react';
import { Link2, Unlink2 } from 'lucide-react';
import { cn } from '../../../../../lib/utils';
import { Button } from '../../../../../components/ui/button';
import { NodeType, StyleProperties } from '../../../../../schemas/styles';
import { usePropertyValues } from '../../../hooks/useResolvedStyle';

interface StylePropertyRowProps {
  label: string;
  property: keyof StyleProperties;
  elementId: string;
  nodeType: NodeType;
  onOverride: (property: keyof StyleProperties, value: unknown) => void;
  onUnlink: (property: keyof StyleProperties) => void;
  children: (value: unknown, onChange: (value: unknown) => void) => React.ReactNode;
}

/**
 * Individual style property row with link/unlink functionality.
 * Shows current value (from global or override) and allows toggling override state.
 */
export const StylePropertyRow: React.FC<StylePropertyRowProps> = ({
  label,
  property,
  elementId,
  nodeType,
  onOverride,
  onUnlink,
  children,
}) => {
  const { globalValue, resolvedValue, isOverridden } = usePropertyValues(
    elementId,
    nodeType,
    property
  );

  const handleChange = (value: unknown) => {
    onOverride(property, value);
  };

  const handleToggleLink = () => {
    if (isOverridden) {
      onUnlink(property);
    } else {
      // Unlink: copy global value to override
      onOverride(property, globalValue);
    }
  };

  return (
    <div className="flex items-center gap-2">
      <label className="text-xs text-gray-600 w-16 flex-shrink-0">
        {label}
      </label>

      <div className="flex-1 min-w-0">
        {children(resolvedValue, handleChange)}
      </div>

      <Button
        variant="ghost"
        size="icon"
        onClick={handleToggleLink}
        className={cn(
          "h-7 w-7 flex-shrink-0",
          isOverridden
            ? "text-orange-500 hover:bg-orange-50"
            : "text-gray-400 hover:bg-gray-100"
        )}
        title={isOverridden ? "Link to global (remove override)" : "Unlink from global (create override)"}
      >
        {isOverridden ? (
          <Unlink2 className="h-3.5 w-3.5" />
        ) : (
          <Link2 className="h-3.5 w-3.5" />
        )}
      </Button>
    </div>
  );
};

export default StylePropertyRow;
