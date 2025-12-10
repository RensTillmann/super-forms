import React, { useState, useCallback, useRef, useEffect } from 'react';
import { Sparkles, Send, Loader2, User, Bot, Info, ChevronRight } from 'lucide-react';
import { Button } from '../../../../../components/ui/button';
import { cn } from '../../../../../lib/utils';
import { useElementsStore } from '../../../store/useElementsStore';
import { getElementSchema, isElementRegistered } from '../../../../../schemas/core/registry';

interface AITabProps {
  element: {
    id: string;
    type: string;
    properties?: Record<string, unknown>;
    children?: string[];
  };
  onPropertyChange: (propertyName: string, value: unknown) => void;
}

interface ChatMessage {
  id: string;
  role: 'user' | 'assistant' | 'system';
  content: string;
  timestamp: Date;
}

/**
 * AI Tab - Element-focused chat interface
 *
 * Provides a chat interface that understands the current element context,
 * including its properties, schema, and children (for container elements).
 */
export const AITab: React.FC<AITabProps> = ({
  element,
  onPropertyChange,
}) => {
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [input, setInput] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLTextAreaElement>(null);

  // Get child elements for container elements
  const childElements = useElementsStore((s) =>
    element.children?.map((childId) => s.items[childId]).filter(Boolean) || []
  );

  // Get element schema
  const hasSchema = isElementRegistered(element.type);
  const schema = hasSchema ? getElementSchema(element.type) : null;

  // Auto-scroll to bottom on new messages
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  // Build element context for AI
  const buildContext = useCallback(() => {
    const context: Record<string, unknown> = {
      elementId: element.id,
      elementType: element.type,
      properties: element.properties || {},
      isContainer: Boolean(element.children?.length),
      childCount: element.children?.length || 0,
    };

    if (schema) {
      context.schemaName = schema.name;
      context.availableProperties = Object.keys(schema.properties || {}).reduce(
        (acc, category) => {
          const categoryProps = schema.properties[category as keyof typeof schema.properties];
          if (categoryProps && typeof categoryProps === 'object') {
            acc[category] = Object.keys(categoryProps);
          }
          return acc;
        },
        {} as Record<string, string[]>
      );
    }

    if (childElements.length > 0) {
      context.children = childElements.map((child) => ({
        id: child.id,
        type: child.type,
        label: child.properties?.label || child.type,
      }));
    }

    return context;
  }, [element, schema, childElements]);

  // Handle send message
  const handleSend = useCallback(async () => {
    if (!input.trim() || isLoading) return;

    const userMessage: ChatMessage = {
      id: `user-${Date.now()}`,
      role: 'user',
      content: input.trim(),
      timestamp: new Date(),
    };

    setMessages((prev) => [...prev, userMessage]);
    setInput('');
    setIsLoading(true);

    // Build context for the AI
    const context = buildContext();

    // For now, show a placeholder response
    // In a full implementation, this would call an AI endpoint
    setTimeout(() => {
      const assistantMessage: ChatMessage = {
        id: `assistant-${Date.now()}`,
        role: 'assistant',
        content: generatePlaceholderResponse(userMessage.content, context),
        timestamp: new Date(),
      };
      setMessages((prev) => [...prev, assistantMessage]);
      setIsLoading(false);
    }, 1000);
  }, [input, isLoading, buildContext]);

  // Handle keyboard submit
  const handleKeyDown = useCallback(
    (e: React.KeyboardEvent) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSend();
      }
    },
    [handleSend]
  );

  // Quick action suggestions
  const quickActions = [
    { label: 'Make required', prompt: 'Make this field required' },
    { label: 'Add validation', prompt: 'Add email validation to this field' },
    { label: 'Style label', prompt: 'Make the label bold and blue' },
  ];

  // Container-specific quick actions
  const containerActions = element.children?.length
    ? [
        { label: 'Make all required', prompt: 'Make all child fields required' },
        { label: 'Align labels', prompt: 'Align all labels to the left' },
      ]
    : [];

  const allQuickActions = [...quickActions, ...containerActions];

  return (
    <div className="flex flex-col h-[400px]" data-testid="ai-tab">
      {/* Context indicator */}
      <div className="flex items-center gap-2 p-3 bg-gradient-to-r from-purple-50 to-blue-50 border-b border-gray-100" data-testid="ai-context-indicator">
        <Sparkles className="h-4 w-4 text-purple-500" />
        <div className="flex-1 min-w-0">
          <span className="text-xs font-medium text-gray-700">
            AI Assistant
          </span>
          <span className="text-xs text-gray-500 ml-2">
            Focused on: {schema?.name || element.type}
            {element.children?.length ? ` (${element.children.length} children)` : ''}
          </span>
        </div>
      </div>

      {/* Messages area */}
      <div className="flex-1 overflow-y-auto p-3 space-y-3" data-testid="ai-messages">
        {messages.length === 0 ? (
          <div className="text-center py-8">
            <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-purple-100 mb-3">
              <Bot className="h-6 w-6 text-purple-500" />
            </div>
            <h4 className="text-sm font-medium text-gray-700 mb-1">AI Element Assistant</h4>
            <p className="text-xs text-gray-500 max-w-[220px] mx-auto mb-4">
              Ask me to modify this element's properties, apply styles, or help with configuration.
            </p>

            {/* Quick actions */}
            <div className="space-y-1.5" data-testid="ai-quick-actions">
              <p className="text-[10px] uppercase tracking-wide text-gray-400 mb-2">
                Try asking:
              </p>
              {allQuickActions.map((action, index) => (
                <button
                  key={index}
                  type="button"
                  onClick={() => setInput(action.prompt)}
                  className={cn(
                    "flex items-center gap-2 w-full px-3 py-2 text-left",
                    "text-xs text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-md",
                    "transition-colors"
                  )}
                  data-testid={`ai-quick-action-${index}`}
                >
                  <ChevronRight className="h-3 w-3 text-gray-400" />
                  {action.label}
                </button>
              ))}
            </div>
          </div>
        ) : (
          <>
            {messages.map((message) => (
              <div
                key={message.id}
                className={cn(
                  "flex gap-2",
                  message.role === 'user' ? 'justify-end' : 'justify-start'
                )}
                data-testid={`ai-message-${message.role}`}
              >
                {message.role === 'assistant' && (
                  <div className="w-6 h-6 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                    <Bot className="h-3.5 w-3.5 text-purple-500" />
                  </div>
                )}
                <div
                  className={cn(
                    "max-w-[85%] px-3 py-2 rounded-lg text-sm",
                    message.role === 'user'
                      ? "bg-primary text-primary-foreground"
                      : "bg-gray-100 text-gray-700"
                  )}
                >
                  {message.content}
                </div>
                {message.role === 'user' && (
                  <div className="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                    <User className="h-3.5 w-3.5 text-gray-500" />
                  </div>
                )}
              </div>
            ))}
            {isLoading && (
              <div className="flex gap-2 justify-start" data-testid="ai-loading">
                <div className="w-6 h-6 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                  <Bot className="h-3.5 w-3.5 text-purple-500" />
                </div>
                <div className="px-3 py-2 rounded-lg bg-gray-100">
                  <Loader2 className="h-4 w-4 animate-spin text-gray-500" />
                </div>
              </div>
            )}
            <div ref={messagesEndRef} />
          </>
        )}
      </div>

      {/* Input area */}
      <div className="p-3 border-t border-gray-100" data-testid="ai-input-area">
        <div className="flex items-end gap-2">
          <textarea
            ref={inputRef}
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyDown={handleKeyDown}
            placeholder="Ask AI to modify this element..."
            className={cn(
              "flex-1 resize-none rounded-lg border border-gray-200 px-3 py-2",
              "text-sm placeholder:text-gray-400",
              "focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent",
              "min-h-[40px] max-h-[100px]"
            )}
            rows={1}
            disabled={isLoading}
            data-testid="ai-input"
          />
          <Button
            size="sm"
            onClick={handleSend}
            disabled={!input.trim() || isLoading}
            className="h-10 w-10 p-0"
            data-testid="ai-send-btn"
          >
            {isLoading ? (
              <Loader2 className="h-4 w-4 animate-spin" />
            ) : (
              <Send className="h-4 w-4" />
            )}
          </Button>
        </div>

        {/* Info note */}
        <div className="flex items-start gap-1.5 mt-2" data-testid="ai-info">
          <Info className="h-3 w-3 text-gray-400 mt-0.5 flex-shrink-0" />
          <p className="text-[10px] text-gray-400">
            AI can modify element properties, apply templates, and help configure this{' '}
            {element.children?.length ? 'container and its children' : 'element'}.
          </p>
        </div>
      </div>
    </div>
  );
};

