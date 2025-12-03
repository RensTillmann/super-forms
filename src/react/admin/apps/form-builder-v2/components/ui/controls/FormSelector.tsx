import React, { useState, useRef, useEffect } from 'react';
import { ChevronDown, Plus, FileText, Archive, Clock } from 'lucide-react';
import { FormSelectorProps, FormOption } from '../types/control.types';

const defaultForms: FormOption[] = [
  { id: '1', name: 'Contact Form', status: 'published', lastModified: Date.now() - 86400000 },
  { id: '2', name: 'Registration Form', status: 'draft', lastModified: Date.now() - 172800000 },
  { id: '3', name: 'Survey Form', status: 'published', lastModified: Date.now() - 259200000 },
];

export const FormSelector: React.FC<FormSelectorProps> = ({ 
  currentForm, 
  onFormSelect,
  forms = defaultForms
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const dropdownRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    };

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isOpen]);

  const currentFormData = forms.find(f => f.id === currentForm);
  const filteredForms = forms.filter(form => 
    form.name.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'published': return 'text-green-600 bg-green-50';
      case 'draft': return 'text-gray-600 bg-gray-50';
      case 'archived': return 'text-orange-600 bg-orange-50';
      default: return 'text-gray-600 bg-gray-50';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'published': return <FileText size={12} />;
      case 'draft': return <Clock size={12} />;
      case 'archived': return <Archive size={12} />;
      default: return null;
    }
  };

  const formatLastModified = (timestamp?: number) => {
    if (!timestamp) return '';
    const now = Date.now();
    const diff = now - timestamp;
    
    if (diff < 3600000) {
      return `${Math.floor(diff / 60000)}m ago`;
    } else if (diff < 86400000) {
      return `${Math.floor(diff / 3600000)}h ago`;
    } else {
      return `${Math.floor(diff / 86400000)}d ago`;
    }
  };

  return (
    <div className="form-selector" ref={dropdownRef}>
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="form-selector-trigger"
        aria-haspopup="listbox"
        aria-expanded={isOpen}
      >
        <div className="form-selector-value">
          <span className="form-selector-name">{currentFormData?.name || 'Select Form'}</span>
          {currentFormData && (
            <span className={`form-selector-status ${getStatusColor(currentFormData.status)}`}>
              {getStatusIcon(currentFormData.status)}
              {currentFormData.status}
            </span>
          )}
        </div>
        <ChevronDown size={16} className={`form-selector-chevron ${isOpen ? 'rotate-180' : ''}`} />
      </button>
      
      {isOpen && (
        <div className="form-selector-dropdown" role="listbox">
          <div className="form-selector-search">
            <input
              type="text"
              placeholder="Search forms..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="form-selector-search-input"
              onClick={(e) => e.stopPropagation()}
            />
          </div>
          
          <div className="form-selector-list">
            {filteredForms.map(form => (
              <div
                key={form.id}
                className={`form-selector-item ${form.id === currentForm ? 'form-selector-item-active' : ''}`}
                onClick={() => {
                  onFormSelect(form.id);
                  setIsOpen(false);
                  setSearchTerm('');
                }}
                role="option"
                aria-selected={form.id === currentForm}
              >
                <div className="form-selector-item-content">
                  <span className="form-selector-item-name">{form.name}</span>
                  <div className="form-selector-item-meta">
                    <span className={`form-selector-item-status ${getStatusColor(form.status)}`}>
                      {getStatusIcon(form.status)}
                      {form.status}
                    </span>
                    {form.lastModified && (
                      <span className="form-selector-item-time">
                        {formatLastModified(form.lastModified)}
                      </span>
                    )}
                  </div>
                </div>
              </div>
            ))}
            
            <div className="form-selector-item form-selector-item-create">
              <Plus size={16} />
              <span>Create New Form</span>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};