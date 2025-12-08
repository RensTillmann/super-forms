import React, { useState, useMemo, useCallback, useEffect, useRef } from 'react';
import { Search, X, Loader2 } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

const ICONS_PER_PAGE = 64; // 8 columns x 8 rows

import { Button } from '../button';
import { Input } from '../input';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '../dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../tabs';
import { getFontAwesomeIcons, FontAwesomeIcon } from './fontawesome-icons';
import { lucideIcons, LucideIconInfo, loadLucideIcon } from './lucide-icons';
import { cn } from '@/lib/utils';

export type IconLibrary = 'fontawesome' | 'lucide';

export interface IconValue {
  library: IconLibrary;
  name: string;
  class?: string; // For Font Awesome: "fas fa-user"
}

interface IconPickerProps {
  value?: string; // Format: "fa:fas fa-user" or "lucide:user"
  onChange: (value: string) => void;
  className?: string;
}

/**
 * Parse icon value string to IconValue object
 */
function parseIconValue(value: string | undefined): IconValue | null {
  if (!value) return null;

  if (value.startsWith('fa:')) {
    const faClass = value.slice(3);
    const parts = faClass.split(' ');
    const name = parts[1]?.replace('fa-', '') || '';
    return { library: 'fontawesome', name, class: faClass };
  }

  if (value.startsWith('lucide:')) {
    return { library: 'lucide', name: value.slice(7) };
  }

  // Legacy format: "fas fa-user" (no prefix)
  if (value.includes('fa-')) {
    const parts = value.split(' ');
    const name = parts[1]?.replace('fa-', '') || parts[0]?.replace('fa-', '') || '';
    return { library: 'fontawesome', name, class: value };
  }

  return null;
}

/**
 * Format IconValue to string
 */
function formatIconValue(icon: IconValue): string {
  if (icon.library === 'fontawesome') {
    return `fa:${icon.class}`;
  }
  return `lucide:${icon.name}`;
}

// Cache for loaded Lucide icons
const iconCache = new Map<string, LucideIcon>();

/**
 * Hook for infinite scroll - simple scroll-based loading
 * Uses state as callback ref pattern for proper effect timing
 */
function useInfiniteScroll(
  totalCount: number,
  initialCount: number = ICONS_PER_PAGE
): {
  visibleCount: number;
  setScrollContainer: (node: HTMLDivElement | null) => void;
  hasMore: boolean;
  reset: () => void;
  loadMore: () => void;
} {
  const [visibleCount, setVisibleCount] = useState(initialCount);
  const [container, setContainer] = useState<HTMLDivElement | null>(null);

  // Use refs to avoid stale closures in scroll handler
  const visibleCountRef = useRef(visibleCount);
  const totalCountRef = useRef(totalCount);

  // Keep refs in sync
  useEffect(() => {
    visibleCountRef.current = visibleCount;
  }, [visibleCount]);

  useEffect(() => {
    totalCountRef.current = totalCount;
  }, [totalCount]);

  const hasMore = visibleCount < totalCount;

  const reset = useCallback(() => {
    setVisibleCount(initialCount);
  }, [initialCount]);

  const loadMore = useCallback(() => {
    setVisibleCount((prev) => Math.min(prev + ICONS_PER_PAGE, totalCountRef.current));
  }, []);

  // Handle scroll event - re-runs when container becomes available
  useEffect(() => {
    if (!container) return;

    const handleScroll = () => {
      if (visibleCountRef.current >= totalCountRef.current) return;

      const { scrollTop, scrollHeight, clientHeight } = container;
      // Load more when within 100px of bottom
      if (scrollHeight - scrollTop - clientHeight < 100) {
        setVisibleCount((prev) => Math.min(prev + ICONS_PER_PAGE, totalCountRef.current));
      }
    };

    container.addEventListener('scroll', handleScroll, { passive: true });
    return () => container.removeEventListener('scroll', handleScroll);
  }, [container]); // Re-runs when container becomes available

  // Initial load: if content doesn't fill the container, keep loading
  useEffect(() => {
    if (!container || visibleCount >= totalCount) return;

    // Check if we need to load more to fill the viewport
    const checkAndFill = () => {
      if (container.scrollHeight <= container.clientHeight && visibleCount < totalCount) {
        loadMore();
      }
    };

    // Small delay to let DOM update
    const timeoutId = setTimeout(checkAndFill, 50);
    return () => clearTimeout(timeoutId);
  }, [container, visibleCount, totalCount, loadMore]);

  return {
    visibleCount: Math.min(visibleCount, totalCount),
    setScrollContainer: setContainer,
    hasMore,
    reset,
    loadMore,
  };
}

/**
 * Dynamic Lucide icon component - lazy loads icons on demand
 */
