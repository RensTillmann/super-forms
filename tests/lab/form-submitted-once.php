<?php
/**
 * Regression check: one form submission fires form.submitted exactly once.
 *
 * Why this is not a PHPUnit test: SUPER_Ajax::submit_form() ends in die() and
 * needs a whole WordPress install (forms table, entries, mail), so the flow it
 * guards cannot run inside the plugin's PHPUnit suites. This script runs once
 * against a live site with the plugin active, submits one form, and exits 0
 * only when that one submission produced one form.submitted event, one
 * automation run, one mail and one entry. The check fails while a submission
 * reaches two call sites of the event.
 *
 * Usage, inside a WordPress container:
 *
 *   php tests/lab/form-submitted-once.php [--form-id=1] [--wp-root=/var/www/html]
 *
 * Without --form-id it copies the site's first published form, so the run does
 * not depend on a form it did not make. It deletes that copy, the entry and
 * the automation it created before it exits.
 *
 * Exit codes: 0 all four counts are 1; 1 a count is not 1; 2 the run could not
 * start (no WordPress, no form).
 *
 * @package Super_Forms
 * @subpackage Tests/Lab
 * @since 6.5.0
 */

$sf_lab_options = array(
	'form-id' => 0,
	'wp-root' => '/var/www/html',
);

foreach ( array_slice( $argv, 1 ) as $sf_lab_arg ) {
	if ( preg_match( '/^--([a-z-]+)=(.+)$/', $sf_lab_arg, $sf_lab_match ) ) {
		$sf_lab_options[ $sf_lab_match[1] ] = $sf_lab_match[2];
	}
}

if ( ! defined( 'ABSPATH' ) ) {
	// The submission handler lives in includes/class-ajax.php, which the plugin
	// loads only for an ajax request.
	if ( ! defined( 'DOING_AJAX' ) ) {
		define( 'DOING_AJAX', true );
	}
	define( 'WP_USE_THEMES', false );
	require rtrim( $sf_lab_options['wp-root'], '/' ) . '/wp-load.php';
}

$sf_lab = array(
	'form_id'       => 0,
	'automation_id' => 0,
	'firings'       => 0,
	'runs'          => array(),
	'mails'         => array(),
	'response'      => '',
);

$sf_lab_name     = 'Lab: form.submitted once';
$sf_lab_subject  = $sf_lab_name . ' (mail)';
$sf_lab_message  = $sf_lab_name . ' (log line)';

/* ---------------------------------------------------------------- hooks --- */

// Count the submission event itself, one callback call per firing.
add_action(
	'super_automation_event',
	function ( $event_id ) use ( &$sf_lab ) {
		if ( 'form.submitted' === $event_id ) {
			$sf_lab['firings']++;
		}
	},
	10,
	2
);

// Count what each firing executed: our automation is one entry in the results.
add_action(
	'super_after_automation_event',
	function ( $event_id, $context, $results ) use ( &$sf_lab ) {
		if ( 'form.submitted' === $event_id ) {
			$sf_lab['runs'][] = isset( $results[ $sf_lab['automation_id'] ] ) ? 1 : 0;
		}
	},
	10,
	3
);

// Count the mails this run produced, by subject, without sending them anywhere.
add_filter(
	'pre_wp_mail',
	function ( $short_circuit, $atts ) use ( &$sf_lab, $sf_lab_subject ) {
		if ( isset( $atts['subject'] ) && $sf_lab_subject === $atts['subject'] ) {
			$sf_lab['mails'][] = $atts['subject'];
		}
		return $short_circuit;
	},
	20,
	2
);

/**
 * Read the result, report it, remove what the run made, then set the exit code.
 * submit_form() exits, so this is the only place the counts can be read.
 */
register_shutdown_function(
	function () use ( &$sf_lab, $sf_lab_name ) {
		// submit_form() answers through the output buffer this run opened.
		if ( ob_get_level() > 0 ) {
			$sf_lab['response'] = (string) ob_get_clean();
		}

		$firings = (int) $sf_lab['firings'];
		$runs    = array_sum( $sf_lab['runs'] );
		$mails   = count( $sf_lab['mails'] );
		$entries = 0;

		if ( $sf_lab['form_id'] ) {
			global $wpdb;
			// The entries table, or the post type when a site still stores there.
			$entries = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}superforms_entries WHERE form_id = %d", $sf_lab['form_id'] )
			);
			if ( 0 === $entries ) {
				$entries = (int) $wpdb->get_var(
					$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'super_contact_entry' AND post_parent = %d", $sf_lab['form_id'] )
				);
			}
		}

		$counts = array(
			'form.submitted firings' => $firings,
			'automation runs'        => $runs,
			'mails'                  => $mails,
			'entries'                => $entries,
		);

		$failed = array();
		echo "\n";
		foreach ( $counts as $what => $count ) {
			$pass     = ( 1 === $count );
			$failed[] = $pass ? null : $what;
			printf( "%s %s: %d (expected 1)\n", $pass ? 'PASS' : 'FAIL', $what, $count );
		}
		$failed = array_filter( $failed );

		if ( '' !== $sf_lab['response'] ) {
			printf( "submit_form answered: %s\n", trim( mb_substr( $sf_lab['response'], 0, 400 ) ) );
		}

		// Remove the automation, the entry and the form this run created.
		global $wpdb;
		if ( $sf_lab['automation_id'] ) {
			$wpdb->delete( $wpdb->prefix . 'superforms_automation_actions', array( 'automation_id' => $sf_lab['automation_id'] ) );
			$wpdb->delete( $wpdb->prefix . 'superforms_automations', array( 'id' => $sf_lab['automation_id'] ) );
		}
		if ( $sf_lab['form_id'] ) {
			$wpdb->delete( $wpdb->prefix . 'superforms_entries', array( 'form_id' => $sf_lab['form_id'] ) );
			$post_ids = $wpdb->get_col(
				$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'super_contact_entry' AND post_parent = %d", $sf_lab['form_id'] )
			);
			foreach ( $post_ids as $sf_lab_post_id ) {
				wp_delete_post( (int) $sf_lab_post_id, true );
			}
			SUPER_Form_DAL::delete( $sf_lab['form_id'] );
		}

		echo $failed
			? 'RESULT: FAIL (' . implode( ', ', $failed ) . " not 1 for one submission)\n"
			: "RESULT: PASS (one submission fired form.submitted once)\n";

		exit( $failed ? 1 : 0 );
	}
);

