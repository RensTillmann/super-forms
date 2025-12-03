import { ReactNode } from 'react';

export interface BasePanelProps {
  isOpen: boolean;
  onClose: () => void;
  title?: string;
  className?: string;
  position?: 'left' | 'right' | 'center';
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
}

export interface SharePanelProps extends BasePanelProps {
  formUrl?: string;
  onInviteCollaborator?: () => void;
  collaborators?: Collaborator[];
  embedOptions?: EmbedOption[];
}

export interface ExportPanelProps extends BasePanelProps {
  onExport: (type: string) => void;
  exportOptions?: ExportOption[];
}

export interface AnalyticsPanelProps extends BasePanelProps {
  analytics?: FormAnalytics;
}

export interface VersionHistoryPanelProps extends BasePanelProps {
  versions?: FormVersion[];
  onRestore: (versionId: string) => void;
}

export interface Collaborator {
  id: string;
  name: string;
  email?: string;
  role: 'owner' | 'editor' | 'viewer';
  avatar?: string;
  initials?: string;
}

export interface EmbedOption {
  type: string;
  label: string;
  icon: ReactNode;
}

export interface ExportOption {
  type: string;
  label: string;
  icon: ReactNode;
  description?: string;
}

export interface FormAnalytics {
  totalViews: number;
  submissions: number;
  conversionRate: number;
  averageTime: string;
  chartData?: any[];
}

export interface FormVersion {
  id: string;
  name: string;
  timestamp: number;
  isCurrent?: boolean;
  author?: string;
  changes?: string;
}