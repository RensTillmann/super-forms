import React, { useRef, useState } from 'react';
import clsx from 'clsx';

function FileField({ 
  label, 
  value, 
  onChange, 
  help,
  required = false,
  error,
  accept = '*',
  multiple = false,
  className,
  useMediaLibrary = true
}) {
  const fileInputRef = useRef(null);
  const [isDragging, setIsDragging] = useState(false);

  const handleFileSelect = (e) => {
    const files = Array.from(e.target.files);
    if (!multiple && files.length > 0) {
      onChange(files[0]);
    } else {
      onChange(files);
    }
  };

  const handleDrop = (e) => {
    e.preventDefault();
    setIsDragging(false);
    
    const files = Array.from(e.dataTransfer.files);
    if (!multiple && files.length > 0) {
      onChange(files[0]);
    } else {
      onChange(files);
    }
  };

  const handleDragOver = (e) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = () => {
    setIsDragging(false);
  };

  const openMediaLibrary = () => {
    if (window.wp && window.wp.media) {
      const frame = window.wp.media({
        title: 'Select Files',
        button: {
          text: 'Use Selected Files'
        },
        multiple: multiple
      });

      frame.on('select', () => {
        const attachments = frame.state().get('selection').toJSON();
        if (!multiple && attachments.length > 0) {
          onChange({
            id: attachments[0].id,
            url: attachments[0].url,
            filename: attachments[0].filename
          });
        } else {
          onChange(attachments.map(att => ({
            id: att.id,
            url: att.url,
            filename: att.filename
          })));
        }
      });

      frame.open();
    } else {
      // Fallback to file input
      fileInputRef.current?.click();
    }
  };

  const removeFile = (index) => {
    if (multiple && Array.isArray(value)) {
      const newFiles = [...value];
      newFiles.splice(index, 1);
      onChange(newFiles);
    } else {
      onChange(null);
    }
  };

  const getFileDisplay = () => {
    if (!value) return null;

    const files = Array.isArray(value) ? value : [value];
    
    return (
      <div className="ev2-mt-2 ev2-space-y-2">
        {files.map((file, index) => (
          <div key={index} className="ev2-flex ev2-items-center ev2-justify-between ev2-p-2 ev2-bg-gray-50 ev2-rounded ev2-text-sm">
            <span className="ev2-text-gray-700 ev2-truncate">
              {file.filename || file.name || 'Selected file'}
            </span>
            <button
              type="button"
              onClick={() => removeFile(index)}
              className="ev2-text-red-500 hover:ev2-text-red-700 ev2-ml-2"
            >
              <svg className="ev2-w-4 ev2-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        ))}
      </div>
    );
  };

  return (
    <div className="ev2-space-y-1">
      {label && (
        <label className="ev2-flex ev2-items-center ev2-gap-2 ev2-text-sm ev2-font-medium ev2-text-gray-700">
          <span>{label}</span>
          {required && <span className="ev2-text-red-500">*</span>}
        </label>
      )}
      
      <div
        className={clsx(
          'ev2-relative ev2-border-2 ev2-border-dashed ev2-rounded-lg ev2-p-6 ev2-text-center ev2-transition-colors',
          isDragging 
            ? 'ev2-border-primary-500 ev2-bg-primary-50' 
            : 'ev2-border-gray-300 hover:ev2-border-gray-400',
          error && 'ev2-border-red-300',
          className
        )}
        onDrop={handleDrop}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
      >
        <input
          ref={fileInputRef}
          type="file"
          accept={accept}
          multiple={multiple}
          onChange={handleFileSelect}
          className="ev2-hidden"
        />
        
        <svg className="ev2-mx-auto ev2-h-12 ev2-w-12 ev2-text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" 
          />
        </svg>
        
        <p className="ev2-mt-2 ev2-text-sm ev2-text-gray-600">
          Drop files here or{' '}
          <button
            type="button"
            onClick={openMediaLibrary}
            className="ev2-text-primary-600 hover:ev2-text-primary-700 ev2-font-medium"
          >
            {useMediaLibrary ? 'open media library' : 'browse'}
          </button>
        </p>
        
        {!useMediaLibrary && (
          <button
            type="button"
            onClick={() => fileInputRef.current?.click()}
            className="ev2-mt-2 ev2-text-xs ev2-text-gray-500 hover:ev2-text-gray-700"
          >
            or select from computer
          </button>
        )}
      </div>
      
      {getFileDisplay()}
      
      {help && (
        <p className="ev2-text-xs ev2-text-gray-500">{help}</p>
      )}
      {error && (
        <p className="ev2-text-xs ev2-text-red-600">{error}</p>
      )}
    </div>
  );
}

export default FileField;