---
name: 04-themes-tab-ui
parent: m-implement-styles-themes-ui
status: pending
created: 2025-12-06
---

# Subtask 4: Themes Tab UI

## Goal

Create the Themes tab with a gallery view showing theme cards, preview swatches, apply functionality, and a dialog for saving custom themes.

## Success Criteria

- [ ] Themes tab registered (position 51, after Style tab)
- [ ] ThemesTab component renders gallery of theme cards
- [ ] ThemeCard shows name, description, preview swatches
- [ ] "Coming Soon" badge on stub themes (is_stub = true)
- [ ] Apply theme button (disabled for stubs)
- [ ] Delete button for user's custom themes
- [ ] "Save Current as Theme" button opens CreateThemeDialog
- [ ] Themes fetched from REST API on mount
- [ ] Real-time theme application updates form preview

## Technical Specification

### Tab Registration

Add to `/src/react/admin/schemas/tabs/index.ts`:

```typescript
/**
 * Themes tab - Pick from presets or save custom themes
 */
export const ThemesTab = registerTab({
  id: 'themes',
  label: 'Themes',
  icon: 'Palette',
  position: 51, // After Style (50)
  lazyLoad: false,
  description: 'Apply and create themes',
});
```

Add `Palette` to icon mapping in `/src/react/admin/apps/form-builder-v2/components/TabBar.tsx`:

```typescript
const iconMap: Record<string, LucideIcon> = {
  // ... existing
  Palette,
};
```

### Component Structure

```
/src/react/admin/components/themes/
├── ThemesTab.tsx          # Main tab component
├── ThemeGallery.tsx       # Grid of theme cards
├── ThemeCard.tsx          # Individual theme card
├── CreateThemeDialog.tsx  # Save current as theme
└── hooks/
    └── useThemes.ts       # Fetch/manage themes
```

### ThemesTab Component

```tsx
// /src/react/admin/components/themes/ThemesTab.tsx

import React, { useState } from 'react';
import { Plus } from 'lucide-react';
import { Button } from '../ui/button';
import { ThemeGallery } from './ThemeGallery';
import { CreateThemeDialog } from './CreateThemeDialog';
import { useThemes } from './hooks/useThemes';

export const ThemesTab: React.FC = () => {
  const { themes, loading, error, refresh, applyTheme, deleteTheme } = useThemes();
  const [showCreateDialog, setShowCreateDialog] = useState(false);

  if (loading) {
    return <div className="p-6 text-center text-muted-foreground">Loading themes...</div>;
  }

  if (error) {
    return <div className="p-6 text-center text-destructive">{error}</div>;
  }

  return (
    <div className="flex-1 flex flex-col overflow-auto">
      {/* Header */}
      <div className="p-4 border-b border-border flex items-center justify-between">
        <div>
          <h3 className="font-semibold text-lg">Form Themes</h3>
          <p className="text-sm text-muted-foreground">
            Choose a preset theme or save your current styling
          </p>
        </div>
        <Button
          onClick={() => setShowCreateDialog(true)}
          size="sm"
          variant="outline"
        >
          <Plus className="h-4 w-4 mr-2" />
          Save Current as Theme
        </Button>
      </div>

      {/* Gallery */}
      <div className="flex-1 overflow-auto p-4">
        <ThemeGallery
          themes={themes}
          onApply={applyTheme}
          onDelete={deleteTheme}
        />
      </div>

      {/* Create Dialog */}
      <CreateThemeDialog
        open={showCreateDialog}
        onOpenChange={setShowCreateDialog}
        onCreated={() => {
          refresh();
          setShowCreateDialog(false);
        }}
      />
    </div>
  );
};
```

### ThemeCard Component

