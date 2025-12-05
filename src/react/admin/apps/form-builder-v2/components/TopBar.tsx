import React, { useState, useRef, useEffect } from 'react';
import {
  Monitor,
  Tablet,
  Smartphone,
  Maximize,
  RotateCcw,
  RefreshCw,
  Grid,
  Layers,
  History,
  Share,
  Download,
  BarChart,
  Eye,
  Save,
  Send,
  ChevronDown,
  LucideIcon,
} from 'lucide-react';
import {
  getToolbarItemsByGroup,
  ToolbarItemSchema,
  ToolbarGroup,
  ToolbarVariant,
} from '../../../schemas/toolbar';
import { cn } from '../../../lib/utils';
import { FormSelector, ZoomControls, InlineEditableText } from './ui';

/**
 * Icon mapping from string names to Lucide components.
 */
const iconMap: Record<string, LucideIcon> = {
  Monitor,
  Tablet,
  Smartphone,
  Maximize,
  RotateCcw,
  RefreshCw,
  Grid,
  Layers,
  History,
  Share,
  Download,
  BarChart,
  Eye,
  Save,
  Send,
};

/**
 * Get the Lucide icon component by name.
 */
function getIcon(iconName?: string): LucideIcon | null {
  if (!iconName) return null;
  return iconMap[iconName] || null;
}

/**
 * Get Tailwind classes for button variants.
 */
function getVariantClasses(variant: ToolbarVariant, isActive?: boolean): string {
  const base = 'flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-md transition-colors';

  switch (variant) {
    case 'ghost':
      return cn(
        base,
        isActive
          ? 'bg-muted text-foreground'
          : 'text-muted-foreground hover:text-foreground hover:bg-muted'
      );
    case 'secondary':
      return cn(base, 'bg-secondary text-secondary-foreground hover:bg-secondary/80');
    case 'save':
      return cn(base, 'bg-primary text-primary-foreground hover:bg-primary/90');
    case 'publish':
      return cn(base, 'bg-green-600 text-white hover:bg-green-700');
    default:
      return cn(base, 'text-muted-foreground hover:text-foreground hover:bg-muted');
  }
}

// =============================================================================
// Props
// =============================================================================

interface TopBarProps {
  // Form info
  currentFormId: string | null;
  onFormSelect: (formId: string | null) => void;
  formTitle: string;
  onFormTitleChange: (title: string) => void;

  // Device preview
  devicePreview: 'desktop' | 'tablet' | 'mobile';
  onDeviceChange: (device: 'desktop' | 'tablet' | 'mobile') => void;
  showDeviceFrame: boolean;
  onToggleDeviceFrame: () => void;

  // History
  canUndo: boolean;
  canRedo: boolean;
  onUndo: () => void;
  onRedo: () => void;

  // Canvas controls
  showGrid: boolean;
  onToggleGrid: () => void;
  zoom: number;
  onZoomChange: (zoom: number) => void;

  // Panels
  showElementsPanel: boolean;
  onToggleElementsPanel: () => void;
  onShowVersionHistory: () => void;
  onShowSharePanel: () => void;
  onShowExportPanel: () => void;
  onShowAnalytics: () => void;

  // Actions
  onPreview: () => void;
  onSave: () => void;
  onPublish: () => void;
  isSaving?: boolean;

  // Responsive
  isMobile?: boolean;
}

// =============================================================================
// TopBar Component
// =============================================================================

export function TopBar(props: TopBarProps) {
  const {
    currentFormId,
    onFormSelect,
    formTitle,
    onFormTitleChange,
    devicePreview,
    onDeviceChange,
    showDeviceFrame,
    onToggleDeviceFrame,
    canUndo,
    canRedo,
    onUndo,
    onRedo,
    showGrid,
    onToggleGrid,
    zoom,
    onZoomChange,
    showElementsPanel,
    onToggleElementsPanel,
    onShowVersionHistory,
    onShowSharePanel,
    onShowExportPanel,
    onShowAnalytics,
    onPreview,
    onSave,
    onPublish,
    isSaving = false,
    isMobile = false,
  } = props;

  return (
    <div className="flex items-center justify-between px-4 py-2 bg-background border-b border-border">
      {/* Left Section */}
      <div className="flex items-center gap-4">
        <FormSelector
          currentForm={currentFormId}
          onFormSelect={onFormSelect}
        />

        <InlineEditableText
          value={formTitle}
          onChange={onFormTitleChange}
          className="text-sm font-medium"
          placeholder="Untitled Form"
        />

        <DeviceSelector
          value={devicePreview}
          onChange={onDeviceChange}
          showFrame={showDeviceFrame}
          onToggleFrame={onToggleDeviceFrame}
        />
      </div>

      {/* Right Section - Action Groups */}
      <div className="flex items-center gap-2">
        {/* History Group */}
        <div className="flex items-center gap-1 pr-2 border-r border-border">
          <ToolbarButton
            icon="RotateCcw"
            tooltip="Undo (Ctrl+Z)"
            onClick={onUndo}
            disabled={!canUndo}
          />
          <ToolbarButton
            icon="RefreshCw"
            tooltip="Redo (Ctrl+Shift+Z)"
            onClick={onRedo}
            disabled={!canRedo}
          />
        </div>

        {/* Canvas Group */}
        <div className="flex items-center gap-1 pr-2 border-r border-border">
          <ToolbarToggle
            icon="Grid"
            tooltip="Toggle Grid"
            isActive={showGrid}
            onClick={onToggleGrid}
          />
          <ZoomControls currentZoom={zoom} onZoomChange={onZoomChange} />
        </div>

        {/* Panels Group */}
        <div className="flex items-center gap-1 pr-2 border-r border-border">
          <ToolbarToggle
            icon="Layers"
            tooltip="Elements Panel"
            isActive={showElementsPanel}
            onClick={onToggleElementsPanel}
          />
          <ToolbarButton
            icon="History"
            tooltip="Version History"
            onClick={onShowVersionHistory}
          />
          <ToolbarButton
            icon="Share"
            tooltip="Share & Collaborate"
            onClick={onShowSharePanel}
          />
          <ToolbarButton
            icon="Download"
            tooltip="Export Form"
            onClick={onShowExportPanel}
          />
          <ToolbarButton
            icon="BarChart"
            tooltip="Analytics"
            onClick={onShowAnalytics}
          />
        </div>

        {/* Primary Group */}
        <div className="flex items-center gap-2">
          <button
            className={getVariantClasses('secondary')}
            onClick={onPreview}
            title="Preview Form"
          >
            <Eye className="w-4 h-4" />
            {!isMobile && <span>Preview</span>}
          </button>
          <button
            className={getVariantClasses('save')}
            onClick={onSave}
            title="Save Form"
          >
            {isSaving ? (
              <RefreshCw className="w-4 h-4 animate-spin" />
            ) : (
              <Save className="w-4 h-4" />
            )}
            {!isMobile && <span>Save</span>}
          </button>
          <button
            className={getVariantClasses('publish')}
            onClick={onPublish}
            title="Publish Form"
          >
            <Send className="w-4 h-4" />
            {!isMobile && <span>Publish</span>}
          </button>
        </div>
      </div>
    </div>
  );
}

