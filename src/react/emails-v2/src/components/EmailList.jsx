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
import useEmailStore from '../hooks/useEmailStore';
import ScheduledIndicator from './shared/ScheduledIndicator';
import EmailSchedulingModal from './EmailSchedulingModal';
import clsx from 'clsx';

function SortableEmailItem({ email, isActive, onSelect, onDelete, onSchedule }) {
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

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={clsx(
        'ev2-group ev2-relative ev2-p-3 ev2-rounded-lg ev2-cursor-pointer ev2-transition-all',
        isActive 
          ? 'ev2-bg-primary-100 ev2-border ev2-border-primary-300' 
          : 'ev2-bg-white ev2-border ev2-border-gray-200 hover:ev2-border-gray-300 hover:ev2-shadow-sm',
        isDragging && 'ev2-shadow-lg ev2-z-50'
      )}
      onClick={() => onSelect(email.id)}
    >
      {/* Drag Handle */}
      <div 
        {...attributes}
        {...listeners}
        className="ev2-absolute ev2-left-0 ev2-top-1/2 ev2--translate-y-1/2 ev2-w-1 ev2-h-8 ev2-bg-gray-300 ev2-rounded-r ev2-opacity-0 group-hover:ev2-opacity-100 ev2-transition-opacity ev2-cursor-move"
      ></div>
      
      {/* Email Status */}
      <div className="ev2-flex ev2-items-start ev2-gap-3">
        <div className={clsx(
          'ev2-w-2 ev2-h-2 ev2-rounded-full ev2-mt-1.5 ev2-flex-shrink-0',
          email.enabled ? 'ev2-bg-green-500' : 'ev2-bg-gray-300'
        )}></div>
        
        <div className="ev2-flex-1 ev2-min-w-0">
          <h4 className="ev2-font-medium ev2-text-sm ev2-text-gray-900 ev2-truncate">
            {email.description || 'Untitled Email'}
          </h4>
          <div className="ev2-flex ev2-items-center ev2-gap-2 ev2-mt-0.5">
            <p className="ev2-text-xs ev2-text-gray-500 ev2-truncate">
              {email.to || 'No recipient'}
            </p>
            {email.scheduled_date && (
              <ScheduledIndicator scheduledDate={email.scheduled_date} size="small" />
            )}
          </div>
        </div>
        
        {/* Action Buttons */}
        <div className="ev2-flex ev2-items-center ev2-gap-1">
          {/* Schedule Button */}
          <button
            onClick={(e) => {
              e.stopPropagation();
              onSchedule(email.id);
            }}
            className="ev2-opacity-0 group-hover:ev2-opacity-100 ev2-transition-opacity ev2-p-1 hover:ev2-bg-blue-50 ev2-rounded"
            title="Schedule email"
          >
            <Clock className="ev2-w-4 ev2-h-4 ev2-text-blue-500" />
          </button>
          
          {/* Delete Button */}
          <button
            onClick={(e) => {
              e.stopPropagation();
              onDelete(email.id);
            }}
            className="ev2-opacity-0 group-hover:ev2-opacity-100 ev2-transition-opacity ev2-p-1 hover:ev2-bg-red-50 ev2-rounded"
            title="Delete email"
          >
            <svg className="ev2-w-4 ev2-h-4 ev2-text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

  const handleScheduleUpdate = (scheduledDate) => {
    if (schedulingEmailId) {
      updateEmailField(schedulingEmailId, 'scheduled_date', scheduledDate);
      setShowSchedulingModal(false);
      setSchedulingEmailId(null);
    }
  };

  const schedulingEmail = emails.find(e => e.id === schedulingEmailId);

  return (
    <div className="ev2-bg-gray-50 ev2-rounded-lg ev2-p-4 ev2-h-full">
      <div className="ev2-flex ev2-items-center ev2-justify-between ev2-mb-4">
        <h3 className="ev2-font-semibold ev2-text-gray-900">Emails</h3>
        <button
          onClick={addEmail}
          className="ev2-bg-primary-600 ev2-text-white ev2-px-3 ev2-py-1 ev2-rounded-md ev2-text-sm hover:ev2-bg-primary-700 ev2-transition-colors"
        >
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
            <div className="ev2-space-y-2">
              {emails.map((email) => (
                <SortableEmailItem
                  key={email.id}
                  email={email}
                  isActive={email.id === activeEmailId}
                  onSelect={setActiveEmailId}
                  onDelete={handleDelete}
                  onSchedule={handleSchedule}
                />
              ))}
            </div>
          </SortableContext>
        </DndContext>
      ) : (
        <div className="ev2-text-center ev2-py-8 ev2-text-gray-500">
          <svg className="ev2-mx-auto ev2-h-12 ev2-w-12 ev2-text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          <p className="ev2-mt-2 ev2-text-sm">No emails configured</p>
          <button
            onClick={addEmail}
            className="ev2-mt-3 ev2-text-primary-600 hover:ev2-text-primary-700 ev2-text-sm ev2-font-medium"
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
        currentSchedule={schedulingEmail?.scheduled_date}
      />
    </div>
  );
}

export default EmailList;