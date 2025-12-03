/**
 * SendEmailModal Component
 * Full-screen modal for configuring Send Email action nodes
 * Integrates the visual email builder with node settings header
 */

import { useState, useEffect, useCallback } from 'react';
import { X, Mail, Power, ChevronDown, ChevronUp, Save } from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import EmailClientBuilder from '@/components/email-builder/Preview/EmailClientBuilder';
import useEmailStore from '@/components/email-builder/hooks/useEmailStore';
import type { WorkflowNode } from '../types/workflow.types';

interface SendEmailModalProps {
  isOpen: boolean;
  onClose: () => void;
  node: WorkflowNode;
  onUpdateNode: (nodeId: string, updates: Partial<WorkflowNode>) => void;
}

export function SendEmailModal({
  isOpen,
  onClose,
  node,
  onUpdateNode,
}: SendEmailModalProps) {
  // Local state for the email configuration
  const [emailConfig, setEmailConfig] = useState({
    to: node.config.to || '',
    subject: node.config.subject || '',
    from: node.config.from || '{admin_email}',
    replyTo: node.config.replyTo || '',
    template: node.config.template || null,
  });

  const [showSettings, setShowSettings] = useState(true);
  const isEnabled = node.config._enabled !== false;

  // Update local state when node changes
  useEffect(() => {
    setEmailConfig({
      to: node.config.to || '',
      subject: node.config.subject || '',
      from: node.config.from || '{admin_email}',
      replyTo: node.config.replyTo || '',
      template: node.config.template || null,
    });
  }, [node.id]);

  /**
   * Handle email builder changes
   */
  const handleBuilderChange = useCallback((templateData: { elements: any[]; html: string }) => {
    setEmailConfig(prev => ({
      ...prev,
      template: templateData,
    }));
  }, []);

  /**
   * Handle field changes
   */
  const handleFieldChange = (field: string, value: string) => {
    setEmailConfig(prev => ({
      ...prev,
      [field]: value,
    }));
  };

  /**
   * Toggle node enabled state
   */
  const handleToggleEnabled = () => {
    onUpdateNode(node.id, {
      config: {
        ...node.config,
        _enabled: !isEnabled,
      },
    });
  };

  /**
   * Save changes and close
   */
  const handleSave = () => {
    onUpdateNode(node.id, {
      config: {
        ...node.config,
        ...emailConfig,
      },
    });
    onClose();
  };

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-w-[95vw] w-[95vw] h-[90vh] max-h-[90vh] p-0 overflow-hidden">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b bg-white">
          <div className="flex items-center gap-4">
            <div className="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
              <Mail className="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <DialogHeader className="p-0 space-y-0">
                <DialogTitle className="text-lg font-semibold">
                  Send Email
                </DialogTitle>
                <DialogDescription className="text-sm text-gray-500">
                  Configure the email that will be sent when this action triggers
                </DialogDescription>
              </DialogHeader>
            </div>
          </div>

          <div className="flex items-center gap-4">
            {/* Enable/Disable Toggle */}
            <div className="flex items-center gap-2">
              <Power className="w-4 h-4 text-gray-500" />
              <span className="text-sm text-gray-600">
                {isEnabled ? 'Enabled' : 'Disabled'}
              </span>
              <button
                onClick={handleToggleEnabled}
                className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                  isEnabled ? 'bg-green-500' : 'bg-gray-300'
                }`}
              >
                <span
                  className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm ${
                    isEnabled ? 'translate-x-6' : 'translate-x-1'
                  }`}
                />
              </button>
            </div>

            {/* Save Button */}
            <button
              onClick={handleSave}
              className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              <Save className="w-4 h-4" />
              Save & Close
            </button>

            {/* Close Button */}
            <button
              onClick={onClose}
              className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <X className="w-5 h-5 text-gray-500" />
            </button>
          </div>
        </div>

        {/* Settings Panel (Collapsible) */}
        <div className="border-b bg-gray-50">
          <button
            onClick={() => setShowSettings(!showSettings)}
            className="w-full flex items-center justify-between px-6 py-3 hover:bg-gray-100 transition-colors"
          >
            <span className="text-sm font-medium text-gray-700">
              Email Settings
            </span>
            {showSettings ? (
              <ChevronUp className="w-4 h-4 text-gray-500" />
            ) : (
              <ChevronDown className="w-4 h-4 text-gray-500" />
            )}
          </button>

          {showSettings && (
            <div className="px-6 pb-4 grid grid-cols-2 gap-4">
              {/* To Field */}
              <div className="space-y-1">
                <label className="block text-sm font-medium text-gray-700">
                  To
                </label>
                <input
                  type="text"
                  value={emailConfig.to}
                  onChange={(e) => handleFieldChange('to', e.target.value)}
                  placeholder="{email} or recipient@example.com"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p className="text-xs text-gray-500">
                  Use {'{field_name}'} to insert form field values
                </p>
              </div>

              {/* Subject Field */}
              <div className="space-y-1">
                <label className="block text-sm font-medium text-gray-700">
                  Subject
                </label>
                <input
                  type="text"
                  value={emailConfig.subject}
                  onChange={(e) => handleFieldChange('subject', e.target.value)}
                  placeholder="Email subject line"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              {/* From Field */}
              <div className="space-y-1">
                <label className="block text-sm font-medium text-gray-700">
                  From
                </label>
                <input
                  type="text"
                  value={emailConfig.from}
                  onChange={(e) => handleFieldChange('from', e.target.value)}
                  placeholder="{admin_email}"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              {/* Reply-To Field */}
              <div className="space-y-1">
                <label className="block text-sm font-medium text-gray-700">
                  Reply-To
                </label>
                <input
                  type="text"
                  value={emailConfig.replyTo}
                  onChange={(e) => handleFieldChange('replyTo', e.target.value)}
                  placeholder="Optional reply-to address"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>
          )}
        </div>

        {/* Email Builder - Note: EmailClientBuilder uses useEmailStore internally */}
        {/* We need to set the active email in the store before rendering */}
        <EmailBuilderWrapper
          node={node}
          emailConfig={emailConfig}
          onChange={handleBuilderChange}
        />
      </DialogContent>
    </Dialog>
  );
}

