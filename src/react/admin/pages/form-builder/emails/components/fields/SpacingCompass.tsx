import React, { useState, useCallback, useRef, useEffect } from 'react';
import clsx from 'clsx';
import { Link, Unlink, Image, Palette } from 'lucide-react';
import ColorPicker from './ColorPicker';
import MediaLibraryButton from './MediaLibraryButton';
import DraggableNumberInput from './DraggableNumberInput';

function SpacingCompass({ 
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
  
  // Debounced color change for smooth performance
  const colorChangeTimeoutRef = useRef(null);
  const [localBackgroundColor, setLocalBackgroundColor] = useState(backgroundColor);
  
  const debouncedBackgroundColorChange = useCallback((color) => {
    setLocalBackgroundColor(color); // Update UI immediately
    
    // Clear previous timeout
    if (colorChangeTimeoutRef.current) {
      clearTimeout(colorChangeTimeoutRef.current);
    }
    
    // Set new timeout for actual update
    colorChangeTimeoutRef.current = setTimeout(() => {
      onBackgroundColorChange(color);
    }, 150); // 150ms debounce
  }, [onBackgroundColorChange]);
  
  // Sync local state when prop changes (from external updates)
  useEffect(() => {
    setLocalBackgroundColor(backgroundColor);
  }, [backgroundColor]);

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
      <div className="flex items-center gap-1">
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
            'w-12 h-8 text-sm text-center border-2 rounded-md',
            'focus:ring-2 focus:border-transparent transition-all',
            'bg-white shadow-sm hover:shadow-md',
            isMargin 
              ? 'border-orange-400 focus:ring-orange-500 hover:border-orange-500' 
              : isBorder
              ? 'border-purple-400 focus:ring-purple-500 hover:border-purple-500'
              : 'border-blue-400 focus:ring-blue-500 hover:border-blue-500'
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
            'p-1 rounded transition-all shadow-sm',
            isLinked 
              ? `text-white hover:opacity-80 ${
                  isMargin ? 'bg-orange-500' : 
                  isBorder ? 'bg-purple-500' : 
                  'bg-blue-500'
                }` 
              : `bg-white border hover:bg-opacity-50 ${
                  isMargin ? 'text-orange-600 border-orange-300' : 
                  isBorder ? 'text-purple-600 border-purple-300' : 
                  'text-blue-600 border-blue-300'
                }`
          )}
          title={`${isLinked ? 'Unlink' : 'Link'} ${type}`}
        >
          {isLinked ? <Link className="w-3 h-3" /> : <Unlink className="w-3 h-3" />}
        </button>
      </div>
    );
  };

  return (
    <div className={clsx('space-y-4 max-w-xl', className)}>
      {label && (
        <label className="text-sm font-medium text-gray-700">
          {label}
        </label>
      )}
      
      {/* Simple margin layer - starting from scratch */}
      <div 
        className="relative mx-auto bg-orange-100 border-2 border-dashed border-orange-300 rounded-lg w-fit h-fit p-12"
        {...(process.env.NODE_ENV === 'development' && {
          'data-spacing-layer': 'margin',
          'data-testid': 'spacing-margin-layer'
        })}
      >
        
        {/* Margin label positioned to the left of the top input */}
        <span className="absolute top-2 left-1/2 transform -translate-x-1/2 -translate-x-[80px] text-sm font-medium text-orange-600">
          Margin
        </span>
        
        {/* Margin Top Input - centered */}
        <DraggableNumberInput
          min={min}
          max={max}
          value={margin.top}
          onChange={(e) => {
            e.stopPropagation();
            handleMarginChange('top', e.target.value);
          }}
          onFocus={(e) => e.stopPropagation()}
          onBlur={(e) => e.stopPropagation()}
          onClick={(e) => e.stopPropagation()}
          data-no-super-forms="true"
          className="absolute top-2 left-1/2 transform -translate-x-1/2 w-12 h-8 text-sm text-center border-2 border-orange-400 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all bg-white shadow-sm hover:shadow-md hover:border-orange-500"
          title={`margin ${isMarginLinked ? '(all sides)' : 'top'}`}
          sensitivity={0.5}
          step={1}
        />
        
        {/* Link button positioned to the right of the centered input */}
        <button
          type="button"
          onClick={() => setIsMarginLinked(!isMarginLinked)}
          className={clsx(
            'absolute top-2 left-1/2 transform -translate-x-1/2 translate-x-[32px] w-8 h-8 rounded transition-all shadow-sm flex items-center justify-center',
            isMarginLinked 
              ? 'bg-orange-500 text-white hover:bg-orange-600' 
              : 'bg-white text-orange-600 border border-orange-300 hover:bg-orange-50'
          )}
          title={isMarginLinked ? 'Unlink margins' : 'Link margins'}
        >
          {isMarginLinked ? <Link className="w-3 h-3" /> : <Unlink className="w-3 h-3" />}
        </button>

        {/* Margin Right Input */}
        {(!isMarginLinked || margin.right !== margin.top) && (
          <div className="absolute right-2.5 top-1/2 transform -translate-y-1/2">
            <DraggableNumberInput
              min={min}
              max={max}
              value={margin.right}
              onChange={(e) => {
                e.stopPropagation();
                handleMarginChange('right', e.target.value);
              }}
              onFocus={(e) => e.stopPropagation()}
              onBlur={(e) => e.stopPropagation()}
              onClick={(e) => e.stopPropagation()}
              data-no-super-forms="true"
              className="w-12 h-8 text-sm text-center border-2 border-orange-400 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all bg-white shadow-sm hover:shadow-md hover:border-orange-500"
              title="margin right"
              sensitivity={0.5}
              step={1}
            />
          </div>
        )}

        {/* Margin Bottom Input */}
        {(!isMarginLinked || margin.bottom !== margin.top) && (
          <div className="absolute bottom-2 left-1/2 transform -translate-x-1/2">
            <input
              type="number"
              min={min}
              max={max}
              value={margin.bottom}
              onChange={(e) => {
                e.stopPropagation();
                handleMarginChange('bottom', e.target.value);
              }}
              onFocus={(e) => e.stopPropagation()}
              onBlur={(e) => e.stopPropagation()}
              onClick={(e) => e.stopPropagation()}
              data-no-super-forms="true"
              className="w-12 h-8 text-sm text-center border-2 border-orange-400 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all bg-white shadow-sm hover:shadow-md hover:border-orange-500"
              title="margin bottom"
            />
          </div>
        )}

        {/* Margin Left Input */}
        {(!isMarginLinked || margin.left !== margin.top) && (
          <div className="absolute left-2.5 top-1/2 transform -translate-y-1/2">
            <DraggableNumberInput
              min={min}
              max={max}
              value={margin.left}
              onChange={(e) => {
                e.stopPropagation();
                handleMarginChange('left', e.target.value);
              }}
              onFocus={(e) => e.stopPropagation()}
              onBlur={(e) => e.stopPropagation()}
              onClick={(e) => e.stopPropagation()}
              data-no-super-forms="true"
              className="w-12 h-8 text-sm text-center border-2 border-orange-400 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all bg-white shadow-sm hover:shadow-md hover:border-orange-500"
              title="margin left"
              sensitivity={0.5}
              step={1}
            />
          </div>
        )}

        {/* Border layer - positioned inside margin layer */}
        <div 
          className="relative bg-purple-100 border-2 border-dashed border-purple-300 rounded-lg w-fit h-fit p-12 mx-5"
          {...(process.env.NODE_ENV === 'development' && {
            'data-spacing-layer': 'border',
            'data-testid': 'spacing-border-layer'
          })}
        >
          
          {/* Border label positioned to the left of the top input */}
          <span className="absolute top-2 left-1/2 transform -translate-x-1/2 -translate-x-[80px] text-sm font-medium text-purple-600">
            Border
          </span>
          
          {/* Border Top Input - centered */}
          <input
            type="number"
            min={min}
            max={max}
            value={border.top}
            onChange={(e) => {
              e.stopPropagation();
              handleBorderChange('top', e.target.value);
            }}
            onFocus={(e) => e.stopPropagation()}
            onBlur={(e) => e.stopPropagation()}
            onClick={(e) => e.stopPropagation()}
            data-no-super-forms="true"
            className="absolute top-2 left-1/2 transform -translate-x-1/2 w-12 h-8 text-sm text-center border-2 border-purple-400 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all bg-white shadow-sm hover:shadow-md hover:border-purple-500"
            title={`border ${isBorderLinked ? '(all sides)' : 'top'}`}
          />
          
          {/* Border Link button positioned to the right of the centered input */}
          <button
            type="button"
            onClick={() => setIsBorderLinked(!isBorderLinked)}
            className={clsx(
              'absolute top-2 left-1/2 transform -translate-x-1/2 translate-x-[32px] w-8 h-8 rounded transition-all shadow-sm flex items-center justify-center',
              isBorderLinked 
                ? 'bg-purple-500 text-white hover:bg-purple-600' 
                : 'bg-white text-purple-600 border border-purple-300 hover:bg-purple-50'
            )}
            title={isBorderLinked ? 'Unlink borders' : 'Link borders'}
          >
            {isBorderLinked ? <Link className="w-3 h-3" /> : <Unlink className="w-3 h-3" />}
          </button>

          {/* Border style buttons positioned to the right of the link button */}
          <div className="absolute top-2 left-1/2 transform -translate-x-1/2 translate-x-[72px] flex gap-1">
            <button
              type="button"
              onClick={() => onBorderStyleChange('solid')}
              className={clsx(
                'w-6 h-6 rounded transition-all flex items-center justify-center',
                borderStyle === 'solid' 
                  ? 'bg-purple-500 text-white' 
                  : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'
              )}
              title="Solid border"
            >
              <svg className="w-3 h-3" viewBox="0 0 16 16"><line x1="0" y1="8" x2="16" y2="8" stroke="currentColor" strokeWidth="2" /></svg>
            </button>
            <button
              type="button"
              onClick={() => onBorderStyleChange('dashed')}
              className={clsx(
                'w-6 h-6 rounded transition-all flex items-center justify-center',
                borderStyle === 'dashed' 
                  ? 'bg-purple-500 text-white' 
                  : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'
              )}
              title="Dashed border"
            >
              <svg className="w-3 h-3" viewBox="0 0 16 16"><line x1="0" y1="8" x2="16" y2="8" stroke="currentColor" strokeWidth="2" strokeDasharray="4 2" /></svg>
            </button>
            <button
              type="button"
              onClick={() => onBorderStyleChange('dotted')}
              className={clsx(
                'w-6 h-6 rounded transition-all flex items-center justify-center',
                borderStyle === 'dotted' 
                  ? 'bg-purple-500 text-white' 
                  : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'
              )}
              title="Dotted border"
            >
              <svg className="w-3 h-3" viewBox="0 0 16 16"><line x1="0" y1="8" x2="16" y2="8" stroke="currentColor" strokeWidth="2" strokeDasharray="1 2" /></svg>
            </button>
          </div>

          {/* Border Right Input */}
          {(!isBorderLinked || border.right !== border.top) && (
            <div className="absolute right-2.5 top-1/2 transform -translate-y-1/2">
              <input
                type="number"
                min={min}
                max={max}
                value={border.right}
                onChange={(e) => {
                  e.stopPropagation();
                  handleBorderChange('right', e.target.value);
                }}
                onFocus={(e) => e.stopPropagation()}
                onBlur={(e) => e.stopPropagation()}
                onClick={(e) => e.stopPropagation()}
                data-no-super-forms="true"
                className="w-12 h-8 text-sm text-center border-2 border-purple-400 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all bg-white shadow-sm hover:shadow-md hover:border-purple-500"
                title="border right"
              />
            </div>
          )}

          {/* Border Bottom Input */}
          {(!isBorderLinked || border.bottom !== border.top) && (
            <div className="absolute bottom-2 left-1/2 transform -translate-x-1/2">
              <input
                type="number"
                min={min}
                max={max}
                value={border.bottom}
                onChange={(e) => {
                  e.stopPropagation();
                  handleBorderChange('bottom', e.target.value);
                }}
                onFocus={(e) => e.stopPropagation()}
                onBlur={(e) => e.stopPropagation()}
                onClick={(e) => e.stopPropagation()}
                data-no-super-forms="true"
                className="w-12 h-8 text-sm text-center border-2 border-purple-400 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all bg-white shadow-sm hover:shadow-md hover:border-purple-500"
                title="border bottom"
              />
            </div>
          )}

          {/* Border Left Input */}
          {(!isBorderLinked || border.left !== border.top) && (
            <div className="absolute left-2.5 top-1/2 transform -translate-y-1/2">
              <input
                type="number"
                min={min}
                max={max}
                value={border.left}
                onChange={(e) => {
                  e.stopPropagation();
                  handleBorderChange('left', e.target.value);
                }}
                onFocus={(e) => e.stopPropagation()}
                onBlur={(e) => e.stopPropagation()}
                onClick={(e) => e.stopPropagation()}
                data-no-super-forms="true"
                className="w-12 h-8 text-sm text-center border-2 border-purple-400 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all bg-white shadow-sm hover:shadow-md hover:border-purple-500"
                title="border left"
              />
            </div>
          )}

          {/* Padding layer - positioned inside border layer */}
          <div 
            className="relative bg-blue-100 border-2 border-dashed border-blue-300 rounded-lg w-fit h-fit p-12 mx-5"
            {...(process.env.NODE_ENV === 'development' && {
              'data-spacing-layer': 'padding',
              'data-testid': 'spacing-padding-layer'
            })}
          >
            
            {/* Padding label positioned to the left of the top input */}
            <span className="absolute top-2 left-1/2 transform -translate-x-1/2 -translate-x-[80px] text-sm font-medium text-blue-600">
              Padding
            </span>
            
            {/* Padding Top Input - centered */}
            <input
              type="number"
              min={min}
              max={max}
              value={padding.top}
              onChange={(e) => {
                e.stopPropagation();
                handlePaddingChange('top', e.target.value);
              }}
              onFocus={(e) => e.stopPropagation()}
              onBlur={(e) => e.stopPropagation()}
              onClick={(e) => e.stopPropagation()}
              data-no-super-forms="true"
              className="absolute top-2 left-1/2 transform -translate-x-1/2 w-12 h-8 text-sm text-center border-2 border-blue-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white shadow-sm hover:shadow-md hover:border-blue-500"
              title={`padding ${isPaddingLinked ? '(all sides)' : 'top'}`}
            />
            
            {/* Padding Link button positioned to the right of the centered input */}
            <button
              type="button"
              onClick={() => setIsPaddingLinked(!isPaddingLinked)}
              className={clsx(
                'absolute top-2 left-1/2 transform -translate-x-1/2 translate-x-[32px] w-8 h-8 rounded transition-all shadow-sm flex items-center justify-center',
                isPaddingLinked 
                  ? 'bg-blue-500 text-white hover:bg-blue-600' 
                  : 'bg-white text-blue-600 border border-blue-300 hover:bg-blue-50'
              )}
              title={isPaddingLinked ? 'Unlink padding' : 'Link padding'}
            >
              {isPaddingLinked ? <Link className="w-3 h-3" /> : <Unlink className="w-3 h-3" />}
            </button>

            {/* Padding Right Input */}
            {(!isPaddingLinked || padding.right !== padding.top) && (
              <div className="absolute right-2.5 top-1/2 transform -translate-y-1/2">
                <input
                  type="number"
                  min={min}
                  max={max}
                  value={padding.right}
                  onChange={(e) => {
                    e.stopPropagation();
                    handlePaddingChange('right', e.target.value);
                  }}
                  onFocus={(e) => e.stopPropagation()}
                  onBlur={(e) => e.stopPropagation()}
                  onClick={(e) => e.stopPropagation()}
                  data-no-super-forms="true"
                  className="w-12 h-8 text-sm text-center border-2 border-blue-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white shadow-sm hover:shadow-md hover:border-blue-500"
                  title="padding right"
                />
              </div>
            )}

            {/* Padding Bottom Input */}
            {(!isPaddingLinked || padding.bottom !== padding.top) && (
              <div className="absolute bottom-2 left-1/2 transform -translate-x-1/2">
                <input
                  type="number"
                  min={min}
                  max={max}
                  value={padding.bottom}
                  onChange={(e) => {
                    e.stopPropagation();
                    handlePaddingChange('bottom', e.target.value);
                  }}
                  onFocus={(e) => e.stopPropagation()}
                  onBlur={(e) => e.stopPropagation()}
                  onClick={(e) => e.stopPropagation()}
                  data-no-super-forms="true"
                  className="w-12 h-8 text-sm text-center border-2 border-blue-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white shadow-sm hover:shadow-md hover:border-blue-500"
                  title="padding bottom"
                />
              </div>
            )}

            {/* Padding Left Input */}
            {(!isPaddingLinked || padding.left !== padding.top) && (
              <div className="absolute left-2.5 top-1/2 transform -translate-y-1/2">
                <input
                  type="number"
                  min={min}
                  max={max}
                  value={padding.left}
                  onChange={(e) => {
                    e.stopPropagation();
                    handlePaddingChange('left', e.target.value);
                  }}
                  onFocus={(e) => e.stopPropagation()}
                  onBlur={(e) => e.stopPropagation()}
                  onClick={(e) => e.stopPropagation()}
                  data-no-super-forms="true"
                  className="w-12 h-8 text-sm text-center border-2 border-blue-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white shadow-sm hover:shadow-md hover:border-blue-500"
                  title="padding left"
                />
              </div>
            )}

            {/* Inner content area - where background controls go */}
            <div 
              className="bg-white border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center w-[300px] h-[200px] mx-5"
              {...(process.env.NODE_ENV === 'development' && {
                'data-spacing-layer': 'content',
                'data-testid': 'spacing-content-area'
              })}
            >
              
              {/* Background Controls - centered in the inner area */}
              <div className="flex items-center gap-3">
                
                {/* Background Color Picker */}
                <div className="flex flex-col items-center gap-1">
                  <input
                    type="color"
                    value={localBackgroundColor}
                    onChange={(e) => {
                      e.stopPropagation();
                      debouncedBackgroundColorChange(e.target.value);
                    }}
                    onFocus={(e) => e.stopPropagation()}
                    onBlur={(e) => e.stopPropagation()}
                    onClick={(e) => e.stopPropagation()}
                    className="w-10 h-10 rounded border-2 border-gray-300 cursor-pointer hover:border-gray-400 transition-all"
                    title="Background color"
                  />
                  <span className="text-xs text-gray-600">Color</span>
                </div>

                {/* Background Image Button */}
                <div className="flex flex-col items-center gap-1">
                  <button
                    type="button"
                    onClick={(e) => {
                      e.stopPropagation();
                      setShowImageInput(!showImageInput);
                    }}
                    className={clsx(
                      'w-10 h-10 rounded border-2 transition-all flex items-center justify-center',
                      showImageInput
                        ? 'bg-green-500 text-white border-green-500'
                        : 'bg-white text-gray-600 border-gray-300 hover:border-gray-400'
                    )}
                    title="Background image"
                  >
                    <Image className="w-5 h-5" />
                  </button>
                  <span className="text-xs text-gray-600">Image</span>
                </div>

              </div>
            </div>
          </div>
        </div>

      </div>

      {/* Background image controls */}
      {showImageInput && (
        <div className="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
          <div className="space-y-3">
            <label className="text-sm font-medium text-gray-700">
              Background Image
            </label>
            
            <div className="flex items-center gap-2">
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
                className="flex-shrink-0"
              />
              <span className="text-sm text-gray-500">or</span>
            </div>
            
            <input
              type="text"
              value={backgroundImage}
              onChange={(e) => onBackgroundImageChange(e.target.value)}
              placeholder="Enter image URL directly"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm"
            />
            
            {backgroundImage && (
              <div className="grid grid-cols-3 gap-3">
                <div>
                  <label className="text-xs font-medium text-gray-600 mb-1 block">
                    Size
                  </label>
                  <select
                    value={backgroundSize}
                    onChange={(e) => onBackgroundSizeChange(e.target.value)}
                    className="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white"
                  >
                    <option value="cover">Cover</option>
                    <option value="contain">Contain</option>
                    <option value="auto">Auto</option>
                    <option value="100% 100%">Stretch</option>
                  </select>
                </div>
                
                <div>
                  <label className="text-xs font-medium text-gray-600 mb-1 block">
                    Position
                  </label>
                  <select
                    value={backgroundPosition}
                    onChange={(e) => onBackgroundPositionChange(e.target.value)}
                    className="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white"
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
                  <label className="text-xs font-medium text-gray-600 mb-1 block">
                    Repeat
                  </label>
                  <select
                    value={backgroundRepeat}
                    onChange={(e) => onBackgroundRepeatChange(e.target.value)}
                    className="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white"
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
      <div className="text-center text-xs text-gray-500">
        All spacing values in {unit}
      </div>
    </div>
  );
}

// Add display name for better React DevTools debugging
SpacingCompass.displayName = 'SpacingCompass';

export default SpacingCompass;