import React from 'react';
import { X, Plus } from 'lucide-react';

interface OptionsEditorProps {
  options: string[];
  onUpdate: (options: string[]) => void;
  className?: string;
}

export const OptionsEditor: React.FC<OptionsEditorProps> = ({ 
  options = ['Option 1', 'Option 2'], 
  onUpdate,
  className = ''
}) => {
  const handleOptionChange = (index: number, value: string) => {
    const newOptions = [...options];
    newOptions[index] = value;
    onUpdate(newOptions);
  };

  const handleRemoveOption = (index: number) => {
    const newOptions = options.filter((_, i) => i !== index);
    onUpdate(newOptions);
  };

  const handleAddOption = () => {
    const newOptions = [...options, `Option ${options.length + 1}`];
    onUpdate(newOptions);
  };

  return (
    <div className={`options-editor ${className}`}>
      {options.map((option, index) => (
        <div key={index} className="option-item">
          <input
            type="text"
            value={option}
            onChange={(e) => handleOptionChange(index, e.target.value)}
            className="form-input"
          />
          <button
            onClick={() => handleRemoveOption(index)}
            className="option-delete-btn"
            disabled={options.length <= 1}
          >
            <X size={14} />
          </button>
        </div>
      ))}
      <button
        onClick={handleAddOption}
        className="add-option-btn"
      >
        <Plus size={14} />
        Add Option
      </button>
    </div>
  );
};