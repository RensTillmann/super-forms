import React from 'react';
import { Clock, Calendar } from 'lucide-react';
import clsx from 'clsx';

/**
 * ScheduledIndicator - Shows when an email is scheduled to be sent
 */
function ScheduledIndicator({ scheduledDate, className = '', size = 'normal' }) {
  if (!scheduledDate) return null;

  const formattedDate = new Date(scheduledDate).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  });

  const isSmall = size === 'small';

  return (
    <div className={clsx(
      'ev2-inline-flex ev2-items-center ev2-gap-1',
      isSmall ? 'ev2-text-xs' : 'ev2-text-sm',
      'ev2-text-blue-600 ev2-bg-blue-50 ev2-rounded-full',
      isSmall ? 'ev2-px-2 ev2-py-0.5' : 'ev2-px-3 ev2-py-1',
      className
    )}>
      <Clock className={clsx(
        isSmall ? 'ev2-w-3 ev2-h-3' : 'ev2-w-4 ev2-h-4'
      )} />
      <span className="ev2-font-medium">Scheduled</span>
      {!isSmall && <span className="ev2-text-blue-500">•</span>}
      {!isSmall && <span>{formattedDate}</span>}
    </div>
  );
}

export default ScheduledIndicator;