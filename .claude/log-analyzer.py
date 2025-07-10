#!/usr/bin/env python3
"""
Claude Code Log Analyzer
Analyzes Claude command logs and hook executions
"""

import json
import os
import sys
from datetime import datetime, timedelta
from pathlib import Path
from collections import defaultdict, Counter

class LogAnalyzer:
    def __init__(self, project_root="/projects/super-forms"):
        self.project_root = Path(project_root)
        self.log_dir = self.project_root / ".claude" / "logs"
        
    def read_log_file(self, log_type):
        """Read and parse a specific log file"""
        log_file = self.log_dir / f"{log_type}.log"
        if not log_file.exists():
            return []
        
        entries = []
        try:
            with open(log_file, 'r', encoding='utf-8') as f:
                for line in f:
                    if line.strip():
                        entries.append(json.loads(line.strip()))
        except Exception as e:
            print(f"Error reading {log_file}: {e}")
        
        return entries
    
    def get_recent_activity(self, hours=24):
        """Get recent activity from all logs"""
        cutoff_time = datetime.now() - timedelta(hours=hours)
        recent_activity = []
        
        # Read all log types
        log_types = ['claude-commands', 'hook-executions', 'security-checks', 
                    'phpcs-validations', 'sync-operations', 'session-activity']
        
        for log_type in log_types:
            entries = self.read_log_file(log_type)
            for entry in entries:
                try:
                    entry_time = datetime.fromisoformat(entry['timestamp'])
                    if entry_time > cutoff_time:
                        recent_activity.append(entry)
                except:
                    continue
        
        # Sort by timestamp
        recent_activity.sort(key=lambda x: x['timestamp'])
        return recent_activity
    
    def analyze_hook_performance(self):
        """Analyze hook execution performance and frequency"""
        hooks = self.read_log_file('hook-executions')
        
        hook_stats = defaultdict(lambda: {'count': 0, 'tools': set(), 'last_run': None})
        
        for entry in hooks:
            hook_event = entry['data'].get('hook_event', 'unknown')
            tool_name = entry['data'].get('tool_name', 'unknown')
            
            hook_stats[hook_event]['count'] += 1
            hook_stats[hook_event]['tools'].add(tool_name)
            hook_stats[hook_event]['last_run'] = entry['timestamp']
        
        return dict(hook_stats)
    
    def analyze_security_issues(self):
        """Analyze security check results"""
        security_logs = self.read_log_file('security-checks')
        
        issue_types = Counter()
        files_with_issues = set()
        total_checks = len(security_logs)
        
        for entry in security_logs:
            data = entry['data']
            issues = data.get('issues', [])
            
            if issues:
                files_with_issues.add(data.get('file_path', 'unknown'))
                for issue in issues:
                    issue_types[issue] += 1
        
        return {
            'total_checks': total_checks,
            'files_with_issues': len(files_with_issues),
            'issue_types': dict(issue_types),
            'clean_rate': ((total_checks - len(files_with_issues)) / total_checks * 100) if total_checks > 0 else 0
        }
    
    def analyze_phpcs_trends(self):
        """Analyze PHPCS validation trends"""
        phpcs_logs = self.read_log_file('phpcs-validations')
        
        violations_over_time = []
        auto_fixes = 0
        
        for entry in phpcs_logs:
            data = entry['data']
            violations_over_time.append({
                'timestamp': entry['timestamp'],
                'violations': data.get('violations_found', 0),
                'files_checked': data.get('files_checked', 1)
            })
            
            if data.get('auto_fixed', False):
                auto_fixes += 1
        
        return {
            'total_runs': len(phpcs_logs),
            'auto_fixes': auto_fixes,
            'violations_trend': violations_over_time[-10:] if violations_over_time else []  # Last 10 runs
        }
    
    def analyze_sync_operations(self):
        """Analyze sync to webserver operations"""
        sync_logs = self.read_log_file('sync-operations')
        
        successful_syncs = 0
        failed_syncs = 0
        
        for entry in sync_logs:
            if entry['data'].get('success', False):
                successful_syncs += 1
            else:
                failed_syncs += 1
        
        total_syncs = successful_syncs + failed_syncs
        success_rate = (successful_syncs / total_syncs * 100) if total_syncs > 0 else 0
        
        return {
            'total_syncs': total_syncs,
            'successful': successful_syncs,
            'failed': failed_syncs,
            'success_rate': success_rate
        }
    
    def generate_summary_report(self, hours=24):
        """Generate a comprehensive summary report"""
        print("=" * 60)
        print("🔍 CLAUDE CODE ACTIVITY REPORT")
        print("=" * 60)
        print(f"📅 Last {hours} hours of activity")
        print()
        
        # Recent Activity
        recent = self.get_recent_activity(hours)
        print(f"📊 RECENT ACTIVITY: {len(recent)} events")
        
        if recent:
            event_types = Counter([entry['event_type'] for entry in recent])
            for event_type, count in event_types.items():
                print(f"   • {event_type}: {count} events")
            
            print(f"\n🕐 First event: {recent[0]['timestamp']}")
            print(f"🕐 Last event: {recent[-1]['timestamp']}")
        print()
        
        # Hook Performance
        hook_stats = self.analyze_hook_performance()
        print("🎣 HOOK EXECUTION ANALYSIS:")
        for hook_event, stats in hook_stats.items():
            tools = ', '.join(stats['tools']) if isinstance(stats['tools'], set) else str(stats['tools'])
            print(f"   • {hook_event}: {stats['count']} executions")
            print(f"     Tools: {tools}")
            print(f"     Last run: {stats['last_run']}")
        print()
        
        # Security Analysis
        security_stats = self.analyze_security_issues()
        print("🔒 SECURITY ANALYSIS:")
        print(f"   • Total security checks: {security_stats['total_checks']}")
        print(f"   • Files with issues: {security_stats['files_with_issues']}")
        print(f"   • Clean rate: {security_stats['clean_rate']:.1f}%")
        
        if security_stats['issue_types']:
            print("   • Common issues:")
            for issue, count in security_stats['issue_types'].items():
                print(f"     - {issue}: {count} times")
        print()
        
        # PHPCS Analysis
        phpcs_stats = self.analyze_phpcs_trends()
        print("✅ PHPCS VALIDATION ANALYSIS:")
        print(f"   • Total PHPCS runs: {phpcs_stats['total_runs']}")
        print(f"   • Auto-fixes applied: {phpcs_stats['auto_fixes']}")
        
        if phpcs_stats['violations_trend']:
            recent_violations = phpcs_stats['violations_trend'][-1]['violations']
            print(f"   • Recent violations: {recent_violations}")
        print()
        
        # Sync Analysis
        sync_stats = self.analyze_sync_operations()
        print("🚀 SYNC OPERATIONS ANALYSIS:")
        print(f"   • Total sync attempts: {sync_stats['total_syncs']}")
        print(f"   • Successful syncs: {sync_stats['successful']}")
        print(f"   • Failed syncs: {sync_stats['failed']}")
        print(f"   • Success rate: {sync_stats['success_rate']:.1f}%")
        print()
        
        # Log Files Info
        print("📁 LOG FILES:")
        for log_file in self.log_dir.glob("*.log"):
            size = log_file.stat().st_size
            print(f"   • {log_file.name}: {size} bytes")
        
        print("=" * 60)
        print(f"📍 Log files location: {self.log_dir}")
        print("=" * 60)
    
    def tail_logs(self, lines=20):
        """Show recent log entries (like tail command)"""
        recent = self.get_recent_activity(24)
        recent_entries = recent[-lines:] if recent else []
        
        print(f"📜 RECENT LOG ENTRIES (last {len(recent_entries)} events):")
        print("-" * 60)
        
        for entry in recent_entries:
            timestamp = entry['timestamp'][:19]  # Remove microseconds
            event_type = entry['event_type']
            
            if event_type == 'hooks':
                tool = entry['data'].get('tool_name', 'unknown')
                print(f"{timestamp} | HOOK    | {tool}")
            elif event_type == 'security':
                file_path = entry['data'].get('file_path', 'unknown')
                issues = len(entry['data'].get('issues', []))
                status = f"{issues} issues" if issues > 0 else "clean"
                print(f"{timestamp} | SECURITY| {Path(file_path).name} ({status})")
            elif event_type == 'phpcs':
                violations = entry['data'].get('violations_found', 0)
                print(f"{timestamp} | PHPCS   | {violations} violations")
            elif event_type == 'sync':
                success = entry['data'].get('success', False)
                status = "✅" if success else "❌"
                print(f"{timestamp} | SYNC    | {status}")
            elif event_type == 'commands':
                cmd = entry['data'].get('command', 'unknown')[:30]
                print(f"{timestamp} | COMMAND | {cmd}")
            else:
                print(f"{timestamp} | {event_type.upper():<7} | {entry['data']}")

