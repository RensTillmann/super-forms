#!/usr/bin/env python3
"""
Refined Super Forms Complexity Analyzer
Focuses on actually enabled features rather than just configured ones.
"""

import json
import os
import re
from collections import defaultdict
from typing import Dict, List, Tuple, Any

class RefinedFormComplexityAnalyzer:
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
        settings = form_data.get('settings', '')
        elements = form_data.get('elements', '')
        
        complexity_info = {
            'form_id': form_id,
            'filename': filename,
            'title': title,
            'element_count': 0,
            'features': [],
            'complexity_reasons': [],
            'total_score': 0,
            'raw_settings': settings,
            'raw_elements': elements
        }
        
        # Count elements
        element_count = self.count_elements(elements)
        complexity_info['element_count'] = element_count
        
        # Analyze features and calculate complexity
        self.analyze_features(settings, elements, complexity_info)
        
        return complexity_info
    
    def count_elements(self, elements_str: str) -> int:
        """Count the total number of form elements."""
        if not elements_str:
            return 0
            
        # Count elements in serialized data - look for field tags
        element_patterns = [
            r'"tag";s:\d+:"(\w+)"',  # Match tag definitions
            r's:3:"tag";s:\d+:"(\w+)"'  # Alternative format
        ]
        
        total_elements = 0
        for pattern in element_patterns:
            matches = re.findall(pattern, elements_str)
            # Filter out layout elements like 'column'
            field_elements = [m for m in matches if m not in ['column', 'spacer', 'divider']]
            total_elements += len(field_elements)
        
        return total_elements
    
    def analyze_features(self, settings: str, elements: str, complexity_info: Dict[str, Any]):
        """Analyze features and calculate complexity score."""
        score = 0
        features = []
        reasons = []
        
        # High Priority Features (20 points each) - Check if actually enabled
        
        # PDF Generator - check if generate is "true"
        if self.check_pdf_enabled(settings):
            score += 20
            features.append('PDF Generator')
            reasons.append('Has active PDF generation')
        
        # Listings - check if enabled is "true"
        if self.check_listings_enabled(settings):
            score += 20
            features.append('Listings')
            reasons.append('Has active listings functionality')
        
        # WooCommerce - check if checkout is enabled
        if self.check_woocommerce_enabled(settings):
            score += 20
            features.append('WooCommerce')
            reasons.append('WooCommerce integration is active')
        
        # PayPal - check if checkout is enabled
        if self.check_paypal_enabled(settings):
            score += 20
            features.append('PayPal')
            reasons.append('PayPal payment integration is active')
        
        # Translations/Internationalization
        if self.check_translations_enabled(settings):
            score += 20
            features.append('Translations')
            reasons.append('Multiple language support enabled')
        
        # File Uploads
        file_upload_count = self.check_file_uploads(elements)
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
        conditional_count = self.check_conditional_logic(elements)
        if conditional_count > 5:
            score += 15
            features.append('Complex Conditional Logic')
            reasons.append(f'Has {conditional_count} conditional logic rules')
        elif conditional_count > 0:
            score += 10
            features.append('Conditional Logic')
            reasons.append(f'Has {conditional_count} conditional logic rules')
        
        # Email Templates (custom)
        if self.check_custom_email_templates(settings):
            score += 10
            features.append('Email Templates')
            reasons.append('Custom email templates configured')
        
        # Multi-part/Column Layout
        column_count = self.check_column_layout(elements)
        if column_count > 5:
            score += 10
            features.append('Complex Layout')
            reasons.append(f'Multi-column layout with {column_count} columns')
        elif column_count > 2:
            score += 5
            features.append('Column Layout')
            reasons.append(f'Has {column_count} column layout')
        
        # Advanced Validations
        validation_count = self.check_advanced_validations(elements)
        if validation_count > 5:
            score += 10
            features.append('Advanced Validations')
            reasons.append(f'Has {validation_count} advanced validation rules')
        elif validation_count > 0:
            score += 5
            features.append('Validations')
            reasons.append(f'Has {validation_count} validation rules')
        
        # Calculator functionality
        if self.check_calculator_features(elements, settings):
            score += 15
            features.append('Calculator')
            reasons.append('Has calculation features')
        
        # Signature fields
        signature_count = self.check_signature_fields(elements)
        if signature_count > 0:
            score += 10
            features.append(f'Signatures ({signature_count})')
            reasons.append(f'Has {signature_count} signature fields')
        
        # Special form elements
        special_elements = self.check_special_elements(elements)
        if special_elements:
            score += len(special_elements) * 3
            features.extend(special_elements)
            reasons.append(f'Special elements: {", ".join(special_elements)}')
        
        # Other active integrations
        other_integrations = self.check_active_integrations(settings)
        if other_integrations:
            score += len(other_integrations) * 5
            features.extend(other_integrations)
            reasons.append(f'Active integrations: {", ".join(other_integrations)}')
        
        complexity_info['features'] = features
        complexity_info['complexity_reasons'] = reasons
        complexity_info['total_score'] = score
    
    def check_pdf_enabled(self, raw_settings: str) -> bool:
        """Check if PDF generation is actually enabled."""
        # Look for _pdf settings with generate set to true
        pdf_patterns = [
            r'_pdf.*generate";s:4:"true"',
            r'pdf_generate.*true',
        ]
        return any(re.search(pattern, raw_settings, re.IGNORECASE) for pattern in pdf_patterns)
    
    def check_listings_enabled(self, raw_settings: str) -> bool:
        """Check if listings functionality is actually enabled."""
        listing_patterns = [
            r'_listings.*enabled";s:4:"true"',
            r'listings_enabled.*true',
        ]
        return any(re.search(pattern, raw_settings, re.IGNORECASE) for pattern in listing_patterns)
    
    def check_woocommerce_enabled(self, raw_settings: str) -> bool:
        """Check if WooCommerce integration is actually enabled."""
        # Look for actual WooCommerce checkout being enabled (not empty)
        wc_patterns = [
            r'woocommerce_checkout";s:[1-9]\d*:"',  # Non-empty value
            r'woocommerce.*enabled";s:4:"true"',
        ]
        return any(re.search(pattern, raw_settings, re.IGNORECASE) for pattern in wc_patterns)
    
    def check_paypal_enabled(self, raw_settings: str) -> bool:
        """Check if PayPal integration is actually enabled."""
        # Look for actual PayPal checkout being enabled (not empty)
        paypal_patterns = [
            r'paypal_checkout";s:[1-9]\d*:"',  # Non-empty value
            r'paypal_merchant_email";s:[1-9]\d*:"',  # Has merchant email
        ]
        return any(re.search(pattern, raw_settings, re.IGNORECASE) for pattern in paypal_patterns)
    
    def check_translations_enabled(self, raw_settings: str) -> bool:
        """Check if translation/internationalization is actually enabled."""
        i18n_patterns = [
            r'i18n_switch";s:4:"true"',
            r'language.*enabled";s:4:"true"',
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
        """Count meaningful conditional logic rules."""
        # Look for conditional_items with actual content
        conditional_pattern = r'conditional_items";a:(\d+):'
        matches = re.findall(conditional_pattern, raw_elements)
        
        total_conditions = 0
        for match in matches:
            count = int(match)
            if count > 0:  # Only count if there are actual conditional items
                total_conditions += count
        
        return total_conditions
    
    def check_custom_email_templates(self, raw_settings: str) -> bool:
        """Check for custom email templates with actual content."""
        email_patterns = [
            r'email_body";s:[1-9]\d*:"',  # Non-empty email body
            r'confirm_body";s:[1-9]\d*:"',  # Non-empty confirm body
            r'email_template.*title";s:[1-9]\d*:"',  # Custom email template title
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
    
    def check_advanced_validations(self, raw_elements: str) -> int:
        """Count advanced validation rules (excluding basic ones)."""
        validation_patterns = [
            r'"validation";s:\d+:"(\w+)"',
            r's:10:"validation";s:\d+:"(\w+)"'
        ]
        count = 0
        for pattern in validation_patterns:
            matches = re.findall(pattern, raw_elements)
            # Only count advanced validations
            advanced_validations = [m for m in matches if m not in ['none', 'empty', ''] and len(m) > 0]
            count += len(advanced_validations)
        return count
    
    def check_calculator_features(self, raw_elements: str, raw_settings: str) -> bool:
        """Check for calculator functionality."""
        calc_patterns = [
            r'"tag";s:10:"calculator"',
            r's:3:"tag";s:10:"calculator"',
            r'calculation.*formula',
            r'dynamic.*calculation'
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
    
    def check_special_elements(self, raw_elements: str) -> List[str]:
        """Check for special form elements."""
        special_elements = []
        element_patterns = {
            'Rating': r'"tag";s:6:"rating"',
            'Slider': r'"tag";s:6:"slider"',
            'Toggle': r'"tag";s:6:"toggle"',
            'Date Picker': r'"tag";s:4:"date"',
            'Time Picker': r'"tag";s:4:"time"',
            'Color Picker': r'"tag";s:5:"color"',
            'Image': r'"tag";s:5:"image"',
            'Gallery': r'"tag";s:7:"gallery"',
            'Map': r'"tag";s:3:"map"',
            'QR Code': r'"tag";s:7:"qr_code"',
            'Barcode': r'"tag";s:7:"barcode"'
        }
        
        for element_name, pattern in element_patterns.items():
            if re.search(pattern, raw_elements, re.IGNORECASE):
                special_elements.append(element_name)
        
        return special_elements
    
    def check_active_integrations(self, raw_settings: str) -> List[str]:
        """Check for actually enabled third-party integrations."""
        integrations = []
        integration_patterns = {
            'Mailchimp': r'mailchimp.*enabled";s:4:"true"',
            'Zapier': r'zapier.*enable";s:[1-9]\d*:"',  # Non-empty value
            'Mailster': r'mailster.*enabled";s:4:"true"',
            'CSV Export': r'csv_attachment.*enable";s:[1-9]\d*:"',  # Non-empty value
            'Popup': r'popup_enabled";s:4:"true"',
            'Register/Login': r'register_login.*enabled";s:4:"true"',
            'Frontend Posting': r'frontend_posting.*enabled";s:4:"true"'
        }
        
        for integration, pattern in integration_patterns.items():
            if re.search(pattern, raw_settings, re.IGNORECASE):
                integrations.append(integration)
        
        return integrations

def main():
    """Main execution function."""
    forms_dir = '/projects/super-forms/test/exports/original'
    analyzer = RefinedFormComplexityAnalyzer(forms_dir)
    
    print("Analyzing Super Forms JSON files for ACTUAL complexity...")
    print("=" * 60)
    
    top_complex_forms = analyzer.analyze_all_forms()
    
    print(f"\nTOP 20 MOST COMPLEX SUPER FORMS FOR TESTING (REFINED):")
    print("=" * 60)
    
    for i, form in enumerate(top_complex_forms, 1):
        print(f"\n{i}. Form #{form['form_id']} - \"{form['title']}\"")
        print(f"   File: {form['filename']}")
        print(f"   Elements: {form['element_count']}")
        print(f"   Complexity Score: {form['total_score']}")
        print(f"   Features: {', '.join(form['features']) if form['features'] else 'Basic form'}")
        print(f"   Reasons: {'; '.join(form['complexity_reasons'])}")
    
    print(f"\n\nREFINED ANALYSIS SUMMARY:")
    print("=" * 60)
    print(f"Total forms analyzed: {len(analyzer.complexity_scores)}")
    print(f"Average complexity score: {sum(f['total_score'] for f in analyzer.complexity_scores) / len(analyzer.complexity_scores):.1f}")
    print(f"Highest complexity score: {max(f['total_score'] for f in analyzer.complexity_scores)}")
    print(f"Forms with advanced features: {len([f for f in analyzer.complexity_scores if f['total_score'] > 30])}")
    
    # Show distribution
    score_ranges = {
        '0-20': len([f for f in analyzer.complexity_scores if 0 <= f['total_score'] <= 20]),
        '21-40': len([f for f in analyzer.complexity_scores if 21 <= f['total_score'] <= 40]),
        '41-60': len([f for f in analyzer.complexity_scores if 41 <= f['total_score'] <= 60]),
        '61-80': len([f for f in analyzer.complexity_scores if 61 <= f['total_score'] <= 80]),
        '81+': len([f for f in analyzer.complexity_scores if f['total_score'] > 80])
    }
    
    print(f"\nComplexity Score Distribution:")
    for range_name, count in score_ranges.items():
        print(f"  {range_name}: {count} forms")

if __name__ == "__main__":
    main()