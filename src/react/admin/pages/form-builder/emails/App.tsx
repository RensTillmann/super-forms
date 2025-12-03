import { useEffect } from 'react';
import useEmailStore from './hooks/useEmailStore';
import EmailList from './components/EmailList';
import EmailClientBuilder from './components/Preview/EmailClientBuilder';
import type { Translations } from './types/email';
import type { EmailConfig, I18nStrings } from '@/types/global';

interface AppProps {
  formId?: string | number;
  emails?: EmailConfig[];
  translations?: Translations | Record<string, unknown>;
  settings?: Record<string, unknown>;
  ajaxUrl?: string;
  nonce?: string;
  i18n?: I18nStrings;
  restNonce?: string;
  currentUserEmail?: string;
  currentPage?: string;
  [key: string]: unknown;
}

function App({ formId, emails, translations, ajaxUrl, nonce, i18n }: AppProps) {
  const {
    initializeStore,
    activeEmailId,
    isDirty,
    isSaving,
    save
  } = useEmailStore();

  // Hide sidebars when Emails tab is active
  useEffect(() => {
    // Hide the right sidebars when Emails tab is active
    // 1. Element Settings panel (property editor)
    const elementSettings = document.querySelector('.super-element-settings') as HTMLElement | null;
    if (elementSettings) {
      elementSettings.style.display = 'none';
    }
    // 2. Elements palette (draggable elements)
    const elementsPanel = document.querySelector('.super-elements') as HTMLElement | null;
    if (elementsPanel) {
      elementsPanel.style.display = 'none';
    }
    // Expand content area
    const tabsContent = document.querySelector('.super-tabs-content') as HTMLElement | null;
    if (tabsContent) {
      tabsContent.style.width = '100%';
      tabsContent.style.float = 'none';
    }

    // Cleanup: restore sidebars when component unmounts
    return () => {
      if (elementSettings) {
        elementSettings.style.display = '';
      }
      if (elementsPanel) {
        elementsPanel.style.display = '';
      }
      if (tabsContent) {
        tabsContent.style.width = '';
        tabsContent.style.float = '';
      }
    };
  }, []);

  // Initialize store on mount
  useEffect(() => {
    initializeStore({ emails, translations });
  }, []);

  // Auto-save functionality
  useEffect(() => {
    if (isDirty && !isSaving) {
      const saveTimer = setTimeout(() => {
        handleSave();
      }, 2000); // Auto-save after 2 seconds of no changes

      return () => clearTimeout(saveTimer);
    }
  }, [isDirty]);

  const handleSave = async () => {
    // Skip saving in development mode if WordPress context is not available
    if (!formId || !ajaxUrl || !nonce) {
      console.log('🔧 Development mode: Skipping WordPress save (no backend context)');
      return { success: true };
    }

    const result = await save(formId, ajaxUrl, nonce);
    if (result.success) {
      // Update the hidden textarea in the Code tab
      updateCodeTab();
    }
    return result;
  };

  const updateCodeTab = () => {
    const state = useEmailStore.getState();
    const emailsTextarea = document.querySelector('.super-raw-code-emails-settings textarea') as HTMLTextAreaElement | null;
    if (emailsTextarea) {
      emailsTextarea.value = JSON.stringify(state.emails, null, 2);
      // Trigger change event for any listeners
      emailsTextarea.dispatchEvent(new Event('change', { bubbles: true }));
    }
  };

  return (
    <div data-testid="emails-tab" className="sfui-emails-tab h-full">
      <div className="flex h-full gap-4">
        {/* Email List Sidebar */}
        <div data-testid="email-list-sidebar" className="w-64 flex-shrink-0">
          <EmailList />
        </div>

        {/* Main Content Area - Email Builder */}
        <div data-testid="email-builder-main" className="flex-1 min-w-0">
          {activeEmailId ? (
            <div
              data-testid="email-builder-container"
              className="bg-white rounded-lg border border-gray-200 overflow-hidden h-full"
              style={{ boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)' }}
            >
              <EmailClientBuilder />
            </div>
          ) : (
            <div data-testid="email-placeholder" className="bg-gray-50 rounded-lg p-8 text-center text-gray-500 h-full flex items-center justify-center">
              <div>
                <svg className="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                  />
                </svg>
                <p className="text-lg">{i18n?.selectEmail || 'Select an email to edit'}</p>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Save Status - positioned within the container */}
      {(isDirty || isSaving) && (
        <div data-testid="save-status" className="absolute bottom-4 left-4 bg-white rounded-lg shadow-md px-4 py-2 flex items-center gap-2 z-10">
          {isSaving ? (
            <>
              <svg className="animate-spin h-4 w-4 text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span className="text-sm text-gray-600">{i18n?.saving || 'Saving...'}</span>
            </>
          ) : (
            <>
              <div className="w-2 h-2 bg-yellow-400 rounded-full"></div>
              <span className="text-sm text-gray-600">Unsaved changes</span>
            </>
          )}
        </div>
      )}

    </div>
  );
}

export default App;
