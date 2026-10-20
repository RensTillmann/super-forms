<?php
/**
 * Button and Divider elements support Conditional Logic.
 *
 * The divider render already called opening_tag()/loop_conditions(), only the builder registration lacked
 * the Conditional Logic settings group. The button had neither: its registration lacks the group and
 * button() never printed the conditional class/dataset or the conditions textarea. A button's `name` is its
 * label, so the conditions must be printed without it (otherwise field_error_msg() prints error markup inside
 * the button and a filter named after the label fires). Buttons without conditions must render exactly as before.
 *
 * @package Super_Forms\Tests
 */

class Test_Security_Button_Divider_Conditional_Logic extends WP_UnitTestCase {

	private $filter_calls = 0;

	public function tearDown(): void {
		unset( $GLOBALS['super_custom_button_used'], $GLOBALS['super_submit_button_found'] );
		parent::tearDown();
	}

	private function settings() {
		return array(
			'form_button'                 => 'Submit',
			'form_button_loading'         => 'Loading...',
			'form_button_radius'          => 'rounded',
			'form_button_type'            => 'flat',
			'form_button_size'            => 'medium',
			'form_button_align'           => 'left',
			'form_button_width'           => 'auto',
			'form_button_icon_option'     => 'none',
			'form_button_icon_visibility' => 'visible',
			'form_button_icon_animation'  => 'horizontal',
			'form_button_icon'            => '',
			'theme_button_color'          => '#f26c68',
			'theme_button_color_hover'    => '#444444',
			'theme_button_font'           => '#ffffff',
			'theme_button_font_hover'     => '#ffffff',
			'theme_hide_icons'            => 'no',
		);
	}

	private function render( $tag, $group, $data ) {
		unset( $GLOBALS['super_custom_button_used'], $GLOBALS['super_submit_button_found'] );
		return SUPER_Shortcodes::output_element_html(
			array(
				'tag'        => $tag,
				'group'      => $group,
				'data'       => $data,
				'shortcodes' => SUPER_Shortcodes::shortcodes(),
				'settings'   => $this->settings(),
			)
		);
	}

	private function conditions() {
		return array(
			array(
				'field'      => 'email',
				'logic'      => 'equal',
				'value'      => 'yes',
				'and_method' => '',
				'field_and'  => '',
				'logic_and'  => '',
				'value_and'  => '',
			),
		);
	}

	public function count_filter( $items ) {
		$this->filter_calls++;
		return $items;
	}

	public function test_registration_contains_conditional_logic_group_for_button_and_divider() {
		$shortcodes = SUPER_Shortcodes::shortcodes();
		$expected   = $shortcodes['form_elements']['shortcodes']['text']['atts']['conditional_logic'];
		$this->assertArrayHasKey( 'conditional_logic', $shortcodes['form_elements']['shortcodes']['button']['atts'] );
		$this->assertSame( $expected, $shortcodes['form_elements']['shortcodes']['button']['atts']['conditional_logic'] );
		$this->assertArrayHasKey( 'conditional_logic', $shortcodes['html_elements']['shortcodes']['divider']['atts'] );
		$this->assertSame( $expected, $shortcodes['html_elements']['shortcodes']['divider']['atts']['conditional_logic'] );
	}

	public function test_button_with_show_condition_renders_conditional_logic_markup() {
		add_filter( 'super_conditional_items_Send now_filter', array( $this, 'count_filter' ) );
		$html = $this->render(
			'button',
			'form_elements',
			array(
				'name'                => 'Send now',
				'action'              => 'submit',
				'conditional_action'  => 'show',
				'conditional_trigger' => 'all',
				'conditional_items'   => $this->conditions(),
			)
		);
		remove_filter( 'super_conditional_items_Send now_filter', array( $this, 'count_filter' ) );

		$this->assertMatchesRegularExpression( '/^<div [^>]*class="super-extra-shortcode super-shortcode super-field super-button [^"]*super-conditional-hidden[^"]*"[^>]* data-conditional-action="show" data-conditional-trigger="all"/', $html );
		$this->assertStringContainsString( '<textarea class="super-conditional-logic" data-fields="{email}">' . json_encode( $this->conditions() ) . '</textarea></div>', $html );
		$this->assertStringEndsWith( '</textarea></div>', $html );
		$this->assertStringNotContainsString( 'super-error-msg', $html );
		$this->assertSame( 0, $this->filter_calls, 'no conditional items filter named after the button label' );
		$this->assertStringContainsString( 'data-normal="Send now"', $html );
	}

