import React from 'react';
import {
  Layout,
  Mail,
  Settings,
  Database,
  Zap,
  PaintBucket,
  Palette,
  Webhook,
  Workflow,
  LucideIcon,
} from 'lucide-react';
import { getTabsSorted, TabSchema } from '../../../schemas/tabs';
import { cn } from '../../../lib/utils';
import { Button } from '../../../components/ui/button';

/**
 * Icon mapping from string names to Lucide components.
 * Add new icons here as tabs are registered.
 */
const iconMap: Record<string, LucideIcon> = {
  Layout,
  Mail,
  Settings,
  Database,
  Zap,
  PaintBucket,
  Palette,
  Webhook,
  Workflow,
};

/**
 * Get the Lucide icon component for a tab.
 */
function getTabIcon(iconName: string): LucideIcon {
  return iconMap[iconName] || Layout;
}

interface TabBarProps {
  /** Currently active tab ID */
  activeTab: string;
  /** Callback when tab is clicked */
  onTabChange: (tabId: string) => void;
  /** Currently active sidebar ID (for sidebar tabs like Style/Themes) */
  activeSidebar?: string | null;
  /** Callback when sidebar tab is toggled */
  onSidebarChange?: (sidebarId: string | null) => void;
  /** Optional CSS class for the container */
  className?: string;
  /** Mobile editing state - hide tabs when editing element on mobile */
  isEditingElement?: boolean;
}

/**
 * Schema-driven tab bar component.
 *
 * Renders tabs from the tab registry using Tailwind CSS.
 * The Canvas tab is special - when active, no side panel is shown.
 * The Builder tab is visible and switches activeTab to 'canvas'.
 * Sidebar tabs (Style, Themes) toggle a right sidebar overlay instead of replacing the canvas.
 */
export function TabBar({ activeTab, onTabChange, activeSidebar, onSidebarChange, className, isEditingElement = false }: TabBarProps) {
  const tabs = getTabsSorted();

  // Hide completely on mobile when editing element
  if (isEditingElement) {
    return null;
  }

  const handleTabClick = (tab: TabSchema) => {
    // Sidebar tabs toggle overlay without changing activeTab
    if (tab.sidebar && onSidebarChange) {
      // Toggle: if already open, close it; otherwise open it
      if (activeSidebar === tab.id) {
        onSidebarChange(null);
      } else {
        onSidebarChange(tab.id);
        // Ensure we're on canvas when opening sidebar
        if (activeTab !== 'canvas') {
          onTabChange('canvas');
        }
      }
      return;
    }

    // Close any open sidebar when switching to a regular tab
    if (onSidebarChange && activeSidebar) {
      onSidebarChange(null);
    }

    // Builder tab switches to canvas view
    if (tab.id === 'builder') {
      onTabChange('canvas');
    } else {
      onTabChange(tab.id);
    }
  };

  return (
    <div
      className={cn(
        'flex items-center gap-1 px-4 py-2 bg-muted/50 border-b border-border',
        'overflow-x-auto scrollbar-hide scroll-smooth',
        className
      )}
      role="tablist"
      aria-label="Form builder tabs"
    >
      {tabs.map((tab) => {
        // Determine if this tab is active
        const isActive = tab.sidebar
          ? activeSidebar === tab.id
          : tab.id === 'builder'
            ? activeTab === 'canvas'
            : activeTab === tab.id;

        return (
          <TabButton
            key={tab.id}
            tab={tab}
            isActive={isActive}
            onClick={() => handleTabClick(tab)}
          />
        );
      })}
    </div>
  );
}

interface TabButtonProps {
  tab: TabSchema;
  isActive: boolean;
  onClick: () => void;
}

/**
 * Individual tab button component.
 * Sidebar tabs get a different visual indicator (left accent bar) when active.
 */
function TabButton({ tab, isActive, onClick }: TabButtonProps) {
  const Icon = getTabIcon(tab.icon);
  const isSidebarTab = tab.sidebar;

  return (
    <Button
      variant="ghost"
      size="sm"
      role="tab"
      aria-selected={isActive}
      aria-controls={`panel-${tab.id}`}
      id={`tab-${tab.id}`}
      className={cn(
        'relative flex items-center gap-2 whitespace-nowrap shrink-0',
        isActive && !isSidebarTab && 'bg-background text-foreground shadow-sm',
        isActive && isSidebarTab && 'bg-primary/10 text-primary',
        !isActive && 'text-muted-foreground'
      )}
      onClick={onClick}
      title={tab.description}
      data-testid={`tab-${tab.id}`}
    >
      {/* Accent bar for active sidebar tabs */}
      {isActive && isSidebarTab && (
        <span className="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-primary rounded-full" />
      )}
      <Icon className={cn('w-4 h-4', isActive && isSidebarTab && 'text-primary')} />
      <span>{tab.label}</span>
    </Button>
  );
}

export default TabBar;
