<?php
/**
 * Regression coverage for server-owned submission carrier contracts.
 *
 * @package Super_Forms_Tests
 */

class Test_Super_Forms_Submission_Contract_Security extends WP_UnitTestCase {
    public static function set_up_before_class() {
        parent::set_up_before_class();
        // SUPER_Ajax is loaded only on AJAX requests; these tests call it directly.
        if( !class_exists('SUPER_Ajax') ) {
            require_once SUPER_PLUGIN_DIR . '/includes/class-ajax.php';
        }
    }

    public function test_phone_length_and_zero_maximum_match_browser_contract() {
        $validate = new ReflectionMethod( 'SUPER_Ajax', 'submission_value_matches_validation' );
        $validate->setAccessible( true );
        $phone = array( 'validation' => 'phone' );
        $this->assertFalse( $validate->invoke( null, '123456789', $phone ) );
        $this->assertTrue( $validate->invoke( null, '1234567890', $phone ) );
        $this->assertTrue( $validate->invoke( null, str_repeat('1', 20), $phone ) );
        $this->assertFalse( $validate->invoke( null, str_repeat('1', 21), $phone ) );
        $this->assertTrue( $validate->invoke( null, 'any text', array( 'maxlength' => '0' ) ) );
        $this->assertFalse( $validate->invoke( null, 'any text', array( 'maxlength' => '1' ) ) );
    }

    public function test_time_picker_bounds_are_not_text_length_requirements() {
        $elements = array( array( 'tag' => 'time', 'data' => array(
            'name' => 'from_time', 'minlength' => '09:00', 'maxlength' => '18:00',
        ) ) );
        $contract = array();
        $collect = new ReflectionMethod( 'SUPER_Ajax', 'collect_submission_field_contract' );
        $collect->setAccessible( true );
        $args = array( $elements, &$contract, 0, 41 );
        $collect->invokeArgs( null, $args );
        $this->assertSame( 'skip', $contract['from_time']['length_mode'] );
        $validate = new ReflectionMethod( 'SUPER_Ajax', 'submission_value_matches_validation' );
        $validate->setAccessible( true );
        $this->assertTrue( $validate->invoke( null, '10:00', $contract['from_time'] ) );
    }

    public function test_numeric_upload_field_keys_match_the_declared_file_route() {
        $parallel = new ReflectionMethod( 'SUPER_Ajax', 'upload_files_are_parallel' );
        $parallel->setAccessible( true );
        $files = array();
        foreach( array( 'name' => 'test.pdf', 'type' => 'application/pdf', 'tmp_name' => '/scratch/test.pdf', 'error' => 0, 'size' => 128 ) as $part => $value ) {
            $files[$part] = array( 123 => array( $value ) );
        }
        $this->assertTrue( $parallel->invoke(null, $files) );
        $collect = new ReflectionMethod( 'SUPER_Ajax', 'collect_submission_file_routes' );
        $collect->setAccessible( true );
        $routes = array();
        $collect->invokeArgs(null, array(array(array('tag'=>'file', 'data'=>array('name'=>'123'))), &$routes));
        $identity = new ReflectionMethod( 'SUPER_Ajax', 'upload_request_field_identities' );
        $identity->setAccessible( true );
        $result = $identity->invoke(null, $files['name'], $routes);
        $this->assertSame( '123', $result[123]['stored_field_name'] );
        $_POST['file_field_map'] = array(123=>'0123');
        $this->assertFalse( $identity->invoke(null, $files['name'], $routes) );
    }

