import { useState, useCallback } from 'react';
import { Send, Loader2, CheckCircle, AlertCircle } from 'lucide-react';
import useEmailStore from '../hooks/useEmailStore';
import clsx from 'clsx';

/**
 * Compact horizontal bar for sending test emails
 * Placed between canvas and element palette in EmailClientBuilder
 */
function TestEmailBar() {
  const { activeEmailId, emails } = useEmailStore();
  const activeEmail = emails.find(e => e.id === activeEmailId);

  // State
  const [dataType, setDataType] = useState('dummy'); // 'dummy' or 'entry'
  const [entryId, setEntryId] = useState('');
  const [testRecipient, setTestRecipient] = useState(() => {
    // Default to current user's email if available
    return window.sfuiData?.currentUserEmail || '';
  });
  const [status, setStatus] = useState({ type: null, message: '' }); // type: 'loading' | 'success' | 'error'

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
          value.forEach((item, index) => {
            formData.append(`email_settings[${key}][${index}]`, item);
          });
        } else {
          formData.append(`email_settings[${key}]`, value);
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
        // Clear success message after 5 seconds
        setTimeout(() => setStatus({ type: null, message: '' }), 5000);
      } else {
        setStatus({ type: 'error', message: result.data?.message || 'Failed to send test email' });
      }
    } catch (error) {
      console.error('Test email error:', error);
      setStatus({ type: 'error', message: 'Network error - please try again' });
    }
  }, [activeEmail, dataType, entryId, testRecipient]);

  // Don't render if no email is selected
  if (!activeEmail) {
    return null;
  }

  return (
    <div data-testid="test-email-bar" className="bg-gray-50 border-t border-b border-gray-200 px-4 py-2">
      <div className="flex items-center gap-4 flex-wrap">
        {/* Data Source Toggle */}
        <div data-testid="data-source-section" className="flex items-center gap-3">
          <span className="text-xs font-medium text-gray-500 uppercase">Test Data:</span>
          <div className="flex items-center gap-2">
            <label className="flex items-center gap-1.5 cursor-pointer">
              <input
                data-testid="dummy-data-radio"
                type="radio"
                name="dataType"
                value="dummy"
                checked={dataType === 'dummy'}
                onChange={(e) => setDataType(e.target.value)}
                className="w-3.5 h-3.5 text-primary-500 border-gray-300 focus:ring-primary-500"
              />
              <span className="text-sm text-gray-700">Dummy data</span>
            </label>
            <label className="flex items-center gap-1.5 cursor-pointer">
              <input
                data-testid="entry-data-radio"
                type="radio"
                name="dataType"
                value="entry"
                checked={dataType === 'entry'}
                onChange={(e) => setDataType(e.target.value)}
                className="w-3.5 h-3.5 text-primary-500 border-gray-300 focus:ring-primary-500"
              />
              <span className="text-sm text-gray-700">Entry data</span>
            </label>
          </div>
        </div>

        {/* Entry ID Input (shown when entry data selected) */}
        {dataType === 'entry' && (
          <div data-testid="entry-id-section" className="flex items-center gap-2">
            <input
              data-testid="entry-id-input"
              type="text"
              value={entryId}
              onChange={(e) => setEntryId(e.target.value)}
              placeholder="Entry ID (empty = latest)"
              className="w-40 px-2.5 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
            />
          </div>
        )}

        {/* Spacer */}
        <div className="flex-1" />

        {/* Recipient and Send Button */}
        <div data-testid="send-section" className="flex items-center gap-2">
          <input
            data-testid="recipient-input"
            type="email"
            value={testRecipient}
            onChange={(e) => setTestRecipient(e.target.value)}
            placeholder="recipient@example.com"
            className="w-52 px-2.5 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
          />
          <button
            data-testid="send-test-btn"
            type="button"
            onClick={handleSendTest}
            disabled={status.type === 'loading'}
            className={clsx(
              'inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded transition-colors',
              status.type === 'loading'
                ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                : 'bg-primary-500 text-white hover:bg-primary-600'
            )}
          >
            {status.type === 'loading' ? (
              <Loader2 className="w-4 h-4 animate-spin" />
            ) : (
              <Send className="w-4 h-4" />
            )}
            <span>Send Test</span>
          </button>
        </div>

        {/* Status Message */}
        {status.type && status.type !== 'loading' && (
          <div data-testid="status-message" className={clsx(
            'flex items-center gap-1.5 text-sm',
            status.type === 'success' && 'text-green-600',
            status.type === 'error' && 'text-red-600'
          )}>
            {status.type === 'success' && <CheckCircle className="w-4 h-4" />}
            {status.type === 'error' && <AlertCircle className="w-4 h-4" />}
            <span>{status.message}</span>
          </div>
        )}
      </div>
    </div>
  );
}

export default TestEmailBar;
