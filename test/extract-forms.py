#!/usr/bin/env python3
"""
Extract live Super Forms from WordPress XML export
Only extracts forms with 'publish' status
"""

import xml.etree.ElementTree as ET
import json
import os
import re
from urllib.parse import unquote

def extract_forms_from_xml(xml_file_path):
    """Extract Super Forms from WordPress XML export"""
    
    print(f"Parsing XML file: {xml_file_path}")
    
    # Parse the XML file
    try:
        tree = ET.parse(xml_file_path)
        root = tree.getroot()
    except Exception as e:
        print(f"Error parsing XML: {e}")
        return []
    
    # Find all items (posts)
    items = root.findall('.//item')
    forms = []
    
    for item in items:
        # Check if this is a Super Form with publish status
        post_type = item.find('.//wp:post_type', namespaces={'wp': 'http://wordpress.org/export/1.2/'})
        post_status = item.find('.//wp:status', namespaces={'wp': 'http://wordpress.org/export/1.2/'})
        
        if (post_type is not None and post_type.text == 'super_form' and 
            post_status is not None and post_status.text == 'publish'):
            
            # Extract form data
            form_data = {
                'id': None,
                'title': '',
                'content': '',
                'date': '',
                'author': '',
                'settings': {},
                'elements': []
            }
            
            # Basic post data
            form_data['id'] = item.find('.//wp:post_id', namespaces={'wp': 'http://wordpress.org/export/1.2/'}).text
            form_data['title'] = item.find('.//title').text if item.find('.//title') is not None else ''
            form_data['content'] = item.find('.//content:encoded', namespaces={'content': 'http://purl.org/rss/1.0/modules/content/'}).text if item.find('.//content:encoded', namespaces={'content': 'http://purl.org/rss/1.0/modules/content/'}) is not None else ''
            form_data['date'] = item.find('.//wp:post_date', namespaces={'wp': 'http://wordpress.org/export/1.2/'}).text if item.find('.//wp:post_date', namespaces={'wp': 'http://wordpress.org/export/1.2/'}) is not None else ''
            form_data['author'] = item.find('.//dc:creator', namespaces={'dc': 'http://purl.org/dc/elements/1.1/'}).text if item.find('.//dc:creator', namespaces={'dc': 'http://purl.org/dc/elements/1.1/'}) is not None else ''
            
            # Extract postmeta
            postmeta_items = item.findall('.//wp:postmeta', namespaces={'wp': 'http://wordpress.org/export/1.2/'})
            
            for meta in postmeta_items:
                meta_key = meta.find('.//wp:meta_key', namespaces={'wp': 'http://wordpress.org/export/1.2/'})
                meta_value = meta.find('.//wp:meta_value', namespaces={'wp': 'http://wordpress.org/export/1.2/'})
                
                if meta_key is not None and meta_value is not None:
                    key = meta_key.text
                    value = meta_value.text
                    
                    if key == '_super_form_settings':
                        # Parse PHP serialized settings
                        try:
                            # For now, store as raw string - we'll handle PHP deserialization later
                            form_data['settings'] = value
                        except Exception as e:
                            print(f"Error parsing settings for form {form_data['id']}: {e}")
                            form_data['settings'] = value
                    
                    elif key == '_super_elements':
                        # Parse PHP serialized elements
                        try:
                            if value:
                                # Store as raw PHP serialized string for now
                                # We'll need to unserialize this in PHP when importing
                                form_data['elements'] = value
                            else:
                                form_data['elements'] = []
                        except Exception as e:
                            print(f"Error handling elements for form {form_data['id']}: {e}")
                            form_data['elements'] = []
                    elif key == '_super_version':
                        # Include form version for proper migration handling
                        form_data['version'] = value
            
            forms.append(form_data)
            print(f"Extracted form: {form_data['id']} - {form_data['title']}")
    
    return forms

def save_forms_to_files(forms, output_dir):
    """Save each form to individual JSON files"""
    
    if not os.path.exists(output_dir):
        os.makedirs(output_dir)
    
    for form in forms:
        filename = f"form_{form['id']}.json"
        filepath = os.path.join(output_dir, filename)
        
        with open(filepath, 'w', encoding='utf-8') as f:
            json.dump(form, f, indent=2, ensure_ascii=False)
        
        print(f"Saved: {filepath}")

def main():
    xml_file = "/mnt/g/Downloads/superforms.WordPress.2025-07-10.xml"
    output_dir = "/projects/super-forms/test/exports/original"
    
    print("=== Super Forms Extraction Tool ===")
    print(f"XML file: {xml_file}")
    print(f"Output directory: {output_dir}")
    print()
    
    # Extract forms
    forms = extract_forms_from_xml(xml_file)
    
    print(f"\nFound {len(forms)} live Super Forms")
    
    # Save to individual files
    save_forms_to_files(forms, output_dir)
    
    print(f"\nExtraction complete! {len(forms)} forms saved to {output_dir}")
    
    # Summary
    print("\n=== Summary ===")
    print(f"Total forms extracted: {len(forms)}")
    print(f"Forms with elements: {sum(1 for f in forms if f['elements'])}")
    print(f"Forms with settings: {sum(1 for f in forms if f['settings'])}")

if __name__ == "__main__":
    main()