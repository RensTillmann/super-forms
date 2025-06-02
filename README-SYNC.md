# Auto-Sync to Dev Server

This setup allows you to automatically sync your Super Forms changes to your dev server at f4d.nl/dev.

## Manual Sync

To manually sync your changes:

```bash
./sync-to-dev.sh
```

## Automatic Sync (Watch Mode)

To automatically sync whenever files change:

```bash
./watch-and-sync.sh
```

This will:
- Watch for any file changes in the `src/` directory
- Automatically sync changes to f4d.nl/dev
- Skip temporary files (.log, .tmp, .swp, etc.)
- Show real-time sync status

## Setup Requirements

1. **SSH Config**: Make sure your `~/.ssh/config` has:
   ```
   Host f4d.nl
   HostName gnldm1014.siteground.biz
   User u2669-dvgugyayggy5
   Port 18765
   ```

2. **SSH Key**: Ensure your SSH key is properly set up and you can connect without password

3. **inotify-tools** (for watch mode):
   ```bash
   sudo apt-get install inotify-tools
   ```

## Usage Examples

### One-time sync after making changes:
```bash
# Make your code changes
vim src/includes/class-common.php

# Sync to dev server
./sync-to-dev.sh
```

### Continuous development with auto-sync:
```bash
# Start watching for changes
./watch-and-sync.sh

# In another terminal, make changes - they'll auto-sync
vim src/includes/class-common.php
# File is automatically synced when saved
```

## What Gets Synced

- All files in `src/` directory
- Excludes: `.git/`, `node_modules/`, `dist/`, `*.log`, `.DS_Store`, etc.
- Uses `--delete` flag to remove files that no longer exist locally

## Troubleshooting

### SSH Connection Issues
Test your SSH connection:
```bash
ssh f4d.nl "echo 'Connection works'"
```

### Permission Issues
Make sure scripts are executable:
```bash
chmod +x sync-to-dev.sh watch-and-sync.sh
```

### Path Issues
Verify the remote path exists:
```bash
ssh f4d.nl "ls -la /home/customer/www/f4d.nl/public_html/dev/wp-content/plugins/"
```