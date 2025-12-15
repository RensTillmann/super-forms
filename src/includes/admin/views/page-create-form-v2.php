<?php
/**
 * Admin View: Create Form V2 (React-based Form Builder)
 *
 * @package SUPER_Forms/Admin/Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style>
	/* Hide WordPress admin chrome for fullscreen builder experience */
	#adminmenumain,
	#wpadminbar,
	#wpfooter,
	.notice,
	.updated,
	.error,
	.update-nag { display: none !important; }
	#wpcontent { margin-left: 0 !important; }
	#wpbody { padding-top: 0 !important; }
	html.wp-toolbar { padding-top: 0 !important; }

	/* Parent page styles - minimal, just size the iframe */
	.super-create-form-v2 {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		margin: 0;
		padding: 0;
	}

	#sfui-builder-iframe {
		width: 100%;
		height: 100%;
		border: 0;
		display: block;
	}

	/* Loading indicator - grey background only (spinner moved to React) */
	#sfui-loading-indicator {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: #f3f4f6;
		z-index: 1;
		/* No transition - instant removal */
	}
</style>

<div class="super-create-form-v2">
	<!-- Loading indicator - just grey background, spinner in React -->
	<div id="sfui-loading-indicator" data-testid="loading-background"></div>

	<!-- iframe for isolated Form Builder V2 - Gutenberg-style isolation -->
	<iframe
		id="sfui-builder-iframe"
		title="Form Builder"
		data-testid="form-builder-iframe"
	></iframe>
</div>

