import React, { useCallback } from 'react';
import EmailContent from '../EmailContent';
import InlineEditableField from '../../shared/InlineEditableField';
import ScheduledIndicator from '../../shared/ScheduledIndicator';
import AttachmentManager from '../AttachmentManager';

function GmailChrome({ email, isMobile, isBuilder, renderBody, updateEmailField, activeEmailId }) {
  const fromName = email.from_name || 'Sender Name';
  const fromEmail = email.from_email || 'sender@example.com';
  const subject = email.subject || 'Email Subject';
  const to = email.to || 'recipient@example.com';
  const cc = email.cc || '';
  const bcc = email.bcc || '';
  
  // Mock data for preview
  const previewDate = new Date().toLocaleString('en-US', { 
    month: 'short', 
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true 
  });

  // Helper function to update email fields
  const handleFieldUpdate = useCallback((field, value) => {
    if (updateEmailField && activeEmailId) {
      updateEmailField(activeEmailId, field, value);
    }
  }, [updateEmailField, activeEmailId]);

  if (isMobile) {
    return (
      <div className="ev2-h-full ev2-bg-white ev2-flex ev2-flex-col ev2-max-w-md ev2-mx-auto">
        {/* Mobile Header */}
        <div className="ev2-bg-white ev2-border-b ev2-px-4 ev2-py-3">
          <div className="ev2-flex ev2-items-center ev2-justify-between">
            <button className="ev2-p-2">
              <svg className="ev2-w-5 ev2-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <div className="ev2-flex ev2-gap-4">
              <button className="ev2-p-2">
                <svg className="ev2-w-5 ev2-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
              </button>
              <button className="ev2-p-2">
                <svg className="ev2-w-5 ev2-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        {/* Mobile Content */}
        <div className="ev2-flex-1 ev2-overflow-auto">
          <div className="ev2-p-4">
            {isBuilder ? (
              <InlineEditableField
                value={email.subject}
                onChange={(value) => handleFieldUpdate('subject', value)}
                placeholder="Email Subject"
                className="ev2-text-lg ev2-font-normal ev2-text-gray-900 ev2-mb-4"
              />
            ) : (
              <h2 className="ev2-text-lg ev2-font-normal ev2-text-gray-900 ev2-mb-4">{subject}</h2>
            )}
            
            <div className="ev2-flex ev2-items-start ev2-gap-3 ev2-mb-4">
              <div className="ev2-w-10 ev2-h-10 ev2-bg-gray-300 ev2-rounded-full ev2-flex ev2-items-center ev2-justify-center ev2-text-gray-600 ev2-font-medium">
                {fromName.charAt(0).toUpperCase()}
              </div>
              <div className="ev2-flex-1">
                <div className="ev2-flex ev2-items-center ev2-gap-2">
                  {isBuilder ? (
                    <InlineEditableField
                      value={email.from_name}
                      onChange={(value) => handleFieldUpdate('from_name', value)}
                      placeholder="Sender Name"
                      className="ev2-font-medium ev2-text-gray-900"
                    />
                  ) : (
                    <span className="ev2-font-medium ev2-text-gray-900">{fromName}</span>
                  )}
                  <span className="ev2-text-xs ev2-text-gray-500">{previewDate}</span>
                </div>
                <div className="ev2-text-sm ev2-text-gray-600 ev2-space-y-1">
                  {isBuilder ? (
                    <>
                      <div className="ev2-flex ev2-items-center">
                        <span className="ev2-inline-block ev2-w-12">to</span>
                        <InlineEditableField
                          value={email.to}
                          onChange={(value) => handleFieldUpdate('to', value)}
                          placeholder="recipient@example.com"
                          type="email"
                          className="ev2-text-sm ev2-text-gray-600"
                        />
                      </div>
                      <div className="ev2-flex ev2-items-center">
                        <span className="ev2-inline-block ev2-w-12">cc</span>
                        <InlineEditableField
                          value={email.cc || ''}
                          onChange={(value) => handleFieldUpdate('cc', value)}
                          placeholder="no CC defined"
                          type="email"
                          className="ev2-text-sm ev2-text-gray-600"
                        />
                      </div>
                      <div className="ev2-flex ev2-items-center">
                        <span className="ev2-inline-block ev2-w-12">bcc</span>
                        <InlineEditableField
                          value={email.bcc || ''}
                          onChange={(value) => handleFieldUpdate('bcc', value)}
                          placeholder="no BCC defined"
                          type="email"
                          className="ev2-text-sm ev2-text-gray-600"
                        />
                      </div>
                    </>
                  ) : (
                    <>
                      <div>to {to || 'me'}</div>
                      {cc && <div>cc: {cc}</div>}
                      {bcc && <div>bcc: {bcc}</div>}
                    </>
                  )}
                </div>
              </div>
            </div>
            
            {/* Attachments Section */}
            <AttachmentManager
              attachments={email.attachments || []}
              onChange={(attachments) => handleFieldUpdate('attachments', attachments)}
              isBuilder={isBuilder}
            />
            
            {/* Mobile Email Container */}
            <div className="ev2-bg-white ev2-p-2">
              <div className="ev2-bg-white ev2-min-h-[200px] ev2-p-0">
                {isBuilder && renderBody ? renderBody() : <EmailContent email={email} />}
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="ev2-h-full ev2-bg-white ev2-flex ev2-flex-col">
      {/* Gmail Desktop Header */}
      <div className="ev2-border-b ev2-bg-white">
        {/* Combined Header with Profile and Subject */}
        <div className="ev2-px-4 ev2-py-3">
          <div className="ev2-flex ev2-items-start ev2-gap-3">
            {/* Profile Picture */}
            <div className="ev2-w-10 ev2-h-10 ev2-bg-gray-300 ev2-rounded-full ev2-flex ev2-items-center ev2-justify-center ev2-text-gray-600 ev2-font-medium ev2-flex-shrink-0">
              {fromName.charAt(0).toUpperCase()}
            </div>
            {/* Content Area */}
            <div className="ev2-flex-1">
              {/* Subject Line with Labels */}
              <div className="ev2-flex ev2-items-center ev2-justify-between ev2-mb-2">
                <div className="ev2-flex ev2-items-center ev2-gap-3">
                  {isBuilder ? (
                    <InlineEditableField
                      value={email.subject}
                      onChange={(value) => handleFieldUpdate('subject', value)}
                      placeholder="Email Subject"
                      className="ev2-text-[1.375rem] ev2-font-normal ev2-text-gray-900"
                      style={{ fontFamily: '"Google Sans", Roboto, RobotoDraft, Helvetica, Arial, sans-serif' }}
                    />
                  ) : (
                    <h1 className="ev2-text-[1.375rem] ev2-font-normal ev2-text-gray-900" style={{ fontFamily: '"Google Sans", Roboto, RobotoDraft, Helvetica, Arial, sans-serif' }}>{subject}</h1>
                  )}
                  <span className="ev2-px-2 ev2-py-0.5 ev2-bg-gray-100 ev2-text-xs ev2-text-gray-600 ev2-rounded">
                    Inbox
                  </span>
                  {email.scheduled_date && (
                    <ScheduledIndicator scheduledDate={email.scheduled_date} />
                  )}
                </div>
                <div className="ev2-flex ev2-items-center ev2-gap-2">
                  <button className="ev2-p-1 hover:ev2-bg-gray-100 ev2-rounded">
                    <svg className="ev2-w-4 ev2-h-4 ev2-text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                  </button>
                  <button className="ev2-p-1 hover:ev2-bg-gray-100 ev2-rounded">
                    <svg className="ev2-w-4 ev2-h-4 ev2-text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                  </button>
                </div>
              </div>
              
              {/* From/To Details */}
              <div className="ev2-flex ev2-items-center ev2-justify-between">
                <div>
                  <div className="ev2-flex ev2-items-center ev2-gap-2">
                    {isBuilder ? (
                      <>
                        <InlineEditableField
                          value={email.from_name}
                          onChange={(value) => handleFieldUpdate('from_name', value)}
                          placeholder="Sender Name"
                          className="ev2-font-medium ev2-text-gray-900"
                        />
                        <span className="ev2-text-sm ev2-text-gray-600">&lt;</span>
                        <InlineEditableField
                          value={email.from_email}
                          onChange={(value) => handleFieldUpdate('from_email', value)}
                          placeholder="sender@example.com"
                          type="email"
                          className="ev2-text-sm ev2-text-gray-600"
                        />
                        <span className="ev2-text-sm ev2-text-gray-600">&gt;</span>
                      </>
                    ) : (
                      <>
                        <span className="ev2-font-medium ev2-text-gray-900">{fromName}</span>
                        <span className="ev2-text-sm ev2-text-gray-600">&lt;{fromEmail}&gt;</span>
                      </>
                    )}
                  </div>
                  <div className="ev2-text-sm ev2-text-gray-600 ev2-space-y-1">
                    {isBuilder ? (
                      <>
                        <div className="ev2-flex ev2-items-center">
                          <span className="ev2-inline-block ev2-w-12">to</span>
                          <InlineEditableField
                            value={email.to}
                            onChange={(value) => handleFieldUpdate('to', value)}
                            placeholder="recipient@example.com"
                            type="email"
                            className="ev2-text-sm ev2-text-gray-600"
                          />
                        </div>
                        <div className="ev2-flex ev2-items-center">
                          <span className="ev2-inline-block ev2-w-12">cc</span>
                          <InlineEditableField
                            value={email.cc || ''}
                            onChange={(value) => handleFieldUpdate('cc', value)}
                            placeholder="no CC defined"
                            type="email"
                            className="ev2-text-sm ev2-text-gray-600"
                          />
                        </div>
                        <div className="ev2-flex ev2-items-center">
                          <span className="ev2-inline-block ev2-w-12">bcc</span>
                          <InlineEditableField
                            value={email.bcc || ''}
                            onChange={(value) => handleFieldUpdate('bcc', value)}
                            placeholder="no BCC defined"
                            type="email"
                            className="ev2-text-sm ev2-text-gray-600"
                          />
                        </div>
                      </>
                    ) : (
                      <>
                        <div>to {to}</div>
                        {cc && <div>cc: {cc}</div>}
                        {bcc && <div>bcc: {bcc}</div>}
                      </>
                    )}
                  </div>
                </div>
                <div className="ev2-flex ev2-items-center ev2-gap-2">
                  <div className="ev2-text-sm ev2-text-gray-500">{previewDate}</div>
                  <button className="ev2-p-1 hover:ev2-bg-gray-100 ev2-rounded">
                    <svg className="ev2-w-4 ev2-h-4 ev2-text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                  </button>
                  <button className="ev2-p-1 hover:ev2-bg-gray-100 ev2-rounded">
                    <svg className="ev2-w-4 ev2-h-4 ev2-text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                  </button>
                </div>
              </div>
              
              {/* Attachments Section */}
              <AttachmentManager
                attachments={email.attachments || []}
                onChange={(attachments) => handleFieldUpdate('attachments', attachments)}
                isBuilder={isBuilder}
              />
            </div>
          </div>
        </div>
      </div>

      {/* Email Content */}
      <div className="ev2-flex-1 ev2-overflow-auto">
        {/* Email Preview Container - Max 1000px for realistic email client viewport */}
        <div className="ev2-max-w-[1000px] ev2-mx-auto ev2-bg-white">
          <div className="ev2-bg-white ev2-min-h-[200px] ev2-p-0">
            {isBuilder && renderBody ? renderBody() : <EmailContent email={email} />}
          </div>
          
          {/* Reply Section */}
          <div className="ev2-border-t ev2-pt-6 ev2-pl-6">
            <div className="ev2-flex ev2-gap-3">
              <button className="ev2-px-4 ev2-py-2 ev2-bg-white ev2-border ev2-rounded-full ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-cursor-not-allowed" disabled>
                <svg className="ev2-w-4 ev2-h-4 ev2-inline-block ev2-mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                </svg>
                Reply
              </button>
              <button className="ev2-px-4 ev2-py-2 ev2-bg-white ev2-border ev2-rounded-full ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-cursor-not-allowed" disabled>
                <svg className="ev2-w-4 ev2-h-4 ev2-inline-block ev2-mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
                Forward
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default GmailChrome;