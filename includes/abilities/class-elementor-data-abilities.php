<?php
/**
 * Elementor_Data_Abilities — low-level Elementor data ops:
 * raw read/write, template management, CSS regeneration.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder\Abilities;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Elementor_Data_Abilities {

    public static function register(): void {
        if ( ! function_exists( 'wp_register_ability' ) ) { return; }

        wp_register_ability( 'np/elementor-get-data', array(
            'label' => 'Get Elementor data', 'category' => 'np-elementor',
            'description' => 'Read the raw _elementor_data of a post as a decoded array of section objects, plus _elementor_page_settings (page-level settings) and _elementor_edit_mode.',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'post_id' ),
                'properties' => array( 'post_id' => array( 'type' => 'integer' ) ),
            ),
            'execute_callback'    => array( __CLASS__, 'get_data' ),
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/elementor-set-data', array(
            'label' => 'Set Elementor data', 'category' => 'np-elementor',
            'description' => 'Replace the raw _elementor_data of a post. Pass an array of Elementor section nodes (the same format returned by np/elementor-get-data). Marks the post as Elementor-built and clears its CSS cache.',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'post_id', 'data' ),
                'properties' => array(
                    'post_id'       => array( 'type' => 'integer' ),
                    'data'          => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
                    'page_settings' => array( 'type' => 'object' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'set_data' ),
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/elementor-list-templates', array(
            'label' => 'List Elementor templates', 'category' => 'np-elementor',
            'description' => 'List Elementor library templates (page, section, container, popup, header, footer, single, archive, loop-item).',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'template_type' => array( 'type' => 'string' ),
                    'limit'         => array( 'type' => 'integer', 'default' => 100 ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'list_templates' ),
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/elementor-save-as-template', array(
            'label' => 'Save post as Elementor template', 'category' => 'np-elementor',
            'description' => 'Copy the Elementor data from an existing post into a new entry of the elementor_library post type so it can be reused across pages.',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'post_id', 'name' ),
                'properties' => array(
                    'post_id'       => array( 'type' => 'integer' ),
                    'name'          => array( 'type' => 'string' ),
                    'template_type' => array( 'type' => 'string', 'default' => 'page' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'save_as_template' ),
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/elementor-apply-template', array(
            'label' => 'Apply Elementor template', 'category' => 'np-elementor',
            'description' => 'Apply an Elementor library template to a target post. Either replaces the target Elementor data (replace=true) or appends the template sections after existing ones.',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'template_id', 'post_id' ),
                'properties' => array(
                    'template_id' => array( 'type' => 'integer' ),
                    'post_id'     => array( 'type' => 'integer' ),
                    'replace'     => array( 'type' => 'boolean', 'default' => false ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'apply_template' ),
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/elementor-regenerate-css', array(
            'label' => 'Regenerate Elementor CSS', 'category' => 'np-elementor',
            'description' => 'Regenerate per-post Elementor CSS files (clears _elementor_css meta, so files are rebuilt on next view). Pass post_id for a single post, or omit to regenerate the global cache.',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'post_id' => array( 'type' => 'integer' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'regenerate_css' ),
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );
    }

    /* ==================================================================== */

    public static function get_data( array $input ) {
        $post_id = (int) ( $input['post_id'] ?? 0 );
        if ( ! $post_id || ! get_post( $post_id ) ) {
            return new \WP_Error( 'np_no_post', 'Post not found.' );
        }
        $raw = (string) get_post_meta( $post_id, '_elementor_data', true );
        $data = $raw !== '' ? json_decode( $raw, true ) : array();
        if ( ! is_array( $data ) ) { $data = array(); }
        return array(
            'post_id'       => $post_id,
            'edit_mode'     => (string) get_post_meta( $post_id, '_elementor_edit_mode', true ),
            'template'      => (string) get_post_meta( $post_id, '_elementor_template_type', true ),
            'data'          => $data,
            'page_settings' => (array) get_post_meta( $post_id, '_elementor_page_settings', true ),
            'sections_count'=> count( $data ),
        );
    }

    public static function set_data( array $input ) {
        if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
            return new \WP_Error( 'np_no_elementor', 'Elementor is not installed.' );
        }
        $post_id = (int) ( $input['post_id'] ?? 0 );
        if ( ! $post_id || ! get_post( $post_id ) ) {
            return new \WP_Error( 'np_no_post', 'Post not found.' );
        }
        $data = $input['data'] ?? array();
        if ( ! is_array( $data ) ) {
            return new \WP_Error( 'np_bad_data', 'data must be an array of Elementor nodes.' );
        }
        update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
        update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
        if ( ! empty( $input['page_settings'] ) && is_array( $input['page_settings'] ) ) {
            update_post_meta( $post_id, '_elementor_page_settings', $input['page_settings'] );
        }
        delete_post_meta( $post_id, '_elementor_css' );
        try {
            \Elementor\Plugin::instance()->files_manager->clear_cache();
        } catch ( \Throwable $e ) { /* noop */ }
        return array( 'post_id' => $post_id, 'sections' => count( $data ) );
    }

    public static function list_templates( array $input ): array {
        $args = array(
            'post_type'      => 'elementor_library',
            'post_status'    => array( 'publish', 'draft', 'private' ),
            'posts_per_page' => max( 1, min( 500, (int) ( $input['limit'] ?? 100 ) ) ),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        );
        if ( ! empty( $input['template_type'] ) ) {
            $args['meta_query'] = array(
                array(
                    'key'   => '_elementor_template_type',
                    'value' => sanitize_text_field( (string) $input['template_type'] ),
                ),
            );
        }
        $posts = get_posts( $args );
        $rows  = array();
        foreach ( $posts as $p ) {
            $rows[] = array(
                'id'            => $p->ID,
                'name'          => $p->post_title,
                'slug'          => $p->post_name,
                'template_type' => (string) get_post_meta( $p->ID, '_elementor_template_type', true ),
                'status'        => $p->post_status,
                'modified'      => $p->post_modified,
            );
        }
        return array( 'count' => count( $rows ), 'templates' => $rows );
    }

    public static function save_as_template( array $input ) {
        $post_id = (int) ( $input['post_id'] ?? 0 );
        if ( ! $post_id || ! get_post( $post_id ) ) {
            return new \WP_Error( 'np_no_post', 'Post not found.' );
        }
        $name = sanitize_text_field( (string) ( $input['name'] ?? '' ) );
        if ( $name === '' ) { return new \WP_Error( 'np_no_name', 'Template name required.' ); }
        $type = sanitize_text_field( (string) ( $input['template_type'] ?? 'page' ) );

        $tpl_id = wp_insert_post( array(
            'post_title'  => $name,
            'post_status' => 'publish',
            'post_type'   => 'elementor_library',
        ), true );
        if ( is_wp_error( $tpl_id ) ) { return $tpl_id; }

        $raw = (string) get_post_meta( $post_id, '_elementor_data', true );
        if ( $raw !== '' ) {
            update_post_meta( $tpl_id, '_elementor_data', wp_slash( $raw ) );
        }
        update_post_meta( $tpl_id, '_elementor_template_type', $type );
        update_post_meta( $tpl_id, '_elementor_edit_mode', 'builder' );
        wp_set_object_terms( $tpl_id, $type, 'elementor_library_type' );

        return array(
            'template_id'   => (int) $tpl_id,
            'name'          => $name,
            'template_type' => $type,
        );
    }

    public static function apply_template( array $input ) {
        if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
            return new \WP_Error( 'np_no_elementor', 'Elementor is not installed.' );
        }
        $tpl_id  = (int) ( $input['template_id'] ?? 0 );
        $post_id = (int) ( $input['post_id'] ?? 0 );
        if ( ! $tpl_id || get_post_type( $tpl_id ) !== 'elementor_library' ) {
            return new \WP_Error( 'np_no_template', 'Template not found.' );
        }
        if ( ! $post_id || ! get_post( $post_id ) ) {
            return new \WP_Error( 'np_no_post', 'Target post not found.' );
        }
        $tpl_raw = (string) get_post_meta( $tpl_id, '_elementor_data', true );
        $tpl_data = $tpl_raw !== '' ? json_decode( $tpl_raw, true ) : array();
        if ( ! is_array( $tpl_data ) ) { $tpl_data = array(); }

        if ( ! empty( $input['replace'] ) ) {
            $merged = $tpl_data;
        } else {
            $cur_raw = (string) get_post_meta( $post_id, '_elementor_data', true );
            $cur     = $cur_raw !== '' ? json_decode( $cur_raw, true ) : array();
            if ( ! is_array( $cur ) ) { $cur = array(); }
            $merged = array_merge( $cur, $tpl_data );
        }
        update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $merged ) ) );
        update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
        delete_post_meta( $post_id, '_elementor_css' );
        try {
            \Elementor\Plugin::instance()->files_manager->clear_cache();
        } catch ( \Throwable $e ) { /* noop */ }
        return array( 'post_id' => $post_id, 'sections' => count( $merged ), 'replaced' => ! empty( $input['replace'] ) );
    }

    public static function regenerate_css( array $input ): array {
        if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
            return array( 'elementor_installed' => false );
        }
        if ( ! empty( $input['post_id'] ) ) {
            $post_id = (int) $input['post_id'];
            delete_post_meta( $post_id, '_elementor_css' );
            return array( 'post_id' => $post_id, 'cleared' => true );
        }
        try {
            \Elementor\Plugin::instance()->files_manager->clear_cache();
        } catch ( \Throwable $e ) {
            return array( 'cleared' => false, 'error' => $e->getMessage() );
        }
        return array( 'cleared' => true );
    }
}
