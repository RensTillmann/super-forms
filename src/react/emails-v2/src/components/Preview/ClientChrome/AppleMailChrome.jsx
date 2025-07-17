import React from 'react';
import EmailContent from '../EmailContent';

function AppleMailChrome({ email, isMobile, isBuilder, renderBody }) {
  const fromName = email.from_name || 'Sender Name';
  const fromEmail = email.from_email || 'sender@example.com';
  const subject = email.subject || 'Email Subject';
  const to = email.to || '{email}';
  const cc = email.cc || '';
  const bcc = email.bcc || '';
  
  const previewDate = new Date().toLocaleString('en-US', { 
    month: 'short', 
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: false 
  });

  return (
    <div className="ev2-h-full ev2-bg-gray-50 ev2-flex ev2-flex-col">
      {/* Apple Mail Header Bar */}
      <div className="ev2-bg-gray-100 ev2-border-b ev2-border-gray-300 ev2-px-4 ev2-py-2">
        <div className="ev2-flex ev2-items-center ev2-justify-between">
          <div className="ev2-flex ev2-items-center ev2-gap-2">
            <button className="ev2-p-1 hover:ev2-bg-gray-200 ev2-rounded">
              <svg className="ev2-w-5 ev2-h-5 ev2-text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
            <div className="ev2-w-px ev2-h-6 ev2-bg-gray-300"></div>
            <button className="ev2-p-1 hover:ev2-bg-gray-200 ev2-rounded">
              <svg className="ev2-w-5 ev2-h-5 ev2-text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
              </svg>
            </button>
            <button className="ev2-p-1 hover:ev2-bg-gray-200 ev2-rounded">
              <svg className="ev2-w-5 ev2-h-5 ev2-text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8l4-4m0 0l-4-4m4 4H3" />
              </svg>
            </button>
            <button className="ev2-p-1 hover:ev2-bg-gray-200 ev2-rounded">
              <svg className="ev2-w-5 ev2-h-5 ev2-text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </button>
            <div className="ev2-w-px ev2-h-6 ev2-bg-gray-300"></div>
            <button className="ev2-p-1 hover:ev2-bg-gray-200 ev2-rounded">
              <svg className="ev2-w-5 ev2-h-5 ev2-text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
              </svg>
            </button>
          </div>
          <div className="ev2-flex ev2-items-center ev2-gap-2">
            <input 
              type="search" 
              placeholder="Search"
              className="ev2-px-2 ev2-py-1 ev2-text-sm ev2-bg-white ev2-border ev2-border-gray-300 ev2-rounded-md ev2-w-40"
            />
          </div>
        </div>
      </div>

      {/* Email Content Area */}
      <div className="ev2-flex-1 ev2-bg-white ev2-overflow-hidden">
        {/* Email Header */}
        <div className="ev2-border-b ev2-px-6 ev2-py-4">
          <div className="ev2-flex ev2-items-center ev2-justify-between ev2-mb-3">
            <h1 className="ev2-text-2xl ev2-font-medium ev2-text-gray-900">{subject}</h1>
            <span className="ev2-text-sm ev2-text-gray-500">{previewDate}</span>
          </div>

          <div className="ev2-grid ev2-grid-cols-[80px_1fr] ev2-gap-2 ev2-text-sm">
            <span className="ev2-text-gray-500 ev2-text-right">From:</span>
            <div>
              <span className="ev2-font-medium ev2-text-gray-900">{fromName}</span>
              <span className="ev2-text-gray-600"> &lt;{fromEmail}&gt;</span>
            </div>
            
            <span className="ev2-text-gray-500 ev2-text-right">To:</span>
            <div className="ev2-text-gray-700">{to}</div>
            
            {cc && (
              <>
                <span className="ev2-text-gray-500 ev2-text-right">Cc:</span>
                <div className="ev2-text-gray-700">{cc}</div>
              </>
            )}
            
            {bcc && (
              <>
                <span className="ev2-text-gray-500 ev2-text-right">Bcc:</span>
                <div className="ev2-text-gray-700">{bcc}</div>
              </>
            )}
            
            {email.attachments && email.attachments.length > 0 && (
              <>
                <span className="ev2-text-gray-500 ev2-text-right">Attachments:</span>
                <div className="ev2-flex ev2-flex-wrap ev2-gap-2">
                  {email.attachments.map((attachment, index) => (
                    <div key={index} className="ev2-flex ev2-items-center ev2-gap-1 ev2-px-2 ev2-py-1 ev2-bg-gray-100 ev2-rounded">
                      <svg className="ev2-w-4 ev2-h-4 ev2-text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                      </svg>
                      <span className="ev2-text-xs">{attachment.filename || attachment.name || 'Attachment'}</span>
                    </div>
                  ))}
                </div>
              </>
            )}
          </div>
        </div>

        {/* Email Body */}
        <div className="ev2-overflow-auto ev2-h-full">
          <div className="ev2-max-w-4xl ev2-mx-auto ev2-p-6">
            {isBuilder && renderBody ? renderBody() : <EmailContent email={email} />}
          </div>
        </div>
      </div>
    </div>
  );
}

export default AppleMailChrome;