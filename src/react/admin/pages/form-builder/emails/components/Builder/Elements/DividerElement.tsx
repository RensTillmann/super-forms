
function DividerElement({ element }) {
  const { 
    height = 1, 
    color = '#cccccc', 
    style = 'solid',
    align = 'center',
    width = '100%'
  } = element.props;

  return (
    <div className="element-content" style={{ textAlign: align }}>
      <hr
        style={{
          height: `${height}px`,
          width: width,
          backgroundColor: style === 'solid' ? color : 'transparent',
          border: 'none',
          borderTop: style === 'dashed' ? `${height}px dashed ${color}` : 
                     style === 'dotted' ? `${height}px dotted ${color}` : undefined,
          margin: 0,
          // Email-friendly divider styles
          display: 'block',
          fontSize: '0',
          lineHeight: '0'
        }}
      />
    </div>
  );
}

export default DividerElement;