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

declare const wp: {
  apiFetch: <T>(options: {
    path: string;
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
    data?: Record<string, unknown>;
  }) => Promise<T>;
};

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

          {error && <p className="text-sm text-destructive">{error}</p>}
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
