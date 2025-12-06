import React, { useState, useCallback, useRef, useEffect, Suspense, lazy } from 'react';
import { v4 as uuidv4 } from 'uuid';
import {
  Type, Mail, FileText, List, CheckSquare, Square, Radio, Calendar,
  Phone, Link, Upload, Image, Star, Hash, Clock, MapPin, User,
  CreditCard, Globe, Code, ChevronDown, Search,
  Monitor, Tablet, Smartphone, Save, Eye, Send, Copy, Trash2,
  Move, Settings, MoreVertical, X, Plus, Layers, Database,
  HelpCircle, AlertCircle, Check, ChevronUp, Zap, BarChart,
  Bold, Italic, Underline, AlignLeft, AlignCenter, AlignRight,
  Palette, Type as FontIcon, Edit3, MousePointer, Lock, EyeOff, CheckCircle,
  PenTool, Sliders, Columns, Scissors, FileCode, Webhook,
  Calculator, ZoomIn, ZoomOut, Grid, Activity, Share, History,
  Download, RotateCcw, Filter, Menu, Maximize, Play, Target,
  Palette as PaintBucket, Layers as Layers3, Repeat, Users, Key, Bell, Archive,
  BookOpen, RefreshCw, ArrowUp, ArrowDown, Layout,
  Pencil, Circle, Gift, Cloud as CloudUpload, ExternalLink, Terminal as Command,
  Shield, CircleCheck, FolderOpen, SquareStack, Layers2,
  ChevronDownSquare, StepForward, Container, Box, Workflow,
  ChevronLeft, ChevronRight
} from 'lucide-react';
import { useElementsStore, useBuilderStore } from './store';
import { ElementRenderer } from './components/elements';
import { PropertyPanelRegistry, FloatingPanel } from './components/property-panels';
import { TabBar } from './components/TabBar';
import { TopBar } from './components/TopBar';
import { cn } from '../../lib/utils';
import { getElementSchema, isElementRegistered } from '../../schemas/core/registry';
// Initialize tab schemas
import '../../schemas/tabs';
// Import UI Components from the extracted library
import {
  FormSelector,
  Toast,
  ToastProvider,
  ErrorBoundary,
  SharePanel,
  ExportPanel,
  AnalyticsPanel,
  VersionHistoryPanel,
  ContextMenu,
  FloatingToolbar,
  GridOverlay,
  ResizableBottomTray,
  ZoomControls,
  InlineEditableText
} from './components/ui';
// Lazy loaded tabs (code splitting)
const EmailsTab = lazy(() => import('./tabs/EmailsTab'));
const AutomationsTab = lazy(() => import('../../components/form-builder/automations/AutomationsTab').then(m => ({ default: m.AutomationsTab })));
const ThemesTab = lazy(() => import('../../components/themes').then(m => ({ default: m.ThemesTab })));
import { GlobalStylesPanel } from '../../components/settings/GlobalStylesPanel';

// Import CSS styles (form-builder.css contains element/canvas styling not yet migrated to Tailwind)
import './styles/form-builder.css';


// Complete Element categories with ALL form element types and searchable metadata
const ELEMENT_CATEGORIES = [
  {
    id: 'all',
    name: 'All',
    elements: [] // Will be populated with all elements from other categories
  },
  {
    id: 'basic',
    name: 'Basic',
    elements: [
      { type: 'text', label: 'Text Input', icon: Type, keywords: ['text', 'input', 'field', 'single', 'line'] },
      { type: 'email', label: 'Email', icon: Mail, keywords: ['email', 'mail', 'address', 'contact'] },
      { type: 'textarea', label: 'Text Area', icon: FileText, keywords: ['textarea', 'multiline', 'paragraph', 'long', 'text'] },
      { type: 'number', label: 'Number', icon: Hash, keywords: ['number', 'numeric', 'integer', 'decimal'] },
      { type: 'phone', label: 'Phone', icon: Phone, keywords: ['phone', 'telephone', 'mobile', 'contact'] },
      { type: 'url', label: 'URL', icon: Link, keywords: ['url', 'link', 'website', 'address'] },
      { type: 'password', label: 'Password', icon: Key, keywords: ['password', 'secret', 'secure', 'hidden'] },
      { type: 'hidden', label: 'Hidden Field', icon: EyeOff, keywords: ['hidden', 'invisible', 'data', 'tracking'] },
    ]
  },
  {
    id: 'choice',
    name: 'Choice',
    elements: [
      { type: 'select', label: 'Dropdown', icon: ChevronDown, keywords: ['dropdown', 'select', 'options', 'choose'] },
      { type: 'multiselect', label: 'Multi Select', icon: List, keywords: ['multiselect', 'multiple', 'options', 'choose'] },
      { type: 'checkbox', label: 'Checkbox', icon: CheckSquare, keywords: ['checkbox', 'check', 'multiple', 'toggle'] },
      { type: 'radio', label: 'Radio Group', icon: Radio, keywords: ['radio', 'single', 'choice', 'option'] },
      { type: 'checkbox-cards', label: 'Checkbox Cards', icon: SquareStack, keywords: ['checkbox', 'cards', 'multiple', 'visual', 'panel'] },
      { type: 'radio-cards', label: 'Radio Cards', icon: Layers2, keywords: ['radio', 'cards', 'single', 'visual', 'panel'] },
      { type: 'toggle', label: 'Toggle Switch', icon: CircleCheck, keywords: ['toggle', 'switch', 'boolean', 'yes'] },
      { type: 'rating', label: 'Rating Stars', icon: Star, keywords: ['rating', 'stars', 'feedback', 'score'] },
      { type: 'likert', label: 'Likert Scale', icon: BarChart, keywords: ['likert', 'scale', 'survey', 'agreement'] },
    ]
  },
  {
    id: 'advanced',
    name: 'Advanced',
    elements: [
      { type: 'date', label: 'Date Picker', icon: Calendar, keywords: ['date', 'calendar', 'picker', 'day'] },
      { type: 'time', label: 'Time Picker', icon: Clock, keywords: ['time', 'clock', 'hour', 'minute'] },
      { type: 'datetime', label: 'DateTime', icon: Calendar, keywords: ['datetime', 'timestamp', 'date', 'time'] },
      { type: 'daterange', label: 'Date Range', icon: Calendar, keywords: ['daterange', 'period', 'from', 'until'] },
      { type: 'slider', label: 'Slider Range', icon: Sliders, keywords: ['slider', 'range', 'numeric', 'scale'] },
      { type: 'color', label: 'Color Picker', icon: Palette, keywords: ['color', 'picker', 'palette', 'hex'] },
      { type: 'location', label: 'Location Map', icon: MapPin, keywords: ['location', 'map', 'address', 'coordinates'] },
      { type: 'signature', label: 'Signature Pad', icon: PenTool, keywords: ['signature', 'draw', 'sign', 'handwriting'] },
    ]
  },
  {
    id: 'upload',
    name: 'Upload',
    elements: [
      { type: 'file', label: 'File Upload', icon: Upload, keywords: ['file', 'upload', 'attachment', 'document'] },
      { type: 'image', label: 'Image Upload', icon: Image, keywords: ['image', 'photo', 'picture', 'upload'] },
      { type: 'multi-file', label: 'Multiple Files', icon: Upload, keywords: ['multiple', 'files', 'batch', 'upload'] },
      { type: 'drag-drop', label: 'Drag & Drop', icon: Upload, keywords: ['drag', 'drop', 'file', 'zone'] },
    ]
  },
  {
    id: 'containers',
    name: 'Containers',
    elements: [
      { type: 'columns', label: 'Columns', icon: Columns, keywords: ['columns', 'layout', 'grid', 'split'], allowChildren: true },
      { type: 'step-wizard', label: 'Step Wizard', icon: StepForward, keywords: ['wizard', 'steps', 'multi-step', 'sequential'], allowChildren: true },
      { type: 'tabs', label: 'Tabs', icon: FolderOpen, keywords: ['tabs', 'tabbed', 'navigation', 'switch'], allowChildren: true },
      { type: 'accordion', label: 'Accordion', icon: ChevronDownSquare, keywords: ['accordion', 'collapsible', 'expandable', 'toggle'], allowChildren: true },
      { type: 'section', label: 'Section', icon: Container, keywords: ['section', 'fieldset', 'group', 'wrapper'], allowChildren: true },
      { type: 'repeater', label: 'Repeater', icon: SquareStack, keywords: ['repeater', 'array', 'dynamic', 'multiple'], allowChildren: true },
      { type: 'conditional-group', label: 'Conditional Group', icon: Workflow, keywords: ['conditional', 'group', 'logic', 'show-hide'], allowChildren: true },
      { type: 'card', label: 'Card/Panel', icon: Box, keywords: ['card', 'panel', 'container', 'box'], allowChildren: true },
    ]
  },
  {
    id: 'layout',
    name: 'Layout',
    elements: [
      { type: 'heading', label: 'Heading', icon: Type, keywords: ['heading', 'title', 'header', 'text'] },
      { type: 'paragraph', label: 'Paragraph', icon: FileText, keywords: ['paragraph', 'text', 'description', 'content'] },
      { type: 'divider', label: 'Divider', icon: Code, keywords: ['divider', 'separator', 'line', 'break'] },
      { type: 'spacer', label: 'Spacer', icon: Code, keywords: ['spacer', 'gap', 'margin', 'padding'] },
      { type: 'page-break', label: 'Page Break', icon: Scissors, keywords: ['page', 'break', 'split', 'new'] },
      { type: 'html-block', label: 'HTML Block', icon: FileCode, keywords: ['html', 'code', 'custom', 'embed'] },
    ]
  },
  {
    id: 'integration',
    name: 'Integration',
    elements: [
      { type: 'payment', label: 'Payment (Stripe)', icon: CreditCard, keywords: ['payment', 'stripe', 'money', 'credit'] },
      { type: 'subscription', label: 'Subscription', icon: CreditCard, keywords: ['subscription', 'recurring', 'monthly', 'billing'] },
      { type: 'webhook', label: 'API Webhook', icon: Webhook, keywords: ['webhook', 'api', 'integration', 'external'] },
      { type: 'calculation', label: 'Calculation', icon: Calculator, keywords: ['calculation', 'formula', 'math', 'compute'] },
      { type: 'conditional', label: 'Conditional Logic', icon: Zap, keywords: ['conditional', 'logic', 'if', 'rules'] },
      { type: 'captcha', label: 'reCAPTCHA', icon: Shield, keywords: ['captcha', 'security', 'bot', 'protection'] },
    ]
  }
];

// Zoom levels for canvas
const ZOOM_LEVELS = [0.5, 0.75, 1, 1.25, 1.5];

// Device frame options
const DEVICE_FRAMES = {
  mobile: {
    width: 375,
    height: 667,
    name: 'iPhone 8'
  },
  tablet: {
    width: 768,
    height: 1024,
    name: 'iPad'
  },
  desktop: {
    width: 1200,
    height: 800,
    name: 'Desktop'
  }
};

// Export options
const EXPORT_OPTIONS = [
  { type: 'html', label: 'HTML Export', icon: FileCode },
  { type: 'json', label: 'JSON Export', icon: Database },
  { type: 'react', label: 'React Component', icon: Code },
  { type: 'pdf', label: 'PDF Export', icon: FileText },
];

// Form version history mock data
const VERSION_HISTORY = [
  { id: '1', name: 'Current', timestamp: Date.now(), isCurrent: true },
  { id: '2', name: 'Added validation rules', timestamp: Date.now() - 3600000 },
  { id: '3', name: 'Initial version', timestamp: Date.now() - 7200000 },
];





// Enhanced Floating Text Toolbar
const FloatingTextToolbar: React.FC<{
  position: { x: number; y: number };
  onClose: () => void;
  onFormat: (format: string) => void;
  selectedText: string;
}> = ({ position, onClose, onFormat, selectedText }) => {
  const toolbarRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (toolbarRef.current && !toolbarRef.current.contains(event.target as Node)) {
        onClose();
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [onClose]);

  return (
    <div
      ref={toolbarRef}
      className="floating-text-toolbar"
      style={{
        position: 'fixed',
        left: position.x,
        top: position.y - 50,
        zIndex: 1000,
      }}
    >
      <div className="toolbar-content">
        <button onClick={() => onFormat('bold')} title="Bold">
          <Bold size={16} />
        </button>
        <button onClick={() => onFormat('italic')} title="Italic">
          <Italic size={16} />
        </button>
        <button onClick={() => onFormat('underline')} title="Underline">
          <Underline size={16} />
        </button>
        <div className="toolbar-divider" />
        <button onClick={() => onFormat('left')} title="Align Left">
          <AlignLeft size={16} />
        </button>
        <button onClick={() => onFormat('center')} title="Align Center">
          <AlignCenter size={16} />
        </button>
        <button onClick={() => onFormat('right')} title="Align Right">
          <AlignRight size={16} />
        </button>
        <div className="toolbar-divider" />
        <button onClick={() => onFormat('color')} title="Text Color">
          <Palette size={16} />
        </button>
        <button onClick={() => onFormat('size')} title="Font Size">
          <Font size={16} />
        </button>
      </div>
    </div>
  );
};