// =============================================================================
// Sub-components
// =============================================================================

interface ToolbarButtonProps {
  icon: string;
  tooltip: string;
  onClick: () => void;
  disabled?: boolean;
}

function ToolbarButton({ icon, tooltip, onClick, disabled }: ToolbarButtonProps) {
  const Icon = getIcon(icon);

  return (
    <button
      className={cn(
        'flex items-center justify-center w-8 h-8 rounded-md transition-colors',
        disabled
          ? 'text-muted-foreground/50 cursor-not-allowed'
          : 'text-muted-foreground hover:text-foreground hover:bg-muted'
      )}
      onClick={onClick}
      disabled={disabled}
      title={tooltip}
    >
      {Icon && <Icon className="w-4 h-4" />}
    </button>
  );
}

interface ToolbarToggleProps {
  icon: string;
  tooltip: string;
  isActive: boolean;
  onClick: () => void;
}

function ToolbarToggle({ icon, tooltip, isActive, onClick }: ToolbarToggleProps) {
  const Icon = getIcon(icon);

  return (
    <button
      className={cn(
        'flex items-center justify-center w-8 h-8 rounded-md transition-colors',
        isActive
          ? 'bg-muted text-foreground'
          : 'text-muted-foreground hover:text-foreground hover:bg-muted'
      )}
      onClick={onClick}
      title={tooltip}
      aria-pressed={isActive}
    >
      {Icon && <Icon className="w-4 h-4" />}
    </button>
  );
}

interface DeviceSelectorProps {
  value: 'desktop' | 'tablet' | 'mobile';
  onChange: (device: 'desktop' | 'tablet' | 'mobile') => void;
  showFrame: boolean;
  onToggleFrame: () => void;
}

function DeviceSelector({ value, onChange, showFrame, onToggleFrame }: DeviceSelectorProps) {
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  // Close on click outside
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const devices = [
    { value: 'desktop', label: 'Desktop', icon: Monitor },
    { value: 'tablet', label: 'Tablet', icon: Tablet },
    { value: 'mobile', label: 'Mobile', icon: Smartphone },
  ] as const;

  const currentDevice = devices.find(d => d.value === value) || devices[0];
  const CurrentIcon = currentDevice.icon;

  return (
    <div className="flex items-center gap-1">
      <div className="relative" ref={dropdownRef}>
        <button
          className={cn(
            'flex items-center gap-2 px-3 py-2 text-sm rounded-md transition-colors',
            'text-muted-foreground hover:text-foreground hover:bg-muted'
          )}
          onClick={() => setIsOpen(!isOpen)}
          aria-haspopup="listbox"
          aria-expanded={isOpen}
        >
          <CurrentIcon className="w-4 h-4" />
          <span>{currentDevice.label}</span>
          <ChevronDown className={cn('w-3 h-3 transition-transform', isOpen && 'rotate-180')} />
        </button>

        {isOpen && (
          <div
            className="absolute top-full left-0 mt-1 py-1 bg-popover border border-border rounded-md shadow-md z-50 min-w-[140px]"
            role="listbox"
          >
            {devices.map((device) => {
              const DeviceIcon = device.icon;
              return (
                <button
                  key={device.value}
                  className={cn(
                    'flex items-center gap-2 w-full px-3 py-2 text-sm transition-colors',
                    value === device.value
                      ? 'bg-muted text-foreground'
                      : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                  )}
                  onClick={() => {
                    onChange(device.value);
                    setIsOpen(false);
                  }}
                  role="option"
                  aria-selected={value === device.value}
                >
                  <DeviceIcon className="w-4 h-4" />
                  <span>{device.label}</span>
                </button>
              );
            })}
          </div>
        )}
      </div>

      <button
        className={cn(
          'flex items-center justify-center w-8 h-8 rounded-md transition-colors',
          showFrame
            ? 'bg-muted text-foreground'
            : 'text-muted-foreground hover:text-foreground hover:bg-muted'
        )}
        onClick={onToggleFrame}
        title="Toggle Device Frame"
        aria-pressed={showFrame}
      >
        <Maximize className="w-4 h-4" />
      </button>
    </div>
  );
}

export default TopBar;
