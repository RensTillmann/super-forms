import React, { useMemo, useCallback } from 'react';
import {
  LayoutTemplate,
  Check,
  DollarSign,
  AtSign,
  Link,
  Globe,
  Percent,
  Scale,
  Copy,
  X,
  Eye,
  EyeOff,
  Hash,
  HelpCircle,
  Search,
  User,
  Mail,
  Phone,
  PoundSterling,
  Euro,
  AlignLeft,
  AlignCenter,
  AlignRight,
  ArrowRight,
  ArrowUp,
  Type,
  Keyboard,
  Lock,
  Calendar,
  Info,
  AlertTriangle,
  Tag,
} from 'lucide-react';
import { cn } from '../../../../../lib/utils';
import { CollapsibleSection } from '../style-sections/CollapsibleSection';
import {
  templateRegistry,
  detectActiveTemplates,
  type Template,
  type TemplateCategory,
} from '../../../../../schemas/templates';

interface TemplatesTabProps {
  element: {
    id: string;
    type: string;
    properties?: Record<string, unknown>;
  };
  onPropertyChange: (propertyName: string, value: unknown) => void;
}

// Map icon names to Lucide components
const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
  'dollar-sign': DollarSign,
  'euro': Euro,
  'pound-sterling': PoundSterling,
  'link': Link,
  'at-sign': AtSign,
  'search': Search,
  'user': User,
  'mail': Mail,
  'phone': Phone,
  'globe': Globe,
  'percent': Percent,
  'scale': Scale,
  'copy': Copy,
  'x': X,
  'eye': Eye,
  'eye-off': EyeOff,
  'hash': Hash,
  'help-circle': HelpCircle,
  'align-left': AlignLeft,
  'align-center': AlignCenter,
  'align-right': AlignRight,
  'arrow-right': ArrowRight,
  'arrow-up': ArrowUp,
  'type': Type,
  'keyboard': Keyboard,
  'lock': Lock,
  'calendar': Calendar,
  'info': Info,
  'alert-triangle': AlertTriangle,
  'tag': Tag,
};

// Category display config - ordered for display
const categoryOrder: TemplateCategory[] = [
  'label',
  'description',
  'placeholder',
  'inset',
  'tooltip',
  'prefix',
  'suffix',
  'action',
  'feedback',
];

const categoryConfig: Record<TemplateCategory, { label: string; icon: React.ReactNode; defaultExpanded?: boolean }> = {
  label: { label: 'Label Position', icon: <Tag className="h-3.5 w-3.5" />, defaultExpanded: true },
  description: { label: 'Description Position', icon: <AlignLeft className="h-3.5 w-3.5" /> },
  placeholder: { label: 'Placeholder Text', icon: <Type className="h-3.5 w-3.5" /> },
  inset: { label: 'Inset Input Icon', icon: <Search className="h-3.5 w-3.5" /> },
  tooltip: { label: 'Input Tooltip', icon: <HelpCircle className="h-3.5 w-3.5" /> },
  prefix: { label: 'Prefix Addons', icon: <AtSign className="h-3.5 w-3.5" /> },
  suffix: { label: 'Suffix Addons', icon: <Globe className="h-3.5 w-3.5" /> },
  action: { label: 'Action Buttons', icon: <Copy className="h-3.5 w-3.5" /> },
  feedback: { label: 'User Feedback', icon: <Hash className="h-3.5 w-3.5" /> },
};

interface TemplateCardProps {
  template: Template;
  isActive: boolean;
  onClick: () => void;
}

const TemplateCard: React.FC<TemplateCardProps> = ({
  template,
  isActive,
  onClick,
}) => {
  const IconComponent = template.icon ? iconMap[template.icon] : LayoutTemplate;

  return (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        "relative flex flex-col items-center gap-1.5 p-3 rounded-lg border-2 transition-all",
        "min-w-[80px] text-center",
        isActive
          ? "border-primary bg-primary/5 text-primary"
          : "border-gray-200 hover:border-primary/50 hover:bg-gray-50 text-gray-700",
        "focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
      )}
      title={template.description}
      data-testid={`template-card-${template.id}`}
    >
      {/* Active indicator */}
      {isActive && (
        <div className="absolute top-1 right-1 w-4 h-4 rounded-full flex items-center justify-center bg-primary text-white">
          <Check className="h-2.5 w-2.5" />
        </div>
      )}

      {/* Icon */}
      <div className={cn(
        "w-8 h-8 rounded-md flex items-center justify-center",
        isActive ? "bg-primary/10" : "bg-gray-100"
      )}>
        {IconComponent && <IconComponent className="h-4 w-4" />}
      </div>

      {/* Preview Label */}
      <span className="text-xs font-medium truncate w-full">
        {template.previewLabel || template.name}
      </span>

      {/* Name (smaller) */}
      <span className="text-[10px] text-gray-500 truncate w-full">
        {template.name}
      </span>
    </button>
  );
};

