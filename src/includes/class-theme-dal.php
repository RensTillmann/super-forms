<?php
/**
 * Themes Data Access Layer
 *
 * Provides database abstraction for the theming system.
 * Themes are stored independently of forms and can be reused across forms.
 * System themes (Light, Dark) are seeded on activation.
 *
 * @author      WebRehab
 * @category    Core
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Theme_DAL
 * @version     1.0.0
 * @since       6.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Theme_DAL' ) ) :

	/**
	 * SUPER_Theme_DAL Class
	 *
	 * Static methods for theme database access.
	 */
	class SUPER_Theme_DAL {

		// ─────────────────────────────────────────────────────────
		// THEME CRUD OPERATIONS
		// ─────────────────────────────────────────────────────────

		/**
		 * Create new theme
		 *
		 * @param array $data Theme data (name, styles, category, etc.)
		 * @return int|WP_Error Theme ID on success, WP_Error on failure
		 * @since 6.6.0
		 */
		public static function create_theme( $data ) {
			global $wpdb;

			// Validate required fields
			if ( empty( $data['name'] ) ) {
				return new WP_Error(
					'missing_name',
					__( 'Theme name is required', 'super-forms' )
				);
			}

			if ( ! isset( $data['styles'] ) ) {
				return new WP_Error(
					'missing_styles',
					__( 'Theme styles are required', 'super-forms' )
				);
			}

			// Set defaults
			$data = wp_parse_args(
				$data,
				array(
					'description'    => '',
					'category'       => 'light',
					'preview_colors' => array(),
					'is_system'      => 0,
					'is_stub'        => 0,
					'user_id'        => null,
				)
			);

			// Generate slug if not provided
			$slug = ! empty( $data['slug'] )
				? sanitize_title( $data['slug'] )
				: self::generate_unique_slug( $data['name'] );

			// Prepare styles (encode if array)
			$styles = $data['styles'];
			if ( is_array( $styles ) ) {
				$styles = wp_json_encode( $styles );
			}

			// Prepare preview_colors (encode if array)
			$preview_colors = $data['preview_colors'];
			if ( is_array( $preview_colors ) ) {
				$preview_colors = wp_json_encode( $preview_colors );
			}

			// Insert theme
			$insert_data = array(
				'name'           => sanitize_text_field( $data['name'] ),
				'slug'           => $slug,
				'description'    => sanitize_textarea_field( $data['description'] ),
				'category'       => sanitize_text_field( $data['category'] ),
				'styles'         => $styles,
				'preview_colors' => $preview_colors,
				'is_system'      => absint( $data['is_system'] ),
				'is_stub'        => absint( $data['is_stub'] ),
				'user_id'        => ! empty( $data['user_id'] ) ? absint( $data['user_id'] ) : null,
				'created_at'     => current_time( 'mysql' ),
				'updated_at'     => current_time( 'mysql' ),
			);

			$result = $wpdb->insert(
				$wpdb->prefix . 'superforms_themes',
				$insert_data,
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' )
			);

			if ( false === $result ) {
				return new WP_Error(
					'db_insert_error',
					$wpdb->last_error ? $wpdb->last_error : __( 'Failed to insert theme', 'super-forms' )
				);
			}

			return $wpdb->insert_id;
		}

		/**
		 * Get theme by ID
		 *
		 * @param int $theme_id Theme ID
		 * @return array|WP_Error Theme data or error
		 * @since 6.6.0
		 */
		public static function get_theme( $theme_id ) {
			global $wpdb;

			if ( empty( $theme_id ) || ! is_numeric( $theme_id ) ) {
				return new WP_Error(
					'invalid_theme_id',
					__( 'Invalid theme ID', 'super-forms' )
				);
			}

			$theme = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}superforms_themes WHERE id = %d",
					$theme_id
				),
				ARRAY_A
			);

			if ( null === $theme ) {
				return new WP_Error(
					'theme_not_found',
					__( 'Theme not found', 'super-forms' )
				);
			}

			return self::decode_theme_json( $theme );
		}

		/**
		 * Get theme by slug
		 *
		 * @param string $slug Theme slug
		 * @return array|WP_Error Theme data or error
		 * @since 6.6.0
		 */
		public static function get_theme_by_slug( $slug ) {
			global $wpdb;

			if ( empty( $slug ) ) {
				return new WP_Error(
					'invalid_slug',
					__( 'Invalid theme slug', 'super-forms' )
				);
			}

			$theme = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}superforms_themes WHERE slug = %s",
					sanitize_title( $slug )
				),
				ARRAY_A
			);

			if ( null === $theme ) {
				return new WP_Error(
					'theme_not_found',
					__( 'Theme not found', 'super-forms' )
				);
			}

			return self::decode_theme_json( $theme );
		}

		/**
		 * Get all themes with optional filters
		 *
		 * @param array $filters Optional filters (category, is_system, user_id, include_stubs)
		 * @return array Array of themes
		 * @since 6.6.0
		 */
		public static function get_all_themes( $filters = array() ) {
			global $wpdb;

			$where_clauses = array();
			$where_values  = array();

			// Filter by category
			if ( ! empty( $filters['category'] ) ) {
				$where_clauses[] = 'category = %s';
				$where_values[]  = sanitize_text_field( $filters['category'] );
			}

			// Filter by system themes
			if ( isset( $filters['is_system'] ) ) {
				$where_clauses[] = 'is_system = %d';
				$where_values[]  = absint( $filters['is_system'] );
			}

			// Filter by user
			if ( ! empty( $filters['user_id'] ) ) {
				$where_clauses[] = 'user_id = %d';
				$where_values[]  = absint( $filters['user_id'] );
			}

			// Exclude stubs unless explicitly included
			if ( isset( $filters['include_stubs'] ) && ! $filters['include_stubs'] ) {
				$where_clauses[] = 'is_stub = 0';
			}

			$where = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

			$query = "SELECT * FROM {$wpdb->prefix}superforms_themes
					  {$where}
					  ORDER BY is_system DESC, is_stub ASC, name ASC";

			if ( ! empty( $where_values ) ) {
				$query = $wpdb->prepare( $query, $where_values );
			}

			$results = $wpdb->get_results( $query, ARRAY_A );

			// Decode JSON fields for each theme
			return array_map( array( __CLASS__, 'decode_theme_json' ), $results );
		}

		/**
		 * Update theme
		 *
		 * @param int   $theme_id Theme ID
		 * @param array $data     Data to update
		 * @return bool|WP_Error True on success, WP_Error on failure
		 * @since 6.6.0
		 */
		public static function update_theme( $theme_id, $data ) {
			global $wpdb;

			if ( empty( $theme_id ) || ! is_numeric( $theme_id ) ) {
				return new WP_Error(
					'invalid_theme_id',
					__( 'Invalid theme ID', 'super-forms' )
				);
			}

			// Verify theme exists
			$existing = self::get_theme( $theme_id );
			if ( is_wp_error( $existing ) ) {
				return $existing;
			}

			// Prepare update data
			$update_data   = array();
			$update_format = array();

			if ( isset( $data['name'] ) ) {
				$update_data['name'] = sanitize_text_field( $data['name'] );
				$update_format[]     = '%s';
			}

			if ( isset( $data['description'] ) ) {
				$update_data['description'] = sanitize_textarea_field( $data['description'] );
				$update_format[]            = '%s';
			}

			if ( isset( $data['category'] ) ) {
				$update_data['category'] = sanitize_text_field( $data['category'] );
				$update_format[]         = '%s';
			}

			if ( isset( $data['styles'] ) ) {
				$styles = $data['styles'];
				if ( is_array( $styles ) ) {
					$styles = wp_json_encode( $styles );
				}
				$update_data['styles'] = $styles;
				$update_format[]       = '%s';
			}

			if ( isset( $data['preview_colors'] ) ) {
				$preview_colors = $data['preview_colors'];
				if ( is_array( $preview_colors ) ) {
					$preview_colors = wp_json_encode( $preview_colors );
				}
				$update_data['preview_colors'] = $preview_colors;
				$update_format[]               = '%s';
			}

			// Always update timestamp
			$update_data['updated_at'] = current_time( 'mysql' );
			$update_format[]           = '%s';

			$result = $wpdb->update(
				$wpdb->prefix . 'superforms_themes',
				$update_data,
				array( 'id' => $theme_id ),
				$update_format,
				array( '%d' )
			);

			if ( false === $result ) {
				return new WP_Error(
					'db_update_error',
					$wpdb->last_error ? $wpdb->last_error : __( 'Failed to update theme', 'super-forms' )
				);
			}

			return true;
		}

		/**
		 * Delete theme (only non-system themes)
		 *
		 * @param int $theme_id Theme ID
		 * @return bool|WP_Error True on success, WP_Error on failure
		 * @since 6.6.0
		 */
		public static function delete_theme( $theme_id ) {
			global $wpdb;

			if ( empty( $theme_id ) || ! is_numeric( $theme_id ) ) {
				return new WP_Error(
					'invalid_theme_id',
					__( 'Invalid theme ID', 'super-forms' )
				);
			}

			// Verify theme exists
			$existing = self::get_theme( $theme_id );
			if ( is_wp_error( $existing ) ) {
				return $existing;
			}

			// Prevent deletion of system themes
			if ( ! empty( $existing['is_system'] ) ) {
				return new WP_Error(
					'cannot_delete_system_theme',
					__( 'System themes cannot be deleted', 'super-forms' )
				);
			}

			// Delete theme
			$result = $wpdb->delete(
				$wpdb->prefix . 'superforms_themes',
				array( 'id' => $theme_id ),
				array( '%d' )
			);

			if ( false === $result ) {
				return new WP_Error(
					'db_delete_error',
					$wpdb->last_error ? $wpdb->last_error : __( 'Failed to delete theme', 'super-forms' )
				);
			}

			return true;
		}

		// ─────────────────────────────────────────────────────────
		// SEEDING METHODS
		// ─────────────────────────────────────────────────────────

		/**
		 * Seed system themes (Light, Dark)
		 * Called on plugin activation
		 *
		 * @since 6.6.0
		 */
		public static function seed_system_themes() {
			// Check if already seeded
			$existing = self::get_theme_by_slug( 'light' );
			if ( ! is_wp_error( $existing ) ) {
				return; // Already seeded
			}

			// Light theme
			self::create_theme( array(
				'name'           => 'Light',
				'slug'           => 'light',
				'description'    => 'Clean, professional look with subtle grays',
				'category'       => 'light',
				'styles'         => self::get_light_preset(),
				'preview_colors' => array( '#ffffff', '#1f2937', '#2563eb', '#d1d5db' ),
				'is_system'      => 1,
				'is_stub'        => 0,
			) );

			// Dark theme
			self::create_theme( array(
				'name'           => 'Dark',
				'slug'           => 'dark',
				'description'    => 'Modern dark mode with good contrast',
				'category'       => 'dark',
				'styles'         => self::get_dark_preset(),
				'preview_colors' => array( '#1f2937', '#f9fafb', '#3b82f6', '#374151' ),
				'is_system'      => 1,
				'is_stub'        => 0,
			) );
		}

		/**
		 * Seed stub themes (coming soon placeholders)
		 * Called on plugin activation
		 *
		 * @since 6.6.0
		 */
		public static function seed_stub_themes() {
			$stubs = array(
				array(
					'name'           => 'Minimal',
					'slug'           => 'minimal',
					'description'    => 'Borderless, maximum whitespace, understated',
					'category'       => 'minimal',
					'preview_colors' => array( '#ffffff', '#374151', '#6b7280', '#f3f4f6' ),
				),
				array(
					'name'           => 'Classic',
					'slug'           => 'classic',
					'description'    => 'Traditional form styling, familiar',
					'category'       => 'light',
					'preview_colors' => array( '#ffffff', '#333333', '#0066cc', '#cccccc' ),
				),
				array(
					'name'           => 'Modern',
					'slug'           => 'modern',
					'description'    => 'Rounded corners, subtle shadows, contemporary',
					'category'       => 'light',
					'preview_colors' => array( '#ffffff', '#1e293b', '#6366f1', '#e2e8f0' ),
				),
				array(
					'name'           => 'Corporate',
					'slug'           => 'corporate',
					'description'    => 'Professional, trust-inspiring, blue tones',
					'category'       => 'light',
					'preview_colors' => array( '#f8fafc', '#0f172a', '#1d4ed8', '#cbd5e1' ),
				),
				array(
					'name'           => 'Playful',
					'slug'           => 'playful',
					'description'    => 'Colorful, friendly, very rounded',
					'category'       => 'light',
					'preview_colors' => array( '#fef3c7', '#7c2d12', '#f97316', '#fcd34d' ),
				),
				array(
					'name'           => 'High Contrast',
					'slug'           => 'high-contrast',
					'description'    => 'Accessibility-focused, WCAG AAA',
					'category'       => 'highContrast',
					'preview_colors' => array( '#000000', '#ffffff', '#ffff00', '#ffffff' ),
				),
			);

			foreach ( $stubs as $stub ) {
				// Check if already exists
				$existing = self::get_theme_by_slug( $stub['slug'] );
				if ( ! is_wp_error( $existing ) ) {
					continue;
				}

				self::create_theme( array(
					'name'           => $stub['name'],
					'slug'           => $stub['slug'],
					'description'    => $stub['description'],
					'category'       => $stub['category'],
					'styles'         => array(), // Empty - stub
					'preview_colors' => $stub['preview_colors'],
					'is_system'      => 1,
					'is_stub'        => 1,
				) );
			}
		}

		// ─────────────────────────────────────────────────────────
		// HELPERS
		// ─────────────────────────────────────────────────────────

		/**
		 * Generate unique slug from name
		 *
		 * @param string $name Theme name
		 * @return string Unique slug
		 * @since 6.6.0
		 */
		private static function generate_unique_slug( $name ) {
			global $wpdb;

			$base_slug = sanitize_title( $name );
			$slug      = $base_slug;
			$counter   = 1;

			while ( true ) {
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$wpdb->prefix}superforms_themes WHERE slug = %s",
						$slug
					)
				);

				if ( ! $exists ) {
					break;
				}

				$slug = $base_slug . '-' . $counter;
				$counter++;
			}

			return $slug;
		}

		/**
		 * Check if theme is owned by user
		 *
		 * @param int $theme_id Theme ID
		 * @param int $user_id  User ID
		 * @return bool True if user owns theme
		 * @since 6.6.0
		 */
		public static function is_owner( $theme_id, $user_id ) {
			$theme = self::get_theme( $theme_id );

			if ( is_wp_error( $theme ) ) {
				return false;
			}

			// System themes have no owner
			if ( $theme['is_system'] ) {
				return false;
			}

			return absint( $theme['user_id'] ) === absint( $user_id );
		}

		/**
		 * Decode JSON fields in theme data
		 *
		 * @param array $theme Theme data from database
		 * @return array Theme with decoded JSON fields
		 * @since 6.6.0
		 */
		public static function decode_theme_json( $theme ) {
			if ( ! empty( $theme['styles'] ) ) {
				$theme['styles'] = json_decode( $theme['styles'], true );
			}

			if ( ! empty( $theme['preview_colors'] ) ) {
				$theme['preview_colors'] = json_decode( $theme['preview_colors'], true );
			}

			// Cast boolean fields
			$theme['is_system'] = (bool) $theme['is_system'];
			$theme['is_stub']   = (bool) $theme['is_stub'];

			return $theme;
		}

		/**
		 * Get Light preset styles
		 * Matches frontend LIGHT_PRESET from React schemas
		 *
		 * @return array Light theme styles
		 * @since 6.6.0
		 */
		private static function get_light_preset() {
			return array(
				'label' => array(
					'fontSize'   => 14,
					'fontFamily' => 'inherit',
					'fontWeight' => '500',
					'lineHeight' => 1.4,
					'color'      => '#1f2937',
					'margin'     => array( 'top' => 0, 'right' => 0, 'bottom' => 4, 'left' => 0 ),
				),
				'description' => array(
					'fontSize'   => 13,
					'fontFamily' => 'inherit',
					'fontStyle'  => 'normal',
					'lineHeight' => 1.4,
					'color'      => '#6b7280',
					'margin'     => array( 'top' => 4, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
				),
				'input' => array(
					'fontSize'        => 14,
					'fontFamily'      => 'inherit',
					'textAlign'       => 'left',
					'color'           => '#1f2937',
					'padding'         => array( 'top' => 10, 'right' => 14, 'bottom' => 10, 'left' => 14 ),
					'border'          => array( 'top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1 ),
					'borderStyle'     => 'solid',
					'borderColor'     => '#d1d5db',
					'borderRadius'    => 6,
					'backgroundColor' => '#ffffff',
					'width'           => '100%',
					'minHeight'       => 42,
				),
				'placeholder' => array(
					'fontStyle' => 'normal',
					'color'     => '#9ca3af',
				),
				'error' => array(
					'fontSize'   => 13,
					'fontWeight' => '500',
					'color'      => '#dc2626',
					'margin'     => array( 'top' => 4, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
				),
				'required' => array(
					'fontSize'   => 14,
					'fontWeight' => '400',
					'color'      => '#dc2626',
				),
				'fieldContainer' => array(
					'margin'          => array( 'top' => 0, 'right' => 0, 'bottom' => 20, 'left' => 0 ),
					'padding'         => array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
					'border'          => array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
					'borderRadius'    => 0,
					'backgroundColor' => 'transparent',
					'width'           => '100%',
				),
				'heading' => array(
					'fontSize'       => 24,
					'fontFamily'     => 'inherit',
					'fontWeight'     => '600',
					'textAlign'      => 'left',
					'textDecoration' => 'none',
					'lineHeight'     => 1.3,
					'letterSpacing'  => 0,
					'color'          => '#111827',
					'margin'         => array( 'top' => 0, 'right' => 0, 'bottom' => 12, 'left' => 0 ),
				),
				'paragraph' => array(
					'fontSize'       => 15,
					'fontFamily'     => 'inherit',
					'fontStyle'      => 'normal',
					'textAlign'      => 'left',
					'textDecoration' => 'none',
					'lineHeight'     => 1.6,
					'color'          => '#4b5563',
					'margin'         => array( 'top' => 0, 'right' => 0, 'bottom' => 16, 'left' => 0 ),
				),
				'button' => array(
					'fontSize'        => 15,
					'fontFamily'      => 'inherit',
					'fontWeight'      => '500',
					'textAlign'       => 'center',
					'letterSpacing'   => 0,
					'color'           => '#ffffff',
					'margin'          => array( 'top' => 12, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
					'padding'         => array( 'top' => 12, 'right' => 24, 'bottom' => 12, 'left' => 24 ),
					'border'          => array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
					'borderRadius'    => 6,
					'backgroundColor' => '#2563eb',
					'minHeight'       => 44,
				),
				'divider' => array(
					'color'  => '#e5e7eb',
					'margin' => array( 'top' => 20, 'right' => 0, 'bottom' => 20, 'left' => 0 ),
					'border' => array( 'top' => 1, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
					'width'  => '100%',
				),
				'optionLabel' => array(
					'fontSize'   => 14,
					'fontFamily' => 'inherit',
					'lineHeight' => 1.4,
					'color'      => '#374151',
				),
				'cardContainer' => array(
					'margin'          => array( 'top' => 0, 'right' => 8, 'bottom' => 8, 'left' => 0 ),
					'padding'         => array( 'top' => 16, 'right' => 16, 'bottom' => 16, 'left' => 16 ),
					'border'          => array( 'top' => 2, 'right' => 2, 'bottom' => 2, 'left' => 2 ),
					'borderRadius'    => 8,
					'backgroundColor' => '#ffffff',
					'width'           => 'auto',
					'minHeight'       => 80,
				),
			);
		}

		/**
		 * Get Dark preset styles
		 * Matches frontend DARK_PRESET from React schemas
		 *
		 * @return array Dark theme styles
		 * @since 6.6.0
		 */
		private static function get_dark_preset() {
			return array(
				'label' => array(
					'fontSize'   => 14,
					'fontFamily' => 'inherit',
					'fontWeight' => '500',
					'lineHeight' => 1.4,
					'color'      => '#f9fafb',
					'margin'     => array( 'top' => 0, 'right' => 0, 'bottom' => 4, 'left' => 0 ),
				),
				'description' => array(
					'fontSize'   => 13,
					'fontFamily' => 'inherit',
					'fontStyle'  => 'normal',
					'lineHeight' => 1.4,
					'color'      => '#9ca3af',
					'margin'     => array( 'top' => 4, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
				),
				'input' => array(
					'fontSize'        => 14,
					'fontFamily'      => 'inherit',
					'textAlign'       => 'left',
					'color'           => '#f9fafb',
					'padding'         => array( 'top' => 10, 'right' => 14, 'bottom' => 10, 'left' => 14 ),
					'border'          => array( 'top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1 ),
					'borderStyle'     => 'solid',
					'borderColor'     => '#4b5563',
					'borderRadius'    => 6,
					'backgroundColor' => '#374151',
					'width'           => '100%',
					'minHeight'       => 42,
				),
				'placeholder' => array(
					'fontStyle' => 'normal',
					'color'     => '#6b7280',
				),
				'error' => array(
					'fontSize'   => 13,
					'fontWeight' => '500',
					'color'      => '#f87171',
					'margin'     => array( 'top' => 4, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
				),
				'required' => array(
					'fontSize'   => 14,
					'fontWeight' => '400',
					'color'      => '#f87171',
				),
				'fieldContainer' => array(
					'margin'          => array( 'top' => 0, 'right' => 0, 'bottom' => 20, 'left' => 0 ),
					'padding'         => array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
					'border'          => array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
					'borderRadius'    => 0,
					'backgroundColor' => 'transparent',
					'width'           => '100%',
				),
				'heading' => array(
					'fontSize'       => 24,
					'fontFamily'     => 'inherit',
					'fontWeight'     => '600',
					'textAlign'      => 'left',
					'textDecoration' => 'none',
					'lineHeight'     => 1.3,
					'letterSpacing'  => 0,
					'color'          => '#ffffff',
					'margin'         => array( 'top' => 0, 'right' => 0, 'bottom' => 12, 'left' => 0 ),
				),
				'paragraph' => array(
					'fontSize'       => 15,
					'fontFamily'     => 'inherit',
					'fontStyle'      => 'normal',
					'textAlign'      => 'left',
					'textDecoration' => 'none',
					'lineHeight'     => 1.6,
					'color'          => '#d1d5db',
					'margin'         => array( 'top' => 0, 'right' => 0, 'bottom' => 16, 'left' => 0 ),
				),
				'button' => array(
					'fontSize'        => 15,
					'fontFamily'      => 'inherit',
					'fontWeight'      => '500',
					'textAlign'       => 'center',
					'letterSpacing'   => 0,
					'color'           => '#ffffff',
					'margin'          => array( 'top' => 12, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
					'padding'         => array( 'top' => 12, 'right' => 24, 'bottom' => 12, 'left' => 24 ),
					'border'          => array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
					'borderRadius'    => 6,
					'backgroundColor' => '#3b82f6',
					'minHeight'       => 44,
				),
				'divider' => array(
					'color'  => '#4b5563',
					'margin' => array( 'top' => 20, 'right' => 0, 'bottom' => 20, 'left' => 0 ),
					'border' => array( 'top' => 1, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
					'width'  => '100%',
				),
				'optionLabel' => array(
					'fontSize'   => 14,
					'fontFamily' => 'inherit',
					'lineHeight' => 1.4,
					'color'      => '#e5e7eb',
				),
				'cardContainer' => array(
					'margin'          => array( 'top' => 0, 'right' => 8, 'bottom' => 8, 'left' => 0 ),
					'padding'         => array( 'top' => 16, 'right' => 16, 'bottom' => 16, 'left' => 16 ),
					'border'          => array( 'top' => 2, 'right' => 2, 'bottom' => 2, 'left' => 2 ),
					'borderRadius'    => 8,
					'backgroundColor' => '#1f2937',
					'width'           => 'auto',
					'minHeight'       => 80,
				),
			);
		}
	}

endif;
