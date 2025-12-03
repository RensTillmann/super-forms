import React, { useCallback } from 'react';
import TagInput from '@/components/ui/TagInput';

/**
 * Email Tag Input component
 * Wrapper around TagInput with email/Super Forms tag validation
 */
function EmailTagInput({
  value = '',
  onChange,
  placeholder = 'Enter email addresses...',
  className,
  'data-testid': testId,
}) {
  // Validator that accepts both emails and Super Forms tags
  const validate = useCallback((tag) => {
    const trimmed = tag.trim();

    // Accept Super Forms tags like {field_name} or {option_admin_email}
    if (/^\{[a-zA-Z0-9_]+\}$/.test(trimmed)) {
      return true;
    }

    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(trimmed);
  }, []);

  // Determine variant based on tag type
  const getVariant = useCallback((tag) => {
    if (/^\{[a-zA-Z0-9_]+\}$/.test(tag.trim())) {
      return 'field';
    }
    return 'email';
  }, []);

  return (
    <TagInput
      value={value}
      onChange={onChange}
      validate={validate}
      getVariant={getVariant}
      placeholder={placeholder}
      className={className}
      data-testid={testId}
    />
  );
}

export default EmailTagInput;