/* ----------------------------------------------------------------- setup --- */

$sf_lab_source_id = absint( $sf_lab_options['form-id'] );
if ( ! $sf_lab_source_id ) {
	global $wpdb;
	$sf_lab_source_id = (int) $wpdb->get_var(
		"SELECT id FROM {$wpdb->prefix}superforms_forms WHERE status = 'publish' ORDER BY id ASC LIMIT 1"
	);
}

$sf_lab_source = $sf_lab_source_id ? SUPER_Form_DAL::get( $sf_lab_source_id ) : null;
if ( ! $sf_lab_source ) {
	fwrite( STDERR, "No form to copy: pass --form-id=<a form that stores entries>.\n" );
	exit( 2 );
}

// A copy, so the run neither reads nor changes the site's own forms.
$sf_lab_form_id = SUPER_Form_DAL::duplicate( $sf_lab_source_id );
if ( is_wp_error( $sf_lab_form_id ) ) {
	fwrite( STDERR, 'Could not copy the form: ' . $sf_lab_form_id->get_error_message() . "\n" );
	exit( 2 );
}
SUPER_Form_DAL::update( $sf_lab_form_id, array( 'status' => 'publish', 'name' => $sf_lab_name ) );
$sf_lab['form_id'] = $sf_lab_form_id;

// One automation: the event, a mail and a log line, so a firing has two
// visible effects besides the event count. A visual graph, because the list
// engine's send_email needs a recipient in the event context.
$sf_lab_automation_id = SUPER_Automation_DAL::create_automation(
	array(
		'name'           => $sf_lab_name,
		'type'           => 'visual',
		'workflow_graph' => array(
			'nodes'       => array(
				array( 'id' => 'event-1', 'type' => 'form.submitted', 'config' => array( 'scope' => 'all' ) ),
				array(
					'id'     => 'mail-1',
					'type'   => 'send_email',
					'config' => array(
						'to'      => get_option( 'admin_email' ),
						'from'    => get_option( 'admin_email' ),
						'subject' => $sf_lab_subject,
						'body'    => 'One submission, one mail.',
					),
				),
				array(
					'id'     => 'log-1',
					'type'   => 'log_message',
					'config' => array(
						'message'   => $sf_lab_message,
						'log_level' => 'info',
					),
				),
			),
			'connections' => array(
				array( 'id' => 'c1', 'from' => 'event-1', 'fromOutput' => 'fired', 'to' => 'mail-1', 'toInput' => 'trigger' ),
				array( 'id' => 'c2', 'from' => 'event-1', 'fromOutput' => 'fired', 'to' => 'log-1', 'toInput' => 'trigger' ),
			),
		),
		'enabled'        => 1,
	)
);
if ( is_wp_error( $sf_lab_automation_id ) ) {
	fwrite( STDERR, 'Could not create the automation: ' . $sf_lab_automation_id->get_error_message() . "\n" );
	exit( 2 );
}
$sf_lab['automation_id'] = $sf_lab_automation_id;

// The field values the front end posts: one per text-like element of the form.
$sf_lab_data = array();
foreach ( (array) $sf_lab_source->elements as $sf_lab_element ) {
	$name = isset( $sf_lab_element['data']['name'] ) ? (string) $sf_lab_element['data']['name'] : '';
	$tag  = isset( $sf_lab_element['tag'] ) ? (string) $sf_lab_element['tag'] : '';
	if ( '' === $name || ! in_array( $tag, array( 'text', 'textarea', 'email', 'tel', 'number', 'url', 'date' ), true ) ) {
		continue;
	}
	$is_email       = ( 'email' === $tag || false !== strpos( $name, 'email' ) );
	$sf_lab_data[] = array(
		'name'  => $name,
		'type'  => 'email' === $tag ? 'email' : 'text',
		'value' => $is_email ? 'lab@example.com' : 'Lab Tester',
	);
}

/* -------------------------------------------------------------- submit --- */

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST']      = (string) wp_parse_url( home_url(), PHP_URL_HOST );
$_SERVER['REQUEST_URI']    = '/';

$_POST = array(
	'action'          => 'super_submit_form',
	'form_id'         => $sf_lab_form_id,
	'hidden_form_id'  => $sf_lab_form_id,
	'super_hp'        => '',
	'data'            => wp_json_encode( $sf_lab_data ),
);

// The same bypass the front end's own request gets from its Origin header.
$GLOBALS['super_csrf'] = true;

ob_start();
SUPER_Ajax::submit_form(); // Ends in die(); the shutdown function above reads the result.