<script>
(function() {
	'use strict';

	// Data to pass to iframe React app
	const sfuiData = {
		currentPage: '<?php echo esc_js( sanitize_text_field( $_GET['page'] ?? '' ) ); ?>',
		formId: <?php echo absint( $form_id ); ?>,
		forms: <?php echo wp_json_encode( $forms ); ?>,
		translations: <?php echo wp_json_encode( $translations ); ?>,
		settings: <?php echo wp_json_encode( $settings ); ?>,
		ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
		restNonce: '<?php echo wp_create_nonce( 'wp_rest' ); ?>',
		restUrl: '<?php echo esc_url( rest_url( 'super-forms/v1' ) ); ?>',
		currentUserEmail: '<?php echo esc_js( wp_get_current_user()->user_email ); ?>',
		i18n: {
			save: '<?php echo esc_js( __( 'Save', 'super-forms' ) ); ?>',
			saving: '<?php echo esc_js( __( 'Saving...', 'super-forms' ) ); ?>',
			saved: '<?php echo esc_js( __( 'Saved!', 'super-forms' ) ); ?>',
			error: '<?php echo esc_js( __( 'Error saving', 'super-forms' ) ); ?>',
			preview: '<?php echo esc_js( __( 'Preview', 'super-forms' ) ); ?>',
			publish: '<?php echo esc_js( __( 'Publish', 'super-forms' ) ); ?>',
			addElement: '<?php echo esc_js( __( 'Add Element', 'super-forms' ) ); ?>',
			deleteElement: '<?php echo esc_js( __( 'Delete Element', 'super-forms' ) ); ?>',
			duplicateElement: '<?php echo esc_js( __( 'Duplicate Element', 'super-forms' ) ); ?>',
			elementSettings: '<?php echo esc_js( __( 'Element Settings', 'super-forms' ) ); ?>',
			formSettings: '<?php echo esc_js( __( 'Form Settings', 'super-forms' ) ); ?>',
			undo: '<?php echo esc_js( __( 'Undo', 'super-forms' ) ); ?>',
			redo: '<?php echo esc_js( __( 'Redo', 'super-forms' ) ); ?>'
		},
		navigation: {
			dashboard: '<?php echo esc_url( admin_url() ); ?>',
			forms: '<?php echo esc_url( admin_url( 'admin.php?page=super_forms_list' ) ); ?>',
			entries: '<?php echo esc_url( admin_url( 'edit.php?post_type=super_contact_entry' ) ); ?>',
			settings: '<?php echo esc_url( admin_url( 'admin.php?page=super_settings' ) ); ?>'
		},
		// WordPress admin sidebar width (currently hidden via CSS, so 0)
		// If sidebar is shown later: 160px (expanded) or 36px (collapsed/folded)
		sidebarWidth: 0
	};

	// Get asset URLs for loading in iframe
	// Note: SUPER_PLUGIN_FILE is already a URL (plugin_dir_url), not a file path
	// Cache-busting: using timestamp during development, use SUPER_VERSION in production
	const adminCssUrl = '<?php echo esc_url( SUPER_PLUGIN_FILE . 'assets/css/backend/admin.css?v=' . time() ); ?>';
	const adminJsUrl = '<?php echo esc_url( SUPER_PLUGIN_FILE . 'assets/js/backend/admin.js?v=' . time() ); ?>';
	const wpHooksUrl = '<?php echo esc_url( includes_url( 'js/dist/hooks.min.js' ) ); ?>';
	const wpI18nUrl = '<?php echo esc_url( includes_url( 'js/dist/i18n.min.js' ) ); ?>';
	const wpApiFetchUrl = '<?php echo esc_url( includes_url( 'js/dist/api-fetch.min.js' ) ); ?>';

	// Initialize iframe when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initIframe);
	} else {
		initIframe();
	}

	function initIframe() {
		const iframe = document.getElementById('sfui-builder-iframe');
		if (!iframe) {
			console.error('SFUI: iframe not found');
			return;
		}

		// CRITICAL: Attach load listener BEFORE setting src to avoid race condition
		// Even though about:blank loads synchronously, this ensures reliability
		iframe.addEventListener('load', function onIframeLoad() {
			iframe.removeEventListener('load', onIframeLoad);

			const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
			const iframeWin = iframe.contentWindow;

			// Create HTML document structure using DOM methods (safer than document.write)
			iframeDoc.open();
			iframeDoc.write('<!DOCTYPE html><html lang="en"></html>');
			iframeDoc.close();

			const html = iframeDoc.documentElement;
			const head = iframeDoc.createElement('head');
			const body = iframeDoc.createElement('body');

			// Head elements
			const metaCharset = iframeDoc.createElement('meta');
			metaCharset.setAttribute('charset', 'UTF-8');
			head.appendChild(metaCharset);

			const metaViewport = iframeDoc.createElement('meta');
			metaViewport.setAttribute('name', 'viewport');
			metaViewport.setAttribute('content', 'width=device-width, initial-scale=1.0');
			head.appendChild(metaViewport);

			const title = iframeDoc.createElement('title');
			title.textContent = 'Form Builder';
			head.appendChild(title);

			// CSS link
			const cssLink = iframeDoc.createElement('link');
			cssLink.rel = 'stylesheet';
			cssLink.href = adminCssUrl;
			head.appendChild(cssLink);

			// Body setup
			body.id = 'sfui-admin-root';

			// Mount point div
			const mountDiv = iframeDoc.createElement('div');
			mountDiv.id = 'sfui-admin-mount';
			mountDiv.className = 'sfui-admin-container';
			mountDiv.setAttribute('data-testid', 'sfui-admin-root');
			body.appendChild(mountDiv);

			// Load wp.hooks first (required by wp.i18n)
			const wpHooksScript = iframeDoc.createElement('script');
			wpHooksScript.src = wpHooksUrl;
			wpHooksScript.addEventListener('error', function(e) {
				showLoadError('wp.hooks', e);
			});
			body.appendChild(wpHooksScript);

			// Wait for wp.hooks to load before loading wp.i18n
			wpHooksScript.addEventListener('load', function() {
				// Load wp.i18n (depends on wp.hooks, required by wp.apiFetch)
				const wpI18nScript = iframeDoc.createElement('script');
				wpI18nScript.src = wpI18nUrl;
				wpI18nScript.addEventListener('error', function(e) {
					showLoadError('wp.i18n', e);
				});
				body.appendChild(wpI18nScript);

				// Wait for wp.i18n to load before loading wp.apiFetch
				wpI18nScript.addEventListener('load', function() {
					// wp.apiFetch script (depends on wp.i18n)
					const wpApiFetchScript = iframeDoc.createElement('script');
					wpApiFetchScript.src = wpApiFetchUrl;
					wpApiFetchScript.addEventListener('error', function(e) {
						showLoadError('wp.apiFetch', e);
					});
					body.appendChild(wpApiFetchScript);

					// Wait for wp.apiFetch to load before continuing
					wpApiFetchScript.addEventListener('load', function() {
						// Data script
						const dataScript = iframeDoc.createElement('script');
						dataScript.textContent = 'window.sfuiData = ' + JSON.stringify(sfuiData) + ';';
						body.appendChild(dataScript);

						// Admin bundle script
						const adminScript = iframeDoc.createElement('script');
						adminScript.src = adminJsUrl;
						adminScript.addEventListener('error', function(e) {
							showLoadError('admin bundle (admin.js)', e);
						});
						adminScript.addEventListener('load', function() {
							// Admin script loaded - React will show skeleton then full UI
							// Remove parent grey background immediately
							const loadingIndicator = document.getElementById('sfui-loading-indicator');
							if (loadingIndicator) {
								loadingIndicator.remove();
							}
						});
						body.appendChild(adminScript);
					});
				});
			});

			// Append to document
			html.appendChild(head);
			html.appendChild(body);

			// Set up parent-iframe communication
			setupCommunicationBridge(iframeWin);
		});

		// Trigger initial load (listener attached above, so no race condition)
		iframe.src = 'about:blank';
	}

	function showLoadError(scriptName, error) {
		console.error('SFUI: Failed to load ' + scriptName, error);

		const iframe = document.getElementById('sfui-builder-iframe');
		if (!iframe || !iframe.contentDocument) return;

		const iframeDoc = iframe.contentDocument;
		const errorDiv = iframeDoc.createElement('div');
		errorDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); ' +
			'background: #fff; border: 2px solid #dc2626; border-radius: 8px; padding: 24px; ' +
			'max-width: 500px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; ' +
			'box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 999999;';

		errorDiv.innerHTML = '<h2 style="margin: 0 0 12px 0; color: #dc2626; font-size: 18px;">Failed to Load Form Builder</h2>' +
			'<p style="margin: 0 0 16px 0; color: #374151; line-height: 1.5;">The Form Builder could not initialize because <strong>' + scriptName + '</strong> failed to load.</p>' +
			'<p style="margin: 0 0 16px 0; color: #6b7280; font-size: 14px;">This usually happens due to:</p>' +
			'<ul style="margin: 0 0 16px 0; padding-left: 20px; color: #6b7280; font-size: 14px;">' +
			'<li>Plugin conflicts blocking WordPress core scripts</li>' +
			'<li>Server configuration issues</li>' +
			'<li>Ad blockers or security extensions</li>' +
			'</ul>' +
			'<button onclick="window.location.reload()" style="background: #2563eb; color: white; border: none; ' +
			'padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">Reload Page</button>';

		iframeDoc.body.appendChild(errorDiv);
	}

	function setupCommunicationBridge(iframeWindow) {
		// Listen for messages from iframe
		window.addEventListener('message', function(event) {
			// Verify message is from our iframe (same origin)
			if (event.source !== iframeWindow) {
				return;
			}

			const message = event.data;

			if (!message || !message.type) {
				return;
			}

			switch (message.type) {
				case 'navigate':
					// Handle navigation requests from iframe
					if (message.url) {
						window.location.href = message.url;
					}
					break;

				case 'toast':
					// Handle toast notifications (future: could show WordPress admin notice)
					console.log('SFUI Toast:', message.message, message.variant);
					break;

				default:
					console.warn('SFUI: Unknown message type:', message.type);
			}
		});
	}
})();
</script>
