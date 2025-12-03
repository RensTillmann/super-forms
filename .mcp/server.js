#!/usr/bin/env node
/**
 * Super Forms MCP Server
 *
 * Exposes form manipulation tools for AI/LLM integration.
 * Communicates with WordPress via REST API.
 *
 * @since 6.6.0 (Phase 27)
 */

const https = require('https');
const fs = require('fs');
const path = require('path');

// Configuration
const WP_SITE_URL = process.env.WP_SITE_URL || 'https://f4d.nl/dev';
const WP_API_NONCE = process.env.WP_API_NONCE || '';
const WP_COOKIE = process.env.WP_COOKIE || '';

// Tool implementations
const tools = {
  async add_form_element({ formId, elementType, position, label, name, config = {} }) {
    const element = {
      type: elementType,
      label,
      name,
      ...config,
    };

    const operation = {
      op: 'add',
      path: position >= 0 ? `/elements/${position}` : '/elements/-',
      value: element,
    };

    return await applyOperations(formId, [operation]);
  },

  async update_form_element({ formId, elementIndex, updates }) {
    const operations = Object.keys(updates).map((key) => ({
      op: 'replace',
      path: `/elements/${elementIndex}/${key}`,
      value: updates[key],
    }));

    return await applyOperations(formId, operations);
  },

  async remove_form_element({ formId, elementIndex }) {
    const operation = {
      op: 'remove',
      path: `/elements/${elementIndex}`,
    };

    return await applyOperations(formId, [operation]);
  },

  async move_form_element({ formId, fromIndex, toIndex }) {
    const operation = {
      op: 'move',
      from: `/elements/${fromIndex}`,
      path: `/elements/${toIndex}`,
    };

    return await applyOperations(formId, [operation]);
  },

  async update_form_settings({ formId, settings }) {
    const operations = Object.keys(settings).map((key) => ({
      op: 'replace',
      path: `/settings/${key}`,
      value: settings[key],
    }));

    return await applyOperations(formId, operations);
  },

  async add_form_translation({ formId, language, translations }) {
    const operation = {
      op: 'replace',
      path: `/translations/${language}`,
      value: translations,
    };

    return await applyOperations(formId, [operation]);
  },

  async get_form({ formId }) {
    return await apiRequest(`/wp-json/super-forms/v1/forms/${formId}`, 'GET');
  },

  async list_forms({ status = 'publish', limit = 20 }) {
    const params = new URLSearchParams({ status, number: limit });
    return await apiRequest(`/wp-json/super-forms/v1/forms?${params}`, 'GET');
  },

  async save_form_version({ formId, message = '' }) {
    return await apiRequest(`/wp-json/super-forms/v1/forms/${formId}/versions`, 'POST', {
      message,
    });
  },

  async revert_form_version({ formId, versionId }) {
    return await apiRequest(
      `/wp-json/super-forms/v1/forms/${formId}/revert/${versionId}`,
      'POST'
    );
  },
};

// Helper: Apply JSON Patch operations via REST API
async function applyOperations(formId, operations) {
  return await apiRequest(`/wp-json/super-forms/v1/forms/${formId}/operations`, 'POST', {
    operations,
  });
}

// Helper: Make WordPress REST API request
function apiRequest(endpoint, method = 'GET', body = null) {
  return new Promise((resolve, reject) => {
    const url = new URL(endpoint, WP_SITE_URL);
    const options = {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': WP_API_NONCE,
        Cookie: WP_COOKIE,
      },
    };

    const req = https.request(url, options, (res) => {
      let data = '';

      res.on('data', (chunk) => {
        data += chunk;
      });

      res.on('end', () => {
        try {
          const parsed = JSON.parse(data);
          if (res.statusCode >= 200 && res.statusCode < 300) {
            resolve(parsed);
          } else {
            reject(new Error(parsed.message || `HTTP ${res.statusCode}`));
          }
        } catch (e) {
          reject(new Error(`Invalid JSON response: ${data}`));
        }
      });
    });

    req.on('error', reject);

    if (body) {
      req.write(JSON.stringify(body));
    }

    req.end();
  });
}

// MCP Server Protocol
async function handleRequest(request) {
  const { method, params } = request;

  if (method === 'tools/list') {
    const schema = JSON.parse(
      fs.readFileSync(path.join(__dirname, 'super-forms-server.json'), 'utf8')
    );
    return { tools: schema.tools };
  }

  if (method === 'tools/call') {
    const { name, arguments: args } = params;

    if (!tools[name]) {
      throw new Error(`Unknown tool: ${name}`);
    }

    try {
      const result = await tools[name](args);
      return {
        content: [
          {
            type: 'text',
            text: JSON.stringify(result, null, 2),
          },
        ],
      };
    } catch (error) {
      return {
        content: [
          {
            type: 'text',
            text: `Error: ${error.message}`,
          },
        ],
        isError: true,
      };
    }
  }

  throw new Error(`Unknown method: ${method}`);
}

// STDIO transport (MCP standard)
process.stdin.setEncoding('utf8');

let buffer = '';

process.stdin.on('data', async (chunk) => {
  buffer += chunk;

  // Process line-delimited JSON messages
  const lines = buffer.split('\n');
  buffer = lines.pop() || '';

  for (const line of lines) {
    if (!line.trim()) continue;

    try {
      const request = JSON.parse(line);
      const response = await handleRequest(request);

      // Send response
      process.stdout.write(JSON.stringify(response) + '\n');
    } catch (error) {
      // Send error response
      process.stdout.write(
        JSON.stringify({
          error: {
            code: -32603,
            message: error.message,
          },
        }) + '\n'
      );
    }
  }
});

process.stdin.on('end', () => {
  process.exit(0);
});

// Handle graceful shutdown
process.on('SIGINT', () => process.exit(0));
process.on('SIGTERM', () => process.exit(0));

console.error('Super Forms MCP Server started');