/**
 * Wrapper component that manages email store state for the modal
 */
function EmailBuilderWrapper({
  node,
  emailConfig,
  onChange,
}: {
  node: WorkflowNode;
  emailConfig: any;
  onChange: (data: { elements: any[]; html: string }) => void;
}) {
  const { initializeStore, activeEmailId, emails, reset } = useEmailStore();

  // Create a temporary email for the builder
  useEffect(() => {
    const tempEmail = {
      id: `temp-${node.id}`,
      description: 'Email Template',
      subject: emailConfig.subject,
      to: emailConfig.to,
      from: emailConfig.from,
      replyTo: emailConfig.replyTo,
      body: emailConfig.template?.html || '',
      body_type: 'visual',
      template: emailConfig.template || { elements: [], html: '' },
    };

    // Set this as the active email in the store using initializeStore
    initializeStore({ emails: [tempEmail] });

    return () => {
      // Cleanup: reset the store
      reset();
    };
  }, [node.id, emailConfig, initializeStore, reset]);

  // Watch for changes in the email store and propagate them up
  useEffect(() => {
    if (activeEmailId && emails.length > 0) {
      const email = emails.find((e) => e.id === activeEmailId);
      if (email?.template) {
        onChange({
          elements: email.template.elements || [],
          html: email.template.html || '',
        });
      }
    }
  }, [emails, activeEmailId, onChange]);

  return (
    <div className="flex-1 overflow-hidden">
      <EmailClientBuilder />
    </div>
  );
}

export default SendEmailModal;
