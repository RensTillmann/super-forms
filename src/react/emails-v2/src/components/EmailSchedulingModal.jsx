import React, { useState } from 'react';
import { X, Calendar, Clock, Send } from 'lucide-react';
import clsx from 'clsx';

/**
 * EmailSchedulingModal - Modal for scheduling email send time
 */
function EmailSchedulingModal({ isOpen, onClose, onSchedule, currentSchedule }) {
  const [selectedDate, setSelectedDate] = useState(
    currentSchedule ? new Date(currentSchedule).toISOString().slice(0, 16) : ''
  );
  const [selectedTime, setSelectedTime] = useState('');

  // Preset time options
  const timePresets = [
    { label: 'In 30 minutes', value: 30 },
    { label: 'In 1 hour', value: 60 },
    { label: 'In 2 hours', value: 120 },
    { label: 'Tomorrow morning (9 AM)', value: 'tomorrow-9am' },
    { label: 'Tomorrow afternoon (2 PM)', value: 'tomorrow-2pm' },
  ];

  const handlePresetClick = (preset) => {
    const now = new Date();
    let scheduledDate;

    if (typeof preset.value === 'number') {
      // Minutes from now
      scheduledDate = new Date(now.getTime() + preset.value * 60000);
    } else if (preset.value === 'tomorrow-9am') {
      scheduledDate = new Date(now);
      scheduledDate.setDate(scheduledDate.getDate() + 1);
      scheduledDate.setHours(9, 0, 0, 0);
    } else if (preset.value === 'tomorrow-2pm') {
      scheduledDate = new Date(now);
      scheduledDate.setDate(scheduledDate.getDate() + 1);
      scheduledDate.setHours(14, 0, 0, 0);
    }

    setSelectedDate(scheduledDate.toISOString().slice(0, 16));
  };

  const handleSchedule = () => {
    if (selectedDate) {
      onSchedule(new Date(selectedDate).toISOString());
      onClose();
    }
  };

  const handleRemoveSchedule = () => {
    onSchedule(null);
    onClose();
  };

  if (!isOpen) return null;

  return (
    <>
      {/* Backdrop */}
      <div 
        className="ev2-fixed ev2-inset-0 ev2-bg-black ev2-bg-opacity-50 ev2-z-40"
        onClick={onClose}
      />
      
      {/* Modal */}
      <div className="ev2-fixed ev2-inset-0 ev2-flex ev2-items-center ev2-justify-center ev2-z-50 ev2-pointer-events-none">
        <div className="ev2-bg-white ev2-rounded-lg ev2-shadow-xl ev2-w-full ev2-max-w-md ev2-pointer-events-auto">
          {/* Header */}
          <div className="ev2-flex ev2-items-center ev2-justify-between ev2-border-b ev2-px-6 ev2-py-4">
            <h2 className="ev2-text-lg ev2-font-semibold ev2-text-gray-900">
              Schedule Email
            </h2>
            <button
              onClick={onClose}
              className="ev2-p-1 hover:ev2-bg-gray-100 ev2-rounded-lg ev2-transition-colors"
            >
              <X className="ev2-w-5 ev2-h-5 ev2-text-gray-500" />
            </button>
          </div>

          {/* Content */}
          <div className="ev2-p-6">
            {/* Quick presets */}
            <div className="ev2-mb-6">
              <h3 className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-3">
                Quick options
              </h3>
              <div className="ev2-grid ev2-grid-cols-2 ev2-gap-2">
                {timePresets.map((preset) => (
                  <button
                    key={preset.value}
                    onClick={() => handlePresetClick(preset)}
                    className="ev2-px-3 ev2-py-2 ev2-text-sm ev2-text-gray-700 ev2-bg-gray-100 hover:ev2-bg-gray-200 ev2-rounded-lg ev2-transition-colors ev2-text-left"
                  >
                    {preset.label}
                  </button>
                ))}
              </div>
            </div>

            {/* Custom date/time picker */}
            <div className="ev2-mb-6">
              <h3 className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-3">
                Or choose a specific time
              </h3>
              <div className="ev2-flex ev2-items-center ev2-gap-2">
                <div className="ev2-relative ev2-flex-1">
                  <Calendar className="ev2-absolute ev2-left-3 ev2-top-1/2 ev2--translate-y-1/2 ev2-w-4 ev2-h-4 ev2-text-gray-400" />
                  <input
                    type="datetime-local"
                    value={selectedDate}
                    onChange={(e) => setSelectedDate(e.target.value)}
                    min={new Date().toISOString().slice(0, 16)}
                    className="ev2-w-full ev2-pl-10 ev2-pr-3 ev2-py-2 ev2-border ev2-border-gray-300 ev2-rounded-lg focus:ev2-outline-none focus:ev2-ring-2 focus:ev2-ring-blue-500 focus:ev2-border-transparent"
                  />
                </div>
              </div>
            </div>

            {/* Current schedule info */}
            {currentSchedule && (
              <div className="ev2-mb-6 ev2-p-3 ev2-bg-blue-50 ev2-rounded-lg">
                <p className="ev2-text-sm ev2-text-blue-800">
                  Currently scheduled for: {new Date(currentSchedule).toLocaleString()}
                </p>
              </div>
            )}
          </div>

          {/* Footer */}
          <div className="ev2-flex ev2-items-center ev2-justify-between ev2-border-t ev2-px-6 ev2-py-4">
            {currentSchedule ? (
              <button
                onClick={handleRemoveSchedule}
                className="ev2-px-4 ev2-py-2 ev2-text-sm ev2-text-red-600 hover:ev2-bg-red-50 ev2-rounded-lg ev2-transition-colors"
              >
                Remove Schedule
              </button>
            ) : (
              <button
                onClick={onClose}
                className="ev2-px-4 ev2-py-2 ev2-text-sm ev2-text-gray-600 hover:ev2-bg-gray-100 ev2-rounded-lg ev2-transition-colors"
              >
                Cancel
              </button>
            )}
            
            <button
              onClick={handleSchedule}
              disabled={!selectedDate}
              className={clsx(
                'ev2-px-4 ev2-py-2 ev2-text-sm ev2-font-medium ev2-rounded-lg ev2-transition-colors',
                'ev2-flex ev2-items-center ev2-gap-2',
                selectedDate
                  ? 'ev2-bg-blue-600 ev2-text-white hover:ev2-bg-blue-700'
                  : 'ev2-bg-gray-200 ev2-text-gray-400 ev2-cursor-not-allowed'
              )}
            >
              <Clock className="ev2-w-4 ev2-h-4" />
              Schedule Email
            </button>
          </div>
        </div>
      </div>
    </>
  );
}

export default EmailSchedulingModal;