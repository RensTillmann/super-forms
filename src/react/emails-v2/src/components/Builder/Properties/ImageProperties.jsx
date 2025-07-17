import React from 'react';
import TextField from '../../fields/TextField';
import SelectField from '../../fields/SelectField';
import { ALIGN_OPTIONS } from '../../../constants/options';

function ImageProperties({ element, onChange }) {
  const { src, alt, width, height, align, link } = element.props;

  return (
    <div className="ev2-space-y-4">
      <TextField
        label="Image URL"
        value={src}
        onChange={(value) => onChange({ src: value })}
        placeholder="https://example.com/image.jpg"
      />

      <TextField
        label="Alt Text"
        value={alt}
        onChange={(value) => onChange({ alt: value })}
        placeholder="Describe the image"
      />

      <TextField
        label="Width"
        value={width}
        onChange={(value) => onChange({ width: value })}
        placeholder="100% or 600px"
      />

      <TextField
        label="Height"
        value={height}
        onChange={(value) => onChange({ height: value })}
        placeholder="auto or 300px"
      />

      <SelectField
        label="Alignment"
        value={align}
        onChange={(value) => onChange({ align: value })}
        options={ALIGN_OPTIONS}
      />

      <TextField
        label="Link URL"
        value={link}
        onChange={(value) => onChange({ link: value })}
        placeholder="https://example.com"
      />
    </div>
  );
}

export default ImageProperties;