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
        <div className="p-4 bg-red-50 border border-red-200 rounded text-sm">
          <div className="flex items-center mb-2">
            <span className="text-red-600 font-medium">⚠️ Editor Error</span>
          </div>
          <p className="text-red-700 mb-2">
            The editor encountered an error and had to be reset. This usually happens when switching between editor types.
          </p>
          <details className="text-xs text-red-600">
            <summary className="cursor-pointer font-medium">Technical Details</summary>
            <pre className="mt-2 p-2 bg-red-100 rounded overflow-auto">
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
            className="mt-3 px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700"
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