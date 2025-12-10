import React, { useState } from 'react';
import { Copy, Check, Code2, Hash, Braces, FileCode, Zap, Bug } from 'lucide-react';
import { Input } from '../../../../../components/ui/input';
import { Label } from '../../../../../components/ui/label';
import { Button } from '../../../../../components/ui/button';
import { Textarea } from '../../../../../components/ui/textarea';
import { CollapsibleSection } from '../style-sections/CollapsibleSection';
import { cn } from '../../../../../lib/utils';

interface CodeTabProps {
  element: {
    id: string;
    type: string;
    properties?: Record<string, unknown>;
  };
  onPropertyChange: (propertyName: string, value: unknown) => void;
}

/**
 * Code tab - developer-focused settings.
 * Element ID, CSS classes, custom attributes, custom CSS, and debug info.
 */
export const CodeTab: React.FC<CodeTabProps> = ({
  element,
  onPropertyChange,
}) => {
  const [copied, setCopied] = useState(false);

  const handleCopyId = () => {
    navigator.clipboard.writeText(element.id);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const customId = (element.properties?.customId as string) || '';
  const cssClasses = (element.properties?.cssClasses as string) || '';
  const customCss = (element.properties?.customCss as string) || '';
  const customAttributes = (element.properties?.customAttributes as string) || '';

  // Check for overrides
  const hasIdentityOverrides = customId !== '' || cssClasses !== '';
  const hasAttributeOverrides = customAttributes !== '';
  const hasCssOverrides = customCss !== '';

  return (
    <div className="space-y-2 p-4">
      {/* Identity Section */}
      <CollapsibleSection
        title="Identity"
        icon={<Hash className="h-3.5 w-3.5" />}
        defaultExpanded={true}
        hasOverrides={hasIdentityOverrides}
      >
        <div className="space-y-4">
          {/* Element ID */}
          <div className="space-y-1.5">
            <Label className="text-xs text-gray-600">Custom ID</Label>
            <div className="flex items-center gap-2">
              <Input
                value={customId}
                onChange={(e) => onPropertyChange('customId', e.target.value)}
                placeholder={element.id}
                className="h-9 text-sm font-mono"
              />
              <Button
                variant="outline"
                size="sm"
                onClick={handleCopyId}
                className="h-9 px-2.5 shrink-0"
                title="Copy internal ID"
              >
                {copied ? (
                  <Check className="h-4 w-4 text-green-500" />
                ) : (
                  <Copy className="h-4 w-4" />
                )}
              </Button>
            </div>
            <p className="text-xs text-gray-400">
              Internal: <code className="bg-gray-100 px-1 rounded text-[11px]">{element.id}</code>
            </p>
          </div>

          {/* CSS Classes */}
          <div className="space-y-1.5">
            <Label className="text-xs text-gray-600 flex items-center gap-1">
              <Code2 className="h-3 w-3" />
              CSS Classes
            </Label>
            <Input
              value={cssClasses}
              onChange={(e) => onPropertyChange('cssClasses', e.target.value)}
              placeholder="my-class another-class"
              className="h-9 text-sm font-mono"
            />
            <p className="text-xs text-gray-400">
              Space-separated class names
            </p>
          </div>
        </div>
      </CollapsibleSection>

      {/* Custom Attributes Section */}
      <CollapsibleSection
        title="Attributes"
        icon={<Braces className="h-3.5 w-3.5" />}
        defaultExpanded={false}
        hasOverrides={hasAttributeOverrides}
      >
        <div className="space-y-3">
          <Textarea
            value={customAttributes}
            onChange={(e) => onPropertyChange('customAttributes', e.target.value)}
            placeholder={'data-tracking="signup-form"\naria-describedby="help-text"'}
            className="min-h-[80px] text-sm font-mono resize-y"
          />
          <p className="text-xs text-gray-500">
            One attribute per line. Supports <code className="bg-gray-100 px-1 rounded">data-*</code> and <code className="bg-gray-100 px-1 rounded">aria-*</code> attributes.
          </p>
        </div>
      </CollapsibleSection>

      {/* Custom CSS Section */}
      <CollapsibleSection
        title="Custom CSS"
        icon={<FileCode className="h-3.5 w-3.5" />}
        defaultExpanded={false}
        hasOverrides={hasCssOverrides}
      >
        <div className="space-y-3">
          <Textarea
            value={customCss}
            onChange={(e) => onPropertyChange('customCss', e.target.value)}
            placeholder={`.this {\n  /* Your CSS here */\n  border-color: #3b82f6;\n}`}
            className="min-h-[120px] text-sm font-mono resize-y bg-gray-900 text-gray-100 placeholder:text-gray-500"
          />
          <p className="text-xs text-gray-500">
            Use <code className="bg-gray-100 px-1 rounded">.this</code> to target this element. CSS is scoped to prevent conflicts.
          </p>
        </div>
      </CollapsibleSection>

      {/* Events Section (Placeholder) */}
      <CollapsibleSection
        title="Events"
        icon={<Zap className="h-3.5 w-3.5" />}
        defaultExpanded={false}
      >
        <div className="text-center py-6">
          <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-3">
            <Zap className="h-5 w-5 text-gray-400" />
          </div>
          <h4 className="text-sm font-medium text-gray-700 mb-1">
            JavaScript Events
          </h4>
          <p className="text-xs text-gray-500 max-w-[200px] mx-auto">
            Execute custom JavaScript on field events like change, focus, or blur.
          </p>
          <button
            className={cn(
              "mt-4 inline-flex items-center gap-1.5 px-3 py-1.5",
              "text-xs font-medium text-primary",
              "bg-primary/5 hover:bg-primary/10 rounded-md transition-colors"
            )}
            onClick={() => {
              // TODO: Open event handler modal
              console.log('Open event handler modal');
            }}
          >
            <span>+ Add event handler</span>
          </button>
        </div>
      </CollapsibleSection>

      {/* Debug Info Section */}
      <CollapsibleSection
        title="Developer Info"
        icon={<Bug className="h-3.5 w-3.5" />}
        defaultExpanded={false}
      >
        <div className="space-y-3">
          <div className="grid grid-cols-2 gap-2 text-xs">
            <div className="p-2 bg-gray-50 rounded">
              <span className="text-gray-500">Type:</span>
              <span className="ml-1 font-mono">{element.type}</span>
            </div>
            <div className="p-2 bg-gray-50 rounded">
              <span className="text-gray-500">ID:</span>
              <span className="ml-1 font-mono truncate">{element.id.slice(0, 8)}...</span>
            </div>
          </div>
          <details className="text-xs">
            <summary className="cursor-pointer text-gray-500 hover:text-gray-700 py-1">
              Full Element JSON
            </summary>
            <pre className="mt-2 p-3 bg-gray-900 text-gray-100 rounded-md overflow-auto max-h-48 text-[11px] font-mono">
              {JSON.stringify(element, null, 2)}
            </pre>
          </details>
        </div>
      </CollapsibleSection>
    </div>
  );
};

export default CodeTab;
