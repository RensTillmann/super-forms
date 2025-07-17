import React, { useState } from 'react';
import clsx from 'clsx';
import { ChevronDown, ChevronRight } from 'lucide-react';

function PanelBody({ 
  title, 
  children, 
  initialOpen = true,
  icon,
  className
}) {
  const [isOpen, setIsOpen] = useState(initialOpen);

  return (
    <div className={clsx(
      'ev2-border-b ev2-border-gray-200 last:ev2-border-b-0',
      className
    )}>
      <button
        type="button"
        onClick={() => setIsOpen(!isOpen)}
        className="ev2-w-full ev2-px-4 ev2-py-3 ev2-flex ev2-items-center ev2-justify-between ev2-text-left hover:ev2-bg-gray-50 ev2-transition-colors"
      >
        <div className="ev2-flex ev2-items-center ev2-gap-2">
          {icon && (
            <span className="ev2-text-gray-500">{icon}</span>
          )}
          <h3 className="ev2-text-sm ev2-font-medium ev2-text-gray-900">{title}</h3>
        </div>
        {isOpen ? (
          <ChevronDown className="ev2-w-4 ev2-h-4 ev2-text-gray-400" />
        ) : (
          <ChevronRight className="ev2-w-4 ev2-h-4 ev2-text-gray-400" />
        )}
      </button>
      
      {isOpen && (
        <div className="ev2-px-4 ev2-py-3 ev2-space-y-4">
          {children}
        </div>
      )}
    </div>
  );
}

export default PanelBody;