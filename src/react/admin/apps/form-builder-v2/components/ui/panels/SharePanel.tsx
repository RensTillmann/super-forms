import React, { useState } from 'react';
import { Copy, Plus, Code, ExternalLink, Layout, Users, Globe, Link } from 'lucide-react';
import { SharePanelProps, Collaborator, EmbedOption } from '../types/panel.types';
import { BasePanel } from './BasePanel';
import { useToast } from '../toast';

const defaultEmbedOptions: EmbedOption[] = [
  { type: 'embed', label: 'Embed Code', icon: <Code size={20} /> },
  { type: 'popup', label: 'Popup', icon: <ExternalLink size={20} /> },
  { type: 'inline', label: 'Inline', icon: <Layout size={20} /> }
];

export const SharePanel: React.FC<SharePanelProps> = ({
  isOpen,
  onClose,
  formUrl = 'https://forms.example.com/contact-form',
  onInviteCollaborator,
  collaborators = [],
  embedOptions = defaultEmbedOptions,
  ...basePanelProps
}) => {
  const [activeTab, setActiveTab] = useState<'link' | 'collaborate' | 'embed'>('link');
  const { addToast } = useToast();

  const copyToClipboard = async (text: string) => {
    try {
      await navigator.clipboard.writeText(text);
      addToast('Copied to clipboard!', 'success');
    } catch (err) {
      addToast('Failed to copy', 'error');
    }
  };

  const handleRemoveCollaborator = (collaboratorId: string) => {
    // Handle collaborator removal
    addToast('Collaborator removed', 'success');
  };

  const handleEmbedOption = (type: string) => {
    // Handle embed option selection
    addToast(`${type} embed code copied`, 'success');
  };

  return (
    <BasePanel
      isOpen={isOpen}
      onClose={onClose}
      title="Share & Collaborate"
      size="md"
      {...basePanelProps}
    >
      <div className="share-content">
        {/* Tab Navigation */}
        <div className="share-tabs">
          <button
            className={`share-tab ${activeTab === 'link' ? 'share-tab-active' : ''}`}
            onClick={() => setActiveTab('link')}
          >
            <Link size={16} />
            Public Link
          </button>
          <button
            className={`share-tab ${activeTab === 'collaborate' ? 'share-tab-active' : ''}`}
            onClick={() => setActiveTab('collaborate')}
          >
            <Users size={16} />
            Collaborate
          </button>
          <button
            className={`share-tab ${activeTab === 'embed' ? 'share-tab-active' : ''}`}
            onClick={() => setActiveTab('embed')}
          >
            <Code size={16} />
            Embed
          </button>
        </div>

        {/* Tab Content */}
        {activeTab === 'link' && (
          <div className="share-section">
            <p className="text-sm text-gray-600 mb-4">
              Share this link to allow anyone to fill out your form
            </p>
            <div className="share-link-input">
              <input
                type="text"
                value={formUrl}
                readOnly
                className="form-input"
              />
              <button 
                className="btn btn-sm"
                onClick={() => copyToClipboard(formUrl)}
              >
                <Copy size={16} />
                Copy
              </button>
            </div>
            
            <div className="mt-4">
              <label className="flex items-center gap-2">
                <input type="checkbox" className="form-checkbox" />
                <span className="text-sm">Make form public</span>
              </label>
            </div>
          </div>
        )}

        {activeTab === 'collaborate' && (
          <div className="share-section">
            <div className="collaborator-list">
              {collaborators.map((collaborator) => (
                <div key={collaborator.id} className="collaborator-item">
                  <div className="collaborator-avatar">
                    {collaborator.avatar ? (
                      <img src={collaborator.avatar} alt={collaborator.name} />
                    ) : (
                      collaborator.initials || collaborator.name.substring(0, 2).toUpperCase()
                    )}
                  </div>
                  <div className="collaborator-info">
                    <span className="collaborator-name">{collaborator.name}</span>
                    <span className="collaborator-role">{collaborator.role}</span>
                  </div>
                  {collaborator.role !== 'owner' && (
                    <button 
                      className="btn btn-sm"
                      onClick={() => handleRemoveCollaborator(collaborator.id)}
                    >
                      Remove
                    </button>
                  )}
                </div>
              ))}
            </div>
            
            {onInviteCollaborator && (
              <button 
                className="btn btn-outline w-full mt-4"
                onClick={onInviteCollaborator}
              >
                <Plus size={16} />
                Invite Collaborator
              </button>
            )}
          </div>
        )}

        {activeTab === 'embed' && (
          <div className="share-section">
            <p className="text-sm text-gray-600 mb-4">
              Choose how you want to embed this form on your website
            </p>
            <div className="embed-options">
              {embedOptions.map((option) => (
                <button
                  key={option.type}
                  className="embed-option"
                  onClick={() => handleEmbedOption(option.type)}
                >
                  {option.icon}
                  <span>{option.label}</span>
                </button>
              ))}
            </div>
            
            <div className="mt-4 p-4 bg-gray-50 rounded">
              <h4 className="text-sm font-medium mb-2">Embed Settings</h4>
              <div className="space-y-2">
                <label className="flex items-center gap-2">
                  <input type="checkbox" className="form-checkbox" defaultChecked />
                  <span className="text-sm">Show form title</span>
                </label>
                <label className="flex items-center gap-2">
                  <input type="checkbox" className="form-checkbox" defaultChecked />
                  <span className="text-sm">Show form description</span>
                </label>
                <label className="flex items-center gap-2">
                  <input type="checkbox" className="form-checkbox" />
                  <span className="text-sm">Auto-resize height</span>
                </label>
              </div>
            </div>
          </div>
        )}
      </div>
    </BasePanel>
  );
};