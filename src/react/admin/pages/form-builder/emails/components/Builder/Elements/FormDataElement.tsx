
function FormDataElement({ element }) {
  const { 
    field = 'email', 
    fallback = '[Form Field]', 
    format = 'text' 
  } = element.props;

  // In the actual implementation, this would pull data from the form
  // For now, we'll just show the field placeholder
  const displayValue = field ? `{${field}}` : fallback;

  return (
    <div className="element-content inline-block px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-sm">
      {displayValue}
    </div>
  );
}

export default FormDataElement;