    public function test_numeric_field_names_survive_json_decoding_and_keep_exact_contracts() {
        foreach( array( '123', '0', '001', '2147483648' ) as $name ) {
            $elements = array( array( 'tag' => 'text', 'data' => array( 'name' => $name, 'validation' => 'none' ) ) );
            $data = array_intersect_key( $this->production_repeater_data(), array_flip( array( 'hidden_form_id', 'hidden_contact_entry_id' ) ) );
            $data[$name] = array( 'name' => $name, 'type' => 'var', 'value' => 'Numeric name value' );
            $data = json_decode( wp_json_encode( (object)$data ), true );
            $this->assertTrue( $this->validate( $data, $elements ), 'Stored numeric name: ' . $name );
            $rebuilt = $this->rebuild_selection_entry_data( $data, $elements );
            $this->assertTrue( is_array($rebuilt), 'Rebuilt numeric name: ' . $name );
            $this->assertSame( 'Numeric name value', $rebuilt[$name]['value'] );
            $data[$name]['name'] = 'different_name';
            $this->assertFalse( $this->validate( $data, $elements ) );
        }
    }

    public function test_numeric_repeater_names_match_both_rows_and_reject_changed_aliases() {
        foreach( array( '123', '0' ) as $name ) {
            $elements = json_decode( str_replace( array('guest_name', 'guest_document'), array($name, '456'), wp_json_encode($this->repeater_elements()) ), true );
            $data = json_decode( str_replace( array('guest_name', 'guest_document'), array($name, '456'), wp_json_encode($this->production_repeater_data()) ), true );
            $this->assertTrue( $this->validate( $data, $elements ), 'Numeric repeater: ' . $name );
            $missing_group = $data;
            unset($missing_group['_super_dynamic_data']);
            $this->assertFalse( $this->validate( $missing_group, $elements ) );
            $data[$name . '_2']['value'] = 'Forged alias';
            $this->assertFalse( $this->validate( $data, $elements ) );
        }
    }

    public function test_numeric_zero_names_retain_requiredness_and_single_field_repeater_rows() {
        $collect = new ReflectionMethod( 'SUPER_Ajax', 'collect_required_fields' );
        $collect->setAccessible( true );
        $required = $collect->invoke(null, array(array('tag'=>'text', 'data'=>array('name'=>'0', 'validation'=>'required'))));
        $this->assertArrayHasKey( 0, $required );
        $this->assertTrue( $required[0]['always_present'] );
        $this->assertFalse( $this->files_match_stored_policy(array(), array(array('tag'=>'file', 'data'=>array('name'=>'0', 'minlength'=>'1')))) );
        $elements = json_decode(str_replace('guest_name', '0', wp_json_encode($this->repeater_elements())), true);
        unset($elements[0]['inner'][1]);
        $data = json_decode(str_replace('guest_name', '0', wp_json_encode($this->production_repeater_data())), true);
        unset($data['guest_document'], $data['guest_document_2']);
        unset($data['_super_dynamic_data'][0][0]['guest_document'], $data['_super_dynamic_data'][0][1]['guest_document_2']);
        $this->assertTrue( $this->validate($data, $elements) );
        $data['_super_dynamic_data'][0][0][1] = $data['_super_dynamic_data'][0][0][0];
        $this->assertFalse( $this->validate($data, $elements) );
    }

    public function test_numeric_file_names_do_not_skip_stored_size_policy() {
        $elements = array( array( 'tag' => 'file', 'data' => array( 'name' => '123', 'filesize' => '1' ) ) );
        $data = json_decode( '{"123":{"type":"files","files":[{"size":2097152}]}}', true );
        $this->assertFalse( $this->files_match_stored_policy( $data, $elements ) );
        $data[123]['files'][0]['size'] = 128;
        $this->assertTrue( $this->files_match_stored_policy( $data, $elements ) );
        $data[999] = $data[123];
        $this->assertFalse( $this->files_match_stored_policy( $data, $elements ) );
    }

