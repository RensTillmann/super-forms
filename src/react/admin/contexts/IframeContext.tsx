import React, { createContext, useContext, useMemo } from 'react';

interface IframeContextValue {
  /** The document to use for portal rendering (iframe document or parent document) */
  portalDocument: Document;
  /** Whether we're running in an iframe */
  isInIframe: boolean;
}

const IframeContext = createContext<IframeContextValue | undefined>(undefined);

interface IframeProviderProps {
  children: React.ReactNode;
}

/**
 * IframeContext provider - detects iframe context and provides correct document for portals
 *
 * When running in iframe, components should portal to iframe's document.body instead of
 * parent's document.body to ensure they're in the isolated CSS context.
 */
export const IframeProvider: React.FC<IframeProviderProps> = ({ children }) => {
  const value = useMemo<IframeContextValue>(() => {
    const isInIframe = window !== window.parent;

    return {
      portalDocument: document,
      isInIframe,
    };
  }, []);

  return <IframeContext.Provider value={value}>{children}</IframeContext.Provider>;
};

/**
 * Hook to access iframe context
 *
 * @returns IframeContextValue with portalDocument and isInIframe flag
 */
export const useIframeContext = (): IframeContextValue => {
  const context = useContext(IframeContext);

  if (!context) {
    throw new Error('useIframeContext must be used within IframeProvider');
  }

  return context;
};

/**
 * Hook to get the correct document for portal rendering
 *
 * @returns Document to use for createPortal()
 */
export const usePortalDocument = (): Document => {
  const { portalDocument } = useIframeContext();
  return portalDocument;
};
