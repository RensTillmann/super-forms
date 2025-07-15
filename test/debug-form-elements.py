#!/usr/bin/env python3
"""
Debug form elements extraction for a specific form
"""

import xml.etree.ElementTree as ET
import json
import os

def debug_form_elements(xml_file_path, form_id):
    """Debug elements extraction for a specific form"""
    
    print(f"Debugging form {form_id} in XML file: {xml_file_path}")
    
    # Parse the XML file
    try:
        tree = ET.parse(xml_file_path)
        root = tree.getroot()
    except Exception as e:
        print(f"Error parsing XML: {e}")
        return
    
    # Find all items (posts)
    items = root.findall('.//item')
    
    for item in items:
        # Check if this is the specific Super Form
        post_id = item.find('.//wp:post_id', namespaces={'wp': 'http://wordpress.org/export/1.2/'})
        post_type = item.find('.//wp:post_type', namespaces={'wp': 'http://wordpress.org/export/1.2/'})
        
        if (post_id is not None and post_id.text == form_id and
            post_type is not None and post_type.text == 'super_form'):
            
            print(f"Found form {form_id}")
            
            # Get basic info
            title = item.find('.//title')
            print(f"Title: {title.text if title is not None else 'N/A'}")
            
            # Extract all postmeta to see what's available
            postmeta_items = item.findall('.//wp:postmeta', namespaces={'wp': 'http://wordpress.org/export/1.2/'})
            
            print(f"Found {len(postmeta_items)} postmeta entries:")
            
            elements_found = False
            settings_found = False
            
            for meta in postmeta_items:
                meta_key = meta.find('.//wp:meta_key', namespaces={'wp': 'http://wordpress.org/export/1.2/'})
                meta_value = meta.find('.//wp:meta_value', namespaces={'wp': 'http://wordpress.org/export/1.2/'})
                
                if meta_key is not None:
                    key = meta_key.text
                    value = meta_value.text if meta_value is not None else ''
                    
                    if key == '_super_elements':
                        elements_found = True
                        print(f"\n=== _super_elements ===")
                        print(f"Length: {len(value) if value else 0}")
                        print(f"Value: {value[:200]}..." if value and len(value) > 200 else f"Value: {value}")
                        
                        # Try to parse as JSON
                        if value:
                            try:
                                elements = json.loads(value)
                                print(f"Parsed JSON elements: {len(elements)} items")
                                if elements:
                                    print(f"First element: {elements[0] if elements else 'None'}")
                            except Exception as e:
                                print(f"Failed to parse JSON: {e}")
                        else:
                            print("Elements value is empty!")
                    
                    elif key == '_super_form_settings':
                        settings_found = True
                        print(f"\n=== _super_form_settings ===")
                        print(f"Length: {len(value) if value else 0}")
                        print(f"Value: {value[:100]}..." if value and len(value) > 100 else f"Value: {value}")
                    
                    elif key.startswith('_super'):
                        print(f"  {key}: {len(value) if value else 0} chars")
            
            if not elements_found:
                print("\n❌ _super_elements meta key not found!")
            if not settings_found:
                print("\n❌ _super_form_settings meta key not found!")
            
            return
    
    print(f"❌ Form {form_id} not found in XML")

def main():
    xml_file = "/mnt/g/Downloads/superforms.WordPress.2025-07-10.xml"
    form_id = "71954"  # The form you mentioned
    
    if not os.path.exists(xml_file):
        print(f"❌ XML file not found: {xml_file}")
        return
    
    debug_form_elements(xml_file, form_id)

if __name__ == "__main__":
    main()