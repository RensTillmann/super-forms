
function ButtonElement({ element }) {
  const { 
    text = 'Button', 
    href = '#', 
    backgroundColor = '#007cba', 
    color = '#ffffff', 
    fontSize = 16, 
    fontWeight = 'normal',
    borderRadius = 4, 
    align = 'left',
    fullWidth = false,
    target = '_blank'
  } = element.props;

  return (
    <div 
      className="element-content"
      style={{ textAlign: align }}
    >
      <a
        href={href}
        target={target}
        rel={target === '_blank' ? 'noopener noreferrer' : undefined}
        className="inline-block no-underline transition-opacity hover:opacity-80"
        style={{
          backgroundColor,
          color,
          fontSize: `${fontSize}px`,
          fontWeight,
          borderRadius: `${borderRadius}px`,
          width: fullWidth ? '100%' : 'auto',
          textAlign: 'center',
          // Note: padding now handled by UniversalElementWrapper's SpacingLayer
          padding: '12px 24px', // Default button padding
        }}
      >
        {text}
      </a>
    </div>
  );
}

export default ButtonElement;