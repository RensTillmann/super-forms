<?php
/**
 * Create Post Action
 *
 * Creates a WordPress post from form data
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SUPER_Action_Create_Post extends SUPER_Trigger_Action_Base {

    /**
     * Get action ID
     *
     * @return string
     */
    public function get_id() {
        return 'create_post';
    }

    /**
     * Get action label
     *
     * @return string
     */
    public function get_label() {
        return __('Create WordPress Post', 'super-forms');
    }

    /**
     * Get action category
     *
     * @return string
     */
    public function get_category() {
        return 'content';
    }

    /**
     * Get action description
     *
     * @return string
     */
    public function get_description() {
        return __('Create a WordPress post or custom post type from form data', 'super-forms');
    }

    /**
     * Get settings schema
     *
     * @return array
     */
    public function get_settings_schema() {
        return [
            [
                'name' => 'post_type',
                'label' => __('Post Type', 'super-forms'),
                'type' => 'select',
                'required' => true,
                'default' => 'post',
                'options' => $this->get_post_type_options(),
                'description' => __('The type of post to create', 'super-forms')
            ],
            [
                'name' => 'post_title',
                'label' => __('Post Title', 'super-forms'),
                'type' => 'text',
                'required' => true,
                'default' => '',
                'placeholder' => '{form_data.name} - Submission',
                'description' => __('Post title. Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'post_content',
                'label' => __('Post Content', 'super-forms'),
                'type' => 'textarea',
                'required' => false,
                'default' => '',
                'description' => __('Post content. Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'post_excerpt',
                'label' => __('Post Excerpt', 'super-forms'),
                'type' => 'textarea',
                'required' => false,
                'default' => '',
                'description' => __('Post excerpt. Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'post_status',
                'label' => __('Post Status', 'super-forms'),
                'type' => 'select',
                'required' => true,
                'default' => 'draft',
                'options' => [
                    'draft' => __('Draft', 'super-forms'),
                    'publish' => __('Published', 'super-forms'),
                    'pending' => __('Pending Review', 'super-forms'),
                    'private' => __('Private', 'super-forms')
                ],
                'description' => __('Initial post status', 'super-forms')
            ],
            [
                'name' => 'post_author',
                'label' => __('Post Author', 'super-forms'),
                'type' => 'select',
                'required' => false,
                'default' => 'current_user',
                'options' => [
                    'current_user' => __('Current User', 'super-forms'),
                    'admin' => __('Site Admin', 'super-forms'),
                    'custom' => __('Custom User ID', 'super-forms')
                ],
                'description' => __('Who should be the post author', 'super-forms')
            ],
            [
                'name' => 'custom_author_id',
                'label' => __('Custom Author ID', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'placeholder' => '1 or {user_id}',
                'description' => __('User ID for post author. Supports {tags}.', 'super-forms'),
                'show_if' => ['post_author' => 'custom']
            ],
            [
                'name' => 'post_category',
                'label' => __('Categories', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'placeholder' => '1,5,12',
                'description' => __('Comma-separated category IDs. Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'post_tags',
                'label' => __('Tags', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'placeholder' => 'tag1,tag2,tag3',
                'description' => __('Comma-separated tag names. Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'link_to_entry',
                'label' => __('Link to Entry', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => true,
                'description' => __('Store entry_id in post meta for reference', 'super-forms')
            ]
        ];
    }

    /**
     * Execute the action
     *
     * @param array $context Event context data
     * @param array $config Action configuration
     * @return array|WP_Error Result data or error
     */
    public function execute($context, $config) {
        // Build post data
        $post_data = [
            'post_type' => $config['post_type'] ?? 'post',
            'post_title' => $this->replace_tags($config['post_title'], $context),
            'post_status' => $config['post_status'] ?? 'draft'
        ];

        // Post content
        if (!empty($config['post_content'])) {
            $post_data['post_content'] = $this->replace_tags($config['post_content'], $context);
        }

        // Post excerpt
        if (!empty($config['post_excerpt'])) {
            $post_data['post_excerpt'] = $this->replace_tags($config['post_excerpt'], $context);
        }

        // Post author
        $post_data['post_author'] = $this->get_post_author($context, $config);

        // Insert post
        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Set categories
        if (!empty($config['post_category'])) {
            $categories = $this->replace_tags($config['post_category'], $context);
            $category_ids = array_map('absint', array_map('trim', explode(',', $categories)));
            wp_set_post_categories($post_id, $category_ids);
        }

        // Set tags
        if (!empty($config['post_tags'])) {
            $tags = $this->replace_tags($config['post_tags'], $context);
            $tag_names = array_map('trim', explode(',', $tags));
            wp_set_post_tags($post_id, $tag_names);
        }

        // Link to entry
        if (!empty($config['link_to_entry']) && !empty($context['entry_id'])) {
            update_post_meta($post_id, '_super_contact_entry_id', $context['entry_id']);
            update_post_meta($post_id, '_super_form_id', $context['form_id'] ?? 0);
        }

        // Fire action hook
        do_action('super_trigger_post_created', $post_id, $context, $config);

        return [
            'post_id' => $post_id,
            'post_url' => get_permalink($post_id),
            'edit_url' => get_edit_post_link($post_id, 'raw'),
            'message' => sprintf(__('Post created: %s', 'super-forms'), get_the_title($post_id))
        ];
    }

    /**
     * Get post author ID
     *
     * @param array $context
     * @param array $config
     * @return int
     */
    protected function get_post_author($context, $config) {
        $author_option = $config['post_author'] ?? 'current_user';

        switch ($author_option) {
            case 'admin':
                $admin_users = get_users(['role' => 'administrator', 'number' => 1]);
                return !empty($admin_users) ? $admin_users[0]->ID : 1;

            case 'custom':
                if (!empty($config['custom_author_id'])) {
                    $author_id = $this->replace_tags($config['custom_author_id'], $context);
                    return absint($author_id);
                }
                return get_current_user_id();

            case 'current_user':
            default:
                return get_current_user_id() ?: 1;
        }
    }

    /**
     * Get available post types
     *
     * @return array
     */
    protected function get_post_type_options() {
        $post_types = get_post_types(['public' => true], 'objects');
        $options = [];

        foreach ($post_types as $post_type) {
            $options[$post_type->name] = $post_type->label;
        }

        return $options;
    }

    /**
     * Check if action can run
     *
     * @param array $context
     * @return bool
     */
    public function can_run($context) {
        return true;
    }

    /**
     * Get required capabilities
     *
     * @return array
     */
    public function get_required_capabilities() {
        return ['manage_options', 'publish_posts'];
    }
}
