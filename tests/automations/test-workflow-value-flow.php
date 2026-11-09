<?php
/**
 * Value flow: a node's output reaches the node after it
 *
 * The visual engine walks a graph and the list engine runs its actions in
 * order; both must carry what one node produced into the next one, instead of
 * handing every node the original event data. These tests pin that contract
 * (module-format.md §6.1 ports, §8 tags).
 *
 * Runs without a WordPress install: the few WP functions the executor touches
 * are stubbed below when WordPress has not defined them, so this file also runs
 * inside the plugin's WordPress test suites.
 *
 * @package Super_Forms
 * @subpackage Tests/Automations
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value ) {
		return json_encode( $value );
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type ) {
		return '2026-11-09 12:00:00';
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		return $default;
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;

		public function __construct( $code = '', $message = '' ) {
			$this->code = $code;
			$this->message = $message;
		}

		public function get_error_message() {
			return $this->message;
		}

		public function get_error_code() {
			return $this->code;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'wp_mail' ) ) {
	function wp_mail( $to, $subject, $body, $headers = array() ) {
		$GLOBALS['super_forms_test_mails'][] = array(
			'to' => $to,
			'subject' => $subject,
			'body' => $body,
		);
		return true;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

require_once dirname( __DIR__, 2 ) . '/src/includes/class-workflow-executor.php';
require_once dirname( __DIR__, 2 ) . '/src/includes/automations/class-action-base.php';
require_once dirname( __DIR__, 2 ) . '/src/includes/automations/actions/class-action-set-variable.php';
require_once dirname( __DIR__, 2 ) . '/src/includes/automations/class-automation-executor.php';

if ( ! class_exists( 'SUPER_Test_Tag_Action' ) ) {
	/**
	 * An action that exposes the base class's tag replacement.
	 */
	class SUPER_Test_Tag_Action extends SUPER_Action_Base {

		public function get_id() {
			return 'test_tag';
		}

		public function get_label() {
			return 'Test tag';
		}

		public function execute( $context, $config ) {
			return array( 'success' => true );
		}

		public function replace( $string, $context ) {
			return $this->replace_tags( $string, $context );
		}
	}
}

/**
 * What one node hands the node after it.
 */
