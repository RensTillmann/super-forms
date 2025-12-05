import React from 'react';
import {
  Layout,
  Mail,
  Settings,
  Database,
  Zap,
  PaintBucket,
  Webhook,
  Workflow,
  LucideIcon,
} from 'lucide-react';
import { getTabsSorted, TabSchema } from '../../../schemas/tabs';
import { cn } from '../../../lib/utils';

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
  /** Optional CSS class for the container */
  className?: string;
}

/**
 * Schema-driven tab bar component.
 *
 * Renders tabs from the tab registry using Tailwind CSS.
 * The Canvas tab is special - when active, no side panel is shown.
 */
export function TabBar({ activeTab, onTabChange, className }: TabBarProps) {
  const tabs = getTabsSorted();

  // Filter out the canvas tab - it's handled specially (always shows main canvas)
  const visibleTabs = tabs.filter(tab => tab.id !== 'canvas');

  return (
    <div
      className={cn(
        'flex items-center gap-1 px-4 py-2 bg-muted/50 border-b border-border',
        className
      )}
      role="tablist"
      aria-label="Form builder tabs"
    >
      {visibleTabs.map((tab) => (
        <TabButton
          key={tab.id}
          tab={tab}
          isActive={activeTab === tab.id}
          onClick={() => onTabChange(tab.id)}
        />
      ))}
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
 */
function TabButton({ tab, isActive, onClick }: TabButtonProps) {
  const Icon = getTabIcon(tab.icon);

  return (
    <button
      role="tab"
      aria-selected={isActive}
      aria-controls={`panel-${tab.id}`}
      id={`tab-${tab.id}`}
      className={cn(
        'flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md transition-colors',
        isActive
          ? 'bg-background text-foreground shadow-sm'
          : 'text-muted-foreground hover:text-foreground hover:bg-muted'
      )}
      onClick={onClick}
      title={tab.description}
    >
      <Icon className="w-4 h-4" />
      <span>{tab.label}</span>
    </button>
  );
}

export default TabBar;
