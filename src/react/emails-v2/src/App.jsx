import React, { useEffect } from 'react';
import useEmailStore from './hooks/useEmailStore';
import EmailList from './components/EmailList';
import EmailEditor from './components/EmailEditor';
import EmailClientBuilder from './components/Preview/EmailClientBuilder';
import PropertyPanel from './components/Builder/PropertyPanel';
import useEmailBuilder from './hooks/useEmailBuilder';

function App({ formId, emails, translations, settings, ajaxUrl, nonce, i18n }) {
  const { 
    initializeStore, 
    activeEmailId,
    isDirty,
    isSaving,
    save
  } = useEmailStore();
  
  const { selectedElementId } = useEmailBuilder();

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
    const result = await save(formId, ajaxUrl, nonce);
    if (result.success) {
      // Update the hidden textarea in the Code tab
      updateCodeTab();
    }
  };

  const updateCodeTab = () => {
    const state = useEmailStore.getState();
    const emailsTextarea = document.querySelector('.super-raw-code-emails-settings textarea');
    if (emailsTextarea) {
      emailsTextarea.value = JSON.stringify(state.emails, null, 2);
      // Trigger change event for any listeners
      emailsTextarea.dispatchEvent(new Event('change', { bubbles: true }));
    }
  };

  return (
    <div className="super-emails-v2-container ev2-h-full">
      <div className="ev2-flex ev2-h-full ev2-gap-4">
        {/* Email List Sidebar */}
        <div className="ev2-w-64 ev2-flex-shrink-0">
          <EmailList />
        </div>

        {/* Main Content Area - Split View */}
        <div className="ev2-flex-1 ev2-flex ev2-gap-4">
          {/* Email Editor or Element Properties */}
          <div className="ev2-flex-1 ev2-min-w-0">
            {selectedElementId ? (
              <PropertyPanel />
            ) : activeEmailId ? (
              <EmailEditor />
            ) : (
              <div className="ev2-bg-gray-50 ev2-rounded-lg ev2-p-8 ev2-text-center ev2-text-gray-500 ev2-h-full ev2-flex ev2-items-center ev2-justify-center">
                <div>
                  <svg className="ev2-mx-auto ev2-h-12 ev2-w-12 ev2-text-gray-300 ev2-mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" 
                    />
                  </svg>
                  <p className="ev2-text-lg">{i18n.selectEmail || 'Select an email to edit'}</p>
                </div>
              </div>
            )}
          </div>

          {/* Email Builder - Always Visible */}
          <div className="ev2-w-1/2 ev2-min-w-[400px] ev2-bg-white ev2-rounded-lg ev2-shadow-sm ev2-border ev2-border-gray-200 ev2-overflow-hidden">
            <EmailClientBuilder />
          </div>
        </div>
      </div>

      {/* Save Status */}
      {(isDirty || isSaving) && (
        <div className="ev2-fixed ev2-bottom-4 ev2-left-4 ev2-bg-white ev2-rounded-lg ev2-shadow-md ev2-px-4 ev2-py-2 ev2-flex ev2-items-center ev2-gap-2">
          {isSaving ? (
            <>
              <svg className="ev2-animate-spin ev2-h-4 ev2-w-4 ev2-text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle className="ev2-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                <path className="ev2-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span className="ev2-text-sm ev2-text-gray-600">{i18n.saving || 'Saving...'}</span>
            </>
          ) : (
            <>
              <div className="ev2-w-2 ev2-h-2 ev2-bg-yellow-400 ev2-rounded-full"></div>
              <span className="ev2-text-sm ev2-text-gray-600">Unsaved changes</span>
            </>
          )}
        </div>
      )}

    </div>
  );
}

export default App;