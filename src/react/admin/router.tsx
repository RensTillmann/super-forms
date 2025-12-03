/**
 * SFUI Admin Router Configuration
 *
 * Pure SPA architecture using HashRouter for WordPress admin compatibility.
 * React owns all routing and tab navigation.
 *
 * Route structure:
 * - /super_create_form → Form Builder page
 *   - #/emails → Email Builder (React)
 *   - #/builder → Form Builder (Placeholder/Legacy)
 *   - #/settings → Form Settings (Placeholder)
 * - /super_settings → Global Settings page (future)
 * - /super_entries → Entries page (future)
 */

import { lazy, Suspense } from 'react';
import { HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import { Skeleton } from '@/components/ui/skeleton';
import { FormBuilderLayout } from './components/FormBuilderLayout';

// Lazy load page components for code splitting
const EmailsPage = lazy(() => import('./pages/form-builder/emails/App'));
const AutomationsPage = lazy(() => import('./pages/form-builder/automations/AutomationsPage'));

/**
 * Page loading skeleton
 * Displays while lazy-loaded components are being fetched
 */
function PageSkeleton() {
  return (
    <div className="flex h-full gap-4 p-4" data-testid="page-skeleton">
      {/* Left sidebar skeleton */}
      <div className="w-72 space-y-3">
        <Skeleton className="h-10 w-full" />
        <Skeleton className="h-16 w-full" />
        <Skeleton className="h-16 w-full" />
        <Skeleton className="h-16 w-full" />
      </div>
      {/* Main content skeleton */}
      <div className="flex-1 space-y-4">
        <Skeleton className="h-12 w-full" />
        <Skeleton className="h-64 w-full" />
        <Skeleton className="h-32 w-full" />
      </div>
    </div>
  );
}

/**
 * Form Builder Router
 * Pure SPA routing with React-owned tab navigation
 */
export function FormBuilderRouter() {
  return (
    <HashRouter>
      <Suspense fallback={<PageSkeleton />}>
        <Routes>
          <Route element={<FormBuilderLayout />}>
            {/* Default to emails tab */}
            <Route index element={<Navigate to="/emails" replace />} />
            <Route path="/emails" element={<EmailsPage {...window.sfuiData} />} />
            <Route path="/automations" element={<AutomationsPage {...window.sfuiData} />} />
            <Route path="/builder" element={<BuilderPlaceholder />} />
            <Route path="/settings" element={<SettingsPlaceholder />} />
            {/* Catch-all redirect to emails */}
            <Route path="*" element={<Navigate to="/emails" replace />} />
          </Route>
        </Routes>
      </Suspense>
    </HashRouter>
  );
}

/**
 * Placeholder for Form Builder tab
 * TODO: Migrate legacy jQuery builder to React or iframe
 */
function BuilderPlaceholder() {
  return (
    <div className="p-8 text-center bg-gray-50 rounded-lg" data-testid="builder-placeholder">
      <div className="max-w-md mx-auto">
        <h2 className="text-2xl font-semibold text-gray-900 mb-4">
          🔨 Form Builder
        </h2>
        <p className="text-gray-600 mb-6">
          The form builder will be available here soon.
        </p>
        <p className="text-sm text-gray-500">
          This tab will either show the legacy jQuery builder in an iframe,
          or a new React-based builder in future phases.
        </p>
      </div>
    </div>
  );
}

/**
 * Placeholder for Settings tab
 * TODO: Migrate settings to React
 */
function SettingsPlaceholder() {
  return (
    <div className="p-8 text-center bg-gray-50 rounded-lg" data-testid="settings-placeholder">
      <div className="max-w-md mx-auto">
        <h2 className="text-2xl font-semibold text-gray-900 mb-4">
          ⚙️ Settings
        </h2>
        <p className="text-gray-600 mb-6">
          Form settings will be available here soon.
        </p>
        <p className="text-sm text-gray-500">
          Settings configuration UI is planned for future migration phases.
        </p>
      </div>
    </div>
  );
}

/**
 * Settings Page Router (future)
 */
export function SettingsRouter() {
  return (
    <HashRouter>
      <Suspense fallback={<PageSkeleton />}>
        <Routes>
          <Route path="/" element={<div>Settings Page (coming soon)</div>} />
        </Routes>
      </Suspense>
    </HashRouter>
  );
}

/**
 * Entries Page Router (future)
 */
export function EntriesRouter() {
  return (
    <HashRouter>
      <Suspense fallback={<PageSkeleton />}>
        <Routes>
          <Route path="/" element={<div>Entries Page (coming soon)</div>} />
        </Routes>
      </Suspense>
    </HashRouter>
  );
}