function DynamicLucideIcon({
  name,
  size = 16,
  className,
}: {
  name: string;
  size?: number;
  className?: string;
}) {
  const [Icon, setIcon] = useState<LucideIcon | null>(() => iconCache.get(name) || null);
  const [loading, setLoading] = useState(!iconCache.has(name));

  useEffect(() => {
    // If already cached, use it
    if (iconCache.has(name)) {
      setIcon(iconCache.get(name)!);
      setLoading(false);
      return;
    }

    // Load the icon dynamically
    let cancelled = false;
    setLoading(true);

    loadLucideIcon(name).then((loadedIcon) => {
      if (cancelled) return;
      if (loadedIcon) {
        iconCache.set(name, loadedIcon);
        setIcon(loadedIcon);
      }
      setLoading(false);
    });

    return () => {
      cancelled = true;
    };
  }, [name]);

  if (loading) {
    return (
      <div
        className={cn('animate-pulse bg-muted rounded', className)}
        style={{ width: size, height: size }}
      />
    );
  }

  if (!Icon) {
    return <span className="text-muted-foreground text-xs">?</span>;
  }

  return <Icon size={size} className={className} />;
}

/**
 * Icon preview component
 */
function IconPreview({ value, size = 20 }: { value: IconValue | null; size?: number }) {
  if (!value) {
    return (
      <div
        className="flex items-center justify-center bg-muted rounded border border-dashed border-border"
        style={{ width: size + 8, height: size + 8 }}
      >
        <span className="text-muted-foreground text-xs">?</span>
      </div>
    );
  }

  if (value.library === 'lucide') {
    return <DynamicLucideIcon name={value.name} size={size} className="text-foreground" />;
  }

  // Font Awesome - render as <i> tag
  return <i className={`${value.class} text-foreground`} style={{ fontSize: size }} />;
}

/**
 * Icon grid for Font Awesome with infinite scroll
 */
function FontAwesomeGrid({
  icons,
  selectedIcon,
  onSelect,
  visibleCount,
  setScrollContainer,
  hasMore,
}: {
  icons: FontAwesomeIcon[];
  selectedIcon: IconValue | null;
  onSelect: (icon: FontAwesomeIcon) => void;
  visibleCount: number;
  setScrollContainer: (node: HTMLDivElement | null) => void;
  hasMore: boolean;
}) {
  const visibleIcons = icons.slice(0, visibleCount);

  return (
    <div ref={setScrollContainer} className="max-h-[300px] overflow-y-auto p-2">
      <div className="grid grid-cols-8 gap-1.5">
        {visibleIcons.map((icon) => {
          const isSelected =
            selectedIcon?.library === 'fontawesome' && selectedIcon.class === icon.class;
          return (
            <button
              key={icon.class}
              type="button"
              className={cn(
                'flex items-center justify-center w-9 h-9 rounded-md border border-transparent',
                'hover:bg-accent hover:border-border transition-colors',
                isSelected && 'bg-primary text-primary-foreground border-primary hover:bg-primary hover:border-primary'
              )}
              onClick={() => onSelect(icon)}
              title={`${icon.name} (${icon.style})`}
            >
              <i className={icon.class} style={{ fontSize: 16 }} />
            </button>
          );
        })}
      </div>
      {/* Loading indicator */}
      {hasMore && (
        <div className="h-8 flex items-center justify-center">
          <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
        </div>
      )}
      {icons.length > 0 && (
        <p className="text-xs text-muted-foreground text-center py-2">
          Showing {visibleCount} of {icons.length} icons
        </p>
      )}
    </div>
  );
}

/**
 * Icon grid for Lucide - uses dynamic loading with infinite scroll
 */
function LucideGrid({
  icons,
  selectedIcon,
  onSelect,
  visibleCount,
  setScrollContainer,
  hasMore,
}: {
  icons: LucideIconInfo[];
  selectedIcon: IconValue | null;
  onSelect: (icon: LucideIconInfo) => void;
  visibleCount: number;
  setScrollContainer: (node: HTMLDivElement | null) => void;
  hasMore: boolean;
}) {
  const visibleIcons = icons.slice(0, visibleCount);

  return (
    <div ref={setScrollContainer} className="max-h-[300px] overflow-y-auto p-2">
      <div className="grid grid-cols-8 gap-1.5">
        {visibleIcons.map((icon) => {
          const isSelected = selectedIcon?.library === 'lucide' && selectedIcon.name === icon.name;

          return (
            <button
              key={icon.name}
              type="button"
              className={cn(
                'flex items-center justify-center w-9 h-9 rounded-md border border-transparent',
                'hover:bg-accent hover:border-border transition-colors',
                isSelected && 'bg-primary text-primary-foreground border-primary hover:bg-primary hover:border-primary'
              )}
              onClick={() => onSelect(icon)}
              title={icon.name}
            >
              <DynamicLucideIcon name={icon.name} size={16} />
            </button>
          );
        })}
      </div>
      {/* Loading indicator */}
      {hasMore && (
        <div className="h-8 flex items-center justify-center">
          <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
        </div>
      )}
      {icons.length > 0 && (
        <p className="text-xs text-muted-foreground text-center py-2">
          Showing {visibleCount} of {icons.length} icons
        </p>
      )}
    </div>
  );
}

