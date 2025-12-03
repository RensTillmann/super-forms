import React, { Component, ErrorInfo, ReactNode } from 'react';
import { AlertCircle, RefreshCw } from 'lucide-react';
import { ErrorBoundaryProps, ErrorBoundaryState, ErrorFallbackProps } from '../types/error-boundary.types';

const DefaultErrorFallback: React.FC<ErrorFallbackProps> = ({ error, errorInfo, onReset }) => {
  const isDevelopment = process.env.NODE_ENV === 'development';

  return (
    <div className="error-boundary">
      <div className="error-content">
        <AlertCircle size={48} className="text-red-500 mb-4" />
        <h2 className="text-xl font-semibold mb-2">Something went wrong</h2>
        <p className="text-gray-600 mb-4">
          The form builder encountered an error. Please refresh the page to continue.
        </p>
        
        {isDevelopment && (
          <details className="mb-4 text-left max-w-2xl">
            <summary className="cursor-pointer text-sm text-gray-500 hover:text-gray-700">
              Error Details (Development Only)
            </summary>
            <div className="mt-2 p-4 bg-gray-100 rounded text-xs">
              <p className="font-mono text-red-600 mb-2">{error.toString()}</p>
              {errorInfo && (
                <pre className="text-gray-700 overflow-auto">
                  {errorInfo.componentStack}
                </pre>
              )}
            </div>
          </details>
        )}
        
        <div className="flex gap-2 justify-center">
          <button 
            onClick={onReset}
            className="btn btn-primary"
          >
            <RefreshCw size={16} className="mr-2" />
            Try Again
          </button>
          <button 
            onClick={() => window.location.reload()}
            className="btn btn-outline"
          >
            Refresh Page
          </button>
        </div>
      </div>
    </div>
  );
};

export class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
  private resetTimeoutId: number | null = null;
  private previousResetKeys: string[] = [];

  constructor(props: ErrorBoundaryProps) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    const { onError } = this.props;
    
    // Log error to console
    console.error('ErrorBoundary caught an error:', error, errorInfo);
    
    // Call custom error handler if provided
    if (onError) {
      onError(error, errorInfo);
    }
    
    // Update state with error info
    this.setState({ errorInfo });
  }

  componentDidUpdate(prevProps: ErrorBoundaryProps) {
    const { resetKeys = [], resetOnPropsChange } = this.props;
    const hasResetKeyChanged = resetKeys.some((key, index) => key !== this.previousResetKeys[index]);
    
    if (hasResetKeyChanged || (resetOnPropsChange && prevProps.children !== this.props.children)) {
      this.resetErrorBoundary();
      this.previousResetKeys = [...resetKeys];
    }
  }

  componentWillUnmount() {
    if (this.resetTimeoutId) {
      clearTimeout(this.resetTimeoutId);
    }
  }

  resetErrorBoundary = () => {
    if (this.resetTimeoutId) {
      clearTimeout(this.resetTimeoutId);
    }
    
    this.resetTimeoutId = window.setTimeout(() => {
      this.setState({ hasError: false, error: undefined, errorInfo: undefined });
    }, 0);
  };

  render() {
    const { hasError, error, errorInfo } = this.state;
    const { children, fallback } = this.props;

    if (hasError && error && errorInfo) {
      if (fallback) {
        return fallback(error, errorInfo, this.resetErrorBoundary);
      }
      
      return (
        <DefaultErrorFallback 
          error={error} 
          errorInfo={errorInfo} 
          onReset={this.resetErrorBoundary}
        />
      );
    }

    return children;
  }
}