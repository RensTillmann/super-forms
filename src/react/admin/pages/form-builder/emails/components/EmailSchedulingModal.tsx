import React, { useState, useEffect } from 'react';
import { X, Calendar, Clock, Send, Plus, Trash2, Tag, Info, ChevronDown, ChevronRight, BookOpen, Mail, Bell, CalendarDays, Repeat, Timer } from 'lucide-react';
import clsx from 'clsx';

/**
 * EmailSchedulingModal - Modal for scheduling email send times
 * Supports multiple schedules/reminders with base date and offset functionality
 * Allows using {tags} for dynamic form values
 */
function EmailSchedulingModal({ isOpen, onClose, onSchedule, currentSchedule }) {
  const [schedules, setSchedules] = useState(currentSchedule?.schedules || []);
  const [showDocs, setShowDocs] = useState(false);
  const [expandedUseCase, setExpandedUseCase] = useState(null);
  const [validationErrors, setValidationErrors] = useState({});

  // Update state when currentSchedule changes
  React.useEffect(() => {
    setSchedules(currentSchedule?.schedules || []);
  }, [currentSchedule]);

  // Add a new empty schedule
  const addSchedule = () => {
    const newSchedule = {
      id: Date.now(), // Simple ID generation
      baseDate: '', // Empty means use submission date
      daysOffset: '',
      executionMethod: '', // No default - user must select
      time: '', // No default time
      offset: '' // No default offset
    };
    setSchedules([...schedules, newSchedule]);
  };

  // Remove a schedule
  const removeSchedule = (id) => {
    setSchedules(schedules.filter(s => s.id !== id));
  };

  // Update a specific schedule
  const updateSchedule = (id, field, value) => {
    setSchedules(schedules.map(schedule => 
      schedule.id === id 
        ? { ...schedule, [field]: value }
        : schedule
    ));
    // Clear validation error for this field when user starts typing
    const errorKey = `${id}_${field}`;
    if (validationErrors[errorKey]) {
      setValidationErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[errorKey];
        return newErrors;
      });
    }
  };

  // Get error message for a field
  const getFieldError = (scheduleId, fieldName) => {
    return validationErrors[`${scheduleId}_${fieldName}`] || null;
  };

  // Check if field has error
  const hasFieldError = (scheduleId, fieldName) => {
    return Boolean(validationErrors[`${scheduleId}_${fieldName}`]);
  };


  const handleSave = () => {
    // Validate schedules
    const fieldErrors = {};
    
    schedules.forEach((schedule, index) => {
      const scheduleId = schedule.id;
      
      // Validate days offset (required)
      if (!schedule.daysOffset && schedule.daysOffset !== '0') {
        fieldErrors[`${scheduleId}_daysOffset`] = 'Days offset is required';
      }
      
      // Validate execution method (required)
      if (!schedule.executionMethod) {
        fieldErrors[`${scheduleId}_executionMethod`] = 'Execution method is required';
      }
      
      // Validate based on execution method
      if ((schedule.executionMethod === 'time' || schedule.executionMethod.includes('time')) && !schedule.time) {
        fieldErrors[`${scheduleId}_time`] = 'Time is required for "time" execution method';
      }
      
      if ((schedule.executionMethod === 'offset' || schedule.executionMethod.includes('offset')) && !schedule.offset && schedule.offset !== '0') {
        fieldErrors[`${scheduleId}_offset`] = 'Offset is required for "offset" execution method';
      }
      
      // Validate date format if provided and not a tag
      if (schedule.baseDate && !schedule.baseDate.includes('{')) {
        const dateRegex = /^\d{2}-\d{2}-\d{4}$/;
        if (!dateRegex.test(schedule.baseDate)) {
          fieldErrors[`${scheduleId}_baseDate`] = 'Base date must be in DD-MM-YYYY format';
        }
      }
    });
    
    setValidationErrors(fieldErrors);
    
    if (Object.keys(fieldErrors).length > 0) {
      return;
    }
    
    onSchedule({
      enabled: schedules.length > 0,
      schedules
    });
    onClose();
  };

  const handleCancel = () => {
    // Reset to original values
    setSchedules(currentSchedule?.schedules || []);
    setValidationErrors({});
    onClose();
  };

  if (!isOpen) return null;

  return (
    <>
      {/* Backdrop */}
      <div 
        className="fixed inset-0 bg-black bg-opacity-50 z-40"
        onClick={handleCancel}
      />
      
      {/* Modal */}
      <div className="fixed inset-0 flex items-center justify-center z-50 pointer-events-none">
        <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden pointer-events-auto flex flex-col">
          {/* Header */}
          <div className="flex items-center justify-between border-b px-6 py-4">
            <h2 className="text-lg font-semibold text-gray-900">
              Schedule Settings
            </h2>
            <button
              onClick={handleCancel}
              className="p-1 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <X className="w-5 h-5 text-gray-500" />
            </button>
          </div>

          {/* Content */}
          <div className="flex-1 overflow-y-auto p-6">
            {/* Schedules section */}
            <div>
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-sm font-medium text-gray-700">
                    Schedules
                  </h3>
                  <button
                    onClick={addSchedule}
                    className="flex items-center gap-1 px-3 py-1 text-sm text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                  >
                    <Plus className="w-4 h-4" />
                    Add Item
                  </button>
                </div>

                {/* Tag help info */}
                <div className="mb-4 p-3 bg-blue-50 rounded-lg flex items-start gap-2">
                  <Info className="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" />
                  <div className="text-xs text-blue-800">
                    <p className="font-medium mb-1">You can use {'{tags}'} to retrieve form values</p>
                    <p>Examples: <code className="bg-blue-100 px-1 rounded">{'{date}'}</code>, <code className="bg-blue-100 px-1 rounded">{'{date;timestamp}'}</code>, <code className="bg-blue-100 px-1 rounded">{'{field_name}'}</code></p>
                  </div>
                </div>

                {/* Schedule items */}
                <div className="space-y-4">
                  {schedules.map((schedule, index) => (
                    <div 
                      key={schedule.id}
                      className="p-4 border border-gray-200 rounded-lg bg-gray-50"
                    >
                      <div className="flex items-start justify-between mb-4">
                        <h4 className="text-sm font-medium text-gray-900">
                          Schedule {index + 1}
                        </h4>
                        <button
                          onClick={() => removeSchedule(schedule.id)}
                          className="p-1 text-red-600 hover:bg-red-50 rounded"
                          title="Remove schedule"
                        >
                          <X className="w-4 h-4" />
                        </button>
                      </div>

                      {/* Horizontal layout for main fields */}
                      <div className="grid grid-cols-3 gap-3 mb-4">
                        {/* Base date */}
                        <div>
                          <label className="flex items-center gap-2 text-xs font-medium text-gray-700 mb-1">
                            <span>Base date</span>
                            {schedule.baseDate && schedule.baseDate.includes('{') && (
                              <Tag className="w-3 h-3 text-blue-600" />
                            )}
                          </label>
                          <input
                            type="text"
                            value={schedule.baseDate}
                            onChange={(e) => updateSchedule(schedule.id, 'baseDate', e.target.value)}
                            className={clsx(
                              "w-full px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:border-transparent font-mono",
                              hasFieldError(schedule.id, 'baseDate') 
                                ? "border-red-500 focus:ring-red-500" 
                                : "border-gray-300 focus:ring-blue-500"
                            )}
                            placeholder="DD-MM-YYYY or {date;timestamp}"
                          />
                          {getFieldError(schedule.id, 'baseDate') && (
                            <p className="mt-1 text-xs text-red-600">
                              {getFieldError(schedule.id, 'baseDate')}
                            </p>
                          )}
                        </div>

                        {/* Days offset */}
                        <div>
                          <label className="flex items-center gap-2 text-xs font-medium text-gray-700 mb-1">
                            <span>Days offset</span>
                            {schedule.daysOffset && schedule.daysOffset.includes('{') && (
                              <Tag className="w-3 h-3 text-blue-600" />
                            )}
                          </label>
                          <input
                            type="text"
                            value={schedule.daysOffset}
                            onChange={(e) => updateSchedule(schedule.id, 'daysOffset', e.target.value)}
                            className={clsx(
                              "w-full px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:border-transparent font-mono",
                              hasFieldError(schedule.id, 'daysOffset') 
                                ? "border-red-500 focus:ring-red-500" 
                                : "border-gray-300 focus:ring-blue-500"
                            )}
                            placeholder="0, 1, -1, or {tag}"
                          />
                          {getFieldError(schedule.id, 'daysOffset') && (
                            <p className="mt-1 text-xs text-red-600">
                              {getFieldError(schedule.id, 'daysOffset')}
                            </p>
                          )}
                        </div>

                        {/* Execution method */}
                        <div>
                          <label className="flex items-center gap-2 text-xs font-medium text-gray-700 mb-1">
                            <span>Execution method</span>
                            {schedule.executionMethod && schedule.executionMethod.includes('{') && (
                              <Tag className="w-3 h-3 text-blue-600" />
                            )}
                          </label>
                          <input
                            type="text"
                            value={schedule.executionMethod}
                            onChange={(e) => updateSchedule(schedule.id, 'executionMethod', e.target.value)}
                            className={clsx(
                              "w-full px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:border-transparent font-mono",
                              hasFieldError(schedule.id, 'executionMethod') 
                                ? "border-red-500 focus:ring-red-500" 
                                : "border-gray-300 focus:ring-blue-500"
                            )}
                            placeholder="instant, time, offset, or {tag}"
                          />
                          {getFieldError(schedule.id, 'executionMethod') && (
                            <p className="mt-1 text-xs text-red-600">
                              {getFieldError(schedule.id, 'executionMethod')}
                            </p>
                          )}
                        </div>
                      </div>

                      {/* Help text */}
                      <div className="text-xs text-gray-500 mb-4">
                        <p className="mb-1"><strong>Base date:</strong> Leave blank for submission date, or use DD-MM-YYYY format or {'{date;timestamp}'}</p>
                        <p><strong>Days offset:</strong> 0 = same day, 1 = next day, -1 = previous day</p>
                      </div>

                      {/* Time input (only for time method) */}
                      {(schedule.executionMethod === 'time' || schedule.executionMethod.includes('time')) && (
                        <div>
                          <label className="flex items-center gap-2 text-xs font-medium text-gray-700 mb-1">
                            <span>Time (24h format)</span>
                            {schedule.time && schedule.time.includes('{') && (
                              <Tag className="w-3 h-3 text-blue-600" />
                            )}
                          </label>
                          <input
                            type="text"
                            value={schedule.time || ''}
                            onChange={(e) => updateSchedule(schedule.id, 'time', e.target.value)}
                            className={clsx(
                              "w-full px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:border-transparent font-mono",
                              hasFieldError(schedule.id, 'time') 
                                ? "border-red-500 focus:ring-red-500" 
                                : "border-gray-300 focus:ring-blue-500"
                            )}
                            placeholder="14:30"
                          />
                          {getFieldError(schedule.id, 'time') && (
                            <p className="mt-1 text-xs text-red-600">
                              {getFieldError(schedule.id, 'time')}
                            </p>
                          )}
                          <p className="mt-1 text-xs text-gray-500">
                            Use 24h format: 09:00, 14:30, 18:00 or {'{field_name}'}
                          </p>
                        </div>
                      )}

                      {/* Offset input (only for offset method) */}
                      {(schedule.executionMethod === 'offset' || schedule.executionMethod.includes('offset')) && (
                        <div>
                          <label className="flex items-center gap-2 text-xs font-medium text-gray-700 mb-1">
                            <span>Offset (in hours)</span>
                            {schedule.offset && schedule.offset.includes('{') && (
                              <Tag className="w-3 h-3 text-blue-600" />
                            )}
                          </label>
                          <input
                            type="text"
                            value={schedule.offset || '0'}
                            onChange={(e) => updateSchedule(schedule.id, 'offset', e.target.value)}
                            className={clsx(
                              "w-full px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:border-transparent font-mono",
                              hasFieldError(schedule.id, 'offset') 
                                ? "border-red-500 focus:ring-red-500" 
                                : "border-gray-300 focus:ring-blue-500"
                            )}
                            placeholder="0"
                          />
                          {getFieldError(schedule.id, 'offset') && (
                            <p className="mt-1 text-xs text-red-600">
                              {getFieldError(schedule.id, 'offset')}
                            </p>
                          )}
                          <p className="mt-1 text-xs text-gray-500">
                            0 = instantly, 0.08 = 5 min, 0.5 = 30 min, 2 = 2 hours, -5 = 5 hours before
                          </p>
                        </div>
                      )}
                    </div>
                  ))}

                  {/* Empty state */}
                  {schedules.length === 0 && (
                    <div className="text-center py-8 text-gray-500">
                      <Clock className="w-12 h-12 mx-auto mb-3 text-gray-300" />
                      <p className="text-sm">No schedules configured</p>
                      <p className="text-xs mt-1">Click "Add Item" to create a schedule</p>
                    </div>
                  )}
                </div>
              </div>
              
              {/* Documentation Section */}
              <div className="mt-8 border-t pt-6">
                <button
                  onClick={() => setShowDocs(!showDocs)}
                  className="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors"
                >
                  {showDocs ? <ChevronDown className="w-4 h-4" /> : <ChevronRight className="w-4 h-4" />}
                  <BookOpen className="w-4 h-4" />
                  Documentation & Examples
                </button>
                
                {showDocs && (
                  <div className="mt-4 space-y-6">
                    {/* Use Cases */}
                    <div className="space-y-3">
                      <h4 className="text-sm font-semibold text-gray-900">Common Use Cases</h4>
                      
                      {/* Immediate Confirmation */}
                      <div className="border border-gray-200 rounded-lg">
                        <button
                          onClick={() => setExpandedUseCase(expandedUseCase === 'immediate' ? null : 'immediate')}
                          className="w-full p-3 flex items-center justify-between text-left hover:bg-gray-50 transition-colors"
                        >
                          <div className="flex items-center gap-2">
                            <Mail className="w-4 h-4 text-blue-600" />
                            <h5 className="text-xs font-semibold text-gray-800">
                              Immediate Confirmation Email
                            </h5>
                          </div>
                          {expandedUseCase === 'immediate' ? 
                            <ChevronDown className="w-4 h-4 text-gray-400" /> : 
                            <ChevronRight className="w-4 h-4 text-gray-400" />
                          }
                        </button>
                        {expandedUseCase === 'immediate' && (
                          <div className="px-3 pb-3">
                            <p className="text-xs text-gray-600 mb-2">Send a confirmation immediately after form submission.</p>
                            <div className="bg-gray-100 p-2 rounded text-xs font-mono">
                              <div>Base date: <span className="text-blue-600">(leave empty)</span></div>
                              <div>Days offset: <span className="text-blue-600">0</span></div>
                              <div>Execution: <span className="text-blue-600">Instant</span></div>
                            </div>
                          </div>
                        )}
                      </div>

                      {/* Next Day Follow-up */}
                      <div className="border border-gray-200 rounded-lg">
                        <button
                          onClick={() => setExpandedUseCase(expandedUseCase === 'nextday' ? null : 'nextday')}
                          className="w-full p-3 flex items-center justify-between text-left hover:bg-gray-50 transition-colors"
                        >
                          <div className="flex items-center gap-2">
                            <Bell className="w-4 h-4 text-orange-600" />
                            <h5 className="text-xs font-semibold text-gray-800">
                              Next Day Follow-up
                            </h5>
                          </div>
                          {expandedUseCase === 'nextday' ? 
                            <ChevronDown className="w-4 h-4 text-gray-400" /> : 
                            <ChevronRight className="w-4 h-4 text-gray-400" />
                          }
                        </button>
                        {expandedUseCase === 'nextday' && (
                          <div className="px-3 pb-3">
                            <p className="text-xs text-gray-600 mb-2">Send a follow-up email the day after submission at 9 AM.</p>
                            <div className="bg-gray-100 p-2 rounded text-xs font-mono">
                              <div>Base date: <span className="text-blue-600">(leave empty)</span></div>
                              <div>Days offset: <span className="text-blue-600">1</span></div>
                              <div>Execution: <span className="text-blue-600">At specific time</span></div>
                              <div>Time: <span className="text-blue-600">09:00</span></div>
                            </div>
                          </div>
                        )}
                      </div>

                      {/* Appointment Reminder */}
                      <div className="border border-gray-200 rounded-lg">
                        <button
                          onClick={() => setExpandedUseCase(expandedUseCase === 'appointment' ? null : 'appointment')}
                          className="w-full p-3 flex items-center justify-between text-left hover:bg-gray-50 transition-colors"
                        >
                          <div className="flex items-center gap-2">
                            <CalendarDays className="w-4 h-4 text-green-600" />
                            <h5 className="text-xs font-semibold text-gray-800">
                              Appointment Reminder
                            </h5>
                          </div>
                          {expandedUseCase === 'appointment' ? 
                            <ChevronDown className="w-4 h-4 text-gray-400" /> : 
                            <ChevronRight className="w-4 h-4 text-gray-400" />
                          }
                        </button>
                        {expandedUseCase === 'appointment' && (
                          <div className="px-3 pb-3">
                            <p className="text-xs text-gray-600 mb-2">Send reminder 1 day before an appointment date from the form.</p>
                            <div className="bg-gray-100 p-2 rounded text-xs font-mono">
                              <div>Base date: <span className="text-blue-600">{'{appointment_date}'}</span></div>
                              <div>Days offset: <span className="text-blue-600">-1</span></div>
                              <div>Execution: <span className="text-blue-600">At specific time</span></div>
                              <div>Time: <span className="text-blue-600">14:00</span></div>
                            </div>
                          </div>
                        )}
                      </div>

                      {/* Multiple Reminders */}
                      <div className="border border-gray-200 rounded-lg">
                        <button
                          onClick={() => setExpandedUseCase(expandedUseCase === 'weekly' ? null : 'weekly')}
                          className="w-full p-3 flex items-center justify-between text-left hover:bg-gray-50 transition-colors"
                        >
                          <div className="flex items-center gap-2">
                            <Repeat className="w-4 h-4 text-purple-600" />
                            <h5 className="text-xs font-semibold text-gray-800">
                              Weekly Follow-up Series
                            </h5>
                          </div>
                          {expandedUseCase === 'weekly' ? 
                            <ChevronDown className="w-4 h-4 text-gray-400" /> : 
                            <ChevronRight className="w-4 h-4 text-gray-400" />
                          }
                        </button>
                        {expandedUseCase === 'weekly' && (
                          <div className="px-3 pb-3">
                            <p className="text-xs text-gray-600 mb-2">Send emails 1, 7, and 14 days after submission.</p>
                            <div className="bg-gray-100 p-2 rounded text-xs font-mono">
                              <div className="grid grid-cols-3 gap-2">
                                <div className="p-2 bg-white rounded border border-gray-200">
                                  <div className="font-semibold text-gray-700 mb-1">Schedule 1:</div>
                                  <div className="text-gray-600">Days offset: <span className="text-blue-600">1</span></div>
                                  <div className="text-gray-600">Execution: <span className="text-blue-600">At 09:00</span></div>
                                </div>
                                <div className="p-2 bg-white rounded border border-gray-200">
                                  <div className="font-semibold text-gray-700 mb-1">Schedule 2:</div>
                                  <div className="text-gray-600">Days offset: <span className="text-blue-600">7</span></div>
                                  <div className="text-gray-600">Execution: <span className="text-blue-600">At 09:00</span></div>
                                </div>
                                <div className="p-2 bg-white rounded border border-gray-200">
                                  <div className="font-semibold text-gray-700 mb-1">Schedule 3:</div>
                                  <div className="text-gray-600">Days offset: <span className="text-blue-600">14</span></div>
                                  <div className="text-gray-600">Execution: <span className="text-blue-600">At 09:00</span></div>
                                </div>
                              </div>
                            </div>
                          </div>
                        )}
                      </div>
                    </div>

                    {/* Overview */}
                    <div className="bg-gray-50 p-4 rounded-lg">
                      <h4 className="text-sm font-semibold text-gray-900 mb-2">How Email Scheduling Works</h4>
                      <p className="text-xs text-gray-600 mb-3">
                        The email scheduler allows you to send emails at specific times relative to form submission. 
                        You can create multiple schedules to send follow-up emails, reminders, or notifications.
                      </p>
                      <div className="text-xs text-gray-600 space-y-1">
                        <p><strong>Base Date:</strong> The starting point for calculating when to send (default: form submission date)</p>
                        <p><strong>Days Offset:</strong> Number of days to add/subtract from the base date</p>
                        <p><strong>Execution Method:</strong> When exactly on that day to send the email</p>
                      </div>
                    </div>

                    {/* Tag and Time Reference Grid */}
                    <div className="grid grid-cols-2 gap-4">
                      {/* Tag Reference */}
                      <div className="bg-blue-50 p-4 rounded-lg">
                        <h4 className="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2">
                          <Tag className="w-4 h-4" />
                          Using Dynamic Tags
                        </h4>
                        <p className="text-xs text-gray-700 mb-3">
                          Tags allow you to use form field values dynamically. Any field with a tag icon supports dynamic values.
                        </p>
                        <div className="space-y-2 text-xs">
                          <div>
                            <code className="bg-blue-100 px-1 rounded">{'{date}'}</code>
                            <span className="text-gray-600 ml-2">Current date (25-03-2024 format)</span>
                          </div>
                          <div>
                            <code className="bg-blue-100 px-1 rounded">{'{date;timestamp}'}</code>
                            <span className="text-gray-600 ml-2">Unix timestamp</span>
                          </div>
                          <div>
                            <code className="bg-blue-100 px-1 rounded">{'{field_name}'}</code>
                            <span className="text-gray-600 ml-2">Value from form field with name "field_name"</span>
                          </div>
                          <div>
                            <code className="bg-blue-100 px-1 rounded">{'{appointment_date}'}</code>
                            <span className="text-gray-600 ml-2">Date from appointment_date field</span>
                          </div>
                        </div>
                      </div>

                      {/* Time Offset Reference */}
                      <div className="bg-amber-50 p-4 rounded-lg">
                        <h4 className="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2">
                          <Timer className="w-4 h-4" />
                          Time Offset Reference
                        </h4>
                        <p className="text-xs text-gray-700 mb-3">
                          When using "Time offset" execution method, specify hours as decimal numbers:
                        </p>
                        <div className="grid grid-cols-2 gap-2 text-xs">
                          <div><code className="bg-amber-100 px-1 rounded">0</code> = Instantly</div>
                          <div><code className="bg-amber-100 px-1 rounded">0.08</code> = 5 minutes</div>
                          <div><code className="bg-amber-100 px-1 rounded">0.25</code> = 15 minutes</div>
                          <div><code className="bg-amber-100 px-1 rounded">0.5</code> = 30 minutes</div>
                          <div><code className="bg-amber-100 px-1 rounded">1</code> = 1 hour</div>
                          <div><code className="bg-amber-100 px-1 rounded">2</code> = 2 hours</div>
                          <div><code className="bg-amber-100 px-1 rounded">24</code> = 24 hours</div>
                          <div><code className="bg-amber-100 px-1 rounded">-1</code> = 1 hour before</div>
                        </div>
                      </div>
                    </div>
                  </div>
                )}
              </div>
          </div>

          {/* Footer */}
          <div className="flex items-center justify-end gap-3 border-t px-6 py-4">
            <button
              onClick={handleCancel}
              className="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
            >
              Cancel
            </button>
            
            <button
              onClick={handleSave}
              className={clsx(
                'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                'flex items-center gap-2',
                'bg-blue-600 text-white hover:bg-blue-700'
              )}
            >
              <Send className="w-4 h-4" />
              Save Schedule
            </button>
          </div>
        </div>
      </div>
    </>
  );
}

export default EmailSchedulingModal;