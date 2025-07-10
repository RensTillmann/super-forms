#!/usr/bin/env python3
"""
Claude Code Command Logger
Logs all Claude commands, hook executions, and development activities
"""

import json
import sys
import os
from datetime import datetime
from pathlib import Path

class ClaudeLogger:
    def __init__(self, project_root="/projects/super-forms"):
        self.project_root = Path(project_root)
        self.log_dir = self.project_root / ".claude" / "logs"
        self.log_dir.mkdir(parents=True, exist_ok=True)
        
        # Different log files for different types of activities
        self.log_files = {
            'commands': self.log_dir / "claude-commands.log",
            'hooks': self.log_dir / "hook-executions.log",
            'security': self.log_dir / "security-checks.log",
            'phpcs': self.log_dir / "phpcs-validations.log",
            'sync': self.log_dir / "sync-operations.log",
            'session': self.log_dir / "session-activity.log"
        }
    
    def log_event(self, event_type, data):
        """Log an event with timestamp and context"""
        timestamp = datetime.now().isoformat()
        
        log_entry = {
            'timestamp': timestamp,
            'event_type': event_type,
            'session_id': data.get('session_id', 'unknown'),
            'data': data
        }
        
        # Determine which log file to use
        log_file = self.log_files.get(event_type, self.log_files['session'])
        
        # Append to log file
        with open(log_file, 'a', encoding='utf-8') as f:
            f.write(json.dumps(log_entry) + '\n')
        
        # Also log to main session file for comprehensive tracking
        if event_type != 'session':
            with open(self.log_files['session'], 'a', encoding='utf-8') as f:
                f.write(json.dumps(log_entry) + '\n')
    
    def log_hook_execution(self, hook_data):
        """Log hook execution details"""
        self.log_event('hooks', {
            'hook_event': hook_data.get('hook_event_name'),
            'tool_name': hook_data.get('tool_name'),
            'tool_input': hook_data.get('tool_input', {}),
            'success': True,
            'message': f"Hook executed for {hook_data.get('tool_name', 'unknown')} tool"
        })
    
    def log_security_check(self, file_path, issues, tool_name="security"):
        """Log security validation results"""
        self.log_event('security', {
            'file_path': file_path,
            'tool_name': tool_name,
            'issues_found': len(issues),
            'issues': issues,
            'status': 'warning' if issues else 'clean'
        })
    
    def log_phpcs_check(self, files_checked, violations_found, auto_fixed=False):
        """Log PHPCS validation results"""
        self.log_event('phpcs', {
            'files_checked': files_checked,
            'violations_found': violations_found,
            'auto_fixed': auto_fixed,
            'status': 'violations' if violations_found > 0 else 'clean'
        })
    
    def log_sync_operation(self, sync_script, success=True, message=""):
        """Log sync to webserver operations"""
        self.log_event('sync', {
            'sync_script': sync_script,
            'success': success,
            'message': message,
            'status': 'success' if success else 'failed'
        })
    
    def log_command(self, command, description, success=True, output=""):
        """Log general Claude commands"""
        self.log_event('commands', {
            'command': command,
            'description': description,
            'success': success,
            'output': output[:500] if len(output) > 500 else output  # Truncate long output
        })

def main():
    """Main entry point for CLI usage"""
    if len(sys.argv) < 3:
        print("Usage: python3 claude-logger.py <event_type> <data_json>")
        sys.exit(1)
    
    event_type = sys.argv[1]
    data_json = sys.argv[2]
    
    try:
        data = json.loads(data_json)
        logger = ClaudeLogger()
        logger.log_event(event_type, data)
        print(f"Logged {event_type} event successfully")
    except json.JSONDecodeError as e:
        print(f"Error parsing JSON data: {e}", file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f"Error logging event: {e}", file=sys.stderr)
        sys.exit(1)

if __name__ == '__main__':
    main()