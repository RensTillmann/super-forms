import React from 'react';

/**
 * Email Wrapper Element - System element representing the email client container
 * This is the outermost container with background color control only
 */
function EmailWrapperElement({ element, renderElements }) {
  const { children = [] } = element;

  return (
    <div 
      className="email-wrapper-content w-full min-h-[200px] relative"
      {...({
        // Always visible identification
        'data-component': 'EmailWrapperElement',
        'data-element-type': 'emailWrapper',
        'data-element-id': element.id,
        'data-is-system': 'true',
        'data-children-count': children.length,
        
        // Development debugging attributes
        ...(!process.env.NODE_ENV || process.env.NODE_ENV !== 'production') && {
          'data-debug-background': element.props?.backgroundColor || 'default',
          'data-debug-min-height': '200px',
          'data-debug-role': 'email-client-container'
        }
      })}
    >
      {children.length > 0 ? renderElements(children, element.id) : (
        <div className="p-6 text-center text-gray-400 border-2 border-dashed border-purple-200 rounded-lg min-h-[150px] flex flex-col items-center justify-center">
          <div className="mb-4">
            <div className="text-4xl mb-2">📧</div>
            <h3 className="text-lg font-medium text-gray-600 mb-2">Email Client Background</h3>
            <p className="text-sm text-gray-500 mb-4">
              Click to edit the email client background color
            </p>
            <div className="bg-purple-100 text-purple-700 px-3 py-1 rounded text-xs font-medium">
              Clickable Email Wrapper
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default EmailWrapperElement;