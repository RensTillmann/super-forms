import { useState, useCallback } from 'react';
import { Send, Loader2, CheckCircle, AlertCircle } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import useEmailStore from '../hooks/useEmailStore';
import { cn } from '@/lib/utils';

interface TestEmailModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

/**
 * Modal dialog for sending test emails
 * Triggered by reply/forward buttons in email preview
 */
function TestEmailModal({ open, onOpenChange }: TestEmailModalProps) {
  const { activeEmailId, emails } = useEmailStore();
  const activeEmail = emails.find(e => e.id === activeEmailId);

  // State
  const [dataType, setDataType] = useState('dummy'); // 'dummy' or 'entry'
  const [entryId, setEntryId] = useState('');
  const [testRecipient, setTestRecipient] = useState(() => {
    return window.sfuiData?.currentUserEmail || '';
  });
  const [status, setStatus] = useState<{ type: 'loading' | 'success' | 'error' | null; message: string }>({ type: null, message: '' });

  /**
   * Send test email via AJAX
   */
  const handleSendTest = useCallback(async () => {
    if (!testRecipient) {
      setStatus({ type: 'error', message: 'Please enter a recipient email address' });
      return;
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(testRecipient)) {
      setStatus({ type: 'error', message: 'Please enter a valid email address' });
      return;
    }

    if (!activeEmail) {
      setStatus({ type: 'error', message: 'No email selected' });
      return;
    }

    // Get WordPress AJAX URL and nonce from global data
    const { formId, ajaxUrl, nonce } = window.sfuiData || {};

    if (!ajaxUrl || !nonce) {
      setStatus({ type: 'error', message: 'WordPress context not available' });
      return;
    }

    setStatus({ type: 'loading', message: 'Sending test email...' });

    try {
      // Prepare email settings from active email
      const emailSettings = {
        from: activeEmail.from_email || '',
        from_name: activeEmail.from_name || '',
        reply: activeEmail.reply_to?.email || '',
        reply_name: activeEmail.reply_to?.name || '',
        cc: activeEmail.cc || '',
        bcc: activeEmail.bcc || '',
        subject: activeEmail.subject || 'Test Email',
        body: activeEmail.body || '',
        attachments: activeEmail.attachments || [],
        // Loop settings for {loop_fields} tag processing
        loop_open: '<table cellpadding="5" style="border-collapse: collapse;">',
        loop: '<tr><th valign="top" align="right" style="padding: 8px; border-bottom: 1px solid #eee;">{loop_label}</th><td style="padding: 8px; border-bottom: 1px solid #eee;">{loop_value}</td></tr>',
        loop_close: '</table>',
        exclude_empty: true,
      };

      // Build form data
      const formData = new FormData();
      formData.append('action', 'super_send_test_email');
      formData.append('nonce', nonce);
      formData.append('form_id', formId || 0);
      formData.append('data_type', dataType);
      formData.append('entry_id', dataType === 'entry' ? entryId : '');
      formData.append('test_recipient', testRecipient);

      // Append email settings as nested object
      Object.entries(emailSettings).forEach(([key, value]) => {
        if (Array.isArray(value)) {
          (value as string[]).forEach((item, index) => {
            formData.append(`email_settings[${key}][${index}]`, item);
          });
        } else {
          formData.append(`email_settings[${key}]`, String(value));
        }
      });

      const response = await fetch(ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
      });

      const result = await response.json();

      if (result.success) {
        setStatus({ type: 'success', message: result.data?.message || 'Test email sent!' });
        // Auto-close after success
        setTimeout(() => {
          onOpenChange(false);
          setStatus({ type: null, message: '' });
        }, 2000);
      } else {
        setStatus({ type: 'error', message: result.data?.message || 'Failed to send test email' });
      }
    } catch (error) {
      console.error('Test email error:', error);
      setStatus({ type: 'error', message: 'Network error - please try again' });
    }
  }, [activeEmail, dataType, entryId, testRecipient, onOpenChange]);

  // Reset state when modal closes
  const handleOpenChange = useCallback((newOpen: boolean) => {
    if (!newOpen) {
      setStatus({ type: null, message: '' });
    }
    onOpenChange(newOpen);
  }, [onOpenChange]);

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Send Test Email</DialogTitle>
          <DialogDescription>
            Send a test version of this email to verify it looks correct.
          </DialogDescription>
        </DialogHeader>

        {/* Recipient Email Input */}
        <div data-testid="recipient-section" className="grid gap-2">
          <Label htmlFor="test-recipient">Recipient Email</Label>
          <Input
            id="test-recipient"
            data-testid="test-recipient-input"
            type="email"
            value={testRecipient}
            onChange={(e) => setTestRecipient(e.target.value)}
            placeholder="recipient@example.com"
          />
        </div>

        {/* Data Source Radio Group */}
        <div data-testid="data-source-section" className="grid gap-2">
          <Label>Test Data Source</Label>
          <RadioGroup
            value={dataType}
            onValueChange={setDataType}
            className="flex items-center gap-4"
          >
            <div className="flex items-center space-x-2">
              <RadioGroupItem value="dummy" id="dummy-data" data-testid="dummy-data-radio" />
              <Label htmlFor="dummy-data" className="font-normal cursor-pointer">
                Dummy data
              </Label>
            </div>
            <div className="flex items-center space-x-2">
              <RadioGroupItem value="entry" id="entry-data" data-testid="entry-data-radio" />
              <Label htmlFor="entry-data" className="font-normal cursor-pointer">
                Entry data
              </Label>
            </div>
          </RadioGroup>
        </div>

        {/* Entry ID Input (shown when entry data selected) */}
        {dataType === 'entry' && (
          <div data-testid="entry-id-section" className="grid gap-2">
            <Label htmlFor="entry-id">Entry ID</Label>
            <Input
              id="entry-id"
              data-testid="entry-id-input"
              type="number"
              value={entryId}
              onChange={(e) => setEntryId(e.target.value)}
              placeholder="Leave empty for latest entry"
              min={1}
            />
          </div>
        )}

        {/* Status Message */}
        {status.type && (
          <div
            data-testid="status-message"
            className={cn(
              'flex items-center gap-2 p-3 rounded-md text-sm',
              status.type === 'loading' && 'bg-blue-50 text-blue-700',
              status.type === 'success' && 'bg-green-50 text-green-700',
              status.type === 'error' && 'bg-red-50 text-red-700'
            )}
          >
            {status.type === 'loading' && <Loader2 className="w-4 h-4 animate-spin" />}
            {status.type === 'success' && <CheckCircle className="w-4 h-4" />}
            {status.type === 'error' && <AlertCircle className="w-4 h-4" />}
            <span>{status.message}</span>
          </div>
        )}

        <DialogFooter className="sm:justify-start">
          <Button
            data-testid="send-test-btn"
            onClick={handleSendTest}
            disabled={status.type === 'loading'}
          >
            {status.type === 'loading' ? (
              <Loader2 className="w-4 h-4 animate-spin" />
            ) : (
              <Send className="w-4 h-4" />
            )}
            <span>Send Test</span>
          </Button>
          <Button
            data-testid="cancel-btn"
            type="button"
            variant="secondary"
            onClick={() => handleOpenChange(false)}
            disabled={status.type === 'loading'}
          >
            Close
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export default TestEmailModal;
