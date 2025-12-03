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
      <div className="mt-2 space-y-2">
        {files.map((file, index) => (
          <div key={index} className="flex items-center justify-between p-2 bg-gray-50 rounded text-sm">
            <span className="text-gray-700 truncate">
              {file.filename || file.name || 'Selected file'}
            </span>
            <button
              type="button"
              onClick={() => removeFile(index)}
              className="text-red-500 hover:text-red-700 ml-2"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        ))}
      </div>
    );
  };

  return (
    <div className="space-y-1">
      {label && (
        <label className="flex items-center gap-2 text-sm font-medium text-gray-700">
          <span>{label}</span>
          {required && <span className="text-red-500">*</span>}
        </label>
      )}
      
      <div
        className={clsx(
          'relative border-2 border-dashed rounded-lg p-6 text-center transition-colors',
          isDragging 
            ? 'border-primary-500 bg-primary-50' 
            : 'border-gray-300 hover:border-gray-400',
          error && 'border-red-300',
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
          className="hidden"
        />
        
        <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" 
          />
        </svg>
        
        <p className="mt-2 text-sm text-gray-600">
          Drop files here or{' '}
          <button
            type="button"
            onClick={openMediaLibrary}
            className="text-primary-600 hover:text-primary-700 font-medium"
          >
            {useMediaLibrary ? 'open media library' : 'browse'}
          </button>
        </p>
        
        {!useMediaLibrary && (
          <button
            type="button"
            onClick={() => fileInputRef.current?.click()}
            className="mt-2 text-xs text-gray-500 hover:text-gray-700"
          >
            or select from computer
          </button>
        )}
      </div>
      
      {getFileDisplay()}
      
      {help && (
        <p className="text-xs text-gray-500">{help}</p>
      )}
      {error && (
        <p className="text-xs text-red-600">{error}</p>
      )}
    </div>
  );
}

export default FileField;