import { Paintbrush } from 'lucide-react';
import { useStyleClipboard } from '../hooks/useStyleClipboard';

export function StyleClipboardIndicator() {
  const clipboard = useStyleClipboard((state) => state.clipboard);

  if (!clipboard) return null;

  return (
    <div className="fixed bottom-4 right-4 flex items-center gap-2 px-3 py-2 bg-secondary text-secondary-foreground rounded-full text-sm shadow-lg animate-in fade-in slide-in-from-bottom-2">
      <Paintbrush className="h-3 w-3" />
      Style copied
    </div>
  );
}
