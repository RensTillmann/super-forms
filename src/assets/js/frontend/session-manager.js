/**
 * Super Forms Session Manager (Vanilla JS)
 *
 * Handles progressive form saving, session creation, and recovery.
 * Creates sessions on first field interaction and auto-saves on blur/change.
 *
 * Key features:
 * - Zero jQuery dependency
 * - Diff tracking: only sends changed fields (not entire form)
 * - AbortController: cancels pending requests before new ones
 * - Debounced saves: 500ms delay prevents request spam
 * - LocalStorage: client-side session persistence
 *
 * @package Super_Forms
 * @since 6.5.0
 */
/* global super_session_i18n, super_common_i18n */
(function() {
	'use strict';

	var SUPER_SessionManager = {

		// Configuration
		config: {
			autoSaveDelay: 500,      // ms debounce for auto-save
			recoveryCheckDelay: 100, // ms after init to check recovery
			clientTokenKey: 'super_client_token',
			maxSessionAge: 24 * 60 * 60 * 1000 // 24 hours in ms
		},

		// Per-form state storage: Map<formId, SessionState>
		sessions: {},

		/**
		 * Initialize session management
		 */
		init: function() {
			var self = this;
			var forms = document.querySelectorAll('.super-form');

			forms.forEach(function(form) {
				self.initForm(form);
			});

			// Listen for dynamically added forms
			document.addEventListener('super:form:loaded', function(e) {
				if (e.detail && e.detail.form) {
					self.initForm(e.detail.form);
				}
			});

			// Clear session on successful submission
			document.addEventListener('super:form:submitted', function(e) {
				if (e.detail && e.detail.form_id) {
					self.clearSession(e.detail.form_id);
				}
			});
		},

		/**
		 * Initialize session management for a single form
		 *
		 * @param {HTMLElement} form The form element
		 */
		initForm: function(form) {
			var self = this;
			var formId = form.dataset.id;

			if (!formId || this.sessions[formId]) {
				return; // No ID or already initialized
			}

			// Initialize state for this form
			this.sessions[formId] = {
				sessionKey: null,
				lastSavedData: {},
				saveTimer: null,
				abortController: null,
				isCreating: false,
				recoveryShown: false
			};

			// Check for recoverable session
			setTimeout(function() {
				self.checkRecovery(form, formId);
			}, this.config.recoveryCheckDelay);

			// Bind events
			this.bindEvents(form, formId);
		},

		/**
		 * Bind form events
		 *
		 * @param {HTMLElement} form The form element
		 * @param {string} formId The form ID
		 */
		bindEvents: function(form, formId) {
			var self = this;

			// Create session on first field focus (using capture for early handling)
			var focusHandler = function(e) {
				if (self.isFormField(e.target)) {
					self.ensureSession(form, formId, e.target);
					// Remove after first focus - session only needs to be created once
					form.removeEventListener('focusin', focusHandler, true);
				}
			};
			form.addEventListener('focusin', focusHandler, true);

			// Auto-save on blur (debounced)
			form.addEventListener('focusout', function(e) {
				if (self.isFormField(e.target)) {
					self.queueSave(form, formId);
				}
			});

			// Auto-save on change (for selects, checkboxes, radios)
			form.addEventListener('change', function(e) {
				if (self.isFormField(e.target)) {
					self.queueSave(form, formId);
				}
			});
		},

		/**
		 * Check if element is a trackable form field
		 *
		 * @param {HTMLElement} el The element to check
		 * @returns {boolean}
		 */
		isFormField: function(el) {
			if (!el || !el.tagName) return false;

			var validTags = ['INPUT', 'TEXTAREA', 'SELECT'];
			if (validTags.indexOf(el.tagName) === -1) return false;

			// Skip system fields and non-data fields
			var name = el.name || '';
			if (name.indexOf('super_') === 0) return false;
			if (el.type === 'hidden') return false;
			if (el.type === 'submit') return false;
			if (el.type === 'button') return false;
			if (el.type === 'file') return false; // Files handled separately

			return true;
		},

		/**
		 * Ensure session exists, create if needed
		 *
		 * @param {HTMLElement} form The form element
		 * @param {string} formId The form ID
		 * @param {HTMLElement} field The focused field
		 */
		ensureSession: function(form, formId, field) {
			var state = this.sessions[formId];
			if (!state || state.sessionKey || state.isCreating) return;

			// Check localStorage for existing valid session
			var stored = this.getStoredSession(formId);
			if (stored && stored.sessionKey) {
				state.sessionKey = stored.sessionKey;
				state.lastSavedData = stored.formData || {};
				this.addSessionField(form, stored.sessionKey);
				return;
			}

			// Create new session
			this.createSession(form, formId, field);
		},

		/**
		 * Create new session on server
		 *
		 * @param {HTMLElement} form The form element
		 * @param {string} formId The form ID
		 * @param {HTMLElement} field The first focused field
		 */
		createSession: function(form, formId, field) {
			var self = this;
			var state = this.sessions[formId];
			var ajaxUrl = this.getAjaxUrl();

			if (!ajaxUrl) return;

			state.isCreating = true;

			var fieldName = field.name || '';
			if (!fieldName && field.closest) {
				var wrapper = field.closest('.super-shortcode-field');
				if (wrapper) fieldName = wrapper.dataset.name || '';
			}

			fetch(ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: this.buildQuery({
					action: 'super_create_session',
					form_id: formId,
					field_name: fieldName,
					page_url: window.location.href,
					client_token: this.getClientToken(),
					fingerprint: this.generateFingerprint()
				})
			})
			.then(function(response) { return response.json(); })
			.then(function(data) {
				state.isCreating = false;
				if (data.success && data.data && data.data.session_key) {
					state.sessionKey = data.data.session_key;
					state.lastSavedData = {};
					self.storeSession(formId, state.sessionKey, {});
					self.addSessionField(form, state.sessionKey);
				}
			})
			.catch(function() {
				state.isCreating = false;
				// Fail silently - form still works without sessions
			});
		},

		/**
		 * Queue auto-save with debounce
		 *
		 * @param {HTMLElement} form The form element
		 * @param {string} formId The form ID
		 */
		queueSave: function(form, formId) {
			var self = this;
			var state = this.sessions[formId];

			if (!state || !state.sessionKey) return;

			// Clear pending timer
			if (state.saveTimer) {
				clearTimeout(state.saveTimer);
			}

			// Cancel pending request
			if (state.abortController) {
				state.abortController.abort();
				state.abortController = null;
			}

			// Debounce: wait 500ms before saving
			state.saveTimer = setTimeout(function() {
				self.saveChangedFields(form, formId);
			}, this.config.autoSaveDelay);
		},

		/**
		 * Save only changed fields (diff tracking)
		 *
		 * @param {HTMLElement} form The form element
		 * @param {string} formId The form ID
		 */
		saveChangedFields: function(form, formId) {
			var self = this;
			var state = this.sessions[formId];
			var ajaxUrl = this.getAjaxUrl();

			if (!state || !state.sessionKey || !ajaxUrl) return;

			// Collect current form data
			var currentData = this.collectFormData(form);

			// Calculate diff against last saved
			var changes = {};
			var hasChanges = false;
			var key;

			// Find changed/new fields
			for (key in currentData) {
				if (Object.prototype.hasOwnProperty.call(currentData, key)) {
					if (state.lastSavedData[key] !== currentData[key]) {
						changes[key] = currentData[key];
						hasChanges = true;
					}
				}
			}

			// Find removed/cleared fields
			for (key in state.lastSavedData) {
				if (Object.prototype.hasOwnProperty.call(state.lastSavedData, key)) {
					if (!(key in currentData)) {
						changes[key] = '';
						hasChanges = true;
					}
				}
			}

			if (!hasChanges) return;

			// Create AbortController for this request
			state.abortController = new AbortController();

			fetch(ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: this.buildQuery({
					action: 'super_auto_save_session',
					session_key: state.sessionKey,
					form_id: formId,
					changes: JSON.stringify(changes),
					client_token: this.getClientToken()
				}),
				signal: state.abortController.signal
			})
			.then(function(response) { return response.json(); })
			.then(function(data) {
				if (data.success) {
					// Update last saved state with changes
					for (var k in changes) {
						if (Object.prototype.hasOwnProperty.call(changes, k)) {
							if (changes[k] === '') {
								delete state.lastSavedData[k];
							} else {
								state.lastSavedData[k] = changes[k];
							}
						}
					}
					self.storeSession(formId, state.sessionKey, state.lastSavedData);
				}
			})
			.catch(function(err) {
				// Ignore abort errors (expected when user types fast)
				if (err.name !== 'AbortError') {
					console.warn('[Super Forms] Session save failed:', err);
				}
			});
		},

		/**
		 * Collect all form field data
		 *
		 * @param {HTMLElement} form The form element
		 * @returns {Object} Field name -> value mapping
		 */
		collectFormData: function(form) {
			var data = {};
			var inputs = form.querySelectorAll('input, textarea, select');
			var self = this;

			inputs.forEach(function(input) {
				if (!self.isFormField(input)) return;

				var name = input.name;
				if (!name && input.closest) {
					var wrapper = input.closest('.super-shortcode-field');
					if (wrapper) name = wrapper.dataset.name;
				}
				if (!name) return;

				if (input.type === 'checkbox') {
					data[name] = input.checked ? (input.value || '1') : '';
				} else if (input.type === 'radio') {
					if (input.checked) {
						data[name] = input.value;
					}
				} else {
					data[name] = input.value;
				}
			});

			return data;
		},

		/**
		 * Check for recoverable session
		 *
		 * @param {HTMLElement} form The form element
		 * @param {string} formId The form ID
		 */
		checkRecovery: function(form, formId) {
			var self = this;
			var stored = this.getStoredSession(formId);
			var ajaxUrl = this.getAjaxUrl();

			if (!stored || !stored.sessionKey || !ajaxUrl) return;

			// Check if session is too old
			if (stored.timestamp && (Date.now() - stored.timestamp > this.config.maxSessionAge)) {
				this.clearStoredSession(formId);
				return;
			}

			// Check if there's data to recover
			if (!stored.formData || Object.keys(stored.formData).length === 0) {
				return;
			}

			// Validate session still exists on server
			fetch(ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: this.buildQuery({
					action: 'super_check_session_recovery',
					form_id: formId,
					client_token: this.getClientToken(),
					stored_session: stored.sessionKey
				})
			})
			.then(function(response) { return response.json(); })
			.then(function(data) {
				if (data.success && data.data && data.data.has_session) {
					self.showRecoveryBanner(form, formId, data.data);
				} else {
					// Session invalid/expired on server
					self.clearStoredSession(formId);
				}
			})
			.catch(function() {
				// Network error - don't show recovery
			});
		},

		/**
		 * Show recovery banner
		 *
		 * @param {HTMLElement} form The form element
		 * @param {string} formId The form ID
		 * @param {Object} sessionData Session data from server
		 */
		showRecoveryBanner: function(form, formId, sessionData) {
			var self = this;
			var state = this.sessions[formId];

			if (state.recoveryShown) return;
			state.recoveryShown = true;

			// Get i18n strings
			var i18n = this.getI18n();

			// Format time ago
			var timeAgo = 'earlier';
			if (sessionData.last_saved) {
				var lastSaved = new Date(sessionData.last_saved.replace(' ', 'T'));
				timeAgo = this.formatTimeAgo(lastSaved);
			}

			// Create banner element
			var banner = document.createElement('div');
			banner.className = 'super-session-recovery';
			banner.innerHTML =
				'<div class="super-session-recovery-content">' +
					'<span class="super-session-recovery-icon">💾</span>' +
					'<span class="super-session-recovery-text">' +
						i18n.message.replace('{time}', timeAgo) +
					'</span>' +
					'<button type="button" class="super-session-recovery-restore">' + i18n.restore + '</button>' +
					'<button type="button" class="super-session-recovery-dismiss">' + i18n.dismiss + '</button>' +
				'</div>';

			// Insert before form
			form.parentNode.insertBefore(banner, form);

			// Bind restore button
			banner.querySelector('.super-session-recovery-restore').addEventListener('click', function() {
				self.restoreSession(form, formId, sessionData.session_key, banner);
			});

			// Bind dismiss button
			banner.querySelector('.super-session-recovery-dismiss').addEventListener('click', function() {
				self.dismissSession(form, formId, sessionData.session_key, banner);
			});
		},

		/**
		 * Restore session data to form
		 *
		 * @param {HTMLElement} form The form element
		 * @param {string} formId The form ID
		 * @param {string} sessionKey The session key
		 * @param {HTMLElement} banner The recovery banner
		 */
		restoreSession: function(form, formId, sessionKey, banner) {
			var self = this;
			var state = this.sessions[formId];
			var ajaxUrl = this.getAjaxUrl();

			fetch(ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: this.buildQuery({
					action: 'super_resume_session',
					session_key: sessionKey,
					client_token: this.getClientToken()
				})
			})
			.then(function(response) { return response.json(); })
			.then(function(data) {
				if (data.success && data.data && data.data.form_data) {
					// Update state
					state.sessionKey = sessionKey;
					state.lastSavedData = data.data.form_data;
					self.storeSession(formId, sessionKey, data.data.form_data);
					self.addSessionField(form, sessionKey);

					// Populate form
					self.populateForm(form, data.data.form_data);

					// Remove banner
					self.removeBanner(banner);

					// Fire event
					form.dispatchEvent(new CustomEvent('super:session:restored', {
						detail: { formId: formId, data: data.data.form_data }
					}));
				}
			})
			.catch(function(err) {
				console.warn('[Super Forms] Session restore failed:', err);
			});
		},

		/**
		 * Dismiss session (user chose to start fresh)
		 *
		 * @param {HTMLElement} form The form element
		 * @param {string} formId The form ID
		 * @param {string} sessionKey The session key
		 * @param {HTMLElement} banner The recovery banner
		 */
		dismissSession: function(form, formId, sessionKey, banner) {
			var ajaxUrl = this.getAjaxUrl();

			// Tell server to mark as dismissed
			if (ajaxUrl) {
				fetch(ajaxUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: this.buildQuery({
						action: 'super_dismiss_session',
						session_key: sessionKey,
						client_token: this.getClientToken()
					})
				}).catch(function() {});
			}

			// Clear local storage
			this.clearStoredSession(formId);

			// Remove banner
			this.removeBanner(banner);
		},

		/**
		 * Populate form with saved data
		 *
		 * @param {HTMLElement} form The form element
		 * @param {Object} formData The saved form data
		 */
		populateForm: function(form, formData) {
			if (!formData || typeof formData !== 'object') return;

			for (var fieldName in formData) {
				if (!Object.prototype.hasOwnProperty.call(formData, fieldName)) continue;

				var value = formData[fieldName];
				var fields = form.querySelectorAll('[name="' + fieldName + '"]');

				// Try data-name wrapper if not found by name
				if (!fields.length) {
					var wrapper = form.querySelector('.super-shortcode-field[data-name="' + fieldName + '"]');
					if (wrapper) {
						fields = wrapper.querySelectorAll('input, textarea, select');
					}
				}

				fields.forEach(function(field) {
					if (field.type === 'checkbox') {
						field.checked = !!value;
					} else if (field.type === 'radio') {
						field.checked = (field.value === value);
					} else {
						field.value = value;
					}

					// Trigger change for conditional logic
					field.dispatchEvent(new Event('change', { bubbles: true }));
				});
			}
		},

		/**
		 * Clear session after successful submission
		 *
		 * @param {string} formId The form ID
		 */
		clearSession: function(formId) {
			var state = this.sessions[formId];
			if (state) {
				state.sessionKey = null;
				state.lastSavedData = {};
				if (state.saveTimer) {
					clearTimeout(state.saveTimer);
				}
				if (state.abortController) {
					state.abortController.abort();
				}
			}
			this.clearStoredSession(formId);

			// Remove hidden field from form
			var form = document.querySelector('.super-form[data-id="' + formId + '"]');
			if (form) {
				var field = form.querySelector('input[name="super_session_key"]');
				if (field) field.remove();
			}
		},

		/**
		 * Add hidden session field to form
		 *
		 * @param {HTMLElement} form The form element
		 * @param {string} sessionKey The session key
		 */
		addSessionField: function(form, sessionKey) {
			var existing = form.querySelector('input[name="super_session_key"]');
			if (existing) {
				existing.value = sessionKey;
			} else {
				var input = document.createElement('input');
				input.type = 'hidden';
				input.name = 'super_session_key';
				input.value = sessionKey;
				form.appendChild(input);
			}
		},

		/**
		 * Remove recovery banner with animation
		 *
		 * @param {HTMLElement} banner The banner element
		 */
		removeBanner: function(banner) {
			banner.style.transition = 'opacity 0.3s ease-out';
			banner.style.opacity = '0';
			setTimeout(function() {
				if (banner.parentNode) {
					banner.parentNode.removeChild(banner);
				}
			}, 300);
		},

		// ==========================================
		// LocalStorage Helpers
		// ==========================================

		getStorageKey: function(formId) {
			return 'super_session_' + formId;
		},

		getStoredSession: function(formId) {
			try {
				var key = this.getStorageKey(formId);
				var data = localStorage.getItem(key);
				return data ? JSON.parse(data) : null;
			} catch (_e) {
				return null;
			}
		},

		storeSession: function(formId, sessionKey, formData) {
			try {
				var key = this.getStorageKey(formId);
				localStorage.setItem(key, JSON.stringify({
					sessionKey: sessionKey,
					formData: formData,
					timestamp: Date.now()
				}));
			} catch (_e) {
				// localStorage full or disabled
			}
		},

		clearStoredSession: function(formId) {
			try {
				localStorage.removeItem(this.getStorageKey(formId));
			} catch (_e) { /* empty */ }
		},

		// ==========================================
		// Utility Helpers
		// ==========================================

		/**
		 * Get AJAX URL from localized data
		 *
		 * @returns {string|null}
		 */
		getAjaxUrl: function() {
			if (typeof super_common_i18n !== 'undefined' && super_common_i18n.ajaxurl) {
				return super_common_i18n.ajaxurl;
			}
			if (typeof super_session_i18n !== 'undefined' && super_session_i18n.ajaxurl) {
				return super_session_i18n.ajaxurl;
			}
			if (typeof ajaxurl !== 'undefined') {
				return ajaxurl;
			}
			return null;
		},

		/**
		 * Get i18n strings
		 *
		 * @returns {Object}
		 */
		getI18n: function() {
			var i18n = (typeof super_session_i18n !== 'undefined') ? super_session_i18n : {};
			return {
				message: i18n.recovery_message || 'You have unsaved form data from {time}.',
				restore: i18n.restore_button || 'Restore',
				dismiss: i18n.dismiss_button || 'Start Fresh'
			};
		},

		/**
		 * Build URL-encoded query string
		 *
		 * @param {Object} params Key-value pairs
		 * @returns {string}
		 */
		buildQuery: function(params) {
			var parts = [];
			for (var key in params) {
				if (Object.prototype.hasOwnProperty.call(params, key)) {
					parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(params[key]));
				}
			}
			return parts.join('&');
		},

		/**
		 * Get or create client token (UUID per browser)
		 *
		 * @returns {string}
		 */
		getClientToken: function() {
			var token;
			try {
				token = localStorage.getItem(this.config.clientTokenKey);
			} catch (_e) {
				token = null;
			}

			if (!token) {
				// Generate UUID v4
				if (typeof crypto !== 'undefined' && crypto.randomUUID) {
					token = crypto.randomUUID();
				} else {
					token = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
						var r = Math.random() * 16 | 0;
						var v = (c === 'x') ? r : (r & 0x3 | 0x8);
						return v.toString(16);
					});
				}
				try {
					localStorage.setItem(this.config.clientTokenKey, token);
				} catch (_e) { /* empty */ }
			}

			return token;
		},

		/**
		 * Generate browser fingerprint for spam detection metadata
		 *
		 * @returns {string}
		 */
		generateFingerprint: function() {
			var components = [
				navigator.userAgent || '',
				navigator.language || '',
				(screen.width || 0) + 'x' + (screen.height || 0),
				new Date().getTimezoneOffset(),
				navigator.platform || ''
			];

			// Simple hash
			var str = components.join('|');
			var hash = 0;
			for (var i = 0; i < str.length; i++) {
				var char = str.charCodeAt(i);
				hash = ((hash << 5) - hash) + char;
				hash = hash & hash;
			}
			return 'fp_' + Math.abs(hash).toString(36);
		},

		/**
		 * Format time ago string
		 *
		 * @param {Date} date The date to format
		 * @returns {string}
		 */
		formatTimeAgo: function(date) {
			var seconds = Math.floor((Date.now() - date.getTime()) / 1000);

			if (seconds < 60) return 'just now';
			if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
			if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
			return Math.floor(seconds / 86400) + ' days ago';
		}
	};

	// Initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function() {
			SUPER_SessionManager.init();
		});
	} else {
		SUPER_SessionManager.init();
	}

	// Expose globally
	window.SUPER_SessionManager = SUPER_SessionManager;

})();
