import React, { useEffect } from 'react';
import { X } from 'lucide-react';

// Global reference to the WordPress media frame
let globalMediaFrame = null;

/**
 * MediaLibraryModal - WordPress Media Library integration
 * Opens WordPress media modal for file selection
 */
function MediaLibraryModal({ onSelect, onClose, title = "Select Media" }) {
  useEffect(() => {

    // Check if wp.media is available
    if (typeof wp === 'undefined' || !wp.media) {
      console.error('WordPress media library is not available');
      // Fallback to mock data for development
      if (process.env.NODE_ENV === 'development') {
        // Mock media selection for development
        const mockMedia = {
          id: Date.now(),
          title: 'Sample Document.pdf',
          filename: 'sample-document.pdf',
          url: '/wp-content/uploads/2024/01/sample-document.pdf',
          mime: 'application/pdf',
          type: 'application/pdf',
          filesizeHumanReadable: '245 KB',
          filesize: 250880
        };
        
        // Show a simple dialog for development
        setTimeout(() => {
          const confirmed = window.confirm(`Development Mode: Select mock file "${mockMedia.title}"?`);
          if (confirmed) {
            onSelect(mockMedia);
          } else {
            onClose();
          }
        }, 100);
        return;
      }
      onClose();
      return;
    }

    // Use existing frame or create new one
    if (!globalMediaFrame) {
      // Create media frame only once
      globalMediaFrame = wp.media({
        title: title,
        button: {
          text: 'Select'
        },
        multiple: false
      });
    } else {
      // Update title if frame already exists
      globalMediaFrame.options.title = title;
    }

    // Remove any existing listeners
    globalMediaFrame.off('select');
    globalMediaFrame.off('close');

    // Handle media selection
    const handleSelect = () => {
      const attachment = globalMediaFrame.state().get('selection').first().toJSON();
      
      if (attachment) {
        const mediaData = {
          id: attachment.id,
          title: attachment.title,
          filename: attachment.filename,
          url: attachment.url,
          mime: attachment.mime || attachment.type,
          type: attachment.mime || attachment.type,
          filesizeHumanReadable: attachment.filesizeHumanReadable || formatFileSize(attachment.filesize),
          filesize: attachment.filesize
        };
        onSelect(mediaData);
      }
    };

    // Handle modal close
    const handleClose = () => {
      onClose();
    };

    // Attach new listeners
    globalMediaFrame.on('select', handleSelect);
    globalMediaFrame.on('close', handleClose);

    // Open the media frame
    globalMediaFrame.open();

    // Cleanup function
    return () => {
      if (globalMediaFrame) {
        globalMediaFrame.off('select', handleSelect);
        globalMediaFrame.off('close', handleClose);
      }
    };
  }, []); // Empty dependency array - only run once on mount

  // Helper function to format file size
  function formatFileSize(bytes) {
    if (!bytes) return 'Unknown size';
    
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
  }

  // Return null as WordPress media library handles its own UI
  return null;
}

// Fallback component for when WordPress media is not available
export function MediaLibraryFallback({ onSelect, onClose, title }) {
  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-xl p-6 max-w-md w-full">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-lg font-medium">{title}</h3>
          <button
            onClick={onClose}
            className="p-1 hover:bg-gray-100 rounded"
          >
            <X className="w-5 h-5" />
          </button>
        </div>
        
        <div className="text-center py-12">
          <p className="text-gray-500 mb-4">
            WordPress Media Library is not available in this context.
          </p>
          <p className="text-sm text-gray-400">
            This feature requires WordPress admin environment.
          </p>
        </div>
        
        <div className="flex justify-end gap-2 mt-6">
          <button
            onClick={onClose}
            className="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  );
}

export default React.memo(MediaLibraryModal);