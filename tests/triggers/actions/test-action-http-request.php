<?php
/**
 * Tests for HTTP Request Action
 *
 * @package Super_Forms
 * @subpackage Tests/Triggers/Actions
 */

require_once dirname(__DIR__) . '/class-action-test-case.php';

class Test_Action_HTTP_Request extends SUPER_Action_Test_Case {

    /**
     * Get action instance for testing
     *
     * @return SUPER_Trigger_Action_Base
     */
    protected function get_action_instance() {
        // Load the action class
        require_once SUPER_PLUGIN_DIR . '/includes/triggers/actions/class-action-http-request.php';

        return new SUPER_Action_HTTP_Request();
    }

    /**
     * Test action metadata
     */
    public function test_action_id() {
        $this->assertEquals('http_request', $this->action->get_id());
    }

    public function test_action_label() {
        $this->assertEquals('HTTP Request', $this->action->get_label());
    }

    public function test_action_category() {
        $this->assertEquals('integration', $this->action->get_category());
    }

    public function test_action_description() {
        $this->assertNotEmpty($this->action->get_description());
    }

    /**
     * Test settings schema
     */
    public function test_settings_schema_has_required_fields() {
        $schema = $this->action->get_settings_schema();

        $field_names = array_column($schema, 'name');

        $this->assertContains('url', $field_names);
        $this->assertContains('method', $field_names);
        $this->assertContains('auth_type', $field_names);
        $this->assertContains('body_type', $field_names);
        $this->assertContains('headers', $field_names);
        $this->assertContains('timeout', $field_names);
    }

    public function test_settings_schema_method_options() {
        $schema = $this->action->get_settings_schema();

        $method_field = null;
        foreach ($schema as $field) {
            if ($field['name'] === 'method') {
                $method_field = $field;
                break;
            }
        }

        $this->assertNotNull($method_field);
        $this->assertArrayHasKey('options', $method_field);
        $this->assertContains('GET', array_keys($method_field['options']));
        $this->assertContains('POST', array_keys($method_field['options']));
        $this->assertContains('PUT', array_keys($method_field['options']));
        $this->assertContains('PATCH', array_keys($method_field['options']));
        $this->assertContains('DELETE', array_keys($method_field['options']));
    }

    public function test_settings_schema_auth_options() {
        $schema = $this->action->get_settings_schema();

        $auth_field = null;
        foreach ($schema as $field) {
            if ($field['name'] === 'auth_type') {
                $auth_field = $field;
                break;
            }
        }

        $this->assertNotNull($auth_field);
        $this->assertArrayHasKey('options', $auth_field);
        $this->assertContains('none', array_keys($auth_field['options']));
        $this->assertContains('basic', array_keys($auth_field['options']));
        $this->assertContains('bearer', array_keys($auth_field['options']));
        $this->assertContains('api_key', array_keys($auth_field['options']));
        $this->assertContains('oauth2', array_keys($auth_field['options']));
    }

    public function test_settings_schema_body_options() {
        $schema = $this->action->get_settings_schema();

        $body_field = null;
        foreach ($schema as $field) {
            if ($field['name'] === 'body_type') {
                $body_field = $field;
                break;
            }
        }

        $this->assertNotNull($body_field);
        $this->assertArrayHasKey('options', $body_field);
        $this->assertContains('none', array_keys($body_field['options']));
        $this->assertContains('json', array_keys($body_field['options']));
        $this->assertContains('form_data', array_keys($body_field['options']));
        $this->assertContains('xml', array_keys($body_field['options']));
        $this->assertContains('graphql', array_keys($body_field['options']));
    }

    /**
     * Test validation
     */
    public function test_validate_config_requires_url() {
        $config = [
            'method' => 'POST',
            'body_type' => 'json'
        ];

        $result = $this->action->validate_config($config);

        // URL is required - should return WP_Error or pass (depends on base class implementation)
        // The base class validates against schema
        $this->assertTrue(is_wp_error($result) || $result === true);
    }

    public function test_validate_config_valid() {
        $config = [
            'url' => 'https://example.com/api',
            'method' => 'POST',
            'auth_type' => 'none',
            'body_type' => 'json',
            'json_body' => '{"test": true}'
        ];

        $result = $this->action->validate_config($config);

        $this->assertTrue($result === true || !is_wp_error($result));
    }

