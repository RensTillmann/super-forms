/**
 * Super Forms Node Type Definitions
 * Maps Super Forms events and actions to visual workflow nodes
 */

import {
  FormInput,
  Shield,
  FileText,
  RefreshCw,
  Mail,
  Globe,
  Webhook,
  Trash,
  ShoppingCart,
  Users,
  CreditCard,
  DollarSign,
  Clock,
  GitBranch,
  Filter,
  Database,
  FileUp,
  AlertCircle,
  CheckCircle,
  XCircle,
  Pause,
  Play,
  MessageSquare,
  Upload,
  Download,
  Settings,
  Code,
} from 'lucide-react';
import type { NodeTypeDefinition } from '../types/workflow.types';

/**
 * Super Forms Event Nodes
 * Based on 36 registered events in the trigger system
 */
export const EVENT_NODES: Record<string, NodeTypeDefinition> = {
  // Form Events
  'form.submitted': {
    id: 'form.submitted',
    name: 'Form Submitted',
    category: 'event',
    color: '#10b981',
    icon: FormInput,
    description: 'Trigger when a form is submitted',
    inputs: [],
    outputs: ['entry_data', 'form_data'],
    config: {
      scope: 'all',
      formId: null,
      includeSpam: false,
    },
  },
  'form.spam_detected': {
    id: 'form.spam_detected',
    name: 'Spam Detected',
    category: 'event',
    color: '#10b981',
    icon: Shield,
    description: 'Trigger when spam is detected',
    inputs: [],
    outputs: ['detection_details'],
    config: {
      scope: 'all',
      formId: null,
      detectionMethods: [],
    },
  },
  'form.duplicate_detected': {
    id: 'form.duplicate_detected',
    name: 'Duplicate Detected',
    category: 'event',
    color: '#10b981',
    icon: AlertCircle,
    description: 'Trigger when duplicate submission detected',
    inputs: [],
    outputs: ['duplicate_data'],
    config: {
      scope: 'all',
      formId: null,
    },
  },

  // Entry Events
  'entry.created': {
    id: 'entry.created',
    name: 'Entry Created',
    category: 'event',
    color: '#10b981',
    icon: FileText,
    description: 'Trigger after entry is saved to database',
    inputs: [],
    outputs: ['entry_id', 'entry_data'],
    config: {
      scope: 'all',
      formId: null,
    },
  },
  'entry.updated': {
    id: 'entry.updated',
    name: 'Entry Updated',
    category: 'event',
    color: '#10b981',
    icon: RefreshCw,
    description: 'Trigger when entry data is modified',
    inputs: [],
    outputs: ['entry_id', 'changes'],
    config: {
      scope: 'all',
      formId: null,
    },
  },
  'entry.status_changed': {
    id: 'entry.status_changed',
    name: 'Entry Status Changed',
    category: 'event',
    color: '#10b981',
    icon: RefreshCw,
    description: 'Trigger when entry status changes',
    inputs: [],
    outputs: ['entry_id', 'old_status', 'new_status'],
    config: {
      scope: 'all',
      formId: null,
    },
  },
  'entry.deleted': {
    id: 'entry.deleted',
    name: 'Entry Deleted',
    category: 'event',
    color: '#10b981',
    icon: Trash,
    description: 'Trigger when entry is deleted',
    inputs: [],
    outputs: ['entry_id'],
    config: {
      scope: 'all',
      formId: null,
    },
  },

  // Payment Events (Stripe)
  'payment.stripe.payment_succeeded': {
    id: 'payment.stripe.payment_succeeded',
    name: 'Stripe Payment Success',
    category: 'event',
    color: '#10b981',
    icon: CreditCard,
    description: 'Trigger when Stripe payment succeeds',
    inputs: [],
    outputs: ['payment_data'],
    config: {
      scope: 'all',
    },
  },
  'payment.stripe.payment_failed': {
    id: 'payment.stripe.payment_failed',
    name: 'Stripe Payment Failed',
    category: 'event',
    color: '#10b981',
    icon: XCircle,
    description: 'Trigger when Stripe payment fails',
    inputs: [],
    outputs: ['error_data'],
    config: {
      scope: 'all',
    },
  },

  // Payment Events (PayPal)
  'payment.paypal.capture_completed': {
    id: 'payment.paypal.capture_completed',
    name: 'PayPal Payment Complete',
    category: 'event',
    color: '#10b981',
    icon: DollarSign,
    description: 'Trigger when PayPal payment completes',
    inputs: [],
    outputs: ['payment_data'],
    config: {
      scope: 'all',
    },
  },

  // Session Events
  'session.abandoned': {
    id: 'session.abandoned',
    name: 'Session Abandoned',
    category: 'event',
    color: '#10b981',
    icon: Clock,
    description: 'Trigger when form session is abandoned',
    inputs: [],
    outputs: ['session_data'],
    config: {
      scope: 'all',
      formId: null,
      abandonTime: 30, // minutes
    },
  },

  // File Events
  'file.uploaded': {
    id: 'file.uploaded',
    name: 'File Uploaded',
    category: 'event',
    color: '#10b981',
    icon: Upload,
    description: 'Trigger when file is uploaded',
    inputs: [],
    outputs: ['file_data'],
    config: {
      scope: 'all',
      formId: null,
    },
  },
};

