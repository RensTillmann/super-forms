import React, { useState } from 'react';
import { Copy, Plus, Code, ExternalLink, Layout, Users, Globe, Link } from 'lucide-react';
import { SharePanelProps, Collaborator, EmbedOption } from '../types/panel.types';
import { BasePanel } from './BasePanel';
import { useToast } from '../toast';
import { Input } from '../../../../../components/ui/input';
import { Button } from '../../../../../components/ui/button';
import { Checkbox } from '../../../../../components/ui/checkbox';
import { Label } from '../../../../../components/ui/label';

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
    addToast('Collaborator removed', 'success');
  };

  const handleEmbedOption = (type: string) => {
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
        <div className="flex gap-1 p-1 bg-muted rounded-lg mb-4">
          <Button
            variant={activeTab === 'link' ? 'secondary' : 'ghost'}
            size="sm"
            className="flex-1"
            onClick={() => setActiveTab('link')}
          >
            <Link className="h-4 w-4 mr-2" />
            Public Link
          </Button>
          <Button
            variant={activeTab === 'collaborate' ? 'secondary' : 'ghost'}
            size="sm"
            className="flex-1"
            onClick={() => setActiveTab('collaborate')}
          >
            <Users className="h-4 w-4 mr-2" />
            Collaborate
          </Button>
          <Button
            variant={activeTab === 'embed' ? 'secondary' : 'ghost'}
            size="sm"
            className="flex-1"
            onClick={() => setActiveTab('embed')}
          >
            <Code className="h-4 w-4 mr-2" />
            Embed
          </Button>
        </div>

        {/* Tab Content */}
        {activeTab === 'link' && (
          <div className="space-y-4">
            <p className="text-sm text-muted-foreground">
              Share this link to allow anyone to fill out your form
            </p>
            <div className="flex gap-2">
              <Input
                type="text"
                value={formUrl}
                readOnly
                className="flex-1"
              />
              <Button
                variant="outline"
                size="sm"
                onClick={() => copyToClipboard(formUrl)}
              >
                <Copy className="h-4 w-4 mr-2" />
                Copy
              </Button>
            </div>

            <div className="flex items-center gap-2">
              <Checkbox id="share-public" />
              <Label htmlFor="share-public" className="text-sm cursor-pointer">
                Make form public
              </Label>
            </div>
          </div>
        )}

        {activeTab === 'collaborate' && (
          <div className="space-y-4">
            <div className="space-y-2">
              {collaborators.map((collaborator) => (
                <div key={collaborator.id} className="flex items-center gap-3 p-2 rounded-lg bg-muted/50">
                  <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-sm font-medium">
                    {collaborator.avatar ? (
                      <img src={collaborator.avatar} alt={collaborator.name} className="h-8 w-8 rounded-full" />
                    ) : (
                      collaborator.initials || collaborator.name.substring(0, 2).toUpperCase()
                    )}
                  </div>
                  <div className="flex-1">
                    <span className="text-sm font-medium">{collaborator.name}</span>
                    <span className="text-xs text-muted-foreground ml-2">{collaborator.role}</span>
                  </div>
                  {collaborator.role !== 'owner' && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => handleRemoveCollaborator(collaborator.id)}
                    >
                      Remove
                    </Button>
                  )}
                </div>
              ))}
            </div>

            {onInviteCollaborator && (
              <Button
                variant="outline"
                className="w-full"
                onClick={onInviteCollaborator}
              >
                <Plus className="h-4 w-4 mr-2" />
                Invite Collaborator
              </Button>
            )}
          </div>
        )}

        {activeTab === 'embed' && (
          <div className="space-y-4">
            <p className="text-sm text-muted-foreground">
              Choose how you want to embed this form on your website
            </p>
            <div className="grid grid-cols-3 gap-2">
              {embedOptions.map((option) => (
                <Button
                  key={option.type}
                  variant="outline"
                  className="flex flex-col items-center gap-2 h-auto py-4"
                  onClick={() => handleEmbedOption(option.type)}
                >
                  {option.icon}
                  <span className="text-xs">{option.label}</span>
                </Button>
              ))}
            </div>

            <div className="p-4 bg-muted rounded-lg space-y-3">
              <h4 className="text-sm font-medium">Embed Settings</h4>
              <div className="space-y-2">
                <div className="flex items-center gap-2">
                  <Checkbox id="embed-title" defaultChecked />
                  <Label htmlFor="embed-title" className="text-sm cursor-pointer">
                    Show form title
                  </Label>
                </div>
                <div className="flex items-center gap-2">
                  <Checkbox id="embed-desc" defaultChecked />
                  <Label htmlFor="embed-desc" className="text-sm cursor-pointer">
                    Show form description
                  </Label>
                </div>
                <div className="flex items-center gap-2">
                  <Checkbox id="embed-resize" />
                  <Label htmlFor="embed-resize" className="text-sm cursor-pointer">
                    Auto-resize height
                  </Label>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </BasePanel>
  );
};
