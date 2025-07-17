import React from 'react';
import TextField from '../../fields/TextField';
import SelectField from '../../fields/SelectField';
import ColorPicker from '../../fields/ColorPicker';
import ToggleControl from '../../fields/ToggleControl';
import BoxControl from '../../fields/BoxControl';
import RangeControl from '../../fields/RangeControl';
import PanelBody from '../../fields/PanelBody';
import { MousePointerClick, Type, Palette, Layout, Link2 } from 'lucide-react';
import { ALIGN_OPTIONS, FONT_WEIGHT_OPTIONS } from '../../../constants/options';

function ButtonProperties({ element, onChange }) {
  const { 
    text, 
    href, 
    backgroundColor, 
    color, 
    fontSize, 
    fontWeight,
    padding, 
    borderRadius, 
    align,
    fullWidth 
  } = element.props;

  return (
    <div className="ev2-divide-y ev2-divide-gray-200">
      <PanelBody title="Content" icon={<MousePointerClick className="ev2-w-4 ev2-h-4" />} initialOpen={true}>
        <TextField
          label="Button Text"
          value={text}
          onChange={(value) => onChange({ text: value })}
          placeholder="Click Here"
        />

        <TextField
          label="Link URL"
          value={href}
          onChange={(value) => onChange({ href: value })}
          placeholder="https://example.com"
          help="Where the button should link to"
        />
      </PanelBody>

      <PanelBody title="Style" icon={<Palette className="ev2-w-4 ev2-h-4" />}>
        <ColorPicker
          label="Background Color"
          value={backgroundColor}
          onChange={(value) => onChange({ backgroundColor: value })}
        />

        <ColorPicker
          label="Text Color"
          value={color}
          onChange={(value) => onChange({ color: value })}
        />

        <RangeControl
          label="Border Radius"
          value={borderRadius}
          onChange={(value) => onChange({ borderRadius: value })}
          min={0}
          max={50}
          unit="px"
          withInputField={true}
          help="Rounded corners for the button"
        />
      </PanelBody>

      <PanelBody title="Typography" icon={<Type className="ev2-w-4 ev2-h-4" />}>
        <RangeControl
          label="Font Size"
          value={fontSize}
          onChange={(value) => onChange({ fontSize: value })}
          min={12}
          max={32}
          unit="px"
          withInputField={true}
        />

        <SelectField
          label="Font Weight"
          value={fontWeight}
          onChange={(value) => onChange({ fontWeight: value })}
          options={FONT_WEIGHT_OPTIONS}
        />
      </PanelBody>

      <PanelBody title="Layout" icon={<Layout className="ev2-w-4 ev2-h-4" />}>
        <BoxControl
          label="Padding"
          values={padding}
          onChange={(value) => onChange({ padding: value })}
          min={0}
          max={50}
          unit="px"
        />

        <SelectField
          label="Button Alignment"
          value={align}
          onChange={(value) => onChange({ align: value })}
          options={ALIGN_OPTIONS}
        />

        <ToggleControl
          label="Full Width"
          checked={fullWidth}
          onChange={(value) => onChange({ fullWidth: value })}
          help="Button will stretch to fill the container width"
        />
      </PanelBody>
    </div>
  );
}

export default ButtonProperties;