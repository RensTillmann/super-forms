import React from 'react';
import { Eye, Save, Send, RefreshCw } from 'lucide-react';
import { cn } from '../../../lib/utils';

interface MobileActionBarProps {
  onPreview: () => void;
  onSave: () => void;
  onPublish: () => void;
  isSaving?: boolean;
}

/**
 * Fixed bottom action bar for primary actions on mobile.
 * Only visible on mobile viewports (< 640px).
 */
export function MobileActionBar({
  onPreview,
  onSave,
  onPublish,
  isSaving = false,
}: MobileActionBarProps) {
  return (
    <div className="fixed bottom-0 left-0 right-0 sm:hidden z-40 bg-background border-t border-border p-3 pb-safe">
      <div className="flex items-center justify-around gap-2">
        <button
          onClick={onPreview}
          className={cn(
            'flex-1 flex flex-col items-center gap-1 py-2 rounded-md',
            'min-h-[44px]', // Touch target
            'text-muted-foreground hover:bg-muted active:bg-muted transition-colors'
          )}
        >
          <Eye className="w-5 h-5" />
          <span className="text-xs font-medium">Preview</span>
        </button>

        <button
          onClick={onSave}
          disabled={isSaving}
          className={cn(
            'flex-1 flex flex-col items-center gap-1 py-2 rounded-md',
            'min-h-[44px]', // Touch target
            'bg-primary text-primary-foreground',
            'hover:bg-primary/90 active:bg-primary/80 transition-colors',
            isSaving && 'opacity-70 cursor-not-allowed'
          )}
        >
          {isSaving ? (
            <RefreshCw className="w-5 h-5 animate-spin" />
          ) : (
            <Save className="w-5 h-5" />
          )}
          <span className="text-xs font-medium">{isSaving ? 'Saving...' : 'Save'}</span>
        </button>

        <button
          onClick={onPublish}
          className={cn(
            'flex-1 flex flex-col items-center gap-1 py-2 rounded-md',
            'min-h-[44px]', // Touch target
            'bg-green-600 text-white',
            'hover:bg-green-700 active:bg-green-800 transition-colors'
          )}
        >
          <Send className="w-5 h-5" />
          <span className="text-xs font-medium">Publish</span>
        </button>
      </div>
    </div>
  );
}

export default MobileActionBar;
