<?php
/**
 * Calculate Expression Action
 *
 * Safely evaluates mathematical expressions with form field values.
 * Uses a custom tokenizer/parser - never uses eval().
 *
 * @package Super_Forms
 * @subpackage Automations/Actions
 * @since 6.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SUPER_Action_Calculate extends SUPER_Action_Base {

	/**
	 * Get action ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'calculate';
	}

	/**
	 * Get action label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Calculate Expression', 'super-forms' );
	}

	/**
	 * Get action category
	 *
	 * @return string
	 */
	public function get_category() {
		return 'utility';
	}

	/**
	 * Get action description
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Evaluate mathematical expressions using form field values. Supports +, -, *, /, %, and parentheses.', 'super-forms' );
	}

	/**
	 * Get settings schema for UI
	 *
	 * @return array
	 */
	public function get_settings_schema() {
		return [
			[
				'name'        => 'expression',
				'label'       => __( 'Expression', 'super-forms' ),
				'type'        => 'text',
				'required'    => true,
				'default'     => '',
				'placeholder' => '{quantity} * {unit_price} * (1 + {tax_rate} / 100)',
				'description' => __( 'Math expression with {field_name} tags. Supports: + - * / % ( )', 'super-forms' ),
			],
			[
				'name'        => 'precision',
				'label'       => __( 'Decimal Places', 'super-forms' ),
				'type'        => 'number',
				'required'    => false,
				'default'     => 2,
				'min'         => 0,
				'max'         => 10,
				'description' => __( 'Number of decimal places in result', 'super-forms' ),
			],
			[
				'name'        => 'format',
				'label'       => __( 'Output Format', 'super-forms' ),
				'type'        => 'select',
				'required'    => false,
				'default'     => 'number',
				'options'     => [
					'number'     => __( 'Number', 'super-forms' ),
					'currency'   => __( 'Currency', 'super-forms' ),
					'percentage' => __( 'Percentage', 'super-forms' ),
				],
				'description' => __( 'How to format the result for display', 'super-forms' ),
			],
			[
				'name'        => 'currency_symbol',
				'label'       => __( 'Currency Symbol', 'super-forms' ),
				'type'        => 'text',
				'required'    => false,
				'default'     => '$',
				'placeholder' => '$',
				'description' => __( 'Symbol to prepend for currency format', 'super-forms' ),
				'show_if'     => [ 'format' => 'currency' ],
			],
			[
				'name'        => 'currency_position',
				'label'       => __( 'Symbol Position', 'super-forms' ),
				'type'        => 'select',
				'required'    => false,
				'default'     => 'before',
				'options'     => [
					'before' => __( 'Before ($100)', 'super-forms' ),
					'after'  => __( 'After (100$)', 'super-forms' ),
				],
				'show_if'     => [ 'format' => 'currency' ],
			],
			[
				'name'        => 'thousands_separator',
				'label'       => __( 'Thousands Separator', 'super-forms' ),
				'type'        => 'text',
				'required'    => false,
				'default'     => ',',
				'placeholder' => ',',
				'description' => __( 'Character for thousands separation', 'super-forms' ),
			],
			[
				'name'        => 'decimal_separator',
				'label'       => __( 'Decimal Separator', 'super-forms' ),
				'type'        => 'text',
				'required'    => false,
				'default'     => '.',
				'placeholder' => '.',
				'description' => __( 'Character for decimal point', 'super-forms' ),
			],
			[
				'name'        => 'store_in_variable',
				'label'       => __( 'Store Result As Variable', 'super-forms' ),
				'type'        => 'text',
				'required'    => false,
				'default'     => '',
				'placeholder' => 'total_price',
				'description' => __( 'Optional: Store result in a variable for use in subsequent actions', 'super-forms' ),
			],
		];
	}

	/**
	 * Execute the action
	 *
	 * @param array $context Event context data.
	 * @param array $config  Action configuration.
	 * @return array|WP_Error Result data or error.
	 */
	public function execute( $context, $config ) {
		$expression = $config['expression'] ?? '';

		if ( empty( $expression ) ) {
			return new WP_Error(
				'missing_expression',
				__( 'Expression is required', 'super-forms' )
			);
		}

		// Security: Limit expression complexity to prevent DoS.
		if ( strlen( $expression ) > 500 ) {
			return new WP_Error(
				'expression_too_long',
				__( 'Expression exceeds maximum length of 500 characters', 'super-forms' )
			);
		}

		// Build context for variable replacement (flatten form_data).
		$eval_context = $this->build_eval_context( $context );

		// Replace {tags} with numeric values.
		$numeric_expression = $this->replace_tags_with_numbers( $expression, $eval_context );

		// Safely evaluate the expression.
		$result = $this->safe_evaluate( $numeric_expression );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Apply precision.
		$precision = isset( $config['precision'] ) ? absint( $config['precision'] ) : 2;
		$precision = min( $precision, 10 );
		$result    = round( $result, $precision );

		// Format result.
		$formatted = $this->format_result_value( $result, $config );

		// Store in variable if requested.
		$variable_name = sanitize_key( $config['store_in_variable'] ?? '' );
		if ( ! empty( $variable_name ) ) {
			// Store in context for subsequent actions.
			do_action( 'super_set_workflow_variable', $variable_name, $result, $context );
		}

		return [
			'success' => true,
			'data'    => [
				'result'           => $result,
				'formatted_result' => $formatted,
				'expression'       => $expression,
				'evaluated'        => $numeric_expression,
				'precision'        => $precision,
				'variable'         => $variable_name ?: null,
				'message'          => sprintf(
					__( 'Calculated: %s', 'super-forms' ),
					$formatted
				),
			],
		];
	}

	/**
	 * Build evaluation context from event context
	 *
	 * @param array $context Event context.
	 * @return array Flattened key-value pairs.
	 */
	private function build_eval_context( $context ) {
		$eval_context = [];

		// Add direct context values.
		foreach ( [ 'entry_id', 'form_id', 'user_id' ] as $key ) {
			if ( isset( $context[ $key ] ) ) {
				$eval_context[ $key ] = $context[ $key ];
			}
		}

		// Add form_data values.
		$form_data = $context['form_data'] ?? [];
		foreach ( $form_data as $key => $value ) {
			// Handle arrays (multi-select, checkboxes).
			if ( is_array( $value ) ) {
				// For arrays, use count or sum if all numeric.
				$all_numeric = array_reduce( $value, function ( $carry, $v ) {
					return $carry && is_numeric( $v );
				}, true );

				if ( $all_numeric && ! empty( $value ) ) {
					$eval_context[ $key ]           = array_sum( $value );
					$eval_context[ $key . '_count' ] = count( $value );
				} else {
					$eval_context[ $key . '_count' ] = count( $value );
				}
			} else {
				$eval_context[ $key ] = $value;
			}
		}

		// Add mapped_data from previous actions.
		$mapped_data = $context['mapped_data'] ?? [];
		foreach ( $mapped_data as $key => $value ) {
			if ( ! isset( $eval_context[ $key ] ) ) {
				$eval_context[ $key ] = $value;
			}
		}

		return $eval_context;
	}

	/**
	 * Replace {tags} with numeric values
	 *
	 * @param string $expression Expression with tags.
	 * @param array  $context    Evaluation context.
	 * @return string Expression with numbers.
	 */
	private function replace_tags_with_numbers( $expression, $context ) {
		return preg_replace_callback(
			'/\{([a-zA-Z0-9_]+)\}/',
			function ( $matches ) use ( $context ) {
				$variable = $matches[1];

				if ( ! isset( $context[ $variable ] ) ) {
					return '0'; // Default to 0 for missing variables.
				}

				$value = $context[ $variable ];

				// Convert to numeric.
				if ( is_numeric( $value ) ) {
					return (string) floatval( $value );
				}

				// Try to extract number from string (e.g., "$100.00" -> 100.00).
				$cleaned = preg_replace( '/[^0-9.\-]/', '', $value );
				if ( is_numeric( $cleaned ) ) {
					return (string) floatval( $cleaned );
				}

				// Not numeric, return 0.
				return '0';
			},
			$expression
		);
	}

	/**
	 * Safely evaluate a mathematical expression
	 *
	 * Uses a simple recursive descent parser - NEVER uses eval().
	 * Supports: +, -, *, /, %, (, )
	 *
	 * @param string $expression Numeric expression.
	 * @return float|WP_Error Result or error.
	 */
	private function safe_evaluate( $expression ) {
		// Remove all whitespace.
		$expression = preg_replace( '/\s+/', '', $expression );

		// Validate characters (only allow numbers, operators, parentheses, decimal).
		if ( preg_match( '/[^0-9+\-*\/%().e]/', $expression ) ) {
			return new WP_Error(
				'invalid_expression',
				__( 'Expression contains invalid characters', 'super-forms' )
			);
		}

		// Check for empty expression.
		if ( empty( $expression ) ) {
			return new WP_Error(
				'empty_expression',
				__( 'Expression is empty after variable substitution', 'super-forms' )
			);
		}

		// Check balanced parentheses.
		$paren_count = 0;
		for ( $i = 0; $i < strlen( $expression ); $i++ ) {
			if ( '(' === $expression[ $i ] ) {
				$paren_count++;
			} elseif ( ')' === $expression[ $i ] ) {
				$paren_count--;
			}
			if ( $paren_count < 0 ) {
				return new WP_Error(
					'unbalanced_parentheses',
					__( 'Unbalanced parentheses in expression', 'super-forms' )
				);
			}
		}
		if ( 0 !== $paren_count ) {
			return new WP_Error(
				'unbalanced_parentheses',
				__( 'Unbalanced parentheses in expression', 'super-forms' )
			);
		}

		try {
			$pos    = 0;
			$result = $this->parse_expression( $expression, $pos );

			// Ensure entire expression was consumed.
			if ( $pos < strlen( $expression ) ) {
				return new WP_Error(
					'parse_error',
					__( 'Unexpected characters in expression', 'super-forms' )
				);
			}

			// Check for special float values.
			if ( is_nan( $result ) || is_infinite( $result ) ) {
				return new WP_Error(
					'math_error',
					__( 'Mathematical error (division by zero or overflow)', 'super-forms' )
				);
			}

			return $result;
		} catch ( Exception $e ) {
			return new WP_Error(
				'evaluation_error',
				$e->getMessage()
			);
		}
	}

	/**
	 * Parse expression (addition/subtraction level)
	 *
	 * @param string $expr Expression string.
	 * @param int    $pos  Current position (by reference).
	 * @return float Result.
	 */
	private function parse_expression( $expr, &$pos ) {
		$result = $this->parse_term( $expr, $pos );

		while ( $pos < strlen( $expr ) ) {
			$char = $expr[ $pos ];

			if ( '+' === $char ) {
				$pos++;
				$result += $this->parse_term( $expr, $pos );
			} elseif ( '-' === $char ) {
				$pos++;
				$result -= $this->parse_term( $expr, $pos );
			} else {
				break;
			}
		}

		return $result;
	}

	/**
	 * Parse term (multiplication/division/modulo level)
	 *
	 * @param string $expr Expression string.
	 * @param int    $pos  Current position (by reference).
	 * @return float Result.
	 */
	private function parse_term( $expr, &$pos ) {
		$result = $this->parse_factor( $expr, $pos );

		while ( $pos < strlen( $expr ) ) {
			$char = $expr[ $pos ];

			if ( '*' === $char ) {
				$pos++;
				$result *= $this->parse_factor( $expr, $pos );
			} elseif ( '/' === $char ) {
				$pos++;
				$divisor = $this->parse_factor( $expr, $pos );
				if ( 0.0 === $divisor ) {
					throw new Exception( __( 'Division by zero', 'super-forms' ) );
				}
				$result /= $divisor;
			} elseif ( '%' === $char ) {
				$pos++;
				$divisor = $this->parse_factor( $expr, $pos );
				if ( 0.0 === $divisor ) {
					throw new Exception( __( 'Modulo by zero', 'super-forms' ) );
				}
				$result = fmod( $result, $divisor );
			} else {
				break;
			}
		}

		return $result;
	}

	/**
	 * Parse factor (number or parenthesized expression)
	 *
	 * @param string $expr Expression string.
	 * @param int    $pos  Current position (by reference).
	 * @return float Result.
	 */
	private function parse_factor( $expr, &$pos ) {
		// Handle unary minus/plus.
		$sign = 1;
		while ( $pos < strlen( $expr ) && ( '-' === $expr[ $pos ] || '+' === $expr[ $pos ] ) ) {
			if ( '-' === $expr[ $pos ] ) {
				$sign *= -1;
			}
			$pos++;
		}

		if ( $pos >= strlen( $expr ) ) {
			throw new Exception( __( 'Unexpected end of expression', 'super-forms' ) );
		}

		$char = $expr[ $pos ];

		// Parenthesized expression.
		if ( '(' === $char ) {
			$pos++;
			$result = $this->parse_expression( $expr, $pos );
			if ( $pos >= strlen( $expr ) || ')' !== $expr[ $pos ] ) {
				throw new Exception( __( 'Missing closing parenthesis', 'super-forms' ) );
			}
			$pos++;
			return $sign * $result;
		}

		// Number.
		if ( preg_match( '/^([0-9]*\.?[0-9]+(?:[eE][+-]?[0-9]+)?)/', substr( $expr, $pos ), $matches ) ) {
			$pos += strlen( $matches[1] );
			return $sign * floatval( $matches[1] );
		}

		throw new Exception( __( 'Expected number or expression', 'super-forms' ) );
	}

	/**
	 * Format the result value
	 *
	 * @param float $value  Numeric result.
	 * @param array $config Action configuration.
	 * @return string Formatted result.
	 */
	private function format_result_value( $value, $config ) {
		$format              = $config['format'] ?? 'number';
		$precision           = isset( $config['precision'] ) ? absint( $config['precision'] ) : 2;
		$thousands_separator = $config['thousands_separator'] ?? ',';
		$decimal_separator   = $config['decimal_separator'] ?? '.';

		// Format number.
		$formatted = number_format( $value, $precision, $decimal_separator, $thousands_separator );

		switch ( $format ) {
			case 'currency':
				$symbol   = $config['currency_symbol'] ?? '$';
				$position = $config['currency_position'] ?? 'before';
				if ( 'after' === $position ) {
					$formatted = $formatted . $symbol;
				} else {
					$formatted = $symbol . $formatted;
				}
				break;

			case 'percentage':
				$formatted = $formatted . '%';
				break;
		}

		return $formatted;
	}

	/**
	 * Supports async execution
	 *
	 * @return bool
	 */
	public function supports_async() {
		return true;
	}

	/**
	 * Get execution mode - sync preferred for immediate result
	 *
	 * @return string
	 */
	public function get_execution_mode() {
		return 'sync';
	}
}
