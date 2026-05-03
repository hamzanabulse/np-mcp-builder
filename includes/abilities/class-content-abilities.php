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
                    'featured_image_id' => (int) get_post_thumbnail_id( $p ),
                    'categories' => wp_get_post_terms( $p->ID, 'category', array( 'fields' => 'names' ) ),
                    'tags'       => wp_get_post_terms( $p->ID, 'post_tag', array( 'fields' => 'names' ) ),
                    'meta'     => array(
                        'yoast_title' => get_post_meta( $p->ID, '_yoast_wpseo_title', true ),
                        'yoast_desc'  => get_post_meta( $p->ID, '_yoast_wpseo_metadesc', true ),
                        'yoast_focus' => get_post_meta( $p->ID, '_yoast_wpseo_focuskw', true ),
                        'yoast_canonical' => get_post_meta( $p->ID, '_yoast_wpseo_canonical', true ),
                        'yoast_schema_page_type' => get_post_meta( $p->ID, '_yoast_wpseo_schema_page_type', true ),
                        'schema_jsonld' => get_post_meta( $p->ID, '_np_mcp_schema_jsonld', true ),
                    ),
                );
            },
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        // 4) Create post.
        wp_register_ability( 'np/create-post', array(
            'label'       => 'Create Post',
            'description' => 'Create a new blog post/page with SEO-ready fields: slug, categories, tags, featured image, Yoast meta, schema JSON-LD, custom CSS/JS. Returns the new ID, permalink and edit link.',
            'category'    => 'np-content',
            'input_schema' => array(
                'type' => 'object',
                'required' => array( 'title' ),
                'properties' => array(
                    'title'      => array( 'type' => 'string' ),
                    'content'    => array( 'type' => 'string' ),
                    'excerpt'    => array( 'type' => 'string' ),
                    'slug'       => array( 'type' => 'string' ),
                    'status'     => array( 'type' => 'string', 'enum' => array( 'draft', 'publish', 'pending', 'private' ), 'default' => 'draft' ),
                    'post_type'  => array( 'type' => 'string', 'default' => 'post' ),
                    'categories' => array( 'type' => 'array', 'items' => array( 'type' => array( 'string', 'integer' ) ), 'description' => 'Category IDs, names, or slugs.' ),
                    'tags'       => array( 'type' => 'array', 'items' => array( 'type' => array( 'string', 'integer' ) ), 'description' => 'Tag IDs, names, or slugs.' ),
                    'featured_image_id' => array( 'type' => 'integer' ),
                    'yoast_title' => array( 'type' => 'string' ),
                    'yoast_desc'  => array( 'type' => 'string' ),
                    'yoast_focus' => array( 'type' => 'string' ),
                    'yoast_canonical' => array( 'type' => 'string' ),
                    'yoast_og_title'  => array( 'type' => 'string' ),
                    'yoast_og_desc'   => array( 'type' => 'string' ),
                    'yoast_og_image'  => array( 'type' => 'string' ),
                    'yoast_twitter_title' => array( 'type' => 'string' ),
                    'yoast_twitter_desc'  => array( 'type' => 'string' ),
                    'yoast_twitter_image' => array( 'type' => 'string' ),
                    'yoast_schema_page_type' => array( 'type' => 'string' ),
                    'schema_jsonld' => array( 'type' => 'array', 'description' => 'Array of JSON-LD strings or objects injected in wp_head.' ),
                    'custom_css'    => array( 'type' => 'string' ),
                    'custom_js'     => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback' => static function ( array $input ) {
                $post_data = array(
                    'post_title'    => $input['title'],
                    'post_content'  => $input['content'] ?? '',
                    'post_excerpt'  => $input['excerpt'] ?? '',
                    'post_status'   => $input['status'] ?? 'draft',
                    'post_type'     => $input['post_type'] ?? 'post',
                );
                if ( ! empty( $input['slug'] ) ) {
                    $post_data['post_name'] = sanitize_title( (string) $input['slug'] );
                }
                $id = wp_insert_post( $post_data, true );
                if ( is_wp_error( $id ) ) { return array( 'error' => $id->get_error_message() ); }
                self::apply_post_terms( $id, $input );
                self::apply_featured_image( $id, $input );
                self::apply_seo_meta( $id, $input );
                self::apply_np_assets( $id, $input );
                return array( 'id' => $id, 'link' => get_permalink( $id ), 'edit_link' => get_edit_post_link( $id, 'raw' ) );
            },
            'permission_callback' => static function () { return current_user_can( 'publish_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        // 5) Update post.
        wp_register_ability( 'np/update-post', array(
            'label'       => 'Update Post',
            'description' => 'Update fields, taxonomy, featured image, Yoast meta, schema JSON-LD and custom CSS/JS of an existing post or page.',
            'category'    => 'np-content',
            'input_schema' => array(
                'type' => 'object',
                'required' => array( 'id' ),
                'properties' => array(
                    'id'      => array( 'type' => 'integer' ),
                    'title'   => array( 'type' => 'string' ),
                    'content' => array( 'type' => 'string' ),
                    'excerpt' => array( 'type' => 'string' ),
                    'slug'    => array( 'type' => 'string' ),
                    'status'  => array( 'type' => 'string' ),
                    'categories' => array( 'type' => 'array', 'items' => array( 'type' => array( 'string', 'integer' ) ), 'description' => 'Category IDs, names, or slugs.' ),
                    'tags'       => array( 'type' => 'array', 'items' => array( 'type' => array( 'string', 'integer' ) ), 'description' => 'Tag IDs, names, or slugs.' ),
                    'append_terms' => array( 'type' => 'boolean', 'default' => false ),
                    'featured_image_id' => array( 'type' => 'integer' ),
                    'yoast_title' => array( 'type' => 'string' ),
                    'yoast_desc'  => array( 'type' => 'string' ),
                    'yoast_focus' => array( 'type' => 'string' ),
                    'yoast_canonical' => array( 'type' => 'string' ),
                    'yoast_og_title'  => array( 'type' => 'string' ),
                    'yoast_og_desc'   => array( 'type' => 'string' ),
                    'yoast_og_image'  => array( 'type' => 'string' ),
                    'yoast_twitter_title' => array( 'type' => 'string' ),
                    'yoast_twitter_desc'  => array( 'type' => 'string' ),
                    'yoast_twitter_image' => array( 'type' => 'string' ),
                    'yoast_schema_page_type' => array( 'type' => 'string' ),
                    'schema_jsonld' => array( 'type' => 'array', 'description' => 'Array of JSON-LD strings or objects injected in wp_head.' ),
                    'custom_css'    => array( 'type' => 'string' ),
                    'custom_js'     => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback' => static function ( array $input ) {
                $data = array( 'ID' => (int) $input['id'] );
                foreach ( array( 'title' => 'post_title', 'content' => 'post_content', 'excerpt' => 'post_excerpt', 'status' => 'post_status', 'slug' => 'post_name' ) as $k => $col ) {
                    if ( array_key_exists( $k, $input ) ) { $data[ $col ] = ( $k === 'slug' ) ? sanitize_title( (string) $input[ $k ] ) : $input[ $k ]; }
                }
                $r = wp_update_post( $data, true );
                if ( is_wp_error( $r ) ) { return array( 'error' => $r->get_error_message() ); }
                self::apply_post_terms( $r, $input, ! empty( $input['append_terms'] ) );
                self::apply_featured_image( $r, $input );
                self::apply_seo_meta( $r, $input );
                self::apply_np_assets( $r, $input );
                return array( 'id' => $r, 'link' => get_permalink( $r ) );
            },
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );
    }

    private static function apply_post_terms( int $post_id, array $input, bool $append = false ): void {
        if ( array_key_exists( 'categories', $input ) ) {
            wp_set_post_terms( $post_id, self::resolve_terms( (array) $input['categories'], 'category' ), 'category', $append );
        }
        if ( array_key_exists( 'tags', $input ) ) {
            wp_set_post_terms( $post_id, self::resolve_terms( (array) $input['tags'], 'post_tag' ), 'post_tag', $append );
        }
    }

    private static function resolve_terms( array $terms, string $taxonomy ): array {
        $resolved = array();
        $hierarchical = is_taxonomy_hierarchical( $taxonomy );
        foreach ( $terms as $term ) {
            if ( is_int( $term ) || ( is_string( $term ) && ctype_digit( $term ) ) ) {
                $resolved[] = (int) $term;
                continue;
            }
            $name = trim( wp_strip_all_tags( (string) $term ) );
            if ( $name === '' ) { continue; }
            $existing = get_term_by( 'name', $name, $taxonomy );
            if ( ! $existing ) { $existing = get_term_by( 'slug', sanitize_title( $name ), $taxonomy ); }
            if ( $existing ) {
                $resolved[] = (int) $existing->term_id;
                continue;
            }
            if ( $hierarchical ) {
                $created = wp_insert_term( $name, $taxonomy );
                if ( ! is_wp_error( $created ) ) { $resolved[] = (int) $created['term_id']; }
            } else {
                $resolved[] = $name;
            }
        }
        return $resolved;
    }

    private static function apply_featured_image( int $post_id, array $input ): void {
        if ( array_key_exists( 'featured_image_id', $input ) ) {
            $attachment_id = (int) $input['featured_image_id'];
            if ( $attachment_id > 0 ) { set_post_thumbnail( $post_id, $attachment_id ); }
            else { delete_post_thumbnail( $post_id ); }
        }
    }

    private static function apply_seo_meta( int $post_id, array $input ): void {
        $map = array(
            'yoast_title'            => '_yoast_wpseo_title',
            'yoast_desc'             => '_yoast_wpseo_metadesc',
            'yoast_focus'            => '_yoast_wpseo_focuskw',
            'yoast_canonical'        => '_yoast_wpseo_canonical',
            'yoast_og_title'         => '_yoast_wpseo_opengraph-title',
            'yoast_og_desc'          => '_yoast_wpseo_opengraph-description',
            'yoast_og_image'         => '_yoast_wpseo_opengraph-image',
            'yoast_twitter_title'    => '_yoast_wpseo_twitter-title',
            'yoast_twitter_desc'     => '_yoast_wpseo_twitter-description',
            'yoast_twitter_image'    => '_yoast_wpseo_twitter-image',
            'yoast_schema_page_type' => '_yoast_wpseo_schema_page_type',
        );
        foreach ( $map as $input_key => $meta_key ) {
            if ( array_key_exists( $input_key, $input ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( (string) $input[ $input_key ] ) );
            }
        }
    }

    private static function apply_np_assets( int $post_id, array $input ): void {
        if ( array_key_exists( 'schema_jsonld', $input ) ) {
            $schemas = array();
            foreach ( (array) $input['schema_jsonld'] as $schema ) {
                if ( is_array( $schema ) || is_object( $schema ) ) { $schema = wp_json_encode( $schema ); }
                if ( is_string( $schema ) && trim( $schema ) !== '' ) { $schemas[] = trim( $schema ); }
            }
            update_post_meta( $post_id, '_np_mcp_schema_jsonld', $schemas );
        }
        if ( array_key_exists( 'custom_css', $input ) ) { update_post_meta( $post_id, '_np_mcp_custom_css', (string) $input['custom_css'] ); }
        if ( array_key_exists( 'custom_js', $input ) )  { update_post_meta( $post_id, '_np_mcp_custom_js', (string) $input['custom_js'] ); }
    }
}
