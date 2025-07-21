import React, { useState, useCallback } from 'react';
import { 
  Paperclip, 
  X, 
  Pencil, 
  FileText, 
  FileImage, 
  File, 
  ExternalLink,
  FileSpreadsheet,
  FileVideo,
  FileAudio,
  FileCode,
  FileArchive,
  FileType
} from 'lucide-react';
import MediaLibraryModal from './MediaLibraryModal';
import clsx from 'clsx';

// Custom PDF icon component
const PdfIcon = ({ className }) => (
  <svg 
    className={className} 
    viewBox="0 0 24 24" 
    fill="none" 
    stroke="currentColor" 
    strokeWidth="2" 
    strokeLinecap="round" 
    strokeLinejoin="round"
  >
    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
    <polyline points="14 2 14 8 20 8" />
    <text x="7" y="17" fontSize="8" fontWeight="600" fill="currentColor" stroke="none">PDF</text>
  </svg>
);

/**
 * AttachmentManager - Handles email attachments with media library integration
 * Displays attachments as previews with options to open, replace, or delete
 */
function AttachmentManager({ attachments = [], onChange, isBuilder = false }) {
  const [showMediaModal, setShowMediaModal] = useState(false);
  const [editingIndex, setEditingIndex] = useState(null);

  // Handle adding new attachment
  const handleAddAttachment = useCallback((media) => {
    const newAttachment = {
      id: media.id,
      name: media.filename || media.title || 'Untitled',
      url: media.url,
      type: media.mime || media.type || 'application/octet-stream',
      size: media.filesizeHumanReadable || media.filesize || 'Unknown size'
    };

    if (editingIndex !== null) {
      // Replace existing attachment
      const updated = [...attachments];
      updated[editingIndex] = newAttachment;
      onChange(updated);
      setEditingIndex(null);
    } else {
      // Add new attachment
      onChange([...attachments, newAttachment]);
    }
    
    setShowMediaModal(false);
  }, [attachments, onChange, editingIndex]);

  // Handle removing attachment
  const handleRemoveAttachment = (index) => {
    const updated = attachments.filter((_, i) => i !== index);
    onChange(updated);
  };

  // Handle edit attachment
  const handleEditAttachment = (index) => {
    setEditingIndex(index);
    setShowMediaModal(true);
  };

  // Handle modal close
  const handleModalClose = useCallback(() => {
    setShowMediaModal(false);
    setEditingIndex(null);
  }, []);

  // Get icon based on file type
  const getFileIcon = (type, filename = '') => {
    // Check MIME type first
    if (type.startsWith('image/')) return FileImage;
    if (type.includes('pdf')) return PdfIcon;
    if (type.includes('spreadsheet') || type.includes('excel') || type.includes('csv')) return FileSpreadsheet;
    if (type.includes('video/')) return FileVideo;
    if (type.includes('audio/')) return FileAudio;
    if (type.includes('zip') || type.includes('tar') || type.includes('archive')) return FileArchive;
    if (type.includes('text/plain')) return FileType;
    
    // Check file extension as fallback
    const ext = filename.split('.').pop()?.toLowerCase();
    switch (ext) {
      case 'pdf':
        return PdfIcon;
      case 'csv':
      case 'xls':
      case 'xlsx':
      case 'ods':
        return FileSpreadsheet;
      case 'doc':
      case 'docx':
      case 'odt':
      case 'rtf':
      case 'txt':
        return FileType;
      case 'mp4':
      case 'avi':
      case 'mov':
      case 'wmv':
      case 'flv':
      case 'webm':
        return FileVideo;
      case 'mp3':
      case 'wav':
      case 'ogg':
      case 'flac':
      case 'aac':
        return FileAudio;
      case 'zip':
      case 'rar':
      case '7z':
      case 'tar':
      case 'gz':
        return FileArchive;
      case 'js':
      case 'jsx':
      case 'ts':
      case 'tsx':
      case 'html':
      case 'css':
      case 'php':
      case 'py':
      case 'java':
      case 'cpp':
      case 'c':
      case 'json':
      case 'xml':
        return FileCode;
      case 'jpg':
      case 'jpeg':
      case 'png':
      case 'gif':
      case 'svg':
      case 'webp':
      case 'bmp':
      case 'ico':
        return FileImage;
      default:
        return File;
    }
  };

  if (!isBuilder && attachments.length === 0) {
    return null; // Don't show anything if not in builder mode and no attachments
  }

  return (
    <div className="ev2-border-t ev2-pt-4 ev2-mt-2.5">
      {/* Attach files button */}
      {isBuilder && (
        <button
          onClick={() => {
            setEditingIndex(null);
            setShowMediaModal(true);
          }}
          className="ev2-text-sm ev2-text-blue-600 hover:ev2-text-blue-700 ev2-flex ev2-items-center ev2-gap-1 ev2-mb-3"
        >
          <Paperclip className="ev2-w-3 ev2-h-3" />
          Attach files
        </button>
      )}

      {/* Attachment List */}
      {attachments.length > 0 && (
        <div className="ev2-flex ev2-flex-wrap ev2-gap-2">
          {attachments.map((attachment, index) => {
            const IconComponent = getFileIcon(attachment.type, attachment.name);
            
            return (
              <div
                key={`${attachment.id}-${index}`}
                className="ev2-group ev2-inline-flex ev2-items-center ev2-gap-2 ev2-px-3 ev2-py-2 ev2-rounded"
                style={{ backgroundColor: '#f5f5f5' }}
              >
                {/* File Icon */}
                <IconComponent className="ev2-w-4 ev2-h-4 ev2-text-gray-500 ev2-flex-shrink-0" />
                
                {/* File Info - all on one line */}
                <a
                  href={attachment.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="ev2-text-sm ev2-text-gray-700 group-hover:ev2-text-blue-600 ev2-transition-colors ev2-flex ev2-items-center ev2-gap-1"
                >
                  <span>{attachment.name}</span>
                  <ExternalLink className="ev2-w-3 ev2-h-3 ev2-opacity-0 group-hover:ev2-opacity-100 ev2-transition-opacity" />
                </a>
                
                {/* File size */}
                <span className="ev2-text-xs ev2-text-gray-500">({attachment.size})</span>
                
                {/* Actions */}
                {isBuilder && (
                  <div className="ev2-flex ev2-items-center ev2-gap-1 ev2-opacity-0 group-hover:ev2-opacity-100 ev2-transition-opacity">
                    <button
                      onClick={() => handleEditAttachment(index)}
                      className="ev2-p-1 ev2-text-blue-600 hover:ev2-text-blue-700"
                      title="Replace attachment"
                    >
                      <Pencil className="ev2-w-4 ev2-h-4" />
                    </button>
                    <button
                      onClick={() => handleRemoveAttachment(index)}
                      className="ev2-p-1 ev2-text-red-600 hover:ev2-text-red-700"
                      title="Remove attachment"
                    >
                      <X className="ev2-w-4 ev2-h-4" />
                    </button>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}

      {/* Media Library Modal */}
      {showMediaModal && (
        <MediaLibraryModal
          onSelect={handleAddAttachment}
          onClose={handleModalClose}
          title={editingIndex !== null ? "Replace Attachment" : "Select Attachment"}
        />
      )}
    </div>
  );
}

export default React.memo(AttachmentManager);