def main():
    """Main CLI interface"""
    analyzer = LogAnalyzer()
    
    if len(sys.argv) < 2:
        analyzer.generate_summary_report()
        return
    
    command = sys.argv[1]
    
    if command == "summary":
        hours = int(sys.argv[2]) if len(sys.argv) > 2 else 24
        analyzer.generate_summary_report(hours)
    elif command == "tail":
        lines = int(sys.argv[2]) if len(sys.argv) > 2 else 20
        analyzer.tail_logs(lines)
    elif command == "security":
        stats = analyzer.analyze_security_issues()
        print(json.dumps(stats, indent=2))
    elif command == "phpcs":
        stats = analyzer.analyze_phpcs_trends()
        print(json.dumps(stats, indent=2))
    elif command == "sync":
        stats = analyzer.analyze_sync_operations()
        print(json.dumps(stats, indent=2))
    else:
        print("Usage: python3 log-analyzer.py [summary|tail|security|phpcs|sync] [options]")
        print("  summary [hours]  - Generate summary report (default: 24 hours)")
        print("  tail [lines]     - Show recent log entries (default: 20 lines)")
        print("  security         - Security analysis JSON")
        print("  phpcs           - PHPCS analysis JSON")
        print("  sync            - Sync analysis JSON")

if __name__ == '__main__':
    main()