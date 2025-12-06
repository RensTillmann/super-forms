import { useState, useEffect, useCallback } from 'react';
import { styleRegistry } from '../../../schemas/styles';

declare const wp: {
  apiFetch: <T>(options: {
    path: string;
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
    data?: Record<string, unknown>;
  }) => Promise<T>;
};

export interface Theme {
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
      const response = await wp.apiFetch<Theme[]>({
        path: '/super-forms/v1/themes',
        method: 'GET',
      });
      setThemes(response);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load themes');
    } finally {
      setLoading(false);
    }
  }, []);

  const applyTheme = useCallback(
    async (themeId: number) => {
      try {
        const theme = themes.find((t) => t.id === themeId);
        if (!theme || theme.is_stub) return;

        // Apply to local registry immediately for live preview
        styleRegistry.importStyles(JSON.stringify(theme.styles));
        setActiveThemeId(themeId);
      } catch (err) {
        console.error('Failed to apply theme:', err);
      }
    },
    [themes]
  );

  const deleteTheme = useCallback(async (themeId: number) => {
    if (!confirm('Are you sure you want to delete this theme?')) return;

    try {
      await wp.apiFetch({
        path: `/super-forms/v1/themes/${themeId}`,
        method: 'DELETE',
      });
      setThemes((prev) => prev.filter((t) => t.id !== themeId));
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
