#!/usr/bin/env python3
"""
Super Forms Complexity Analyzer
Analyzes all JSON form exports to identify the most complex forms for testing.
"""

import json
import os
import re
from collections import defaultdict
from typing import Dict, List, Tuple, Any

class FormComplexityAnalyzer:
    def __init__(self, forms_directory: str):
        self.forms_directory = forms_directory
        self.complexity_scores = []
        
    def analyze_all_forms(self) -> List[Dict[str, Any]]:
        """Analyze all forms in the directory and return sorted by complexity."""
        form_files = [f for f in os.listdir(self.forms_directory) if f.startswith('form_') and f.endswith('.json')]
        
        for form_file in form_files:
            try:
                form_data = self.load_form(form_file)
                if form_data:
                    complexity_info = self.analyze_form_complexity(form_data, form_file)
                    self.complexity_scores.append(complexity_info)
            except Exception as e:
                print(f"Error analyzing {form_file}: {str(e)}")
                
        # Sort by complexity score (descending)
        self.complexity_scores.sort(key=lambda x: x['total_score'], reverse=True)
        return self.complexity_scores[:20]  # Return top 20
    
    def load_form(self, filename: str) -> Dict[str, Any]:
        """Load and parse a form JSON file."""
        filepath = os.path.join(self.forms_directory, filename)
        try:
            with open(filepath, 'r', encoding='utf-8') as f:
                return json.load(f)
        except Exception as e:
            print(f"Failed to load {filename}: {str(e)}")
            return None
    
    def analyze_form_complexity(self, form_data: Dict[str, Any], filename: str) -> Dict[str, Any]:
        """Analyze a single form's complexity and return detailed info."""
        form_id = form_data.get('id', 'unknown')
        title = form_data.get('title', 'Unknown Title')
        
        # Parse serialized settings and elements
        settings = self.parse_serialized_data(form_data.get('settings', ''))
        elements = self.parse_serialized_data(form_data.get('elements', ''))
        
        complexity_info = {
            'form_id': form_id,
            'filename': filename,
            'title': title,
            'element_count': 0,
            'features': [],
            'complexity_reasons': [],
            'total_score': 0,
            'settings': settings,
            'elements': elements
        }
        
        # Count elements
        element_count = self.count_elements(elements)
        complexity_info['element_count'] = element_count
        
        # Analyze features and calculate complexity
        self.analyze_features(settings, elements, complexity_info)
        
        return complexity_info
    
    def parse_serialized_data(self, data_str: str) -> Dict[str, Any]:
        """Parse PHP serialized data (simplified parser for this use case)."""
        if not data_str:
            return {}
            
        try:
            # For now, just look for key patterns in the serialized string
            # This is a simplified approach for detecting features
            return {'raw': data_str}
        except:
            return {}
    
    def count_elements(self, elements: Dict[str, Any]) -> int:
        """Count the total number of form elements."""
        if not elements:
            return 0
            
        # Count elements in serialized data
        raw_data = elements.get('raw', '')
        if raw_data:
            # Count element tags
            element_patterns = [
                r'"tag";s:\d+:"(\w+)"',  # Match tag definitions
                r's:3:"tag";s:\d+:"(\w+)"'  # Alternative format
            ]
            
            total_elements = 0
            for pattern in element_patterns:
                matches = re.findall(pattern, raw_data)
                total_elements += len(matches)
            
            return total_elements
        
        return 0
    
    def analyze_features(self, settings: Dict[str, Any], elements: Dict[str, Any], complexity_info: Dict[str, Any]):
        """Analyze features and calculate complexity score."""
        raw_settings = settings.get('raw', '')
        raw_elements = elements.get('raw', '')
        
        score = 0
        features = []
        reasons = []
        
        # High Priority Features (20 points each)
        
        # PDF Generator
        if self.check_pdf_features(raw_settings):
            score += 20
            features.append('PDF Generator')
            reasons.append('Has PDF generation capabilities')
        
        # Listings
        if self.check_listings_features(raw_settings):
            score += 20
            features.append('Listings')
            reasons.append('Has listings functionality')
        
        # WooCommerce
        if self.check_woocommerce_features(raw_settings):
            score += 20
            features.append('WooCommerce')
            reasons.append('WooCommerce integration')
        
        # PayPal
        if self.check_paypal_features(raw_settings):
            score += 20
            features.append('PayPal')
            reasons.append('PayPal payment integration')
        
        # Translations/Internationalization
        if self.check_translation_features(raw_settings):
            score += 20
            features.append('Translations')
            reasons.append('Multiple language support')
        
        # File Uploads
        file_upload_count = self.check_file_uploads(raw_elements)
        if file_upload_count > 0:
            score += 15
            features.append(f'File Uploads ({file_upload_count})')
            reasons.append(f'Has {file_upload_count} file upload fields')
        
        # Secondary Complexity Indicators (5-15 points each)
        
        # Large element count
        element_count = complexity_info['element_count']
        if element_count > 20:
            score += 15
            reasons.append(f'Large form with {element_count} elements')
        elif element_count > 10:
            score += 10
            reasons.append(f'Medium-sized form with {element_count} elements')
        elif element_count > 5:
            score += 5
            reasons.append(f'Multiple elements ({element_count})')
        
        # Conditional Logic
        conditional_count = self.check_conditional_logic(raw_elements)
        if conditional_count > 5:
            score += 15
            features.append('Complex Conditional Logic')
            reasons.append(f'Has {conditional_count} conditional logic rules')
        elif conditional_count > 0:
            score += 10
            features.append('Conditional Logic')
            reasons.append(f'Has {conditional_count} conditional logic rules')
        
        # Email Templates
        if self.check_email_templates(raw_settings):
            score += 10
            features.append('Email Templates')
            reasons.append('Custom email templates configured')
        
        # Multi-part/Column Layout
        column_count = self.check_column_layout(raw_elements)
        if column_count > 5:
            score += 10
            features.append('Complex Layout')
            reasons.append(f'Multi-column layout with {column_count} columns')
        elif column_count > 0:
            score += 5
            features.append('Column Layout')
            reasons.append(f'Has {column_count} column layout')
        
        # Advanced Validations
        validation_count = self.check_validations(raw_elements)
        if validation_count > 5:
            score += 10
            features.append('Advanced Validations')
            reasons.append(f'Has {validation_count} validation rules')
        elif validation_count > 0:
            score += 5
            features.append('Validations')
            reasons.append(f'Has {validation_count} validation rules')
        
        # Calculator functionality
        if self.check_calculator_features(raw_elements, raw_settings):
            score += 15
            features.append('Calculator')
            reasons.append('Has calculation features')
        
        # Signature fields
        signature_count = self.check_signature_fields(raw_elements)
        if signature_count > 0:
            score += 10
            features.append(f'Signatures ({signature_count})')
            reasons.append(f'Has {signature_count} signature fields')
        
        # Other integrations
        other_integrations = self.check_other_integrations(raw_settings)
        if other_integrations:
            score += len(other_integrations) * 5
            features.extend(other_integrations)
            reasons.append(f'Third-party integrations: {", ".join(other_integrations)}')
        
        complexity_info['features'] = features
        complexity_info['complexity_reasons'] = reasons
        complexity_info['total_score'] = score
    
    def check_pdf_features(self, raw_settings: str) -> bool:
        """Check for PDF generation features."""
        pdf_patterns = [
            r'_pdf.*generate.*true',
            r'pdf_.*enabled',
            r'generate_pdf',
            r'pdf_attachment'
        ]
        return any(re.search(pattern, raw_settings, re.IGNORECASE) for pattern in pdf_patterns)
    
    def check_listings_features(self, raw_settings: str) -> bool:
        """Check for listings functionality."""
        listing_patterns = [
            r'_listings.*enabled.*true',
            r'listing_enabled',
            r'display_entries',
            r'entry_listing'
        ]
        return any(re.search(pattern, raw_settings, re.IGNORECASE) for pattern in listing_patterns)
    
    def check_woocommerce_features(self, raw_settings: str) -> bool:
        """Check for WooCommerce integration."""
        wc_patterns = [
            r'woocommerce_checkout',
            r'woocommerce_.*enabled',
            r'wc_integration',
            r'woocommerce_products'
        ]
        return any(re.search(pattern, raw_settings, re.IGNORECASE) for pattern in wc_patterns)
    
    def check_paypal_features(self, raw_settings: str) -> bool:
        """Check for PayPal integration."""
        paypal_patterns = [
            r'paypal_checkout',
            r'paypal_merchant_email',
            r'paypal_.*enabled',
            r'paypal_integration'
        ]
        return any(re.search(pattern, raw_settings, re.IGNORECASE) for pattern in paypal_patterns)
    
    def check_translation_features(self, raw_settings: str) -> bool:
        """Check for translation/internationalization features."""
        i18n_patterns = [
            r'i18n_switch.*true',
            r'language_.*enabled',
            r'multilingual',
            r'translation_.*enabled'
        ]
        return any(re.search(pattern, raw_settings, re.IGNORECASE) for pattern in i18n_patterns)
    
    def check_file_uploads(self, raw_elements: str) -> int:
        """Count file upload fields."""
        file_patterns = [
            r'"tag";s:4:"file"',
            r's:3:"tag";s:4:"file"'
        ]
        count = 0
        for pattern in file_patterns:
            count += len(re.findall(pattern, raw_elements))
        return count
    
    def check_conditional_logic(self, raw_elements: str) -> int:
        """Count conditional logic rules."""
        conditional_patterns = [
            r'conditional_items',
            r'conditional_action',
            r'conditional_trigger'
        ]
        count = 0
        for pattern in conditional_patterns:
            count += len(re.findall(pattern, raw_elements))
        return count // 3  # Divide by 3 as each conditional has 3 parts
    
    def check_email_templates(self, raw_settings: str) -> bool:
        """Check for custom email templates."""
        email_patterns = [
            r'email_template_.*_title',
            r'confirm_body',
            r'email_body',
            r'custom_email_template'
        ]
        return any(re.search(pattern, raw_settings, re.IGNORECASE) for pattern in email_patterns)
    
    def check_column_layout(self, raw_elements: str) -> int:
        """Count column layout elements."""
        column_patterns = [
            r'"tag";s:6:"column"',
            r's:3:"tag";s:6:"column"'
        ]
        count = 0
        for pattern in column_patterns:
            count += len(re.findall(pattern, raw_elements))
        return count
    
    def check_validations(self, raw_elements: str) -> int:
        """Count validation rules."""
        validation_patterns = [
            r'"validation";s:\d+:"(\w+)"',
            r's:10:"validation";s:\d+:"(\w+)"'
        ]
        count = 0
        for pattern in validation_patterns:
            matches = re.findall(pattern, raw_elements)
            # Filter out 'none' and 'empty' as basic validations
            count += len([m for m in matches if m not in ['none', 'empty', '']])
        return count
    
    def check_calculator_features(self, raw_elements: str, raw_settings: str) -> bool:
        """Check for calculator functionality."""
        calc_patterns = [
            r'calculator',
            r'calculation',
            r'math_.*field',
            r'dynamic_.*calculation'
        ]
        return any(re.search(pattern, raw_elements + raw_settings, re.IGNORECASE) for pattern in calc_patterns)
    
    def check_signature_fields(self, raw_elements: str) -> int:
        """Count signature fields."""
        sig_patterns = [
            r'"tag";s:9:"signature"',
            r's:3:"tag";s:9:"signature"'
        ]
        count = 0
        for pattern in sig_patterns:
            count += len(re.findall(pattern, raw_elements))
        return count
    
    def check_other_integrations(self, raw_settings: str) -> List[str]:
        """Check for other third-party integrations."""
        integrations = []
        integration_patterns = {
            'Mailchimp': r'mailchimp.*enabled',
            'Zapier': r'zapier.*enabled',
            'Mailster': r'mailster.*enabled',
            'CSV Export': r'csv_attachment.*enable',
            'Popup': r'popup_enabled',
            'Register/Login': r'register_login',
            'Frontend Posting': r'frontend_posting'
        }
        
        for integration, pattern in integration_patterns.items():
            if re.search(pattern, raw_settings, re.IGNORECASE):
                integrations.append(integration)
        
        return integrations

