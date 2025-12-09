import React from 'react';
import { ZoomIn, ZoomOut, Maximize } from 'lucide-react';
import { ZoomControlsProps } from '../types/control.types';
import { Button } from '../../../../../components/ui/button';

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
    <div className="flex items-center gap-1" role="group" aria-label="Zoom controls">
      <Button
        variant="ghost"
        size="icon"
        onClick={handleZoomOut}
        disabled={!canZoomOut}
        className="h-8 w-8"
        aria-label="Zoom out"
        title={`Zoom out (${zoomLevels[Math.max(0, zoomLevels.indexOf(currentZoom) - 1)] * 100}%)`}
        data-testid="zoom-out"
      >
        <ZoomOut size={16} />
      </Button>

      <Button
        variant="ghost"
        size="sm"
        onClick={handleZoomReset}
        className="min-w-[50px] text-xs font-medium"
        aria-label={`Current zoom ${zoomPercentage}%, click to reset to 100%`}
        title="Reset zoom to 100%"
        data-testid="zoom-reset"
      >
        {zoomPercentage}%
      </Button>

      <Button
        variant="ghost"
        size="icon"
        onClick={handleZoomIn}
        disabled={!canZoomIn}
        className="h-8 w-8"
        aria-label="Zoom in"
        title={`Zoom in (${zoomLevels[Math.min(zoomLevels.length - 1, zoomLevels.indexOf(currentZoom) + 1)] * 100}%)`}
        data-testid="zoom-in"
      >
        <ZoomIn size={16} />
      </Button>

      {onFitToScreen && (
        <>
          <div className="w-px h-4 bg-border mx-1" />
          <Button
            variant="ghost"
            size="icon"
            onClick={onFitToScreen}
            className="h-8 w-8"
            aria-label="Fit to screen"
            title="Fit form to screen"
            data-testid="zoom-fit"
          >
            <Maximize size={16} />
          </Button>
        </>
      )}
    </div>
  );
};