// Enhanced Floating Properties Panel with more tabs
const FloatingPropertiesPanel: React.FC<{
  element: any;
  position: { x: number; y: number };
  onClose: () => void;
  onUpdate: (property: string, value: any) => void;
  onDelete: () => void;
}> = ({ element, position, onClose, onUpdate, onDelete }) => {
  const panelRef = useRef<HTMLDivElement>(null);
  const [activeTab, setActiveTab] = useState('general');

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (panelRef.current && !panelRef.current.contains(event.target as Node)) {
        onClose();
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [onClose]);

  const isFormField = !['heading', 'paragraph', 'divider', 'spacer', 'html-block', 'section', 'page-break'].includes(element.type);

  return (
    <div
      ref={panelRef}
      className="floating-properties-panel"
      style={{
        position: 'fixed',
        left: Math.min(position.x, window.innerWidth - 400),
        top: Math.min(position.y, window.innerHeight - 500),
        zIndex: 1000,
      }}
    >
      <div className="panel-header">
        <h3 className="panel-title">
          <element.icon size={16} />
          {element.label} Properties
        </h3>
        <button onClick={onClose} className="panel-close-btn">
          <X size={16} />
        </button>
      </div>

      <div className="panel-tabs">
        <button
          className={`panel-tab ${activeTab === 'general' ? 'panel-tab-active' : ''}`}
          onClick={() => setActiveTab('general')}
        >
          General
        </button>
        {isFormField && (
          <button
            className={`panel-tab ${activeTab === 'validation' ? 'panel-tab-active' : ''}`}
            onClick={() => setActiveTab('validation')}
          >
            Validation
          </button>
        )}
        <button
          className={`panel-tab ${activeTab === 'styling' ? 'panel-tab-active' : ''}`}
          onClick={() => setActiveTab('styling')}
        >
          Styling
        </button>
        <button
          className={`panel-tab ${activeTab === 'advanced' ? 'panel-tab-active' : ''}`}
          onClick={() => setActiveTab('advanced')}
        >
          Advanced
        </button>
      </div>

      <div className="panel-content">
        {activeTab === 'general' && (
          <div className="panel-section">
            {/* Quick Toggles */}
            {isFormField && (
              <div className="quick-toggles">
                <button
                  className={`quick-toggle ${element.properties?.required ? 'quick-toggle-active' : ''}`}
                  onClick={() => onUpdate('required', !element.properties?.required)}
                >
                  <CheckCircle size={16} />
                  Required
                </button>
                <button
                  className={`quick-toggle ${element.properties?.hidden ? 'quick-toggle-active' : ''}`}
                  onClick={() => onUpdate('hidden', !element.properties?.hidden)}
                >
                  <EyeOff size={16} />
                  Hidden
                </button>
                <button
                  className={`quick-toggle ${element.properties?.readonly ? 'quick-toggle-active' : ''}`}
                  onClick={() => onUpdate('readonly', !element.properties?.readonly)}
                >
                  <Lock size={16} />
                  Read-only
                </button>
              </div>
            )}

            {/* Standard Properties */}
            <div className="property-field">
              <label className="property-label">Field Name/ID</label>
              <input
                type="text"
                value={element.name || ''}
                onChange={(e) => onUpdate('name', e.target.value)}
                className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
                placeholder="field_name"
              />
            </div>

            <div className="property-field">
              <label className="property-label">Label</label>
              <input
                type="text"
                value={element.properties?.label || ''}
                onChange={(e) => onUpdate('label', e.target.value)}
                className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
              />
            </div>

            {/* Type-specific properties */}
            <PropertyPanelRegistry element={element} onUpdate={onUpdate} />
          </div>
        )}

        {activeTab === 'validation' && isFormField && (
          <div className="panel-section">
            <div className="property-field">
              <label className="property-label">Minimum Length</label>
              <input
                type="number"
                value={element.properties?.validation?.minLength || ''}
                onChange={(e) => onUpdate('validation', { 
                  ...element.properties?.validation, 
                  minLength: parseInt(e.target.value) || undefined 
                })}
                className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
              />
            </div>

            <div className="property-field">
              <label className="property-label">Maximum Length</label>
              <input
                type="number"
                value={element.properties?.validation?.maxLength || ''}
                onChange={(e) => onUpdate('validation', { 
                  ...element.properties?.validation, 
                  maxLength: parseInt(e.target.value) || undefined 
                })}
                className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
              />
            </div>

            {element.type === 'text' && (
              <div className="property-field">
                <label className="property-label">Pattern (Regex)</label>
                <input
                  type="text"
                  value={element.properties?.validation?.pattern || ''}
                  onChange={(e) => onUpdate('validation', { 
                    ...element.properties?.validation, 
                    pattern: e.target.value 
                  })}
                  className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
                  placeholder="^[A-Za-z]+$"
                />
              </div>
            )}

            <div className="property-field">
              <label className="property-label">Custom Error Message</label>
              <input
                type="text"
                value={element.properties?.validation?.message || ''}
                onChange={(e) => onUpdate('validation', { 
                  ...element.properties?.validation, 
                  message: e.target.value 
                })}
                className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
                placeholder="Please enter a valid value"
              />
            </div>
          </div>
        )}

        {activeTab === 'styling' && (
          <div className="panel-section">
            <div className="property-field">
              <label className="property-label">Width</label>
              <select
                value={element.properties?.width || 'full'}
                onChange={(e) => onUpdate('width', e.target.value)}
                className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
              >
                <option value="25">25%</option>
                <option value="33">33%</option>
                <option value="50">50%</option>
                <option value="66">66%</option>
                <option value="75">75%</option>
                <option value="full">100%</option>
              </select>
            </div>

            <div className="property-field">
              <label className="property-label">Margin</label>
              <input
                type="text"
                value={element.properties?.margin || ''}
                onChange={(e) => onUpdate('margin', e.target.value)}
                className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
                placeholder="10px 0"
              />
            </div>

            <div className="property-field">
              <label className="property-label">Background Color</label>
              <input
                type="color"
                value={element.properties?.backgroundColor || '#ffffff'}
                onChange={(e) => onUpdate('backgroundColor', e.target.value)}
                className="w-full h-10 px-1 py-1 border border-border rounded-md cursor-pointer"
              />
            </div>

            <div className="property-field">
              <label className="property-label">Border Style</label>
              <select
                value={element.properties?.borderStyle || 'solid'}
                onChange={(e) => onUpdate('borderStyle', e.target.value)}
                className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
              >
                <option value="none">None</option>
                <option value="solid">Solid</option>
                <option value="dashed">Dashed</option>
                <option value="dotted">Dotted</option>
              </select>
            </div>
          </div>
        )}

        {activeTab === 'advanced' && (
          <div className="panel-section">
            <div className="property-field">
              <label className="property-label">CSS Class</label>
              <input
                type="text"
                value={element.properties?.cssClass || ''}
                onChange={(e) => onUpdate('cssClass', e.target.value)}
                className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
                placeholder="custom-class"
              />
            </div>

            <div className="property-field">
              <label className="property-label">Conditional Logic</label>
              <select
                value={element.properties?.conditionalLogic || 'none'}
                onChange={(e) => onUpdate('conditionalLogic', e.target.value)}
                className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
              >
                <option value="none">No conditions</option>
                <option value="show">Show if...</option>
                <option value="hide">Hide if...</option>
                <option value="require">Require if...</option>
              </select>
            </div>

            {isFormField && (
              <div className="property-field">
                <label className="property-label">Default Value</label>
                <input
                  type="text"
                  value={element.properties?.defaultValue || ''}
                  onChange={(e) => onUpdate('defaultValue', e.target.value)}
                  className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
                />
              </div>
            )}

            <div className="property-field">
              <label className="property-label">Custom Attributes</label>
              <textarea
                value={element.properties?.customAttributes || ''}
                onChange={(e) => onUpdate('customAttributes', e.target.value)}
                className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
                rows={3}
                placeholder="data-custom=&quot;value&quot;&#10;aria-label=&quot;Custom label&quot;"
              />
            </div>
          </div>
        )}
      </div>

      <div className="panel-footer">
        <button
          onClick={onDelete}
          className="flex items-center gap-1 px-2 py-1 text-xs font-medium text-destructive-foreground bg-destructive hover:bg-destructive/90 rounded-md"
        >
          <Trash2 size={14} />
          Delete Element
        </button>
      </div>
    </div>
  );
};

// Enhanced Form Wrapper Settings Panel with Device Support
const FormWrapperSettingsPanel: React.FC<{
  position: { x: number; y: number };
  onClose: () => void;
  settings: any;
  onUpdate: (property: string, value: any) => void;
  devicePreview: 'desktop' | 'tablet' | 'mobile';
}> = ({ position, onClose, settings, onUpdate, devicePreview }) => {
  const panelRef = useRef<HTMLDivElement>(null);
  const [activeTab, setActiveTab] = useState('appearance');
  const [panelPosition, setPanelPosition] = useState(position);
  const [isDragging, setIsDragging] = useState(false);
  const [dragOffset, setDragOffset] = useState({ x: 0, y: 0 });

  // Visual spacing editor component
  const SpacingEditor: React.FC<{
    label: string;
    values: { top: number; right: number; bottom: number; left: number };
    onChange: (values: { top: number; right: number; bottom: number; left: number }) => void;
  }> = ({ label, values, onChange }) => {
    const handleChange = (side: keyof typeof values, value: number) => {
      onChange({ ...values, [side]: value });
    };

    return (
      <div className="spacing-editor">
        <label className="property-label">{label}</label>
        <div className="spacing-visual">
          {/* Top */}
          <input
            type="number"
            value={values.top}
            onChange={(e) => handleChange('top', parseInt(e.target.value) || 0)}
            className="spacing-input spacing-input-top"
            placeholder="0"
            min="0"
            max="200"
          />
          {/* Left and Right */}
          <div className="spacing-middle">
            <input
              type="number"
              value={values.left}
              onChange={(e) => handleChange('left', parseInt(e.target.value) || 0)}
              className="spacing-input spacing-input-left"
              placeholder="0"
              min="0"
              max="200"
            />
            <div className="spacing-preview">
              <div className="spacing-content">Content</div>
            </div>
            <input
              type="number"
              value={values.right}
              onChange={(e) => handleChange('right', parseInt(e.target.value) || 0)}
              className="spacing-input spacing-input-right"
              placeholder="0"
              min="0"
              max="200"
            />
          </div>
          {/* Bottom */}
          <input
            type="number"
            value={values.bottom}
            onChange={(e) => handleChange('bottom', parseInt(e.target.value) || 0)}
            className="spacing-input spacing-input-bottom"
            placeholder="0"
            min="0"
            max="200"
          />
        </div>
      </div>
    );
  };

  // Dragging functionality
  const handleMouseDown = (e: React.MouseEvent) => {
    if ((e.target as HTMLElement).closest('.panel-header')) {
      setIsDragging(true);
      setDragOffset({
        x: e.clientX - panelPosition.x,
        y: e.clientY - panelPosition.y
      });
    }
  };

  useEffect(() => {
    const handleMouseMove = (e: MouseEvent) => {
      if (isDragging) {
        setPanelPosition({
          x: Math.max(0, Math.min(e.clientX - dragOffset.x, window.innerWidth - 400)),
          y: Math.max(0, Math.min(e.clientY - dragOffset.y, window.innerHeight - 500))
        });
      }
    };

    const handleMouseUp = () => {
      setIsDragging(false);
    };

    if (isDragging) {
      document.addEventListener('mousemove', handleMouseMove);
      document.addEventListener('mouseup', handleMouseUp);
    }

    return () => {
      document.removeEventListener('mousemove', handleMouseMove);
      document.removeEventListener('mouseup', handleMouseUp);
    };
  }, [isDragging, dragOffset]);

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (panelRef.current && !panelRef.current.contains(event.target as Node)) {
        onClose();
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [onClose]);

  // Get device-specific settings
  const deviceSettings = settings[devicePreview] || settings;

  return (
    <div
      ref={panelRef}
      className={`floating-properties-panel form-wrapper-panel ${isDragging ? 'dragging' : ''}`}
      style={{
        position: 'fixed',
        left: panelPosition.x,
        top: panelPosition.y,
        zIndex: 1000,
        cursor: isDragging ? 'grabbing' : 'default',
      }}
      onMouseDown={handleMouseDown}
    >
      <div className="panel-header" style={{ cursor: 'grab' }}>
        <div className="panel-title-group">
          <h3 className="panel-title">
            <Container size={16} />
            Form Wrapper Settings
          </h3>
          <div className="device-indicator">
            <span className={`device-badge device-badge-${devicePreview}`}>
              {devicePreview === 'desktop' && <Monitor size={14} />}
              {devicePreview === 'tablet' && <Tablet size={14} />}
              {devicePreview === 'mobile' && <Smartphone size={14} />}
              {devicePreview.charAt(0).toUpperCase() + devicePreview.slice(1)}
            </span>
          </div>
        </div>
        <button onClick={onClose} className="panel-close-btn">
          <X size={16} />
        </button>
      </div>

      <div className="panel-tabs">
        <button
          className={`panel-tab ${activeTab === 'appearance' ? 'panel-tab-active' : ''}`}
          onClick={() => setActiveTab('appearance')}
        >
          Appearance
        </button>
        <button
          className={`panel-tab ${activeTab === 'spacing' ? 'panel-tab-active' : ''}`}
          onClick={() => setActiveTab('spacing')}
        >
          Spacing
        </button>
      </div>

      <div className="panel-content">
        {activeTab === 'appearance' && (
          <div className="panel-section">
            <div className="property-field">
              <label className="property-label">Background Type</label>
              <select
                value={settings.backgroundType}
                onChange={(e) => onUpdate('backgroundType', e.target.value)}
                className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
              >
                <option value="none">None</option>
                <option value="color">Solid Color</option>
                <option value="image">Background Image</option>
              </select>
            </div>

            {settings.backgroundType === 'color' && (
              <>
                <div className="property-field">
                  <label className="property-label">Background Color</label>
                  <input
                    type="color"
                    value={settings.backgroundColor}
                    onChange={(e) => onUpdate('backgroundColor', e.target.value)}
                    className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
                  />
                </div>
                
                <div className="property-field">
                  <label className="property-label">Opacity</label>
                  <input
                    type="range"
                    min="0"
                    max="1"
                    step="0.1"
                    value={settings.backgroundOpacity}
                    onChange={(e) => onUpdate('backgroundOpacity', parseFloat(e.target.value))}
                    className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
                  />
                  <span className="text-sm text-gray-500">{Math.round(settings.backgroundOpacity * 100)}%</span>
                </div>
              </>
            )}

            {settings.backgroundType === 'image' && (
              <div className="property-field">
                <label className="property-label">Image URL</label>
                <input
                  type="url"
                  value={settings.backgroundImage}
                  onChange={(e) => onUpdate('backgroundImage', e.target.value)}
                  className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
                  placeholder="https://example.com/image.jpg"
                />
              </div>
            )}
          </div>
        )}

        {activeTab === 'spacing' && (
          <div className="panel-section">
            <SpacingEditor
              label="Padding"
              values={deviceSettings.padding || { top: 0, right: 0, bottom: 0, left: 0 }}
              onChange={(values) => onUpdate(`${devicePreview}.padding`, values)}
            />
            
            <SpacingEditor
              label="Margin"
              values={deviceSettings.margin || { top: 0, right: 0, bottom: 0, left: 0 }}
              onChange={(values) => onUpdate(`${devicePreview}.margin`, values)}
            />
          </div>
        )}
      </div>
    </div>
  );
};

// Enhanced Entries Tab with filters and search
const EntriesTabContent: React.FC = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [dateFilter, setDateFilter] = useState('all');

  return (
    <div className="flex-1 overflow-auto">
      <div className="p-4">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-semibold">Form Entries</h3>
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-500">247 total</span>
            <button className="flex items-center gap-1 px-2 py-1 text-xs font-medium border border-border text-foreground hover:bg-muted rounded-md">
              <Download size={14} />
              Export
            </button>
          </div>
        </div>

        {/* Search and Filters */}
        <div className="space-y-3 mb-4">
          <div className="relative">
            <Search size={16} className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search entries..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-3 py-2 border border-border rounded-md text-sm bg-background"
            />
          </div>
          
          <div className="flex gap-2">
            <select 
              value={statusFilter} 
              onChange={(e) => setStatusFilter(e.target.value)}
              className="flex-1 px-3 py-2 border border-border rounded-md text-sm bg-background"
            >
              <option value="all">All Status</option>
              <option value="new">New</option>
              <option value="read">Read</option>
              <option value="flagged">Flagged</option>
            </select>
            
            <select 
              value={dateFilter} 
              onChange={(e) => setDateFilter(e.target.value)}
              className="flex-1 px-3 py-2 border border-border rounded-md text-sm bg-background"
            >
              <option value="all">All Time</option>
              <option value="today">Today</option>
              <option value="week">This Week</option>
              <option value="month">This Month</option>
            </select>
          </div>
        </div>

        {/* Entry List */}
        <div className="space-y-2">
          {[1, 2, 3, 4, 5, 6, 7, 8].map(i => (
            <div key={i} className="entry-item">
              <div className="flex items-center justify-between mb-1">
                <span className="font-medium">Entry #{247 - i}</span>
                <div className="flex items-center gap-2">
                  <span className={`entry-status ${i <= 2 ? 'entry-status-new' : i === 3 ? 'entry-status-flagged' : 'entry-status-read'}`}>
                    {i <= 2 ? 'New' : i === 3 ? 'Flagged' : 'Read'}
                  </span>
                  <button className="flex items-center justify-center w-6 h-6 text-muted-foreground hover:text-foreground hover:bg-muted rounded">
                    <Eye size={12} />
                  </button>
                </div>
              </div>
              <p className="text-sm text-gray-500 mb-1">
                {i === 1 ? 'john.doe@example.com' : i === 2 ? 'jane.smith@example.com' : `user${i}@example.com`}
              </p>
              <p className="text-xs text-gray-400">
                {i <= 2 ? `${i * 15} minutes ago` : i === 3 ? '2 hours ago' : `${i} days ago`}
              </p>
            </div>
          ))}
        </div>

        {/* Pagination */}
        <div className="flex items-center justify-between mt-4 pt-4 border-t">
          <span className="text-sm text-gray-500">Showing 1-8 of 247</span>
          <div className="flex gap-1">
            <button className="px-2 py-1 text-xs font-medium border border-border text-foreground hover:bg-muted rounded">Previous</button>
            <button className="px-2 py-1 text-xs font-medium border border-border text-foreground hover:bg-muted rounded">Next</button>
          </div>
        </div>
      </div>
    </div>
  );
};

// Loading fallback for lazy-loaded tabs
const TabLoadingFallback = () => (
  <div className="flex-1 flex items-center justify-center p-8">
    <div className="text-center">
      <RefreshCw className="w-8 h-8 mx-auto text-muted-foreground/50 animate-spin mb-2" />
      <p className="text-sm text-muted-foreground">Loading...</p>
    </div>
  </div>
);

// Style Tab with GlobalStylesPanel integration
const StyleTabContent: React.FC = () => {
  return (
    <div className="flex-1 overflow-auto">
      <GlobalStylesPanel />
    </div>
  );
};