def main():
    """Main execution function."""
    forms_dir = '/projects/super-forms/test/exports/original'
    analyzer = FormComplexityAnalyzer(forms_dir)
    
    print("Analyzing Super Forms JSON files for complexity...")
    print("=" * 60)
    
    top_complex_forms = analyzer.analyze_all_forms()
    
    print(f"\nTOP 20 MOST COMPLEX SUPER FORMS FOR TESTING:")
    print("=" * 60)
    
    for i, form in enumerate(top_complex_forms, 1):
        print(f"\n{i}. Form #{form['form_id']} - \"{form['title']}\"")
        print(f"   File: {form['filename']}")
        print(f"   Elements: {form['element_count']}")
        print(f"   Complexity Score: {form['total_score']}")
        print(f"   Features: {', '.join(form['features']) if form['features'] else 'Basic form'}")
        print(f"   Reasons: {'; '.join(form['complexity_reasons'])}")
    
    print(f"\n\nANALYSIS SUMMARY:")
    print("=" * 60)
    print(f"Total forms analyzed: {len(analyzer.complexity_scores)}")
    print(f"Average complexity score: {sum(f['total_score'] for f in analyzer.complexity_scores) / len(analyzer.complexity_scores):.1f}")
    print(f"Highest complexity score: {max(f['total_score'] for f in analyzer.complexity_scores)}")
    print(f"Forms with advanced features: {len([f for f in analyzer.complexity_scores if f['total_score'] > 30])}")

if __name__ == "__main__":
    main()