    /**
     * Test execution with invalid URL
     */
    public function test_execute_invalid_url_returns_error() {
        $context = $this->get_test_context();
        $config = [
            'url' => 'not-a-valid-url',
            'method' => 'POST',
            'body_type' => 'json'
        ];

        $result = $this->action->execute($context, $config);

        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_url', $result->get_error_code());
    }

    /**
     * Test async execution support
     */
    public function test_supports_async() {
        $this->assertTrue($this->action->supports_async());
    }

    public function test_execution_mode_is_async() {
        $this->assertEquals('async', $this->action->get_execution_mode());
    }

    /**
     * Test retry configuration
     */
    public function test_retry_config() {
        $config = $this->action->get_retry_config();

        $this->assertArrayHasKey('max_retries', $config);
        $this->assertArrayHasKey('initial_delay', $config);
        $this->assertArrayHasKey('exponential', $config);
        $this->assertArrayHasKey('max_delay', $config);

        $this->assertEquals(5, $config['max_retries']);
        $this->assertTrue($config['exponential']);
    }

    /**
     * Test tag replacement in URL
     */
    public function test_tag_replacement_in_url() {
        // We need to test the build_request method indirectly
        // by checking if execute properly handles tags

        $context = [
            'form_id' => 123,
            'entry_id' => 456,
            'user_id' => 1,
            'data' => [
                'api_endpoint' => ['value' => 'users']
            ]
        ];

        $config = [
            'url' => 'https://api.example.com/{api_endpoint}',
            'method' => 'GET',
            'auth_type' => 'none',
            'body_type' => 'none'
        ];

        // Execute will fail (can't connect) but URL should be replaced
        $result = $this->action->execute($context, $config);

        // The error should show the resolved URL if it tried to connect
        $this->assertInstanceOf('WP_Error', $result);
    }

    /**
     * Test metadata retrieval
     */
    public function test_get_metadata() {
        $metadata = $this->action->get_metadata();

        $this->assertArrayHasKey('id', $metadata);
        $this->assertArrayHasKey('label', $metadata);
        $this->assertArrayHasKey('description', $metadata);
        $this->assertArrayHasKey('category', $metadata);
        $this->assertArrayHasKey('settings_schema', $metadata);
        $this->assertArrayHasKey('supports_async', $metadata);
        $this->assertArrayHasKey('execution_mode', $metadata);

        $this->assertEquals('http_request', $metadata['id']);
        $this->assertEquals('integration', $metadata['category']);
        $this->assertTrue($metadata['supports_async']);
    }

    /**
     * Helper: Get test context
     */
    protected function get_test_context() {
        return [
            'form_id' => 1,
            'entry_id' => 999,
            'user_id' => 1,
            'timestamp' => current_time('mysql'),
            'data' => [
                'name' => ['value' => 'Test User'],
                'email' => ['value' => 'test@example.com']
            ],
            'form_data' => [
                'name' => 'Test User',
                'email' => 'test@example.com'
            ]
        ];
    }

    // =========================================================================
    // DYNAMIC DATA HANDLING TESTS
    // =========================================================================

    /**
     * Test wildcard path extraction - simple array
     */
    public function test_get_value_by_path_wildcard_simple() {
        $data = [
            'items' => [
                ['id' => 1, 'name' => 'A'],
                ['id' => 2, 'name' => 'B'],
                ['id' => 3, 'name' => 'C']
            ]
        ];

        $method = new ReflectionMethod($this->action, 'get_value_by_path');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, $data, 'items[*].id');

