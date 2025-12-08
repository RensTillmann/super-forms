import React from 'react';
import { Drawer } from 'vaul';
import {
  Menu,
  RotateCcw,
  RefreshCw,
  Grid,
  Layers,
  History,
  Share,
  Download,
  BarChart,
  Monitor,
  Tablet,
  Smartphone,
  Maximize,
} from 'lucide-react';
import { cn } from '../../../lib/utils';

interface MobileMenuProps {
  // History actions
  canUndo: boolean;
  canRedo: boolean;
  onUndo: () => void;
  onRedo: () => void;

  // Canvas controls
  showGrid: boolean;
  onToggleGrid: () => void;

  // Panels
  showElementsPanel: boolean;
  onToggleElementsPanel: () => void;
  onShowVersionHistory: () => void;
  onShowSharePanel: () => void;
  onShowExportPanel: () => void;
  onShowAnalytics: () => void;

  // Device preview
  devicePreview: 'desktop' | 'tablet' | 'mobile';
  onDeviceChange: (device: 'desktop' | 'tablet' | 'mobile') => void;
  showDeviceFrame: boolean;
  onToggleDeviceFrame: () => void;
}

/**
 * Mobile hamburger menu with Vaul drawer.
 * Contains all toolbar actions hidden on mobile.
 */
export function MobileMenu({
  canUndo,
  canRedo,
  onUndo,
  onRedo,
  showGrid,
  onToggleGrid,
  showElementsPanel,
  onToggleElementsPanel,
  onShowVersionHistory,
  onShowSharePanel,
  onShowExportPanel,
  onShowAnalytics,
  devicePreview,
  onDeviceChange,
  showDeviceFrame,
  onToggleDeviceFrame,
}: MobileMenuProps) {
  const [open, setOpen] = React.useState(false);

  const MenuItem = ({
    icon: Icon,
    label,
    onClick,
    disabled,
    active,
  }: {
    icon: React.ElementType;
    label: string;
    onClick: () => void;
    disabled?: boolean;
    active?: boolean;
  }) => (
    <button
      onClick={() => {
        onClick();
        setOpen(false);
      }}
      disabled={disabled}
      className={cn(
        'flex items-center gap-3 w-full px-4 py-3 text-sm transition-colors',
        'min-h-[44px]', // Touch target
        disabled && 'opacity-50 cursor-not-allowed',
        active && 'bg-primary/10 text-primary',
        !disabled && !active && 'hover:bg-muted active:bg-muted'
      )}
    >
      <Icon className="w-5 h-5" />
      <span>{label}</span>
    </button>
  );

  const MenuSection = ({ title, children }: { title: string; children: React.ReactNode }) => (
    <div className="py-2">
      <div className="px-4 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
        {title}
      </div>
      {children}
    </div>
  );

  return (
    <Drawer.Root open={open} onOpenChange={setOpen}>
      <Drawer.Trigger asChild>
        <button
          className="sm:hidden relative z-[101] flex items-center justify-center w-10 h-10 rounded-md hover:bg-muted text-muted-foreground transition-colors"
          aria-label="Open menu"
        >
          <Menu className="w-5 h-5" />
        </button>
      </Drawer.Trigger>
      <Drawer.Portal>
        <Drawer.Overlay className="fixed inset-0 bg-black/40 z-50" />
        <Drawer.Content className="fixed bottom-0 left-0 right-0 z-50 bg-background rounded-t-xl max-h-[85vh] flex flex-col">
          <div className="mx-auto w-12 h-1.5 bg-muted rounded-full mt-4 mb-2 shrink-0" />
          <Drawer.Title className="sr-only">Menu</Drawer.Title>

          <div className="flex-1 overflow-y-auto pb-safe">
            {/* History Section */}
            <MenuSection title="History">
              <MenuItem
                icon={RotateCcw}
                label="Undo"
                onClick={onUndo}
                disabled={!canUndo}
              />
              <MenuItem
                icon={RefreshCw}
                label="Redo"
                onClick={onRedo}
                disabled={!canRedo}
              />
            </MenuSection>

            {/* Canvas Section */}
            <MenuSection title="Canvas">
              <MenuItem
                icon={Grid}
                label="Toggle Grid"
                onClick={onToggleGrid}
                active={showGrid}
              />
              <MenuItem
                icon={Layers}
                label="Elements Panel"
                onClick={onToggleElementsPanel}
                active={showElementsPanel}
              />
            </MenuSection>

            {/* Device Preview Section */}
            <MenuSection title="Device Preview">
              <MenuItem
                icon={Monitor}
                label="Desktop"
                onClick={() => onDeviceChange('desktop')}
                active={devicePreview === 'desktop'}
              />
              <MenuItem
                icon={Tablet}
                label="Tablet"
                onClick={() => onDeviceChange('tablet')}
                active={devicePreview === 'tablet'}
              />
              <MenuItem
                icon={Smartphone}
                label="Mobile"
                onClick={() => onDeviceChange('mobile')}
                active={devicePreview === 'mobile'}
              />
              <MenuItem
                icon={Maximize}
                label="Device Frame"
                onClick={onToggleDeviceFrame}
                active={showDeviceFrame}
              />
            </MenuSection>

            {/* Tools Section */}
            <MenuSection title="Tools">
              <MenuItem
                icon={History}
                label="Version History"
                onClick={onShowVersionHistory}
              />
              <MenuItem
                icon={Share}
                label="Share & Collaborate"
                onClick={onShowSharePanel}
              />
              <MenuItem
                icon={Download}
                label="Export Form"
                onClick={onShowExportPanel}
              />
              <MenuItem
                icon={BarChart}
                label="Analytics"
                onClick={onShowAnalytics}
              />
            </MenuSection>
          </div>
        </Drawer.Content>
      </Drawer.Portal>
    </Drawer.Root>
  );
}

export default MobileMenu;
