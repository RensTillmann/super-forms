import React from 'react';
import { PropertyField } from '../shared';
import { X, Plus } from 'lucide-react';
import { v4 as uuidv4 } from 'uuid';

interface ContainerPropertiesProps {
  element: any;
  onUpdate: (property: string, value: any) => void;
}

export const ContainerProperties: React.FC<ContainerPropertiesProps> = ({ 
  element, 
  onUpdate 
}) => {
  const renderColumnsProperties = () => (
    <>
      <PropertyField label="Number of Columns">
        <select
          value={element.properties?.columnCount || 2}
          onChange={(e) => onUpdate('columnCount', parseInt(e.target.value))}
          className="form-input"
        >
          <option value={1}>1 Column</option>
          <option value={2}>2 Columns</option>
          <option value={3}>3 Columns</option>
          <option value={4}>4 Columns</option>
        </select>
      </PropertyField>

      <PropertyField label="Gap Between Columns">
        <input
          type="text"
          value={element.properties?.gap || '20px'}
          onChange={(e) => onUpdate('gap', e.target.value)}
          placeholder="20px"
          className="form-input"
        />
      </PropertyField>
    </>
  );

  const renderTabsProperties = () => (
    <>
      <PropertyField label="Tab Position">
        <select
          value={element.properties?.tabPosition || 'top'}
          onChange={(e) => onUpdate('tabPosition', e.target.value)}
          className="form-input"
        >
          <option value="top">Top</option>
          <option value="bottom">Bottom</option>
          <option value="left">Left</option>
          <option value="right">Right</option>
        </select>
      </PropertyField>

      <PropertyField label="Tabs">
        <div className="space-y-2">
          {element.properties?.tabs?.map((tab: any, index: number) => (
            <div key={tab.id} className="flex gap-2">
              <input
                type="text"
                value={tab.title}
                onChange={(e) => {
                  const newTabs = [...(element.properties.tabs || [])];
                  newTabs[index] = { ...tab, title: e.target.value };
                  onUpdate('tabs', newTabs);
                }}
                className="form-input flex-1"
                placeholder={`Tab ${index + 1}`}
              />
              <button
                onClick={() => {
                  const newTabs = element.properties.tabs?.filter((_: any, i: number) => i !== index);
                  onUpdate('tabs', newTabs);
                }}
                className="btn btn-ghost btn-icon text-red-500"
              >
                <X size={14} />
              </button>
            </div>
          ))}
          <button
            onClick={() => {
              const newTabs = [...(element.properties.tabs || []), { 
                id: uuidv4(), 
                title: `Tab ${(element.properties.tabs?.length || 0) + 1}`, 
                active: false 
              }];
              onUpdate('tabs', newTabs);
            }}
            className="btn btn-ghost text-sm"
          >
            <Plus size={14} />
            Add Tab
          </button>
        </div>
      </PropertyField>
    </>
  );

  const renderAccordionProperties = () => (
    <>
      <PropertyField label="Allow Multiple Open">
        <input
          type="checkbox"
          checked={element.properties?.allowMultiple || false}
          onChange={(e) => onUpdate('allowMultiple', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      <PropertyField label="Sections">
        <div className="space-y-2">
          {element.properties?.sections?.map((section: any, index: number) => (
            <div key={section.id} className="flex gap-2">
              <input
                type="text"
                value={section.title}
                onChange={(e) => {
                  const newSections = [...(element.properties.sections || [])];
                  newSections[index] = { ...section, title: e.target.value };
                  onUpdate('sections', newSections);
                }}
                className="form-input flex-1"
                placeholder={`Section ${index + 1}`}
              />
              <button
                onClick={() => {
                  const newSections = element.properties.sections?.filter((_: any, i: number) => i !== index);
                  onUpdate('sections', newSections);
                }}
                className="btn btn-ghost btn-icon text-red-500"
              >
                <X size={14} />
              </button>
            </div>
          ))}
          <button
            onClick={() => {
              const newSections = [...(element.properties.sections || []), { 
                id: uuidv4(), 
                title: `Section ${(element.properties.sections?.length || 0) + 1}`, 
                expanded: false 
              }];
              onUpdate('sections', newSections);
            }}
            className="btn btn-ghost text-sm"
          >
            <Plus size={14} />
            Add Section
          </button>
        </div>
      </PropertyField>
    </>
  );

  const renderSectionProperties = () => (
    <>
      <PropertyField label="Legend/Title">
        <input
          type="text"
          value={element.properties?.legend || ''}
          onChange={(e) => onUpdate('legend', e.target.value)}
          placeholder="Section title"
          className="form-input"
        />
      </PropertyField>

      <PropertyField label="Show Border">
        <input
          type="checkbox"
          checked={element.properties?.showBorder !== false}
          onChange={(e) => onUpdate('showBorder', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      <PropertyField label="Collapsible">
        <input
          type="checkbox"
          checked={element.properties?.collapsible || false}
          onChange={(e) => onUpdate('collapsible', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>
    </>
  );

  const renderCardProperties = () => (
    <>
      <PropertyField label="Show Header">
        <input
          type="checkbox"
          checked={element.properties?.showHeader !== false}
          onChange={(e) => onUpdate('showHeader', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      {element.properties?.showHeader !== false && (
        <PropertyField label="Header Title">
          <input
            type="text"
            value={element.properties?.headerTitle || ''}
            onChange={(e) => onUpdate('headerTitle', e.target.value)}
            placeholder="Card title"
            className="form-input"
          />
        </PropertyField>
      )}

      <PropertyField label="Show Footer">
        <input
          type="checkbox"
          checked={element.properties?.showFooter || false}
          onChange={(e) => onUpdate('showFooter', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      <PropertyField label="Elevation">
        <select
          value={element.properties?.elevation || 'medium'}
          onChange={(e) => onUpdate('elevation', e.target.value)}
          className="form-input"
        >
          <option value="none">None</option>
          <option value="small">Small</option>
          <option value="medium">Medium</option>
          <option value="large">Large</option>
        </select>
      </PropertyField>
    </>
  );

  // Render appropriate properties based on element type
  switch (element.type) {
    case 'columns':
      return renderColumnsProperties();
    case 'tabs':
      return renderTabsProperties();
    case 'accordion':
      return renderAccordionProperties();
    case 'section':
      return renderSectionProperties();
    case 'card':
      return renderCardProperties();
    default:
      return null;
  }
};