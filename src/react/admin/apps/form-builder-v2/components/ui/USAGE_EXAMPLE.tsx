import React from 'react';
import { ToastProvider } from './ui-components';
import FormBuilderComplete from './FormBuilderComplete';

/**
 * Example of how to use the updated FormBuilderComplete with the extracted UI components
 */
export const FormBuilderApp: React.FC = () => {
  return (
    // Wrap with ToastProvider to enable toast notifications
    <ToastProvider position="top-right" maxToasts={5}>
      <FormBuilderComplete />
    </ToastProvider>
  );
};

/**
 * Example of using individual UI components in other parts of the application
 */
export const OtherFeatureExample: React.FC = () => {
  const [isShareOpen, setIsShareOpen] = React.useState(false);
  const [isAnalyticsOpen, setIsAnalyticsOpen] = React.useState(false);
  const { addToast } = useToast();

  return (
    <div>
      <h1>Other Feature Using Shared Components</h1>
      
      {/* Reuse the SharePanel component */}
      <button onClick={() => setIsShareOpen(true)}>
        Share Feature
      </button>
      
      <SharePanel
        isOpen={isShareOpen}
        onClose={() => setIsShareOpen(false)}
        formUrl="https://example.com/feature"
      />
      
      {/* Reuse the AnalyticsPanel component */}
      <button onClick={() => setIsAnalyticsOpen(true)}>
        View Analytics
      </button>
      
      <AnalyticsPanel
        isOpen={isAnalyticsOpen}
        onClose={() => setIsAnalyticsOpen(false)}
        analytics={{
          totalViews: 5678,
          submissions: 234,
          conversionRate: 4.1,
          averageTime: '1.2min'
        }}
      />
      
      {/* Use toast notifications */}
      <button onClick={() => addToast('Feature saved!', 'success')}>
        Save with Toast
      </button>
    </div>
  );
};