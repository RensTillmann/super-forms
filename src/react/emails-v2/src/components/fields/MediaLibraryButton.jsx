import React, { useEffect, useRef } from 'react';
import clsx from 'clsx';
import { Image } from 'lucide-react';

function MediaLibraryButton({ 
  value, 
  onChange, 
  onChangeWithId, // New prop for getting both ID and URL
  buttonText = 'Select Image',
  className,
  multiple = false,
  allowedTypes = ['image']
}) {
  const frameRef = useRef(null);

  useEffect(() => {
    // Cleanup function to remove event listeners
    return () => {
      if (frameRef.current) {
        frameRef.current.off('select');
        frameRef.current = null;
      }
    };
  }, []);

  const openMediaLibrary = () => {
    // Check if wp.media is available
    if (!window.wp || !window.wp.media) {
      console.error('WordPress Media Library is not available');
      alert('Media Library is not available. Please ensure you are in the WordPress admin area.');
      return;
    }

    // Create media frame if it doesn't exist
    if (!frameRef.current) {
      frameRef.current = window.wp.media({
        title: 'Select Image',
        button: {
          text: 'Use this image'
        },
        multiple: multiple,
        library: {
          type: allowedTypes
        }
      });

      // Handle selection
      frameRef.current.on('select', function() {
        const attachment = frameRef.current.state().get('selection').first().toJSON();
        
        // Use the appropriate size URL
        let imageUrl = attachment.url;
        if (attachment.sizes) {
          // Prefer large size if available, otherwise use full
          if (attachment.sizes.large) {
            imageUrl = attachment.sizes.large.url;
          } else if (attachment.sizes.full) {
            imageUrl = attachment.sizes.full.url;
          }
        }
        
        // If onChangeWithId is provided, pass both ID and URL
        if (onChangeWithId) {
          onChangeWithId({
            id: attachment.id,
            url: imageUrl
          });
        } else {
          onChange(imageUrl);
        }
      });
    }

    // Open the frame
    frameRef.current.open();
  };

  return (
    <button
      type="button"
      onClick={openMediaLibrary}
      className={clsx(
        'ev2-inline-flex ev2-items-center ev2-gap-2 ev2-px-3 ev2-py-2',
        'ev2-bg-white ev2-border ev2-border-gray-300 ev2-rounded-md',
        'hover:ev2-bg-gray-50 hover:ev2-border-gray-400',
        'focus:ev2-ring-2 focus:ev2-ring-primary-500 focus:ev2-border-transparent',
        'ev2-transition-all ev2-text-sm ev2-font-medium',
        className
      )}
    >
      <Image className="ev2-w-4 ev2-h-4" />
      {buttonText}
    </button>
  );
}

export default MediaLibraryButton;