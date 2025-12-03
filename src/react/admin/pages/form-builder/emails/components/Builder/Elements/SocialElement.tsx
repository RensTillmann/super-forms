
const socialIcons = {
  facebook: { 
    icon: '📘', 
    defaultUrl: 'https://facebook.com',
    label: 'Facebook'
  },
  twitter: { 
    icon: '🐦', 
    defaultUrl: 'https://twitter.com',
    label: 'Twitter'
  },
  instagram: { 
    icon: '📷', 
    defaultUrl: 'https://instagram.com',
    label: 'Instagram'
  },
  linkedin: { 
    icon: '💼', 
    defaultUrl: 'https://linkedin.com',
    label: 'LinkedIn'
  },
  youtube: { 
    icon: '📺', 
    defaultUrl: 'https://youtube.com',
    label: 'YouTube'
  },
  pinterest: { 
    icon: '📌', 
    defaultUrl: 'https://pinterest.com',
    label: 'Pinterest'
  },
};

function SocialElement({ element }) {
  const { 
    icons = ['facebook', 'twitter', 'instagram'], 
    iconSize = 32, 
    iconColor = '#333333', 
    spacing = 8, 
    align = 'left' 
  } = element.props;

  return (
    <div className="element-content" style={{ textAlign: align }}>
      <div 
        className="inline-flex items-center"
        style={{ gap: `${spacing}px` }}
      >
        {icons.map((iconName) => {
          const social = socialIcons[iconName];
          if (!social) return null;

          return (
            <a
              key={iconName}
              href={social.defaultUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center justify-center transition-opacity hover:opacity-70"
              style={{
                width: `${iconSize}px`,
                height: `${iconSize}px`,
                fontSize: `${iconSize * 0.6}px`,
                color: iconColor,
              }}
              title={social.label}
            >
              <span>{social.icon}</span>
            </a>
          );
        })}
      </div>
    </div>
  );
}

export default SocialElement;