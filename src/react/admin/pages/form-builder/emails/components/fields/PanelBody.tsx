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
      'border-b border-gray-200 last:border-b-0',
      className
    )}>
      <button
        type="button"
        onClick={() => setIsOpen(!isOpen)}
        className="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-gray-50 transition-colors"
      >
        <div className="flex items-center gap-2">
          {icon && (
            <span className="text-gray-500">{icon}</span>
          )}
          <h3 className="text-sm font-medium text-gray-900">{title}</h3>
        </div>
        {isOpen ? (
          <ChevronDown className="w-4 h-4 text-gray-400" />
        ) : (
          <ChevronRight className="w-4 h-4 text-gray-400" />
        )}
      </button>
      
      {isOpen && (
        <div className="px-4 py-3 space-y-4">
          {children}
        </div>
      )}
    </div>
  );
}

export default PanelBody;