import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Search, Plus, MoreHorizontal, Copy, Archive, ArchiveRestore, Trash2, FileText } from 'lucide-react';

// WordPress REST API global
declare const wp: {
  apiFetch: (options: {
    path: string;
    method?: string;
    data?: any;
  }) => Promise<any>;
};

interface Form {
  id: number;
  name: string;
  status: string;
  shortcode: string;
  created_at: string;
  updated_at: string;
  entry_count?: number;
}

interface FormsListProps {
  forms: Form[];
  statusCounts: {
    all: number;
    publish: number;
    draft: number;
    archived: number;
  };
  currentStatus: string;
  searchQuery: string;
}

export function FormsList({
  forms: initialForms,
  statusCounts: initialStatusCounts,
  currentStatus: initialStatus,
  searchQuery: initialSearch,
}: FormsListProps) {
  const [forms, setForms] = useState<Form[]>(initialForms);
  const [statusCounts, setStatusCounts] = useState(initialStatusCounts);
  const [currentStatus, setCurrentStatus] = useState(initialStatus);
  const [searchQuery, setSearchQuery] = useState(initialSearch);
  const [selectedForms, setSelectedForms] = useState<Set<number>>(new Set());
  const [isLoading, setIsLoading] = useState(false);

  // Handle status filter change
  const handleStatusChange = (status: string) => {
    setCurrentStatus(status);
    // Reload page with new status
    const url = new URL(window.location.href);
    if (status === 'all') {
      url.searchParams.delete('status');
    } else {
      url.searchParams.set('status', status);
    }
    window.location.href = url.toString();
  };

  // Handle search
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    const url = new URL(window.location.href);
    if (searchQuery) {
      url.searchParams.set('s', searchQuery);
    } else {
      url.searchParams.delete('s');
    }
    window.location.href = url.toString();
  };

  // Handle select all
  const handleSelectAll = (checked: boolean) => {
    if (checked) {
      setSelectedForms(new Set(forms.map((f) => f.id)));
    } else {
      setSelectedForms(new Set());
    }
  };

  // Handle individual select
  const handleSelect = (formId: number, checked: boolean) => {
    const newSelected = new Set(selectedForms);
    if (checked) {
      newSelected.add(formId);
    } else {
      newSelected.delete(formId);
    }
    setSelectedForms(newSelected);
  };

  // Handle bulk action using REST API
  const handleBulkAction = async (action: string) => {
    if (selectedForms.size === 0) {
      alert('Please select at least one form.');
      return;
    }

    if (action === 'delete') {
      if (!confirm(`Are you sure you want to delete ${selectedForms.size} form(s)?`)) {
        return;
      }
    }

    setIsLoading(true);

    try {
      await wp.apiFetch({
        path: `/super-forms/v1/forms/bulk/${action}`,
        method: 'POST',
        data: {
          form_ids: Array.from(selectedForms),
        },
      });

      window.location.reload();
    } catch (error) {
      console.error('Bulk action error:', error);
      alert('An error occurred. Please try again.');
    } finally {
      setIsLoading(false);
    }
  };

  // Handle single form action using REST API
  const handleFormAction = async (formId: number, action: string) => {
    if (action === 'delete') {
      if (!confirm('Are you sure you want to delete this form?')) {
        return;
      }
    }

    setIsLoading(true);

    try {
      if (action === 'delete') {
        // Use dedicated DELETE endpoint for single form
        await wp.apiFetch({
          path: `/super-forms/v1/forms/${formId}`,
          method: 'DELETE',
        });
      } else {
        // Use bulk endpoint for other operations (duplicate, archive, restore)
        await wp.apiFetch({
          path: `/super-forms/v1/forms/bulk/${action}`,
          method: 'POST',
          data: {
            form_ids: [formId],
          },
        });
      }

      window.location.reload();
    } catch (error) {
      console.error('Form action error:', error);
      alert('An error occurred. Please try again.');
    } finally {
      setIsLoading(false);
    }
  };

  const allSelected = forms.length > 0 && selectedForms.size === forms.length;
  const someSelected = selectedForms.size > 0 && selectedForms.size < forms.length;

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-semibold text-foreground">Forms</h1>
        <Button asChild>
          <a href="?page=super_create_form">
            <Plus className="mr-2 h-4 w-4" />
            Add New Form
          </a>
        </Button>
      </div>

      {/* Status Filter */}
      <div className="mb-4 flex gap-4 border-b border-border">
        {[
          { key: 'all', label: 'All', count: statusCounts.all },
          { key: 'publish', label: 'Published', count: statusCounts.publish },
          { key: 'draft', label: 'Draft', count: statusCounts.draft },
          { key: 'archived', label: 'Archived', count: statusCounts.archived },
        ].map((status) => (
          <button
            key={status.key}
            onClick={() => handleStatusChange(status.key)}
            className={`px-3 py-2 text-sm font-medium transition-colors ${
              currentStatus === status.key
                ? 'border-b-2 border-primary text-primary'
                : 'text-muted-foreground hover:text-foreground'
            }`}
          >
            {status.label} ({status.count})
          </button>
        ))}
      </div>

      {/* Search & Bulk Actions */}
      <div className="mb-4 flex items-center gap-4">
        <form onSubmit={handleSearch} className="flex flex-1 gap-2">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              type="text"
              placeholder="Search forms..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-9"
            />
          </div>
          <Button type="submit">Search</Button>
        </form>

        {selectedForms.size > 0 && (
          <div className="flex gap-2">
            <select
              className="rounded-md border border-input bg-background px-3 py-2 text-sm"
              onChange={(e) => handleBulkAction(e.target.value)}
              defaultValue=""
              disabled={isLoading}
            >
              <option value="">Bulk Actions</option>
              <option value="delete">Delete</option>
              <option value="archive">Archive</option>
              <option value="restore">Restore</option>
            </select>
          </div>
        )}
      </div>

      {/* Forms Table */}
      <div className="rounded-lg border border-border bg-card">
        <table className="w-full">
          <thead className="border-b border-border bg-muted/50">
            <tr>
              <th className="w-12 p-4 text-left">
                <input
                  type="checkbox"
                  checked={allSelected}
                  ref={(el) => {
                    if (el) el.indeterminate = someSelected;
                  }}
                  onChange={(e) => handleSelectAll(e.target.checked)}
                  className="rounded border-gray-300"
                />
              </th>
              <th className="p-4 text-left text-sm font-medium text-muted-foreground">Title</th>
              <th className="p-4 text-left text-sm font-medium text-muted-foreground">Shortcode</th>
              <th className="p-4 text-left text-sm font-medium text-muted-foreground">Entries</th>
              <th className="p-4 text-left text-sm font-medium text-muted-foreground">Status</th>
              <th className="p-4 text-left text-sm font-medium text-muted-foreground">Date</th>
              <th className="p-4 text-left text-sm font-medium text-muted-foreground">Actions</th>
            </tr>
          </thead>
          <tbody>
            {forms.length === 0 ? (
              <tr>
                <td colSpan={7} className="p-8 text-center text-muted-foreground">
                  No forms found.
                </td>
              </tr>
            ) : (
              forms.map((form) => (
                <tr key={form.id} className="border-b border-border last:border-0 hover:bg-muted/30">
                  <td className="p-4">
                    <input
                      type="checkbox"
                      checked={selectedForms.has(form.id)}
                      onChange={(e) => handleSelect(form.id, e.target.checked)}
                      className="rounded border-gray-300"
                    />
                  </td>
                  <td className="p-4">
                    <a
                      href={`?page=super_create_form&id=${form.id}`}
                      className="font-medium text-foreground hover:text-primary"
                    >
                      {form.name || `Form #${form.id}`}
                    </a>
                  </td>
                  <td className="p-4">
                    <code className="rounded bg-muted px-2 py-1 text-xs text-muted-foreground">
                      [super_form id="{form.id}"]
                    </code>
                  </td>
                  <td className="p-4">
                    {form.entry_count !== undefined && form.entry_count > 0 ? (
                      <a
                        href={`?page=super_contact_entries&form_id=${form.id}`}
                        className="text-primary hover:underline"
                      >
                        {form.entry_count}
                      </a>
                    ) : (
                      <span className="text-muted-foreground">0</span>
                    )}
                  </td>
                  <td className="p-4">
                    <span
                      className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${
                        form.status === 'publish'
                          ? 'bg-green-100 text-green-700'
                          : form.status === 'draft'
                          ? 'bg-gray-100 text-gray-700'
                          : 'bg-yellow-100 text-yellow-700'
                      }`}
                    >
                      {form.status.charAt(0).toUpperCase() + form.status.slice(1)}
                    </span>
                  </td>
                  <td className="p-4 text-sm text-muted-foreground">
                    {new Date(form.created_at).toLocaleDateString()}
                  </td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() =>
                          (window.location.href = `?page=super_create_form&id=${form.id}`)
                        }
                        title="Edit"
                      >
                        <FileText className="h-4 w-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleFormAction(form.id, 'duplicate')}
                        title="Duplicate"
                        disabled={isLoading}
                      >
                        <Copy className="h-4 w-4" />
                      </Button>
                      {form.status === 'archived' ? (
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleFormAction(form.id, 'restore')}
                          title="Restore"
                          disabled={isLoading}
                        >
                          <ArchiveRestore className="h-4 w-4" />
                        </Button>
                      ) : (
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleFormAction(form.id, 'archive')}
                          title="Archive"
                          disabled={isLoading}
                        >
                          <Archive className="h-4 w-4" />
                        </Button>
                      )}
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleFormAction(form.id, 'delete')}
                        title="Delete"
                        disabled={isLoading}
                      >
                        <Trash2 className="h-4 w-4 text-destructive" />
                      </Button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
