import React from 'react';
import { GridOverlayProps } from '../types/overlay.types';

export const GridOverlay: React.FC<GridOverlayProps> = ({ 
  isVisible, 
  gridSize = 20,
  color = '#E5E7EB',
  opacity = 0.5
}) => {
  if (!isVisible) return null;

  return (
    <div className="grid-overlay" style={{ opacity }}>
      <svg 
        className="grid-svg" 
        xmlns="http://www.w3.org/2000/svg"
        width="100%"
        height="100%"
        aria-hidden="true"
      >
        <defs>
          <pattern
            id="grid"
            width={gridSize}
            height={gridSize}
            patternUnits="userSpaceOnUse"
          >
            <path
              d={`M ${gridSize} 0 L 0 0 0 ${gridSize}`}
              fill="none"
              stroke={color}
              strokeWidth="1"
            />
          </pattern>
          <pattern
            id="grid-large"
            width={gridSize * 5}
            height={gridSize * 5}
            patternUnits="userSpaceOnUse"
          >
            <rect
              width={gridSize * 5}
              height={gridSize * 5}
              fill="url(#grid)"
            />
            <path
              d={`M ${gridSize * 5} 0 L 0 0 0 ${gridSize * 5}`}
              fill="none"
              stroke={color}
              strokeWidth="2"
              opacity="0.5"
            />
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#grid-large)" />
      </svg>
    </div>
  );
};