/**
 * IconPicker component with Font Awesome and Lucide support
 */
export function IconPicker({ value, onChange, className }: IconPickerProps) {
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState('');
  const [activeTab, setActiveTab] = useState<IconLibrary>('fontawesome');

  const parsedValue = useMemo(() => parseIconValue(value), [value]);

  // Get all Font Awesome icons
  const fontAwesomeIcons = useMemo(() => getFontAwesomeIcons(), []);

  // Filter icons based on search
  const filteredFaIcons = useMemo(() => {
    if (!search) return fontAwesomeIcons;
    const lowerSearch = search.toLowerCase();
    return fontAwesomeIcons.filter((icon) => icon.name.toLowerCase().includes(lowerSearch));
  }, [fontAwesomeIcons, search]);

  const filteredLucideIcons = useMemo(() => {
    if (!search) return lucideIcons;
    const lowerSearch = search.toLowerCase();
    return lucideIcons.filter(
      (icon) =>
        icon.name.toLowerCase().includes(lowerSearch) ||
        icon.tags.some((tag) => tag.toLowerCase().includes(lowerSearch))
    );
  }, [search]);

  // Infinite scroll for Font Awesome
  const faScroll = useInfiniteScroll(filteredFaIcons.length);

  // Infinite scroll for Lucide
  const lucideScroll = useInfiniteScroll(filteredLucideIcons.length);

  // Reset scroll when search changes
  useEffect(() => {
    faScroll.reset();
    lucideScroll.reset();
  }, [search]);

  const handleSelectFa = useCallback(
    (icon: FontAwesomeIcon) => {
      const iconValue: IconValue = {
        library: 'fontawesome',
        name: icon.name,
        class: icon.class,
      };
      onChange(formatIconValue(iconValue));
      setOpen(false);
    },
    [onChange]
  );

  const handleSelectLucide = useCallback(
    (icon: LucideIconInfo) => {
      const iconValue: IconValue = {
        library: 'lucide',
        name: icon.name,
      };
      onChange(formatIconValue(iconValue));
      setOpen(false);
    },
    [onChange]
  );

  const handleClear = useCallback(() => {
    onChange('');
  }, [onChange]);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <div className={cn('flex items-center gap-2', className)}>
        <DialogTrigger asChild>
          <Button
            type="button"
            variant="outline"
            className="flex items-center gap-2 min-w-[120px] justify-start"
          >
            <IconPreview value={parsedValue} size={16} />
            <span className="text-sm truncate">
              {parsedValue?.name || 'Select icon'}
            </span>
          </Button>
        </DialogTrigger>
        {parsedValue && (
          <Button
            type="button"
            variant="ghost"
            size="icon"
            className="h-8 w-8"
            onClick={handleClear}
          >
            <X className="h-4 w-4" />
          </Button>
        )}
      </div>

      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>Select Icon</DialogTitle>
        </DialogHeader>

        <div className="space-y-4">
          {/* Search */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              type="text"
              placeholder="Search icons..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="pl-9"
            />
          </div>

          {/* Tabs */}
          <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as IconLibrary)}>
            <TabsList className="grid w-full grid-cols-2">
              <TabsTrigger value="fontawesome">
                Font Awesome ({filteredFaIcons.length})
              </TabsTrigger>
              <TabsTrigger value="lucide">
                Lucide ({filteredLucideIcons.length})
              </TabsTrigger>
            </TabsList>

            <TabsContent value="fontawesome" className="mt-4">
              {filteredFaIcons.length === 0 ? (
                <div className="text-center py-8 text-muted-foreground">
                  No icons found for "{search}"
                </div>
              ) : (
                <FontAwesomeGrid
                  icons={filteredFaIcons}
                  selectedIcon={parsedValue}
                  onSelect={handleSelectFa}
                  visibleCount={faScroll.visibleCount}
                  setScrollContainer={faScroll.setScrollContainer}
                  hasMore={faScroll.hasMore}
                />
              )}
            </TabsContent>

            <TabsContent value="lucide" className="mt-4">
              {filteredLucideIcons.length === 0 ? (
                <div className="text-center py-8 text-muted-foreground">
                  No icons found for "{search}"
                </div>
              ) : (
                <LucideGrid
                  icons={filteredLucideIcons}
                  selectedIcon={parsedValue}
                  onSelect={handleSelectLucide}
                  visibleCount={lucideScroll.visibleCount}
                  setScrollContainer={lucideScroll.setScrollContainer}
                  hasMore={lucideScroll.hasMore}
                />
              )}
            </TabsContent>
          </Tabs>

          {/* Selected icon preview */}
          {parsedValue && (
            <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
              <IconPreview value={parsedValue} size={24} />
              <div className="text-sm">
                <div className="font-medium">{parsedValue.name}</div>
                <div className="text-muted-foreground text-xs">
                  {parsedValue.library === 'fontawesome' ? parsedValue.class : `lucide:${parsedValue.name}`}
                </div>
              </div>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}

export default IconPicker;