```tsx
// /src/react/admin/components/themes/ThemeCard.tsx

import React from 'react';
import { Check, Trash2 } from 'lucide-react';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { cn } from '../../lib/utils';

interface ThemeCardProps {
  theme: {
    id: number;
    name: string;
    description?: string;
    category: string;
    preview_colors: string[];
    is_system: boolean;
    is_stub: boolean;
    user_id: number | null;
  };
  isActive?: boolean;
  onApply: (themeId: number) => void;
  onDelete?: (themeId: number) => void;
}

export const ThemeCard: React.FC<ThemeCardProps> = ({
  theme,
  isActive,
  onApply,
  onDelete,
}) => {
  const canDelete = !theme.is_system && theme.user_id !== null;
  const isStub = theme.is_stub;

  return (
    <div
      className={cn(
        "relative group rounded-lg border border-border p-4 transition-all",
        "hover:border-primary/50 hover:shadow-sm",
        isActive && "border-primary ring-2 ring-primary/20"
      )}
    >
      {/* Preview Swatches */}
      <div className="flex gap-1 mb-3">
        {theme.preview_colors.map((color, index) => (
          <div
            key={index}
            className="h-8 flex-1 rounded-sm border border-border/50"
            style={{ backgroundColor: color }}
          />
        ))}
      </div>

      {/* Theme Info */}
      <div className="mb-3">
        <div className="flex items-center gap-2">
          <h4 className="font-medium text-sm">{theme.name}</h4>
          {isStub && (
            <Badge variant="secondary" className="text-xs">
              Coming Soon
            </Badge>
          )}
          {isActive && (
            <Badge variant="default" className="text-xs">
              Active
            </Badge>
          )}
        </div>
        {theme.description && (
          <p className="text-xs text-muted-foreground mt-1 line-clamp-2">
            {theme.description}
          </p>
        )}
      </div>

      {/* Actions */}
      <div className="flex items-center gap-2">
        <Button
          size="sm"
          variant={isActive ? "secondary" : "default"}
          className="flex-1"
          disabled={isStub}
          onClick={() => onApply(theme.id)}
        >
          {isActive ? (
            <>
              <Check className="h-4 w-4 mr-1" />
              Applied
            </>
          ) : isStub ? (
            'Coming Soon'
          ) : (
            'Apply'
          )}
        </Button>

        {canDelete && (
          <Button
            size="sm"
            variant="ghost"
            className="text-destructive hover:text-destructive hover:bg-destructive/10"
            onClick={() => onDelete?.(theme.id)}
          >
            <Trash2 className="h-4 w-4" />
          </Button>
        )}
      </div>
    </div>
  );
};
```

### ThemeGallery Component

```tsx
// /src/react/admin/components/themes/ThemeGallery.tsx

import React from 'react';
import { ThemeCard } from './ThemeCard';

interface Theme {
  id: number;
  name: string;
  description?: string;
  category: string;
  preview_colors: string[];
  is_system: boolean;
  is_stub: boolean;
  user_id: number | null;
}

interface ThemeGalleryProps {
  themes: Theme[];
  activeThemeId?: number;
  onApply: (themeId: number) => void;
  onDelete: (themeId: number) => void;
}

export const ThemeGallery: React.FC<ThemeGalleryProps> = ({
  themes,
  activeThemeId,
  onApply,
  onDelete,
}) => {
  // Group themes by category
  const systemThemes = themes.filter(t => t.is_system && !t.is_stub);
  const stubThemes = themes.filter(t => t.is_stub);
  const customThemes = themes.filter(t => !t.is_system);

  return (
    <div className="space-y-8">
      {/* System Themes */}
      {systemThemes.length > 0 && (
        <section>
          <h4 className="text-sm font-medium text-muted-foreground mb-3">
            System Themes
          </h4>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            {systemThemes.map(theme => (
              <ThemeCard
                key={theme.id}
                theme={theme}
                isActive={theme.id === activeThemeId}
                onApply={onApply}
                onDelete={onDelete}
              />
            ))}
          </div>
        </section>
      )}

      {/* Coming Soon */}
      {stubThemes.length > 0 && (
        <section>
          <h4 className="text-sm font-medium text-muted-foreground mb-3">
            Coming Soon
          </h4>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            {stubThemes.map(theme => (
              <ThemeCard
                key={theme.id}
                theme={theme}
                onApply={onApply}
                onDelete={onDelete}
              />
            ))}
          </div>
        </section>
      )}

      {/* Custom Themes */}
      {customThemes.length > 0 && (
        <section>
          <h4 className="text-sm font-medium text-muted-foreground mb-3">
            My Custom Themes
          </h4>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            {customThemes.map(theme => (
              <ThemeCard
                key={theme.id}
                theme={theme}
                isActive={theme.id === activeThemeId}
                onApply={onApply}
                onDelete={onDelete}
              />
            ))}
          </div>
        </section>
      )}

      {themes.length === 0 && (
        <div className="text-center text-muted-foreground py-8">
          No themes available
        </div>
      )}
    </div>
  );
};
```

### CreateThemeDialog Component

