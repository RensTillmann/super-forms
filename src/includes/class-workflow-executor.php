<?php
/**
 * SUPER_Workflow_Executor
 *
 * Executes visual workflow graphs created with the visual builder
 *
 * @package Super_Forms
 * @since   7.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Workflow_Executor' ) ) {
	class SUPER_Workflow_Executor {

		/**
		 * Execute a visual workflow
		 *
		 * @param array $workflow_graph The workflow graph from workflow_graph column
		 * @param array $event_data Data passed from the event
		 * @param int   $form_id The form ID this workflow belongs to
		 *
		 * @return array Result with status and data
		 */
		public static function execute( $workflow_graph, $event_data, $form_id ) {
			error_log( '=== SUPER FORMS: Workflow Executor START ===' );
			error_log( 'Form ID: ' . $form_id );
			error_log( 'Workflow Graph: ' . json_encode( $workflow_graph ) );
			error_log( 'Event Data: ' . json_encode( $event_data ) );

			// Parse workflow graph if JSON string
			if ( is_string( $workflow_graph ) ) {
				$workflow_graph = json_decode( $workflow_graph, true );
			}

			// Validate workflow graph structure
			if ( ! isset( $workflow_graph['nodes'] ) || ! isset( $workflow_graph['connections'] ) ) {
				error_log( 'ERROR: Invalid workflow graph structure' );
				return array(
					'status' => 'error',
					'message' => 'Invalid workflow graph structure',
				);
			}

			$nodes = $workflow_graph['nodes'];
			$connections = $workflow_graph['connections'];

			// Find trigger nodes (entry points)
			$automation_nodes = array_filter(
				$nodes,
				function( $node ) {
					return strpos( $node['type'], 'form.' ) === 0 ||
						   strpos( $node['type'], 'entry.' ) === 0 ||
						   strpos( $node['type'], 'payment.' ) === 0 ||
						   strpos( $node['type'], 'session.' ) === 0;
				}
			);

			if ( empty( $automation_nodes ) ) {
				error_log( 'WARNING: No trigger nodes found in workflow' );
				return array(
					'status' => 'warning',
					'message' => 'No trigger nodes found in workflow',
				);
			}

			// Execution context: one array, passed by reference the whole run, so
			// every node reads what the nodes before it produced.
			$context = array(
				'form_id' => $form_id,
				'event_data' => $event_data,
				'executed_nodes' => array(), // Track which nodes have been executed
				'node_outputs' => array(),   // [node id][output port] => value
				'node_inputs' => array(),    // [node id][input port] => value, from the edge that feeds it
				'variables' => array(),      // set_variable: [name] => value, readable as {name}
				'abort' => false,            // Flag to abort workflow execution
			);

			// Execute workflow starting from trigger nodes
			foreach ( $automation_nodes as $automation_node ) {
				self::execute_node( $automation_node, $nodes, $connections, $context );

				// If workflow was aborted, stop executing other trigger nodes
				if ( $context['abort'] ) {
					break;
				}
			}

			error_log( '=== SUPER FORMS: Workflow Executor END ===' );

			return array(
				'status' => $context['abort'] ? 'aborted' : 'success',
				'executed_nodes' => $context['executed_nodes'],
				'node_outputs' => $context['node_outputs'],
				'variables' => $context['variables'],
				'context' => $context,
			);
		}

		/**
		 * Execute a single node and its downstream connections
		 *
		 * @param array $node The node to execute
		 * @param array $nodes All nodes in the workflow
		 * @param array $connections All connections in the workflow
		 * @param array &$context Execution context (passed by reference)
		 */
		private static function execute_node( $node, $nodes, $connections, &$context ) {
			$node_id = $node['id'];

			// Skip if already executed (prevent infinite loops)
			if ( in_array( $node_id, $context['executed_nodes'], true ) ) {
				error_log( "Node {$node_id} already executed, skipping" );
				return;
			}

			error_log( "Executing node: {$node_id} (type: {$node['type']})" );

			// Mark as executed
			$context['executed_nodes'][] = $node_id;

			// What the edges into this node carry. An edge out of a data port is
			// that node's value for the input port it lands on; the control port
			// (trigger) only activates the node and carries nothing.
			$context['node_inputs'][ $node_id ] = self::gather_node_inputs( $node_id, $connections, $context );

			// Execute node action based on type
			$output = self::execute_node_action( $node, $context );

			// Store what this node produced, per output port, so every node after
			// it reads it as {<node id>.<port>} (module-format.md §8) and a data
			// edge can carry it on.
			$context['node_outputs'][ $node_id ] = ( isset( $output['outputs'] ) && is_array( $output['outputs'] ) )
				? $output['outputs']
				: array();

			// set_variable: the value is readable by name in every later node.
			if ( ! empty( $output['variables'] ) && is_array( $output['variables'] ) ) {
				foreach ( $output['variables'] as $variable_name => $variable_value ) {
					$context['variables'][ $variable_name ] = $variable_value;
				}
			}

			// Check if workflow should abort
			if ( isset( $output['abort'] ) && $output['abort'] === true ) {
				error_log( 'Workflow aborted by node: ' . $node_id );
				$context['abort'] = true;
				return;
			}

			// Find and execute downstream nodes
			$downstream_connections = array_filter(
				$connections,
				function( $conn ) use ( $node_id ) {
					return $conn['from'] === $node_id;
				}
			);

			foreach ( $downstream_connections as $connection ) {
				// Only the control port activates a node (module-format.md §6.1); an
				// edge onto a data port carries a value there and activates nothing.
				$to_input = $connection['toInput'] ?? '';
				if ( '' !== $to_input && 'trigger' !== $to_input ) {
					continue;
				}

				// Check if connection condition is met (for conditional nodes)
				if ( ! self::check_connection_condition( $connection, $output, $node ) ) {
					error_log( "Connection condition not met for: {$connection['id']}" );
					continue;
				}

				// Find target node
				$target_node = self::find_node_by_id( $connection['to'], $nodes );
				if ( $target_node ) {
					self::execute_node( $target_node, $nodes, $connections, $context );

					// If workflow was aborted, stop executing other branches
					if ( $context['abort'] ) {
						break;
					}
				}
			}
		}

		/**
		 * Execute the actual action for a node
		 *
		 * @param array $node The node configuration
		 * @param array $context Execution context
		 *
		 * @return array Output from the node execution
		 */
		private static function execute_node_action( $node, &$context ) {
			$node_id = $node['id'];
			$type = $node['type'];

			// A port id is the declaration's property name (§6.1), so a value an
			// edge carried into an input port is this action's config value under
			// the same name.
			$inputs = isset( $context['node_inputs'][ $node_id ] ) ? $context['node_inputs'][ $node_id ] : array();
			$node_config = isset( $node['config'] ) && is_array( $node['config'] ) ? $node['config'] : array();
			$config = array_merge( $node_config, $inputs );

			$event_data = $context['event_data'];

			error_log( "Executing action for node type: {$type}" );

			// Map node types to actions
			switch ( $type ) {
				// === TRIGGERS (no action: the node's outputs are the event's payload) ===
				case 'form.submitted':
				case 'form.spam_detected':
				case 'entry.created':
				case 'entry.updated':
				case 'payment.stripe.payment_succeeded':
				case 'payment.stripe.payment_failed':
				case 'payment.paypal.payment_completed':
				case 'session.started':
				case 'session.abandoned':
					return array(
						'status' => 'success',
						'outputs' => is_array( $event_data ) ? $event_data : array(),
					);

				// === ACTIONS ===
				case 'send_email':
					return self::action_send_email( $config, $context );

				case 'http_request':
				case 'webhook':
					return self::action_http_request( $config, $context );

				case 'create_post':
					return self::action_create_post( $config, $context );

				case 'update_entry_status':
					return self::action_update_entry_status( $config, $context );

				case 'log_message':
					return self::action_log_message( $config, $context );

				case 'set_variable':
					return self::action_set_variable( $config, $context );

				case 'abort_submission':
					return self::action_abort_submission( $config, $context );

				case 'delay_execution':
					return self::action_delay_execution( $config, $context );

				// === CONDITIONS ===
				case 'conditional_action':
				case 'condition_group':
					return self::evaluate_condition( $config, $context );

				default:
					error_log( "WARNING: Unknown node type: {$type}" );
					return array( 'status' => 'unknown_type' );
			}
		}

		/**
		 * Collect the values the edges into a node carry, by input port
		 *
		 * @param string $node_id The node being executed
		 * @param array  $connections All connections in the workflow
		 * @param array  $context Execution context
		 *
		 * @return array [input port] => value
		 */
		private static function gather_node_inputs( $node_id, $connections, $context ) {
			$inputs = array();

			foreach ( $connections as $connection ) {
				if ( ( $connection['to'] ?? '' ) !== $node_id ) {
					continue;
				}

				$to_input = $connection['toInput'] ?? '';
				if ( '' === $to_input || 'trigger' === $to_input ) {
					continue; // the control port: it activates the node, it carries no value
				}

				$from = $connection['from'] ?? '';
				$from_output = $connection['fromOutput'] ?? '';

				if ( ! isset( $context['node_outputs'][ $from ] ) || ! array_key_exists( $from_output, $context['node_outputs'][ $from ] ) ) {
					continue; // the source has not produced that port
				}

				// Two edges into one input: the last one wins until the port
				// schema's cardinality and combine decide (§6.1).
				$inputs[ $to_input ] = $context['node_outputs'][ $from ][ $from_output ];
			}

			return $inputs;
		}

		/**
		 * Action: Send Email
		 */
		private static function action_send_email( $config, $context ) {
			error_log( 'ACTION: send_email' );

			// Replace tags in email fields
			$to = self::replace_tags( $config['to'] ?? '', $context );
			$subject = self::replace_tags( $config['subject'] ?? 'No Subject', $context );
			$body = self::replace_tags( $config['body'] ?? '', $context );
			$from = self::replace_tags( $config['from'] ?? get_option( 'admin_email' ), $context );
			$replyTo = self::replace_tags( $config['replyTo'] ?? '', $context );

			// Build headers
			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
				"From: {$from}",
			);

			if ( ! empty( $replyTo ) ) {
				$headers[] = "Reply-To: {$replyTo}";
			}

			// Send email
			$sent = wp_mail( $to, $subject, $body, $headers );

			error_log( $sent ? 'Email sent successfully' : 'Failed to send email' );

			return array(
				'status' => $sent ? 'success' : 'error',
				'sent' => $sent,
				'outputs' => array(
					'success' => (bool) $sent,
					'to' => $to,
					'recipients' => $to,
					'subject' => $subject,
					'message' => $body,
				),
			);
		}

		/**
		 * Action: HTTP Request / Webhook
		 */
		private static function action_http_request( $config, $context ) {
			error_log( 'ACTION: http_request' );

			$url = self::replace_tags( $config['url'] ?? '', $context );
			$method = $config['method'] ?? 'POST';
			$headers = $config['headers'] ?? array();
			$body = self::replace_tags( $config['body'] ?? '', $context );

			// Make HTTP request
			$args = array(
				'method' => $method,
				'headers' => $headers,
				'body' => $body,
				'timeout' => 30,
			);

			$response = wp_remote_request( $url, $args );

			if ( is_wp_error( $response ) ) {
				error_log( 'HTTP request failed: ' . $response->get_error_message() );
				return array(
					'status' => 'error',
					'error' => $response->get_error_message(),
					'outputs' => array(
						'success' => false,
						'url' => $url,
						'method' => $method,
						'status_code' => 0,
						'response_body' => '',
						'message' => $response->get_error_message(),
					),
				);
			}

			$status_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );

			error_log( "HTTP request completed with status: {$status_code}" );

			return array(
				'status' => 'success',
				'status_code' => $status_code,
				'response' => $response_body,
				'outputs' => array(
					'success' => true,
					'url' => $url,
					'method' => $method,
					'status_code' => $status_code,
					'response_body' => $response_body,
					'message' => '',
				),
			);
		}

		/**
		 * Action: Create Post
		 */
		private static function action_create_post( $config, $context ) {
			error_log( 'ACTION: create_post' );

			$post_data = array(
				'post_title' => self::replace_tags( $config['postTitle'] ?? 'Untitled', $context ),
				'post_content' => self::replace_tags( $config['postContent'] ?? '', $context ),
				'post_status' => $config['postStatus'] ?? 'draft',
				'post_type' => $config['postType'] ?? 'post',
			);

			$post_id = wp_insert_post( $post_data );

			if ( is_wp_error( $post_id ) ) {
				error_log( 'Failed to create post: ' . $post_id->get_error_message() );
				return array(
					'status' => 'error',
					'error' => $post_id->get_error_message(),
					'outputs' => array(
						'post_id' => 0,
						'message' => $post_id->get_error_message(),
					),
				);
			}

			error_log( "Post created with ID: {$post_id}" );

			return array(
				'status' => 'success',
				'post_id' => $post_id,
				'outputs' => array(
					'post_id' => $post_id,
					'post_url' => get_permalink( $post_id ),
					'message' => '',
				),
			);
		}

		/**
		 * Action: Update Entry Status
		 */
		private static function action_update_entry_status( $config, $context ) {
			error_log( 'ACTION: update_entry_status' );

			$event_data = $context['event_data'];
			$entry_id = $event_data['entry_id'] ?? null;
			$new_status = $config['status'] ?? 'completed';

			if ( ! $entry_id ) {
				error_log( 'ERROR: No entry_id in event data' );
				return array(
					'status' => 'error',
					'message' => 'No entry_id',
					'outputs' => array(
						'entry_id' => null,
						'new_status' => $new_status,
						'message' => 'No entry_id',
					),
				);
			}

			global $wpdb;
			$table_name = $wpdb->prefix . 'superforms_entries';

			$updated = $wpdb->update(
				$table_name,
				array( 'status' => $new_status ),
				array( 'id' => $entry_id ),
				array( '%s' ),
				array( '%d' )
			);

			error_log( $updated ? "Entry {$entry_id} status updated to: {$new_status}" : 'Failed to update entry status' );

			return array(
				'status' => $updated ? 'success' : 'error',
				'updated' => $updated,
				'outputs' => array(
					'entry_id' => $entry_id,
					'new_status' => $new_status,
					'message' => '',
				),
			);
		}

		/**
		 * Action: Log Message
		 */
		private static function action_log_message( $config, $context ) {
			$message = self::replace_tags( $config['message'] ?? '', $context );
			$level = $config['level'] ?? 'info';

			error_log( "[{$level}] {$message}" );

			return array(
				'status' => 'success',
				'outputs' => array(
					'success' => true,
					'message' => $message,
					'level' => $level,
				),
			);
		}

		/**
		 * Action: Set Variable
		 *
		 * Stores a value under a name in this run's context, so a later node
		 * reads it as {name}; the node's own variable_value output carries it too.
		 */
		private static function action_set_variable( $config, $context ) {
			error_log( 'ACTION: set_variable' );

			// The canvas writes variableName/value; the declaration's port ids are
			// variable_name/variable_value (§6.1).
			$variable_name = (string) ( $config['variable_name'] ?? $config['variableName'] ?? '' );
			$variable_name = preg_replace( '/[^a-zA-Z0-9_]/', '', $variable_name );

			if ( '' === $variable_name ) {
				return array(
					'status' => 'error',
					'error' => 'Variable name is required',
					'outputs' => array(
						'success' => false,
						'variable_name' => '',
						'variable_value' => null,
					),
				);
			}

			$raw_value = $config['variable_value'] ?? $config['value'] ?? '';
			$value = self::replace_tags( (string) $raw_value, $context );

			return array(
				'status' => 'success',
				'outputs' => array(
					'success' => true,
					'variable_name' => $variable_name,
					'variable_value' => $value,
					'set_at' => current_time( 'mysql' ),
				),
				'variables' => array( $variable_name => $value ),
			);
		}

		/**
		 * Action: Abort Submission
		 */
		private static function action_abort_submission( $config, $context ) {
			error_log( 'ACTION: abort_submission' );

			$message = self::replace_tags( $config['message'] ?? 'Submission aborted', $context );

			return array(
				'status' => 'success',
				'abort' => true,
				'message' => $message,
			);
		}

		/**
		 * Action: Delay Execution
		 */
		private static function action_delay_execution( $config, $context ) {
			error_log( 'ACTION: delay_execution' );

			$duration = $config['duration'] ?? 1;
			$unit = $config['unit'] ?? 'hours';

			// Convert to seconds
			$seconds = self::convert_duration_to_seconds( $duration, $unit );

			error_log( "Delaying execution for {$duration} {$unit} ({$seconds} seconds)" );

			// Schedule continuation using Action Scheduler
			// Note: This is a simplified version. In production, we'd need to:
			// 1. Store workflow state
			// 2. Schedule continuation
			// 3. Resume from this point later

			return array(
				'status' => 'delayed',
				'duration' => $duration,
				'unit' => $unit,
				'seconds' => $seconds,
			);
		}

		/**
		 * Evaluate Condition
		 */
		private static function evaluate_condition( $config, $context ) {
			error_log( 'CONDITION: evaluating' );

			$operator = $config['operator'] ?? 'AND';
			$rules = $config['rules'] ?? array();

			$results = array();

			foreach ( $rules as $rule ) {
				// A rule's field is a tag or a bare path: {name}, {node_id.port},
				// or the name itself.
				$field = $rule['field'] ?? '';
				$field_value = self::resolve_value( $field, $context );
				$comparison_value = self::resolve_value( $rule['value'] ?? '', $context );
				$rule_operator = $rule['operator'] ?? '==';

				$result = self::compare_values( $field_value, $rule_operator, $comparison_value );
				$results[] = $result;

				error_log( "Rule: {$field} {$rule_operator} {$comparison_value} = " . ( $result ? 'true' : 'false' ) );
			}

			// Evaluate based on AND/OR operator
			$condition_met = $operator === 'AND'
				? ! in_array( false, $results, true )
				: in_array( true, $results, true );

			error_log( "Condition result ({$operator}): " . ( $condition_met ? 'true' : 'false' ) );

			return array(
				'status' => 'success',
				'condition_met' => $condition_met,
				'output_path' => $condition_met ? 'true' : 'false',
				'outputs' => array(),
			);
		}

		/**
		 * Check if a connection condition is met
		 */
		private static function check_connection_condition( $connection, $node_output, $node ) {
			// For conditional nodes, check if output matches connection path
			if ( $node['type'] === 'conditional_action' || $node['type'] === 'condition_group' ) {
				$output_path = $node_output['output_path'] ?? 'true';
				$from_output = $connection['fromOutput'] ?? '';

				return $output_path === $from_output;
			}

			// For other nodes, always proceed
			return true;
		}

		/**
		 * Replace tags in a string with the values this run holds
		 */
		private static function replace_tags( $string, $context ) {
			if ( ! is_string( $string ) || '' === $string ) {
				return $string;
			}

			// Replace {tag} format
			preg_match_all( '/\{([^}]+)\}/', $string, $matches );

			foreach ( $matches[1] as $tag ) {
				$value = self::resolve_path( $tag, $context );
				if ( null === $value ) {
					continue; // unknown tag: leave it as written
				}

				$string = str_replace( '{' . $tag . '}', self::stringify( $value ), $string );
			}

			return $string;
		}

		/**
		 * Resolve a tag or bare path (field, {field}, node_id.port, variable name)
		 *
		 * @param mixed $path A tag, or a value that is not a string
		 * @param array $context Execution context
		 *
		 * @return mixed The value (a string when nothing declares the path)
		 */
		private static function resolve_value( $path, $context ) {
			if ( ! is_string( $path ) || '' === $path ) {
				return $path;
			}

			$trimmed = preg_match( '/^\{([^}]+)\}$/', $path, $match ) ? $match[1] : $path;
			$value = self::resolve_path( $trimmed, $context );

			return null === $value ? $path : $value;
		}

		/**
		 * Walk a dotted path through the run's values
		 *
		 * @param string $path node_id.port[.key], a variable name, or an event key
		 * @param array  $context Execution context
		 *
		 * @return mixed|null
		 */
		private static function resolve_path( $path, $context ) {
			$parts = explode( '.', $path );
			$node_id = $parts[0];

			if ( count( $parts ) > 1 && isset( $context['node_outputs'][ $node_id ] ) ) {
				$value = $context['node_outputs'][ $node_id ];
				foreach ( array_slice( $parts, 1 ) as $part ) {
					if ( ! is_array( $value ) || ! array_key_exists( $part, $value ) ) {
						return null;
					}
					$value = $value[ $part ];
				}
				return $value;
			}

			if ( isset( $context['variables'] ) && array_key_exists( $path, $context['variables'] ) ) {
				return $context['variables'][ $path ];
			}

			$event_data = $context['event_data'] ?? array();
			if ( is_array( $event_data ) && array_key_exists( $path, $event_data ) ) {
				return $event_data[ $path ];
			}

			return null;
		}

		/**
		 * A tag's value as the text it is written into
		 */
		private static function stringify( $value ) {
			if ( is_array( $value ) || is_object( $value ) ) {
				return wp_json_encode( $value );
			}
			if ( is_bool( $value ) ) {
				return $value ? 'true' : 'false';
			}

			return (string) $value;
		}

		/**
		 * Compare two values based on operator
		 */
		private static function compare_values( $value1, $operator, $value2 ) {
			switch ( $operator ) {
				case '==':
				case '=':
					return $value1 == $value2;
				case '!=':
					return $value1 != $value2;
				case '>':
					return floatval( $value1 ) > floatval( $value2 );
				case '<':
					return floatval( $value1 ) < floatval( $value2 );
				case '>=':
					return floatval( $value1 ) >= floatval( $value2 );
				case '<=':
					return floatval( $value1 ) <= floatval( $value2 );
				case 'contains':
					return strpos( $value1, $value2 ) !== false;
				case 'not_contains':
					return strpos( $value1, $value2 ) === false;
				default:
					return false;
			}
		}

		/**
		 * Convert duration to seconds
		 */
		private static function convert_duration_to_seconds( $duration, $unit ) {
			switch ( $unit ) {
				case 'minutes':
					return $duration * 60;
				case 'hours':
					return $duration * 3600;
				case 'days':
					return $duration * 86400;
				default:
					return $duration;
			}
		}

		/**
		 * Find node by ID
		 */
		private static function find_node_by_id( $node_id, $nodes ) {
			foreach ( $nodes as $node ) {
				if ( $node['id'] === $node_id ) {
					return $node;
				}
			}
			return null;
		}
	}
}
