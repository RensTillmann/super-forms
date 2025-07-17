import React from 'react';
import TextField from '../../fields/TextField';
import ToggleControl from '../../fields/ToggleControl';
import SpacingCompass from '../../fields/SpacingCompass';
import PanelBody from '../../fields/PanelBody';
import { Layout, Expand } from 'lucide-react';

function SectionProperties({ element, onChange }) {
  const { padding, backgroundColor, backgroundImage, fullWidth } = element.props;
  
  // Initialize margin if not present
  const margin = element.props.margin || { top: 0, right: 0, bottom: 0, left: 0 };
  const border = element.props.border || { top: 0, right: 0, bottom: 0, left: 0 };
  const borderStyle = element.props.borderStyle || 'solid';
  const borderColor = element.props.borderColor || '#000000';
  const backgroundSize = element.props.backgroundSize || 'cover';
  const backgroundPosition = element.props.backgroundPosition || 'center';
  const backgroundRepeat = element.props.backgroundRepeat || 'no-repeat';
  const backgroundImageId = element.props.backgroundImageId || null;

  return (
    <div className="ev2-divide-y ev2-divide-gray-200">
      <PanelBody title="Spacing & Background" icon={<Layout className="ev2-w-4 ev2-h-4" />} initialOpen={true}>
        <SpacingCompass
          label="Section Spacing"
          margin={margin}
          border={border}
          borderStyle={borderStyle}
          borderColor={borderColor}
          padding={padding}
          backgroundColor={backgroundColor}
          backgroundImage={backgroundImage}
          backgroundImageId={backgroundImageId}
          backgroundSize={backgroundSize}
          backgroundPosition={backgroundPosition}
          backgroundRepeat={backgroundRepeat}
          onMarginChange={(value) => onChange({ margin: value })}
          onBorderChange={(value) => onChange({ border: value })}
          onBorderStyleChange={(value) => onChange({ borderStyle: value })}
          onBorderColorChange={(value) => onChange({ borderColor: value })}
          onPaddingChange={(value) => onChange({ padding: value })}
          onBackgroundColorChange={(value) => onChange({ backgroundColor: value })}
          onBackgroundImageChange={(value) => onChange({ backgroundImage: value })}
          onBackgroundImageIdChange={(value) => onChange({ backgroundImageId: value })}
          onBackgroundSizeChange={(value) => onChange({ backgroundSize: value })}
          onBackgroundPositionChange={(value) => onChange({ backgroundPosition: value })}
          onBackgroundRepeatChange={(value) => onChange({ backgroundRepeat: value })}
          unit="px"
          min={0}
          max={100}
        />
      </PanelBody>

      <PanelBody title="Layout Options" icon={<Expand className="ev2-w-4 ev2-h-4" />}>
        <ToggleControl
          label="Full Width Section"
          checked={fullWidth}
          onChange={(value) => onChange({ fullWidth: value })}
          help="Section will extend to full width of the email"
        />
      </PanelBody>
    </div>
  );
}

export default SectionProperties;