/**
 * Super Forms Action Nodes
 * Based on 20+ registered actions in the trigger system
 */
export const ACTION_NODES: Record<string, NodeTypeDefinition> = {
  // Email Actions
  send_email: {
    id: 'send_email',
    name: 'Send Email',
    category: 'action',
    color: '#3b82f6',
    icon: Mail,
    description: 'Send an email notification',
    inputs: ['trigger'],
    outputs: ['success'],
    config: {
      to: '',
      subject: '',
      body: '',
      bodyType: 'html',
      from: '{admin_email}',
      replyTo: '',
    },
  },

  // HTTP/API Actions
  http_request: {
    id: 'http_request',
    name: 'HTTP Request',
    category: 'action',
    color: '#3b82f6',
    icon: Globe,
    description: 'Make an HTTP API call',
    inputs: ['trigger'],
    outputs: ['response'],
    config: {
      url: '',
      method: 'POST',
      headers: {},
      body: '',
      auth: 'none',
    },
  },
  webhook: {
    id: 'webhook',
    name: 'Webhook',
    category: 'action',
    color: '#3b82f6',
    icon: Webhook,
    description: 'Send webhook notification',
    inputs: ['trigger'],
    outputs: ['success'],
    config: {
      url: '',
      method: 'POST',
      headers: {},
      payload: '',
    },
  },

  // Entry Actions
  update_entry_status: {
    id: 'update_entry_status',
    name: 'Update Entry Status',
    category: 'action',
    color: '#3b82f6',
    icon: RefreshCw,
    description: 'Change entry status',
    inputs: ['trigger'],
    outputs: ['success'],
    config: {
      status: 'approved',
    },
  },
  update_entry_field: {
    id: 'update_entry_field',
    name: 'Update Entry Field',
    category: 'action',
    color: '#3b82f6',
    icon: FileText,
    description: 'Modify entry field value',
    inputs: ['trigger'],
    outputs: ['success'],
    config: {
      fieldName: '',
      value: '',
    },
  },
  delete_entry: {
    id: 'delete_entry',
    name: 'Delete Entry',
    category: 'action',
    color: '#3b82f6',
    icon: Trash,
    description: 'Delete the entry',
    inputs: ['trigger'],
    outputs: ['success'],
    config: {},
  },

  // Post/Content Actions
  create_post: {
    id: 'create_post',
    name: 'Create Post',
    category: 'action',
    color: '#3b82f6',
    icon: FileText,
    description: 'Create WordPress post',
    inputs: ['trigger'],
    outputs: ['post_id'],
    config: {
      postType: 'post',
      postStatus: 'publish',
      postTitle: '',
      postContent: '',
    },
  },

  // Flow Control Actions
  delay_execution: {
    id: 'delay_execution',
    name: 'Delay',
    category: 'action',
    color: '#3b82f6',
    icon: Pause,
    description: 'Delay execution for specified time',
    inputs: ['trigger'],
    outputs: ['continue'],
    config: {
      duration: 60,
      unit: 'minutes',
    },
  },
  abort_submission: {
    id: 'abort_submission',
    name: 'Abort Submission',
    category: 'action',
    color: '#3b82f6',
    icon: XCircle,
    description: 'Stop form submission',
    inputs: ['trigger'],
    outputs: [],
    config: {
      message: 'Submission rejected',
    },
  },
  stop_execution: {
    id: 'stop_execution',
    name: 'Stop Execution',
    category: 'action',
    color: '#3b82f6',
    icon: XCircle,
    description: 'Stop trigger execution',
    inputs: ['trigger'],
    outputs: [],
    config: {},
  },

  // Variable/Data Actions
  set_variable: {
    id: 'set_variable',
    name: 'Set Variable',
    category: 'action',
    color: '#3b82f6',
    icon: Code,
    description: 'Set a context variable',
    inputs: ['trigger'],
    outputs: ['continue'],
    config: {
      variableName: '',
      value: '',
    },
  },
  log_message: {
    id: 'log_message',
    name: 'Log Message',
    category: 'action',
    color: '#3b82f6',
    icon: MessageSquare,
    description: 'Write to debug log',
    inputs: ['trigger'],
    outputs: ['continue'],
    config: {
      message: '',
      level: 'info',
    },
  },

  // Integration Actions
  'mailpoet.add_subscriber': {
    id: 'mailpoet.add_subscriber',
    name: 'MailPoet Subscribe',
    category: 'action',
    color: '#3b82f6',
    icon: Users,
    description: 'Add subscriber to MailPoet',
    inputs: ['trigger'],
    outputs: ['success'],
    config: {
      listIds: [],
      email: '{email}',
      firstName: '{first_name}',
      lastName: '{last_name}',
    },
  },
  'woocommerce.add_to_cart': {
    id: 'woocommerce.add_to_cart',
    name: 'WooCommerce Add to Cart',
    category: 'action',
    color: '#3b82f6',
    icon: ShoppingCart,
    description: 'Add product to cart',
    inputs: ['trigger'],
    outputs: ['cart_data'],
    config: {
      productId: '',
      quantity: 1,
    },
  },
};

