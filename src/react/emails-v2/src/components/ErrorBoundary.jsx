import React from 'react';

/**
 * Error Boundary component to catch and handle React errors gracefully
 */
class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null, errorInfo: null };
  }

  static getDerivedStateFromError(error) {
    // Update state so the next render will show the fallback UI
    return { hasError: true };
  }

  componentDidCatch(error, errorInfo) {
    // Log the error details
    console.error('Error Boundary caught an error:', error);
    console.error('Error Info:', errorInfo);
    
    // Update state with error details
    this.setState({
      error: error,
      errorInfo: errorInfo
    });
  }

  render() {
    if (this.state.hasError) {
      // Fallback UI
      return (
        <div className="ev2-p-4 ev2-bg-red-50 ev2-border ev2-border-red-200 ev2-rounded ev2-text-sm">
          <div className="ev2-flex ev2-items-center ev2-mb-2">
            <span className="ev2-text-red-600 ev2-font-medium">⚠️ Editor Error</span>
          </div>
          <p className="ev2-text-red-700 ev2-mb-2">
            The editor encountered an error and had to be reset. This usually happens when switching between editor types.
          </p>
          <details className="ev2-text-xs ev2-text-red-600">
            <summary className="ev2-cursor-pointer ev2-font-medium">Technical Details</summary>
            <pre className="ev2-mt-2 ev2-p-2 ev2-bg-red-100 ev2-rounded ev2-overflow-auto">
              {this.state.error && this.state.error.toString()}
              {this.state.errorInfo && this.state.errorInfo.componentStack}
            </pre>
          </details>
          <button
            onClick={() => {
              this.setState({ hasError: false, error: null, errorInfo: null });
              // Trigger re-render of children
              if (this.props.onReset) {
                this.props.onReset();
              }
            }}
            className="ev2-mt-3 ev2-px-3 ev2-py-1 ev2-bg-red-600 ev2-text-white ev2-text-xs ev2-rounded hover:ev2-bg-red-700"
          >
            Reset Editor
          </button>
        </div>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;