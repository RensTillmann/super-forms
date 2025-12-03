/**
 * Workflow Templates
 * Pre-built workflows for common use cases
 */

import type { WorkflowTemplate } from '../types/workflow.types';

export const WORKFLOW_TEMPLATES: WorkflowTemplate[] = [
  {
    id: 'simple-email',
    name: 'Simple Email Notification',
    description: 'Send email when form is submitted',
    category: 'basic',
    nodes: [
      {
        id: 'node-1',
        type: 'form.submitted',
        position: { x: 100, y: 200 },
        config: { formId: null },
        selected: false,
        zIndex: 1,
      },
      {
        id: 'node-2',
        type: 'send_email',
        position: { x: 400, y: 200 },
        config: {
          to: '{email}',
          subject: 'Thank you for your submission',
          body: 'We received your form submission.',
          bodyType: 'html',
          from: '{admin_email}',
          replyTo: '',
        },
        selected: false,
        zIndex: 2,
      },
    ],
    connections: [
      {
        id: 'conn-1',
        from: 'node-1',
        fromOutput: 'entry_data',
        to: 'node-2',
        toInput: 'trigger',
        selected: false,
      },
    ],
  },

  {
    id: 'conditional-email',
    name: 'Conditional Email Routing',
    description: 'Send different emails based on form data',
    category: 'advanced',
    nodes: [
      {
        id: 'node-1',
        type: 'form.submitted',
        position: { x: 100, y: 250 },
        config: {},
        selected: false,
        zIndex: 1,
      },
      {
        id: 'node-2',
        type: 'conditional_action',
        position: { x: 350, y: 250 },
        config: {
          operator: 'AND',
          rules: [
            {
              field: '{order_total}',
              operator: '>',
              value: '100',
            },
          ],
        },
        selected: false,
        zIndex: 2,
      },
      {
        id: 'node-3',
        type: 'send_email',
        position: { x: 600, y: 150 },
        config: {
          to: 'vip@company.com',
          subject: 'VIP Order Notification',
          body: 'Large order received: ${order_total}',
        },
        selected: false,
        zIndex: 3,
      },
      {
        id: 'node-4',
        type: 'send_email',
        position: { x: 600, y: 350 },
        config: {
          to: 'orders@company.com',
          subject: 'New Order',
          body: 'Standard order received',
        },
        selected: false,
        zIndex: 4,
      },
    ],
    connections: [
      {
        id: 'conn-1',
        from: 'node-1',
        fromOutput: 'entry_data',
        to: 'node-2',
        toInput: 'trigger',
        selected: false,
      },
      {
        id: 'conn-2',
        from: 'node-2',
        fromOutput: 'true',
        to: 'node-3',
        toInput: 'trigger',
        selected: false,
      },
      {
        id: 'conn-3',
        from: 'node-2',
        fromOutput: 'false',
        to: 'node-4',
        toInput: 'trigger',
        selected: false,
      },
    ],
  },

  {
    id: 'webhook-integration',
    name: 'API Integration',
    description: 'Send data to external API via webhook',
    category: 'integration',
    nodes: [
      {
        id: 'node-1',
        type: 'form.submitted',
        position: { x: 100, y: 200 },
        config: {},
        selected: false,
        zIndex: 1,
      },
      {
        id: 'node-2',
        type: 'http_request',
        position: { x: 400, y: 200 },
        config: {
          url: 'https://api.example.com/leads',
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            name: '{name}',
            email: '{email}',
            phone: '{phone}',
          }),
          auth: 'none',
        },
        selected: false,
        zIndex: 2,
      },
    ],
    connections: [
      {
        id: 'conn-1',
        from: 'node-1',
        fromOutput: 'entry_data',
        to: 'node-2',
        toInput: 'trigger',
        selected: false,
      },
    ],
  },

  {
    id: 'delayed-followup',
    name: 'Delayed Follow-up Email',
    description: 'Send follow-up email after delay',
    category: 'automation',
    nodes: [
      {
        id: 'node-1',
        type: 'form.submitted',
        position: { x: 100, y: 200 },
        config: {},
        selected: false,
        zIndex: 1,
      },
      {
        id: 'node-2',
        type: 'delay_execution',
        position: { x: 350, y: 200 },
        config: {
          duration: 24,
          unit: 'hours',
        },
        selected: false,
        zIndex: 2,
      },
      {
        id: 'node-3',
        type: 'send_email',
        position: { x: 600, y: 200 },
        config: {
          to: '{email}',
          subject: 'Following up on your submission',
          body: 'Thank you for your interest. How can we help you further?',
        },
        selected: false,
        zIndex: 3,
      },
    ],
    connections: [
      {
        id: 'conn-1',
        from: 'node-1',
        fromOutput: 'entry_data',
        to: 'node-2',
        toInput: 'trigger',
        selected: false,
      },
      {
        id: 'conn-2',
        from: 'node-2',
        fromOutput: 'continue',
        to: 'node-3',
        toInput: 'trigger',
        selected: false,
      },
    ],
  },

  {
    id: 'spam-protection',
    name: 'Spam Detection & Logging',
    description: 'Log spam submissions without creating entries',
    category: 'security',
    nodes: [
      {
        id: 'node-1',
        type: 'form.spam_detected',
        position: { x: 100, y: 200 },
        config: {},
        selected: false,
        zIndex: 1,
      },
      {
        id: 'node-2',
        type: 'log_message',
        position: { x: 350, y: 200 },
        config: {
          message: 'Spam detected from IP: {ip_address}',
          level: 'warning',
        },
        selected: false,
        zIndex: 2,
      },
      {
        id: 'node-3',
        type: 'abort_submission',
        position: { x: 600, y: 200 },
        config: {
          message: 'Your submission appears to be spam. Please try again.',
        },
        selected: false,
        zIndex: 3,
      },
    ],
    connections: [
      {
        id: 'conn-1',
        from: 'node-1',
        fromOutput: 'detection_details',
        to: 'node-2',
        toInput: 'trigger',
        selected: false,
      },
      {
        id: 'conn-2',
        from: 'node-2',
        fromOutput: 'continue',
        to: 'node-3',
        toInput: 'trigger',
        selected: false,
      },
    ],
  },

  {
    id: 'payment-success',
    name: 'Payment Success Workflow',
    description: 'Handle successful payment processing',
    category: 'ecommerce',
    nodes: [
      {
        id: 'node-1',
        type: 'payment.stripe.payment_succeeded',
        position: { x: 100, y: 200 },
        config: {},
        selected: false,
        zIndex: 1,
      },
      {
        id: 'node-2',
        type: 'update_entry_status',
        position: { x: 350, y: 150 },
        config: {
          status: 'paid',
        },
        selected: false,
        zIndex: 2,
      },
      {
        id: 'node-3',
        type: 'send_email',
        position: { x: 350, y: 300 },
        config: {
          to: '{email}',
          subject: 'Payment Received - Order Confirmed',
          body: 'Thank you for your payment. Your order is confirmed.',
        },
        selected: false,
        zIndex: 3,
      },
    ],
    connections: [
      {
        id: 'conn-1',
        from: 'node-1',
        fromOutput: 'payment_data',
        to: 'node-2',
        toInput: 'trigger',
        selected: false,
      },
      {
        id: 'conn-2',
        from: 'node-1',
        fromOutput: 'payment_data',
        to: 'node-3',
        toInput: 'trigger',
        selected: false,
      },
    ],
  },

  {
    id: 'multi-step-approval',
    name: 'Multi-Step Approval Workflow',
    description: 'Create post and send notification for approval',
    category: 'workflow',
    nodes: [
      {
        id: 'node-1',
        type: 'form.submitted',
        position: { x: 100, y: 250 },
        config: {},
        selected: false,
        zIndex: 1,
      },
      {
        id: 'node-2',
        type: 'create_post',
        position: { x: 350, y: 200 },
        config: {
          postType: 'application',
          postStatus: 'pending',
          postTitle: '{applicant_name} - Application',
          postContent: '{application_details}',
        },
        selected: false,
        zIndex: 2,
      },
      {
        id: 'node-3',
        type: 'send_email',
        position: { x: 350, y: 350 },
        config: {
          to: '{email}',
          subject: 'Application Received',
          body: 'We received your application. You will hear from us soon.',
        },
        selected: false,
        zIndex: 3,
      },
      {
        id: 'node-4',
        type: 'send_email',
        position: { x: 600, y: 250 },
        config: {
          to: 'admin@company.com',
          subject: 'New Application Pending Review',
          body: 'New application requires approval.',
        },
        selected: false,
        zIndex: 4,
      },
    ],
    connections: [
      {
        id: 'conn-1',
        from: 'node-1',
        fromOutput: 'entry_data',
        to: 'node-2',
        toInput: 'trigger',
        selected: false,
      },
      {
        id: 'conn-2',
        from: 'node-1',
        fromOutput: 'entry_data',
        to: 'node-3',
        toInput: 'trigger',
        selected: false,
      },
      {
        id: 'conn-3',
        from: 'node-2',
        fromOutput: 'post_id',
        to: 'node-4',
        toInput: 'trigger',
        selected: false,
      },
    ],
  },
];

/**
 * Get templates by category
 */
export function getTemplatesByCategory(category: string): WorkflowTemplate[] {
  return WORKFLOW_TEMPLATES.filter((t) => t.category === category);
}

/**
 * Get all template categories
 */
export function getTemplateCategories(): string[] {
  return Array.from(new Set(WORKFLOW_TEMPLATES.map((t) => t.category)));
}
