import React from 'react';

interface ColumnsContainerProps {
  element: {
    type: 'columns';
    id: string;
    properties?: {
      columnCount?: number;
      gap?: string;
      columnWidths?: string[];
      alignment?: string;
      width?: string | number;
      margin?: string;
      backgroundColor?: string;
      borderStyle?: string;
    };
  };
}

export const ColumnsContainer: React.FC<ColumnsContainerProps> = ({ element }) => {
  const columnCount = element.properties?.columnCount || 2;
  const gap = element.properties?.gap || '20px';

  return (
    <div 
      className="columns-container"
      style={{
        display: 'grid',
        gridTemplateColumns: `repeat(${columnCount}, 1fr)`,
        gap: gap,
        minHeight: '80px',
        border: '2px dashed #e5e7eb',
        borderRadius: '8px',
        padding: '16px'
      }}
    >
      {Array.from({ length: columnCount }).map((_, index) => (
        <div
          key={index}
          className="column-drop-zone"
          style={{
            border: '1px dashed #d1d5db',
            borderRadius: '4px',
            minHeight: '60px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            backgroundColor: '#f9fafb',
            color: '#6b7280',
            fontSize: '14px'
          }}
        >
          Column {index + 1}
        </div>
      ))}
    </div>
  );
};

export default ColumnsContainer;