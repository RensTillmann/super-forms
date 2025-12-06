import React, { useState } from 'react';
import { Plus } from 'lucide-react';
import { Button } from '../ui/button';
import { ThemeGallery } from './ThemeGallery';
import { CreateThemeDialog } from './CreateThemeDialog';
import { useThemes } from './hooks/useThemes';

export const ThemesTab: React.FC = () => {
  const { themes, loading, error, activeThemeId, refresh, applyTheme, deleteTheme } =
    useThemes();
  const [showCreateDialog, setShowCreateDialog] = useState(false);

  if (loading) {
    return (
      <div className="p-6 text-center text-muted-foreground">
        Loading themes...
      </div>
    );
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
          activeThemeId={activeThemeId ?? undefined}
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
