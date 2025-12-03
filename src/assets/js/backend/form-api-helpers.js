/**
 * Super Forms REST API JavaScript Helpers
 *
 * Lightweight wrappers around the REST API endpoints for form operations.
 * Replaces legacy AJAX handlers (save_form, delete_form, etc.)
 *
 * @since 6.5.0
 */

(function() {
	'use strict';

	// Ensure global namespace exists
	window.SUPER = window.SUPER || {};

	/**
	 * Form API Helper Class
	 */
	class FormAPI {
		constructor() {
			this.baseUrl = window.sfuiData?.restUrl || '/wp-json/super-forms/v1';
			this.nonce = window.sfuiData?.restNonce || '';
		}

		/**
		 * Get default headers for API requests
		 */
		getHeaders() {
			return {
				'Content-Type': 'application/json',
				'X-WP-Nonce': this.nonce
			};
		}

		/**
		 * Handle API response
		 */
		async handleResponse(response) {
			if (!response.ok) {
				const error = await response.json();
				throw new Error(error.message || 'API request failed');
			}
			return response.json();
		}

		/**
		 * Get a single form by ID
		 *
		 * @param {number} formId Form ID
		 * @returns {Promise} Form data
		 */
		async get(formId) {
			const response = await fetch(`${this.baseUrl}/forms/${formId}`, {
				method: 'GET',
				headers: this.getHeaders()
			});
			return this.handleResponse(response);
		}

		/**
		 * Query forms with filters
		 *
		 * @param {Object} params Query parameters (status, number, offset, orderby, order)
		 * @returns {Promise} Array of forms
		 */
		async query(params = {}) {
			const queryString = new URLSearchParams(params).toString();
			const url = `${this.baseUrl}/forms${queryString ? '?' + queryString : ''}`;

			const response = await fetch(url, {
				method: 'GET',
				headers: this.getHeaders()
			});
			return this.handleResponse(response);
		}

		/**
		 * Create a new form
		 *
		 * @param {Object} data Form data (name, status, elements, settings, translations)
		 * @returns {Promise} Created form
		 */
		async create(data) {
			const response = await fetch(`${this.baseUrl}/forms`, {
				method: 'POST',
				headers: this.getHeaders(),
				body: JSON.stringify(data)
			});
			return this.handleResponse(response);
		}

		/**
		 * Update a form using JSON Patch operations
		 *
		 * @param {number} formId Form ID
		 * @param {Array} operations JSON Patch operations (RFC 6902)
		 * @returns {Promise} Updated form
		 */
		async applyOperations(formId, operations) {
			const response = await fetch(`${this.baseUrl}/forms/${formId}/operations`, {
				method: 'POST',
				headers: this.getHeaders(),
				body: JSON.stringify({ operations })
			});
			return this.handleResponse(response);
		}

		/**
		 * Update a form (full replacement)
		 *
		 * @param {number} formId Form ID
		 * @param {Object} data Form data to update
		 * @returns {Promise} Updated form
		 */
		async update(formId, data) {
			const response = await fetch(`${this.baseUrl}/forms/${formId}`, {
				method: 'PUT',
				headers: this.getHeaders(),
				body: JSON.stringify(data)
			});
			return this.handleResponse(response);
		}

		/**
		 * Delete a form
		 *
		 * @param {number} formId Form ID
		 * @returns {Promise} Deletion result
		 */
		async delete(formId) {
			const response = await fetch(`${this.baseUrl}/forms/${formId}`, {
				method: 'DELETE',
				headers: this.getHeaders()
			});
			return this.handleResponse(response);
		}

		/**
		 * Duplicate a form
		 *
		 * @param {number} formId Form ID to duplicate
		 * @returns {Promise} New form data
		 */
		async duplicate(formId) {
			const response = await fetch(`${this.baseUrl}/forms/${formId}/duplicate`, {
				method: 'POST',
				headers: this.getHeaders()
			});
			return this.handleResponse(response);
		}

		/**
		 * Search forms
		 *
		 * @param {string} query Search query
		 * @param {Object} params Additional parameters
		 * @returns {Promise} Array of matching forms
		 */
		async search(query, params = {}) {
			const searchParams = new URLSearchParams({ query, ...params });
			const response = await fetch(`${this.baseUrl}/forms/search?${searchParams}`, {
				method: 'GET',
				headers: this.getHeaders()
			});
			return this.handleResponse(response);
		}

		/**
		 * Archive a form (soft delete)
		 *
		 * @param {number} formId Form ID
		 * @returns {Promise} Result
		 */
		async archive(formId) {
			const response = await fetch(`${this.baseUrl}/forms/${formId}/archive`, {
				method: 'POST',
				headers: this.getHeaders()
			});
			return this.handleResponse(response);
		}

		/**
		 * Restore an archived form
		 *
		 * @param {number} formId Form ID
		 * @returns {Promise} Result
		 */
		async restore(formId) {
			const response = await fetch(`${this.baseUrl}/forms/${formId}/restore`, {
				method: 'POST',
				headers: this.getHeaders()
			});
			return this.handleResponse(response);
		}

		/**
		 * Export a form
		 *
		 * @param {number} formId Form ID
		 * @returns {Promise} Export data
		 */
		async export(formId) {
			const response = await fetch(`${this.baseUrl}/forms/${formId}/export`, {
				method: 'GET',
				headers: this.getHeaders()
			});
			return this.handleResponse(response);
		}

		/**
		 * Import a form
		 *
		 * @param {Object} data Form export data
		 * @returns {Promise} Imported form
		 */
		async import(data) {
			const response = await fetch(`${this.baseUrl}/forms/import`, {
				method: 'POST',
				headers: this.getHeaders(),
				body: JSON.stringify(data)
			});
			return this.handleResponse(response);
		}

		/**
		 * Bulk operations on multiple forms
		 *
		 * @param {string} operation Operation type (delete, archive, restore, change_status)
		 * @param {Array} formIds Array of form IDs
		 * @param {Object} params Additional parameters (e.g., status for change_status)
		 * @returns {Promise} Bulk operation results
		 */
		async bulk(operation, formIds, params = {}) {
			const response = await fetch(`${this.baseUrl}/forms/bulk`, {
				method: 'POST',
				headers: this.getHeaders(),
				body: JSON.stringify({ operation, form_ids: formIds, ...params })
			});
			return this.handleResponse(response);
		}

		/**
		 * Get form versions
		 *
		 * @param {number} formId Form ID
		 * @returns {Promise} Array of versions
		 */
		async getVersions(formId) {
			const response = await fetch(`${this.baseUrl}/forms/${formId}/versions`, {
				method: 'GET',
				headers: this.getHeaders()
			});
			return this.handleResponse(response);
		}

		/**
		 * Create a version snapshot
		 *
		 * @param {number} formId Form ID
		 * @param {string} label Version label
		 * @returns {Promise} Created version
		 */
		async createVersion(formId, label) {
			const response = await fetch(`${this.baseUrl}/forms/${formId}/versions`, {
				method: 'POST',
				headers: this.getHeaders(),
				body: JSON.stringify({ label })
			});
			return this.handleResponse(response);
		}

		/**
		 * Revert to a previous version
		 *
		 * @param {number} formId Form ID
		 * @param {number} versionId Version ID to revert to
		 * @returns {Promise} Reverted form
		 */
		async revertToVersion(formId, versionId) {
			const response = await fetch(`${this.baseUrl}/forms/${formId}/revert/${versionId}`, {
				method: 'POST',
				headers: this.getHeaders()
			});
			return this.handleResponse(response);
		}
	}

	// Create singleton instance
	window.SUPER.FormAPI = new FormAPI();

	/**
	 * Legacy compatibility layer
	 * Provides backward compatibility for old AJAX-based code
	 */
	window.SUPER.legacySaveForm = async function(formId, data) {
		console.warn('DEPRECATED: SUPER.legacySaveForm() is deprecated. Use SUPER.FormAPI.update() or SUPER.FormAPI.applyOperations() instead.');
		return window.SUPER.FormAPI.update(formId, data);
	};

	window.SUPER.legacyDeleteForm = async function(formId) {
		console.warn('DEPRECATED: SUPER.legacyDeleteForm() is deprecated. Use SUPER.FormAPI.delete() instead.');
		return window.SUPER.FormAPI.delete(formId);
	};

})();
