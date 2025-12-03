import React, { useState } from 'react';
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import {
  useSortable,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Clock } from 'lucide-react';
import useEmailStore from './hooks/useEmailStore';
import ScheduledIndicator from './shared/ScheduledIndicator';
import EmailSchedulingModal from './EmailSchedulingModal';
import clsx from 'clsx';

function SortableEmailItem({ email, isActive, onSelect, onDelete, onSchedule, onToggle }) {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id: email.id });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : 1,
  };

  const cardStyle = {
    ...style,
    boxShadow: isActive
      ? '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)'
      : '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)'
  };

  return (
    <div
      ref={setNodeRef}
      style={cardStyle}
      className={clsx(
        'group relative p-3 rounded-lg cursor-pointer transition-all',
        isActive
          ? 'bg-blue-50 border-2 border-blue-400 ring-2 ring-blue-200'
          : 'bg-white border border-gray-200 hover:border-gray-300',
        isDragging && 'z-50'
      )}
      onClick={() => onSelect(email.id)}
    >
      {/* Drag Handle */}
      <div
        {...attributes}
        {...listeners}
        className="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-gray-300 rounded-r opacity-0 group-hover:opacity-100 transition-opacity cursor-move"
      ></div>

      {/* Email Content */}
      <div className="flex items-start gap-3">
        {/* Toggle Switch */}
        <div className="flex-shrink-0 pt-0.5">
          <button
            type="button"
            onClick={(e) => {
              e.stopPropagation();
              onToggle(email.id, !email.enabled);
            }}
            className={clsx(
              'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
              email.enabled ? 'bg-green-500' : 'bg-gray-300'
            )}
            title={email.enabled ? 'Disable email' : 'Enable email'}
          >
            <span
              className={clsx(
                'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                email.enabled ? 'translate-x-4' : 'translate-x-0'
              )}
            />
          </button>
        </div>

        <div className="flex-1 min-w-0">
          <h4 className={clsx(
            'font-medium text-sm truncate',
            email.enabled ? 'text-gray-900' : 'text-gray-400'
          )}>
            {email.description || 'Untitled Email'}
          </h4>
          <p className={clsx(
            'text-xs truncate mt-0.5',
            email.enabled ? 'text-gray-500' : 'text-gray-400'
          )}>
            {email.subject || 'No subject'}
          </p>
          {(email.scheduled_date || email.schedule) && (
            <div className="mt-1">
              <ScheduledIndicator
                scheduledDate={email.scheduled_date}
                schedule={email.schedule}
                size="small"
              />
            </div>
          )}
        </div>

        {/* Action Buttons */}
        <div className="flex items-center gap-1">
          {/* Schedule Button */}
          <button
            onClick={(e) => {
              e.stopPropagation();
              onSchedule(email.id);
            }}
            className="opacity-0 group-hover:opacity-100 transition-opacity p-1 hover:bg-blue-50 rounded"
            title="Schedule email"
          >
            <Clock className="w-4 h-4 text-blue-500" />
          </button>

          {/* Delete Button */}
          <button
            onClick={(e) => {
              e.stopPropagation();
              onDelete(email.id);
            }}
            className="opacity-0 group-hover:opacity-100 transition-opacity p-1 hover:bg-red-50 rounded"
            title="Delete email"
          >
            <svg className="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  );
}

function EmailList() {
  const {
    emails,
    activeEmailId,
    setActiveEmailId,
    addEmail,
    removeEmail,
    reorderEmails,
    updateEmailField
  } = useEmailStore();

  const [schedulingEmailId, setSchedulingEmailId] = useState(null);
  const [showSchedulingModal, setShowSchedulingModal] = useState(false);

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 8,
      },
    }),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  const handleDragEnd = (event) => {
    const { active, over } = event;

    if (active.id !== over.id) {
      const oldIndex = emails.findIndex((email) => email.id === active.id);
      const newIndex = emails.findIndex((email) => email.id === over.id);

      if (oldIndex !== -1 && newIndex !== -1) {
        reorderEmails(oldIndex, newIndex);
      }
    }
  };

  const handleDelete = (emailId) => {
    if (window.confirm('Are you sure you want to delete this email?')) {
      removeEmail(emailId);
    }
  };

  const handleSchedule = (emailId) => {
    setSchedulingEmailId(emailId);
    setShowSchedulingModal(true);
  };

  const handleToggle = (emailId, enabled) => {
    updateEmailField(emailId, 'enabled', enabled);
  };

  const handleScheduleUpdate = (scheduleData) => {
    if (schedulingEmailId) {
      updateEmailField(schedulingEmailId, 'schedule', scheduleData);
      setShowSchedulingModal(false);
      setSchedulingEmailId(null);
    }
  };

  const schedulingEmail = emails.find(e => e.id === schedulingEmailId);

  return (
    <div className="bg-gray-50 rounded-lg p-4 h-full">
      {/* Add Email button */}
      <div className="flex items-center justify-end mb-4">
        <button
          type="button"
          onClick={addEmail}
          className="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-green-600 text-white shadow-sm hover:bg-green-700 focus:outline-none focus:bg-green-700"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          Add Email
        </button>
      </div>

      {emails.length > 0 ? (
        <DndContext
          sensors={sensors}
          collisionDetection={closestCenter}
          onDragEnd={handleDragEnd}
        >
          <SortableContext
            items={emails.map(email => email.id)}
            strategy={verticalListSortingStrategy}
          >
            <div className="space-y-2">
              {emails.map((email) => (
                <SortableEmailItem
                  key={email.id}
                  email={email}
                  isActive={email.id === activeEmailId}
                  onSelect={setActiveEmailId}
                  onDelete={handleDelete}
                  onSchedule={handleSchedule}
                  onToggle={handleToggle}
                />
              ))}
            </div>
          </SortableContext>
        </DndContext>
      ) : (
        <div className="text-center py-8 text-gray-500">
          <svg className="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          <p className="mt-2 text-sm">No emails configured</p>
          <button
            onClick={addEmail}
            className="mt-3 text-primary-600 hover:text-primary-700 text-sm font-medium"
          >
            Add your first email
          </button>
        </div>
      )}
      
      {/* Scheduling Modal */}
      <EmailSchedulingModal
        isOpen={showSchedulingModal}
        onClose={() => {
          setShowSchedulingModal(false);
          setSchedulingEmailId(null);
        }}
        onSchedule={handleScheduleUpdate}
        currentSchedule={schedulingEmail?.schedule}
      />
    </div>
  );
}

export default EmailList;