import EmailContent from '../EmailContent';
import { User } from 'lucide-react';

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
      <div className="h-full bg-gray-50 flex flex-col max-w-md mx-auto">
        {/* Mobile Outlook Header */}
        <div className="bg-blue-600 text-white px-4 py-3">
          <div className="flex items-center justify-between">
            <button className="p-2">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <span className="font-medium">Message</span>
            <button className="p-2">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
              </svg>
            </button>
          </div>
        </div>

        {/* Mobile Content */}
        <div className="bg-white flex-1 overflow-auto">
          <div className="p-4 border-b">
            <h2 className="text-lg font-semibold text-gray-900 mb-2">{subject}</h2>
            <div className="flex items-center gap-3 mb-2">
              <div className="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white">
                <User className="w-6 h-6" />
              </div>
              <div>
                <div className="font-medium text-gray-900">{fromName}</div>
                <div className="text-sm text-gray-600">{fromEmail}</div>
              </div>
            </div>
            <div className="text-xs text-gray-500">{previewDate}</div>
          </div>
          
          <div className="p-4">
            {isBuilder && renderBody ? renderBody() : <EmailContent email={email} />}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="h-full bg-gray-100 flex flex-col">
      {/* Outlook Desktop Ribbon */}
      <div className="bg-white border-b px-4 py-2">
        <div className="flex items-center gap-6">
          <button className="flex flex-col items-center gap-1 px-3 py-1 hover:bg-gray-100 rounded">
            <svg className="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
            </svg>
            <span className="text-xs">Reply</span>
          </button>
          <button className="flex flex-col items-center gap-1 px-3 py-1 hover:bg-gray-100 rounded">
            <svg className="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
            <span className="text-xs">Forward</span>
          </button>
          <button className="flex flex-col items-center gap-1 px-3 py-1 hover:bg-gray-100 rounded">
            <svg className="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span className="text-xs">Delete</span>
          </button>
        </div>
      </div>

      {/* Email Header */}
      <div className="bg-white px-6 py-4 border-b">
        <h1 className="text-xl font-semibold text-gray-900 mb-3">{subject}</h1>
        
        <div className="flex items-start gap-3">
          <div className="w-12 h-12 bg-blue-500 rounded flex items-center justify-center text-white">
            <User className="w-7 h-7" />
          </div>
          <div className="flex-1">
            <div className="flex items-center justify-between">
              <div>
                <div className="font-medium text-gray-900">{fromName}</div>
                <div className="text-sm text-gray-600">{fromEmail}</div>
              </div>
              <div className="text-sm text-gray-500">{previewDate}</div>
            </div>
            <div className="mt-1 text-sm text-gray-600">
              <span className="font-medium">To:</span> {to}
              {cc && <span className="ml-3"><span className="font-medium">Cc:</span> {cc}</span>}
              {bcc && <span className="ml-3"><span className="font-medium">Bcc:</span> {bcc}</span>}
            </div>
            {email.attachments && email.attachments.length > 0 && (
              <div className="mt-2 flex items-center gap-2">
                <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                <span className="text-sm text-gray-600">{email.attachments.length} attachment(s)</span>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Email Content */}
      <div className="flex-1 overflow-auto bg-white">
        <div className="max-w-4xl mx-auto p-6">
          <EmailContent email={email} />
        </div>
      </div>
    </div>
  );
}

export default OutlookChrome;