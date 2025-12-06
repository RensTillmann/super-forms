import {
  Copy,
  ClipboardPaste,
  Paintbrush,
  Link,
  RotateCcw,
  ChevronRight,
} from 'lucide-react';
import { cn } from '../../../lib/utils';
import { useStyleClipboard, useCanPasteStyle } from '../hooks/useStyleClipboard';
import { styleRegistry, getElementNodes, StyleProperties } from '../../../schemas/styles';
import { useElementsStore } from '../store/useElementsStore';
import { useState } from 'react';

interface StyleMenuItemsProps {
  elementId: string;
  elementType: string;
  onAction?: () => void;
}

/**
 * Style-related context menu items.
 * Renders as a submenu with copy/paste/reset/set-as-global options.
 */
export function StyleMenuItems({
  elementId,
  elementType,
  onAction,
}: StyleMenuItemsProps) {
  const [subMenuOpen, setSubMenuOpen] = useState(false);
  const { copy, paste } = useStyleClipboard();
  const canPaste = useCanPasteStyle(elementType);
  const element = useElementsStore((state) => state.items[elementId]);
  const { clearAllStyleOverrides } = useElementsStore();

  const hasOverrides =
    element?.styleOverrides &&
    Object.keys(element.styleOverrides).length > 0;

  const handleCopyStyle = () => {
    copy(elementId);
    onAction?.();
  };

  const handlePasteStyle = () => {
    if (canPaste) {
      paste(elementId);
    }
    onAction?.();
  };

  const handleResetToGlobal = () => {
    clearAllStyleOverrides(elementId);
    onAction?.();
  };

  const handleSetAsGlobal = () => {
    if (!element?.styleOverrides) return;

    // Promote all overrides to global
    const nodes = getElementNodes(elementType);
    nodes.forEach((nodeType) => {
      const overrides = element.styleOverrides?.[nodeType];
      if (overrides) {
        Object.entries(overrides).forEach(([prop, value]) => {
          styleRegistry.setGlobalProperty(
            nodeType,
            prop as keyof StyleProperties,
            value
          );
        });
      }
    });

    // Clear local overrides
    clearAllStyleOverrides(elementId);
    onAction?.();
  };

  const menuItemClass = cn(
    'flex items-center gap-2 px-3 py-2 text-sm cursor-pointer',
    'hover:bg-accent hover:text-accent-foreground rounded-sm',
    'transition-colors'
  );

  const disabledClass = 'opacity-50 pointer-events-none';

  return (
    <div className="relative">
      {/* Separator */}
      <div className="h-px bg-border my-1" />

      {/* Submenu trigger */}
      <button
        className={menuItemClass}
        onMouseEnter={() => setSubMenuOpen(true)}
        onMouseLeave={() => setSubMenuOpen(false)}
        onClick={(e) => e.stopPropagation()}
      >
        <Paintbrush className="h-4 w-4" />
        <span className="flex-1">Styles</span>
        <ChevronRight className="h-4 w-4" />
      </button>

      {/* Submenu */}
      {subMenuOpen && (
        <div
          className="absolute left-full top-0 ml-1 min-w-[180px] bg-popover border rounded-md shadow-md p-1 z-50"
          onMouseEnter={() => setSubMenuOpen(true)}
          onMouseLeave={() => setSubMenuOpen(false)}
        >
          <button className={menuItemClass} onClick={handleCopyStyle}>
            <Copy className="h-4 w-4" />
            <span className="flex-1">Copy Style</span>
            <span className="text-xs text-muted-foreground">Ctrl+Alt+C</span>
          </button>

          <button
            className={cn(menuItemClass, !canPaste && disabledClass)}
            onClick={handlePasteStyle}
            disabled={!canPaste}
          >
            <ClipboardPaste className="h-4 w-4" />
            <span className="flex-1">Paste Style</span>
            <span className="text-xs text-muted-foreground">Ctrl+Alt+V</span>
          </button>

          <div className="h-px bg-border my-1" />

          <button
            className={cn(menuItemClass, !hasOverrides && disabledClass)}
            onClick={handleSetAsGlobal}
            disabled={!hasOverrides}
          >
            <Link className="h-4 w-4" />
            <span>Set as Global</span>
          </button>

          <button
            className={cn(menuItemClass, !hasOverrides && disabledClass)}
            onClick={handleResetToGlobal}
            disabled={!hasOverrides}
          >
            <RotateCcw className="h-4 w-4" />
            <span>Reset to Global</span>
          </button>
        </div>
      )}
    </div>
  );
}