    private function rebuild_selection_entry_data( $data, $elements, $form_id=41 ) {
        $method = new ReflectionMethod( 'SUPER_Ajax', 'rebuild_selection_entry_values' );
        $method->setAccessible( true );
        return $method->invoke( null, $data, $elements, $form_id );
    }
    private function files_match_stored_policy( $data, $elements ) {
        $method = new ReflectionMethod( 'SUPER_Ajax', 'submission_files_match_stored_policy' );
        $method->setAccessible( true );
        return $method->invoke( null, $data, $elements );
    }
    private function validate( $data, $elements, $form_id=41, $entry_id='', $list_id='' ) {
        $method = new ReflectionMethod( 'SUPER_Ajax', 'submission_data_matches_contract' );
        $method->setAccessible( true );
        return $method->invoke( null, $data, $elements, $form_id, $entry_id, $list_id );
    }

    private function elements() {
        return array(
            array(
                'tag' => 'text',
                'data' => array(
                    'name' => 'address',
                    'enable_address_auto_complete' => 'true',
                    'may_be_empty' => 'false',
                ),
            ),
            array(
                'tag' => 'html',
                'data' => array(
                    'name' => 'formatted_note',
                    'may_be_empty' => 'false',
                ),
            ),
        );
    }

    private function production_data( $location ) {
        return array(
            'address' => array(
                'name' => 'address',
                'value' => 'Damrak 1, Amsterdam',
                'label' => 'Address',
                'exclude' => 'false',
                'replace_commas' => 'false',
                'exclude_entry' => 'false',
                'excludeconditional' => 'false',
                'type' => 'google_address',
                'geometry' => array( 'location' => $location ),
            ),
            'formatted_note' => array(
                'name' => 'formatted_note',
                'value' => '<strong>Confirmed</strong>',
                'label' => 'Formatted note',
                'exclude' => 'false',
                'replace_commas' => 'false',
                'exclude_entry' => 'false',
                'excludeconditional' => 'false',
                'type' => 'html',
            ),
            'hidden_form_id' => array(
                'name' => 'hidden_form_id',
                'value' => '41',
                'type' => 'form_id',
            ),
            'hidden_contact_entry_id' => array(
                'name' => 'hidden_contact_entry_id',
                'value' => '',
                'type' => 'entry_id',
            ),
        );
    }

    private function repeater_elements() {
        return array(
            array(
                'tag' => 'column',
                'data' => array( 'duplicate' => 'enabled' ),
                'inner' => array(
                    array(
                        'tag' => 'text',
                        'data' => array( 'name' => 'guest_name', 'validation' => 'none' ),
                    ),
                    array(
                        'tag' => 'file',
                        'data' => array( 'name' => 'guest_document' ),
                    ),
                ),
            ),
        );
    }

    private function production_repeater_data() {
        $data = array(
            'hidden_form_id' => array(
                'name' => 'hidden_form_id',
                'value' => '41',
                'type' => 'form_id',
            ),
            'hidden_contact_entry_id' => array(
                'name' => 'hidden_contact_entry_id',
                'value' => '',
                'type' => 'entry_id',
            ),
            '_super_dynamic_data' => array(
                'guest_name' => array(
                    array(
                        'guest_name' => array(
                            'name' => 'guest_name',
                            'value' => 'Ada',
                            'label' => 'Guest name',
                            'exclude' => 'false',
                            'replace_commas' => 'false',
                            'exclude_entry' => 'false',
                            'excludeconditional' => 'false',
                            'type' => 'var',
                        ),
                        'guest_document' => array(
                            'label' => 'Guest document',
                            'type' => 'files',
                            'exclude' => 'false',
                            'exclude_entry' => 'false',
                            'files' => array(),
                        ),
                    ),
                    // A duplicated row's fields carry the browser's suffixed route names
                    // (guest_name_2); a repeated bare route would be a duplicate carrier.
                    array(
                        'guest_name_2' => array(
                            'name' => 'guest_name_2',
                            'value' => 'Grace',
                            'label' => 'Guest name',
                            'exclude' => 'false',
                            'replace_commas' => 'false',
                            'exclude_entry' => 'false',
                            'excludeconditional' => 'false',
                            'type' => 'var',
                        ),
                    ),
                ),
            ),
        );
        // SUPER.collect_dynamic_columns_data() copies each row carrier from the top-level
        // form data, so the browser submits every row route at the top level as well.
        foreach( $data['_super_dynamic_data']['guest_name'] as $row ) {
            foreach( $row as $route_name => $carrier ) {
                $data[$route_name] = $carrier;
            }
        }
        return $data;
    }

