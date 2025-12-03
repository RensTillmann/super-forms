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
    <div className="h-full bg-gray-50 flex flex-col">
      {/* Apple Mail Header Bar */}
      <div className="bg-gray-100 border-b border-gray-300 px-4 py-2">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <button className="p-1 hover:bg-gray-200 rounded">
              <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
            <div className="w-px h-6 bg-gray-300"></div>
            <button className="p-1 hover:bg-gray-200 rounded">
              <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
              </svg>
            </button>
            <button className="p-1 hover:bg-gray-200 rounded">
              <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8l4-4m0 0l-4-4m4 4H3" />
              </svg>
            </button>
            <button className="p-1 hover:bg-gray-200 rounded">
              <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </button>
            <div className="w-px h-6 bg-gray-300"></div>
            <button className="p-1 hover:bg-gray-200 rounded">
              <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
              </svg>
            </button>
          </div>
          <div className="flex items-center gap-2">
            <input 
              type="search" 
              placeholder="Search"
              className="px-2 py-1 text-sm bg-white border border-gray-300 rounded-md w-40"
            />
          </div>
        </div>
      </div>

      {/* Email Content Area */}
      <div className="flex-1 bg-white overflow-hidden">
        {/* Email Header */}
        <div className="border-b px-6 py-4">
          <div className="flex items-center justify-between mb-3">
            <h1 className="text-2xl font-medium text-gray-900">{subject}</h1>
            <span className="text-sm text-gray-500">{previewDate}</span>
          </div>

          <div className="grid grid-cols-[80px_1fr] gap-2 text-sm">
            <span className="text-gray-500 text-right">From:</span>
            <div>
              <span className="font-medium text-gray-900">{fromName}</span>
              <span className="text-gray-600"> &lt;{fromEmail}&gt;</span>
            </div>
            
            <span className="text-gray-500 text-right">To:</span>
            <div className="text-gray-700">{to}</div>
            
            {cc && (
              <>
                <span className="text-gray-500 text-right">Cc:</span>
                <div className="text-gray-700">{cc}</div>
              </>
            )}
            
            {bcc && (
              <>
                <span className="text-gray-500 text-right">Bcc:</span>
                <div className="text-gray-700">{bcc}</div>
              </>
            )}
            
            {email.attachments && email.attachments.length > 0 && (
              <>
                <span className="text-gray-500 text-right">Attachments:</span>
                <div className="flex flex-wrap gap-2">
                  {email.attachments.map((attachment, index) => (
                    <div key={index} className="flex items-center gap-1 px-2 py-1 bg-gray-100 rounded">
                      <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                      </svg>
                      <span className="text-xs">{attachment.filename || attachment.name || 'Attachment'}</span>
                    </div>
                  ))}
                </div>
              </>
            )}
          </div>
        </div>

        {/* Email Body */}
        <div className="overflow-auto h-full">
          <div className="max-w-4xl mx-auto p-6">
            {isBuilder && renderBody ? renderBody() : <EmailContent email={email} />}
          </div>
        </div>
      </div>
    </div>
  );
}

export default AppleMailChrome;