import React, { useState } from 'react';
import useEmailStore from '../hooks/useEmailStore';
import Accordion from './shared/Accordion';
import TextField from './fields/TextField';
import TextareaField from './fields/TextareaField';
import CheckboxField from './fields/CheckboxField';
import SelectField from './fields/SelectField';
import RichTextEditor from './fields/RichTextEditor';
import FileField from './fields/FileField';
import ConditionalWrapper from './shared/ConditionalWrapper';
import Repeater from './shared/Repeater';

function EmailEditor() {
  const { activeEmailId, getActiveEmail, updateEmailField } = useEmailStore();
  const email = getActiveEmail();
  
  const [openSections, setOpenSections] = useState({
    basic: true,
    headers: false,
    content: false,
    attachments: false,
    template: false,
    advanced: false,
    conditional: false,
    schedule: false
  });


  if (!email) return null;

  const toggleSection = (section) => {
    setOpenSections(prev => ({
      ...prev,
      [section]: !prev[section]
    }));
  };

  const handleFieldChange = (path, value) => {
    updateEmailField(activeEmailId, path, value);
  };

  const logicOptions = [
    { value: '==', label: '== Equal' },
    { value: '!=', label: '!= Not equal' },
    { value: '??', label: '?? Contains' },
    { value: '!!', label: '!! Not contains' },
    { value: '>', label: '> Greater than' },
    { value: '<', label: '< Less than' },
    { value: '>=', label: '>= Greater than or equal to' },
    { value: '<=', label: '<= Less than or equal' }
  ];

  return (
    <div className="ev2-bg-white ev2-rounded-lg ev2-shadow-sm ev2-border ev2-border-gray-200 ev2-p-4 ev2-space-y-4">
          {/* Basic Settings */}
          <Accordion 
        title="Basic Settings" 
        isOpen={openSections.basic}
        onToggle={() => toggleSection('basic')}
      >
        <div className="ev2-space-y-4">
          <CheckboxField
            label="Enable this email"
            value={email.enabled}
            onChange={(value) => handleFieldChange('enabled', value)}
          />
          
          <TextField
            label="Description"
            value={email.description}
            onChange={(value) => handleFieldChange('description', value)}
            placeholder="e.g., Customer Notification"
            i18n={true}
          />
        </div>
      </Accordion>

      {/* Email Headers */}
      <Accordion 
        title="Email Headers" 
        isOpen={openSections.headers}
        onToggle={() => toggleSection('headers')}
      >
        <div className="ev2-space-y-4">
          <TextField
            label="To (recipients)"
            value={email.to}
            onChange={(value) => handleFieldChange('to', value)}
            placeholder="{email}"
            i18n={true}
            help="Separate multiple recipients with commas"
          />
          
          <div className="ev2-grid ev2-grid-cols-2 ev2-gap-4">
            <TextField
              label="From Email"
              value={email.from_email}
              onChange={(value) => handleFieldChange('from_email', value)}
              placeholder="no-reply@domain.com"
              i18n={true}
            />
            
            <TextField
              label="From Name"
              value={email.from_name}
              onChange={(value) => handleFieldChange('from_name', value)}
              placeholder="{option_blogname}"
              i18n={true}
            />
          </div>
          
          {/* Reply To */}
          <div className="ev2-border ev2-border-gray-200 ev2-rounded-lg ev2-p-4">
            <CheckboxField
              label="Set custom Reply-To address"
              value={email.reply_to?.enabled || false}
              onChange={(value) => handleFieldChange('reply_to.enabled', value)}
            />
            
            <ConditionalWrapper show={email.reply_to?.enabled}>
              <div className="ev2-mt-3 ev2-space-y-3 ev2-pl-6">
                <TextField
                  label="Reply-To Email"
                  value={email.reply_to?.email || ''}
                  onChange={(value) => handleFieldChange('reply_to.email', value)}
                  placeholder="reply@domain.com"
                  i18n={true}
                />
                
                <TextField
                  label="Reply-To Name"
                  value={email.reply_to?.name || ''}
                  onChange={(value) => handleFieldChange('reply_to.name', value)}
                  placeholder="Support Team"
                  i18n={true}
                />
              </div>
            </ConditionalWrapper>
          </div>
          
          <TextField
            label="CC"
            value={email.cc}
            onChange={(value) => handleFieldChange('cc', value)}
            placeholder="cc@domain.com"
            help="Separate multiple addresses with commas"
          />
          
          <TextField
            label="BCC"
            value={email.bcc}
            onChange={(value) => handleFieldChange('bcc', value)}
            placeholder="bcc@domain.com"
            help="Separate multiple addresses with commas"
          />
        </div>
      </Accordion>

      {/* Email Content */}
      <Accordion 
        title="Email Content" 
        isOpen={openSections.content}
        onToggle={() => toggleSection('content')}
      >
        <div className="ev2-space-y-4">
          <TextField
            label="Subject"
            value={email.subject}
            onChange={(value) => handleFieldChange('subject', value)}
            placeholder="New submission from {name}"
            i18n={true}
          />
          
          <RichTextEditor
            label="Email Body"
            value={email.body}
            onChange={(value) => handleFieldChange('body', value)}
            i18n={true}
          />
        </div>
      </Accordion>

      {/* Attachments */}
      <Accordion 
        title="Attachments" 
        isOpen={openSections.attachments}
        onToggle={() => toggleSection('attachments')}
      >
        <div className="ev2-space-y-4">
          <FileField
            label="File Attachments"
            value={email.attachments}
            onChange={(value) => handleFieldChange('attachments', value)}
            multiple={true}
            i18n={true}
            help="Hold Ctrl to select multiple files"
          />
          
          {/* CSV Attachment */}
          <div className="ev2-border ev2-border-gray-200 ev2-rounded-lg ev2-p-4">
            <CheckboxField
              label="Attach CSV file with form data"
              value={email.csv_attachment?.enabled || false}
              onChange={(value) => handleFieldChange('csv_attachment.enabled', value)}
            />
            
            <ConditionalWrapper show={email.csv_attachment?.enabled}>
              <div className="ev2-mt-3 ev2-space-y-3 ev2-pl-6">
                <TextField
                  label="CSV Filename"
                  value={email.csv_attachment?.name || 'super-csv-attachment'}
                  onChange={(value) => handleFieldChange('csv_attachment.name', value)}
                  i18n={true}
                />
                
                <div className="ev2-grid ev2-grid-cols-2 ev2-gap-4">
                  <TextField
                    label="Delimiter"
                    value={email.csv_attachment?.delimiter || ','}
                    onChange={(value) => handleFieldChange('csv_attachment.delimiter', value)}
                  />
                  
                  <TextField
                    label="Enclosure"
                    value={email.csv_attachment?.enclosure || '"'}
                    onChange={(value) => handleFieldChange('csv_attachment.enclosure', value)}
                  />
                </div>
              </div>
            </ConditionalWrapper>
          </div>
        </div>
      </Accordion>

      {/* Conditional Logic */}
      <Accordion 
        title="Conditional Logic" 
        isOpen={openSections.conditional}
        onToggle={() => toggleSection('conditional')}
      >
        <div className="ev2-space-y-4">
          <CheckboxField
            label="Only send this email when conditions are met"
            value={email.conditions?.enabled || false}
            onChange={(value) => handleFieldChange('conditions.enabled', value)}
          />
          
          <ConditionalWrapper show={email.conditions?.enabled}>
            <div className="ev2-pl-6 ev2-space-y-3">
              <div className="ev2-flex ev2-gap-3 ev2-items-end">
                <div className="ev2-flex-1">
                  <TextField
                    label="First Field"
                    value={email.conditions?.f1 || ''}
                    onChange={(value) => handleFieldChange('conditions.f1', value)}
                    placeholder="e.g. {field_name}"
                  />
                </div>
                
                <div className="ev2-w-40">
                  <SelectField
                    label="Condition"
                    value={email.conditions?.logic || '=='}
                    onChange={(value) => handleFieldChange('conditions.logic', value)}
                    options={logicOptions}
                  />
                </div>
                
                <div className="ev2-flex-1">
                  <TextField
                    label="Second Field/Value"
                    value={email.conditions?.f2 || ''}
                    onChange={(value) => handleFieldChange('conditions.f2', value)}
                    placeholder="e.g. true"
                  />
                </div>
              </div>
            </div>
          </ConditionalWrapper>
        </div>
      </Accordion>

      {/* Schedule Settings */}
      <Accordion 
        title="Schedule Settings" 
        isOpen={openSections.schedule}
        onToggle={() => toggleSection('schedule')}
      >
        <div className="ev2-space-y-4">
          <CheckboxField
            label="Enable scheduled execution"
            value={email.schedule?.enabled || false}
            onChange={(value) => handleFieldChange('schedule.enabled', value)}
          />
          
          <ConditionalWrapper show={email.schedule?.enabled}>
            <div className="ev2-pl-6">
              <Repeater
                label="Schedules"
                items={email.schedule?.schedules || []}
                onChange={(value) => handleFieldChange('schedule.schedules', value)}
                defaultItem={{
                  date: '',
                  days: '0',
                  method: 'time',
                  time: '09:00',
                  offset: '0'
                }}
                renderItem={(item, index, updateItem) => (
                  <div className="ev2-space-y-3">
                    <TextField
                      label="Base date (leave blank to use submission date)"
                      value={item.date || ''}
                      onChange={(value) => updateItem(index, { ...item, date: value })}
                      placeholder="25-03-2024"
                      help="English format: DD-MM-YYYY"
                    />
                    
                    <TextField
                      label="Days offset"
                      value={item.days || '0'}
                      onChange={(value) => updateItem(index, { ...item, days: value })}
                      help="0 = same day, 1 = next day, -1 = previous day"
                    />
                    
                    <SelectField
                      label="Execution method"
                      value={item.method || 'time'}
                      onChange={(value) => updateItem(index, { ...item, method: value })}
                      options={[
                        { value: 'instant', label: 'Instant' },
                        { value: 'time', label: 'At specific time' },
                        { value: 'offset', label: 'Time offset' }
                      ]}
                    />
                    
                    <ConditionalWrapper show={item.method === 'time'}>
                      <TextField
                        label="Time (24h format)"
                        value={item.time || '09:00'}
                        onChange={(value) => updateItem(index, { ...item, time: value })}
                        placeholder="14:30"
                      />
                    </ConditionalWrapper>
                    
                    <ConditionalWrapper show={item.method === 'offset'}>
                      <TextField
                        label="Offset (in hours)"
                        value={item.offset || '0'}
                        onChange={(value) => updateItem(index, { ...item, offset: value })}
                        help="0.5 = 30 min, 2 = 2 hours, -1 = 1 hour before"
                      />
                    </ConditionalWrapper>
                  </div>
                )}
              />
            </div>
          </ConditionalWrapper>
        </div>
      </Accordion>
    </div>
  );
}

export default EmailEditor;