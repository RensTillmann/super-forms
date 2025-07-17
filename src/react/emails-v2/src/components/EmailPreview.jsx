import React, { useState, useEffect } from 'react';
import useEmailStore from '../hooks/useEmailStore';
import clsx from 'clsx';

function EmailPreview() {
  const { activeEmailId, emails } = useEmailStore();
  const [previewMode, setPreviewMode] = useState('desktop'); // desktop, mobile
  const [previewContent, setPreviewContent] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [showTestDialog, setShowTestDialog] = useState(false);
  const [testEmail, setTestEmail] = useState('');
  const [testDataSource, setTestDataSource] = useState('dummy'); // dummy, last

  const activeEmail = emails.find(e => e.id === activeEmailId);

  useEffect(() => {
    if (activeEmail) {
      generatePreview();
    }
  }, [activeEmail]);

  const generatePreview = async () => {
    if (!activeEmail) return;
    
    setIsLoading(true);
    
    // Simulate preview generation with template processing
    // In real implementation, this would call a backend endpoint
    setTimeout(() => {
      const { body, subject, from_name, from_email } = activeEmail;
      
      // Basic email preview template
      const preview = `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
          <div style="background: #f5f5f5; padding: 20px; border-bottom: 1px solid #ddd;">
            <p style="margin: 0; color: #666;">From: ${from_name || 'Sender Name'} &lt;${from_email || 'sender@example.com'}&gt;</p>
            <p style="margin: 5px 0 0 0; color: #666;">Subject: <strong>${subject || 'Email Subject'}</strong></p>
          </div>
          <div style="padding: 20px;">
            ${body || '<p>Email content will appear here...</p>'}
          </div>
        </div>
      `;
      
      setPreviewContent(preview);
      setIsLoading(false);
    }, 300);
  };

  const handleSendTest = async () => {
    if (!testEmail) {
      alert('Please enter a test email address');
      return;
    }

    // In real implementation, this would send a test email via backend
    // console.log('Sending test email to:', testEmail, 'with data source:', testDataSource);
    
    // Simulate sending
    setIsLoading(true);
    setTimeout(() => {
      setIsLoading(false);
      setShowTestDialog(false);
      alert(`Test email sent to ${testEmail}`);
    }, 1000);
  };

  const getPreviewStyles = () => {
    if (previewMode === 'mobile') {
      return {
        width: '375px',
        height: '667px',
        margin: '0 auto'
      };
    }
    return {
      width: '100%',
      height: '600px'
    };
  };

  if (!activeEmail) {
    return (
      <div className="ev2-flex ev2-items-center ev2-justify-center ev2-h-full ev2-text-gray-500">
        <p>Select an email to preview</p>
      </div>
    );
  }

  return (
    <div className="ev2-h-full ev2-flex ev2-flex-col">
      {/* Preview Controls */}
      <div className="ev2-bg-gray-50 ev2-border-b ev2-px-4 ev2-py-3 ev2-flex ev2-items-center ev2-justify-between">
        <div className="ev2-flex ev2-items-center ev2-gap-4">
          <h3 className="ev2-font-medium ev2-text-gray-900">Email Preview</h3>
          
          <div className="ev2-flex ev2-items-center ev2-gap-1 ev2-bg-white ev2-rounded-md ev2-border ev2-p-1">
            <button
              type="button"
              onClick={() => setPreviewMode('desktop')}
              className={clsx(
                'ev2-px-3 ev2-py-1 ev2-rounded ev2-text-sm ev2-transition-colors',
                previewMode === 'desktop' 
                  ? 'ev2-bg-primary-500 ev2-text-white' 
                  : 'ev2-text-gray-600 hover:ev2-bg-gray-100'
              )}
            >
              <svg className="ev2-w-4 ev2-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" 
                />
              </svg>
            </button>
            <button
              type="button"
              onClick={() => setPreviewMode('mobile')}
              className={clsx(
                'ev2-px-3 ev2-py-1 ev2-rounded ev2-text-sm ev2-transition-colors',
                previewMode === 'mobile' 
                  ? 'ev2-bg-primary-500 ev2-text-white' 
                  : 'ev2-text-gray-600 hover:ev2-bg-gray-100'
              )}
            >
              <svg className="ev2-w-4 ev2-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                  d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" 
                />
              </svg>
            </button>
          </div>
        </div>
        
        <div className="ev2-flex ev2-items-center ev2-gap-2">
          <button
            type="button"
            onClick={generatePreview}
            className="ev2-px-3 ev2-py-1.5 ev2-text-sm ev2-bg-white ev2-border ev2-rounded-md hover:ev2-bg-gray-50"
          >
            <svg className="ev2-w-4 ev2-h-4 ev2-inline-block ev2-mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" 
              />
            </svg>
            Refresh
          </button>
          
          <button
            type="button"
            onClick={() => setShowTestDialog(true)}
            className="ev2-px-3 ev2-py-1.5 ev2-text-sm ev2-bg-primary-500 ev2-text-white ev2-rounded-md hover:ev2-bg-primary-600"
          >
            <svg className="ev2-w-4 ev2-h-4 ev2-inline-block ev2-mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" 
              />
            </svg>
            Send Test
          </button>
        </div>
      </div>
      
      {/* Preview Content */}
      <div className="ev2-flex-1 ev2-overflow-auto ev2-bg-gray-100 ev2-p-4">
        <div 
          className="ev2-bg-white ev2-shadow-lg ev2-overflow-auto"
          style={getPreviewStyles()}
        >
          {isLoading ? (
            <div className="ev2-flex ev2-items-center ev2-justify-center ev2-h-full">
              <div className="ev2-animate-spin ev2-rounded-full ev2-h-8 ev2-w-8 ev2-border-b-2 ev2-border-primary-500"></div>
            </div>
          ) : (
            <div 
              dangerouslySetInnerHTML={{ __html: previewContent }}
              className="ev2-h-full"
            />
          )}
        </div>
      </div>
      
      {/* Test Email Dialog */}
      {showTestDialog && (
        <div className="ev2-fixed ev2-inset-0 ev2-bg-black ev2-bg-opacity-50 ev2-flex ev2-items-center ev2-justify-center ev2-z-50">
          <div className="ev2-bg-white ev2-rounded-lg ev2-p-6 ev2-w-96">
            <h3 className="ev2-text-lg ev2-font-medium ev2-mb-4">Send Test Email</h3>
            
            <div className="ev2-space-y-4">
              <div>
                <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
                  Test Email Address
                </label>
                <input
                  type="email"
                  value={testEmail}
                  onChange={(e) => setTestEmail(e.target.value)}
                  placeholder="test@example.com"
                  className="ev2-w-full ev2-px-3 ev2-py-2 ev2-border ev2-border-gray-300 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-primary-500 focus:ev2-border-transparent"
                />
              </div>
              
              <div>
                <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
                  Test Data Source
                </label>
                <select
                  value={testDataSource}
                  onChange={(e) => setTestDataSource(e.target.value)}
                  className="ev2-w-full ev2-px-3 ev2-py-2 ev2-border ev2-border-gray-300 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-primary-500 focus:ev2-border-transparent"
                >
                  <option value="dummy">Dummy Data</option>
                  <option value="last">Last Form Entry</option>
                </select>
              </div>
            </div>
            
            <div className="ev2-flex ev2-justify-end ev2-gap-2 ev2-mt-6">
              <button
                type="button"
                onClick={() => setShowTestDialog(false)}
                className="ev2-px-4 ev2-py-2 ev2-text-sm ev2-text-gray-700 ev2-bg-white ev2-border ev2-border-gray-300 ev2-rounded-md hover:ev2-bg-gray-50"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={handleSendTest}
                disabled={isLoading}
                className="ev2-px-4 ev2-py-2 ev2-text-sm ev2-bg-primary-500 ev2-text-white ev2-rounded-md hover:ev2-bg-primary-600 disabled:ev2-opacity-50"
              >
                {isLoading ? 'Sending...' : 'Send Test'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default EmailPreview;