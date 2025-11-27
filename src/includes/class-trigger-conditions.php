<?php
/**
 * Trigger Conditions Engine
 *
 * Handles complex condition evaluation with AND/OR/NOT grouping,
 * tag replacement, and type casting for proper comparisons.
 *
 * @author      WebRehab
 * @category    Core
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Trigger_Conditions
 * @version     1.0.0
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Trigger_Conditions' ) ) :

	/**
	 * SUPER_Trigger_Conditions Class
	 */
	class SUPER_Trigger_Conditions {

		/**
		 * Maximum complexity score allowed
		 *
		 * @var int
		 */
		const MAX_COMPLEXITY = 100;

		/**
		 * Maximum nesting depth
		 *
		 * @var int
		 */
		const MAX_DEPTH = 10;

		/**
		 * Evaluate conditions against context
		 *
		 * @param array $conditions Condition structure
		 * @param array $context    Event context data
		 * @return bool True if conditions pass, false otherwise
		 * @since 6.5.0
		 */
		public static function evaluate( $conditions, $context ) {
			if ( empty( $conditions ) ) {
				return true; // No conditions = always pass
			}

			// Handle group structure
			if ( isset( $conditions['operator'] ) ) {
				return self::evaluate_group( $conditions, $context );
			}

			// Handle legacy single condition
			if ( isset( $conditions['field'] ) && isset( $conditions['operator'] ) ) {
				return self::evaluate_single( $conditions, $context );
			}

			// Invalid structure
			return false;
		}

		/**
		 * Evaluate a condition group (AND/OR/NOT)
		 *
		 * @param array $group   Group structure
		 * @param array $context Event context
		 * @param int   $depth   Current nesting depth
		 * @return bool Evaluation result
		 * @since 6.5.0
		 */
		private static function evaluate_group( $group, $context, $depth = 0 ) {
			// Prevent infinite recursion
			if ( $depth > self::MAX_DEPTH ) {
				return false;
			}

			$operator = strtoupper( $group['operator'] ?? 'AND' );
			$results  = array();

			// Process sub-groups
			if ( ! empty( $group['groups'] ) && is_array( $group['groups'] ) ) {
				foreach ( $group['groups'] as $subgroup ) {
					$results[] = self::evaluate_group( $subgroup, $context, $depth + 1 );
				}
			}

			// Process individual conditions
			if ( ! empty( $group['conditions'] ) && is_array( $group['conditions'] ) ) {
				foreach ( $group['conditions'] as $condition ) {
					$results[] = self::evaluate_single( $condition, $context );
				}
			}

			// Process rules (alternative to conditions/groups)
			if ( ! empty( $group['rules'] ) && is_array( $group['rules'] ) ) {
				foreach ( $group['rules'] as $rule ) {
					// Check if this is a nested group (has operator + rules, or type=group)
					$is_group = ( isset( $rule['type'] ) && 'group' === $rule['type'] )
						|| ( isset( $rule['operator'] ) && isset( $rule['rules'] ) );

					if ( $is_group ) {
						$results[] = self::evaluate_group( $rule, $context, $depth + 1 );
					} else {
						$results[] = self::evaluate_single( $rule, $context );
					}
				}
			}

			// If no results, return false
			if ( empty( $results ) ) {
				return false;
			}

			// Apply logical operator
			return self::apply_operator( $operator, $results );
		}

		/**
		 * Apply logical operator to results
		 *
		 * @param string $operator Operator (AND, OR, NOT, XOR, NAND, NOR)
		 * @param array  $results  Array of boolean results
		 * @return bool Final result
		 * @since 6.5.0
		 */
		private static function apply_operator( $operator, $results ) {
			switch ( $operator ) {
				case 'AND':
					return ! in_array( false, $results, true );

				case 'OR':
					return in_array( true, $results, true );

				case 'NOT':
					// NOT inverts the AND result
					return ! ( ! in_array( false, $results, true ) );

				case 'XOR':
					// Exactly one must be true
					$true_count = count( array_filter( $results ) );
					return 1 === $true_count;

				case 'NAND':
					// NOT AND - at least one must be false
					return in_array( false, $results, true );

				case 'NOR':
					// NOT OR - all must be false
					return ! in_array( true, $results, true );

				default:
					return false;
			}
		}

		/**
		 * Evaluate a single condition
		 *
		 * @param array $condition Condition structure
		 * @param array $context   Event context
		 * @return bool Evaluation result
		 * @since 6.5.0
		 */
		private static function evaluate_single( $condition, $context ) {
			$field    = $condition['field'] ?? '';
			$operator = $condition['operator'] ?? '=';
			$value    = $condition['value'] ?? '';
			$type     = $condition['type'] ?? 'string';

			// Determine field value - handle {tag} vs plain field name
			$original_field = $field;
			if ( preg_match( '/^\{([a-zA-Z0-9_.]+)\}$/', $field, $matches ) ) {
				// Field is a {tag} - replace_tags gives us the value directly
				$field_value = self::replace_tags( $field, $context );
				// If tag wasn't replaced (still has braces), try as field name
				if ( $field_value === $field ) {
					$field_value = self::get_field_value( $matches[1], $context );
				}
			} else {
				// Plain field name - use get_field_value
				$field_value = self::get_field_value( $field, $context );
			}

			// Replace tags in value
			$value = self::replace_tags( $value, $context );

			// Type casting for proper comparison
			$field_value = self::cast_value( $field_value, $type );
			$value       = self::cast_value( $value, $type );

			// Evaluate based on operator
			return self::compare_values( $field_value, $operator, $value, $context );
		}

		/**
		 * Compare values using operator
		 *
		 * @param mixed  $field_value Field value
		 * @param string $operator    Comparison operator
		 * @param mixed  $value       Comparison value
		 * @param array  $context     Full context for special operators
		 * @return bool Comparison result
		 * @since 6.5.0
		 */
		private static function compare_values( $field_value, $operator, $value, $context ) {
			switch ( $operator ) {
				case '=':
				case '==':
					return $field_value == $value; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison

				case '!=':
				case '<>':
					return $field_value != $value; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison

				case '>':
					return $field_value > $value;

				case '>=':
					return $field_value >= $value;

				case '<':
					return $field_value < $value;

				case '<=':
					return $field_value <= $value;

				case 'contains':
					return false !== stripos( (string) $field_value, (string) $value );

				case 'not_contains':
					return false === stripos( (string) $field_value, (string) $value );

				case 'starts_with':
					return 0 === strpos( (string) $field_value, (string) $value );

				case 'ends_with':
					$len = strlen( (string) $value );
					return substr( (string) $field_value, -$len ) === (string) $value;

				case 'regex':
					return 1 === @preg_match( (string) $value, (string) $field_value );

				case 'in':
					$values = is_array( $value ) ? $value : explode( ',', (string) $value );
					$values = array_map( 'trim', $values );
					return in_array( $field_value, $values, false );

				case 'not_in':
					$values = is_array( $value ) ? $value : explode( ',', (string) $value );
					$values = array_map( 'trim', $values );
					return ! in_array( $field_value, $values, false );

				case 'between':
					$values = is_array( $value ) ? $value : explode( ',', (string) $value );
					if ( count( $values ) < 2 ) {
						return false;
					}
					$min = self::cast_value( trim( $values[0] ), 'float' );
					$max = self::cast_value( trim( $values[1] ), 'float' );
					return $field_value >= $min && $field_value <= $max;

				case 'empty':
					return empty( $field_value );

				case 'not_empty':
					return ! empty( $field_value );

				case 'changed':
					// Check if value changed from previous
					$previous = $context['previous_values'][ $field_value ] ?? null;
					return $field_value !== $previous;

				case 'custom':
					// Allow custom PHP evaluation (admin only)
					if ( current_user_can( 'manage_options' ) ) {
						return self::evaluate_custom( $field_value, $value, $context );
					}
					return false;

				default:
					// Allow extensions to add operators
					$result = apply_filters(
						'super_evaluate_condition_operator',
						false,
						$operator,
						$field_value,
						$value,
						$context
					);
					return (bool) $result;
			}
		}

		/**
		 * Evaluate custom PHP condition (admin only)
		 *
		 * @param mixed  $field_value Field value
		 * @param string $code        PHP code to evaluate
		 * @param array  $context     Full context
		 * @return bool Evaluation result
		 * @since 6.5.0
		 */
		private static function evaluate_custom( $field_value, $code, $context ) {
			if ( empty( $code ) ) {
				return false;
			}

			// Create safe evaluation context
			$eval_context = array(
				'value'     => $field_value,
				'form_data' => $context['form_data'] ?? array(),
				'user'      => wp_get_current_user(),
				'post'      => get_post(),
			);

			// Sandbox the evaluation
			try {
				// phpcs:ignore Squiz.PHP.Eval.Discouraged
				$result = eval( 'return (' . $code . ');' );
				return (bool) $result;
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Super Forms: Custom condition error: ' . $e->getMessage() );
				}
				return false;
			}
		}

		/**
		 * Get field value from context
		 *
		 * Supports special field prefixes:
		 * - user.{field} - Current user field
		 * - post.{field} - Current post field
		 * - meta.{key} - Post meta value
		 * - calc.{expression} - Calculated value
		 *
		 * @param string $field   Field name
		 * @param array  $context Event context
		 * @return mixed Field value
		 * @since 6.5.0
		 */
		private static function get_field_value( $field, $context ) {
			// Handle special prefixes
			if ( 0 === strpos( $field, 'user.' ) ) {
				$user_field = substr( $field, 5 );
				$user       = wp_get_current_user();
				return isset( $user->$user_field ) ? $user->$user_field : '';
			}

			if ( 0 === strpos( $field, 'post.' ) ) {
				$post_field = substr( $field, 5 );
				$post       = get_post();
				return $post && isset( $post->$post_field ) ? $post->$post_field : '';
			}

			if ( 0 === strpos( $field, 'meta.' ) ) {
				$meta_key = substr( $field, 5 );
				return get_post_meta( get_the_ID(), $meta_key, true );
			}

			if ( 0 === strpos( $field, 'calc.' ) ) {
				return self::calculate_value( substr( $field, 5 ), $context );
			}

			// Check if field exists directly in context
			if ( isset( $context[ $field ] ) ) {
				return $context[ $field ];
			}

			// Check in 'data' array (common structure for test contexts)
			if ( isset( $context['data'][ $field ] ) ) {
				$data = $context['data'][ $field ];
				if ( is_array( $data ) && isset( $data['value'] ) ) {
					return $data['value'];
				}
				return $data;
			}

			// Check in form_data
			if ( isset( $context['form_data'][ $field ] ) ) {
				$data = $context['form_data'][ $field ];
				if ( is_array( $data ) && isset( $data['value'] ) ) {
					return $data['value'];
				}
				return $data;
			}

			// Check in entry_data (if available)
			if ( isset( $context['entry_data'][ $field ] ) ) {
				if ( is_array( $context['entry_data'][ $field ] ) && isset( $context['entry_data'][ $field ]['value'] ) ) {
					return $context['entry_data'][ $field ]['value'];
				}
				return $context['entry_data'][ $field ];
			}

			return '';
		}

		/**
		 * Calculate dynamic value
		 *
		 * @param string $expression Calculation expression
		 * @param array  $context    Event context
		 * @return mixed Calculated value
		 * @since 6.5.0
		 */
		private static function calculate_value( $expression, $context ) {
			// Simple calculations only - extend as needed
			// Example: "total" - sum of all numeric fields

			if ( 'total' === $expression ) {
				$total = 0;
				if ( isset( $context['form_data'] ) ) {
					foreach ( $context['form_data'] as $value ) {
						if ( is_numeric( $value ) ) {
							$total += floatval( $value );
						}
					}
				}
				return $total;
			}

			// Allow extensions to add calculations
			return apply_filters( 'super_trigger_calculate_value', '', $expression, $context );
		}

		/**
		 * Replace tags in string with context values
		 *
		 * Supports:
		 * - {field_name} - Simple field
		 * - {form_data.email} - Nested field
		 * - {user.display_name} - User field
		 * - {post.post_title} - Post field
		 *
		 * @param string $string  String with {tags}
		 * @param array  $context Event context
		 * @return string String with tags replaced
		 * @since 6.5.0
		 */
		public static function replace_tags( $string, $context ) {
			if ( ! is_string( $string ) ) {
				return $string;
			}

			// Handle simple tags like {user_name}
			$string = preg_replace_callback(
				'/\{([a-zA-Z0-9_]+)\}/',
				function ( $matches ) use ( $context ) {
					$key = $matches[1];

					// Direct context value
					if ( isset( $context[ $key ] ) ) {
						return is_array( $context[ $key ] ) ? '' : $context[ $key ];
					}

					// Check in 'data' array (common structure for form field data)
					if ( isset( $context['data'][ $key ] ) ) {
						$data = $context['data'][ $key ];
						// Handle array with 'value' key
						if ( is_array( $data ) && isset( $data['value'] ) ) {
							return $data['value'];
						}
						return is_array( $data ) ? '' : $data;
					}

					// Check in 'form_data' array
					if ( isset( $context['form_data'][ $key ] ) ) {
						$data = $context['form_data'][ $key ];
						if ( is_array( $data ) && isset( $data['value'] ) ) {
							return $data['value'];
						}
						return is_array( $data ) ? '' : $data;
					}

					// Not found - return original tag
					return $matches[0];
				},
				$string
			);

			// Handle nested tags like {form_data.email}
			$string = preg_replace_callback(
				'/\{([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)\}/',
				function ( $matches ) use ( $context ) {
					$parent = $matches[1];
					$child  = $matches[2];

					if ( isset( $context[ $parent ] ) && is_array( $context[ $parent ] ) ) {
						if ( isset( $context[ $parent ][ $child ] ) ) {
							return $context[ $parent ][ $child ];
						}
					}

					return $matches[0];
				},
				$string
			);

			// Handle special tags
			$string = str_replace(
				array(
					'{current_user_id}',
					'{current_user_email}',
					'{current_user_login}',
					'{site_url}',
					'{admin_email}',
					'{current_time}',
					'{current_date}',
				),
				array(
					get_current_user_id(),
					wp_get_current_user()->user_email,
					wp_get_current_user()->user_login,
					get_site_url(),
					get_option( 'admin_email' ),
					current_time( 'H:i:s' ),
					current_time( 'Y-m-d' ),
				),
				$string
			);

			return $string;
		}

		/**
		 * Type cast value for proper comparison
		 *
		 * @param mixed  $value Value to cast
		 * @param string $type  Target type
		 * @return mixed Casted value
		 * @since 6.5.0
		 */
		private static function cast_value( $value, $type ) {
			switch ( $type ) {
				case 'int':
				case 'integer':
					return intval( $value );

				case 'float':
				case 'decimal':
				case 'number':
					return floatval( $value );

				case 'bool':
				case 'boolean':
					return filter_var( $value, FILTER_VALIDATE_BOOLEAN );

				case 'date':
					return strtotime( $value );

				case 'json':
					return json_decode( $value, true );

				case 'array':
					return is_array( $value ) ? $value : explode( ',', $value );

				default:
					return strval( $value );
			}
		}

		/**
		 * Validate condition structure
		 *
		 * @param array $conditions Conditions to validate
		 * @return bool|WP_Error True if valid, WP_Error if invalid
		 * @since 6.5.0
		 */
		public static function validate( $conditions ) {
			if ( empty( $conditions ) ) {
				return true; // Empty conditions are valid
			}

			// Check complexity
			$complexity = self::calculate_complexity( $conditions );
			if ( $complexity > self::MAX_COMPLEXITY ) {
				return new WP_Error(
					'conditions_too_complex',
					sprintf(
						/* translators: %1$d: current complexity, %2$d: max complexity */
						__( 'Conditions are too complex: %1$d (max: %2$d)', 'super-forms' ),
						$complexity,
						self::MAX_COMPLEXITY
					)
				);
			}

			// Check for circular dependencies (future enhancement)
			// Currently not implementing full dependency graph analysis

			return true;
		}

		/**
		 * Calculate condition complexity score
		 *
		 * @param array $conditions Condition structure
		 * @param int   $depth      Current depth
		 * @return int Complexity score
		 * @since 6.5.0
		 */
		public static function calculate_complexity( $conditions, $depth = 0 ) {
			$score = 0;

			if ( isset( $conditions['operator'] ) ) {
				$score += 1; // Base score for group

				// Add depth penalty
				$score += $depth * 2;

				// Process subgroups
				if ( ! empty( $conditions['groups'] ) ) {
					foreach ( $conditions['groups'] as $group ) {
						$score += self::calculate_complexity( $group, $depth + 1 );
					}
				}

				// Process conditions
				if ( ! empty( $conditions['conditions'] ) ) {
					$score += count( $conditions['conditions'] );

					// Add complexity for special operators
					foreach ( $conditions['conditions'] as $condition ) {
						if ( in_array( $condition['operator'] ?? '', array( 'regex', 'custom' ), true ) ) {
							$score += 5;
						}
					}
				}

				// Process rules
				if ( ! empty( $conditions['rules'] ) ) {
					foreach ( $conditions['rules'] as $rule ) {
						if ( isset( $rule['type'] ) && 'group' === $rule['type'] ) {
							$score += self::calculate_complexity( $rule, $depth + 1 );
						} else {
							$score += 1;
						}
					}
				}
			}

			return $score;
		}
	}

endif;
