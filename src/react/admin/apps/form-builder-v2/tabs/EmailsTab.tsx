/**
 * Emails Tab - Email notification configuration
 * Lazy loaded via React.lazy()
 */

import React from 'react';
import { Mail, Plus } from 'lucide-react';
import { Button } from '../../../components/ui/button';

export default function EmailsTab() {
  return (
    <div className="flex-1 overflow-auto p-4">
      <div className="flex items-center justify-between mb-4">
        <h3 className="font-semibold">Email Notifications</h3>
        <Button size="sm" data-testid="add-email-button">
          <Plus size={14} />
          Add Email
        </Button>
      </div>
      <p className="text-sm text-muted-foreground mb-4">
        Configure email notifications sent when this form is submitted.
      </p>
      <div className="border border-dashed border-border rounded-lg p-8 text-center">
        <Mail className="w-12 h-12 mx-auto text-muted-foreground/50 mb-3" />
        <p className="text-sm text-muted-foreground">
          No email notifications configured yet.
        </p>
        <p className="text-xs text-muted-foreground/70 mt-1">
          Click "Add Email" to create your first notification.
        </p>
      </div>
    </div>
  );
}
