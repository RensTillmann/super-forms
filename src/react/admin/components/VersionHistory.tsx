/**
 * VersionHistory - UI component for viewing and managing form versions
 *
 * Displays version history with ability to revert to previous versions.
 *
 * @since 6.6.0 (Phase 27)
 */

import React, { useEffect, useState } from 'react';
import { Clock, GitBranch, RotateCcw, Save, User } from 'lucide-react';
import { Button } from './ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/card';
import { ScrollArea } from './ui/scroll-area';
import { Badge } from './ui/badge';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from './ui/dialog';

interface Version {
  id: number;
  form_id: number;
  version_number: number;
  snapshot: any;
  operations: any[] | null;
  created_by: number;
  created_at: string;
  message: string | null;
}

interface VersionHistoryProps {
  formId: number;
  onRevert?: (version: Version) => void;
  className?: string;
}

export function VersionHistory({ formId, onRevert, className }: VersionHistoryProps) {
  const [versions, setVersions] = useState<Version[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedVersion, setSelectedVersion] = useState<Version | null>(null);
  const [revertDialogOpen, setRevertDialogOpen] = useState(false);
  const [reverting, setReverting] = useState(false);

  useEffect(() => {
    loadVersions();
  }, [formId]);

  const loadVersions = async () => {
    try {
      setLoading(true);
      setError(null);

      const response = await fetch(
        `/wp-json/super-forms/v1/forms/${formId}/versions?limit=20`,
        {
          headers: {
            'X-WP-Nonce': (window as any).wpApiSettings?.nonce || '',
          },
        }
      );

      if (!response.ok) {
        throw new Error('Failed to load versions');
      }

      const data = await response.json();
      setVersions(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unknown error');
    } finally {
      setLoading(false);
    }
  };

  const handleRevertClick = (version: Version) => {
    setSelectedVersion(version);
    setRevertDialogOpen(true);
  };

  const handleRevertConfirm = async () => {
    if (!selectedVersion) return;

    try {
      setReverting(true);

      const response = await fetch(
        `/wp-json/super-forms/v1/forms/${formId}/revert/${selectedVersion.id}`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': (window as any).wpApiSettings?.nonce || '',
          },
        }
      );

      if (!response.ok) {
        throw new Error('Failed to revert to version');
      }

      const result = await response.json();

      // Reload versions
      await loadVersions();

      // Notify parent
      if (onRevert) {
        onRevert(selectedVersion);
      }

      setRevertDialogOpen(false);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Revert failed');
    } finally {
      setReverting(false);
    }
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} min${diffMins === 1 ? '' : 's'} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours === 1 ? '' : 's'} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays === 1 ? '' : 's'} ago`;

    return date.toLocaleDateString();
  };

  if (loading) {
    return (
      <Card className={className}>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <GitBranch className="h-5 w-5" />
            Version History
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-center py-8">
            <div className="text-muted-foreground">Loading versions...</div>
          </div>
        </CardContent>
      </Card>
    );
  }

  if (error) {
    return (
      <Card className={className}>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <GitBranch className="h-5 w-5" />
            Version History
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-center py-8 text-destructive">
            Error: {error}
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <>
      <Card className={className}>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <GitBranch className="h-5 w-5" />
            Version History
          </CardTitle>
          <CardDescription>
            View and revert to previous versions of this form
          </CardDescription>
        </CardHeader>
        <CardContent>
          {versions.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-8 text-muted-foreground">
              <Save className="mb-2 h-8 w-8 opacity-20" />
              <p>No saved versions yet</p>
              <p className="text-sm">Save your form to create the first version</p>
            </div>
          ) : (
            <ScrollArea className="h-[400px]">
              <div className="space-y-3">
                {versions.map((version, index) => (
                  <div
                    key={version.id}
                    className="flex items-start gap-3 rounded-lg border p-3 hover:bg-accent/50 transition-colors"
                  >
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 mb-1">
                        <Badge variant={index === 0 ? 'default' : 'secondary'}>
                          v{version.version_number}
                        </Badge>
                        {index === 0 && (
                          <Badge variant="outline" className="text-xs">
                            Current
                          </Badge>
                        )}
                      </div>

                      {version.message && (
                        <p className="text-sm font-medium mb-1">{version.message}</p>
                      )}

                      <div className="flex items-center gap-3 text-xs text-muted-foreground">
                        <span className="flex items-center gap-1">
                          <Clock className="h-3 w-3" />
                          {formatDate(version.created_at)}
                        </span>
                        {version.operations && version.operations.length > 0 && (
                          <span className="flex items-center gap-1">
                            {version.operations.length} change
                            {version.operations.length === 1 ? '' : 's'}
                          </span>
                        )}
                      </div>
                    </div>

                    {index !== 0 && (
                      <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => handleRevertClick(version)}
                        className="shrink-0"
                      >
                        <RotateCcw className="h-4 w-4 mr-1" />
                        Revert
                      </Button>
                    )}
                  </div>
                ))}
              </div>
            </ScrollArea>
          )}
        </CardContent>
      </Card>

      <Dialog open={revertDialogOpen} onOpenChange={setRevertDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Revert to Version {selectedVersion?.version_number}?</DialogTitle>
            <DialogDescription>
              This will restore the form to its state at this version. Your current changes
              will be saved as a new version before reverting, so you can always undo this
              action.
            </DialogDescription>
          </DialogHeader>

          {selectedVersion?.message && (
            <div className="rounded-lg bg-muted p-3">
              <p className="text-sm font-medium mb-1">Version message:</p>
              <p className="text-sm text-muted-foreground">{selectedVersion.message}</p>
            </div>
          )}

          <DialogFooter>
            <Button variant="outline" onClick={() => setRevertDialogOpen(false)}>
              Cancel
            </Button>
            <Button onClick={handleRevertConfirm} disabled={reverting}>
              {reverting ? 'Reverting...' : 'Revert to This Version'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
