import React, { useState } from 'react';
import clsx from 'clsx';
import { Link, Unlink, Image, Palette } from 'lucide-react';
import ColorPicker from './ColorPicker';
import MediaLibraryButton from './MediaLibraryButton';

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
      
      {/* Simple margin layer - starting from scratch */}
      <div className="ev2-relative ev2-mx-auto ev2-bg-orange-100 ev2-border-2 ev2-border-dashed ev2-border-orange-300 ev2-rounded-lg ev2-w-fit ev2-h-fit ev2-p-12">
        
        {/* Margin label positioned to the left of the top input */}
        <span className="ev2-absolute ev2-top-2 ev2-left-1/2 ev2-transform -ev2-translate-x-1/2 -ev2-translate-x-[80px] ev2-text-sm ev2-font-medium ev2-text-orange-600">
          Margin
        </span>
        
        {/* Margin Top Input - centered */}
        <input
          type="number"
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
          className="ev2-absolute ev2-top-2 ev2-left-1/2 ev2-transform -ev2-translate-x-1/2 ev2-w-12 ev2-h-8 ev2-text-sm ev2-text-center ev2-border-2 ev2-border-orange-400 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-orange-500 focus:ev2-border-transparent ev2-transition-all ev2-bg-white ev2-shadow-sm hover:ev2-shadow-md hover:ev2-border-orange-500"
          title={`margin ${isMarginLinked ? '(all sides)' : 'top'}`}
        />
        
        {/* Link button positioned to the right of the centered input */}
        <button
          type="button"
          onClick={() => setIsMarginLinked(!isMarginLinked)}
          className={clsx(
            'ev2-absolute ev2-top-2 ev2-left-1/2 ev2-transform -ev2-translate-x-1/2 ev2-translate-x-[32px] ev2-w-8 ev2-h-8 ev2-rounded ev2-transition-all ev2-shadow-sm ev2-flex ev2-items-center ev2-justify-center',
            isMarginLinked 
              ? 'ev2-bg-orange-500 ev2-text-white hover:ev2-bg-orange-600' 
              : 'ev2-bg-white ev2-text-orange-600 ev2-border ev2-border-orange-300 hover:ev2-bg-orange-50'
          )}
          title={isMarginLinked ? 'Unlink margins' : 'Link margins'}
        >
          {isMarginLinked ? <Link className="ev2-w-3 ev2-h-3" /> : <Unlink className="ev2-w-3 ev2-h-3" />}
        </button>

        {/* Margin Right Input */}
        {(!isMarginLinked || margin.right !== margin.top) && (
          <div className="ev2-absolute ev2-right-2.5 ev2-top-1/2 ev2-transform -ev2-translate-y-1/2">
            <input
              type="number"
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
              className="ev2-w-12 ev2-h-8 ev2-text-sm ev2-text-center ev2-border-2 ev2-border-orange-400 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-orange-500 focus:ev2-border-transparent ev2-transition-all ev2-bg-white ev2-shadow-sm hover:ev2-shadow-md hover:ev2-border-orange-500"
              title="margin right"
            />
          </div>
        )}

        {/* Margin Bottom Input */}
        {(!isMarginLinked || margin.bottom !== margin.top) && (
          <div className="ev2-absolute ev2-bottom-2 ev2-left-1/2 ev2-transform -ev2-translate-x-1/2">
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
              className="ev2-w-12 ev2-h-8 ev2-text-sm ev2-text-center ev2-border-2 ev2-border-orange-400 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-orange-500 focus:ev2-border-transparent ev2-transition-all ev2-bg-white ev2-shadow-sm hover:ev2-shadow-md hover:ev2-border-orange-500"
              title="margin bottom"
            />
          </div>
        )}

        {/* Margin Left Input */}
        {(!isMarginLinked || margin.left !== margin.top) && (
          <div className="ev2-absolute ev2-left-2.5 ev2-top-1/2 ev2-transform -ev2-translate-y-1/2">
            <input
              type="number"
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
              className="ev2-w-12 ev2-h-8 ev2-text-sm ev2-text-center ev2-border-2 ev2-border-orange-400 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-orange-500 focus:ev2-border-transparent ev2-transition-all ev2-bg-white ev2-shadow-sm hover:ev2-shadow-md hover:ev2-border-orange-500"
              title="margin left"
            />
          </div>
        )}

        {/* Border layer - positioned inside margin layer */}
        <div className="ev2-relative ev2-bg-purple-100 ev2-border-2 ev2-border-dashed ev2-border-purple-300 ev2-rounded-lg ev2-w-fit ev2-h-fit ev2-p-12 ev2-mx-5">
          
          {/* Border label positioned to the left of the top input */}
          <span className="ev2-absolute ev2-top-2 ev2-left-1/2 ev2-transform -ev2-translate-x-1/2 -ev2-translate-x-[80px] ev2-text-sm ev2-font-medium ev2-text-purple-600">
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
            className="ev2-absolute ev2-top-2 ev2-left-1/2 ev2-transform -ev2-translate-x-1/2 ev2-w-12 ev2-h-8 ev2-text-sm ev2-text-center ev2-border-2 ev2-border-purple-400 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-purple-500 focus:ev2-border-transparent ev2-transition-all ev2-bg-white ev2-shadow-sm hover:ev2-shadow-md hover:ev2-border-purple-500"
            title={`border ${isBorderLinked ? '(all sides)' : 'top'}`}
          />
          
          {/* Border Link button positioned to the right of the centered input */}
          <button
            type="button"
            onClick={() => setIsBorderLinked(!isBorderLinked)}
            className={clsx(
              'ev2-absolute ev2-top-2 ev2-left-1/2 ev2-transform -ev2-translate-x-1/2 ev2-translate-x-[32px] ev2-w-8 ev2-h-8 ev2-rounded ev2-transition-all ev2-shadow-sm ev2-flex ev2-items-center ev2-justify-center',
              isBorderLinked 
                ? 'ev2-bg-purple-500 ev2-text-white hover:ev2-bg-purple-600' 
                : 'ev2-bg-white ev2-text-purple-600 ev2-border ev2-border-purple-300 hover:ev2-bg-purple-50'
            )}
            title={isBorderLinked ? 'Unlink borders' : 'Link borders'}
          >
            {isBorderLinked ? <Link className="ev2-w-3 ev2-h-3" /> : <Unlink className="ev2-w-3 ev2-h-3" />}
          </button>

          {/* Border style buttons positioned to the right of the link button */}
          <div className="ev2-absolute ev2-top-2 ev2-left-1/2 ev2-transform -ev2-translate-x-1/2 ev2-translate-x-[72px] ev2-flex ev2-gap-1">
            <button
              type="button"
              onClick={() => onBorderStyleChange('solid')}
              className={clsx(
                'ev2-w-6 ev2-h-6 ev2-rounded ev2-transition-all ev2-flex ev2-items-center ev2-justify-center',
                borderStyle === 'solid' 
                  ? 'ev2-bg-purple-500 ev2-text-white' 
                  : 'ev2-bg-white ev2-text-gray-600 ev2-border ev2-border-gray-300 hover:ev2-bg-gray-50'
              )}
              title="Solid border"
            >
              <svg className="ev2-w-3 ev2-h-3" viewBox="0 0 16 16"><line x1="0" y1="8" x2="16" y2="8" stroke="currentColor" strokeWidth="2" /></svg>
            </button>
            <button
              type="button"
              onClick={() => onBorderStyleChange('dashed')}
              className={clsx(
                'ev2-w-6 ev2-h-6 ev2-rounded ev2-transition-all ev2-flex ev2-items-center ev2-justify-center',
                borderStyle === 'dashed' 
                  ? 'ev2-bg-purple-500 ev2-text-white' 
                  : 'ev2-bg-white ev2-text-gray-600 ev2-border ev2-border-gray-300 hover:ev2-bg-gray-50'
              )}
              title="Dashed border"
            >
              <svg className="ev2-w-3 ev2-h-3" viewBox="0 0 16 16"><line x1="0" y1="8" x2="16" y2="8" stroke="currentColor" strokeWidth="2" strokeDasharray="4 2" /></svg>
            </button>
            <button
              type="button"
              onClick={() => onBorderStyleChange('dotted')}
              className={clsx(
                'ev2-w-6 ev2-h-6 ev2-rounded ev2-transition-all ev2-flex ev2-items-center ev2-justify-center',
                borderStyle === 'dotted' 
                  ? 'ev2-bg-purple-500 ev2-text-white' 
                  : 'ev2-bg-white ev2-text-gray-600 ev2-border ev2-border-gray-300 hover:ev2-bg-gray-50'
              )}
              title="Dotted border"
            >
              <svg className="ev2-w-3 ev2-h-3" viewBox="0 0 16 16"><line x1="0" y1="8" x2="16" y2="8" stroke="currentColor" strokeWidth="2" strokeDasharray="1 2" /></svg>
            </button>
          </div>

          {/* Border Right Input */}
          {(!isBorderLinked || border.right !== border.top) && (
            <div className="ev2-absolute ev2-right-2.5 ev2-top-1/2 ev2-transform -ev2-translate-y-1/2">
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
                className="ev2-w-12 ev2-h-8 ev2-text-sm ev2-text-center ev2-border-2 ev2-border-purple-400 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-purple-500 focus:ev2-border-transparent ev2-transition-all ev2-bg-white ev2-shadow-sm hover:ev2-shadow-md hover:ev2-border-purple-500"
                title="border right"
              />
            </div>
          )}

          {/* Border Bottom Input */}
          {(!isBorderLinked || border.bottom !== border.top) && (
            <div className="ev2-absolute ev2-bottom-2 ev2-left-1/2 ev2-transform -ev2-translate-x-1/2">
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
                className="ev2-w-12 ev2-h-8 ev2-text-sm ev2-text-center ev2-border-2 ev2-border-purple-400 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-purple-500 focus:ev2-border-transparent ev2-transition-all ev2-bg-white ev2-shadow-sm hover:ev2-shadow-md hover:ev2-border-purple-500"
                title="border bottom"
              />
            </div>
          )}

          {/* Border Left Input */}
          {(!isBorderLinked || border.left !== border.top) && (
            <div className="ev2-absolute ev2-left-2.5 ev2-top-1/2 ev2-transform -ev2-translate-y-1/2">
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
                className="ev2-w-12 ev2-h-8 ev2-text-sm ev2-text-center ev2-border-2 ev2-border-purple-400 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-purple-500 focus:ev2-border-transparent ev2-transition-all ev2-bg-white ev2-shadow-sm hover:ev2-shadow-md hover:ev2-border-purple-500"
                title="border left"
              />
            </div>
          )}

          {/* Padding layer - positioned inside border layer */}
          <div className="ev2-relative ev2-bg-blue-100 ev2-border-2 ev2-border-dashed ev2-border-blue-300 ev2-rounded-lg ev2-w-fit ev2-h-fit ev2-p-12 ev2-mx-5">
            
            {/* Padding label positioned to the left of the top input */}
            <span className="ev2-absolute ev2-top-2 ev2-left-1/2 ev2-transform -ev2-translate-x-1/2 -ev2-translate-x-[80px] ev2-text-sm ev2-font-medium ev2-text-blue-600">
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
              className="ev2-absolute ev2-top-2 ev2-left-1/2 ev2-transform -ev2-translate-x-1/2 ev2-w-12 ev2-h-8 ev2-text-sm ev2-text-center ev2-border-2 ev2-border-blue-400 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-blue-500 focus:ev2-border-transparent ev2-transition-all ev2-bg-white ev2-shadow-sm hover:ev2-shadow-md hover:ev2-border-blue-500"
              title={`padding ${isPaddingLinked ? '(all sides)' : 'top'}`}
            />
            
            {/* Padding Link button positioned to the right of the centered input */}
            <button
              type="button"
              onClick={() => setIsPaddingLinked(!isPaddingLinked)}
              className={clsx(
                'ev2-absolute ev2-top-2 ev2-left-1/2 ev2-transform -ev2-translate-x-1/2 ev2-translate-x-[32px] ev2-w-8 ev2-h-8 ev2-rounded ev2-transition-all ev2-shadow-sm ev2-flex ev2-items-center ev2-justify-center',
                isPaddingLinked 
                  ? 'ev2-bg-blue-500 ev2-text-white hover:ev2-bg-blue-600' 
                  : 'ev2-bg-white ev2-text-blue-600 ev2-border ev2-border-blue-300 hover:ev2-bg-blue-50'
              )}
              title={isPaddingLinked ? 'Unlink padding' : 'Link padding'}
            >
              {isPaddingLinked ? <Link className="ev2-w-3 ev2-h-3" /> : <Unlink className="ev2-w-3 ev2-h-3" />}
            </button>

            {/* Padding Right Input */}
            {(!isPaddingLinked || padding.right !== padding.top) && (
              <div className="ev2-absolute ev2-right-2.5 ev2-top-1/2 ev2-transform -ev2-translate-y-1/2">
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
                  className="ev2-w-12 ev2-h-8 ev2-text-sm ev2-text-center ev2-border-2 ev2-border-blue-400 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-blue-500 focus:ev2-border-transparent ev2-transition-all ev2-bg-white ev2-shadow-sm hover:ev2-shadow-md hover:ev2-border-blue-500"
                  title="padding right"
                />
              </div>
            )}

            {/* Padding Bottom Input */}
            {(!isPaddingLinked || padding.bottom !== padding.top) && (
              <div className="ev2-absolute ev2-bottom-2 ev2-left-1/2 ev2-transform -ev2-translate-x-1/2">
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
                  className="ev2-w-12 ev2-h-8 ev2-text-sm ev2-text-center ev2-border-2 ev2-border-blue-400 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-blue-500 focus:ev2-border-transparent ev2-transition-all ev2-bg-white ev2-shadow-sm hover:ev2-shadow-md hover:ev2-border-blue-500"
                  title="padding bottom"
                />
              </div>
            )}

            {/* Padding Left Input */}
            {(!isPaddingLinked || padding.left !== padding.top) && (
              <div className="ev2-absolute ev2-left-2.5 ev2-top-1/2 ev2-transform -ev2-translate-y-1/2">
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
                  className="ev2-w-12 ev2-h-8 ev2-text-sm ev2-text-center ev2-border-2 ev2-border-blue-400 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-blue-500 focus:ev2-border-transparent ev2-transition-all ev2-bg-white ev2-shadow-sm hover:ev2-shadow-md hover:ev2-border-blue-500"
                  title="padding left"
                />
              </div>
            )}

            {/* Inner content area - where background controls go */}
            <div className="ev2-bg-white ev2-border-2 ev2-border-dashed ev2-border-gray-300 ev2-rounded-lg ev2-flex ev2-items-center ev2-justify-center ev2-w-[300px] ev2-h-[200px] ev2-mx-5">
              
              {/* Background Controls - centered in the inner area */}
              <div className="ev2-flex ev2-items-center ev2-gap-3">
                
                {/* Background Color Picker */}
                <div className="ev2-flex ev2-flex-col ev2-items-center ev2-gap-1">
                  <input
                    type="color"
                    value={backgroundColor}
                    onChange={(e) => {
                      e.stopPropagation();
                      onBackgroundColorChange(e.target.value);
                    }}
                    onFocus={(e) => e.stopPropagation()}
                    onBlur={(e) => e.stopPropagation()}
                    onClick={(e) => e.stopPropagation()}
                    className="ev2-w-10 ev2-h-10 ev2-rounded ev2-border-2 ev2-border-gray-300 ev2-cursor-pointer hover:ev2-border-gray-400 ev2-transition-all"
                    title="Background color"
                  />
                  <span className="ev2-text-xs ev2-text-gray-600">Color</span>
                </div>

                {/* Background Image Button */}
                <div className="ev2-flex ev2-flex-col ev2-items-center ev2-gap-1">
                  <button
                    type="button"
                    onClick={(e) => {
                      e.stopPropagation();
                      setShowImageInput(!showImageInput);
                    }}
                    className={clsx(
                      'ev2-w-10 ev2-h-10 ev2-rounded ev2-border-2 ev2-transition-all ev2-flex ev2-items-center ev2-justify-center',
                      showImageInput
                        ? 'ev2-bg-green-500 ev2-text-white ev2-border-green-500'
                        : 'ev2-bg-white ev2-text-gray-600 ev2-border-gray-300 hover:ev2-border-gray-400'
                    )}
                    title="Background image"
                  >
                    <Image className="ev2-w-5 ev2-h-5" />
                  </button>
                  <span className="ev2-text-xs ev2-text-gray-600">Image</span>
                </div>

              </div>
            </div>
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

export default SpacingCompass;