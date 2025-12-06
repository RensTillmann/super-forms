import { Link, Unlink } from 'lucide-react';
import { cn } from '../../../lib/utils';
import { Button } from '../button';

interface LinkedPropertyInputProps<T> {
  label: string;
  value: T;
  globalValue: T;
  isLinked: boolean;
  onLink: () => void;
  onUnlink: () => void;
  onChange: (value: T) => void;
  renderInput: (props: {
    value: T;
    onChange: (value: T) => void;
    disabled: boolean;
  }) => React.ReactNode;
  className?: string;
}

export function LinkedPropertyInput<T>({
  label,
  value,
  globalValue,
  isLinked,
  onLink,
  onUnlink,
  onChange,
  renderInput,
  className,
}: LinkedPropertyInputProps<T>) {
  const displayValue = isLinked ? globalValue : value;

  const handleToggleLink = () => {
    if (isLinked) {
      // Unlinking: copy current global value as the override
      onUnlink();
    } else {
      // Linking: remove override, revert to global
      onLink();
    }
  };

  return (
    <div className={cn('flex items-center gap-2', className)}>
      <span className="text-sm text-muted-foreground w-24 flex-shrink-0">
        {label}
      </span>

      <div className="flex-1">
        {renderInput({
          value: displayValue,
          onChange,
          disabled: isLinked,
        })}
      </div>

      <Button
        variant="ghost"
        size="icon"
        className={cn(
          'h-8 w-8 flex-shrink-0',
          isLinked
            ? 'text-primary bg-primary/10'
            : 'text-muted-foreground hover:text-foreground'
        )}
        onClick={handleToggleLink}
        title={isLinked ? 'Unlink from global (override)' : 'Link to global (use theme)'}
      >
        {isLinked ? (
          <Link className="h-4 w-4" />
        ) : (
          <Unlink className="h-4 w-4" />
        )}
      </Button>
    </div>
  );
}
