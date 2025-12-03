import React from 'react';
import { FileCode, Database, Code, FileText, Download } from 'lucide-react';
import { ExportPanelProps, ExportOption } from '../types/panel.types';
import { BasePanel } from './BasePanel';

const defaultExportOptions: ExportOption[] = [
  { 
    type: 'html', 
    label: 'HTML Export', 
    icon: <FileCode size={24} />,
    description: 'Export as standalone HTML file with embedded styles'
  },
  { 
    type: 'json', 
    label: 'JSON Export', 
    icon: <Database size={24} />,
    description: 'Export form structure as JSON data'
  },
  { 
    type: 'react', 
    label: 'React Component', 
    icon: <Code size={24} />,
    description: 'Export as a React component with TypeScript'
  },
  { 
    type: 'pdf', 
    label: 'PDF Export', 
    icon: <FileText size={24} />,
    description: 'Export form as a printable PDF document'
  }
];

export const ExportPanel: React.FC<ExportPanelProps> = ({
  isOpen,
  onClose,
  onExport,
  exportOptions = defaultExportOptions,
  ...basePanelProps
}) => {
  const handleExport = (type: string) => {
    onExport(type);
    // Optionally close the panel after export
    setTimeout(() => onClose(), 500);
  };

  return (
    <BasePanel
      isOpen={isOpen}
      onClose={onClose}
      title="Export Form"
      size="md"
      {...basePanelProps}
    >
      <div className="export-content">
        <p className="text-sm text-gray-600 mb-6">
          Choose a format to export your form. Each format is optimized for different use cases.
        </p>
        
        <div className="export-options">
          {exportOptions.map((option) => (
            <button
              key={option.type}
              className="export-option"
              onClick={() => handleExport(option.type)}
            >
              <div className="export-option-icon">
                {option.icon}
              </div>
              <div className="export-option-content">
                <span className="export-option-label">{option.label}</span>
                {option.description && (
                  <span className="export-option-description">{option.description}</span>
                )}
              </div>
              <Download size={16} className="export-option-action" />
            </button>
          ))}
        </div>

        <div className="mt-6 p-4 bg-blue-50 rounded-lg">
          <h4 className="text-sm font-medium text-blue-900 mb-1">Pro Tip</h4>
          <p className="text-sm text-blue-700">
            Use JSON export to backup your form structure or migrate it to another system. 
            HTML export is perfect for embedding in static websites.
          </p>
        </div>
      </div>
    </BasePanel>
  );
};