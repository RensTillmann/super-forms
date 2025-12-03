import React, { useCallback } from 'react';
import EmailContent from '../EmailContent';
import InlineEditableField from '../../shared/InlineEditableField';
import ScheduledIndicator from '../../shared/ScheduledIndicator';
import AttachmentManager from '../AttachmentManager';
import { User, Palette, Code } from 'lucide-react';
import clsx from 'clsx';

function GmailChrome({ email, isMobile, isBuilder, renderBody, updateEmailField, activeEmailId, isHtmlMode, onModeChange }) {
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
      <div className="h-full bg-white flex flex-col max-w-md mx-auto">
        {/* Mobile Header */}
        <div className="bg-white border-b px-4 py-3">
          <div className="flex items-center justify-between">
            <button className="p-2">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <div className="flex gap-4">
              <button className="p-2">
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
              </button>
              <button className="p-2">
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        {/* Mobile Content */}
        <div className="flex-1 overflow-auto">
          <div className="p-4">
            {isBuilder ? (
              <InlineEditableField
                value={email.subject}
                onChange={(value) => handleFieldUpdate('subject', value)}
                placeholder="Email Subject"
                className="text-lg font-normal text-gray-900 mb-4"
                noPadding={true}
              />
            ) : (
              <h2 className="text-lg font-normal text-gray-900 mb-4">{subject}</h2>
            )}
            
            <div className="flex items-start gap-5 mb-4">
              <div className="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-600">
                <User className="w-6 h-6" />
              </div>
              <div className="flex-1">
                <div className="flex items-center gap-2">
                  {isBuilder ? (
                    <InlineEditableField
                      value={email.from_name}
                      onChange={(value) => handleFieldUpdate('from_name', value)}
                      placeholder="Sender Name"
                      className="font-medium text-gray-900"
                      noPadding={true}
                    />
                  ) : (
                    <span className="font-medium text-gray-900">{fromName}</span>
                  )}
                  <span className="text-xs text-gray-500">{previewDate}</span>
                </div>
                <div className="text-sm text-gray-600 space-y-1">
                  {isBuilder ? (
                    <>
                      <div className="flex items-center">
                        <span className="inline-block w-12">to</span>
                        <InlineEditableField
                          value={email.to}
                          onChange={(value) => handleFieldUpdate('to', value)}
                          placeholder="recipient@example.com"
                          type="email"
                          className="text-sm text-gray-600"
                        />
                      </div>
                      <div className="flex items-center">
                        <span className="inline-block w-12">cc</span>
                        <InlineEditableField
                          value={email.cc || ''}
                          onChange={(value) => handleFieldUpdate('cc', value)}
                          placeholder="no CC defined"
                          type="email"
                          className="text-sm text-gray-600"
                        />
                      </div>
                      <div className="flex items-center">
                        <span className="inline-block w-12">bcc</span>
                        <InlineEditableField
                          value={email.bcc || ''}
                          onChange={(value) => handleFieldUpdate('bcc', value)}
                          placeholder="no BCC defined"
                          type="email"
                          className="text-sm text-gray-600"
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
            
            {/* Attachments and Mode Toggle Row */}
            <div className="flex items-center justify-between gap-2 px-2">
              <AttachmentManager
                attachments={email.attachments || []}
                onChange={(attachments) => handleFieldUpdate('attachments', attachments)}
                isBuilder={isBuilder}
              />

              {/* Mode Toggle - Visual/HTML (Mobile) */}
              {isBuilder && onModeChange && (
                <div className="flex items-center gap-0.5 bg-gray-100 rounded-md p-0.5">
                  <button
                    onClick={() => !isHtmlMode || onModeChange('visual')}
                    className={clsx(
                      'flex items-center gap-1 px-2 py-1 rounded text-xs font-medium transition-all',
                      !isHtmlMode
                        ? 'bg-white text-gray-900 shadow-sm'
                        : 'text-gray-500'
                    )}
                    title="Visual Builder"
                  >
                    <Palette className="w-3 h-3" />
                  </button>
                  <button
                    onClick={() => isHtmlMode || onModeChange('html')}
                    className={clsx(
                      'flex items-center gap-1 px-2 py-1 rounded text-xs font-medium transition-all',
                      isHtmlMode
                        ? 'bg-white text-gray-900 shadow-sm'
                        : 'text-gray-500'
                    )}
                    title="HTML Editor"
                  >
                    <Code className="w-3 h-3" />
                  </button>
                </div>
              )}
            </div>

            {/* Mobile Email Container */}
            <div className="bg-white p-2">
              <div className="bg-white min-h-[200px] p-0">
                {isBuilder && renderBody ? renderBody() : <EmailContent email={email} />}
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="h-full bg-white flex flex-col">
      {/* Gmail Desktop Header */}
      <div className="border-b bg-white">
        {/* Combined Header with Profile and Subject */}
        <div className="px-4 py-3">
          <div className="flex items-start gap-5">
            {/* Profile Picture */}
            <div className="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 flex-shrink-0">
              <User className="w-6 h-6" />
            </div>
            {/* Content Area */}
            <div className="flex-1">
              {/* Subject Line with Labels */}
              <div className="flex items-center justify-between mb-2">
                <div className="flex items-center gap-3">
                  {isBuilder ? (
                    <InlineEditableField
                      value={email.subject}
                      onChange={(value) => handleFieldUpdate('subject', value)}
                      placeholder="Email Subject"
                      className="text-[1.375rem] font-normal text-gray-900"
                      style={{ fontFamily: '"Google Sans", Roboto, RobotoDraft, Helvetica, Arial, sans-serif' }}
                      noPadding={true}
                    />
                  ) : (
                    <h1 className="text-[1.375rem] font-normal text-gray-900" style={{ fontFamily: '"Google Sans", Roboto, RobotoDraft, Helvetica, Arial, sans-serif' }}>{subject}</h1>
                  )}
                  <span className="px-2 py-0.5 bg-gray-100 text-xs text-gray-600 rounded">
                    Inbox
                  </span>
                  {(email.scheduled_date || email.schedule) && (
                    <ScheduledIndicator 
                      scheduledDate={email.scheduled_date}
                      schedule={email.schedule}
                    />
                  )}
                </div>
                <div className="flex items-center gap-2">
                  <button className="p-1 hover:bg-gray-100 rounded">
                    <svg className="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                  </button>
                  <button className="p-1 hover:bg-gray-100 rounded">
                    <svg className="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                  </button>
                </div>
              </div>
              
              {/* From/To Details */}
              <div className="flex items-center justify-between mt-2">
                <div>
                  <div className="flex items-center">
                    {isBuilder ? (
                      <>
                        <InlineEditableField
                          value={email.from_name}
                          onChange={(value) => handleFieldUpdate('from_name', value)}
                          placeholder="Sender Name"
                          className="font-medium text-gray-900"
                          noPadding={true}
                        />
                        <span className="text-sm text-gray-600 ml-2.5">&lt;</span>
                        <InlineEditableField
                          value={email.from_email}
                          onChange={(value) => handleFieldUpdate('from_email', value)}
                          placeholder="sender@example.com"
                          type="email"
                          className="text-sm text-gray-600"
                          noPadding={true}
                        />
                        <span className="text-sm text-gray-600">&gt;</span>
                      </>
                    ) : (
                      <>
                        <span className="font-medium text-gray-900">{fromName}</span>
                        <span className="text-sm text-gray-600">&lt;{fromEmail}&gt;</span>
                      </>
                    )}
                  </div>
                  <div className="text-sm text-gray-600 space-y-1">
                    {isBuilder ? (
                      <>
                        <div className="flex items-center">
                          <span className="inline-block w-12">to</span>
                          <InlineEditableField
                            value={email.to}
                            onChange={(value) => handleFieldUpdate('to', value)}
                            placeholder="recipient@example.com"
                            type="email"
                            className="text-sm text-gray-600"
                          />
                        </div>
                        <div className="flex items-center">
                          <span className="inline-block w-12">cc</span>
                          <InlineEditableField
                            value={email.cc || ''}
                            onChange={(value) => handleFieldUpdate('cc', value)}
                            placeholder="no CC defined"
                            type="email"
                            className="text-sm text-gray-600"
                          />
                        </div>
                        <div className="flex items-center">
                          <span className="inline-block w-12">bcc</span>
                          <InlineEditableField
                            value={email.bcc || ''}
                            onChange={(value) => handleFieldUpdate('bcc', value)}
                            placeholder="no BCC defined"
                            type="email"
                            className="text-sm text-gray-600"
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
                <div className="flex items-center gap-2">
                  <div className="text-sm text-gray-500">{previewDate}</div>
                  <button className="p-1 hover:bg-gray-100 rounded">
                    <svg className="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                  </button>
                  <button className="p-1 hover:bg-gray-100 rounded">
                    <svg className="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                  </button>
                </div>
              </div>
              
              {/* Attachments and Mode Toggle Row */}
              <div className="flex items-center justify-between gap-4">
                <AttachmentManager
                  attachments={email.attachments || []}
                  onChange={(attachments) => handleFieldUpdate('attachments', attachments)}
                  isBuilder={isBuilder}
                />

                {/* Mode Toggle - Visual/HTML */}
                {isBuilder && onModeChange && (
                  <div className="flex items-center gap-1 bg-gray-100 rounded-lg p-0.5">
                    <button
                      onClick={() => !isHtmlMode || onModeChange('visual')}
                      className={clsx(
                        'flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium transition-all',
                        !isHtmlMode
                          ? 'bg-white text-gray-900 shadow-sm'
                          : 'text-gray-500 hover:text-gray-700'
                      )}
                      title="Visual Builder"
                    >
                      <Palette className="w-4 h-4" />
                      <span>Visual</span>
                    </button>
                    <button
                      onClick={() => isHtmlMode || onModeChange('html')}
                      className={clsx(
                        'flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium transition-all',
                        isHtmlMode
                          ? 'bg-white text-gray-900 shadow-sm'
                          : 'text-gray-500 hover:text-gray-700'
                      )}
                      title="HTML Editor"
                    >
                      <Code className="w-4 h-4" />
                      <span>HTML</span>
                    </button>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Email Content */}
      <div className="flex-1 overflow-auto">
        {/* Email Preview Container - Max 1000px for realistic email client viewport */}
        <div className="max-w-[1000px] mx-auto bg-white">
          <div className="bg-white min-h-[200px] p-0">
            {isBuilder && renderBody ? renderBody() : <EmailContent email={email} />}
          </div>
          
          {/* Reply Section */}
          <div className="border-t pt-6 pl-6">
            <div className="flex gap-3">
              <button className="px-4 py-2 bg-white border rounded-full text-sm font-medium text-gray-700 cursor-not-allowed" disabled>
                <svg className="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                </svg>
                Reply
              </button>
              <button className="px-4 py-2 bg-white border rounded-full text-sm font-medium text-gray-700 cursor-not-allowed" disabled>
                <svg className="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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