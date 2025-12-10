import React, { useState } from 'react';
import { Clock, RotateCcw, User, FileText, ChevronRight } from 'lucide-react';
import { VersionHistoryPanelProps, FormVersion } from '../types/panel.types';
import { BasePanel } from './BasePanel';
import { toast } from 'sonner';
import { Button } from '../../../../../components/ui/button';

const defaultVersions: FormVersion[] = [
  { 
    id: '1', 
    name: 'Current Version', 
    timestamp: Date.now(), 
    isCurrent: true,
    author: 'John Doe',
    changes: 'Added new fields and validation rules'
  },
  { 
    id: '2', 
    name: 'Added validation rules', 
    timestamp: Date.now() - 3600000,
    author: 'Jane Smith',
    changes: 'Implemented email validation and required fields'
  },
  { 
    id: '3', 
    name: 'Initial version', 
    timestamp: Date.now() - 7200000,
    author: 'John Doe',
    changes: 'Created form with basic fields'
  }
];

export const VersionHistoryPanel: React.FC<VersionHistoryPanelProps> = ({
  isOpen,
  onClose,
  versions = defaultVersions,
  onRestore,
  ...basePanelProps
}) => {
  const [selectedVersion, setSelectedVersion] = useState<string | null>(null);
  const [showComparison, setShowComparison] = useState(false);

  const handleRestore = (versionId: string) => {
    const version = versions.find(v => v.id === versionId);
    if (version && !version.isCurrent) {
      onRestore(versionId);
      toast.success(`Restored to version: ${version.name}`);
      setTimeout(() => onClose(), 1000);
    }
  };

  const formatTimestamp = (timestamp: number) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    
    if (diff < 3600000) {
      return `${Math.floor(diff / 60000)} minutes ago`;
    } else if (diff < 86400000) {
      return `${Math.floor(diff / 3600000)} hours ago`;
    } else {
      return date.toLocaleDateString() + ' at ' + date.toLocaleTimeString();
    }
  };

  return (
    <BasePanel
      isOpen={isOpen}
      onClose={onClose}
      title="Version History"
      size="md"
      {...basePanelProps}
    >
      <div className="version-history-content">
        <div className="version-history-header">
          <p className="text-sm text-gray-600">
            Track changes and restore previous versions of your form
          </p>
          <label className="flex items-center gap-2 mt-2">
            <input 
              type="checkbox" 
              checked={showComparison}
              onChange={(e) => setShowComparison(e.target.checked)}
              className="form-checkbox"
            />
            <span className="text-sm">Show version comparison</span>
          </label>
        </div>

        <div className="version-list">
          {versions.map((version) => (
            <div
              key={version.id}
              className={`version-item ${version.isCurrent ? 'version-item-current' : ''} 
                         ${selectedVersion === version.id ? 'version-item-selected' : ''}`}
              onClick={() => setSelectedVersion(version.id)}
            >
              <div className="version-item-header">
                <div className="version-info">
                  <h4 className="version-name">
                    {version.name}
                    {version.isCurrent && (
                      <span className="version-badge">Current</span>
                    )}
                  </h4>
                  <div className="version-meta">
                    <Clock size={12} />
                    <span>{formatTimestamp(version.timestamp)}</span>
                    {version.author && (
                      <>
                        <User size={12} />
                        <span>{version.author}</span>
                      </>
                    )}
                  </div>
                </div>
                <ChevronRight 
                  size={16} 
                  className={`version-chevron ${selectedVersion === version.id ? 'rotate-90' : ''}`}
                />
              </div>
              
              {selectedVersion === version.id && (
                <div className="version-details">
                  {version.changes && (
                    <div className="version-changes">
                      <FileText size={14} />
                      <p>{version.changes}</p>
                    </div>
                  )}
                  
                  {!version.isCurrent && (
                    <div className="version-actions">
                      <Button
                        size="sm"
                        onClick={(e) => {
                          e.stopPropagation();
                          handleRestore(version.id);
                        }}
                        data-testid={`restore-version-${version.id}`}
                      >
                        <RotateCcw size={14} />
                        Restore This Version
                      </Button>
                      {showComparison && (
                        <Button variant="outline" size="sm" data-testid={`compare-version-${version.id}`}>
                          Compare with Current
                        </Button>
                      )}
                    </div>
                  )}
                </div>
              )}
            </div>
          ))}
        </div>

        <div className="version-history-footer">
          <p className="text-xs text-gray-500">
            Version history is automatically saved when you make changes to your form.
            Older versions may be archived after 30 days.
          </p>
        </div>
      </div>
    </BasePanel>
  );
};