	public function test_button_hide_and_readonly_actions_render_visible_class() {
		foreach ( array( 'hide', 'readonly' ) as $action ) {
			$html = $this->render(
				'button',
				'form_elements',
				array(
					'name'                => 'Next step',
					'action'              => 'next',
					'conditional_action'  => $action,
					'conditional_trigger' => 'one',
					'conditional_items'   => $this->conditions(),
				)
			);
			$this->assertMatchesRegularExpression( '/class="[^"]*super-next-multipart[^"]*super-conditional-visible"[^>]* data-conditional-action="' . $action . '" data-conditional-trigger="one">/', $html );
			$this->assertStringContainsString( '<textarea class="super-conditional-logic"', $html );
			$this->assertStringNotContainsString( 'super-error-msg', $html );
		}
	}

	/**
	 * Expected HTML captured from button() before Conditional Logic support was added.
	 */
	public function test_button_without_conditions_renders_unchanged_html() {
		$colors = '<div data-color="#f26c68" data-light="#ff807c" data-dark="#d44e4a" data-hover-color="#444444" data-hover-light="#585858" data-hover-dark="#262626" data-font="#ffffff" data-font-hover="#ffffff" data-radius="rounded" data-type="flat" ';
		$cases  = array(
			array(
				array( 'name' => 'Send now' ),
				$colors . 'class="super-extra-shortcode super-shortcode super-field super-button super-clear-none super-form-button super-radius-rounded super-type-flat super-button-medium super-button-align-left super-button-width-auto"><div data-href="" class="super-button-wrap no_link"><div class="super-button-name" data-action="submit" data-normal="Send now" data-loading="Loading...">Send now</div><span class="super-after"></span></div></div>',
			),
			array(
				array(
					'name'               => 'Next step',
					'action'             => 'next',
					'conditional_action' => 'disabled',
					'conditional_items'  => $this->conditions(),
					'wrapper_class'      => 'my-wrap',
					'class'              => 'my-btn',
				),
				$colors . 'class="super-extra-shortcode super-shortcode super-field super-button super-clear-none super-next-multipart super-radius-rounded super-type-flat super-button-medium super-button-align-left super-button-width-auto my-wrap"><div data-href="" class="super-button-wrap no_link my-btn"><div class="super-button-name" data-action="next" data-normal="Next step" data-loading="Loading...">Next step</div><span class="super-after"></span></div></div>',
			),
			array(
				array(
					'name'            => 'Go',
					'action'          => 'url',
					'link'            => 'custom',
					'custom_link'     => 'https://example.org/next',
					'target'          => '_blank',
					'custom_icon'     => 'custom',
					'icon_option'     => 'left',
					'icon_visibility' => 'visible',
					'icon'            => 'arrow-right;fas',
				),
				$colors . 'class="super-extra-shortcode super-shortcode super-field super-button super-clear-none super-form-button super-radius-rounded super-type-flat super-button-medium super-button-align-left super-button-width-auto super-button-icon-option-left super-button-icon-visibility-visible"><div data-target="_blank" data-href="https://example.org/next" class="super-button-wrap no_link"><span class="super-before"><i class="fas fa-arrow-right"></i></span><div class="super-button-name" data-action="url" data-normal="Go" data-loading="Loading...">Go</div><span class="super-after"></span></div></div>',
			),
		);
		foreach ( $cases as $case ) {
			$this->assertSame( $case[1], $this->render( 'button', 'form_elements', $case[0] ) );
		}
	}

	public function test_divider_with_conditions_renders_conditional_logic_markup() {
		$html = $this->render(
			'divider',
			'html_elements',
			array(
				'conditional_action'  => 'show',
				'conditional_trigger' => 'all',
				'conditional_items'   => $this->conditions(),
			)
		);
		$this->assertMatchesRegularExpression( '/^<div [^>]*class="[^"]*super-divider[^"]*super-conditional-hidden[^"]*"[^>]*data-conditional-action="show" data-conditional-trigger="all"/', $html );
		$this->assertStringContainsString( '<textarea class="super-conditional-logic" data-fields="{email}">', $html );
		$this->assertStringNotContainsString( 'super-error-msg', $html );
	}
}