    public function test_production_address_and_html_carriers_match_the_stored_schema() {
        $this->assertTrue( $this->validate(
            $this->production_data( array( 'lat' => '52.377956', 'lng' => '4.897070' ) ),
            $this->elements()
        ) );
        $this->assertTrue(
            $this->validate( $this->production_data( array() ), $this->elements() ),
            'The browser emits an empty location object when an address was typed without selecting a place.'
        );
    }

    public function test_google_address_geometry_rejects_non_browser_shapes() {
        $valid = $this->production_data( array( 'lat' => 52.377956, 'lng' => 4.897070 ) );
        $cases = array();

        $candidate = $valid;
        unset( $candidate['address']['geometry'] );
        $cases['missing geometry'] = $candidate;

        $candidate = $valid;
        $candidate['address']['geometry'] = array();
        $cases['missing location'] = $candidate;

        $candidate = $valid;
        $candidate['address']['geometry']['location'] = array( 'lat' => 52.377956 );
        $cases['one coordinate'] = $candidate;

        $candidate = $valid;
        $candidate['address']['geometry']['location']['lat'] = 'north';
        $cases['non-numeric coordinate'] = $candidate;

        $candidate = $valid;
        $candidate['address']['geometry']['location']['lat'] = 91;
        $cases['latitude out of range'] = $candidate;

        $candidate = $valid;
        $candidate['address']['geometry']['location']['accuracy'] = 1;
        $cases['extra location member'] = $candidate;

        $candidate = $valid;
        $candidate['address']['geometry']['viewport'] = array();
        $cases['extra geometry member'] = $candidate;

        foreach ( $cases as $label => $candidate ) {
            $this->assertFalse( $this->validate( $candidate, $this->elements() ), $label );
        }
    }

    public function test_plain_text_schema_cannot_be_claimed_as_a_google_address() {
        $elements = $this->elements();
        $elements[0]['data']['enable_address_auto_complete'] = 'false';
        $data = $this->production_data( array() );
        unset( $data['formatted_note'] );
        $this->assertFalse( $this->validate( $data, array( $elements[0] ) ) );
    }

    public function test_exact_browser_identity_carriers_match_authoritative_submission_ids() {
        $data = $this->production_data( array() );
        $data['hidden_contact_entry_id']['value'] = '73';

        $this->assertTrue( $this->validate( $data, $this->elements(), 41, 73 ) );

        $forgeries = array();
        $candidate = $data;
        $candidate['hidden_form_id']['value'] = '42';
        $forgeries['form id from another form'] = $candidate;
        $candidate = $data;
        $candidate['hidden_contact_entry_id']['value'] = '074';
        $forgeries['non-canonical entry id'] = $candidate;
        $candidate = $data;
        $candidate['hidden_form_id']['name'] = 'form_id';
        $forgeries['form carrier name'] = $candidate;
        $candidate = $data;
        $candidate['hidden_contact_entry_id']['type'] = 'var';
        $forgeries['entry carrier type'] = $candidate;
        $candidate = $data;
        $candidate['hidden_form_id']['extra'] = 'forged';
        $forgeries['form carrier extra member'] = $candidate;
        $candidate = $data;
        $candidate['hidden_form_id']['value'] = '41junk';
        $forgeries['malformed form id'] = $candidate;
        $candidate = $data;
        unset( $candidate['hidden_contact_entry_id'] );
        $forgeries['missing entry carrier'] = $candidate;
        $candidate = $data;
        $candidate['hidden_contact_entry_id']['value'] = array( '73' );
        $forgeries['entry carrier non-scalar value'] = $candidate;

        foreach ( $forgeries as $label => $candidate ) {
            $this->assertFalse( $this->validate( $candidate, $this->elements(), 41, 73 ), $label );
        }
    }

