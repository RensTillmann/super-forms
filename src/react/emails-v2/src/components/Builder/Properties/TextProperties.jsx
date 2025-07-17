import React from 'react';
import TextField from '../../fields/TextField';
import SelectField from '../../fields/SelectField';
import ColorPicker from '../../fields/ColorPicker';
import RichTextEditor from '../../fields/RichTextEditor';
import RangeControl from '../../fields/RangeControl';
import PanelBody from '../../fields/PanelBody';
import { Type, Palette, AlignLeft } from 'lucide-react';
import { FONT_OPTIONS, TEXT_ALIGN_OPTIONS } from '../../../constants/options';

function TextProperties({ element, onChange }) {
  const { content, fontSize, fontFamily, color, lineHeight, align } = element.props;

  return (
    <div className="ev2-divide-y ev2-divide-gray-200">
      <PanelBody title="Content" initialOpen={true}>
        <RichTextEditor
          label="Text Content"
          value={content}
          onChange={(value) => onChange({ content: value })}
          height={200}
        />
      </PanelBody>

      <PanelBody title="Typography" icon={<Type className="ev2-w-4 ev2-h-4" />}>
        <RangeControl
          label="Font Size"
          value={fontSize}
          onChange={(value) => onChange({ fontSize: value })}
          min={10}
          max={48}
          unit="px"
          withInputField={true}
        />

        <SelectField
          label="Font Family"
          value={fontFamily}
          onChange={(value) => onChange({ fontFamily: value })}
          options={FONT_OPTIONS}
        />

        <RangeControl
          label="Line Height"
          value={lineHeight}
          onChange={(value) => onChange({ lineHeight: value })}
          min={1}
          max={3}
          step={0.1}
          withInputField={true}
        />
      </PanelBody>

      <PanelBody title="Color & Style" icon={<Palette className="ev2-w-4 ev2-h-4" />}>
        <ColorPicker
          label="Text Color"
          value={color}
          onChange={(value) => onChange({ color: value })}
        />
      </PanelBody>

      <PanelBody title="Layout" icon={<AlignLeft className="ev2-w-4 ev2-h-4" />}>
        <SelectField
          label="Text Alignment"
          value={align}
          onChange={(value) => onChange({ align: value })}
          options={TEXT_ALIGN_OPTIONS}
        />
      </PanelBody>
    </div>
  );
}

export default TextProperties;