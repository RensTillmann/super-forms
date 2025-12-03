import { useCallback, useState } from 'react';
import EmailContent from '../EmailContent';
import InlineEditableField from '../../shared/InlineEditableField';
import EmailTagInput from '../../fields/EmailTagInput';
import ScheduledIndicator from '../../shared/ScheduledIndicator';
import AttachmentManager from '../AttachmentManager';
import TestEmailModal from '../../TestEmailModal';
import CustomButton from '@/components/ui/CustomButton';
import { Button } from '@/components/ui/button';
import { User, Palette, Code, Reply, Forward, Star, Monitor, Smartphone, Tag, EllipsisVertical } from 'lucide-react';
import clsx from 'clsx';

function GmailChrome({ email, isMobile, isBuilder, renderBody, updateEmailField, activeEmailId, isHtmlMode, onModeChange, onShowTestEmail }) {
  const fromName = email.from_name || 'Sender Name';
  const fromEmail = email.from_email || 'sender@example.com';
  const subject = email.subject || 'Email Subject';
  const to = email.to || 'recipient@example.com';
  const cc = email.cc || '';
  const bcc = email.bcc || '';

  // Test email modal state
  const [showTestEmailModal, setShowTestEmailModal] = useState(false);

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
      <div data-testid="gmail-chrome-mobile" className="h-full bg-white flex flex-col max-w-md mx-auto">
        {/* Mobile Header */}
        <div data-testid="mobile-header" className="bg-white border-b px-4 py-3">
          <div data-testid="mobile-header-nav" className="flex items-center justify-between">
            <button data-testid="mobile-back-btn" className="p-2">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <div data-testid="mobile-header-actions" className="flex gap-4">
              {isBuilder && (
                <Button data-testid="mobile-reply-btn" variant="outline" size="icon" className="h-8 w-8" aria-label="Send Test Email" onClick={() => setShowTestEmailModal(true)}>
                  <Reply className="w-4 h-4" />
                </Button>
              )}
              <Button data-testid="mobile-label-btn" variant="outline" size="icon" className="h-8 w-8" aria-label="Label">
                <Tag className="w-4 h-4" />
              </Button>
              <Button data-testid="mobile-more-btn" variant="outline" size="icon" className="h-8 w-8" aria-label="More options">
                <EllipsisVertical className="w-4 h-4" />
              </Button>
            </div>
          </div>
        </div>

        {/* Mobile Content */}
        <div data-testid="mobile-content-scroll" className="flex-1 overflow-auto">
          <div data-testid="mobile-content-inner" className="p-4">
            {isBuilder ? (
              <InlineEditableField
                data-testid="mobile-subject-field"
                value={email.subject}
                onChange={(value) => handleFieldUpdate('subject', value)}
                placeholder="Email Subject"
                className="text-lg font-normal text-gray-900 mb-4"
                noPadding={true}
              />
            ) : (
              <h2 data-testid="mobile-subject-display" className="text-lg font-normal text-gray-900 mb-4">{subject}</h2>
            )}

            <div data-testid="mobile-sender-section" className="flex items-start gap-5 mb-4">
              <div data-testid="mobile-sender-avatar" className="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-600">
                <User className="w-6 h-6" />
              </div>
              <div data-testid="mobile-sender-details" className="flex-1">
                <div data-testid="mobile-sender-row" className="flex items-center gap-2">
                  {isBuilder ? (
                    <InlineEditableField
                      data-testid="mobile-from-name-field"
                      value={email.from_name}
                      onChange={(value) => handleFieldUpdate('from_name', value)}
                      placeholder="Sender Name"
                      className="font-medium text-gray-900"
                      noPadding={true}
                    />
                  ) : (
                    <span data-testid="mobile-from-name-display" className="font-medium text-gray-900">{fromName}</span>
                  )}
                  <span data-testid="mobile-date" className="text-xs text-gray-500">{previewDate}</span>
                </div>
                <div data-testid="mobile-recipients-list" className="text-sm text-gray-600 space-y-2 mt-2">
                  {isBuilder ? (
                    <>
                      <div data-testid="mobile-to-row" className="flex items-center">
                        <span data-testid="mobile-to-label" className="inline-block w-12 text-gray-500 font-medium">To</span>
                        <div data-testid="mobile-to-field-wrapper" className="flex-1">
                          <EmailTagInput
                            data-testid="mobile-to-field"
                            value={email.to || ''}
                            onChange={(value) => handleFieldUpdate('to', value)}
                            placeholder="Add recipients..."
                          />
                        </div>
                      </div>
                      <div data-testid="mobile-cc-row" className="flex items-center">
                        <span data-testid="mobile-cc-label" className="inline-block w-12 text-gray-500 font-medium">Cc</span>
                        <div data-testid="mobile-cc-field-wrapper" className="flex-1">
                          <EmailTagInput
                            data-testid="mobile-cc-field"
                            value={email.cc || ''}
                            onChange={(value) => handleFieldUpdate('cc', value)}
                            placeholder="Add Cc..."
                          />
                        </div>
                      </div>
                      <div data-testid="mobile-bcc-row" className="flex items-center">
                        <span data-testid="mobile-bcc-label" className="inline-block w-12 text-gray-500 font-medium">Bcc</span>
                        <div data-testid="mobile-bcc-field-wrapper" className="flex-1">
                          <EmailTagInput
                            data-testid="mobile-bcc-field"
                            value={email.bcc || ''}
                            onChange={(value) => handleFieldUpdate('bcc', value)}
                            placeholder="Add Bcc..."
                          />
                        </div>
                      </div>
                    </>
                  ) : (
                    <>
                      <div data-testid="mobile-to-display">to {to || 'me'}</div>
                      {cc && <div data-testid="mobile-cc-display">cc: {cc}</div>}
                      {bcc && <div data-testid="mobile-bcc-display">bcc: {bcc}</div>}
                    </>
                  )}
                </div>
              </div>
            </div>
            
            {/* Attachments and Mode Toggle Row */}
            <div data-testid="mobile-attachments-mode-row" className="flex items-center justify-between gap-2 px-2">
              <AttachmentManager
                attachments={email.attachments || []}
                onChange={(attachments) => handleFieldUpdate('attachments', attachments)}
                isBuilder={isBuilder}
              />

              {/* Mode Toggle - Visual/HTML (Mobile) */}
              {isBuilder && onModeChange && (
                <div data-testid="mobile-mode-toggle" className="flex items-center gap-0.5 bg-gray-100 rounded-md p-0.5">
                  <button
                    data-testid="mobile-visual-mode-btn"
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
                    data-testid="mobile-html-mode-btn"
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
            <div data-testid="mobile-email-container" className="bg-white p-2">
              <div data-testid="mobile-email-body" className="bg-white min-h-[200px] p-0">
                {isBuilder && renderBody ? renderBody() : <EmailContent email={email} />}
              </div>
            </div>
          </div>
        </div>

        {/* Test Email Modal */}
        {isBuilder && (
          <TestEmailModal
            open={showTestEmailModal}
            onOpenChange={setShowTestEmailModal}
          />
        )}
      </div>
    );
  }

  return (
    <div data-testid="gmail-chrome-desktop" className="h-full bg-white flex flex-col">
      {/* Gmail Desktop Header */}
      <div data-testid="gmail-header" className="border-b border-gray-200 bg-white">
        {/* Combined Header with Profile and Subject */}
        <div className="px-4 py-3">
          <div className="flex items-start gap-5">
            {/* Profile Picture */}
            <div data-testid="sender-avatar" className="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 flex-shrink-0">
              <User className="w-6 h-6" />
            </div>
            {/* Content Area */}
            <div className="flex-1">
              {/* Subject Line with Labels */}
              <div data-testid="subject-row" className="flex items-center justify-between mb-2">
                <div className="flex items-center gap-3">
                  {isBuilder ? (
                    <InlineEditableField
                      data-testid="subject-field"
                      value={email.subject}
                      onChange={(value) => handleFieldUpdate('subject', value)}
                      placeholder="Email Subject"
                      className="text-[1.375rem] font-normal text-gray-900"
                      style={{ fontFamily: '"Google Sans", Roboto, RobotoDraft, Helvetica, Arial, sans-serif' }}
                      noPadding={true}
                    />
                  ) : (
                    <h1 data-testid="subject-display" className="text-[1.375rem] font-normal text-gray-900" style={{ fontFamily: '"Google Sans", Roboto, RobotoDraft, Helvetica, Arial, sans-serif' }}>{subject}</h1>
                  )}
                  <span data-testid="inbox-label" className="px-2 py-0.5 bg-gray-100 text-xs text-gray-600 rounded">
                    Inbox
                  </span>
                  {(email.scheduled_date || email.schedule) && (
                    <ScheduledIndicator
                      scheduledDate={email.scheduled_date}
                      schedule={email.schedule}
                    />
                  )}
                </div>
                <div data-testid="subject-row-actions" className="flex items-center gap-2">
                  <Button data-testid="label-btn" variant="outline" size="icon" className="h-8 w-8" aria-label="Label">
                    <Tag className="w-4 h-4" />
                  </Button>
                  <Button data-testid="more-btn" variant="outline" size="icon" className="h-8 w-8" aria-label="More options">
                    <EllipsisVertical className="w-4 h-4" />
                  </Button>
                </div>
              </div>

              {/* From/To Details */}
              <div data-testid="from-to-section" className="flex items-center justify-between mt-2">
                <div data-testid="sender-recipients">
                  <div data-testid="from-row" className="flex items-center pb-8">
                    {isBuilder ? (
                      <>
                        <InlineEditableField
                          data-testid="from-name-field"
                          value={email.from_name}
                          onChange={(value) => handleFieldUpdate('from_name', value)}
                          placeholder="Sender Name"
                          className="font-medium text-gray-900"
                          noPadding={true}
                        />
                        <span className="text-sm text-gray-600 ml-2.5">&lt;</span>
                        <InlineEditableField
                          data-testid="from-email-field"
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
                        <span data-testid="from-name-display" className="font-medium text-gray-900">{fromName}</span>
                        <span data-testid="from-email-display" className="text-sm text-gray-600">&lt;{fromEmail}&gt;</span>
                      </>
                    )}
                  </div>
                  <div data-testid="recipients-list" className="text-sm text-gray-600 border-t border-gray-200">
                    {isBuilder ? (
                      <>
                        <div data-testid="to-row" className="flex items-center">
                          <span data-testid="to-label" className="inline-block w-12 text-gray-500 font-medium">To</span>
                          <div data-testid="to-field-wrapper" className="flex-1">
                            <EmailTagInput
                              data-testid="to-field"
                              value={email.to || ''}
                              onChange={(value) => handleFieldUpdate('to', value)}
                              placeholder="Add recipients..."
                            />
                          </div>
                        </div>
                        <div data-testid="cc-row" className="flex items-center border-t border-gray-200">
                          <span data-testid="cc-label" className="inline-block w-12 text-gray-500 font-medium">Cc</span>
                          <div data-testid="cc-field-wrapper" className="flex-1">
                            <EmailTagInput
                              data-testid="cc-field"
                              value={email.cc || ''}
                              onChange={(value) => handleFieldUpdate('cc', value)}
                              placeholder="Add Cc recipients..."
                            />
                          </div>
                        </div>
                        <div data-testid="bcc-row" className="flex items-center border-t border-gray-200">
                          <span data-testid="bcc-label" className="inline-block w-12 text-gray-500 font-medium">Bcc</span>
                          <div data-testid="bcc-field-wrapper" className="flex-1">
                            <EmailTagInput
                              data-testid="bcc-field"
                              value={email.bcc || ''}
                              onChange={(value) => handleFieldUpdate('bcc', value)}
                              placeholder="Add Bcc recipients..."
                            />
                          </div>
                        </div>
                      </>
                    ) : (
                      <>
                        <div data-testid="to-display" className="py-1">to {to}</div>
                        {cc && <div data-testid="cc-display" className="py-1 border-t border-gray-200">cc: {cc}</div>}
                        {bcc && <div data-testid="bcc-display" className="py-1 border-t border-gray-200">bcc: {bcc}</div>}
                      </>
                    )}
                  </div>
                </div>
                <div data-testid="header-actions" className="flex items-center gap-2">
                  <div data-testid="email-date" className="text-sm text-gray-500">{previewDate}</div>
                  <Button data-testid="star-btn" variant="outline" size="icon" className="h-8" disabled>
                    <Star className="w-4 h-4" />
                  </Button>
                  {isBuilder && (
                    <Button
                      data-testid="reply-header-btn"
                      variant="outline"
                      size="icon"
                      className="h-8"
                      onClick={() => setShowTestEmailModal(true)}
                      title="Send Test Email"
                    >
                      <Reply className="w-4 h-4" />
                    </Button>
                  )}
                </div>
              </div>

              {/* Attachments and Mode Toggle Row */}
              <div data-testid="attachments-mode-row" className="flex items-center justify-between gap-4 mt-2">
                <AttachmentManager
                  attachments={email.attachments || []}
                  onChange={(attachments) => handleFieldUpdate('attachments', attachments)}
                  isBuilder={isBuilder}
                />

                {/* Mode Toggle - Visual/HTML */}
                {isBuilder && onModeChange && (
                  <div data-testid="mode-toggle" className="flex items-center gap-1">
                    <CustomButton
                      data-testid="visual-mode-btn"
                      variant={!isHtmlMode ? 'outlineActive' : 'outline'}
                      size="sm"
                      onClick={() => !isHtmlMode || onModeChange('visual')}
                      title="Visual Builder"
                    >
                      <Palette className="w-4 h-4" />
                      <span>Visual</span>
                    </CustomButton>
                    <CustomButton
                      data-testid="html-mode-btn"
                      variant={isHtmlMode ? 'outlineActive' : 'outline'}
                      size="sm"
                      onClick={() => isHtmlMode || onModeChange('html')}
                      title="HTML Editor"
                    >
                      <Code className="w-4 h-4" />
                      <span>HTML</span>
                    </CustomButton>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Email Content */}
      <div data-testid="email-content-scroll" className="flex-1 overflow-auto">
        {/* Email Preview Container - Max 1000px for realistic email client viewport */}
        <div data-testid="email-content-container" className="max-w-[1000px] mx-auto bg-white">
          <div data-testid="email-body" className="bg-white min-h-[200px] p-0">
            {isBuilder && renderBody ? renderBody() : <EmailContent email={email} />}
          </div>

          {/* Reply Section */}
          <div data-testid="reply-section" className="border-t border-gray-200 pt-6 pl-6">
            <div className="flex gap-3">
              {isBuilder ? (
                <>
                  <CustomButton data-testid="reply-btn" variant="pill" onClick={() => setShowTestEmailModal(true)}>
                    <Reply className="w-4 h-4" />
                    Reply
                  </CustomButton>
                  <CustomButton data-testid="forward-btn" variant="pill" onClick={() => setShowTestEmailModal(true)}>
                    <Forward className="w-4 h-4" />
                    Forward
                  </CustomButton>
                </>
              ) : (
                <>
                  <CustomButton data-testid="reply-btn" variant="pill" disabled>
                    <Reply className="w-4 h-4" />
                    Reply
                  </CustomButton>
                  <CustomButton data-testid="forward-btn" variant="pill" disabled>
                    <Forward className="w-4 h-4" />
                    Forward
                  </CustomButton>
                </>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Test Email Modal */}
      {isBuilder && (
        <TestEmailModal
          open={showTestEmailModal}
          onOpenChange={setShowTestEmailModal}
        />
      )}
    </div>
  );
}

export default GmailChrome;