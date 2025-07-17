import React from 'react';
import TextField from '../../fields/TextField';
import SelectField from '../../fields/SelectField';
import ColorField from '../../fields/ColorField';
import { ALIGN_OPTIONS } from '../../../constants/options';

const iconStyleOptions = [
  { value: 'solid', label: 'Solid Circle' },
  { value: 'outline', label: 'Outline Circle' },
  { value: 'square', label: 'Square' },
  { value: 'rounded', label: 'Rounded Square' },
];

function SocialProperties({ element, onChange }) {
  const { 
    networks, 
    iconSize, 
    iconStyle, 
    iconColor, 
    spacing, 
    align 
  } = element.props;

  const updateNetwork = (network, key, value) => {
    const updatedNetworks = { ...networks };
    if (value === '' && key === 'url') {
      // Remove network if URL is empty
      delete updatedNetworks[network];
    } else {
      updatedNetworks[network] = {
        ...updatedNetworks[network],
        [key]: value,
      };
    }
    onChange({ networks: updatedNetworks });
  };

  const socialNetworks = [
    { id: 'facebook', name: 'Facebook', icon: '🔵' },
    { id: 'twitter', name: 'Twitter/X', icon: '🐦' },
    { id: 'instagram', name: 'Instagram', icon: '📷' },
    { id: 'linkedin', name: 'LinkedIn', icon: '🔗' },
    { id: 'youtube', name: 'YouTube', icon: '📺' },
    { id: 'pinterest', name: 'Pinterest', icon: '📌' },
    { id: 'tiktok', name: 'TikTok', icon: '🎵' },
  ];

  return (
    <div className="ev2-space-y-4">
      <div>
        <h4 className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-3">
          Social Networks
        </h4>
        <div className="ev2-space-y-3">
          {socialNetworks.map((network) => (
            <div key={network.id} className="ev2-border ev2-border-gray-200 ev2-rounded-lg ev2-p-3">
              <div className="ev2-flex ev2-items-center ev2-gap-2 ev2-mb-2">
                <span>{network.icon}</span>
                <span className="ev2-text-sm ev2-font-medium">{network.name}</span>
              </div>
              <TextField
                label="URL"
                value={networks[network.id]?.url || ''}
                onChange={(value) => updateNetwork(network.id, 'url', value)}
                placeholder={`https://${network.id}.com/yourpage`}
              />
            </div>
          ))}
        </div>
      </div>

      <TextField
        label="Icon Size (px)"
        type="number"
        value={iconSize}
        onChange={(value) => onChange({ iconSize: parseInt(value) || 32 })}
      />

      <SelectField
        label="Icon Style"
        value={iconStyle}
        onChange={(value) => onChange({ iconStyle: value })}
        options={iconStyleOptions}
      />

      <ColorField
        label="Icon Color"
        value={iconColor}
        onChange={(value) => onChange({ iconColor: value })}
      />

      <TextField
        label="Spacing Between Icons (px)"
        type="number"
        value={spacing}
        onChange={(value) => onChange({ spacing: parseInt(value) || 10 })}
      />

      <SelectField
        label="Alignment"
        value={align}
        onChange={(value) => onChange({ align: value })}
        options={ALIGN_OPTIONS}
      />
    </div>
  );
}

export default SocialProperties;