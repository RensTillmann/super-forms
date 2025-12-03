
function EmailContent({ email }) {
  // For now, render the raw HTML content
  // In the future, this will render the template builder output
  const htmlContent = email.body || '<p>Email content will appear here...</p>';
  
  // Create a safe HTML rendering with email-specific styles
  const emailStyles = `
    <style>
      /* Reset styles for email content */
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }
      
      body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        font-size: 14px;
        line-height: 1.6;
        color: #333333;
      }
      
      p {
        margin: 0 0 10px 0;
      }
      
      h1, h2, h3, h4, h5, h6 {
        margin: 0 0 10px 0;
        font-weight: 600;
      }
      
      h1 { font-size: 24px; }
      h2 { font-size: 20px; }
      h3 { font-size: 18px; }
      h4 { font-size: 16px; }
      h5 { font-size: 14px; }
      h6 { font-size: 12px; }
      
      a {
        color: #0066cc;
        text-decoration: underline;
      }
      
      img {
        max-width: 100%;
        height: auto;
      }
      
      table {
        border-collapse: collapse;
        width: 100%;
      }
      
      th, td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #ddd;
      }
      
      blockquote {
        margin: 0 0 10px 0;
        padding: 10px 20px;
        border-left: 4px solid #ddd;
        background-color: #f9f9f9;
      }
      
      ul, ol {
        margin: 0 0 10px 20px;
        padding: 0;
      }
      
      li {
        margin: 0 0 5px 0;
      }
      
      /* Button styles */
      .button {
        display: inline-block;
        padding: 12px 24px;
        background-color: #0066cc;
        color: #ffffff;
        text-decoration: none;
        border-radius: 4px;
        font-weight: 600;
      }
      
      /* Responsive */
      @media only screen and (max-width: 600px) {
        body {
          font-size: 16px !important;
        }
        
        table {
          width: 100% !important;
        }
        
        .button {
          display: block !important;
          width: 100% !important;
        }
      }
    </style>
  `;
  
  // Wrap content in a container for email rendering
  const fullHtml = `
    <!DOCTYPE html>
    <html>
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        ${emailStyles}
      </head>
      <body>
        <div style="max-width: 600px; margin: 0 auto;">
          ${htmlContent}
        </div>
      </body>
    </html>
  `;

  return (
    <div className="email-content">
      <iframe
        srcDoc={fullHtml}
        className="w-full h-full border-0"
        style={{ minHeight: '400px' }}
        title="Email Preview"
        sandbox="allow-same-origin"
      />
    </div>
  );
}

export default EmailContent;