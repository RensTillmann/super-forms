import React from 'react';
import { Clock, Calendar } from 'lucide-react';
import clsx from 'clsx';

/**
 * ScheduledIndicator - Shows when an email is scheduled to be sent
 * Can accept either a simple date or a schedule object
 */
function ScheduledIndicator({ scheduledDate, schedule, className = '', size = 'normal' }) {
  // Support both old format (scheduledDate) and new format (schedule object)
  const hasSchedule = schedule?.enabled && schedule?.schedules?.length > 0;
  
  if (!scheduledDate && !hasSchedule) return null;

  const isSmall = size === 'small';
  
  // For the new schedule format, show a simplified indicator
  if (hasSchedule && !scheduledDate) {
    const scheduleCount = schedule.schedules.length;
    return (
      <div className={clsx(
        'inline-flex items-center gap-1',
        isSmall ? 'text-xs' : 'text-sm',
        'text-blue-600 bg-blue-50 rounded-full',
        isSmall ? 'px-2 py-0.5' : 'px-3 py-1',
        className
      )}>
        <Clock className={clsx(
          isSmall ? 'w-3 h-3' : 'w-4 h-4'
        )} />
        <span className="font-medium">
          {scheduleCount} {scheduleCount === 1 ? 'Schedule' : 'Schedules'}
        </span>
      </div>
    );
  }

  // Legacy support for simple scheduledDate
  const formattedDate = new Date(scheduledDate).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  });

  return (
    <div className={clsx(
      'inline-flex items-center gap-1',
      isSmall ? 'text-xs' : 'text-sm',
      'text-blue-600 bg-blue-50 rounded-full',
      isSmall ? 'px-2 py-0.5' : 'px-3 py-1',
      className
    )}>
      <Clock className={clsx(
        isSmall ? 'w-3 h-3' : 'w-4 h-4'
      )} />
      <span className="font-medium">Scheduled</span>
      {!isSmall && <span className="text-blue-500">•</span>}
      {!isSmall && <span>{formattedDate}</span>}
    </div>
  );
}

export default ScheduledIndicator;