/**
 * Condition Nodes
 * For branching logic in workflows
 */
export const CONDITION_NODES: Record<string, NodeTypeDefinition> = {
  conditional_action: {
    id: 'conditional_action',
    name: 'If Condition',
    category: 'condition',
    color: '#f97316',
    icon: GitBranch,
    description: 'Branch based on conditions',
    inputs: ['trigger'],
    outputs: ['true', 'false'],
    config: {
      operator: 'AND',
      rules: [],
    },
  },
  condition_group: {
    id: 'condition_group',
    name: 'Condition Group',
    category: 'condition',
    color: '#f97316',
    icon: Filter,
    description: 'Group multiple conditions (AND/OR)',
    inputs: ['trigger'],
    outputs: ['true', 'false'],
    config: {
      operator: 'AND',
      rules: [],
    },
  },
};

/**
 * Combined node types lookup
 */
export const SUPER_FORMS_NODE_TYPES: Record<string, NodeTypeDefinition> = {
  ...EVENT_NODES,
  ...ACTION_NODES,
  ...CONDITION_NODES,
};

/**
 * Get all node types by category
 */
export function getNodeTypesByCategory(category: 'event' | 'action' | 'condition'): NodeTypeDefinition[] {
  return Object.values(SUPER_FORMS_NODE_TYPES).filter((node) => node.category === category);
}

/**
 * Get node type by ID
 */
export function getNodeType(id: string): NodeTypeDefinition | undefined {
  return SUPER_FORMS_NODE_TYPES[id];
}
