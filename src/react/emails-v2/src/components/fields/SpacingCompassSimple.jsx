import React, { useState } from 'react';
import clsx from 'clsx';
import { Link, Unlink, Image, Palette } from 'lucide-react';
import ColorPicker from './ColorPicker';
import MediaLibraryButton from './MediaLibraryButton';

function SpacingCompassSimple({ 
  label,
  margin = { top: 0, right: 0, bottom: 0, left: 0 },
  border = { top: 0, right: 0, bottom: 0, left: 0 },
  borderStyle = 'solid',
  borderColor = '#000000',
  padding = { top: 0, right: 0, bottom: 0, left: 0 },
  backgroundColor = '#ffffff',
  backgroundImage = '',
  backgroundImageId = null,
  backgroundSize = 'cover',
  backgroundPosition = 'center',
  backgroundRepeat = 'no-repeat',
  onMarginChange,
  onBorderChange,
  onBorderStyleChange,
  onBorderColorChange,
  onPaddingChange,
  onBackgroundColorChange,
  onBackgroundImageChange,
  onBackgroundImageIdChange,
  onBackgroundSizeChange,
  onBackgroundPositionChange,
  onBackgroundRepeatChange,
  unit = 'px',
  min = 0,
  max = 100,
  className
}) {
  const [isMarginLinked, setIsMarginLinked] = useState(
    margin.top === margin.right && 
    margin.top === margin.bottom && 
    margin.top === margin.left
  );
  
  const [isBorderLinked, setIsBorderLinked] = useState(
    border.top === border.right && 
    border.top === border.bottom && 
    border.top === border.left
  );
  
  const [isPaddingLinked, setIsPaddingLinked] = useState(
    padding.top === padding.right && 
    padding.top === padding.bottom && 
    padding.top === padding.left
  );

  const [showImageInput, setShowImageInput] = useState(false);
  const [showBorderColor, setShowBorderColor] = useState(false);

  const handleMarginChange = (side, value) => {
    const numValue = parseInt(value) || 0;
    
    if (isMarginLinked) {
      onMarginChange({
        top: numValue,
        right: numValue,
        bottom: numValue,
        left: numValue
      });
    } else {
      onMarginChange({
        ...margin,
        [side]: numValue
      });
    }
  };

  const handleBorderChange = (side, value) => {
    const numValue = parseInt(value) || 0;
    
    // Auto-set border style to 'solid' when changing from 0 to positive
    const currentTotal = border.top + border.right + border.bottom + border.left;
    if (currentTotal === 0 && numValue > 0 && !borderStyle) {
      onBorderStyleChange('solid');
    }
    
    if (isBorderLinked) {
      onBorderChange({
        top: numValue,
        right: numValue,
        bottom: numValue,
        left: numValue
      });
    } else {
      onBorderChange({
        ...border,
        [side]: numValue
      });
    }
  };

  const handlePaddingChange = (side, value) => {
    const numValue = parseInt(value) || 0;
    
    if (isPaddingLinked) {
      onPaddingChange({
        top: numValue,
        right: numValue,
        bottom: numValue,
        left: numValue
      });
    } else {
      onPaddingChange({
        ...padding,
        [side]: numValue
      });
    }
  };

  const renderInput = (type, side, value, isLinked) => {
    const isMargin = type === 'margin';
    const isBorder = type === 'border';
    const isPadding = type === 'padding';
    
    let handleChange;
    if (isMargin) {
      handleChange = handleMarginChange;
    } else if (isBorder) {
      handleChange = handleBorderChange;
    } else {
      handleChange = handlePaddingChange;
    }
    
    // Hide non-top inputs when linked
    if (isLinked && side !== 'top') {
      return null;
    }
    
    return (
      <div className="ev2-flex ev2-items-center ev2-gap-1">
        <input
          type="number"
          min={min}
          max={max}
          value={value}
          onChange={(e) => {
            e.stopPropagation();
            handleChange(side, e.target.value);
          }}
          onFocus={(e) => e.stopPropagation()}
          onBlur={(e) => e.stopPropagation()}
          onClick={(e) => e.stopPropagation()}
          data-no-super-forms="true"
          className={clsx(
            'ev2-w-12 ev2-h-8 ev2-text-sm ev2-text-center ev2-border-2 ev2-rounded-md',
            'focus:ev2-ring-2 focus:ev2-border-transparent ev2-transition-all',
            'ev2-bg-white ev2-shadow-sm hover:ev2-shadow-md',
            isMargin 
              ? 'ev2-border-orange-400 focus:ev2-ring-orange-500 hover:ev2-border-orange-500' 
              : isBorder
              ? 'ev2-border-purple-400 focus:ev2-ring-purple-500 hover:ev2-border-purple-500'
              : 'ev2-border-blue-400 focus:ev2-ring-blue-500 hover:ev2-border-blue-500'
          )}
          title={`${type} ${isLinked ? '(all sides)' : side}`}
        />
        
        {/* Link button next to input */}
        <button
          type="button"
          onClick={() => {
            if (isMargin) setIsMarginLinked(!isMarginLinked);
            else if (isBorder) setIsBorderLinked(!isBorderLinked);
            else setIsPaddingLinked(!isPaddingLinked);
          }}
          className={clsx(
            'ev2-p-1 ev2-rounded ev2-transition-all ev2-shadow-sm',
            isLinked 
              ? `ev2-text-white hover:ev2-opacity-80 ${
                  isMargin ? 'ev2-bg-orange-500' : 
                  isBorder ? 'ev2-bg-purple-500' : 
                  'ev2-bg-blue-500'
                }` 
              : `ev2-bg-white ev2-border hover:ev2-bg-opacity-50 ${
                  isMargin ? 'ev2-text-orange-600 ev2-border-orange-300' : 
                  isBorder ? 'ev2-text-purple-600 ev2-border-purple-300' : 
                  'ev2-text-blue-600 ev2-border-blue-300'
                }`
          )}
          title={`${isLinked ? 'Unlink' : 'Link'} ${type}`}
        >
          {isLinked ? <Link className="ev2-w-3 ev2-h-3" /> : <Unlink className="ev2-w-3 ev2-h-3" />}
        </button>
      </div>
    );
  };

  return (
    <div className={clsx('ev2-space-y-4 ev2-max-w-xl', className)}>
      {label && (
        <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700">
          {label}
        </label>
      )}
      
      {/* Simple grid-based layout */}
      <div className="ev2-bg-gray-50 ev2-p-4 ev2-rounded-lg ev2-border ev2-border-gray-200">
        
        {/* Margin */}
        <div className="ev2-mb-4">
          <div className="ev2-flex ev2-items-center ev2-gap-2 ev2-mb-2">
            <div className="ev2-w-3 ev2-h-3 ev2-bg-orange-400 ev2-rounded-sm"></div>
            <span className="ev2-text-sm ev2-font-medium ev2-text-gray-700">Margin</span>
          </div>
          
          <div className="ev2-grid ev2-grid-cols-2 ev2-gap-2">
            {renderInput('margin', 'top', margin.top, isMarginLinked)}
            {renderInput('margin', 'right', margin.right, isMarginLinked)}
            {renderInput('margin', 'bottom', margin.bottom, isMarginLinked)}
            {renderInput('margin', 'left', margin.left, isMarginLinked)}
          </div>
        </div>

        {/* Border */}
        <div className="ev2-mb-4">
          <div className="ev2-flex ev2-items-center ev2-gap-2 ev2-mb-2">
            <div className="ev2-w-3 ev2-h-3 ev2-bg-purple-400 ev2-rounded-sm"></div>
            <span className="ev2-text-sm ev2-font-medium ev2-text-gray-700">Border</span>
          </div>
          
          <div className="ev2-grid ev2-grid-cols-2 ev2-gap-2 ev2-mb-2">
            {renderInput('border', 'top', border.top, isBorderLinked)}
            {renderInput('border', 'right', border.right, isBorderLinked)}
            {renderInput('border', 'bottom', border.bottom, isBorderLinked)}
            {renderInput('border', 'left', border.left, isBorderLinked)}
          </div>
          
          {/* Border style and color */}
          <div className="ev2-flex ev2-gap-2">
            <div className="ev2-flex ev2-gap-1">
              <button
                type="button"
                onClick={() => onBorderStyleChange('solid')}
                className={clsx(
                  'ev2-p-1.5 ev2-rounded ev2-transition-all ev2-w-8 ev2-h-8 ev2-flex ev2-items-center ev2-justify-center',
                  borderStyle === 'solid' 
                    ? 'ev2-bg-purple-500 ev2-text-white' 
                    : 'ev2-bg-white ev2-text-gray-600 ev2-border ev2-border-gray-300 hover:ev2-bg-gray-50'
                )}
                title="Solid"
              >
                <svg className="ev2-w-4 ev2-h-4" viewBox="0 0 16 16"><line x1="0" y1="8" x2="16" y2="8" stroke="currentColor" strokeWidth="2" /></svg>
              </button>
              <button
                type="button"
                onClick={() => onBorderStyleChange('dashed')}
                className={clsx(
                  'ev2-p-1.5 ev2-rounded ev2-transition-all ev2-w-8 ev2-h-8 ev2-flex ev2-items-center ev2-justify-center',
                  borderStyle === 'dashed' 
                    ? 'ev2-bg-purple-500 ev2-text-white' 
                    : 'ev2-bg-white ev2-text-gray-600 ev2-border ev2-border-gray-300 hover:ev2-bg-gray-50'
                )}
                title="Dashed"
              >
                <svg className="ev2-w-4 ev2-h-4" viewBox="0 0 16 16"><line x1="0" y1="8" x2="16" y2="8" stroke="currentColor" strokeWidth="2" strokeDasharray="4 2" /></svg>
              </button>
              <button
                type="button"
                onClick={() => onBorderStyleChange('dotted')}
                className={clsx(
                  'ev2-p-1.5 ev2-rounded ev2-transition-all ev2-w-8 ev2-h-8 ev2-flex ev2-items-center ev2-justify-center',
                  borderStyle === 'dotted' 
                    ? 'ev2-bg-purple-500 ev2-text-white' 
                    : 'ev2-bg-white ev2-text-gray-600 ev2-border ev2-border-gray-300 hover:ev2-bg-gray-50'
                )}
                title="Dotted"
              >
                <svg className="ev2-w-4 ev2-h-4" viewBox="0 0 16 16"><line x1="0" y1="8" x2="16" y2="8" stroke="currentColor" strokeWidth="2" strokeDasharray="1 2" /></svg>
              </button>
            </div>
            
            <input
              type="color"
              value={borderColor}
              onChange={(e) => onBorderColorChange(e.target.value)}
              className="ev2-w-8 ev2-h-8 ev2-rounded ev2-border ev2-border-gray-300 ev2-cursor-pointer"
              title="Border color"
            />
          </div>
        </div>

        {/* Padding */}
        <div className="ev2-mb-4">
          <div className="ev2-flex ev2-items-center ev2-gap-2 ev2-mb-2">
            <div className="ev2-w-3 ev2-h-3 ev2-bg-blue-400 ev2-rounded-sm"></div>
            <span className="ev2-text-sm ev2-font-medium ev2-text-gray-700">Padding</span>
          </div>
          
          <div className="ev2-grid ev2-grid-cols-2 ev2-gap-2">
            {renderInput('padding', 'top', padding.top, isPaddingLinked)}
            {renderInput('padding', 'right', padding.right, isPaddingLinked)}
            {renderInput('padding', 'bottom', padding.bottom, isPaddingLinked)}
            {renderInput('padding', 'left', padding.left, isPaddingLinked)}
          </div>
        </div>

        {/* Background */}
        <div>
          <div className="ev2-flex ev2-items-center ev2-gap-2 ev2-mb-2">
            <div className="ev2-w-3 ev2-h-3 ev2-bg-green-400 ev2-rounded-sm"></div>
            <span className="ev2-text-sm ev2-font-medium ev2-text-gray-700">Background</span>
          </div>
          
          <div className="ev2-flex ev2-gap-2">
            <input
              type="color"
              value={backgroundColor}
              onChange={(e) => onBackgroundColorChange(e.target.value)}
              className="ev2-w-8 ev2-h-8 ev2-rounded ev2-border ev2-border-gray-300 ev2-cursor-pointer"
              title="Background color"
            />
            
            <button
              type="button"
              onClick={() => setShowImageInput(!showImageInput)}
              className={clsx(
                'ev2-p-1.5 ev2-rounded ev2-transition-all ev2-shadow-sm ev2-w-8 ev2-h-8 ev2-flex ev2-items-center ev2-justify-center',
                showImageInput
                  ? 'ev2-bg-green-500 ev2-text-white'
                  : 'ev2-bg-white ev2-border ev2-border-gray-300 hover:ev2-bg-gray-50'
              )}
              title="Background image"
            >
              <Image className="ev2-w-4 ev2-h-4" />
            </button>
          </div>
        </div>
      </div>

      {/* Background image controls */}
      {showImageInput && (
        <div className="ev2-mt-4 ev2-p-4 ev2-bg-gray-50 ev2-rounded-lg ev2-border ev2-border-gray-200">
          <div className="ev2-space-y-3">
            <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700">
              Background Image
            </label>
            
            <div className="ev2-flex ev2-items-center ev2-gap-2">
              <MediaLibraryButton
                value={backgroundImage}
                onChange={onBackgroundImageChange}
                onChangeWithId={(data) => {
                  onBackgroundImageChange(data.url);
                  if (onBackgroundImageIdChange) {
                    onBackgroundImageIdChange(data.id);
                  }
                }}
                buttonText="Choose from Media Library"
                className="ev2-flex-shrink-0"
              />
              <span className="ev2-text-sm ev2-text-gray-500">or</span>
            </div>
            
            <input
              type="text"
              value={backgroundImage}
              onChange={(e) => onBackgroundImageChange(e.target.value)}
              placeholder="Enter image URL directly"
              className="ev2-w-full ev2-px-3 ev2-py-2 ev2-border ev2-border-gray-300 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-primary-500 focus:ev2-border-transparent ev2-text-sm"
            />
            
            {backgroundImage && (
              <div className="ev2-grid ev2-grid-cols-3 ev2-gap-3">
                <div>
                  <label className="ev2-text-xs ev2-font-medium ev2-text-gray-600 ev2-mb-1 ev2-block">
                    Size
                  </label>
                  <select
                    value={backgroundSize}
                    onChange={(e) => onBackgroundSizeChange(e.target.value)}
                    className="ev2-w-full ev2-px-2 ev2-py-1 ev2-text-sm ev2-border ev2-border-gray-300 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-primary-500 focus:ev2-border-transparent ev2-bg-white"
                  >
                    <option value="cover">Cover</option>
                    <option value="contain">Contain</option>
                    <option value="auto">Auto</option>
                    <option value="100% 100%">Stretch</option>
                  </select>
                </div>
                
                <div>
                  <label className="ev2-text-xs ev2-font-medium ev2-text-gray-600 ev2-mb-1 ev2-block">
                    Position
                  </label>
                  <select
                    value={backgroundPosition}
                    onChange={(e) => onBackgroundPositionChange(e.target.value)}
                    className="ev2-w-full ev2-px-2 ev2-py-1 ev2-text-sm ev2-border ev2-border-gray-300 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-primary-500 focus:ev2-border-transparent ev2-bg-white"
                  >
                    <option value="center">Center</option>
                    <option value="top">Top</option>
                    <option value="bottom">Bottom</option>
                    <option value="left">Left</option>
                    <option value="right">Right</option>
                    <option value="top left">Top Left</option>
                    <option value="top right">Top Right</option>
                    <option value="bottom left">Bottom Left</option>
                    <option value="bottom right">Bottom Right</option>
                  </select>
                </div>
                
                <div>
                  <label className="ev2-text-xs ev2-font-medium ev2-text-gray-600 ev2-mb-1 ev2-block">
                    Repeat
                  </label>
                  <select
                    value={backgroundRepeat}
                    onChange={(e) => onBackgroundRepeatChange(e.target.value)}
                    className="ev2-w-full ev2-px-2 ev2-py-1 ev2-text-sm ev2-border ev2-border-gray-300 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-primary-500 focus:ev2-border-transparent ev2-bg-white"
                  >
                    <option value="no-repeat">No Repeat</option>
                    <option value="repeat">Repeat</option>
                    <option value="repeat-x">Repeat X</option>
                    <option value="repeat-y">Repeat Y</option>
                  </select>
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Units indicator */}
      <div className="ev2-text-center ev2-text-xs ev2-text-gray-500">
        All spacing values in {unit}
      </div>
    </div>
  );
}

export default SpacingCompassSimple;