class SUPER_Workflow_Value_Flow_Test extends PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		$GLOBALS['super_forms_test_mails'] = array();
	}

	/**
	 * A value set by one node arrives at the next node over the edge between them,
	 * and its name is readable as a tag there.
	 */
	public function test_visual_engine_carries_a_nodes_value_to_the_next_node() {
		$graph = array(
			'nodes' => array(
				array( 'id' => 'evt', 'type' => 'form.submitted', 'config' => array() ),
				array(
					'id' => 'a',
					'type' => 'set_variable',
					'config' => array(
						'variableName' => 'greeting',
						'value' => 'Dear {session_key}',
					),
				),
				array(
					'id' => 'b',
					'type' => 'send_email',
					'config' => array(
						'to' => 'admin@example.com',
						'from' => 'from@example.com',
						'subject' => 'Greeting: {greeting}',
						'body' => '',
					),
				),
			),
			'connections' => array(
				array( 'id' => 'c1', 'from' => 'evt', 'fromOutput' => 'entry_data', 'to' => 'a', 'toInput' => 'trigger' ),
				array( 'id' => 'c2', 'from' => 'a', 'fromOutput' => 'done', 'to' => 'b', 'toInput' => 'trigger' ),
				array( 'id' => 'c3', 'from' => 'a', 'fromOutput' => 'variable_value', 'to' => 'b', 'toInput' => 'body' ),
			),
		);

		$result = SUPER_Workflow_Executor::execute(
			$graph,
			array(
				'session_key' => 'sess-1',
				'entry_id' => 42,
				'form_id' => 7,
			),
			7
		);

		$this->assertSame( 'success', $result['status'] );

		// The value node A produced, not the event payload.
		$this->assertSame( 'Dear sess-1', $result['node_outputs']['a']['variable_value'] );
		$this->assertSame( 'Dear sess-1', $result['variables']['greeting'] );

		// Node B read it, both over the edge and as a tag.
		$this->assertCount( 1, $GLOBALS['super_forms_test_mails'] );
		$this->assertSame( 'Dear sess-1', $GLOBALS['super_forms_test_mails'][0]['body'] );
		$this->assertSame( 'Greeting: Dear sess-1', $GLOBALS['super_forms_test_mails'][0]['subject'] );
	}

	/**
	 * An action's output port is readable downstream as {node_id.port}.
	 */
	public function test_visual_engine_reads_a_nodes_output_port_by_tag() {
		$graph = array(
			'nodes' => array(
				array( 'id' => 'evt', 'type' => 'form.submitted', 'config' => array() ),
				array(
					'id' => 'logger',
					'type' => 'log_message',
					'config' => array( 'message' => 'Message for {session_key}', 'level' => 'info' ),
				),
				array(
					'id' => 'mail',
					'type' => 'send_email',
					'config' => array(
						'to' => 'admin@example.com',
						'from' => 'from@example.com',
						'subject' => 'relayed',
						'body' => 'A said: {logger.message}',
					),
				),
			),
			'connections' => array(
				array( 'id' => 'c1', 'from' => 'evt', 'fromOutput' => 'entry_data', 'to' => 'logger', 'toInput' => 'trigger' ),
				array( 'id' => 'c2', 'from' => 'logger', 'fromOutput' => 'done', 'to' => 'mail', 'toInput' => 'trigger' ),
			),
		);

		$result = SUPER_Workflow_Executor::execute( $graph, array( 'session_key' => 'sess-2' ), 7 );

		$this->assertSame( 'Message for sess-2', $result['node_outputs']['logger']['message'] );
		$this->assertSame( 'A said: Message for sess-2', $GLOBALS['super_forms_test_mails'][0]['body'] );
	}

	/**
	 * Condition nodes keep branching on the branch label from the connection.
	 */
	public function test_visual_engine_keeps_condition_branching() {
		$graph = array(
			'nodes' => array(
				array( 'id' => 'evt', 'type' => 'form.submitted', 'config' => array() ),
				array(
					'id' => 'cond',
					'type' => 'conditional_action',
					'config' => array(
						'operator' => 'AND',
						'rules' => array(
							array( 'field' => '{entry_id}', 'operator' => '==', 'value' => '42' ),
						),
					),
				),
				array(
					'id' => 'yes',
					'type' => 'send_email',
					'config' => array( 'to' => 'yes@example.com', 'from' => 'from@example.com', 'subject' => 'true-path', 'body' => '' ),
				),
				array(
					'id' => 'no',
					'type' => 'send_email',
					'config' => array( 'to' => 'no@example.com', 'from' => 'from@example.com', 'subject' => 'false-path', 'body' => '' ),
				),
			),
			'connections' => array(
				array( 'id' => 'c1', 'from' => 'evt', 'fromOutput' => 'entry_data', 'to' => 'cond', 'toInput' => 'trigger' ),
				array( 'id' => 'c2', 'from' => 'cond', 'fromOutput' => 'true', 'to' => 'yes', 'toInput' => 'trigger' ),
				array( 'id' => 'c3', 'from' => 'cond', 'fromOutput' => 'false', 'to' => 'no', 'toInput' => 'trigger' ),
			),
		);

		$result = SUPER_Workflow_Executor::execute( $graph, array( 'entry_id' => 42 ), 7 );

		$this->assertSame( array(), $result['node_outputs']['cond'] );
		$this->assertCount( 1, $GLOBALS['super_forms_test_mails'] );
		$this->assertSame( 'true-path', $GLOBALS['super_forms_test_mails'][0]['subject'] );
	}

	/**
	 * The list engine merges what an action produced into the context the next
	 * action reads.
	 */
	public function test_list_engine_carries_a_set_variable_to_the_next_action() {
		$set_variable = new SUPER_Action_Set_Variable();
		$result = $set_variable->execute(
			array( 'entry_id' => 42 ),
			array(
				'variable_name' => 'greeting',
				'variable_value' => 'Dear customer',
			)
		);

		$this->assertSame( array( 'greeting' => 'Dear customer' ), $result['variables'] );

		$absorb = new ReflectionMethod( 'SUPER_Automation_Executor', 'absorb_action_result' );
		$absorb->setAccessible( true );

		$context = $absorb->invoke(
			null,
			array( 'entry_id' => 42 ),
			array( 'id' => 11, 'action_type' => 'set_variable' ),
			$result
		);

		$this->assertSame( 'Dear customer', $context['variables']['greeting'] );

		// The next action's tag replacement finds it.
		$action = new SUPER_Test_Tag_Action();
		$next_context = $absorb->invoke(
			null,
			$context,
			array( 'id' => 12, 'action_type' => 'send_email' ),
			array( 'outputs' => array( 'message' => 'sent' ) )
		);

		$this->assertSame( 'Hello Dear customer', $action->replace( 'Hello {greeting}', $next_context ) );
		$this->assertSame( 'Port: sent', $action->replace( 'Port: {12.message}', $next_context ) );
	}
}
