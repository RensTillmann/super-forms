import React from 'react';
import { ZoomIn, ZoomOut, Maximize } from 'lucide-react';
import { ZoomControlsProps } from '../types/control.types';

const defaultZoomLevels = [0.5, 0.75, 1, 1.25, 1.5, 2];

export const ZoomControls: React.FC<ZoomControlsProps> = ({
  currentZoom,
  zoomLevels = defaultZoomLevels,
  onZoomChange,
  onFitToScreen
}) => {
  const handleZoomIn = () => {
    const currentIndex = zoomLevels.indexOf(currentZoom);
    if (currentIndex < zoomLevels.length - 1) {
      onZoomChange(zoomLevels[currentIndex + 1]);
    }
  };

  const handleZoomOut = () => {
    const currentIndex = zoomLevels.indexOf(currentZoom);
    if (currentIndex > 0) {
      onZoomChange(zoomLevels[currentIndex - 1]);
    }
  };

  const handleZoomReset = () => {
    onZoomChange(1);
  };

  const zoomPercentage = Math.round((currentZoom || 1) * 100);
  const canZoomIn = currentZoom < zoomLevels[zoomLevels.length - 1];
  const canZoomOut = currentZoom > zoomLevels[0];

  return (
    <div className="zoom-controls" role="group" aria-label="Zoom controls">
      <button
        onClick={handleZoomOut}
        disabled={!canZoomOut}
        className="zoom-btn"
        aria-label="Zoom out"
        title={`Zoom out (${zoomLevels[Math.max(0, zoomLevels.indexOf(currentZoom) - 1)] * 100}%)`}
      >
        <ZoomOut size={16} />
      </button>
      
      <button
        onClick={handleZoomReset}
        className="zoom-value"
        aria-label={`Current zoom ${zoomPercentage}%, click to reset to 100%`}
        title="Reset zoom to 100%"
      >
        {zoomPercentage}%
      </button>
      
      <button
        onClick={handleZoomIn}
        disabled={!canZoomIn}
        className="zoom-btn"
        aria-label="Zoom in"
        title={`Zoom in (${zoomLevels[Math.min(zoomLevels.length - 1, zoomLevels.indexOf(currentZoom) + 1)] * 100}%)`}
      >
        <ZoomIn size={16} />
      </button>
      
      {onFitToScreen && (
        <>
          <div className="zoom-divider" />
          <button
            onClick={onFitToScreen}
            className="zoom-btn"
            aria-label="Fit to screen"
            title="Fit form to screen"
          >
            <Maximize size={16} />
          </button>
        </>
      )}
    </div>
  );
};