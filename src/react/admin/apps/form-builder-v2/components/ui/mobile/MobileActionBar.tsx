import React from 'react';
import { Eye, Save, Send, RefreshCw } from 'lucide-react';
import { Button } from '../../../../../components/ui/button';

interface MobileActionBarProps {
  onPreview: () => void;
  onSave: () => void;
  onPublish: () => void;
  isSaving?: boolean;
}

/**
 * Mobile-only fixed bottom action bar for primary actions.
 * Only visible on mobile viewports (< 640px).
 */
export function MobileActionBar({
  onPreview,
  onSave,
  onPublish,
  isSaving = false,
}: MobileActionBarProps) {
  return (
    <div
      className="fixed bottom-0 left-0 right-0 sm:hidden z-40 bg-background border-t border-border p-3 pb-safe"
      data-testid="mobile-action-bar"
    >
      <div className="flex items-center justify-around gap-2">
        <Button
          variant="ghost"
          onClick={onPreview}
          className="flex-1 flex flex-col items-center gap-1 h-auto py-2 min-h-[44px]"
          data-testid="mobile-action-preview"
        >
          <Eye className="w-5 h-5" />
          <span className="text-xs font-medium">Preview</span>
        </Button>

        <Button
          variant="default"
          onClick={onSave}
          disabled={isSaving}
          className="flex-1 flex flex-col items-center gap-1 h-auto py-2 min-h-[44px]"
          data-testid="mobile-action-save"
        >
          {isSaving ? (
            <RefreshCw className="w-5 h-5 animate-spin" />
          ) : (
            <Save className="w-5 h-5" />
          )}
          <span className="text-xs font-medium">{isSaving ? 'Saving...' : 'Save'}</span>
        </Button>

        <Button
          onClick={onPublish}
          className="flex-1 flex flex-col items-center gap-1 h-auto py-2 min-h-[44px] bg-green-600 hover:bg-green-700 text-white"
          data-testid="mobile-action-publish"
        >
          <Send className="w-5 h-5" />
          <span className="text-xs font-medium">Publish</span>
        </Button>
      </div>
    </div>
  );
}

export default MobileActionBar;
