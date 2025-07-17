import React from 'react';
import EmailContent from '../EmailContent';

function OutlookChrome({ email, isMobile, isBuilder, renderBody }) {
  const fromName = email.from_name || 'Sender Name';
  const fromEmail = email.from_email || 'sender@example.com';
  const subject = email.subject || 'Email Subject';
  const to = email.to || '{email}';
  const cc = email.cc || '';
  const bcc = email.bcc || '';
  
  const previewDate = new Date().toLocaleString('en-US', { 
    weekday: 'short',
    month: 'short', 
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true 
  });

  if (isMobile) {
    return (
      <div className="ev2-h-full ev2-bg-gray-50 ev2-flex ev2-flex-col ev2-max-w-md ev2-mx-auto">
        {/* Mobile Outlook Header */}
        <div className="ev2-bg-blue-600 ev2-text-white ev2-px-4 ev2-py-3">
          <div className="ev2-flex ev2-items-center ev2-justify-between">
            <button className="ev2-p-2">
              <svg className="ev2-w-5 ev2-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <span className="ev2-font-medium">Message</span>
            <button className="ev2-p-2">
              <svg className="ev2-w-5 ev2-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
              </svg>
            </button>
          </div>
        </div>

        {/* Mobile Content */}
        <div className="ev2-bg-white ev2-flex-1 ev2-overflow-auto">
          <div className="ev2-p-4 ev2-border-b">
            <h2 className="ev2-text-lg ev2-font-semibold ev2-text-gray-900 ev2-mb-2">{subject}</h2>
            <div className="ev2-flex ev2-items-center ev2-gap-3 ev2-mb-2">
              <div className="ev2-w-10 ev2-h-10 ev2-bg-blue-500 ev2-rounded-full ev2-flex ev2-items-center ev2-justify-center ev2-text-white ev2-font-medium">
                {fromName.charAt(0).toUpperCase()}
              </div>
              <div>
                <div className="ev2-font-medium ev2-text-gray-900">{fromName}</div>
                <div className="ev2-text-sm ev2-text-gray-600">{fromEmail}</div>
              </div>
            </div>
            <div className="ev2-text-xs ev2-text-gray-500">{previewDate}</div>
          </div>
          
          <div className="ev2-p-4">
            {isBuilder && renderBody ? renderBody() : <EmailContent email={email} />}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="ev2-h-full ev2-bg-gray-100 ev2-flex ev2-flex-col">
      {/* Outlook Desktop Ribbon */}
      <div className="ev2-bg-white ev2-border-b ev2-px-4 ev2-py-2">
        <div className="ev2-flex ev2-items-center ev2-gap-6">
          <button className="ev2-flex ev2-flex-col ev2-items-center ev2-gap-1 ev2-px-3 ev2-py-1 hover:ev2-bg-gray-100 ev2-rounded">
            <svg className="ev2-w-6 ev2-h-6 ev2-text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
            </svg>
            <span className="ev2-text-xs">Reply</span>
          </button>
          <button className="ev2-flex ev2-flex-col ev2-items-center ev2-gap-1 ev2-px-3 ev2-py-1 hover:ev2-bg-gray-100 ev2-rounded">
            <svg className="ev2-w-6 ev2-h-6 ev2-text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
            <span className="ev2-text-xs">Forward</span>
          </button>
          <button className="ev2-flex ev2-flex-col ev2-items-center ev2-gap-1 ev2-px-3 ev2-py-1 hover:ev2-bg-gray-100 ev2-rounded">
            <svg className="ev2-w-6 ev2-h-6 ev2-text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span className="ev2-text-xs">Delete</span>
          </button>
        </div>
      </div>

      {/* Email Header */}
      <div className="ev2-bg-white ev2-px-6 ev2-py-4 ev2-border-b">
        <h1 className="ev2-text-xl ev2-font-semibold ev2-text-gray-900 ev2-mb-3">{subject}</h1>
        
        <div className="ev2-flex ev2-items-start ev2-gap-3">
          <div className="ev2-w-12 ev2-h-12 ev2-bg-blue-500 ev2-rounded ev2-flex ev2-items-center ev2-justify-center ev2-text-white ev2-font-medium">
            {fromName.charAt(0).toUpperCase()}
          </div>
          <div className="ev2-flex-1">
            <div className="ev2-flex ev2-items-center ev2-justify-between">
              <div>
                <div className="ev2-font-medium ev2-text-gray-900">{fromName}</div>
                <div className="ev2-text-sm ev2-text-gray-600">{fromEmail}</div>
              </div>
              <div className="ev2-text-sm ev2-text-gray-500">{previewDate}</div>
            </div>
            <div className="ev2-mt-1 ev2-text-sm ev2-text-gray-600">
              <span className="ev2-font-medium">To:</span> {to}
              {cc && <span className="ev2-ml-3"><span className="ev2-font-medium">Cc:</span> {cc}</span>}
              {bcc && <span className="ev2-ml-3"><span className="ev2-font-medium">Bcc:</span> {bcc}</span>}
            </div>
            {email.attachments && email.attachments.length > 0 && (
              <div className="ev2-mt-2 ev2-flex ev2-items-center ev2-gap-2">
                <svg className="ev2-w-4 ev2-h-4 ev2-text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                <span className="ev2-text-sm ev2-text-gray-600">{email.attachments.length} attachment(s)</span>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Email Content */}
      <div className="ev2-flex-1 ev2-overflow-auto ev2-bg-white">
        <div className="ev2-max-w-4xl ev2-mx-auto ev2-p-6">
          <EmailContent email={email} />
        </div>
      </div>
    </div>
  );
}

export default OutlookChrome;