<?php
/**
 * Content abilities — site info, list/get/create/update posts.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder\Abilities;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Content_Abilities {

    public static function register(): void {
        // 1) Site info.
        wp_register_ability( 'np/site-info', array(
            'label'       => 'Site Info',
            'description' => 'Get basic info about this WordPress site (name, URL, theme, language, version).',
            'category'    => 'np-site',
            'input_schema'  => array( 'type' => 'object' ),
            'output_schema' => array( 'type' => 'object' ),
            'execute_callback' => static function () {
                return array(
                    'name'        => get_bloginfo( 'name' ),
                    'description' => get_bloginfo( 'description' ),
                    'url'         => home_url(),
                    'admin_email' => get_bloginfo( 'admin_email' ),
                    'language'    => get_bloginfo( 'language' ),
                    'version'     => get_bloginfo( 'version' ),
                    'theme'       => get_stylesheet(),
                    'timezone'    => wp_timezone_string(),
                );
            },
            'permission_callback' => static function () { return current_user_can( 'manage_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        // 2) List posts.
        wp_register_ability( 'np/list-posts', array(
            'label'       => 'List Posts',
            'description' => 'List blog posts (any status). Optional filters: per_page, status, search, post_type.',
            'category'    => 'np-content',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'per_page'  => array( 'type' => 'integer', 'default' => 20 ),
                    'status'    => array( 'type' => 'string',  'default' => 'any' ),
                    'search'    => array( 'type' => 'string' ),
                    'post_type' => array( 'type' => 'string', 'default' => 'post' ),
                ),
            ),
            'execute_callback' => static function ( array $input = array() ) {
                $q = new \WP_Query( array(
                    'post_type'      => $input['post_type'] ?? 'post',
                    'posts_per_page' => isset( $input['per_page'] ) ? (int) $input['per_page'] : 20,
                    'post_status'    => $input['status'] ?? 'any',
                    's'              => $input['search'] ?? '',
                ) );
                $out = array();
                foreach ( $q->posts as $p ) {
                    $out[] = array(
                        'id'      => $p->ID,
                        'title'   => get_the_title( $p ),
                        'status'  => $p->post_status,
                        'date'    => $p->post_date,
                        'link'    => get_permalink( $p ),
                        'excerpt' => wp_strip_all_tags( get_the_excerpt( $p ) ),
                    );
                }
                return array( 'count' => count( $out ), 'posts' => $out );
            },
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        // 3) Get post.
        wp_register_ability( 'np/get-post', array(
            'label'       => 'Get Post',
            'description' => 'Get a single post or page by ID with full content and Yoast meta.',
            'category'    => 'np-content',
            'input_schema' => array(
                'type' => 'object',
                'required' => array( 'id' ),
                'properties' => array( 'id' => array( 'type' => 'integer' ) ),
            ),
            'execute_callback' => static function ( array $input ) {
                $p = get_post( (int) $input['id'] );
                if ( ! $p ) { return array( 'error' => 'not_found' ); }
                return array(
                    'id'       => $p->ID,
                    'type'     => $p->post_type,
                    'title'    => $p->post_title,
                    'status'   => $p->post_status,
                    'date'     => $p->post_date,
                    'modified' => $p->post_modified,
                    'content'  => $p->post_content,
                    'excerpt'  => $p->post_excerpt,
                    'slug'     => $p->post_name,
                    'link'     => get_permalink( $p ),
                    'meta'     => array(
                        'yoast_title' => get_post_meta( $p->ID, '_yoast_wpseo_title', true ),
                        'yoast_desc'  => get_post_meta( $p->ID, '_yoast_wpseo_metadesc', true ),
                        'yoast_focus' => get_post_meta( $p->ID, '_yoast_wpseo_focuskw', true ),
                    ),
                );
            },
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        // 4) Create post.
        wp_register_ability( 'np/create-post', array(
            'label'       => 'Create Post',
            'description' => 'Create a new blog post. Returns the new ID, permalink and edit link.',
            'category'    => 'np-content',
            'input_schema' => array(
                'type' => 'object',
                'required' => array( 'title' ),
                'properties' => array(
                    'title'      => array( 'type' => 'string' ),
                    'content'    => array( 'type' => 'string' ),
                    'excerpt'    => array( 'type' => 'string' ),
                    'status'     => array( 'type' => 'string', 'enum' => array( 'draft', 'publish', 'pending', 'private' ), 'default' => 'draft' ),
                    'post_type'  => array( 'type' => 'string', 'default' => 'post' ),
                    'categories' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
                    'tags'       => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
                    'yoast_title' => array( 'type' => 'string' ),
                    'yoast_desc'  => array( 'type' => 'string' ),
                    'yoast_focus' => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback' => static function ( array $input ) {
                $id = wp_insert_post( array(
                    'post_title'    => $input['title'],
                    'post_content'  => $input['content'] ?? '',
                    'post_excerpt'  => $input['excerpt'] ?? '',
                    'post_status'   => $input['status'] ?? 'draft',
                    'post_type'     => $input['post_type'] ?? 'post',
                    'post_category' => $input['categories'] ?? array(),
                ), true );
                if ( is_wp_error( $id ) ) { return array( 'error' => $id->get_error_message() ); }
                if ( ! empty( $input['tags'] ) ) { wp_set_post_tags( $id, $input['tags'] ); }
                if ( ! empty( $input['yoast_title'] ) ) { update_post_meta( $id, '_yoast_wpseo_title', $input['yoast_title'] ); }
                if ( ! empty( $input['yoast_desc'] ) )  { update_post_meta( $id, '_yoast_wpseo_metadesc', $input['yoast_desc'] ); }
                if ( ! empty( $input['yoast_focus'] ) ) { update_post_meta( $id, '_yoast_wpseo_focuskw', $input['yoast_focus'] ); }
                return array( 'id' => $id, 'link' => get_permalink( $id ), 'edit_link' => get_edit_post_link( $id, 'raw' ) );
            },
            'permission_callback' => static function () { return current_user_can( 'publish_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        // 5) Update post.
        wp_register_ability( 'np/update-post', array(
            'label'       => 'Update Post',
            'description' => 'Update fields and Yoast meta of an existing post or page.',
            'category'    => 'np-content',
            'input_schema' => array(
                'type' => 'object',
                'required' => array( 'id' ),
                'properties' => array(
                    'id'      => array( 'type' => 'integer' ),
                    'title'   => array( 'type' => 'string' ),
                    'content' => array( 'type' => 'string' ),
                    'excerpt' => array( 'type' => 'string' ),
                    'status'  => array( 'type' => 'string' ),
                    'yoast_title' => array( 'type' => 'string' ),
                    'yoast_desc'  => array( 'type' => 'string' ),
                    'yoast_focus' => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback' => static function ( array $input ) {
                $data = array( 'ID' => (int) $input['id'] );
                foreach ( array( 'title' => 'post_title', 'content' => 'post_content', 'excerpt' => 'post_excerpt', 'status' => 'post_status' ) as $k => $col ) {
                    if ( array_key_exists( $k, $input ) ) { $data[ $col ] = $input[ $k ]; }
                }
                $r = wp_update_post( $data, true );
                if ( is_wp_error( $r ) ) { return array( 'error' => $r->get_error_message() ); }
                if ( array_key_exists( 'yoast_title', $input ) ) { update_post_meta( $r, '_yoast_wpseo_title', $input['yoast_title'] ); }
                if ( array_key_exists( 'yoast_desc', $input ) )  { update_post_meta( $r, '_yoast_wpseo_metadesc', $input['yoast_desc'] ); }
                if ( array_key_exists( 'yoast_focus', $input ) ) { update_post_meta( $r, '_yoast_wpseo_focuskw', $input['yoast_focus'] ); }
                return array( 'id' => $r, 'link' => get_permalink( $r ) );
            },
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );
    }
}
