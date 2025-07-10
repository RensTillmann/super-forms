#!/usr/bin/env python3
"""
WordPress Security Validation Script for Super Forms Plugin
Checks for common WordPress security issues and best practices.
"""

import os
import re
import sys
import json
import argparse
from pathlib import Path
from typing import List, Dict, Tuple, Optional

class WordPressSecurityChecker:
    """WordPress security validation checker"""
    
    def __init__(self, plugin_dir: str = "."):
        self.plugin_dir = Path(plugin_dir)
        self.issues = []
        self.warnings = []
        self.info = []
        
        # Security patterns to check
        self.security_patterns = {
            'nonce_missing': {
                'pattern': r'\$_(POST|GET|REQUEST)',
                'required': r'wp_verify_nonce|wp_nonce_field|check_ajax_referer',
                'message': 'Missing nonce verification for form submission'
            },
            'input_not_sanitized': {
                'pattern': r'\$_(POST|GET|REQUEST)\[',
                'required': r'sanitize_|esc_|wp_kses|intval|floatval|absint',
                'message': 'Input not properly sanitized'
            },
            'output_not_escaped': {
                'pattern': r'echo\s+\$|print\s+\$',
                'required': r'esc_html|esc_attr|esc_url|wp_kses',
                'message': 'Output not properly escaped'
            },
            'capability_missing': {
                'pattern': r'(admin|manage|delete|edit)_',
                'required': r'current_user_can|is_admin|wp_die',
                'message': 'Missing capability check for admin functionality'
            },
            'direct_db_query': {
                'pattern': r'\$wpdb->query\(|mysql_query|mysqli_query',
                'required': r'prepare|%s|%d',
                'message': 'Direct database query without prepared statement'
            },
            'file_inclusion': {
                'pattern': r'include\s*\(|require\s*\(',
                'required': r'wp_verify_nonce|current_user_can|is_admin',
                'message': 'File inclusion without security check'
            }
        }
        
        # WordPress best practices
        self.wp_patterns = {
            'global_prefix': {
                'pattern': r'function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(',
                'check': lambda match: not any(match.group(1).startswith(prefix) for prefix in ['super_forms_', 'SUPER_', 'wp_', '__']),
                'message': 'Function should be prefixed with super_forms_ or SUPER_'
            },
            'text_domain': {
                'pattern': r'__\(["\']([^"\']+)["\'](?:,\s*["\']([^"\']*)["\'])?',
                'check': lambda match: match.group(2) != 'super-forms' if match.group(2) else True,
                'message': 'Text domain should be "super-forms"'
            },
            'deprecated_functions': {
                'pattern': r'\b(mysql_query|split|ereg|session_start|extract)\b',
                'message': 'Using deprecated or discouraged PHP functions'
            }
        }
    
    def scan_file(self, file_path: Path) -> None:
        """Scan a single PHP file for security issues"""
        try:
            with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
                
            # Check security patterns
            for check_name, pattern_info in self.security_patterns.items():
                self._check_security_pattern(file_path, content, check_name, pattern_info)
            
            # Check WordPress best practices
            for check_name, pattern_info in self.wp_patterns.items():
                self._check_wp_pattern(file_path, content, check_name, pattern_info)
                
        except Exception as e:
            self.warnings.append(f"Error scanning {file_path}: {str(e)}")
    
    def _check_security_pattern(self, file_path: Path, content: str, check_name: str, pattern_info: Dict) -> None:
        """Check for security pattern violations"""
        main_pattern = pattern_info['pattern']
        required_pattern = pattern_info.get('required', '')
        message = pattern_info['message']
        
        main_matches = re.findall(main_pattern, content, re.IGNORECASE)
        if main_matches and required_pattern:
            # Check if required security pattern is present
            if not re.search(required_pattern, content, re.IGNORECASE):
                self.issues.append({
                    'file': str(file_path),
                    'type': 'security',
                    'check': check_name,
                    'message': message,
                    'matches': len(main_matches)
                })
    
    def _check_wp_pattern(self, file_path: Path, content: str, check_name: str, pattern_info: Dict) -> None:
        """Check for WordPress best practice violations"""
        pattern = pattern_info['pattern']
        message = pattern_info['message']
        
        if 'check' in pattern_info:
            # Custom check function
            for match in re.finditer(pattern, content):
                if pattern_info['check'](match):
                    self.warnings.append({
                        'file': str(file_path),
                        'type': 'best_practice',
                        'check': check_name,
                        'message': message,
                        'line': content[:match.start()].count('\n') + 1,
                        'match': match.group(0)
                    })
        else:
            # Simple pattern match
            matches = re.findall(pattern, content, re.IGNORECASE)
            if matches:
                self.warnings.append({
                    'file': str(file_path),
                    'type': 'best_practice',
                    'check': check_name,
                    'message': message,
                    'matches': len(matches)
                })
    
    def scan_directory(self, directory: Optional[Path] = None) -> None:
        """Scan directory for PHP files"""
        if directory is None:
            directory = self.plugin_dir
            
        # Find all PHP files, excluding vendor and node_modules
        php_files = []
        for pattern in ['**/*.php']:
            for file_path in directory.glob(pattern):
                if not any(part in str(file_path) for part in ['vendor', 'node_modules', 'lib', 'build', 'dist']):
                    php_files.append(file_path)
        
        if not php_files:
            self.warnings.append("No PHP files found to scan")
            return
        
        self.info.append(f"Scanning {len(php_files)} PHP files...")
        
        for file_path in php_files:
            self.scan_file(file_path)
    
    def check_plugin_structure(self) -> None:
        """Check for proper WordPress plugin structure"""
        required_files = ['super-forms.php', 'readme.txt']
        
        for file_name in required_files:
            file_path = self.plugin_dir / file_name
            if not file_path.exists():
                self.warnings.append({
                    'file': str(file_path),
                    'type': 'structure',
                    'check': 'required_files',
                    'message': f'Required file {file_name} not found'
                })
        
        # Check for plugin header
        main_file = self.plugin_dir / 'super-forms.php'
        if main_file.exists():
            try:
                with open(main_file, 'r', encoding='utf-8') as f:
                    content = f.read()
                    if 'Plugin Name:' not in content:
                        self.warnings.append({
                            'file': str(main_file),
                            'type': 'structure',
                            'check': 'plugin_header',
                            'message': 'Plugin header missing from main file'
                        })
            except Exception as e:
                self.warnings.append(f"Error reading main plugin file: {str(e)}")
    
    def check_wp_config_exposure(self) -> None:
        """Check for wp-config.php exposure or hardcoded credentials"""
        for file_path in self.plugin_dir.glob('**/*.php'):
            if any(part in str(file_path) for part in ['vendor', 'node_modules']):
                continue
                
            try:
                with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read()
                    
                # Check for hardcoded credentials
                patterns = [
                    r'DB_PASSWORD["\']?\s*=\s*["\'][^"\']+["\']',
                    r'DB_USER["\']?\s*=\s*["\'][^"\']+["\']',
                    r'password["\']?\s*=\s*["\'][^"\']+["\']',
                    r'secret["\']?\s*=\s*["\'][^"\']+["\']'
                ]
                
                for pattern in patterns:
                    if re.search(pattern, content, re.IGNORECASE):
                        self.issues.append({
                            'file': str(file_path),
                            'type': 'security',
                            'check': 'hardcoded_credentials',
                            'message': 'Potential hardcoded credentials found'
                        })
                        break
                        
            except Exception as e:
                self.warnings.append(f"Error checking {file_path}: {str(e)}")
    
    def generate_report(self) -> Dict:
        """Generate comprehensive security report"""
        return {
            'summary': {
                'total_issues': len(self.issues),
                'total_warnings': len(self.warnings),
                'total_info': len(self.info),
                'scan_directory': str(self.plugin_dir)
            },
            'issues': self.issues,
            'warnings': self.warnings,
            'info': self.info
        }
    
    def print_report(self, verbose: bool = False) -> None:
        """Print formatted report to console"""
        print("🔍 WordPress Security Scan Report")
        print("=" * 50)
        
        if self.info:
            print(f"ℹ️  {len(self.info)} info messages")
            if verbose:
                for info in self.info:
                    print(f"   • {info}")
        
        if self.issues:
            print(f"❌ {len(self.issues)} security issues found")
            for issue in self.issues:
                print(f"   • {issue['file']}: {issue['message']}")
        else:
            print("✅ No critical security issues found")
        
        if self.warnings:
            print(f"⚠️  {len(self.warnings)} warnings")
            if verbose:
                for warning in self.warnings:
                    if isinstance(warning, dict):
                        print(f"   • {warning['file']}: {warning['message']}")
                    else:
                        print(f"   • {warning}")
        
        print("\nRecommendations:")
        print("- Review wp-plugin-guidelines.md for detailed security practices")
        print("- Run PHPCS with WordPress standards: composer run phpcs")
        print("- Use Plugin Check: wp plugin check super-forms")
        print("- Test all functionality after security fixes")

def main():
    """Main entry point"""
    parser = argparse.ArgumentParser(description='WordPress Security Checker for Super Forms')
    parser.add_argument('--dir', '-d', default='.', help='Directory to scan (default: current)')
    parser.add_argument('--verbose', '-v', action='store_true', help='Verbose output')
    parser.add_argument('--json', '-j', action='store_true', help='Output as JSON')
    parser.add_argument('--output', '-o', help='Output file for report')
    
    args = parser.parse_args()
    
    # Initialize checker
    checker = WordPressSecurityChecker(args.dir)
    
    # Run all checks
    checker.scan_directory()
    checker.check_plugin_structure()
    checker.check_wp_config_exposure()
    
    # Generate report
    report = checker.generate_report()
    
    if args.json:
        print(json.dumps(report, indent=2))
    else:
        checker.print_report(args.verbose)
    
    # Save report if requested
    if args.output:
        with open(args.output, 'w') as f:
            json.dump(report, f, indent=2)
        print(f"\nReport saved to {args.output}")
    
    # Exit with appropriate code
    sys.exit(1 if checker.issues else 0)

if __name__ == '__main__':
    main()