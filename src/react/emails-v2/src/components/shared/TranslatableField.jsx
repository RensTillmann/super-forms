import React, { useState, useRef, useEffect } from 'react';
import useEmailStore from '../../hooks/useEmailStore';
import clsx from 'clsx';

function TranslatableField({ label, value, onChange, required, error, help, children }) {
  const [showTranslations, setShowTranslations] = useState(false);
  const [position, setPosition] = useState({ top: 0, left: 0 });
  const buttonRef = useRef(null);
  const popoverRef = useRef(null);
  
  const { 
    activeLanguage, 
    translations, 
    activeEmailId,
    updateTranslation 
  } = useEmailStore();

  // Get available languages from translations
  const availableLanguages = Object.keys(translations);
  const hasTranslations = availableLanguages.length > 0;

  useEffect(() => {
    if (showTranslations && buttonRef.current) {
      const rect = buttonRef.current.getBoundingClientRect();
      setPosition({
        top: rect.bottom + 5,
        left: rect.left
      });
    }
  }, [showTranslations]);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (popoverRef.current && !popoverRef.current.contains(event.target) &&
          buttonRef.current && !buttonRef.current.contains(event.target)) {
        setShowTranslations(false);
      }
    };

    if (showTranslations) {
      document.addEventListener('mousedown', handleClickOutside);
    }
    
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [showTranslations]);

  const getFlagClass = (lang) => {
    // Map language codes to flag classes
    const flagMap = {
      'en': 'us',
      'es': 'es',
      'fr': 'fr',
      'de': 'de',
      'it': 'it',
      'nl': 'nl',
      'pt': 'pt',
      'ru': 'ru',
      'ja': 'jp',
      'zh': 'cn'
    };
    return flagMap[lang] || lang;
  };

  const handleTranslationChange = (lang, fieldName, value) => {
    updateTranslation(activeEmailId, fieldName, lang, value);
  };

  return (
    <div className="ev2-space-y-1">
      {label && (
        <label className="ev2-flex ev2-items-center ev2-gap-2 ev2-text-sm ev2-font-medium ev2-text-gray-700">
          <span>{label}</span>
          {required && <span className="ev2-text-red-500">*</span>}
          {hasTranslations && (
            <button
              ref={buttonRef}
              type="button"
              onClick={() => setShowTranslations(!showTranslations)}
              className={clsx(
                'ev2-p-1 ev2-rounded ev2-transition-colors',
                showTranslations 
                  ? 'ev2-bg-primary-100 ev2-text-primary-700' 
                  : 'hover:ev2-bg-gray-100'
              )}
              title="Manage translations"
            >
              <svg className="ev2-w-4 ev2-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                  d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" 
                />
              </svg>
            </button>
          )}
        </label>
      )}
      
      {children}
      
      {help && (
        <p className="ev2-text-xs ev2-text-gray-500">{help}</p>
      )}
      {error && (
        <p className="ev2-text-xs ev2-text-red-600">{error}</p>
      )}
      
      {/* Translation Popover */}
      {showTranslations && (
        <div
          ref={popoverRef}
          className="ev2-fixed ev2-z-50 ev2-bg-white ev2-rounded-lg ev2-shadow-xl ev2-border ev2-border-gray-200 ev2-p-4 ev2-min-w-[300px]"
          style={{ top: position.top, left: position.left }}
        >
          <h4 className="ev2-font-medium ev2-text-sm ev2-text-gray-900 ev2-mb-3">
            Translations
          </h4>
          <div className="ev2-space-y-3">
            {availableLanguages.map(lang => (
              <div key={lang} className="ev2-space-y-1">
                <label className="ev2-flex ev2-items-center ev2-gap-2 ev2-text-xs ev2-font-medium ev2-text-gray-600">
                  <img 
                    src={`/wp-content/plugins/super-forms/assets/images/flags/${getFlagClass(lang)}.png`}
                    alt={lang}
                    className="ev2-w-4 ev2-h-3"
                  />
                  <span>{lang.toUpperCase()}</span>
                </label>
                <input
                  type="text"
                  value={translations[lang]?.[activeEmailId]?.[label] || ''}
                  onChange={(e) => handleTranslationChange(lang, label, e.target.value)}
                  placeholder={`${label} in ${lang.toUpperCase()}`}
                  className="ev2-w-full ev2-px-2 ev2-py-1 ev2-text-sm ev2-border ev2-border-gray-300 ev2-rounded focus:ev2-ring-1 focus:ev2-ring-primary-500 focus:ev2-border-transparent"
                />
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

export default TranslatableField;