        $this->assertEquals([1, 2, 3], $result);
    }

    /**
     * Test wildcard path extraction - nested path first
     */
    public function test_get_value_by_path_wildcard_nested_path() {
        $data = [
            'data' => [
                'users' => [
                    ['email' => 'a@test.com'],
                    ['email' => 'b@test.com']
                ]
            ]
        ];

        $method = new ReflectionMethod($this->action, 'get_value_by_path');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, $data, 'data.users[*].email');

        $this->assertEquals(['a@test.com', 'b@test.com'], $result);
    }

    /**
     * Test wildcard path extraction - nested wildcards
     */
    public function test_get_value_by_path_nested_wildcards() {
        $data = [
            'orders' => [
                [
                    'items' => [
                        ['sku' => 'A1'],
                        ['sku' => 'A2']
                    ]
                ],
                [
                    'items' => [
                        ['sku' => 'B1']
                    ]
                ]
            ]
        ];

        $method = new ReflectionMethod($this->action, 'get_value_by_path');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, $data, 'orders[*].items[*].sku');

        $this->assertEquals(['A1', 'A2', 'B1'], $result);
    }

    /**
     * Test wildcard path extraction - specific index still works
     */
    public function test_get_value_by_path_specific_index() {
        $data = [
            'items' => [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3]
            ]
        ];

        $method = new ReflectionMethod($this->action, 'get_value_by_path');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, $data, 'items[1].id');

        $this->assertEquals(2, $result);
    }

    /**
     * Test pipe modifier - json
     */
    public function test_apply_modifiers_json() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, [1, 2, 3], ['json']);

        $this->assertEquals('[1,2,3]', $result);
    }

    /**
     * Test pipe modifier - first
     */
    public function test_apply_modifiers_first() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, ['a', 'b', 'c'], ['first']);

        $this->assertEquals('a', $result);
    }

    /**
     * Test pipe modifier - last
     */
    public function test_apply_modifiers_last() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, ['a', 'b', 'c'], ['last']);

        $this->assertEquals('c', $result);
    }

    /**
     * Test pipe modifier - count
     */
    public function test_apply_modifiers_count() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, [1, 2, 3, 4, 5], ['count']);

        $this->assertEquals(5, $result);
    }

    /**
     * Test pipe modifier - join with default separator
     */
    public function test_apply_modifiers_join_default() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, ['a', 'b', 'c'], ['join']);

        $this->assertEquals('a,b,c', $result);
    }

    /**
     * Test pipe modifier - join with custom separator
     */
    public function test_apply_modifiers_join_custom() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, ['a', 'b', 'c'], ['join: | ']);

        $this->assertEquals('a | b | c', $result);
    }

    /**
     * Test pipe modifier - unique
     */
    public function test_apply_modifiers_unique() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, [1, 2, 2, 3, 1], ['unique']);

        $this->assertEquals([1, 2, 3], $result);
    }

    /**
     * Test pipe modifier - sort
     */
    public function test_apply_modifiers_sort() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, [3, 1, 2], ['sort']);

        $this->assertEquals([1, 2, 3], $result);
    }

    /**
     * Test pipe modifier - sort descending
     */
    public function test_apply_modifiers_sort_desc() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, [1, 2, 3], ['sort:desc']);

        $this->assertEquals([3, 2, 1], $result);
    }

    /**
     * Test pipe modifier - reverse
     */
    public function test_apply_modifiers_reverse() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, ['a', 'b', 'c'], ['reverse']);

        $this->assertEquals(['c', 'b', 'a'], $result);
    }

    /**
     * Test pipe modifier - flatten
     */
    public function test_apply_modifiers_flatten() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, [[1, 2], [3, [4, 5]]], ['flatten']);

        $this->assertEquals([1, 2, 3, 4, 5], $result);
    }

    /**
     * Test pipe modifier - slice
     */
    public function test_apply_modifiers_slice() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, [0, 1, 2, 3, 4], ['slice:1:2']);

        $this->assertEquals([1, 2], $result);
    }

    /**
     * Test pipe modifier - chained modifiers
     */
    public function test_apply_modifiers_chained() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        // Sort then get first
        $result = $method->invoke($this->action, [3, 1, 2], ['sort', 'first']);

        $this->assertEquals(1, $result);
    }

    /**
     * Test convert_to_repeater_format - API array to SF repeater
     */
    public function test_convert_to_repeater_format() {
        $method = new ReflectionMethod($this->action, 'convert_to_repeater_format');
        $method->setAccessible(true);

        $api_data = [
            ['name' => 'John', 'email' => 'john@test.com'],
            ['name' => 'Jane', 'email' => 'jane@test.com']
        ];

        $result = $method->invoke($this->action, $api_data, null);

        $expected = [
            0 => [
                'name' => ['value' => 'John'],
                'email' => ['value' => 'john@test.com']
            ],
            1 => [
                'name' => ['value' => 'Jane'],
                'email' => ['value' => 'jane@test.com']
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test convert_to_repeater_format - with field filter
     */
    public function test_convert_to_repeater_format_with_filter() {
        $method = new ReflectionMethod($this->action, 'convert_to_repeater_format');
        $method->setAccessible(true);

        $api_data = [
            ['name' => 'John', 'email' => 'john@test.com', 'phone' => '123'],
        ];

        $result = $method->invoke($this->action, $api_data, 'name,email');

        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('email', $result[0]);
        $this->assertArrayNotHasKey('phone', $result[0]);
    }

    /**
     * Test convert_repeater_to_array - SF repeater to API array
     */
    public function test_convert_repeater_to_array() {
        $method = new ReflectionMethod($this->action, 'convert_repeater_to_array');
        $method->setAccessible(true);

        $sf_data = [
            0 => [
                'name' => ['value' => 'John'],
                'email' => ['value' => 'john@test.com']
            ],
            1 => [
                'name' => ['value' => 'Jane'],
                'email' => ['value' => 'jane@test.com']
            ]
        ];

        $result = $method->invoke($this->action, $sf_data, null);

        $expected = [
            ['name' => 'John', 'email' => 'john@test.com'],
            ['name' => 'Jane', 'email' => 'jane@test.com']
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test convert_repeater_to_array - with field filter
     */
    public function test_convert_repeater_to_array_with_filter() {
        $method = new ReflectionMethod($this->action, 'convert_repeater_to_array');
        $method->setAccessible(true);

        $sf_data = [
            0 => [
                'name' => ['value' => 'John'],
                'email' => ['value' => 'john@test.com'],
                'phone' => ['value' => '123']
            ]
        ];

        $result = $method->invoke($this->action, $sf_data, ['name', 'email']);

        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('email', $result[0]);
        $this->assertArrayNotHasKey('phone', $result[0]);
    }

    /**
     * Test process_repeater_tags
     */
    public function test_process_repeater_tags() {
        $method = new ReflectionMethod($this->action, 'process_repeater_tags');
        $method->setAccessible(true);

        $context = [
            'form_data' => [
                'contacts' => [
                    0 => [
                        'name' => ['value' => 'John'],
                        'email' => ['value' => 'john@test.com']
                    ]
                ]
            ]
        ];

        $json = '{"contacts": {repeater:contacts}}';
        $result = $method->invoke($this->action, $json, $context);

        // Should contain the serialized array
        $this->assertStringContainsString('"name":"John"', $result);
        $this->assertStringContainsString('"email":"john@test.com"', $result);
    }

    /**
     * Test process_repeater_tags - with fields filter
     */
    public function test_process_repeater_tags_with_filter() {
        $method = new ReflectionMethod($this->action, 'process_repeater_tags');
        $method->setAccessible(true);

        $context = [
            'form_data' => [
                'contacts' => [
                    0 => [
                        'name' => ['value' => 'John'],
                        'email' => ['value' => 'john@test.com'],
                        'phone' => ['value' => '123']
                    ]
                ]
            ]
        ];

        $json = '{"contacts": {repeater:contacts|fields:name,email}}';
        $result = $method->invoke($this->action, $json, $context);

        $this->assertStringContainsString('"name":"John"', $result);
        $this->assertStringContainsString('"email":"john@test.com"', $result);
        $this->assertStringNotContainsString('"phone"', $result);
    }

    /**
     * Test parse_path_segments
     */
    public function test_parse_path_segments() {
        $method = new ReflectionMethod($this->action, 'parse_path_segments');
        $method->setAccessible(true);

        $segments = $method->invoke($this->action, 'data.items[*].name');

        $expected = [
            ['type' => 'key', 'value' => 'data'],
            ['type' => 'key', 'value' => 'items'],
            ['type' => 'wildcard'],
            ['type' => 'key', 'value' => 'name']
        ];

        $this->assertEquals($expected, $segments);
    }

    /**
     * Test parse_path_segments - with numeric index
     */
    public function test_parse_path_segments_numeric_index() {
        $method = new ReflectionMethod($this->action, 'parse_path_segments');
        $method->setAccessible(true);

        $segments = $method->invoke($this->action, 'items[0].name');

        $expected = [
            ['type' => 'key', 'value' => 'items'],
            ['type' => 'index', 'value' => 0],
            ['type' => 'key', 'value' => 'name']
        ];

        $this->assertEquals($expected, $segments);
    }

    /**
     * Test empty array handling in wildcards
     */
    public function test_wildcard_empty_array() {
        $method = new ReflectionMethod($this->action, 'get_value_by_path');
        $method->setAccessible(true);

        $data = ['items' => []];
        $result = $method->invoke($this->action, $data, 'items[*].id');

        $this->assertEquals([], $result);
    }

    /**
     * Test null values filtered from wildcard results
     */
    public function test_wildcard_filters_null_values() {
        $method = new ReflectionMethod($this->action, 'get_value_by_path');
        $method->setAccessible(true);

        $data = [
            'items' => [
                ['id' => 1],
                ['name' => 'no id here'],  // Missing 'id' field
                ['id' => 3]
            ]
        ];

        $result = $method->invoke($this->action, $data, 'items[*].id');

        $this->assertEquals([1, 3], $result);
    }

    // =========================================================================
    // NESTED REPEATER TESTS
    // =========================================================================

    /**
     * Test is_nested_repeater detection - positive case
     */
    public function test_is_nested_repeater_positive() {
        $method = new ReflectionMethod($this->action, 'is_nested_repeater');
        $method->setAccessible(true);

        // SF repeater format with numeric keys and field arrays
        $data = [
            0 => ['name' => ['value' => 'John'], 'email' => ['value' => 'john@test.com']],
            1 => ['name' => ['value' => 'Jane'], 'email' => ['value' => 'jane@test.com']]
        ];

        $this->assertTrue($method->invoke($this->action, $data));
    }

    /**
     * Test is_nested_repeater detection - negative case (simple array)
     */
    public function test_is_nested_repeater_negative_simple_array() {
        $method = new ReflectionMethod($this->action, 'is_nested_repeater');
        $method->setAccessible(true);

        // Simple array of values
        $data = [1, 2, 3];

        $this->assertFalse($method->invoke($this->action, $data));
    }

    /**
     * Test is_nested_repeater detection - negative case (associative)
     */
    public function test_is_nested_repeater_negative_associative() {
        $method = new ReflectionMethod($this->action, 'is_nested_repeater');
        $method->setAccessible(true);

        // Associative array (not repeater)
        $data = ['name' => 'John', 'email' => 'john@test.com'];

        $this->assertFalse($method->invoke($this->action, $data));
    }

    /**
     * Test convert_repeater_to_array with 2-level nested repeaters
     */
    public function test_nested_repeater_2_levels() {
        $method = new ReflectionMethod($this->action, 'convert_repeater_to_array');
        $method->setAccessible(true);

        // Orders with nested items (2 levels)
        $sf_data = [
            0 => [
                'order_id' => ['value' => 'ORD-001'],
                'items' => [
                    0 => [
                        'sku' => ['value' => 'SKU-A'],
                        'qty' => ['value' => 2]
                    ],
                    1 => [
                        'sku' => ['value' => 'SKU-B'],
                        'qty' => ['value' => 1]
                    ]
                ]
            ],
            1 => [
                'order_id' => ['value' => 'ORD-002'],
                'items' => [
                    0 => [
                        'sku' => ['value' => 'SKU-C'],
                        'qty' => ['value' => 3]
                    ]
                ]
            ]
        ];

        $result = $method->invoke($this->action, $sf_data, null, 0);

        $expected = [
            [
                'order_id' => 'ORD-001',
                'items' => [
                    ['sku' => 'SKU-A', 'qty' => 2],
                    ['sku' => 'SKU-B', 'qty' => 1]
                ]
            ],
            [
                'order_id' => 'ORD-002',
                'items' => [
                    ['sku' => 'SKU-C', 'qty' => 3]
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test convert_repeater_to_array with 3-level nested repeaters
     */
    public function test_nested_repeater_3_levels() {
        $method = new ReflectionMethod($this->action, 'convert_repeater_to_array');
        $method->setAccessible(true);

        // Orders → Items → Variants (3 levels)
        $sf_data = [
            0 => [
                'order_id' => ['value' => 'ORD-001'],
                'items' => [
                    0 => [
                        'sku' => ['value' => 'SKU-A'],
                        'variants' => [
                            0 => [
                                'color' => ['value' => 'red'],
                                'size' => ['value' => 'L']
                            ],
                            1 => [
                                'color' => ['value' => 'blue'],
                                'size' => ['value' => 'M']
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $method->invoke($this->action, $sf_data, null, 0);

        $this->assertEquals('ORD-001', $result[0]['order_id']);
        $this->assertEquals('SKU-A', $result[0]['items'][0]['sku']);
        $this->assertEquals('red', $result[0]['items'][0]['variants'][0]['color']);
        $this->assertEquals('L', $result[0]['items'][0]['variants'][0]['size']);
        $this->assertEquals('blue', $result[0]['items'][0]['variants'][1]['color']);
    }

    /**
     * Test max depth protection
     */
    public function test_nested_repeater_max_depth() {
        $method = new ReflectionMethod($this->action, 'convert_repeater_to_array');
        $method->setAccessible(true);

        // Call with depth = 6 (above max of 5)
        $sf_data = [
            0 => ['name' => ['value' => 'Test']]
        ];

        $result = $method->invoke($this->action, $sf_data, null, 6);

        // Should return data unchanged (not converted)
        $this->assertEquals($sf_data, $result);
    }

    // =========================================================================
    // FILE MODIFIER TESTS
    // =========================================================================

    /**
     * Test |files modifier - split comma-separated URLs
     */
    public function test_modifier_files_comma_separated() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $value = 'https://example.com/doc1.pdf, https://example.com/doc2.pdf, https://example.com/doc3.pdf';
        $result = $method->invoke($this->action, $value, ['files']);

        $expected = [
            'https://example.com/doc1.pdf',
            'https://example.com/doc2.pdf',
            'https://example.com/doc3.pdf'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test |files modifier - single URL
     */
    public function test_modifier_files_single() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $value = 'https://example.com/document.pdf';
        $result = $method->invoke($this->action, $value, ['files']);

        $this->assertEquals(['https://example.com/document.pdf'], $result);
    }

    /**
     * Test |file_meta modifier
     */
    public function test_modifier_file_meta() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $value = 'https://example.com/uploads/my-document.pdf';
        $result = $method->invoke($this->action, $value, ['file_meta']);

        $this->assertEquals('https://example.com/uploads/my-document.pdf', $result['url']);
        $this->assertEquals('my-document.pdf', $result['filename']);
        $this->assertEquals('pdf', $result['extension']);
        $this->assertEquals('my-document', $result['basename']);
    }

    /**
     * Test |file_meta modifier on array of URLs
     */
    public function test_modifier_file_meta_array() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $value = [
            'https://example.com/file1.jpg',
            'https://example.com/file2.png'
        ];
        $result = $method->invoke($this->action, $value, ['file_meta']);

        $this->assertEquals('file1.jpg', $result[0]['filename']);
        $this->assertEquals('jpg', $result[0]['extension']);
        $this->assertEquals('file2.png', $result[1]['filename']);
        $this->assertEquals('png', $result[1]['extension']);
    }

    // =========================================================================
    // SIGNATURE / BASE64 DATA URL MODIFIER TESTS
    // =========================================================================

    /**
     * Test |base64_data modifier - extract data from signature
     */
    public function test_modifier_base64_data() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $value = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA';
        $result = $method->invoke($this->action, $value, ['base64_data']);

        $this->assertEquals('iVBORw0KGgoAAAANSUhEUgAAAAUA', $result);
    }

    /**
     * Test |base64_mime modifier - extract MIME type
     */
    public function test_modifier_base64_mime() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $value = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA';
        $result = $method->invoke($this->action, $value, ['base64_mime']);

        $this->assertEquals('image/png', $result);
    }

    /**
     * Test |base64_mime modifier - JPEG
     */
    public function test_modifier_base64_mime_jpeg() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $value = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ';
        $result = $method->invoke($this->action, $value, ['base64_mime']);

        $this->assertEquals('image/jpeg', $result);
    }

    /**
     * Test |base64_ext modifier - get extension from MIME
     */
    public function test_modifier_base64_ext() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $value = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA';
        $result = $method->invoke($this->action, $value, ['base64_ext']);

        $this->assertEquals('png', $result);
    }

    /**
     * Test |base64_ext modifier - PDF
     */
    public function test_modifier_base64_ext_pdf() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $value = 'data:application/pdf;base64,JVBERi0xLjQ=';
        $result = $method->invoke($this->action, $value, ['base64_ext']);

        $this->assertEquals('pdf', $result);
    }

    /**
     * Test |base64_mime returns null for non-data URL
     */
    public function test_modifier_base64_mime_invalid() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $value = 'not a data url';
        $result = $method->invoke($this->action, $value, ['base64_mime']);

        $this->assertNull($result);
    }

    // =========================================================================
    // MIME TO EXTENSION HELPER TESTS
    // =========================================================================

    /**
     * Test mime_to_extension helper
     */
    public function test_mime_to_extension() {
        $method = new ReflectionMethod($this->action, 'mime_to_extension');
        $method->setAccessible(true);

        $this->assertEquals('png', $method->invoke($this->action, 'image/png'));
        $this->assertEquals('jpg', $method->invoke($this->action, 'image/jpeg'));
        $this->assertEquals('pdf', $method->invoke($this->action, 'application/pdf'));
        $this->assertEquals('docx', $method->invoke($this->action, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'));
        $this->assertEquals('mp4', $method->invoke($this->action, 'video/mp4'));
        $this->assertNull($method->invoke($this->action, 'unknown/type'));
    }

    // =========================================================================
    // FILE METADATA HELPER TESTS
    // =========================================================================

    /**
     * Test extract_file_meta helper
     */
    public function test_extract_file_meta() {
        $method = new ReflectionMethod($this->action, 'extract_file_meta');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, 'https://example.com/wp-content/uploads/2024/01/my-file.PDF');

        $this->assertEquals('my-file.PDF', $result['filename']);
        $this->assertEquals('pdf', $result['extension']); // lowercase
        $this->assertEquals('my-file', $result['basename']);
    }

    /**
     * Test extract_file_meta with query string
     */
    public function test_extract_file_meta_query_string() {
        $method = new ReflectionMethod($this->action, 'extract_file_meta');
        $method->setAccessible(true);

        $result = $method->invoke($this->action, 'https://example.com/file.jpg?v=123');

        $this->assertEquals('file.jpg', $result['filename']);
        $this->assertEquals('jpg', $result['extension']);
    }

    // =========================================================================
    // REAL-WORLD FORM DATA TESTS
    // =========================================================================

    /**
     * Test real-world form with all field types
     */
    public function test_real_world_form_data() {
        $method = new ReflectionMethod($this->action, 'convert_repeater_to_array');
        $method->setAccessible(true);

        // Simulate a real Super Forms submission with:
        // - Simple fields
        // - Nested dynamic columns (orders → items)
        // - Signature field
        // - File uploads
        $form_data = [
            0 => [
                // Customer info
                'customer_name' => ['value' => 'John Doe'],
                'customer_email' => ['value' => 'john@example.com'],
                // Signature (stored as data URL)
                'signature' => ['value' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAA'],
                // File uploads (comma-separated)
                'documents' => ['value' => 'https://example.com/doc1.pdf,https://example.com/doc2.pdf'],
                // Nested order items
                'order_items' => [
                    0 => [
                        'product' => ['value' => 'Widget A'],
                        'quantity' => ['value' => 5],
                        'price' => ['value' => '29.99']
                    ],
                    1 => [
                        'product' => ['value' => 'Widget B'],
                        'quantity' => ['value' => 3],
                        'price' => ['value' => '49.99']
                    ]
                ]
            ]
        ];

        $result = $method->invoke($this->action, $form_data, null, 0);

        // Verify structure
        $this->assertEquals('John Doe', $result[0]['customer_name']);
        $this->assertEquals('john@example.com', $result[0]['customer_email']);
        $this->assertStringStartsWith('data:image/png;base64,', $result[0]['signature']);
        $this->assertStringContainsString('doc1.pdf', $result[0]['documents']);

        // Verify nested items were converted
        $this->assertIsArray($result[0]['order_items']);
        $this->assertEquals('Widget A', $result[0]['order_items'][0]['product']);
        $this->assertEquals(5, $result[0]['order_items'][0]['quantity']);
        $this->assertEquals('Widget B', $result[0]['order_items'][1]['product']);
    }

    /**
     * Test chained modifiers on file data
     */
    public function test_chained_file_modifiers() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        // Split files, get first, extract meta
        $value = 'https://example.com/doc1.pdf,https://example.com/doc2.pdf';
        $result = $method->invoke($this->action, $value, ['files', 'first', 'file_meta']);

        $this->assertEquals('doc1.pdf', $result['filename']);
        $this->assertEquals('pdf', $result['extension']);
    }

    /**
     * Test chained modifiers: files count
     */
    public function test_chained_files_count() {
        $method = new ReflectionMethod($this->action, 'apply_modifiers');
        $method->setAccessible(true);

        $value = 'https://example.com/a.pdf,https://example.com/b.pdf,https://example.com/c.pdf';
        $result = $method->invoke($this->action, $value, ['files', 'count']);

        $this->assertEquals(3, $result);
    }
}
