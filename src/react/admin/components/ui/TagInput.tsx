import React, { useState, useRef, useCallback } from 'react';
import Tag from './Tag';

type TagVariant = 'default' | 'email' | 'field' | 'success' | 'warning' | 'error';

interface TagInputProps {
  value?: string;
  onChange: (value: string) => void;
  validate?: (tag: string) => boolean;
  getVariant?: (tag: string) => TagVariant;
  placeholder?: string;
  className?: string;
  'data-testid'?: string;
}

/**
 * TagInput - Free-form tag input component
 *
 * Allows users to type values and add them as tags.
 * Supports Enter, comma, Tab to add tags, Backspace to remove.
 */
export default function TagInput({
  value = '',
  onChange,
  validate,
  getVariant,
  placeholder = 'Type and press Enter...',
  className,
  'data-testid': testId,
}: TagInputProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [inputValue, setInputValue] = useState('');

  // Parse comma-separated string to array
  const tags = value
    ? value.split(',').map(t => t.trim()).filter(Boolean)
    : [];

  // Add a new tag
  const addTag = useCallback((newTag: string) => {
    const trimmed = newTag.trim();
    if (!trimmed) return;

    // Check if already exists
    if (tags.includes(trimmed)) {
      setInputValue('');
      return;
    }

    // Validate if validator provided
    if (validate && !validate(trimmed)) {
      // Could show error state here
      return;
    }

    const newTags = [...tags, trimmed];
    onChange(newTags.join(', '));
    setInputValue('');
  }, [tags, onChange, validate]);

  // Remove a tag by index
  const removeTag = useCallback((index: number) => {
    const newTags = tags.filter((_, i) => i !== index);
    onChange(newTags.join(', '));
  }, [tags, onChange]);

  // Handle key events
  const handleKeyDown = useCallback((e: React.KeyboardEvent<HTMLInputElement>) => {
    const val = inputValue.trim();

    // Enter, Tab, or comma adds the tag
    if (e.key === 'Enter' || e.key === 'Tab' || e.key === ',') {
      if (val) {
        e.preventDefault();
        addTag(val);
      } else if (e.key === 'Tab') {
        // Allow Tab to move focus if no value
        return;
      } else {
        e.preventDefault();
      }
    }

    // Backspace removes last tag when input is empty
    if (e.key === 'Backspace' && !inputValue && tags.length > 0) {
      removeTag(tags.length - 1);
    }
  }, [inputValue, addTag, removeTag, tags.length]);

  // Handle paste - split by comma and add multiple
  const handlePaste = useCallback((e: React.ClipboardEvent<HTMLInputElement>) => {
    e.preventDefault();
    const pasted = e.clipboardData.getData('text');
    const pastedTags = pasted.split(',').map(t => t.trim()).filter(Boolean);
    const newTags = [...tags];

    pastedTags.forEach(tag => {
      if (!newTags.includes(tag) && (!validate || validate(tag))) {
        newTags.push(tag);
      }
    });

    onChange(newTags.join(', '));
  }, [tags, onChange, validate]);

  // Handle blur - add current input as tag
  const handleBlur = useCallback(() => {
    if (inputValue.trim()) {
      addTag(inputValue);
    }
  }, [inputValue, addTag]);

  // Determine variant for a tag
  const getTagVariant = useCallback((tag: string): TagVariant => {
    if (getVariant) return getVariant(tag);
    return 'default';
  }, [getVariant]);

  // Container styles
  const containerStyles: React.CSSProperties = {
    display: 'flex',
    flexWrap: 'wrap',
    alignItems: 'center',
    gap: '4px',
    minHeight: '32px',
    padding: '4px 8px',
    background: 'transparent',
    borderRadius: '6px',
    cursor: 'text',
  };

  // Input styles
  const inputStyles: React.CSSProperties = {
    flex: 1,
    minWidth: '120px',
    border: 'none',
    outline: 'none',
    padding: '2px 4px',
    fontSize: '14px',
    background: 'transparent',
    fontFamily: 'inherit',
  };

  return (
    <div
      data-testid={testId}
      className={className}
      style={containerStyles}
      onClick={() => inputRef.current?.focus()}
    >
      {tags.map((tag, index) => (
        <Tag
          key={`${tag}-${index}`}
          data-testid={testId ? `${testId}-tag-${index}` : undefined}
          variant={getTagVariant(tag)}
          onRemove={() => removeTag(index)}
        >
          {tag}
        </Tag>
      ))}
      <input
        ref={inputRef}
        type="text"
        value={inputValue}
        onChange={(e) => setInputValue(e.target.value)}
        onKeyDown={handleKeyDown}
        onPaste={handlePaste}
        onBlur={handleBlur}
        placeholder={tags.length === 0 ? placeholder : ''}
        style={inputStyles}
        data-testid={testId ? `${testId}-input` : undefined}
      />
    </div>
  );
}
