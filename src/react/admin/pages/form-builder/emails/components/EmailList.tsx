import { useState } from 'react';
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
import { Clock, Plus, Trash2, Copy } from 'lucide-react';
import useEmailStore from '../hooks/useEmailStore';
import ScheduledIndicator from './shared/ScheduledIndicator';
import EmailSchedulingModal from './EmailSchedulingModal';
import CustomButton, { IconButton } from '@/components/ui/CustomButton';
import clsx from 'clsx';

function SortableEmailItem({ email, isActive, onSelect, onDelete, onSchedule, onToggle, onDuplicate }) {
  const [isHovered, setIsHovered] = useState(false);

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
      data-testid={'email-item-' + email.id}
      className={clsx(
        'relative p-3 rounded-lg cursor-pointer transition-all',
        isActive
          ? 'bg-blue-50 border-2 border-blue-400 ring-2 ring-blue-200'
          : 'bg-white border border-gray-200 hover:border-gray-300',
        isDragging && 'z-50'
      )}
      onClick={() => onSelect(email.id)}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      {/* Email Content */}
      <div className="flex items-center gap-3">
        {/* Drag Handle - wider and centered on card border for easier grabbing */}
        <div
          data-testid="drag-handle"
          {...attributes}
          {...listeners}
          style={{ position: 'absolute', left: -4, top: '50%', transform: 'translateY(-50%)' }}
          className={clsx(
            'w-2 h-10 bg-gray-300 rounded transition-opacity cursor-move hover:bg-gray-400',
            isHovered ? 'opacity-100' : 'opacity-0'
          )}
        ></div>
        {/* Toggle Switch */}
        <div data-testid="toggle-container" className="flex-shrink-0 flex items-center">
          <button
            data-testid="email-toggle"
            type="button"
            onClick={(e) => {
              e.stopPropagation();
              onToggle(email.id, !email.enabled);
            }}
            className={clsx(
              'relative inline-flex items-center flex-shrink-0 cursor-pointer rounded-full border-0 p-0 m-0 transition-colors duration-200 ease-in-out focus:outline-none',
              email.enabled ? 'bg-green-500' : 'bg-gray-300'
            )}
            style={{ width: 36, height: 20, minHeight: 20, lineHeight: 'normal' }}
            title={email.enabled ? 'Disable email' : 'Enable email'}
          >
            <span
              data-testid="toggle-knob"
              className="pointer-events-none rounded-full bg-white shadow ring-0 transition-transform duration-200 ease-in-out"
              style={{
                position: 'absolute',
                top: 2,
                left: 2,
                width: 16,
                height: 16,
                transform: email.enabled ? 'translateX(16px)' : 'translateX(0)'
              }}
            />
          </button>
        </div>

        <div data-testid="email-info" className="flex-1 min-w-0 overflow-hidden">
          <span data-testid="email-name" className={clsx(
            'font-medium text-sm block whitespace-nowrap overflow-hidden text-ellipsis',
            email.enabled ? 'text-gray-900' : 'text-gray-400'
          )}>
            {email.description || 'Untitled Email'}
          </span>
          <span data-testid="email-subject" className={clsx(
            'text-xs block whitespace-nowrap overflow-hidden text-ellipsis',
            email.enabled ? 'text-gray-500' : 'text-gray-400'
          )}>
            {email.subject || 'No subject'}
          </span>
          {(email.scheduled_date || email.schedule) && (
            <div data-testid="schedule-indicator" className="mt-1">
              <ScheduledIndicator
                scheduledDate={email.scheduled_date}
                schedule={email.schedule}
                size="small"
              />
            </div>
          )}
        </div>

        {/* Action Buttons - hidden by default, flex on hover */}
        <div
          data-testid="email-actions"
          className={clsx(
            'items-center gap-0.5 flex-shrink-0',
            isHovered ? 'flex' : 'hidden'
          )}
        >
          {/* Duplicate Button */}
          <IconButton
            data-testid="duplicate-btn"
            variant="ghost"
            size="sm"
            onClick={(e) => {
              e.stopPropagation();
              onDuplicate(email.id);
            }}
            className="text-gray-400 hover:text-gray-600"
            title="Duplicate email"
          >
            <Copy className="w-4 h-4" />
          </IconButton>

          {/* Schedule Button */}
          <IconButton
            data-testid="schedule-btn"
            variant="ghost"
            size="sm"
            onClick={(e) => {
              e.stopPropagation();
              onSchedule(email.id);
            }}
            className="text-gray-400 hover:text-gray-600"
            title="Schedule email"
          >
            <Clock className="w-4 h-4" />
          </IconButton>

          {/* Delete Button */}
          <IconButton
            data-testid="delete-btn"
            variant="ghost"
            size="sm"
            onClick={(e) => {
              e.stopPropagation();
              onDelete(email.id);
            }}
            className="text-gray-400 hover:text-red-500"
            title="Delete email"
          >
            <Trash2 className="w-4 h-4" />
          </IconButton>
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
    duplicateEmail,
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
    <div data-testid="email-list" className="bg-gray-50 rounded-lg p-4 h-full">
      {/* Add Email button */}
      <div className="flex items-center justify-end mb-4">
        <CustomButton
          data-testid="add-email-btn"
          variant="ghost"
          onClick={addEmail}
        >
          <Plus className="w-4 h-4" />
          Add Email
        </CustomButton>
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
                  onDuplicate={duplicateEmail}
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
          <CustomButton
            variant="link"
            onClick={addEmail}
            className="mt-3"
          >
            Add your first email
          </CustomButton>
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