    public function test_hidden_list_id_matches_the_present_post_list_identity() {
        $data = $this->production_data( array() );
        $data['hidden_list_id'] = array(
            'name'  => 'hidden_list_id',
            'value' => '0',
            'type'  => 'var',
        );
        $this->assertTrue( $this->validate( $data, $this->elements(), 41, '', 0 ) );

        $forgeries = array();
        $candidate = $data;
        $candidate['hidden_list_id']['value'] = '1';
        $forgeries['mismatched list identity'] = $candidate;
        $candidate = $data;
        $candidate['hidden_list_id']['value'] = '00';
        $forgeries['non-canonical list identity'] = $candidate;
        $candidate = $data;
        unset( $candidate['hidden_list_id']['value'] );
        $forgeries['missing list value'] = $candidate;
        $candidate = $data;
        unset( $candidate['hidden_list_id']['type'] );
        $forgeries['missing list type'] = $candidate;
        $candidate = $data;
        $candidate['hidden_list_id']['type'] = 'list_id';
        $forgeries['list carrier type'] = $candidate;
        $candidate = $data;
        $candidate['hidden_list_id'] = '0';
        $forgeries['non-array list carrier'] = $candidate;

        foreach ( $forgeries as $label => $candidate ) {
            $this->assertFalse( $this->validate( $candidate, $this->elements(), 41, '', 0 ), $label );
        }
        $this->assertFalse( $this->validate( $data, $this->elements() ), 'missing POST list identity' );
        $data['unknown_field'] = array( 'name' => 'unknown_field', 'value' => 'x', 'type' => 'var' );
        $this->assertFalse( $this->validate( $data, $this->elements(), 41, '', 0 ), 'unknown non-reserved field' );
    }

    public function test_production_repeater_carriers_match_the_stored_repeater_structure() {
        $this->assertTrue( $this->validate( $this->production_repeater_data(), $this->repeater_elements() ) );
    }

    public function test_unknown_or_malformed_repeater_carriers_fail_the_stored_contract() {
        $valid = $this->production_repeater_data();
        $forgeries = array();

        $candidate = $valid;
        $candidate['_super_dynamic_data']['forged_group'] = $candidate['_super_dynamic_data']['guest_name'];
        $forgeries['unknown repeater group'] = $candidate;
        $candidate = $valid;
        $candidate['_super_dynamic_data']['guest_name'][0]['forged_field'] = array(
            'name' => 'forged_field',
            'value' => 'forged',
            'type' => 'var',
        );
        $forgeries['unknown nested field'] = $candidate;
        $candidate = $valid;
        $candidate['_super_dynamic_data']['guest_name'][0]['guest_name']['value'] = array( 'Ada' );
        $forgeries['nested scalar array'] = $candidate;
        $candidate = $valid;
        $candidate['_super_dynamic_data']['guest_name'][0]['guest_document']['files'] = 'forged';
        $forgeries['nested file shape'] = $candidate;
        $candidate = $valid;
        $candidate['_super_dynamic_data']['guest_name'][0]['guest_name']['type'] = 'files';
        $forgeries['nested carrier type'] = $candidate;
        $candidate = $valid;
        $candidate['_super_dynamic_data']['guest_name']['row'] = $candidate['_super_dynamic_data']['guest_name'][0];
        $forgeries['non-indexed rows'] = $candidate;

        foreach ( $forgeries as $label => $candidate ) {
            $this->assertFalse( $this->validate( $candidate, $this->repeater_elements() ), $label );
        }
    }
}
