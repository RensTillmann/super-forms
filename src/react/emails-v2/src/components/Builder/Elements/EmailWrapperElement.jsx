import React from 'react';

/**
 * Email Wrapper Element - System element representing the email client container
 * This is the outermost container with background color control only
 */
function EmailWrapperElement({ element, renderElements }) {
  const { children = [] } = element;

  return (
    <div 
      className="email-wrapper-content ev2-w-full ev2-min-h-[100vh] ev2-relative"
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
          'data-debug-min-height': '100vh',
          'data-debug-role': 'email-client-container'
        }
      })}
    >
      {children.length > 0 ? renderElements(children, element.id) : (
        <div className="ev2-p-8 ev2-text-center ev2-text-gray-400 ev2-border-2 ev2-border-dashed ev2-border-purple-200 ev2-rounded-lg ev2-min-h-[400px] ev2-flex ev2-flex-col ev2-items-center ev2-justify-center">
          <div className="ev2-mb-4">
            <div className="ev2-text-4xl ev2-mb-2">📧</div>
            <h3 className="ev2-text-lg ev2-font-medium ev2-text-gray-600 ev2-mb-2">Email Client Background</h3>
            <p className="ev2-text-sm ev2-text-gray-500 ev2-mb-4">
              Click to edit the email client background color
            </p>
            <div className="ev2-bg-purple-100 ev2-text-purple-700 ev2-px-3 ev2-py-1 ev2-rounded ev2-text-xs ev2-font-medium">
              Clickable Email Wrapper
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default EmailWrapperElement;