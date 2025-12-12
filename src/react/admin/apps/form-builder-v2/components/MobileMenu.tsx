import React from 'react';
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
import { Button } from '../../../components/ui/button';
import { MobileDrawer } from '../../../components/ui/mobile-drawer';

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
 * Mobile hamburger menu with custom drawer.
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
    testId,
  }: {
    icon: React.ElementType;
    label: string;
    onClick: () => void;
    disabled?: boolean;
    active?: boolean;
    testId?: string;
  }) => (
    <Button
      variant="ghost"
      onClick={() => {
        onClick();
        setOpen(false);
      }}
      disabled={disabled}
      className={cn(
        'flex items-center justify-start gap-3 w-full h-auto px-4 py-3 text-sm',
        'min-h-[44px] rounded-none', // Touch target, no rounded for list items
        active && 'bg-primary/10 text-primary'
      )}
      data-testid={testId}
    >
      <Icon className="w-5 h-5" />
      <span>{label}</span>
    </Button>
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
    <>
      {/* Trigger button */}
      <Button
        variant="ghost"
        size="icon"
        className="sm:hidden relative z-[101]"
        aria-label="Open menu"
        onClick={() => setOpen(true)}
        data-testid="mobile-menu-trigger"
      >
        <Menu className="w-5 h-5" />
      </Button>

      {/* Drawer */}
      <MobileDrawer
        open={open}
        onClose={() => setOpen(false)}
        title="Menu"
        description="Form builder mobile navigation menu"
        data-testid="mobile-menu-drawer"
      >
        <div className="flex-1 overflow-y-auto pb-safe" data-testid="mobile-menu-content">
          {/* History Section */}
          <MenuSection title="History">
            <MenuItem
              icon={RotateCcw}
              label="Undo"
              onClick={onUndo}
              disabled={!canUndo}
              testId="mobile-menu-undo"
            />
            <MenuItem
              icon={RefreshCw}
              label="Redo"
              onClick={onRedo}
              disabled={!canRedo}
              testId="mobile-menu-redo"
            />
          </MenuSection>

          {/* Canvas Section */}
          <MenuSection title="Canvas">
            <MenuItem
              icon={Grid}
              label="Toggle Grid"
              onClick={onToggleGrid}
              active={showGrid}
              testId="mobile-menu-toggle-grid"
            />
            <MenuItem
              icon={Layers}
              label="Elements Panel"
              onClick={onToggleElementsPanel}
              active={showElementsPanel}
              testId="mobile-menu-elements-panel"
            />
          </MenuSection>

          {/* Device Preview Section */}
          <MenuSection title="Device Preview">
            <MenuItem
              icon={Monitor}
              label="Desktop"
              onClick={() => onDeviceChange('desktop')}
              active={devicePreview === 'desktop'}
              testId="mobile-menu-device-desktop"
            />
            <MenuItem
              icon={Tablet}
              label="Tablet"
              onClick={() => onDeviceChange('tablet')}
              active={devicePreview === 'tablet'}
              testId="mobile-menu-device-tablet"
            />
            <MenuItem
              icon={Smartphone}
              label="Mobile"
              onClick={() => onDeviceChange('mobile')}
              active={devicePreview === 'mobile'}
              testId="mobile-menu-device-mobile"
            />
            <MenuItem
              icon={Maximize}
              label="Device Frame"
              onClick={onToggleDeviceFrame}
              active={showDeviceFrame}
              testId="mobile-menu-device-frame"
            />
          </MenuSection>

          {/* Tools Section */}
          <MenuSection title="Tools">
            <MenuItem
              icon={History}
              label="Version History"
              onClick={onShowVersionHistory}
              testId="mobile-menu-version-history"
            />
            <MenuItem
              icon={Share}
              label="Share & Collaborate"
              onClick={onShowSharePanel}
              testId="mobile-menu-share"
            />
            <MenuItem
              icon={Download}
              label="Export Form"
              onClick={onShowExportPanel}
              testId="mobile-menu-export"
            />
            <MenuItem
              icon={BarChart}
              label="Analytics"
              onClick={onShowAnalytics}
              testId="mobile-menu-analytics"
            />
          </MenuSection>
        </div>
      </MobileDrawer>
    </>
  );
}

export default MobileMenu;
