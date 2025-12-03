import { NavLink, Outlet } from 'react-router-dom';

/**
 * Form Builder Layout
 * Main layout wrapper for the form builder with tab navigation
 */
export function FormBuilderLayout() {
  return (
    <div className="sfui-form-builder" data-testid="form-builder-layout">
      {/* Tab Navigation */}
      <nav className="sfui-tabs border-b border-gray-200 mb-6">
        <div className="flex space-x-1">
          <NavLink
            to="/emails"
            className={({ isActive }) =>
              `px-6 py-3 text-sm font-medium rounded-t-lg transition-colors ${
                isActive
                  ? 'bg-white text-blue-600 border-b-2 border-blue-600'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
              }`
            }
            data-testid="tab-emails"
          >
            📧 Emails
          </NavLink>

          <NavLink
            to="/builder"
            className={({ isActive }) =>
              `px-6 py-3 text-sm font-medium rounded-t-lg transition-colors ${
                isActive
                  ? 'bg-white text-blue-600 border-b-2 border-blue-600'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
              }`
            }
            data-testid="tab-builder"
          >
            🔨 Form Builder
          </NavLink>

          <NavLink
            to="/automations"
            className={({ isActive }) =>
              `px-6 py-3 text-sm font-medium rounded-t-lg transition-colors ${
                isActive
                  ? 'bg-white text-blue-600 border-b-2 border-blue-600'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
              }`
            }
            data-testid="tab-automations"
          >
            ⚡ Automations
          </NavLink>

          <NavLink
            to="/settings"
            className={({ isActive}) =>
              `px-6 py-3 text-sm font-medium rounded-t-lg transition-colors ${
                isActive
                  ? 'bg-white text-blue-600 border-b-2 border-blue-600'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
              }`
            }
            data-testid="tab-settings"
          >
            ⚙️ Settings
          </NavLink>
        </div>
      </nav>

      {/* Tab Content */}
      <div className="sfui-tab-content">
        <Outlet />
      </div>
    </div>
  );
}
