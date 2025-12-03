<?php
/**
 * SUPER_Form_Operations - JSON Patch Operations Handler
 *
 * Implements RFC 6902 JSON Patch operations for atomic form updates.
 * Enables AI/LLM integration, undo/redo, and minimal payloads.
 *
 * @since 6.6.0 (Phase 27)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Form_Operations' ) ) {

	class SUPER_Form_Operations {

		/**
		 * Supported JSON Patch operation types
		 */
		const SUPPORTED_OPS = array( 'add', 'remove', 'replace', 'move', 'copy', 'test' );

		/**
		 * Apply a single JSON Patch operation to form data
		 *
		 * @param array $form_data Current form data (elements, settings, translations)
		 * @param array $operation JSON Patch operation
		 * @return array Modified form data
		 * @throws Exception If operation is invalid
		 */
		public static function apply_operation( $form_data, $operation ) {
			// Validate operation structure
			if ( ! isset( $operation['op'] ) || ! in_array( $operation['op'], self::SUPPORTED_OPS, true ) ) {
				throw new Exception( 'Invalid operation type' );
			}

			if ( ! isset( $operation['path'] ) ) {
				throw new Exception( 'Operation missing required "path" field' );
			}

			// Dispatch to appropriate handler
			switch ( $operation['op'] ) {
				case 'add':
					return self::op_add( $form_data, $operation );
				case 'remove':
					return self::op_remove( $form_data, $operation );
				case 'replace':
					return self::op_replace( $form_data, $operation );
				case 'move':
					return self::op_move( $form_data, $operation );
				case 'copy':
					return self::op_copy( $form_data, $operation );
				case 'test':
					return self::op_test( $form_data, $operation );
				default:
					throw new Exception( 'Unsupported operation: ' . $operation['op'] );
			}
		}

		/**
		 * Apply multiple operations in sequence
		 *
		 * @param array $form_data Current form data
		 * @param array $operations Array of JSON Patch operations
		 * @return array Modified form data
		 */
		public static function apply_operations( $form_data, $operations ) {
			$result = $form_data;

			foreach ( $operations as $index => $operation ) {
				try {
					$result = self::apply_operation( $result, $operation );
				} catch ( Exception $e ) {
					throw new Exception(
						sprintf( 'Operation %d failed: %s', $index, $e->getMessage() ),
						0,
						$e
					);
				}
			}

			return $result;
		}

		/**
		 * Add operation - Add value to specified path
		 *
		 * @param array $data Form data
		 * @param array $op Operation
		 * @return array Modified data
		 */
		private static function op_add( $data, $op ) {
			if ( ! isset( $op['value'] ) ) {
				throw new Exception( 'Add operation requires "value" field' );
			}

			$path_parts = self::parse_path( $op['path'] );
			$result = $data;
			self::set_value_at_path( $result, $path_parts, $op['value'], true );

			return $result;
		}

		/**
		 * Remove operation - Remove value at specified path
		 *
		 * @param array $data Form data
		 * @param array $op Operation
		 * @return array Modified data
		 */
		private static function op_remove( $data, $op ) {
			$path_parts = self::parse_path( $op['path'] );
			$result = $data;
			self::unset_value_at_path( $result, $path_parts );

			return $result;
		}

		/**
		 * Replace operation - Replace value at specified path
		 *
		 * @param array $data Form data
		 * @param array $op Operation
		 * @return array Modified data
		 */
		private static function op_replace( $data, $op ) {
			if ( ! isset( $op['value'] ) ) {
				throw new Exception( 'Replace operation requires "value" field' );
			}

			$path_parts = self::parse_path( $op['path'] );
			$result = $data;
			self::set_value_at_path( $result, $path_parts, $op['value'], false );

			return $result;
		}

		/**
		 * Move operation - Move value from one path to another
		 *
		 * @param array $data Form data
		 * @param array $op Operation
		 * @return array Modified data
		 */
		private static function op_move( $data, $op ) {
			if ( ! isset( $op['from'] ) ) {
				throw new Exception( 'Move operation requires "from" field' );
			}

			// Get value from source path
			$from_parts = self::parse_path( $op['from'] );
			$value = self::get_value_at_path( $data, $from_parts );

			// Remove from source
			$result = $data;
			self::unset_value_at_path( $result, $from_parts );

			// Add to destination
			$to_parts = self::parse_path( $op['path'] );
			self::set_value_at_path( $result, $to_parts, $value, true );

			return $result;
		}

		/**
		 * Copy operation - Copy value from one path to another
		 *
		 * @param array $data Form data
		 * @param array $op Operation
		 * @return array Modified data
		 */
		private static function op_copy( $data, $op ) {
			if ( ! isset( $op['from'] ) ) {
				throw new Exception( 'Copy operation requires "from" field' );
			}

			// Get value from source path
			$from_parts = self::parse_path( $op['from'] );
			$value = self::get_value_at_path( $data, $from_parts );

			// Add to destination (don't remove from source)
			$result = $data;
			$to_parts = self::parse_path( $op['path'] );
			self::set_value_at_path( $result, $to_parts, $value, true );

			return $result;
		}

		/**
		 * Test operation - Verify value at path matches expected
		 *
		 * @param array $data Form data
		 * @param array $op Operation
		 * @return array Unmodified data (throws exception if test fails)
		 */
		private static function op_test( $data, $op ) {
			if ( ! isset( $op['value'] ) ) {
				throw new Exception( 'Test operation requires "value" field' );
			}

			$path_parts = self::parse_path( $op['path'] );
			$current_value = self::get_value_at_path( $data, $path_parts );

			if ( $current_value !== $op['value'] ) {
				throw new Exception( 'Test operation failed: value mismatch' );
			}

			return $data; // Unchanged
		}

		/**
		 * Parse JSON Pointer path into array of parts
		 *
		 * Converts "/elements/0/label" to ["elements", "0", "label"]
		 *
		 * @param string $path JSON Pointer path
		 * @return array Path parts
		 */
		private static function parse_path( $path ) {
			if ( $path === '' || $path === '/' ) {
				return array();
			}

			// Remove leading slash and split
			$path = ltrim( $path, '/' );
			$parts = explode( '/', $path );

			// Decode special characters (~0 = ~, ~1 = /)
			return array_map( function( $part ) {
				$part = str_replace( '~1', '/', $part );
				$part = str_replace( '~0', '~', $part );
				return $part;
			}, $parts );
		}

		/**
		 * Get value at specified path in data structure
		 *
		 * @param array $data Data structure
		 * @param array $path_parts Path components
		 * @return mixed Value at path
		 * @throws Exception If path not found
		 */
		private static function get_value_at_path( $data, $path_parts ) {
			$current = $data;

			foreach ( $path_parts as $part ) {
				if ( is_array( $current ) && array_key_exists( $part, $current ) ) {
					$current = $current[ $part ];
				} else {
					throw new Exception( 'Path not found: /' . implode( '/', $path_parts ) );
				}
			}

			return $current;
		}

		/**
		 * Set value at specified path in data structure
		 *
		 * @param array &$data Data structure (modified by reference)
		 * @param array $path_parts Path components
		 * @param mixed $value Value to set
		 * @param bool $allow_append Whether to append to arrays with "-" notation
		 * @throws Exception If path invalid
		 */
		private static function set_value_at_path( &$data, $path_parts, $value, $allow_append = false ) {
			if ( empty( $path_parts ) ) {
				$data = $value;
				return;
			}

			$current = &$data;
			$last_index = count( $path_parts ) - 1;

			for ( $i = 0; $i < $last_index; $i++ ) {
				$part = $path_parts[ $i ];

				if ( ! isset( $current[ $part ] ) ) {
					$current[ $part ] = array();
				}

				if ( ! is_array( $current[ $part ] ) ) {
					throw new Exception( 'Cannot traverse non-array value at /' . implode( '/', array_slice( $path_parts, 0, $i + 1 ) ) );
				}

				$current = &$current[ $part ];
			}

			$final_part = $path_parts[ $last_index ];

			// Handle array append notation "-"
			if ( $final_part === '-' && $allow_append ) {
				if ( ! is_array( $current ) ) {
					throw new Exception( 'Cannot append to non-array' );
				}
				$current[] = $value;
			} else {
				$current[ $final_part ] = $value;
			}
		}

		/**
		 * Unset value at specified path in data structure
		 *
		 * @param array &$data Data structure (modified by reference)
		 * @param array $path_parts Path components
		 * @throws Exception If path not found
		 */
		private static function unset_value_at_path( &$data, $path_parts ) {
			if ( empty( $path_parts ) ) {
				throw new Exception( 'Cannot remove root element' );
			}

			$current = &$data;
			$last_index = count( $path_parts ) - 1;

			for ( $i = 0; $i < $last_index; $i++ ) {
				$part = $path_parts[ $i ];

				if ( ! isset( $current[ $part ] ) || ! is_array( $current[ $part ] ) ) {
					throw new Exception( 'Path not found: /' . implode( '/', $path_parts ) );
				}

				$current = &$current[ $part ];
			}

			$final_part = $path_parts[ $last_index ];

			if ( ! array_key_exists( $final_part, $current ) ) {
				throw new Exception( 'Path not found: /' . implode( '/', $path_parts ) );
			}

			unset( $current[ $final_part ] );
		}

		/**
		 * Generate inverse operation for undo functionality
		 *
		 * @param array $operation Original operation
		 * @param mixed $old_value Value before operation (for remove/replace)
		 * @return array Inverse operation
		 */
		public static function get_inverse_operation( $operation, $old_value = null ) {
			switch ( $operation['op'] ) {
				case 'add':
					// Inverse of add is remove
					return array(
						'op' => 'remove',
						'path' => $operation['path'],
					);

				case 'remove':
					// Inverse of remove is add (need old value)
					if ( $old_value === null ) {
						throw new Exception( 'Cannot invert remove operation without old_value' );
					}
					return array(
						'op' => 'add',
						'path' => $operation['path'],
						'value' => $old_value,
					);

				case 'replace':
					// Inverse of replace is replace back (need old value)
					if ( $old_value === null ) {
						throw new Exception( 'Cannot invert replace operation without old_value' );
					}
					return array(
						'op' => 'replace',
						'path' => $operation['path'],
						'value' => $old_value,
					);

				case 'move':
					// Inverse of move is move back
					return array(
						'op' => 'move',
						'from' => $operation['path'],
						'path' => $operation['from'],
					);

				case 'copy':
					// Inverse of copy is remove (from destination)
					return array(
						'op' => 'remove',
						'path' => $operation['path'],
					);

				case 'test':
					// Test operations don't modify data, no inverse needed
					return $operation;

				default:
					throw new Exception( 'Cannot invert unknown operation: ' . $operation['op'] );
			}
		}

		/**
		 * Validate operation structure and semantics
		 *
		 * @param array $operation Operation to validate
		 * @return bool True if valid
		 * @throws Exception If invalid
		 */
		public static function validate_operation( $operation ) {
			// Check op field
			if ( ! isset( $operation['op'] ) ) {
				throw new Exception( 'Operation missing "op" field' );
			}

			if ( ! in_array( $operation['op'], self::SUPPORTED_OPS, true ) ) {
				throw new Exception( 'Unsupported operation type: ' . $operation['op'] );
			}

			// Check path field
			if ( ! isset( $operation['path'] ) || ! is_string( $operation['path'] ) ) {
				throw new Exception( 'Operation missing or invalid "path" field' );
			}

			// Operation-specific validation
			switch ( $operation['op'] ) {
				case 'add':
				case 'replace':
				case 'test':
					if ( ! array_key_exists( 'value', $operation ) ) {
						throw new Exception( $operation['op'] . ' operation requires "value" field' );
					}
					break;

				case 'move':
				case 'copy':
					if ( ! isset( $operation['from'] ) || ! is_string( $operation['from'] ) ) {
						throw new Exception( $operation['op'] . ' operation requires "from" field' );
					}
					break;
			}

			return true;
		}

		/**
		 * Validate array of operations
		 *
		 * @param array $operations Operations to validate
		 * @return bool True if all valid
		 * @throws Exception If any invalid
		 */
		public static function validate_operations( $operations ) {
			if ( ! is_array( $operations ) ) {
				throw new Exception( 'Operations must be an array' );
			}

			foreach ( $operations as $index => $operation ) {
				try {
					self::validate_operation( $operation );
				} catch ( Exception $e ) {
					throw new Exception(
						sprintf( 'Operation %d invalid: %s', $index, $e->getMessage() ),
						0,
						$e
					);
				}
			}

			return true;
		}
	}
}
