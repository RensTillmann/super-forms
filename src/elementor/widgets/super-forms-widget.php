<?php
/**
 * Elementor Super Forms Widget.
 *
 * Elementor widget that inserts an embbedable content into the page, from any given URL.
 *
 * @since 4.9.300
 */
class Elementor_Super_Forms_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * Retrieve Super Forms widget name.
	 *
	 * @since 4.9.300
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'oembed';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve Super Forms widget title.
	 *
	 * @since 4.9.300
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return 'Super Forms';
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve Super Forms widget icon.
	 *
	 * @since 4.9.300
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
        return 'elementor-super-forms-icon';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the Super Forms widget belongs to.
	 *
	 * @since 4.9.300
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'basic' ];
	}

	/**
	 * Register Super Forms widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 4.9.300
	 * @access protected
	 */
	protected function _register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Content', 'super-forms' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);
        // Get all Forms created with Super Forms (post type: super_form)
        $args = array(
			'post_type' => 'super_form',
			'numberposts' => -1
        );
        $forms = get_posts( $args );
        $form_ids = [ '0' => esc_html__( '- select -', 'super-forms' ) ];
        foreach($forms as $value){
            $form_ids[$value->ID] = $value->post_title;
		}
		$this->add_control(
			'form_id',
			[
				'label' => esc_html__( 'Select a form', 'super-forms' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'options' => $form_ids,
				'default' => '0',
				'separator' => 'after',
				'label_block' => true
			]
		);		

		$this->end_controls_section();

	}

	/**
	 * Render Super Forms widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 4.9.300
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$form_id = absint($settings['form_id']);
		if($form_id==0){
			echo esc_html__( 'No form selected, please select a form!', 'super-forms' );	
		}else{
			echo do_shortcode('[super_form id="'.$form_id.'"]');
			SUPER_Forms::add_form_styles();
		}
	}

}
