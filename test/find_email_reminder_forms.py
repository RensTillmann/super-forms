#!/usr/bin/env python3
"""
Find Super Forms with Email Reminder configurations.
Searches through all form exports to find forms with actual email reminder settings.
"""

import json
import os
import re
import urllib.parse

def parse_php_serialized_simple(data_str):
    """
    Simple parser for PHP serialized data to extract email reminder settings.
    """
    if not data_str:
        return {}
    
    # Extract email reminder settings using regex
    email_reminders = {}
    
    # Pattern to match email_reminder_X settings
    # Look for patterns like s:16:"email_reminder_1";s:X:"value"
    pattern = r's:\d+:"(email_reminder_[1-3][^"]*?)";s:(\d+):"([^"]*?)"'
    matches = re.findall(pattern, data_str)
    
    for key, length, value in matches:
        # Only include settings with actual content
        if int(length) > 0 and value.strip():
            email_reminders[key] = value
    
    return email_reminders

def analyze_form_for_email_reminders(form_file):
    """
    Analyze a single form file for email reminder configurations.
    """
    try:
        with open(form_file, 'r', encoding='utf-8') as f:
            form_data = json.load(f)
        
        form_id = form_data.get('id', 'unknown')
        title = form_data.get('title', 'Unknown Title')
        settings = form_data.get('settings', '')
        
        # Parse email reminder settings
        email_reminders = parse_php_serialized_simple(settings)
        
        if email_reminders:
            return {
                'form_id': form_id,
                'filename': os.path.basename(form_file),
                'title': title,
                'email_reminders': email_reminders
            }
    except Exception as e:
        print(f"Error analyzing {form_file}: {e}")
    
    return None

def find_all_email_reminder_forms(forms_directory):
    """
    Find all forms with email reminder configurations.
    """
    print(f"Searching for email reminder forms in: {forms_directory}")
    
    form_files = []
    for filename in os.listdir(forms_directory):
        if filename.startswith('form_') and filename.endswith('.json'):
            filepath = os.path.join(forms_directory, filename)
            form_files.append(filepath)
    
    print(f"Found {len(form_files)} form files to analyze")
    
    forms_with_reminders = []
    
    for form_file in form_files:
        result = analyze_form_for_email_reminders(form_file)
        if result:
            forms_with_reminders.append(result)
    
    return forms_with_reminders

def main():
    forms_dir = '/projects/super-forms/test/exports/original'
    
    print("=" * 60)
    print("SUPER FORMS EMAIL REMINDER ANALYSIS")
    print("=" * 60)
    
    forms_with_reminders = find_all_email_reminder_forms(forms_dir)
    
    if not forms_with_reminders:
        print("No forms found with actual email reminder configurations.")
        print("\nNote: Many forms have email_reminder_1, email_reminder_2, email_reminder_3")
        print("fields defined but with empty values (s:0:\"\").")
        print("\nTrying alternative search for any email reminder mention...")
        
        # Alternative search - look for any mention of email reminders
        alt_forms = []
        for filename in os.listdir(forms_dir):
            if filename.startswith('form_') and filename.endswith('.json'):
                filepath = os.path.join(forms_dir, filename)
                try:
                    with open(filepath, 'r') as f:
                        content = f.read()
                    
                    # Look for forms that might have email reminder add-on structure
                    if 'email_reminder' in content and ('base_date' in content or 'date_offset' in content):
                        with open(filepath, 'r') as f:
                            form_data = json.load(f)
                        
                        form_id = form_data.get('id', 'unknown')
                        title = form_data.get('title', 'Unknown Title')
                        
                        # Count email reminder related settings
                        reminder_count = content.count('email_reminder_')
                        
                        alt_forms.append({
                            'form_id': form_id,
                            'filename': filename,
                            'title': title,
                            'reminder_mentions': reminder_count
                        })
                        
                except Exception as e:
                    print(f"Error reading {filename}: {e}")
        
        if alt_forms:
            print(f"\nFound {len(alt_forms)} forms with email reminder structure:")
            for form in sorted(alt_forms, key=lambda x: x['reminder_mentions'], reverse=True)[:10]:
                print(f"  Form {form['form_id']} ({form['filename']}) - {form['title']}")
                print(f"    Reminder mentions: {form['reminder_mentions']}")
        
    else:
        print(f"Found {len(forms_with_reminders)} forms with email reminder configurations:")
        print()
        
        for form in forms_with_reminders:
            print(f"Form ID: {form['form_id']} ({form['filename']})")
            print(f"Title: {form['title']}")
            print(f"Email Reminder Settings:")
            
            for key, value in form['email_reminders'].items():
                # Truncate long values for display
                display_value = value[:100] + "..." if len(value) > 100 else value
                print(f"  {key}: {display_value}")
            
            print("-" * 40)

if __name__ == "__main__":
    main()