// Integrations Tab with webhook and third-party connections
const IntegrationsTabContent: React.FC = () => {
  const [integrations, setIntegrations] = useState([
    { id: 'email', name: 'Email Notifications', enabled: true, configured: true },
    { id: 'webhook', name: 'Webhook', enabled: false, configured: false },
    { id: 'zapier', name: 'Zapier', enabled: false, configured: false },
    { id: 'mailchimp', name: 'Mailchimp', enabled: true, configured: true },
    { id: 'slack', name: 'Slack', enabled: false, configured: false },
  ]);

  return (
    <div className="flex-1 overflow-auto">
      <div className="p-4">
        <h3 className="font-semibold mb-4">Integrations</h3>

        <p className="text-sm text-gray-500 mb-4">
          Connect your form to external services and automate workflows.
        </p>

        <div className="space-y-3">
          {integrations.map(integration => (
            <div key={integration.id} className="integration-item">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div className={`integration-icon integration-icon-${integration.id}`}>
                    {integration.id === 'email' && <Mail size={16} />}
                    {integration.id === 'webhook' && <Webhook size={16} />}
                    {integration.id === 'zapier' && <Zap size={16} />}
                    {integration.id === 'mailchimp' && <Mail size={16} />}
                    {integration.id === 'slack' && <Bell size={16} />}
                  </div>
                  <div>
                    <div className="font-medium">{integration.name}</div>
                    <div className="text-sm text-gray-500">
                      {integration.configured ? 'Configured' : 'Not configured'}
                    </div>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={integration.enabled}
                    onChange={(e) => {
                      setIntegrations(integrations.map(i =>
                        i.id === integration.id ? { ...i, enabled: e.target.checked } : i
                      ));
                    }}
                    disabled={!integration.configured}
                  />
                  <button className="px-2 py-1 text-xs font-medium border border-border text-foreground hover:bg-muted rounded">
                    {integration.configured ? 'Configure' : 'Setup'}
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* Webhook Configuration */}
        <div className="mt-6 p-4 border border-gray-200 rounded-lg">
          <h4 className="font-medium mb-3">Webhook Configuration</h4>
          <div className="space-y-3">
            <div className="property-field">
              <label className="property-label">Webhook URL</label>
              <input
                type="url"
                className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
                placeholder="https://your-webhook-url.com/endpoint"
              />
            </div>
            <div className="property-field">
              <label className="property-label">HTTP Method</label>
              <select className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background">
                <option>POST</option>
                <option>PUT</option>
                <option>PATCH</option>
              </select>
            </div>
            <div className="property-field">
              <label className="property-label">Custom Headers</label>
              <textarea
                className="w-full px-3 py-2 border border-border rounded-md text-sm bg-background"
                rows={3}
                placeholder={`Authorization: Bearer your-token
Content-Type: application/json`}
              />
            </div>
            <button className="flex items-center gap-1 px-2 py-1 text-xs font-medium border border-border text-foreground hover:bg-muted rounded-md">
              <Play size={14} />
              Test Webhook
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

// Draggable Resize Bar Component
const DraggableResizeBar: React.FC<{
  position: 'right' | 'left';
  onResize: (width: string) => void;
  currentWidth: string;
  showInput: boolean;
  onInputVisibilityChange: (show: boolean) => void;
  maxWidth?: string;
}> = ({ position, onResize, currentWidth, showInput, onInputVisibilityChange, maxWidth }) => {
  const [isDragging, setIsDragging] = useState(false);
  const [inputValue, setInputValue] = useState(currentWidth);
  const [inputFocused, setInputFocused] = useState(false);

  const handleMouseDown = (e: React.MouseEvent) => {
    setIsDragging(true);
    e.preventDefault();
  };

  const handleMouseMove = useCallback((e: MouseEvent) => {
    if (isDragging) {
      const wrapper = document.querySelector('.form-wrapper') as HTMLElement;
      if (wrapper) {
        const rect = wrapper.getBoundingClientRect();
        const canvasRect = wrapper.closest('.canvas-responsive')?.getBoundingClientRect();
        if (rect && canvasRect) {
          // Calculate new width based on mouse position relative to canvas
          const canvasMaxWidth = canvasRect.width * 0.95; // Allow up to 95% of canvas width
          const deviceMaxWidth = maxWidth ? parseInt(maxWidth) : Infinity; // Device constraint
          const effectiveMaxWidth = Math.min(canvasMaxWidth, deviceMaxWidth);
          const minWidth = 200; // Minimum width
          const newWidth = Math.min(effectiveMaxWidth, Math.max(minWidth, e.clientX - rect.left));
          onResize(`${Math.round(newWidth)}px`);
        }
      }
    }
  }, [isDragging, onResize]);

  const handleMouseUp = useCallback(() => {
    setIsDragging(false);
  }, []);

  useEffect(() => {
    if (isDragging) {
      document.addEventListener('mousemove', handleMouseMove);
      document.addEventListener('mouseup', handleMouseUp);
      return () => {
        document.removeEventListener('mousemove', handleMouseMove);
        document.removeEventListener('mouseup', handleMouseUp);
      };
    }
  }, [isDragging, handleMouseMove, handleMouseUp]);

  return (
    <div 
      className={`resize-bar resize-bar-${position}`}
      onMouseDown={handleMouseDown}
      onMouseEnter={() => !inputFocused && onInputVisibilityChange(true)}
      onMouseLeave={() => !inputFocused && onInputVisibilityChange(false)}
    >
      <div className="resize-handle" />
      {(showInput || inputFocused) && (
        <div 
          className="resize-input-popup"
          onMouseEnter={() => onInputVisibilityChange(true)}
          onMouseLeave={() => !inputFocused && onInputVisibilityChange(false)}
        >
          <input
            type="text"
            value={inputValue}
            onChange={(e) => setInputValue(e.target.value)}
            onFocus={() => setInputFocused(true)}
            onBlur={() => {
              setInputFocused(false);
              onResize(inputValue);
              onInputVisibilityChange(false);
            }}
            onKeyDown={(e) => {
              if (e.key === 'Enter') {
                onResize(inputValue);
                e.currentTarget.blur();
              }
            }}
            className="resize-input"
            placeholder="100% or 400px"
          />
        </div>
      )}
    </div>
  );
};


// Floating Elements Panel Component
const FloatingElementsPanel: React.FC<{
  isVisible: boolean;
  position: { x: number; y: number };
  isCollapsed: boolean;
  onClose: () => void;
  onPositionChange: (position: { x: number; y: number }) => void;
  onCollapse: (collapsed: boolean) => void;
  onElementClick: (elementId: string) => void;
}> = ({ isVisible, position, isCollapsed, onClose, onPositionChange, onCollapse, onElementClick }) => {
  const { items, order, reorderElements, removeElement, addElement } = useElementsStore();
  const orderedElements = Array.isArray(order) ? order.map(id => items[id]).filter(Boolean) : [];
  const [isDragging, setIsDragging] = useState(false);
  const [dragOffset, setDragOffset] = useState({ x: 0, y: 0 });

  const handleMouseDown = (e: React.MouseEvent) => {
    if ((e.target as HTMLElement).closest('.floating-panel-header')) {
      setIsDragging(true);
      const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
      setDragOffset({
        x: e.clientX - rect.left,
        y: e.clientY - rect.top
      });
    }
  };

  const handleMouseMove = useCallback((e: MouseEvent) => {
    if (isDragging) {
      onPositionChange({
        x: e.clientX - dragOffset.x,
        y: e.clientY - dragOffset.y
      });
    }
  }, [isDragging, dragOffset, onPositionChange]);

  const handleMouseUp = useCallback(() => {
    setIsDragging(false);
  }, []);

  useEffect(() => {
    if (isDragging) {
      document.addEventListener('mousemove', handleMouseMove);
      document.addEventListener('mouseup', handleMouseUp);
      return () => {
        document.removeEventListener('mousemove', handleMouseMove);
        document.removeEventListener('mouseup', handleMouseUp);
      };
    }
  }, [isDragging, handleMouseMove, handleMouseUp]);

  const handleReorder = (dragIndex: number, hoverIndex: number) => {
    const newOrder = [...order];
    const draggedId = newOrder[dragIndex];
    newOrder.splice(dragIndex, 1);
    newOrder.splice(hoverIndex, 0, draggedId);
    reorderElements(newOrder);
  };

  const handleDeleteElement = (elementId: string) => {
    removeElement(elementId);
  };

  const handleDuplicateElement = (elementId: string) => {
    const element = items[elementId];
    if (element) {
      const newElement = {
        ...element,
        id: uuidv4(),
        name: `${element.name}_copy`
      };
      addElement(newElement);
    }
  };

  const handleContextMenu = (e: React.MouseEvent, elementId: string) => {
    e.preventDefault();
    // TODO: Implement context menu
    console.log('Context menu for element:', elementId);
  };

  if (!isVisible) return null;

  return (
    <div 
      className={`floating-elements-panel ${isCollapsed ? 'floating-panel-collapsed' : ''}`}
      style={{
        left: position.x,
        top: position.y,
        cursor: isDragging ? 'grabbing' : 'default'
      }}
      onMouseDown={handleMouseDown}
    >
      <div className="floating-panel-header">
        <div className="floating-panel-title">
          <Layers size={16} />
          <span>Elements ({orderedElements.length})</span>
        </div>
        <div className="floating-panel-controls">
          <button
            className="floating-panel-btn"
            onClick={() => onCollapse(!isCollapsed)}
            title={isCollapsed ? 'Expand' : 'Collapse'}
          >
            {isCollapsed ? <ChevronDown size={14} /> : <ChevronUp size={14} />}
          </button>
          <button
            className="floating-panel-btn"
            onClick={onClose}
            title="Close"
          >
            <X size={14} />
          </button>
        </div>
      </div>

      {!isCollapsed && (
        <div className="floating-panel-content">
          {orderedElements.length === 0 ? (
            <div className="floating-panel-empty">
              <Layers size={24} className="opacity-50" />
              <p className="text-xs text-gray-500">No elements yet</p>
            </div>
          ) : (
            <div className="floating-elements-list">
              {orderedElements.map((element, index) => (
                <div 
                  key={element.id} 
                  className="floating-element-item"
                  draggable
                  onClick={() => onElementClick(element.id)}
                  onContextMenu={(e) => handleContextMenu(e, element.id)}
                  onDragStart={(e) => {
                    e.dataTransfer.setData('text/plain', index.toString());
                  }}
                  onDragOver={(e) => e.preventDefault()}
                  onDrop={(e) => {
                    e.preventDefault();
                    const dragIndex = parseInt(e.dataTransfer.getData('text/plain'));
                    handleReorder(dragIndex, index);
                  }}
                >
                  <div className="floating-element-drag">
                    <Move size={12} />
                  </div>
                  
                  <div className="floating-element-info">
                    <div className="floating-element-label">
                      {element.properties?.label || element.label}
                    </div>
                    <div className="floating-element-type">
                      {element.type.replace('_', ' ')} {element.properties?.required && '•'}
                    </div>
                  </div>

                  <div className="floating-element-actions">
                    <button
                      className="floating-element-btn"
                      onClick={(e) => {
                        e.stopPropagation();
                        handleDuplicateElement(element.id);
                      }}
                      title="Duplicate"
                    >
                      <Copy size={12} />
                    </button>
                    <button
                      className="floating-element-btn"
                      onClick={(e) => {
                        e.stopPropagation();
                        handleDeleteElement(element.id);
                      }}
                      title="Delete"
                    >
                      <Trash2 size={12} />
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
};

// Main FormBuilderComplete Component
interface FormBuilderCompleteProps {}

const FormBuilderCompleteInner: React.FC<FormBuilderCompleteProps> = () => {
  const { items, order, addElement, removeElement, updateElement, reorderElements } = useElementsStore();
  const { selectedElements, setSelectedElements } = useBuilderStore();

  // Collapse WP admin sidebar on mount for better form builder UX
  useEffect(() => {
    document.body.classList.add('sticky-menu', 'folded');
  }, []);

  // Layout state
  const [activeTab, setActiveTab] = useState('canvas');
  const [devicePreview, setDevicePreview] = useState<'desktop' | 'tablet' | 'mobile'>('desktop');
  const [formTitle, setFormTitle] = useState('Untitled Form');
  const [autoSaveStatus, setAutoSaveStatus] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
  const [currentFormId, setCurrentFormId] = useState('1');
  
  // Toast notification state
  const [toasts, setToasts] = useState<Array<{
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    message: string;
    visible: boolean;
    hiding?: boolean;
  }>>([]);
  
  // Toast management functions
  const showToast = useCallback((type: 'success' | 'error' | 'warning' | 'info', message: string, duration = 5000) => {
    const id = Math.random().toString(36).substr(2, 9);
    const newToast = { id, type, message, visible: false };
    
    setToasts(prev => [...prev, newToast]);
    
    // Show toast after a brief delay for animation
    setTimeout(() => {
      setToasts(prev => prev.map(toast => 
        toast.id === id ? { ...toast, visible: true } : toast
      ));
    }, 100);
    
    // Auto-hide toast after duration (unless it's an error)
    if (type !== 'error') {
      setTimeout(() => {
        hideToast(id);
      }, duration);
    }
  }, []);
  
  const hideToast = useCallback((id: string) => {
    // First add the hiding class for left-to-right exit animation
    setToasts(prev => prev.map(toast => 
      toast.id === id ? { ...toast, hiding: true } : toast
    ));
    
    // Remove from array after animation completes (400ms for transform)
    setTimeout(() => {
      setToasts(prev => prev.filter(toast => toast.id !== id));
    }, 400);
  }, []);
  
  // Canvas state
  const [zoom, setZoom] = useState(1);
  const [showGrid, setShowGrid] = useState(false);
  const [showDeviceFrame, setShowDeviceFrame] = useState(true);
  
  // Bottom tray state
  const [trayHeight, setTrayHeight] = useState(250);
  const [isTrayCollapsed, setIsTrayCollapsed] = useState(false);
  const [activeTrayCategory, setActiveTrayCategory] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');
  // Layout mode state for hybrid grid/horizontal switching
  const [isHorizontalLayout, setIsHorizontalLayout] = useState(false);
  const [elementsAreaHeight, setElementsAreaHeight] = useState(150);
  
  // Canvas bottom padding state for footer height
  const [canvasBottomPadding, setCanvasBottomPadding] = useState(250);
  
  // Horizontal scroll state for scroll chevrons
  const [trayScrollLeft, setTrayScrollLeft] = useState(0);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(false);
  
  // Hover overlay state for clipping-free hover effects
  const [hoveredElement, setHoveredElement] = useState<{
    element: any;
    rect: DOMRect;
    containerRect: DOMRect;
  } | null>(null);
  const hoverOverlayRef = useRef<HTMLDivElement>(null);

  // Handle tray height changes and determine layout mode
  const handleTrayHeightChange = useCallback((newHeight: number) => {
    // Update tray height state
    setTrayHeight(newHeight);
    
    const headerHeight = 50; // Height of tray header
    const resizeHandleHeight = 16; // Height of resize handle
    const borderSpacing = 8; // Account for borders and spacing
    const availableHeight = newHeight - headerHeight - resizeHandleHeight - borderSpacing;
    
    // Switch to horizontal layout if available height is too small for multiple rows
    const shouldUseHorizontalLayout = availableHeight < 160;
    setIsHorizontalLayout(shouldUseHorizontalLayout);
    
    if (shouldUseHorizontalLayout) {
      // In horizontal layout: constrain height and use horizontal scrolling
      const minElementsHeight = Math.max(100, availableHeight);
      setElementsAreaHeight(minElementsHeight);
    } else {
      // In grid layout: use available height but allow vertical scrolling if needed
      const gridElementsHeight = Math.max(160, availableHeight); // Ensure minimum for grid
      setElementsAreaHeight(gridElementsHeight);
    }
    
    // Update canvas bottom padding: footer height + additional 100px buffer
    const paddingBuffer = 100;
    const effectiveFooterHeight = isTrayCollapsed ? 40 : newHeight; // Use collapsed height or full height
    setCanvasBottomPadding(effectiveFooterHeight + paddingBuffer);
  }, [isTrayCollapsed]);

  // Initialize layout mode on component mount
  useEffect(() => {
    handleTrayHeightChange(220); // Use default height
  }, [handleTrayHeightChange]);

  // Update canvas padding when tray collapse state changes
  useEffect(() => {
    const paddingBuffer = 100;
    const effectiveFooterHeight = isTrayCollapsed ? 40 : trayHeight;
    setCanvasBottomPadding(effectiveFooterHeight + paddingBuffer);
  }, [isTrayCollapsed, trayHeight]);

  
  // Canvas width state
  const [customCanvasWidth, setCustomCanvasWidth] = useState<string>('');
  const [useCustomWidth, setUseCustomWidth] = useState(false);
  
  // Floating elements panel state
  const [showElementsPanel, setShowElementsPanel] = useState(false);
  const [elementsPanelPosition, setElementsPanelPosition] = useState({ x: 100, y: 100 });
  const [isElementsPanelCollapsed, setIsElementsPanelCollapsed] = useState(false);
  
  // Form wrapper settings with device-specific support
  const [formWrapperSettings, setFormWrapperSettings] = useState({
    backgroundType: 'color' as 'none' | 'color' | 'image',
    backgroundColor: '#ffffff',
    backgroundImage: '',
    backgroundOpacity: 1,
    desktop: {
      padding: { top: 40, right: 40, bottom: 40, left: 40 },
      margin: { top: 20, right: 20, bottom: 20, left: 20 }
    },
    tablet: {
      padding: { top: 30, right: 30, bottom: 30, left: 30 },
      margin: { top: 15, right: 15, bottom: 15, left: 15 }
    },
    mobile: {
      padding: { top: 20, right: 20, bottom: 20, left: 20 },
      margin: { top: 10, right: 10, bottom: 10, left: 10 }
    }
  });
  
  // Breadcrumb and selection state
  const [selectedElementPath, setSelectedElementPath] = useState<string[]>([]);
  const [selectedElementId, setSelectedElementId] = useState<string | null>(null);
  const [isFormWrapperSelected, setIsFormWrapperSelected] = useState(false);
  
  // Form wrapper resize state
  const [isResizingFormWrapper, setIsResizingFormWrapper] = useState(false);
  const [showFormWidthInput, setShowFormWidthInput] = useState(false);
  const [formWrapperWidth, setFormWrapperWidth] = useState<string>('100%');
  
  // Calculate device-specific max width for form wrapper
  const getDeviceMaxWidth = useCallback((device: string) => {
    const deviceInfo = DEVICE_FRAMES[device as keyof typeof DEVICE_FRAMES];
    if (!deviceInfo) return '100%';
    
    // Account for device frame padding/insets (based on device-screen-overlay insets)
    const devicePadding = {
      mobile: 70, // 15px left + 15px right + 40px padding from inset: 60px 15px 60px + padding
      tablet: 90, // 25px left + 25px right + 40px padding from inset: 40px 25px 50px + padding  
      desktop: 30 // 15px left + 15px right from inset: 15px
    };
    
    const padding = devicePadding[device as keyof typeof devicePadding] || 0;
    const maxWidth = deviceInfo.width - padding;
    
    return `${maxWidth}px`;
  }, []);
  
  // Update form wrapper width when device changes to respect device constraints
  useEffect(() => {
    if (showDeviceFrame && devicePreview !== 'desktop') {
      const maxWidth = getDeviceMaxWidth(devicePreview);
      const currentWidthPx = parseInt(formWrapperWidth) || 0;
      const maxWidthPx = parseInt(maxWidth) || Infinity;
      
      // Always set to device max width when switching devices with frame enabled
      // This ensures proper sizing for both larger and smaller devices
      if (maxWidthPx > 0 && currentWidthPx !== maxWidthPx) {
        setFormWrapperWidth(maxWidth);
      }
    } else if (!showDeviceFrame || devicePreview === 'desktop') {
      // When device frame is off or on desktop, allow full width
      const currentWidthPx = parseInt(formWrapperWidth) || 0;
      if (currentWidthPx < 600) { // If currently constrained to a small device width
        setFormWrapperWidth('100%'); // Reset to full width
      }
    }
  }, [devicePreview, showDeviceFrame, getDeviceMaxWidth]);
  const trayScrollRef = useRef<HTMLDivElement>(null);

  // Continuous scrolling state
  const scrollIntervalRef = useRef<NodeJS.Timeout | null>(null);
  const [isScrolling, setIsScrolling] = useState<'left' | 'right' | null>(null);

  // Momentum scroll refs for smooth mousewheel scrolling
  const targetScrollRef = useRef(0);
  const momentumFrameRef = useRef<number | null>(null);
  
  // Drag and drop state
  const [isDragging, setIsDragging] = useState(false);
  const [draggedElement, setDraggedElement] = useState<any>(null);
  const [dragOverIndex, setDragOverIndex] = useState<number | null>(null);
  const [multiSelectElements, setMultiSelectElements] = useState<string[]>([]);
  
  // Inline editing state
  const [contextMenu, setContextMenu] = useState<{ x: number; y: number } | null>(null);
  const [floatingPanel, setFloatingPanel] = useState<{ element: any; position: { x: number; y: number } } | null>(null);
  const [selectedTextInfo, setSelectedTextInfo] = useState<{ text: string; position: { x: number; y: number } } | null>(null);
  
  // Panel states
  const [showVersionHistory, setShowVersionHistory] = useState(false);
  const [showSharePanel, setShowSharePanel] = useState(false);
  const [showExportPanel, setShowExportPanel] = useState(false);
  const [showAnalytics, setShowAnalytics] = useState(false);
  
  // Undo/Redo system
  const [history, setHistory] = useState<any[]>([]);
  const [historyIndex, setHistoryIndex] = useState(-1);
  
  // Mobile detection
  const [isMobile, setIsMobile] = useState(false);
  
  // Device selector dropdown state
  const [isDeviceDropdownOpen, setIsDeviceDropdownOpen] = useState(false);
  const deviceDropdownRef = useRef<HTMLDivElement>(null);

  const canvasRef = useRef<HTMLDivElement>(null);

  // Handle device dropdown outside clicks
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (deviceDropdownRef.current && !deviceDropdownRef.current.contains(event.target as Node)) {
        setIsDeviceDropdownOpen(false);
      }
    };

    if (isDeviceDropdownOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isDeviceDropdownOpen]);

  // Get ordered elements
  const orderedElements = Array.isArray(order) ? order.map(id => items[id]).filter(Boolean) : [];
  const selectedElement = selectedElements[0] ? items[selectedElements[0]] : null;

  // Device selector helper functions
  const getDeviceIcon = (device: string) => {
    switch (device) {
      case 'desktop': return <Monitor size={18} />;
      case 'tablet': return <Tablet size={18} />;
      case 'mobile': return <Smartphone size={18} />;
      default: return <Monitor size={18} />;
    }
  };

  const getDeviceLabel = (device: string) => {
    switch (device) {
      case 'desktop': return 'Desktop';
      case 'tablet': return 'Tablet';
      case 'mobile': return 'Mobile';
      default: return 'Desktop';
    }
  };

  const deviceOptions = [
    { value: 'desktop', label: 'Desktop', icon: <Monitor size={18} /> },
    { value: 'tablet', label: 'Tablet', icon: <Tablet size={18} /> },
    { value: 'mobile', label: 'Mobile', icon: <Smartphone size={18} /> }
  ];

  // Filter elements based on search query
  const getFilteredElements = useCallback(() => {
    if (!searchQuery.trim()) {
      if (activeTrayCategory === 'all') {
        // Return all elements from all categories (excluding the 'all' category itself)
        return ELEMENT_CATEGORIES.filter(cat => cat.id !== 'all').flatMap(cat => cat.elements);
      }
      return ELEMENT_CATEGORIES.find(cat => cat.id === activeTrayCategory)?.elements || [];
    }

    const query = searchQuery.toLowerCase().trim();
    const stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'can', 'this', 'that', 'these', 'those', 'it', 'its', 'he', 'she', 'they', 'them', 'their', 'his', 'her'];
    
    const queryWords = query.split(' ').filter(word => word.length > 2 && !stopWords.includes(word));
    
    const allElements = ELEMENT_CATEGORIES.filter(cat => cat.id !== 'all').flatMap(cat => cat.elements);
    
    return allElements.filter(element => {
      const searchText = [
        element.label.toLowerCase(),
        element.type.toLowerCase(),
        ...(element.keywords || [])
      ].join(' ');
      
      return queryWords.some(word => searchText.includes(word)) || 
             element.label.toLowerCase().includes(query);
    });
  }, [searchQuery, activeTrayCategory]);

  // Removed horizontal scroll button updates - now using vertical grid layout
  // Horizontal scroll functions for tray elements (restored for hybrid layout)
  const updateScrollButtons = useCallback(() => {
    if (trayScrollRef.current) {
      const { scrollLeft, scrollWidth, clientWidth } = trayScrollRef.current;
      // Low threshold for left button (show almost immediately when scrolled)
      // Small threshold for right to avoid edge flickering
      setCanScrollLeft(scrollLeft > 5);
      setCanScrollRight(scrollLeft < scrollWidth - clientWidth - 10);
      setTrayScrollLeft(scrollLeft);
    }
  }, []);

  const scrollTray = useCallback((direction: 'left' | 'right') => {
    if (trayScrollRef.current) {
      const scrollAmount = 200;
      const newScrollLeft = direction === 'left'
        ? trayScrollRef.current.scrollLeft - scrollAmount
        : trayScrollRef.current.scrollLeft + scrollAmount;

      trayScrollRef.current.scrollTo({
        left: newScrollLeft,
        behavior: 'smooth'
      });
    }
  }, []);

  // Momentum-based scroll animation loop
  const animateMomentumScroll = useCallback(() => {
    if (!trayScrollRef.current) {
      momentumFrameRef.current = null;
      return;
    }

    const current = trayScrollRef.current.scrollLeft;
    const target = targetScrollRef.current;
    const diff = target - current;

    // Lerp factor: 0.2 gives a nice balance of smooth and responsive
    const lerp = 0.2;

    if (Math.abs(diff) > 0.5) {
      trayScrollRef.current.scrollLeft = current + diff * lerp;
      momentumFrameRef.current = requestAnimationFrame(animateMomentumScroll);
    } else {
      // Snap to target when close enough
      trayScrollRef.current.scrollLeft = target;
      momentumFrameRef.current = null;
    }
  }, []);

  // Mousewheel horizontal scroll handler with momentum (native event for passive: false support)
  const handleTrayWheelNative = useCallback((e: WheelEvent) => {
    if (!trayScrollRef.current) return;

    const { scrollLeft, scrollWidth, clientWidth } = trayScrollRef.current;
    const canScroll = scrollWidth > clientWidth;

    if (canScroll) {
      const delta = e.deltaY || e.deltaX;
      const maxScroll = scrollWidth - clientWidth;

      // Initialize target from current position on first interaction
      if (momentumFrameRef.current === null) {
        targetScrollRef.current = scrollLeft;
      }

      // Accumulate delta to target (no speed cap!)
      targetScrollRef.current = Math.max(0, Math.min(maxScroll, targetScrollRef.current + delta));

      // Start animation loop if not already running
      if (!momentumFrameRef.current) {
        momentumFrameRef.current = requestAnimationFrame(animateMomentumScroll);
      }

      // Prevent page scroll - works because we use passive: false
      e.preventDefault();
    }
  }, [animateMomentumScroll]);

  // Attach wheel listener with passive: false to allow preventDefault
  useEffect(() => {
    const el = trayScrollRef.current;
    if (!el || !isHorizontalLayout) return;

    el.addEventListener('wheel', handleTrayWheelNative, { passive: false });
    return () => el.removeEventListener('wheel', handleTrayWheelNative);
  }, [isHorizontalLayout, handleTrayWheelNative]);

  // Continuous scroll functions (restored for hybrid layout)
  const stopContinuousScroll = useCallback(() => {
    if (scrollIntervalRef.current) {
      clearInterval(scrollIntervalRef.current);
      scrollIntervalRef.current = null;
    }
    setIsScrolling(null);
    // Sync targetScrollRef to current position so wheel scroll works correctly after button scroll
    if (trayScrollRef.current) {
      targetScrollRef.current = trayScrollRef.current.scrollLeft;
    }
  }, []);

  const startContinuousScroll = useCallback((direction: 'left' | 'right') => {
    // Cancel any active momentum scroll to prevent conflicts
    if (momentumFrameRef.current) {
      cancelAnimationFrame(momentumFrameRef.current);
      momentumFrameRef.current = null;
    }
    if (scrollIntervalRef.current) {
      clearInterval(scrollIntervalRef.current);
    }

    setIsScrolling(direction);
    
    const scroll = () => {
      if (trayScrollRef.current) {
        const { scrollLeft, scrollWidth, clientWidth } = trayScrollRef.current;
        const scrollAmount = 9;
        
        if (direction === 'left' && scrollLeft > 0) {
          trayScrollRef.current.scrollLeft -= scrollAmount;
        } else if (direction === 'right' && scrollLeft < scrollWidth - clientWidth) {
          trayScrollRef.current.scrollLeft += scrollAmount;
        } else {
          stopContinuousScroll();
        }
      }
    };
    
    scroll();
    scrollIntervalRef.current = setInterval(scroll, 16);
  }, [stopContinuousScroll]);

  useEffect(() => {
    return () => {
      if (scrollIntervalRef.current) {
        clearInterval(scrollIntervalRef.current);
      }
      if (momentumFrameRef.current) {
        cancelAnimationFrame(momentumFrameRef.current);
      }
    };
  }, []);

  // Scroll button update listeners (restored for hybrid layout)
  useEffect(() => {
    const scrollContainer = trayScrollRef.current;
    if (scrollContainer && isHorizontalLayout) { // Only enable when in horizontal layout
      updateScrollButtons();
      scrollContainer.addEventListener('scroll', updateScrollButtons);
      window.addEventListener('resize', updateScrollButtons);
      
      return () => {
        scrollContainer.removeEventListener('scroll', updateScrollButtons);
        window.removeEventListener('resize', updateScrollButtons);
      };
    }
  }, [updateScrollButtons, isHorizontalLayout]); // Update when layout mode changes

  // Hover effect handlers for overlay system
  const handleElementHover = useCallback((element: any, targetElement: HTMLElement) => {
    if (!isHorizontalLayout || !trayScrollRef.current) return; // Only use overlay in horizontal mode
    
    const rect = targetElement.getBoundingClientRect();
    const containerRect = trayScrollRef.current.getBoundingClientRect();
    
    setHoveredElement({
      element,
      rect,
      containerRect
    });
  }, [isHorizontalLayout]);

  const handleElementHoverEnd = useCallback(() => {
    setHoveredElement(null);
  }, []);

  // Update hover position on scroll
  useEffect(() => {
    const scrollContainer = trayScrollRef.current;
    if (!scrollContainer || !hoveredElement) return;

    const handleScroll = () => {
      // Clear hover on scroll to avoid misaligned effects
      setHoveredElement(null);
    };

    scrollContainer.addEventListener('scroll', handleScroll);
    return () => scrollContainer.removeEventListener('scroll', handleScroll);
  }, [hoveredElement]);
  

  // Detect mobile device
  useEffect(() => {
    const checkMobile = () => {
      setIsMobile(window.innerWidth <= 768);
    };
    
    checkMobile();
    window.addEventListener('resize', checkMobile);
    return () => window.removeEventListener('resize', checkMobile);
  }, []);

  // Auto-save with toast notifications
  useEffect(() => {
    if (autoSaveStatus === 'saving') {
      const timer = setTimeout(() => {
        setAutoSaveStatus('saved');
        showToast('success', 'All changes saved');
        setTimeout(() => setAutoSaveStatus('idle'), 1000);
      }, 1000);
      return () => clearTimeout(timer);
    }
  }, [autoSaveStatus, showToast]);

  // Keyboard shortcuts
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      const isCtrlOrCmd = e.ctrlKey || e.metaKey;
      
      if (isCtrlOrCmd) {
        switch (e.key) {
          case 'z':
            e.preventDefault();
            if (e.shiftKey) {
              handleRedo();
            } else {
              handleUndo();
            }
            break;
          case 'c':
            if (selectedElement) {
              e.preventDefault();
              handleCopyElement();
            }
            break;
          case 'v':
            e.preventDefault();
            handlePasteElement();
            break;
          case 'd':
            if (selectedElement) {
              e.preventDefault();
              handleDuplicateElement();
            }
            break;
          case 's':
            e.preventDefault();
            handleSaveForm();
            break;
        }
      }
      
      if (e.key === 'Delete' || e.key === 'Backspace') {
        if (selectedElement && !e.target?.closest('.inline-editable-input')) {
          e.preventDefault();
          handleDeleteElement(selectedElement.id);
        }
      }
      
      if (e.key === 'Escape') {
        setContextMenu(null);
        setFloatingPanel(null);
        setSelectedElements([]);
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [selectedElement, history, historyIndex]);

  // Cleanup event listeners on unmount
  useEffect(() => {
    return () => {
      document.removeEventListener('dragover', preventDefault);
      document.removeEventListener('drop', preventDefault);
    };
  }, []);

  const preventDefault = (e: Event) => {
    e.preventDefault();
  };

  // History management
  const saveToHistory = () => {
    const newHistory = history.slice(0, historyIndex + 1);
    newHistory.push({ items: { ...items }, order: [...order] });
    setHistory(newHistory);
    setHistoryIndex(newHistory.length - 1);
  };

  const handleUndo = () => {
    if (historyIndex > 0) {
      setHistoryIndex(historyIndex - 1);
      const prevState = history[historyIndex - 1];
      // Dispatch actions to restore state
    }
  };

  const handleRedo = () => {
    if (historyIndex < history.length - 1) {
      setHistoryIndex(historyIndex + 1);
      const nextState = history[historyIndex + 1];
      // Dispatch actions to restore state
    }
  };

  // Helper: Extract all default values from a schema
  const getSchemaDefaults = (schema: ReturnType<typeof getElementSchema>) => {
    if (!schema) return {};

    const defaults: Record<string, unknown> = { ...schema.defaults };

    // Also extract defaults from property definitions
    for (const category of Object.keys(schema.properties)) {
      const categoryProps = schema.properties[category as keyof typeof schema.properties];
      if (categoryProps) {
        for (const [propName, propSchema] of Object.entries(categoryProps)) {
          if (propSchema.default !== undefined && defaults[propName] === undefined) {
            defaults[propName] = propSchema.default;
          }
        }
      }
    }

    return defaults;
  };

  // Add element to form (schema-first)
  const handleAddElement = useCallback((type: string, label: string, Icon: any) => {
    try {
      const schema = getElementSchema(type);

      if (!schema) {
        console.warn(`No schema registered for element type: ${type}`);
        return;
      }

      // Schema-first: Create element from schema
      const schemaDefaults = getSchemaDefaults(schema);
      const newElement = {
        id: uuidv4(),
        type,
        properties: {
          ...schemaDefaults,
          name: schemaDefaults.name || type + '_' + Date.now().toString(36),
          label: schemaDefaults.label || label,
        },
        children: schema.container ? [] : undefined,
      };

      addElement(newElement);
      setSelectedElements([newElement.id]);
      setAutoSaveStatus('saving');
      saveToHistory();
    } catch (error) {
      console.error('Error adding element:', error);
      setAutoSaveStatus('error');
    }
  }, [addElement, setSelectedElements, items, order]);

  // Enhanced drag and drop handlers
  const handleDragStart = useCallback((e: React.DragEvent, element: any, isNew = false) => {
    try {
      setIsDragging(true);
      // Only store serializable data in state
      const serializableElement = {
        type: element.type,
        label: element.label,
        icon: element.icon?.name || element.type, // Store icon name, not component
        keywords: element.keywords,
        isNew
      };
      setDraggedElement(serializableElement);
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', JSON.stringify(serializableElement));
    } catch (error) {
      console.error('Error in drag start:', error);
      setIsDragging(false);
      setDraggedElement(null);
    }
  }, []);

  const handleDragEnd = useCallback(() => {
    setIsDragging(false);
    setDraggedElement(null);
    setDragOverIndex(null);
  }, []);

  const handleDragOver = useCallback((e: React.DragEvent, index: number) => {
    e.preventDefault();
    e.stopPropagation();
    e.dataTransfer.dropEffect = 'move';
    setDragOverIndex(index);
  }, []);

  const handleDrop = useCallback((e: React.DragEvent, dropIndex: number) => {
    e.preventDefault();
    e.stopPropagation();
    
    try {
      let elementData = draggedElement;
      
      if (!elementData) {
        const transferData = e.dataTransfer.getData('text/plain');
        if (transferData) {
          elementData = JSON.parse(transferData);
        }
      }
      
      if (elementData) {
        if (elementData.isNew) {
          handleAddElementAtPosition(elementData, dropIndex);
        } else {
          const currentIndex = order.indexOf(elementData.id);
          if (currentIndex !== dropIndex && currentIndex !== -1) {
            const newOrder = [...order];
            const [removed] = newOrder.splice(currentIndex, 1);
            newOrder.splice(dropIndex, 0, removed);
            reorderElements(newOrder);
            setAutoSaveStatus('saving');
            saveToHistory();
          }
        }
      }
    } catch (error) {
      console.error('Error in drop handler:', error);
      setAutoSaveStatus('error');
    }
    
    handleDragEnd();
  }, [draggedElement, order, addElement, reorderElements, handleDragEnd]);

  const handleAddElementAtPosition = (elementData: any, position: number) => {
    const schema = getElementSchema(elementData.type);

    if (!schema) {
      console.warn(`No schema registered for element type: ${elementData.type}`);
      return;
    }

    // Schema-first: Create element from schema
    const schemaDefaults = getSchemaDefaults(schema);
    const newElement = {
      id: uuidv4(),
      type: elementData.type,
      properties: {
        ...schemaDefaults,
        name: schemaDefaults.name || elementData.type + '_' + Date.now().toString(36),
        label: schemaDefaults.label || elementData.label,
      },
      children: schema.container ? [] : undefined,
    };

    addElement(newElement, position);
    setSelectedElements([newElement.id]);
    setAutoSaveStatus('saving');
    saveToHistory();
  };

  // Select element and show floating panel
  const handleSelectElement = useCallback((elementId: string, event?: React.MouseEvent) => {
    const isMultiSelect = event?.ctrlKey || event?.metaKey;
    
    if (isMultiSelect) {
      setMultiSelectElements(prev => 
        prev.includes(elementId) 
          ? prev.filter(id => id !== elementId)
          : [...prev, elementId]
      );
    } else {
      setSelectedElements([elementId]);
      setMultiSelectElements([]);

      // Clear form wrapper selection when selecting an element
      setIsFormWrapperSelected(false);
      
      // Update breadcrumb
      setSelectedElementId(elementId);
      setSelectedElementPath([elementId]);
    }
    
    if (event && !isMultiSelect) {
      const element = items[elementId];
      if (element) {
        // Position panel below the clicked element instead of at cursor
        const target = event.currentTarget as HTMLElement;
        const rect = target.getBoundingClientRect();
        
        setFloatingPanel({
          element: element,
          position: { 
            x: rect.left, 
            y: rect.bottom + 10 
          },
          isVisible: true
        });
      }
    }
  }, [items]);

  // Delete element
  const handleDeleteElement = useCallback((elementId: string) => {
    try {
      removeElement(elementId);
      if (selectedElement?.id === elementId) {
        setSelectedElements([]);
        // Clear selection if deleted element was selected
        setSelectedElementId(null);
        setSelectedElementPath([]);
        setIsFormWrapperSelected(false);
      }
      
      // Remove deleted element from breadcrumb path if present
      setSelectedElementPath(prev => prev.filter(id => id !== elementId));
      
      setAutoSaveStatus('saving');
      setFloatingPanel(null);
      saveToHistory();
    } catch (error) {
      console.error('Error deleting element:', error);
      setAutoSaveStatus('error');
    }
  }, [selectedElement, removeElement, setSelectedElements]);

  // Update element property with inline editing support
  const updateElementProperty = useCallback((elementId: string, property: string, value: any) => {
    const element = items[elementId];
    if (element) {
      try {
        updateElement(elementId, {
          properties: {
            ...element.properties,
            [property]: value,
          },
        });
        setAutoSaveStatus('saving');
        saveToHistory();
      } catch (error) {
        console.error('Error updating element property:', error);
        setAutoSaveStatus('error');
      }
    }
  }, [items, updateElement]);

  // Handle right-click context menu
  const handleContextMenu = useCallback((e: React.MouseEvent, elementId: string) => {
    e.preventDefault();
    handleSelectElement(elementId);
    setContextMenu({ x: e.clientX, y: e.clientY });
  }, [handleSelectElement]);

  // Handle context menu actions
  const handleContextAction = useCallback((action: string) => {
    if (selectedElement) {
      try {
        switch (action) {
          case 'edit':
            setFloatingPanel({
              element: selectedElement,
              position: contextMenu || { x: 0, y: 0 }
            });
            break;
          case 'duplicate':
            handleDuplicateElement();
            break;
          case 'copy':
            handleCopyElement();
            break;
          case 'cut':
            handleCutElement();
            break;
          case 'move-up':
            handleMoveElement('up');
            break;
          case 'move-down':
            handleMoveElement('down');
            break;
          case 'delete':
            handleDeleteElement(selectedElement.id);
            break;
        }
      } catch (error) {
        console.error('Error in context action:', error);
        setAutoSaveStatus('error');
      }
    }
    setContextMenu(null);
  }, [selectedElement, handleDeleteElement, contextMenu]);

  // Additional handlers
  const handleDuplicateElement = () => {
    if (selectedElement) {
      const duplicatedElement = {
        ...selectedElement,
        id: uuidv4(),
        properties: {
          ...selectedElement.properties,
          label: `${selectedElement.properties?.label} (Copy)`,
        },
      };
      addElement(duplicatedElement);
      setAutoSaveStatus('saving');
      saveToHistory();
    }
  };

  const handleCopyElement = () => {
    if (selectedElement) {
      localStorage.setItem('copiedElement', JSON.stringify(selectedElement));
    }
  };

  const handleCutElement = () => {
    if (selectedElement) {
      handleCopyElement();
      handleDeleteElement(selectedElement.id);
    }
  };

  const handlePasteElement = () => {
    const copiedElement = localStorage.getItem('copiedElement');
    if (copiedElement) {
      const element = JSON.parse(copiedElement);
      const newElement = {
        ...element,
        id: uuidv4(),
      };
      addElement(newElement);
      setAutoSaveStatus('saving');
      saveToHistory();
    }
  };

  const handleMoveElement = (direction: 'up' | 'down') => {
    if (selectedElement) {
      const currentIndex = order.indexOf(selectedElement.id);
      const newIndex = direction === 'up' ? currentIndex - 1 : currentIndex + 1;
      if (newIndex >= 0 && newIndex < order.length) {
        const newOrder = [...order];
        const [removed] = newOrder.splice(currentIndex, 1);
        newOrder.splice(newIndex, 0, removed);
        reorderElements(newOrder);
        setAutoSaveStatus('saving');
        saveToHistory();
      }
    }
  };

  const handleSaveForm = () => {
    setAutoSaveStatus('saving');
    // Implement actual save logic here
  };

  // Handle text formatting
  const handleTextFormat = useCallback((format: string) => {
    console.log('Format text:', format);
    // Implement text formatting logic here
    setSelectedTextInfo(null);
  }, []);

  // Panel handlers
  const handleVersionRestore = (versionId: string) => {
    console.log('Restore version:', versionId);
    setShowVersionHistory(false);
  };

  const handleExport = (type: string) => {
    console.log('Export as:', type);
    setShowExportPanel(false);
    // Implement export logic
  };

  // DEPRECATED: Render element preview based on type - replaced with ElementRenderer
  // const renderElementPreview = (element: any) => {
//     const commonProps = {
//       className: "form-input",
//       disabled: true,
//       style: {
//         width: element.properties?.width === 'full' ? '100%' : `${element.properties?.width}%`,
//         margin: element.properties?.margin,
//         backgroundColor: element.properties?.backgroundColor,
//         borderStyle: element.properties?.borderStyle,
//       }
//     };
// 
//     switch (element.type) {
//       case 'text':
//       case 'email':
//       case 'phone':
//       case 'url':
//         return (
//           <input
//             type={element.type}
//             placeholder={element.properties?.placeholder}
//             {...commonProps}
//           />
//         );
// 
//       case 'password':
//         return (
//           <input
//             type="password"
//             placeholder={element.properties?.placeholder || "Enter password"}
//             {...commonProps}
//           />
//         );
// 
//       case 'number':
//       case 'number-formatted':
//         return (
//           <input
//             type="number"
//             placeholder={element.properties?.placeholder}
//             {...commonProps}
//           />
//         );
//         
//       case 'textarea':
//         return (
//           <textarea
//             placeholder={element.properties?.placeholder}
//             rows={3}
//             {...commonProps}
//           />
//         );
//         
//       case 'select':
//         return (
//           <select {...commonProps}>
//             <option>Choose an option</option>
//             {element.properties?.options?.map((option: string, idx: number) => (
//               <option key={idx} value={option}>{option}</option>
//             ))}
//           </select>
//         );
//         
//       case 'checkbox':
//         return (
//           <div className="space-y-2">
//             {element.properties?.options?.map((option: string, idx: number) => (
//               <div key={idx} className="flex items-center">
//                 <input type="checkbox" className="w-4 h-4 mr-2" disabled />
//                 <InlineEditableText
//                   value={option}
//                   onChange={(value) => {
//                     const newOptions = [...(element.properties?.options || [])];
//                     newOptions[idx] = value;
//                     updateElementProperty(element.id, 'options', newOptions);
//                   }}
//                   className="text-sm text-gray-600"
//                   placeholder="Checkbox option"
//                 />
//               </div>
//             ))}
//           </div>
//         );
//         
//       case 'radio':
//         return (
//           <div className="space-y-2">
//             {element.properties?.options?.map((option: string, idx: number) => (
//               <div key={idx} className="flex items-center">
//                 <input type="radio" name={element.id} className="w-4 h-4 mr-2" disabled />
//                 <InlineEditableText
//                   value={option}
//                   onChange={(value) => {
//                     const newOptions = [...(element.properties?.options || [])];
//                     newOptions[idx] = value;
//                     updateElementProperty(element.id, 'options', newOptions);
//                   }}
//                   className="text-sm text-gray-600"
//                   placeholder="Radio option"
//                 />
//               </div>
//             ))}
//           </div>
//         );
// 
//       case 'checkbox-cards':
//         return (
//           <div 
//             className="grid gap-3"
//             style={{
//               gridTemplateColumns: `repeat(${element.properties.columnsPerRow || 2}, 1fr)`
//             }}
//           >
//             {element.properties?.options?.map((option: string, idx: number) => (
//               <div 
//                 key={idx} 
//                 className="checkbox-card border-2 border-gray-200 rounded-lg p-4 cursor-pointer transition-all hover:border-blue-300 hover:shadow-md"
//               >
//                 <div className="flex items-start justify-between">
//                   <div className="flex-1">
//                     <div className="checkbox-card-icon mb-2">
//                       <div className="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center">
//                         <CheckSquare size={16} className="text-gray-400" />
//                       </div>
//                     </div>
//                     <div className="checkbox-card-content">
//                       <InlineEditableText
//                         value={option}
//                         onChange={(value) => {
//                           const newOptions = [...(element.properties?.options || [])];
//                           newOptions[idx] = value;
//                           updateElementProperty(element.id, 'options', newOptions);
//                         }}
//                         className="font-medium text-gray-900 text-sm leading-tight"
//                         placeholder="Option title"
//                       />
//                       {element.properties.showDescriptions !== false && (
//                         <p className="text-xs text-gray-500 mt-1">Description for this option</p>
//                       )}
//                     </div>
//                   </div>
//                   <input 
//                     type="checkbox" 
//                     className="checkbox-card-input mt-1" 
//                     disabled 
//                   />
//                 </div>
//               </div>
//             ))}
//           </div>
//         );
// 
//       case 'radio-cards':
//         return (
//           <div 
//             className="grid gap-3"
//             style={{
//               gridTemplateColumns: `repeat(${element.properties.columnsPerRow || 2}, 1fr)`
//             }}
//           >
//             {element.properties?.options?.map((option: string, idx: number) => (
//               <div 
//                 key={idx} 
//                 className="radio-card border-2 border-gray-200 rounded-lg p-4 cursor-pointer transition-all hover:border-blue-300 hover:shadow-md"
//               >
//                 <div className="flex items-start justify-between">
//                   <div className="flex-1">
//                     <div className="radio-card-icon mb-2">
//                       <div className="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center">
//                         <Radio size={16} className="text-gray-400" />
//                       </div>
//                     </div>
//                     <div className="radio-card-content">
//                       <InlineEditableText
//                         value={option}
//                         onChange={(value) => {
//                           const newOptions = [...(element.properties?.options || [])];
//                           newOptions[idx] = value;
//                           updateElementProperty(element.id, 'options', newOptions);
//                         }}
//                         className="font-medium text-gray-900 text-sm leading-tight"
//                         placeholder="Option title"
//                       />
//                       {element.properties.showDescriptions !== false && (
//                         <p className="text-xs text-gray-500 mt-1">Description for this option</p>
//                       )}
//                     </div>
//                   </div>
//                   <input 
//                     type="radio" 
//                     name={element.id}
//                     className="radio-card-input mt-1" 
//                     disabled 
//                   />
//                 </div>
//               </div>
//             ))}
//           </div>
//         );
//         
//       case 'rating':
//         return (
//           <div className="flex gap-1">
//             {Array.from({ length: element.properties?.maxRating || 5 }, (_, i) => (
//               <Star key={i} size={24} className="text-gray-300" />
//             ))}
//           </div>
//         );
// 
//       case 'slider':
//         return (
//           <div className="space-y-2">
//             <input
//               type="range"
//               min={element.properties?.min || 0}
//               max={element.properties?.max || 100}
//               step={element.properties?.step || 1}
//               className="w-full"
//               disabled
//             />
//             <div className="flex justify-between text-sm text-gray-500">
//               <span>{element.properties?.min || 0}</span>
//               <span>{element.properties?.max || 100}</span>
//             </div>
//           </div>
//         );
// 
//       case 'color':
//         return (
//           <input
//             type="color"
//             className="w-16 h-10 border border-gray-300 rounded"
//             disabled
//           />
//         );
//         
//       case 'date':
//         return <input type="date" {...commonProps} />;
//         
//       case 'time':
//         return <input type="time" {...commonProps} />;
// 
//       case 'datetime':
//         return <input type="datetime-local" {...commonProps} />;
//         
//       case 'file':
//         return (
//           <div className="border-2 border-dashed border-gray-300 rounded-md p-4 text-center">
//             <Upload size={24} className="mx-auto mb-2 text-gray-400" />
//             <p className="text-sm text-gray-500">Click to upload or drag and drop</p>
//             {element.properties?.acceptedTypes && (
//               <p className="text-xs text-gray-400 mt-1">
//                 Accepted: {element.properties.acceptedTypes}
//               </p>
//             )}
//           </div>
//         );
// 
//       case 'signature':
//         return (
//           <div className="border border-gray-300 rounded-md p-4 h-32 flex items-center justify-center bg-gray-50">
//             <PenTool size={24} className="text-gray-400 mr-2" />
//             <span className="text-gray-500">Signature pad area</span>
//           </div>
//         );
// 
//       case 'location':
//         return (
//           <div className="border border-gray-300 rounded-md p-4 h-32 flex items-center justify-center bg-gray-50">
//             <MapPin size={24} className="text-gray-400 mr-2" />
//             <span className="text-gray-500">Map picker area</span>
//           </div>
//         );
//         
//       case 'heading':
//         return (
//           <InlineEditableText
//             value={element.properties?.label || 'Heading'}
//             onChange={(value) => updateElementProperty(element.id, 'label', value)}
//             className="text-2xl font-bold"
//             placeholder="Heading text"
//             onFormat={handleTextFormat}
//           />
//         );
//         
//       case 'paragraph':
//         return (
//           <InlineEditableText
//             value={element.properties?.content || 'Add your paragraph text here...'}
//             onChange={(value) => updateElementProperty(element.id, 'content', value)}
//             className="text-gray-600"
//             placeholder="Paragraph content"
//             multiline
//             onFormat={handleTextFormat}
//           />
//         );
// 
//       case 'html-block':
//         return (
//           <div className="border border-gray-300 rounded-md p-4 bg-gray-50">
//             <Code size={20} className="text-gray-400 mb-2" />
//             <div 
//               dangerouslySetInnerHTML={{ 
//                 __html: element.properties?.htmlContent || '<div>Custom HTML content</div>' 
//               }} 
//             />
//           </div>
//         );
// 
//       case 'columns':
//         return (
//           <div className="grid grid-cols-2 gap-4 p-4 border border-dashed border-gray-300 rounded-md">
//             <div className="h-16 bg-gray-100 rounded flex items-center justify-center text-gray-500">
//               Column 1
//             </div>
//             <div className="h-16 bg-gray-100 rounded flex items-center justify-center text-gray-500">
//               Column 2
//             </div>
//           </div>
//         );
// 
//       case 'section':
//         return (
//           <div className="border border-gray-300 rounded-md p-4">
//             <Layout size={20} className="text-gray-400 mb-2" />
//             <span className="text-gray-500">Section container</span>
//           </div>
//         );
// 
//       case 'page-break':
//         return (
//           <div className="flex items-center justify-center py-4">
//             <div className="border-t-2 border-dashed border-gray-400 flex-grow"></div>
//             <span className="px-4 text-sm text-gray-500 bg-white">Page Break</span>
//             <div className="border-t-2 border-dashed border-gray-400 flex-grow"></div>
//           </div>
//         );
//         
//       case 'divider':
//         return <hr className="border-gray-300 my-2" />;
//         
//       case 'spacer':
//         return <div className="h-8" />;
// 
//       case 'payment':
//         return (
//           <div className="border border-gray-300 rounded-md p-4">
//             <CreditCard size={20} className="text-gray-400 mb-2" />
//             <div className="text-sm text-gray-600">
//               Payment Amount: {element.properties?.amountType === 'fixed' ? 'Fixed' : 'Variable'} 
//               ({element.properties?.currency || 'USD'})
//             </div>
//           </div>
//         );
// 
//       case 'embed':
//         return (
//           <div className="border border-gray-300 rounded-md p-4 bg-gray-50">
//             <Code size={20} className="text-gray-400 mb-2" />
//             <span className="text-gray-500">Embedded content area</span>
//           </div>
//         );
// 
//       case 'webhook':
//         return (
//           <div className="border border-gray-300 rounded-md p-4 bg-gray-50">
//             <Webhook size={20} className="text-gray-400 mb-2" />
//             <span className="text-gray-500">Webhook trigger</span>
//           </div>
//         );
// 
//       case 'calculation':
//         return (
//           <div className="border border-gray-300 rounded-md p-4 bg-gray-50">
//             <Calculator size={20} className="text-gray-400 mb-2" />
//             <span className="text-gray-500">Calculated field</span>
//           </div>
//         );
// 
//       case 'columns':
//         return (
//           <div 
//             className="columns-container"
//             style={{
//               display: 'grid',
//               gridTemplateColumns: `repeat(${element.properties.columnCount || 2}, 1fr)`,
//               gap: element.properties.gap || '20px',
//               minHeight: '80px',
//               border: '2px dashed #e5e7eb',
//               borderRadius: '8px',
//               padding: '16px'
//             }}
//           >
//             {Array.from({ length: element.properties.columnCount || 2 }).map((_, index) => (
//               <div
//                 key={index}
//                 className="column-drop-zone"
//                 style={{
//                   border: '1px dashed #d1d5db',
//                   borderRadius: '4px',
//                   padding: '12px',
//                   minHeight: '60px',
//                   display: 'flex',
//                   alignItems: 'center',
//                   justifyContent: 'center',
//                   backgroundColor: '#f9fafb'
//                 }}
//               >
//                 <span className="text-gray-400 text-sm">
//                   Drop elements here
//                 </span>
//               </div>
//             ))}
//           </div>
//         );
// 
//       case 'tabs':
//         return (
//           <div className="tabs-container border border-gray-300 rounded-lg">
//             <div className="tabs-header flex border-b border-gray-200">
//               {element.properties.tabs?.map((tab: any, index: number) => (
//                 <button
//                   key={tab.id}
//                   className={`tab-button px-4 py-2 text-sm font-medium border-b-2 ${
//                     index === (element.properties.activeTab || 0)
//                       ? 'border-blue-500 text-blue-600'
//                       : 'border-transparent text-gray-500 hover:text-gray-700'
//                   }`}
//                 >
//                   {tab.title}
//                 </button>
//               ))}
//             </div>
//             <div 
//               className="tab-content p-4 min-h-[100px] bg-gray-50"
//               style={{
//                 border: '1px dashed #d1d5db',
//                 borderRadius: '4px',
//                 display: 'flex',
//                 alignItems: 'center',
//                 justifyContent: 'center'
//               }}
//             >
//               <span className="text-gray-400 text-sm">
//                 Drop elements in active tab
//               </span>
//             </div>
//           </div>
//         );
// 
//       case 'accordion':
//         return (
//           <div className="accordion-container border border-gray-300 rounded-lg overflow-hidden">
//             {element.properties.sections?.map((section: any, index: number) => (
//               <div key={section.id} className="accordion-section">
//                 <button className="accordion-header w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 border-b border-gray-200 flex items-center justify-between">
//                   <span className="font-medium">{section.title}</span>
//                   <ChevronDown 
//                     size={16} 
//                     className={`transform transition-transform ${section.expanded ? 'rotate-180' : ''}`}
//                   />
//                 </button>
//                 {section.expanded && (
//                   <div 
//                     className="accordion-content p-4 min-h-[80px] bg-white"
//                     style={{
//                       border: '1px dashed #d1d5db',
//                       borderRadius: '4px',
//                       margin: '8px',
//                       display: 'flex',
//                       alignItems: 'center',
//                       justifyContent: 'center'
//                     }}
//                   >
//                     <span className="text-gray-400 text-sm">
//                       Drop elements in this section
//                     </span>
//                   </div>
//                 )}
//               </div>
//             ))}
//           </div>
//         );
// 
//       case 'step-wizard':
//         return (
//           <div className="step-wizard-container border border-gray-300 rounded-lg p-4">
//             <div className="steps-indicator flex items-center justify-between mb-4">
//               {element.properties.steps?.map((step: any, index: number) => (
//                 <div key={step.id} className="flex items-center">
//                   <div 
//                     className={`step-circle w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium ${
//                       index === (element.properties.currentStep || 0)
//                         ? 'bg-blue-500 text-white'
//                         : index < (element.properties.currentStep || 0)
//                         ? 'bg-green-500 text-white'
//                         : 'bg-gray-200 text-gray-600'
//                     }`}
//                   >
//                     {index + 1}
//                   </div>
//                   <div className="ml-2">
//                     <div className="text-sm font-medium">{step.title}</div>
//                     <div className="text-xs text-gray-500">{step.description}</div>
//                   </div>
//                   {index < (element.properties.steps?.length - 1) && (
//                     <div className="w-8 h-px bg-gray-300 mx-4"></div>
//                   )}
//                 </div>
//               ))}
//             </div>
//             <div 
//               className="step-content p-4 min-h-[120px] bg-gray-50"
//               style={{
//                 border: '1px dashed #d1d5db',
//                 borderRadius: '4px',
//                 display: 'flex',
//                 alignItems: 'center',
//                 justifyContent: 'center'
//               }}
//             >
//               <span className="text-gray-400 text-sm">
//                 Drop elements for current step
//               </span>
//             </div>
//           </div>
//         );
// 
//       case 'section':
//         return (
//           <fieldset className="section-container border border-gray-300 rounded-lg p-4">
//             {element.properties.legend && (
//               <legend className="px-2 text-sm font-medium text-gray-700">
//                 {element.properties.legend}
//               </legend>
//             )}
//             <div 
//               className="section-content min-h-[80px] bg-gray-50"
//               style={{
//                 border: '1px dashed #d1d5db',
//                 borderRadius: '4px',
//                 padding: '16px',
//                 display: 'flex',
//                 alignItems: 'center',
//                 justifyContent: 'center'
//               }}
//             >
//               <span className="text-gray-400 text-sm">
//                 Drop elements in this section
//               </span>
//             </div>
//           </fieldset>
//         );
// 
//       case 'repeater':
//         return (
//           <div className="repeater-container border border-gray-300 rounded-lg p-4">
//             <div className="repeater-header flex items-center justify-between mb-3">
//               <span className="text-sm font-medium text-gray-700">
//                 {element.properties.itemLabel || 'Item'} Repeater
//               </span>
//               <button className="text-blue-500 hover:text-blue-700 text-sm">
//                 + Add Item
//               </button>
//             </div>
//             <div 
//               className="repeater-content min-h-[80px] bg-gray-50"
//               style={{
//                 border: '1px dashed #d1d5db',
//                 borderRadius: '4px',
//                 padding: '16px',
//                 display: 'flex',
//                 alignItems: 'center',
//                 justifyContent: 'center'
//               }}
//             >
//               <span className="text-gray-400 text-sm">
//                 Drop elements for repeating template
//               </span>
//             </div>
//           </div>
//         );
// 
//       case 'conditional-group':
//         return (
//           <div className="conditional-group-container border border-gray-300 rounded-lg p-4">
//             <div className="condition-header mb-3">
//               <div className="flex items-center gap-2 text-sm text-gray-600">
//                 <Workflow size={16} />
//                 <span>Show when: </span>
//                 <span className="font-mono bg-gray-100 px-2 py-1 rounded">
//                   {element.properties.condition?.field || '[field]'} {element.properties.condition?.operator || '='} {element.properties.condition?.value || '[value]'}
//                 </span>
//               </div>
//             </div>
//             <div 
//               className="conditional-content min-h-[80px] bg-gray-50"
//               style={{
//                 border: '1px dashed #d1d5db',
//                 borderRadius: '4px',
//                 padding: '16px',
//                 display: 'flex',
//                 alignItems: 'center',
//                 justifyContent: 'center'
//               }}
//             >
//               <span className="text-gray-400 text-sm">
//                 Drop conditional elements here
//               </span>
//             </div>
//           </div>
//         );
// 
//       case 'card':
//         return (
//           <div className="card-container border border-gray-300 rounded-lg overflow-hidden bg-white shadow-sm">
//             {element.properties.showHeader && (
//               <div className="card-header bg-gray-50 px-4 py-3 border-b border-gray-200">
//                 <h3 className="text-sm font-medium text-gray-700">
//                   {element.properties.headerTitle || element.label}
//                 </h3>
//               </div>
//             )}
//             <div 
//               className="card-content p-4 min-h-[80px]"
//               style={{
//                 border: '1px dashed #d1d5db',
//                 borderRadius: '4px',
//                 margin: '8px',
//                 display: 'flex',
//                 alignItems: 'center',
//                 justifyContent: 'center',
//                 backgroundColor: '#f9fafb'
//               }}
//             >
//               <span className="text-gray-400 text-sm">
//                 Drop elements in card body
//               </span>
//             </div>
//             {element.properties.showFooter && (
//               <div className="card-footer bg-gray-50 px-4 py-3 border-t border-gray-200">
//                 <div className="text-sm text-gray-500">Card footer</div>
//               </div>
//             )}
//           </div>
//         );
//         
//       default:
//         return (
//           <div className="border border-gray-300 rounded-md p-4 text-center text-gray-500">
//             Unknown element type: {element.type}
//           </div>
//         );
//     }
  // };

  return (
    <ErrorBoundary>
      <div className="flex flex-col h-screen">
        {/* Top Bar - Schema Driven */}
        <TopBar
          currentFormId={currentFormId}
          onFormSelect={setCurrentFormId}
          formTitle={formTitle}
          onFormTitleChange={setFormTitle}
          devicePreview={devicePreview}
          onDeviceChange={setDevicePreview}
          showDeviceFrame={showDeviceFrame}
          onToggleDeviceFrame={() => setShowDeviceFrame(!showDeviceFrame)}
          canUndo={historyIndex > 0}
          canRedo={historyIndex < history.length - 1}
          onUndo={handleUndo}
          onRedo={handleRedo}
          showGrid={showGrid}
          onToggleGrid={() => setShowGrid(!showGrid)}
          zoom={zoom}
          onZoomChange={setZoom}
          showElementsPanel={showElementsPanel}
          onToggleElementsPanel={() => setShowElementsPanel(!showElementsPanel)}
          onShowVersionHistory={() => setShowVersionHistory(true)}
          onShowSharePanel={() => setShowSharePanel(true)}
          onShowExportPanel={() => setShowExportPanel(true)}
          onShowAnalytics={() => setShowAnalytics(true)}
          onPreview={() => {}}
          onSave={handleSaveForm}
          onPublish={() => {}}
          isSaving={autoSaveStatus === 'saving'}
          isMobile={isMobile}
        />

        {/* Horizontal Tabs Bar - Schema Driven */}
        <TabBar
          activeTab={activeTab}
          onTabChange={setActiveTab}
        />

        {/* Main Content Area */}
        <div className="flex flex-1 overflow-hidden relative">
          {/* Tab Content Panel (full-width, replaces canvas) */}
          {activeTab !== 'canvas' && (
            <div className="flex-1 bg-background flex flex-col overflow-hidden">
              {activeTab === 'settings' && (
              <div className="flex-1 overflow-auto">
                <div className="p-4">
                  <h3 className="font-semibold mb-4">Form Settings</h3>
                  <div className="space-y-4">
                    <div className="space-y-1">
                      <label className="text-xs font-medium text-muted-foreground">Form Name</label>
                      <InlineEditableText
                        value={formTitle}
                        onChange={setFormTitle}
                        className="w-full px-3 py-2 border border-border rounded-md text-sm"
                        placeholder="Form Name"
                      />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-medium text-muted-foreground">Form Description</label>
                      <InlineEditableText
                        value=""
                        onChange={() => {}}
                        className="w-full px-3 py-2 border border-border rounded-md text-sm"
                        placeholder="Describe what this form is for..."
                        multiline
                      />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-medium text-muted-foreground">Submit Button Text</label>
                      <InlineEditableText
                        value="Submit"
                        onChange={() => {}}
                        className="w-full px-3 py-2 border border-border rounded-md text-sm"
                        placeholder="Submit"
                      />
                    </div>

                    {/* Submission Settings */}
                    <div className="pt-4 border-t border-border">
                      <h4 className="font-medium mb-3">Submission Settings</h4>
                      <div className="space-y-2">
                        <label className="flex items-center gap-2 cursor-pointer">
                          <input type="checkbox" className="rounded border-border" />
                          <span className="text-sm text-muted-foreground">Require login to submit</span>
                        </label>
                        <label className="flex items-center gap-2 cursor-pointer">
                          <input type="checkbox" className="rounded border-border" />
                          <span className="text-sm text-muted-foreground">Allow multiple submissions</span>
                        </label>
                        <label className="flex items-center gap-2 cursor-pointer">
                          <input type="checkbox" className="rounded border-border" />
                          <span className="text-sm text-muted-foreground">Save as draft</span>
                        </label>
                        <label className="flex items-center gap-2 cursor-pointer">
                          <input type="checkbox" className="rounded border-border" />
                          <span className="text-sm text-muted-foreground">Show progress bar</span>
                        </label>
                      </div>
                    </div>

                    {/* Notification Settings */}
                    <div className="pt-4 border-t border-border">
                      <h4 className="font-medium mb-3">Notifications</h4>
                      <div className="space-y-3">
                        <div className="space-y-1">
                          <label className="text-xs font-medium text-muted-foreground">Admin Email</label>
                          <input type="email" className="w-full px-3 py-2 border border-border rounded-md text-sm" placeholder="admin@example.com" />
                        </div>
                        <div className="space-y-1">
                          <label className="text-xs font-medium text-muted-foreground">Thank You Page URL</label>
                          <input type="url" className="w-full px-3 py-2 border border-border rounded-md text-sm" placeholder="https://example.com/thank-you" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}
              {activeTab === 'emails' && (
                <Suspense fallback={<TabLoadingFallback />}>
                  <EmailsTab />
                </Suspense>
              )}
              {activeTab === 'entries' && <EntriesTabContent />}
              {activeTab === 'automation' && (
                <Suspense fallback={<TabLoadingFallback />}>
                  <AutomationsTab formId={parseInt(currentFormId) || 0} />
                </Suspense>
              )}
              {activeTab === 'style' && <StyleTabContent />}
              {activeTab === 'themes' && (
                <Suspense fallback={<TabLoadingFallback />}>
                  <ThemesTab />
                </Suspense>
              )}
              {activeTab === 'integrations' && <IntegrationsTabContent />}
            </div>
          )}

          {/* Enhanced Canvas Area - only show when canvas tab is active */}
          {activeTab === 'canvas' && (
          <div
            className="flex-1 flex flex-col overflow-auto bg-muted/30 p-4"
            style={{ paddingBottom: `${canvasBottomPadding}px` }}
          >
            {/* Canvas Width Control */}
            <div className="flex items-center gap-2 mb-3 text-sm text-muted-foreground">
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={useCustomWidth}
                  onChange={(e) => setUseCustomWidth(e.target.checked)}
                  className="rounded border-border"
                />
                <span>Custom Width:</span>
              </label>
              <input
                type="text"
                className="w-24 px-2 py-1 text-sm border border-border rounded-md bg-background disabled:opacity-50"
                value={customCanvasWidth}
                onChange={(e) => setCustomCanvasWidth(e.target.value)}
                placeholder={devicePreview === 'desktop' ? '1200px' : devicePreview === 'tablet' ? '768px' : '375px'}
                disabled={!useCustomWidth}
              />
            </div>

            {/* Canvas Zoom Wrapper */}
            <div
              className="flex-1 flex items-start justify-center origin-top"
              style={{
                transform: `scale(${zoom})`
              }}
            >
              <div
                ref={canvasRef}
                className={cn(
                  'w-full bg-background rounded-lg shadow-sm border border-border min-h-[400px] relative mx-auto',
                  devicePreview === 'tablet' && 'max-w-[768px]',
                  devicePreview === 'mobile' && 'max-w-[375px]',
                  devicePreview === 'desktop' && 'max-w-[1200px]',
                  showDeviceFrame && 'canvas-framed'
                )}
                style={useCustomWidth && customCanvasWidth ? {
                  maxWidth: customCanvasWidth
                } : {}}
              >
              {/* Grid Overlay */}
              <GridOverlay isVisible={showGrid} />

              {/* Breadcrumb for device modes - positioned between tabs and device */}
              {showDeviceFrame && devicePreview !== 'desktop' && (
                <div 
                  className="simple-breadcrumb" 
                  style={{ 
                    position: 'absolute', 
                    top: '20px', 
                    left: '20px', 
                    zIndex: 25
                  }}
                >
                    <button 
                      className={`breadcrumb-root ${isFormWrapperSelected ? 'breadcrumb-current' : ''}`}
                      onClick={() => {
                        // Select form wrapper instead of clearing selection
                        setSelectedElementId(null);
                        setSelectedElementPath([]);
                        setIsFormWrapperSelected(true);
                        setSelectedElements([]); // Clear selection

                        // Flash highlight the form wrapper with orange animation
                        const formWrapper = document.querySelector('.form-wrapper');
                        if (formWrapper) {
                          formWrapper.classList.add('element-orange-flash');
                          setTimeout(() => {
                            formWrapper.classList.remove('element-orange-flash');
                          }, 1500);
                        }
                        
                        // Show form wrapper settings panel at center of viewport
                        setFloatingPanel({
                          element: null, // Special case for form wrapper
                          position: { 
                            x: window.innerWidth / 2 - 200, // Center horizontally 
                            y: window.innerHeight / 2 - 250  // Center vertically
                          }
                        });
                      }}
                    >
                      Form
                    </button>
                    {selectedElementPath.length > 0 && (
                      <>
                        {selectedElementPath.map((elementId, index) => {
                          const element = items[elementId];
                          if (!element) return null;
                          const label = element.properties?.label || element.label || element.type;
                          const isLast = index === selectedElementPath.length - 1;
                          
                          return (
                            <React.Fragment key={elementId}>
                              <span className="breadcrumb-separator">&gt;</span>
                              <button 
                                className={`breadcrumb-item ${isLast ? 'breadcrumb-current' : ''}`}
                                onClick={() => {
                                  // Navigate to this level
                                  const newPath = selectedElementPath.slice(0, index + 1);
                                  setSelectedElementPath(newPath);
                                  setSelectedElementId(elementId);
                                  
                                  // Scroll to and highlight element with orange flash
                                  const elementEl = document.querySelector(`[data-element-id="${elementId}"]`);
                                  if (elementEl) {
                                    elementEl.scrollIntoView({ 
                                      behavior: 'smooth', 
                                      block: 'center' 
                                    });
                                    
                                    // Add orange flash animation
                                    elementEl.classList.add('element-orange-flash');
                                    setTimeout(() => {
                                      elementEl.classList.remove('element-orange-flash');
                                    }, 1500);
                                  }
                                }}
                              >
                                {label}
                              </button>
                            </React.Fragment>
                          );
                        })}
                      </>
                    )}
                </div>
              )}

              {/* Device Frame */}
              {showDeviceFrame && devicePreview !== 'desktop' && (
                <div 
                  className={`device-frame device-frame-${devicePreview}`}
                  style={{ marginTop: '60px' }} // Add space for breadcrumb between tabs and device
                >
                  <div className="device-frame-header">
                    <div className="device-frame-title">
                      {DEVICE_FRAMES[devicePreview].name}
                    </div>
                  </div>
                  
                  {/* Mobile device elements */}
                  {devicePreview === 'mobile' && (
                    <>
                      <div className="device-home-button"></div>
                      <div className="device-speaker"></div>
                      <div className="device-camera"></div>
                    </>
                  )}
                  
                  {/* Tablet device elements */}
                  {devicePreview === 'tablet' && (
                    <>
                      <div className="device-home-button-tablet"></div>
                      <div className="device-camera-tablet"></div>
                    </>
                  )}
                  
                  {/* Device Screen Overlay - Contains form wrapper in device mode */}
                  <div className="device-screen-overlay">
                    {/* Form Wrapper - Inside device screen when in device mode */}
                    <div 
                      className={`form-wrapper ${isFormWrapperSelected ? 'form-wrapper-selected' : ''}`}
                      style={{
                        background: formWrapperSettings.backgroundType === 'none' 
                          ? 'transparent'
                          : formWrapperSettings.backgroundType === 'color'
                          ? `${formWrapperSettings.backgroundColor}${Math.round(formWrapperSettings.backgroundOpacity * 255).toString(16).padStart(2, '0')}`
                          : formWrapperSettings.backgroundImage 
                          ? `url(${formWrapperSettings.backgroundImage})`
                          : formWrapperSettings.backgroundColor,
                        backgroundSize: formWrapperSettings.backgroundType === 'image' ? 'cover' : 'auto',
                        backgroundPosition: formWrapperSettings.backgroundType === 'image' ? 'center' : 'auto',
                        backgroundRepeat: formWrapperSettings.backgroundType === 'image' ? 'no-repeat' : 'auto',
                        padding: `${formWrapperSettings[devicePreview]?.padding?.top || 0}px ${formWrapperSettings[devicePreview]?.padding?.right || 0}px ${formWrapperSettings[devicePreview]?.padding?.bottom || 0}px ${formWrapperSettings[devicePreview]?.padding?.left || 0}px`,
                        margin: `${formWrapperSettings[devicePreview]?.margin?.top || 0}px ${formWrapperSettings[devicePreview]?.margin?.right || 0}px ${formWrapperSettings[devicePreview]?.margin?.bottom || 0}px ${formWrapperSettings[devicePreview]?.margin?.left || 0}px`,
                        borderRadius: 'var(--radius-lg)',
                        minHeight: '200px',
                        width: '100%', // Take full width of device screen overlay
                        position: 'relative'
                      }}
                    >
                      {/* No resize bar in device mode */}
                      {orderedElements.length === 0 ? (
                        <div
                          className={`drop-zone ${isDragging ? 'drop-zone-active' : ''}`}
                          onDragOver={(e) => handleDragOver(e, 0)}
                          onDrop={(e) => handleDrop(e, 0)}
                        >
                          <Layers size={24} className="mb-2" />
                          <p>Drag elements from the bottom tray to start building your form</p>
                        </div>
                      ) : (
                        <div>
                          {orderedElements.map((element, index) => (
                          <React.Fragment key={element.id}>
                            {isDragging && dragOverIndex === index && (
                              <div className="drop-zone drop-zone-hover" />
                            )}
                            
                            <div
                              className={`form-element ${
                                selectedElement?.id === element.id || multiSelectElements.includes(element.id) 
                                  ? 'form-element-selected' : ''
                              } ${isDragging && draggedElement?.id === element.id ? 'form-element-dragging' : ''}`}
                              data-element-id={element.id}
                              draggable
                              onDragStart={(e) => handleDragStart(e, element)}
                              onDragEnd={handleDragEnd}
                              onDragOver={(e) => handleDragOver(e, index + 1)}
                              onDrop={(e) => handleDrop(e, index + 1)}
                              onClick={(e) => handleSelectElement(element.id, e)}
                              onContextMenu={(e) => handleContextMenu(e, element.id)}
                            >
                              <div className="element-controls">
                                <button className="element-control-btn" title="Drag to reorder">
                                  <Move size={16} />
                                </button>
                                <button className="element-control-btn" title="Edit Properties">
                                  <Settings size={16} />
                                </button>
                                <button 
                                  className="element-control-btn"
                                  title="Delete"
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    handleDeleteElement(element.id);
                                  }}
                                >
                                  <Trash2 size={16} />
                                </button>
                              </div>

                              {/* Enhanced Element Preview */}
                              <div className="mb-2">
                                <InlineEditableText
                                  value={element.properties?.label || element.label}
                                  onChange={(value) => updateElementProperty(element.id, 'label', value)}
                                  className="property-label"
                                  placeholder="Field Label"
                                  onFormat={handleTextFormat}
                                />
                                {element.properties?.required && <span className="text-red-500 ml-1">*</span>}
                                {element.properties?.helperText && (
                                  <InlineEditableText
                                    value={element.properties.helperText}
                                    onChange={(value) => updateElementProperty(element.id, 'helperText', value)}
                                    className="property-help"
                                    placeholder="Helper text"
                                  />
                                )}
                              </div>

                              {/* Render enhanced element preview */}
                              <ElementRenderer 
                                element={element} 
                                updateElementProperty={updateElementProperty}
                              />
                            </div>
                            
                            {isDragging && dragOverIndex === orderedElements.length && index === orderedElements.length - 1 && (
                              <div className="drop-zone drop-zone-hover" />
                            )}
                          </React.Fragment>
                        ))}
                        
                        {/* Submit button with inline editing */}
                        <div className="mt-6">
                          <InlineEditableText
                            value="Submit"
                            onChange={() => {}}
                            className="w-full px-4 py-2 bg-blue-500 text-white rounded-md font-medium text-center"
                            placeholder="Submit"
                          />
                        </div>
                      </div>
                      )}
                    </div>
                  </div>
                </div>
              )}

              {/* Interactive Breadcrumb - Only show for desktop mode */}
              {(!showDeviceFrame || devicePreview === 'desktop') && (
                <div className="simple-breadcrumb">
                <button 
                  className={`breadcrumb-root ${isFormWrapperSelected ? 'breadcrumb-current' : ''}`}
                  onClick={() => {
                    // Select form wrapper instead of clearing selection
                    setSelectedElementId(null);
                    setSelectedElementPath([]);
                    setIsFormWrapperSelected(true);
                    setSelectedElements([]); // Clear selection

                    // Flash highlight the form wrapper with orange animation
                    const formWrapper = document.querySelector('.form-wrapper');
                    if (formWrapper) {
                      formWrapper.classList.add('element-orange-flash');
                      setTimeout(() => {
                        formWrapper.classList.remove('element-orange-flash');
                      }, 1500);
                    }
                    
                    // Show form wrapper settings panel at center of viewport
                    setFloatingPanel({
                      element: null, // Special case for form wrapper
                      position: { 
                        x: window.innerWidth / 2 - 200, // Center horizontally 
                        y: window.innerHeight / 2 - 250  // Center vertically
                      }
                    });
                  }}
                >
                  Form
                </button>
                {selectedElementPath.length > 0 && (
                  <>
                    {selectedElementPath.map((elementId, index) => {
                      const element = items[elementId];
                      if (!element) return null;
                      const label = element.properties?.label || element.label || element.type;
                      const isLast = index === selectedElementPath.length - 1;
                      
                      return (
                        <React.Fragment key={elementId}>
                          <span className="breadcrumb-separator">&gt;</span>
                          <button 
                            className={`breadcrumb-item ${isLast ? 'breadcrumb-current' : ''}`}
                            onClick={() => {
                              // Navigate to this level
                              const newPath = selectedElementPath.slice(0, index + 1);
                              setSelectedElementPath(newPath);
                              setSelectedElementId(elementId);
                              
                              // Scroll to and highlight element with orange flash
                              const elementEl = document.querySelector(`[data-element-id="${elementId}"]`);
                              if (elementEl) {
                                elementEl.scrollIntoView({ 
                                  behavior: 'smooth', 
                                  block: 'center' 
                                });
                                
                                // Add orange flash animation
                                elementEl.classList.add('element-orange-flash');
                                setTimeout(() => {
                                  elementEl.classList.remove('element-orange-flash');
                                }, 1500);
                              }
                            }}
                          >
                            {label}
                          </button>
                        </React.Fragment>
                      );
                    })}
                  </>
                )}
              </div>
              )}

              {/* Desktop-only Form Wrapper with resize bar and centering */}
              {(!showDeviceFrame || devicePreview === 'desktop') && (
                <div 
                  className="desktop-form-container"
                  style={{
                    display: 'flex',
                    justifyContent: 'center',
                    width: '100%',
                    minHeight: '200px'
                  }}
                >
                  <div 
                    className={`form-wrapper ${isFormWrapperSelected ? 'form-wrapper-selected' : ''}`}
                    style={{
                      background: formWrapperSettings.backgroundType === 'none' 
                        ? 'transparent'
                        : formWrapperSettings.backgroundType === 'color'
                        ? `${formWrapperSettings.backgroundColor}${Math.round(formWrapperSettings.backgroundOpacity * 255).toString(16).padStart(2, '0')}`
                        : formWrapperSettings.backgroundImage 
                        ? `url(${formWrapperSettings.backgroundImage})`
                        : formWrapperSettings.backgroundColor,
                      backgroundSize: formWrapperSettings.backgroundType === 'image' ? 'cover' : 'auto',
                      backgroundPosition: formWrapperSettings.backgroundType === 'image' ? 'center' : 'auto',
                      backgroundRepeat: formWrapperSettings.backgroundType === 'image' ? 'no-repeat' : 'auto',
                      padding: `${formWrapperSettings[devicePreview]?.padding?.top || 0}px ${formWrapperSettings[devicePreview]?.padding?.right || 0}px ${formWrapperSettings[devicePreview]?.padding?.bottom || 0}px ${formWrapperSettings[devicePreview]?.padding?.left || 0}px`,
                      margin: `${formWrapperSettings[devicePreview]?.margin?.top || 0}px ${formWrapperSettings[devicePreview]?.margin?.right || 0}px ${formWrapperSettings[devicePreview]?.margin?.bottom || 0}px ${formWrapperSettings[devicePreview]?.margin?.left || 0}px`,
                      borderRadius: 'var(--radius-lg)',
                      minHeight: '200px',
                      width: formWrapperWidth,
                      position: 'relative'
                    }}
                  >
                    {/* Desktop-only Form Wrapper Resize Bar */}
                    <DraggableResizeBar
                      position="right"
                      currentWidth={formWrapperWidth}
                      showInput={showFormWidthInput}
                      onInputVisibilityChange={setShowFormWidthInput}
                      onResize={(width) => setFormWrapperWidth(width)}
                      maxWidth={undefined} // No max width restrictions in desktop mode
                    />
                    {orderedElements.length === 0 ? (
                      <div
                        className={`drop-zone ${isDragging ? 'drop-zone-active' : ''}`}
                        onDragOver={(e) => handleDragOver(e, 0)}
                        onDrop={(e) => handleDrop(e, 0)}
                      >
                        <Layers size={24} className="mb-2" />
                        <p>Drag elements from the bottom tray to start building your form</p>
                      </div>
                    ) : (
                      <div>
                        {orderedElements.map((element, index) => (
                        <React.Fragment key={element.id}>
                          {isDragging && dragOverIndex === index && (
                            <div className="drop-zone drop-zone-hover" />
                          )}
                          
                          <div
                            className={`form-element ${
                              selectedElement?.id === element.id || multiSelectElements.includes(element.id) 
                                ? 'form-element-selected' : ''
                            } ${isDragging && draggedElement?.id === element.id ? 'form-element-dragging' : ''}`}
                            data-element-id={element.id}
                            draggable
                            onDragStart={(e) => handleDragStart(e, element)}
                            onDragEnd={handleDragEnd}
                            onDragOver={(e) => handleDragOver(e, index + 1)}
                            onDrop={(e) => handleDrop(e, index + 1)}
                            onClick={(e) => handleSelectElement(element.id, e)}
                            onContextMenu={(e) => handleContextMenu(e, element.id)}
                          >
                            <div className="element-controls">
                              <button className="element-control-btn" title="Drag to reorder">
                                <Move size={16} />
                              </button>
                              <button className="element-control-btn" title="Edit Properties">
                                <Settings size={16} />
                              </button>
                              <button 
                                className="element-control-btn"
                                title="Delete"
                                onClick={(e) => {
                                  e.stopPropagation();
                                  handleDeleteElement(element.id);
                                }}
                              >
                                <Trash2 size={16} />
                              </button>
                            </div>

                            {/* Enhanced Element Preview */}
                            <div className="mb-2">
                              <InlineEditableText
                                value={element.properties?.label || element.label}
                                onChange={(value) => updateElementProperty(element.id, 'label', value)}
                                className="property-label"
                                placeholder="Field Label"
                                onFormat={handleTextFormat}
                              />
                              {element.properties?.required && <span className="text-red-500 ml-1">*</span>}
                              {element.properties?.helperText && (
                                <InlineEditableText
                                  value={element.properties.helperText}
                                  onChange={(value) => updateElementProperty(element.id, 'helperText', value)}
                                  className="property-help"
                                  placeholder="Helper text"
                                />
                              )}
                            </div>

                            {/* Render enhanced element preview */}
                            <ElementRenderer 
                              element={element} 
                              updateElementProperty={updateElementProperty}
                            />
                          </div>
                          
                          {isDragging && dragOverIndex === orderedElements.length && index === orderedElements.length - 1 && (
                            <div className="drop-zone drop-zone-hover" />
                          )}
                        </React.Fragment>
                      ))}
                      
                      {/* Submit button with inline editing */}
                      <div className="mt-6">
                        <InlineEditableText
                          value="Submit"
                          onChange={() => {}}
                          className="w-full px-4 py-2 bg-blue-500 text-white rounded-md font-medium text-center"
                          placeholder="Submit"
                        />
                      </div>
                    </div>
                  )}
                  </div>
                </div>
              )}
              </div>
            </div>
          </div>
        )}
        </div>

        {/* Enhanced Bottom Element Tray with ALL missing elements */}
        <ResizableBottomTray
          isCollapsed={isTrayCollapsed}
          onToggleCollapse={() => setIsTrayCollapsed(!isTrayCollapsed)}
          isMobile={isMobile}
          onHeightChange={handleTrayHeightChange}
        >
          {/* Enhanced Tray Header with Search and Controls */}
          <div className="flex items-center justify-between px-4 py-2 border-b border-border">
            <div className="flex items-center gap-4">
              {/* Categories positioned at the start */}
              <div className="flex gap-2">
                {!searchQuery && ELEMENT_CATEGORIES.map(category => (
                  <button
                    key={category.id}
                    className={cn(
                      "px-3 py-2 rounded-md text-xs font-medium transition-colors",
                      activeTrayCategory === category.id
                        ? "bg-primary text-primary-foreground hover:bg-primary/90"
                        : "text-muted-foreground bg-transparent hover:bg-accent hover:text-foreground"
                    )}
                    onClick={() => setActiveTrayCategory(category.id)}
                  >
                    {category.name}
                  </button>
                ))}
              </div>

              {/* Search input positioned after categories */}
              <div className="relative w-[200px]">
                <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                <input
                  type="text"
                  placeholder="Search elements..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' && searchQuery.trim()) {
                      const filteredElements = getFilteredElements();
                      if (filteredElements.length === 1) {
                        e.preventDefault();
                        const element = filteredElements[0];
                        handleAddElement(element.type, element.label, element.icon);
                        setSearchQuery(''); // Clear search after adding
                      }
                    }
                  }}
                  className="w-full h-8 pl-9 pr-8 text-sm border border-border rounded-md bg-background focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                />
                {searchQuery && (
                  <button
                    onClick={() => setSearchQuery('')}
                    className="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded hover:bg-accent text-muted-foreground hover:text-foreground"
                    title="Clear search"
                  >
                    <X size={14} />
                  </button>
                )}
              </div>
            </div>
          </div>

          {/* Enhanced Elements Container with Dynamic Layout */}
          <div
            className="relative overflow-hidden flex-1"
            style={{ height: `${elementsAreaHeight}px` }}
          >
            {/* Scrollable Elements Area */}
            <div
              ref={trayScrollRef}
              className={cn(
                "h-full overflow-y-auto px-4 py-3",
                isMobile && "tray-elements-mobile"
              )}
            >
              <div
                className={cn(
                  "gap-3 items-start",
                  isHorizontalLayout ? "flex flex-nowrap gap-3" : "grid grid-cols-[repeat(auto-fill,minmax(120px,1fr))] gap-3"
                )}
              >
                {getFilteredElements().map(element => {
                  const filteredElements = getFilteredElements();
                  const isSingleElement = filteredElements.length === 1 && searchQuery.trim();
                  return (
                    <div
                      key={element.type}
                      className={cn(
                        "flex flex-col items-center justify-center gap-2 p-3 min-w-[120px] min-h-[80px]",
                        "bg-white border border-border rounded-lg cursor-grab select-none",
                        "transition-all hover:border-primary/30 hover:bg-primary/5 hover:-translate-y-0.5 hover:shadow-sm",
                        "active:cursor-grabbing active:-translate-y-0.5",
                        isSingleElement && "bg-primary/10 border-primary/40 shadow-sm"
                      )}
                      draggable
                      onDragStart={(e) => handleDragStart(e, element, true)}
                      onDragEnd={handleDragEnd}
                      onClick={() => handleAddElement(element.type, element.label, element.icon)}
                    >
                      <element.icon className="w-6 h-6 text-primary" />
                      <span className="text-xs font-medium text-foreground text-center leading-tight">{element.label}</span>
                      {isSingleElement && (
                        <div className="absolute -top-9 left-1/2 -translate-x-1/2 px-2 py-1 bg-popover border border-border rounded shadow-md text-xs whitespace-nowrap opacity-100 transition-opacity">
                          Press Enter to add
                        </div>
                      )}
                    </div>
                  );
                })}

                {getFilteredElements().length === 0 && searchQuery && (
                  <div className="flex flex-col items-center justify-center gap-2 py-8 text-muted-foreground">
                    <Search size={24} className="opacity-50" />
                    <span className="text-sm font-medium">No elements found for "{searchQuery}"</span>
                  </div>
                )}
              </div>
            </div>

            {/* Left Scroll Button - always render in horizontal layout, use opacity for visibility */}
            {isHorizontalLayout && (
              <button
                className={cn(
                  "absolute top-1/2 -translate-y-1/2 left-0 z-[100]",
                  "w-10 h-14 flex items-center justify-center",
                  "bg-white/95 backdrop-blur-sm border border-border border-l-0",
                  "rounded-r-lg pl-2 cursor-pointer",
                  "shadow-[0_4px_20px_rgba(0,0,0,0.15),0_1px_3px_rgba(0,0,0,0.1)]",
                  "text-foreground transition-all duration-200",
                  "hover:bg-primary-50 hover:border-primary-300 hover:text-primary-600 hover:shadow-md",
                  canScrollLeft ? "opacity-100" : "opacity-0 pointer-events-none"
                )}
                onClick={() => scrollTray('left')}
                onMouseEnter={() => startContinuousScroll('left')}
                onMouseLeave={stopContinuousScroll}
                title="Scroll left"
              >
                <ChevronLeft size={20} />
              </button>
            )}

            {/* Right Scroll Button - always render in horizontal layout, use opacity for visibility */}
            {isHorizontalLayout && (
              <button
                className={cn(
                  "absolute top-1/2 -translate-y-1/2 right-0 z-[100]",
                  "w-10 h-14 flex items-center justify-center",
                  "bg-white/95 backdrop-blur-sm border border-border border-r-0",
                  "rounded-l-lg pr-2 cursor-pointer",
                  "shadow-[0_4px_20px_rgba(0,0,0,0.15),0_1px_3px_rgba(0,0,0,0.1)]",
                  "text-foreground transition-all duration-200",
                  "hover:bg-primary-50 hover:border-primary-300 hover:text-primary-600 hover:shadow-md",
                  canScrollRight ? "opacity-100" : "opacity-0 pointer-events-none"
                )}
                onClick={() => scrollTray('right')}
                onMouseEnter={() => startContinuousScroll('right')}
                onMouseLeave={stopContinuousScroll}
                title="Scroll right"
              >
                <ChevronRight size={20} />
              </button>
            )}

            {/* Shadow Overlays - always render in horizontal layout, use opacity for visibility */}
            {isHorizontalLayout && (
              <div className={cn(
                "absolute top-0 bottom-0 left-0 w-10 pointer-events-none z-[50]",
                "bg-gradient-to-r from-black/10 to-transparent",
                "transition-opacity duration-200",
                canScrollLeft ? "opacity-100" : "opacity-0"
              )} />
            )}
            {isHorizontalLayout && (
              <div className={cn(
                "absolute top-0 bottom-0 right-0 w-10 pointer-events-none z-[50]",
                "bg-gradient-to-l from-black/10 to-transparent",
                "transition-opacity duration-200",
                canScrollRight ? "opacity-100" : "opacity-0"
              )} />
            )}

            {/* Hover Overlay Layer - for clipping-free hover effects in horizontal layout */}
          </div>
        </ResizableBottomTray>

        {/* Schema-Driven Floating Properties Panel */}
        {floatingPanel && floatingPanel.element && (
          <FloatingPanel
            element={floatingPanel.element}
            position={floatingPanel.position}
            onClose={() => setFloatingPanel(null)}
            onPropertyChange={(property, value) => updateElementProperty(floatingPanel.element.id, property, value)}
            onDelete={() => handleDeleteElement(floatingPanel.element.id)}
          />
        )}

        {/* Form Wrapper Settings Panel */}
        {floatingPanel && !floatingPanel.element && isFormWrapperSelected && (
          <FormWrapperSettingsPanel
            position={floatingPanel.position}
            settings={formWrapperSettings}
            devicePreview={devicePreview}
            onClose={() => {
              setFloatingPanel(null);
              setIsFormWrapperSelected(false);
            }}
            onUpdate={(property, value) => {
              if (property.includes('.')) {
                // Handle device-specific nested properties like "desktop.padding"
                const [device, prop] = property.split('.');
                setFormWrapperSettings(prev => ({
                  ...prev,
                  [device]: {
                    ...prev[device as keyof typeof prev],
                    [prop]: value
                  }
                }));
              } else {
                // Handle top-level properties like "backgroundColor"
                setFormWrapperSettings(prev => ({
                  ...prev,
                  [property]: value
                }));
              }
            }}
          />
        )}

        {/* Enhanced Context Menu */}
        {contextMenu && (
          <ContextMenu
            x={contextMenu.x}
            y={contextMenu.y}
            onClose={() => setContextMenu(null)}
            onAction={handleContextAction}
          />
        )}

        {/* Additional Panels */}
        <VersionHistoryPanel
          isOpen={showVersionHistory}
          onClose={() => setShowVersionHistory(false)}
          onRestore={handleVersionRestore}
        />

        <SharePanel
          isOpen={showSharePanel}
          onClose={() => setShowSharePanel(false)}
        />

        <ExportPanel
          isOpen={showExportPanel}
          onClose={() => setShowExportPanel(false)}
          onExport={handleExport}
        />

        <AnalyticsPanel
          isOpen={showAnalytics}
          onClose={() => setShowAnalytics(false)}
        />

        <FloatingElementsPanel
          isVisible={showElementsPanel}
          position={elementsPanelPosition}
          isCollapsed={isElementsPanelCollapsed}
          onClose={() => setShowElementsPanel(false)}
          onPositionChange={setElementsPanelPosition}
          onCollapse={setIsElementsPanelCollapsed}
          onElementClick={(elementId) => {
            // TODO: Select element and open properties panel
            console.log('Element clicked:', elementId);
          }}
        />
        {/* Toast Notifications */}
        <div className="toast-container">
          {toasts.map((toast) => (
            <Toast
              key={toast.id}
              id={toast.id}
              type={toast.type}
              message={toast.message}
              visible={toast.visible}
              hiding={toast.hiding}
              onClose={hideToast}
            />
          ))}
        </div>
      </div>
    </ErrorBoundary>
  );
};

// Wrapper component with ToastProvider
export const FormBuilderV2: React.FC<FormBuilderCompleteProps> = (props) => {
  return (
    <ToastProvider>
      <FormBuilderCompleteInner {...props} />
    </ToastProvider>
  );
};

// Keep old name for backward compatibility
export const FormBuilderComplete = FormBuilderV2;