/**
 * Templates Tab - Visual template picker with instant apply
 *
 * Click a template to apply it instantly. Click again to remove it.
 * Conflicting templates are automatically replaced when selecting a new one.
 */
export const TemplatesTab: React.FC<TemplatesTabProps> = ({
  element,
  onPropertyChange,
}) => {
  const properties = element.properties || {};

  // Get compatible templates for this element type
  const compatibleTemplates = useMemo(
    () => templateRegistry.getCompatibleTemplates(element.type),
    [element.type]
  );

  // Detect which templates are currently active based on element properties
  const activeTemplates = useMemo(
    () => new Set(detectActiveTemplates(element.type, properties)),
    [element.type, properties]
  );

  // Group templates by category (in display order)
  const templatesByCategory = useMemo(() => {
    const grouped = new Map<TemplateCategory, Template[]>();
    for (const template of compatibleTemplates) {
      const list = grouped.get(template.category) || [];
      list.push(template);
      grouped.set(template.category, list);
    }
    return grouped;
  }, [compatibleTemplates]);

  // Handle template click - instant apply/remove
  const handleTemplateClick = useCallback(
    (template: Template) => {
      const isCurrentlyActive = activeTemplates.has(template.id);

      if (isCurrentlyActive) {
        // Deactivate: reset the properties to empty/default values
        for (const mutation of template.mutations) {
          const defaultValue = typeof mutation.value === 'boolean' ? false : '';
          onPropertyChange(mutation.property, defaultValue);
        }
      } else {
        // Activate: apply the template's mutations
        for (const mutation of template.mutations) {
          onPropertyChange(mutation.property, mutation.value);
        }
      }
    },
    [activeTemplates, onPropertyChange]
  );

  // Clear all templates in a category
  const handleClearCategory = useCallback(
    (templates: Template[]) => {
      for (const template of templates) {
        if (activeTemplates.has(template.id)) {
          for (const mutation of template.mutations) {
            const defaultValue = typeof mutation.value === 'boolean' ? false : '';
            onPropertyChange(mutation.property, defaultValue);
          }
        }
      }
    },
    [activeTemplates, onPropertyChange]
  );

  const hasActiveTemplates = activeTemplates.size > 0;

  if (compatibleTemplates.length === 0) {
    return (
      <div className="text-center py-8" data-testid="templates-tab-empty">
        <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-3">
          <LayoutTemplate className="h-5 w-5 text-gray-400" />
        </div>
        <h4 className="text-sm font-medium text-gray-700 mb-1">No Templates Available</h4>
        <p className="text-xs text-gray-500 max-w-[200px] mx-auto">
          This element type doesn't have any templates defined yet.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-3" data-testid="templates-tab">
      {/* Active templates indicator */}
      {hasActiveTemplates && (
        <div className="flex items-center gap-2 p-2 bg-primary/5 border border-primary/20 rounded-lg text-xs text-primary" data-testid="templates-active-indicator">
          <Check className="h-3.5 w-3.5" />
          <span>{activeTemplates.size} active template{activeTemplates.size > 1 ? 's' : ''}</span>
        </div>
      )}

      {/* Template categories - in defined order */}
      {categoryOrder.map((category) => {
        const templates = templatesByCategory.get(category);
        if (!templates || templates.length === 0) return null;

        const config = categoryConfig[category];
        const activeInCategory = templates.filter((t) => activeTemplates.has(t.id)).length;

        return (
          <CollapsibleSection
            key={category}
            title={config.label}
            icon={config.icon}
            defaultExpanded={config.defaultExpanded || false}
            hasOverrides={activeInCategory > 0}
            overrideCount={activeInCategory}
            onReset={
              activeInCategory > 0
                ? () => handleClearCategory(templates)
                : undefined
            }
          >
            <div className="flex flex-wrap gap-2" data-testid={`templates-category-${category}`}>
              {templates.map((template) => (
                <TemplateCard
                  key={template.id}
                  template={template}
                  isActive={activeTemplates.has(template.id)}
                  onClick={() => handleTemplateClick(template)}
                />
              ))}
            </div>
          </CollapsibleSection>
        );
      })}

      {/* Help text */}
      <div className="mt-4 p-3 bg-blue-50 rounded-lg" data-testid="templates-help">
        <p className="text-xs text-blue-700">
          <strong>Tip:</strong> Click a template to apply it instantly. Click again to remove.
          Use the reset button (↺) in section headers to clear all templates in that category.
        </p>
      </div>
    </div>
  );
};

export default TemplatesTab;