```tsx
// /src/react/admin/components/themes/CreateThemeDialog.tsx

import React, { useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '../ui/dialog';
import { Button } from '../ui/button';
import { Input } from '../ui/input';
import { Textarea } from '../ui/textarea';
import { Label } from '../ui/label';
import { styleRegistry } from '../../schemas/styles';

interface CreateThemeDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onCreated: () => void;
}

export const CreateThemeDialog: React.FC<CreateThemeDialogProps> = ({
  open,
  onOpenChange,
  onCreated,
}) => {
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [category, setCategory] = useState('light');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSave = async () => {
    if (!name.trim()) {
      setError('Theme name is required');
      return;
    }

    setSaving(true);
    setError(null);

    try {
      // Export current styles
      const currentStyles = styleRegistry.exportStyles();
      const parsedStyles = JSON.parse(currentStyles);

      // Generate preview colors
      const previewColors = [
        parsedStyles.input?.backgroundColor || '#ffffff',
        parsedStyles.label?.color || '#1f2937',
        parsedStyles.button?.backgroundColor || '#2563eb',
        parsedStyles.input?.borderColor || '#d1d5db',
      ];

      // Save via REST API
      await wp.apiFetch({
        path: '/super-forms/v1/themes',
        method: 'POST',
        data: {
          name: name.trim(),
          description: description.trim(),
          category,
          styles: parsedStyles,
          preview_colors: previewColors,
        },
      });

      // Reset form
      setName('');
      setDescription('');
      setCategory('light');
      onCreated();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save theme');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Save Current as Theme</DialogTitle>
          <DialogDescription>
            Save your current form styling as a reusable theme.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 py-4">
          <div className="space-y-2">
            <Label htmlFor="theme-name">Theme Name</Label>
            <Input
              id="theme-name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="My Custom Theme"
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="theme-description">Description (optional)</Label>
            <Textarea
              id="theme-description"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="A brief description of this theme..."
              rows={3}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="theme-category">Category</Label>
            <select
              id="theme-category"
              value={category}
              onChange={(e) => setCategory(e.target.value)}
              className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
            >
              <option value="light">Light</option>
              <option value="dark">Dark</option>
              <option value="minimal">Minimal</option>
              <option value="corporate">Corporate</option>
              <option value="playful">Playful</option>
            </select>
          </div>

          {error && (
            <p className="text-sm text-destructive">{error}</p>
          )}
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button onClick={handleSave} disabled={saving}>
            {saving ? 'Saving...' : 'Save Theme'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};
```

### useThemes Hook

```tsx
// /src/react/admin/components/themes/hooks/useThemes.ts

import { useState, useEffect, useCallback } from 'react';
import { styleRegistry } from '../../../schemas/styles';

interface Theme {
  id: number;
  name: string;
  slug: string;
  description?: string;
  category: string;
  styles: Record<string, unknown>;
  preview_colors: string[];
  is_system: boolean;
  is_stub: boolean;
  user_id: number | null;
}

export function useThemes() {
  const [themes, setThemes] = useState<Theme[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [activeThemeId, setActiveThemeId] = useState<number | null>(null);

  const fetchThemes = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const response = await wp.apiFetch({
        path: '/super-forms/v1/themes',
        method: 'GET',
      });
      setThemes(response as Theme[]);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load themes');
    } finally {
      setLoading(false);
    }
  }, []);

  const applyTheme = useCallback(async (themeId: number) => {
    try {
      const theme = themes.find(t => t.id === themeId);
      if (!theme || theme.is_stub) return;

      // Apply to local registry immediately for live preview
      styleRegistry.importStyles(JSON.stringify(theme.styles));
      setActiveThemeId(themeId);

      // Persist via REST API (in background)
      // Form ID would come from form context
      // await wp.apiFetch({ ... });

    } catch (err) {
      console.error('Failed to apply theme:', err);
    }
  }, [themes]);

  const deleteTheme = useCallback(async (themeId: number) => {
    if (!confirm('Are you sure you want to delete this theme?')) return;

    try {
      await wp.apiFetch({
        path: `/super-forms/v1/themes/${themeId}`,
        method: 'DELETE',
      });
      setThemes(prev => prev.filter(t => t.id !== themeId));
    } catch (err) {
      console.error('Failed to delete theme:', err);
    }
  }, []);

  useEffect(() => {
    fetchThemes();
  }, [fetchThemes]);

  return {
    themes,
    loading,
    error,
    activeThemeId,
    refresh: fetchThemes,
    applyTheme,
    deleteTheme,
  };
}
```

### Integration in FormBuilderV2

Add to the tab content area in `FormBuilderV2.tsx`:

```tsx
// Add import
import { ThemesTab } from '../components/themes/ThemesTab';

// In render, after style tab
{activeTab === 'themes' && <ThemesTab />}
```

## Files to Create

1. `/src/react/admin/components/themes/ThemesTab.tsx`
2. `/src/react/admin/components/themes/ThemeGallery.tsx`
3. `/src/react/admin/components/themes/ThemeCard.tsx`
4. `/src/react/admin/components/themes/CreateThemeDialog.tsx`
5. `/src/react/admin/components/themes/hooks/useThemes.ts`
6. `/src/react/admin/components/themes/index.ts` (barrel export)

## Files to Modify

1. `/src/react/admin/schemas/tabs/index.ts` - Register themes tab
2. `/src/react/admin/apps/form-builder-v2/components/TabBar.tsx` - Add Palette icon
3. `/src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx` - Render ThemesTab

## Implementation Notes

- Use existing shadcn/ui components (Button, Dialog, Badge, etc.)
- Themes fetched from REST API `/super-forms/v1/themes`
- Preview swatches extracted from theme's `preview_colors` array
- Stub themes show badge and disabled apply button
- Delete only available for user's own custom themes
- Apply theme updates styleRegistry for immediate preview

## Dependencies

- Subtask 01 (Database) must be complete
- Subtask 02 (REST API) must be complete
- Existing shadcn/ui components
- Existing styleRegistry
