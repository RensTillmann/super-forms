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
    <div className="flex-1 flex flex-col min-h-0">
      {/* Action Bar */}
      <div className="px-4 py-3 border-b border-border flex items-center justify-end shrink-0">
        <Button
          onClick={() => setShowCreateDialog(true)}
          size="sm"
          variant="outline"
        >
          <Plus className="h-4 w-4 mr-2" />
          Save Current as Theme
        </Button>
      </div>

      {/* Gallery - scrollable */}
      <div className="flex-1 overflow-y-auto p-4">
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