/**
 * Generate a placeholder response for demo purposes.
 * In production, this would be replaced with actual AI API calls.
 */
function generatePlaceholderResponse(
  userInput: string,
  context: Record<string, unknown>
): string {
  const input = userInput.toLowerCase();

  if (input.includes('required')) {
    return `I understand you want to make this field required. In the Behavior tab, you can toggle "Required field" to enable this. Would you like me to explain the validation options available?`;
  }

  if (input.includes('validation') || input.includes('validate')) {
    return `For validation, you have several options:\n• Required field toggle\n• Min/Max length limits\n• Pattern (regex) validation\n• Format validation (email, URL)\n\nWhat type of validation would you like to add?`;
  }

  if (input.includes('style') || input.includes('label') || input.includes('color')) {
    return `Styling can be done in the Style tab. You can:\n• Change typography (font, size, weight)\n• Adjust colors and backgrounds\n• Set borders and spacing\n\nSelect the "Label" target in the Style tab to modify label appearance.`;
  }

  if (input.includes('children') || input.includes('all fields')) {
    const childCount = context.childCount as number;
    if (childCount > 0) {
      return `This container has ${childCount} child elements. I can help you apply changes to all of them. What would you like to modify across all children?`;
    }
    return `This element doesn't have any children. It's not a container element.`;
  }

  return `I'm here to help you configure this ${context.schemaName || context.elementType} element. You can ask me to:\n• Modify properties\n• Apply validation rules\n• Adjust styling\n• Apply templates\n\nWhat would you like to do?`;
}

export default AITab;
