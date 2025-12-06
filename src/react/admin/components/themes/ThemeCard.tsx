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
        'relative group rounded-lg border border-border p-4 transition-all',
        'hover:border-primary/50 hover:shadow-sm',
        isActive && 'border-primary ring-2 ring-primary/20'
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
          variant={isActive ? 'secondary' : 'default'}
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
