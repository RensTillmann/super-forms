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
  const systemThemes = themes.filter((t) => t.is_system && !t.is_stub);
  const stubThemes = themes.filter((t) => t.is_stub);
  const customThemes = themes.filter((t) => !t.is_system);

  return (
    <div className="space-y-8">
      {/* System Themes */}
      {systemThemes.length > 0 && (
        <section>
          <h4 className="text-sm font-medium text-muted-foreground mb-3">
            System Themes
          </h4>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            {systemThemes.map((theme) => (
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
            {stubThemes.map((theme) => (
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
            {customThemes.map((theme) => (
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
