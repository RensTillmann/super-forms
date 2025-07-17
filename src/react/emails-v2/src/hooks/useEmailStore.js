import { create } from 'zustand';
import { devtools } from 'zustand/middleware';

const useEmailStore = create(devtools((set, get) => ({
  // State
  emails: [],
  activeEmailId: null,
  activeLanguage: 'default',
  translations: {},
  isDirty: false,
  isSaving: false,
  error: null,

  // Actions
  initializeStore: (data) => {
    set({
      emails: data.emails || [],
      activeEmailId: data.emails?.[0]?.id || null,
      translations: data.translations || {},
    });
  },

  // Email CRUD operations
  addEmail: () => {
    const newEmail = {
      id: `email_${Date.now()}`,
      enabled: true,
      description: 'New Email',
      to: '{email}',
      from_email: '',
      from_name: '{option_blogname}',
      subject: 'New submission',
      body: '<p>The following information has been sent:</p>{loop_fields}',
      attachments: [],
      reply_to: {
        enabled: false,
        email: '',
        name: ''
      },
      cc: '',
      bcc: '',
      template: {
        slug: 'none'
      },
      conditions: {
        enabled: false,
        f1: '',
        logic: '==',
        f2: ''
      },
      schedule: {
        enabled: false,
        schedules: []
      }
    };

    set((state) => ({
      emails: [...state.emails, newEmail],
      activeEmailId: newEmail.id,
      isDirty: true
    }));
  },

  removeEmail: (emailId) => {
    set((state) => {
      const newEmails = state.emails.filter(e => e.id !== emailId);
      const newActiveId = state.activeEmailId === emailId 
        ? (newEmails[0]?.id || null)
        : state.activeEmailId;
      
      return {
        emails: newEmails,
        activeEmailId: newActiveId,
        isDirty: true
      };
    });
  },

  updateEmail: (emailId, updates) => {
    set((state) => ({
      emails: state.emails.map(email => 
        email.id === emailId ? { ...email, ...updates } : email
      ),
      isDirty: true
    }));
  },

  updateEmailField: (emailId, path, value) => {
    set((state) => {
      const emails = state.emails.map(email => {
        if (email.id !== emailId) return email;
        
        // Handle nested paths like 'reply_to.enabled'
        const keys = path.split('.');
        const newEmail = { ...email };
        let current = newEmail;
        
        for (let i = 0; i < keys.length - 1; i++) {
          const key = keys[i];
          current[key] = { ...current[key] };
          current = current[key];
        }
        
        current[keys[keys.length - 1]] = value;
        return newEmail;
      });
      
      return { emails, isDirty: true };
    });
  },

  // Active email management
  setActiveEmailId: (emailId) => {
    set({ activeEmailId: emailId });
  },

  getActiveEmail: () => {
    const state = get();
    return state.emails.find(e => e.id === state.activeEmailId);
  },

  // Language/translation management
  setActiveLanguage: (language) => {
    set({ activeLanguage: language });
  },

  updateTranslation: (emailId, field, language, value) => {
    set((state) => {
      const newTranslations = { ...state.translations };
      if (!newTranslations[language]) {
        newTranslations[language] = {};
      }
      if (!newTranslations[language][emailId]) {
        newTranslations[language][emailId] = {};
      }
      newTranslations[language][emailId][field] = value;
      
      return { translations: newTranslations, isDirty: true };
    });
  },

  // Reorder emails
  reorderEmails: (oldIndex, newIndex) => {
    set((state) => {
      const newEmails = [...state.emails];
      const [removed] = newEmails.splice(oldIndex, 1);
      newEmails.splice(newIndex, 0, removed);
      return { emails: newEmails, isDirty: true };
    });
  },

  // Save functionality
  save: async (formId, ajaxUrl, nonce) => {
    const state = get();
    set({ isSaving: true, error: null });

    try {
      const formData = new FormData();
      formData.append('action', 'super_save_form_emails');
      formData.append('form_id', formId);
      formData.append('nonce', nonce);
      formData.append('emails', JSON.stringify(state.emails));
      formData.append('translations', JSON.stringify(state.translations));

      const response = await fetch(ajaxUrl, {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Failed to save');
      }

      set({ isSaving: false, isDirty: false });
      return { success: true };
    } catch (error) {
      set({ isSaving: false, error: error.message });
      return { success: false, error: error.message };
    }
  },

  // Reset store
  reset: () => {
    set({
      emails: [],
      activeEmailId: null,
      activeLanguage: 'default',
      translations: {},
      isDirty: false,
      isSaving: false,
      error: null
    });
  }
})));

export default useEmailStore;