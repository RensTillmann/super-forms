import React from 'react';
import { X, Plus } from 'lucide-react';
import { Input } from '../../../../../components/ui/input';
import { Button } from '../../../../../components/ui/button';

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
    <div className={`space-y-2 ${className}`}>
      {options.map((option, index) => (
        <div key={index} className="flex items-center gap-2">
          <Input
            type="text"
            value={option}
            onChange={(e) => handleOptionChange(index, e.target.value)}
            className="flex-1"
          />
          <Button
            type="button"
            variant="ghost"
            size="icon"
            onClick={() => handleRemoveOption(index)}
            disabled={options.length <= 1}
            className="h-10 w-10 shrink-0"
          >
            <X className="h-4 w-4" />
          </Button>
        </div>
      ))}
      <Button
        type="button"
        variant="outline"
        size="sm"
        onClick={handleAddOption}
        className="w-full"
      >
        <Plus className="h-4 w-4 mr-2" />
        Add Option
      </Button>
    </div>
  );
};
