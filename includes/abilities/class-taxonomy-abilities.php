<?php
/**
 * Taxonomy abilities — list/create/update/delete terms, set post terms.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder\Abilities;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Taxonomy_Abilities {

    public static function register(): void {
        wp_register_ability( 'np/list-terms', array(
            'label'       => 'List Terms',
            'description' => 'List terms of a taxonomy (default: category).',
            'category'    => 'np-taxonomy',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'taxonomy'   => array( 'type' => 'string', 'default' => 'category' ),
                    'hide_empty' => array( 'type' => 'boolean', 'default' => false ),
                    'search'     => array( 'type' => 'string' ),
                    'parent'     => array( 'type' => 'integer' ),
                    'per_page'   => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 200, 'default' => 100 ),
                ),
            ),
            'execute_callback' => static function ( array $input ) {
                $taxonomy = ! empty( $input['taxonomy'] ) ? sanitize_key( (string) $input['taxonomy'] ) : 'category';
                if ( ! taxonomy_exists( $taxonomy ) ) {
                    return new \WP_Error( 'np_bad_tax', "Taxonomy not found: $taxonomy", array( 'status' => 400 ) );
                }
                $args = array(
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => ! empty( $input['hide_empty'] ),
                    'number'     => isset( $input['per_page'] ) ? max( 1, min( 200, (int) $input['per_page'] ) ) : 100,
                );
                if ( ! empty( $input['search'] ) ) { $args['search'] = (string) $input['search']; }
                if ( isset( $input['parent'] ) )    { $args['parent'] = (int) $input['parent']; }
                $terms = get_terms( $args );
                if ( is_wp_error( $terms ) ) { return $terms; }
                $out = array();
                foreach ( $terms as $t ) {
                    $out[] = array(
                        'id'          => (int) $t->term_id,
                        'name'        => $t->name,
                        'slug'        => $t->slug,
                        'parent'      => (int) $t->parent,
                        'count'       => (int) $t->count,
                        'description' => $t->description,
                        'link'        => get_term_link( $t ),
                    );
                }
                return array( 'taxonomy' => $taxonomy, 'count' => count( $out ), 'terms' => $out );
            },
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/create-term', array(
            'label'       => 'Create Term',
            'description' => 'Create a new taxonomy term (e.g. category or tag).',
            'category'    => 'np-taxonomy',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'name' ),
                'properties' => array(
                    'taxonomy'    => array( 'type' => 'string', 'default' => 'category' ),
                    'name'        => array( 'type' => 'string' ),
                    'slug'        => array( 'type' => 'string' ),
                    'description' => array( 'type' => 'string' ),
                    'parent'      => array( 'type' => 'integer' ),
                ),
            ),
            'execute_callback' => static function ( array $input ) {
                $taxonomy = ! empty( $input['taxonomy'] ) ? sanitize_key( (string) $input['taxonomy'] ) : 'category';
                if ( ! taxonomy_exists( $taxonomy ) ) {
                    return new \WP_Error( 'np_bad_tax', "Taxonomy not found: $taxonomy", array( 'status' => 400 ) );
                }
                $name = trim( wp_strip_all_tags( (string) $input['name'] ) );
                if ( $name === '' ) { return new \WP_Error( 'np_bad_name', 'name is required', array( 'status' => 400 ) ); }
                $args = array();
                if ( ! empty( $input['slug'] ) )        { $args['slug']        = sanitize_title( (string) $input['slug'] ); }
                if ( ! empty( $input['description'] ) ) { $args['description'] = (string) $input['description']; }
                if ( isset( $input['parent'] ) )        { $args['parent']      = (int) $input['parent']; }
                $res = wp_insert_term( $name, $taxonomy, $args );
                if ( is_wp_error( $res ) ) { return $res; }
                $term = get_term( (int) $res['term_id'], $taxonomy );
                return array(
                    'id'       => (int) $term->term_id,
                    'name'     => $term->name,
                    'slug'     => $term->slug,
                    'taxonomy' => $taxonomy,
                    'link'     => get_term_link( $term ),
                );
            },
            'permission_callback' => static function () { return current_user_can( 'manage_categories' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/set-post-terms', array(
            'label'       => 'Set Post Terms',
            'description' => 'Assign terms to a post. Replaces by default; pass append=true to add. Accepts ids or names.',
            'category'    => 'np-taxonomy',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'post_id', 'terms' ),
                'properties' => array(
                    'post_id'  => array( 'type' => 'integer' ),
                    'taxonomy' => array( 'type' => 'string', 'default' => 'category' ),
                    'terms'    => array(
                        'type' => 'array',
                        'items' => array( 'type' => array( 'string', 'integer' ) ),
                    ),
                    'append'   => array( 'type' => 'boolean', 'default' => false ),
                ),
            ),
            'execute_callback' => static function ( array $input ) {
                $post_id = (int) $input['post_id'];
                $post    = get_post( $post_id );
                if ( ! $post ) { return new \WP_Error( 'np_no_post', 'Post not found', array( 'status' => 404 ) ); }
                $taxonomy = ! empty( $input['taxonomy'] ) ? sanitize_key( (string) $input['taxonomy'] ) : 'category';
                if ( ! taxonomy_exists( $taxonomy ) ) { return new \WP_Error( 'np_bad_tax', "Taxonomy not found: $taxonomy", array( 'status' => 400 ) ); }
                if ( ! current_user_can( 'edit_post', $post_id ) ) { return new \WP_Error( 'np_forbidden', 'No permission', array( 'status' => 403 ) ); }

                $raw = isset( $input['terms'] ) ? (array) $input['terms'] : array();
                $is_hier = is_taxonomy_hierarchical( $taxonomy );
                $resolved = array();
                foreach ( $raw as $t ) {
                    if ( is_int( $t ) || ( is_string( $t ) && ctype_digit( $t ) ) ) {
                        $resolved[] = (int) $t; continue;
                    }
                    $name = trim( (string) $t );
                    if ( $name === '' ) { continue; }
                    $term = get_term_by( 'name', $name, $taxonomy );
                    if ( ! $term ) { $term = get_term_by( 'slug', sanitize_title( $name ), $taxonomy ); }
                    if ( ! $term ) {
                        if ( $is_hier ) {
                            $r = wp_insert_term( $name, $taxonomy );
                            if ( is_wp_error( $r ) ) { return $r; }
                            $resolved[] = (int) $r['term_id'];
                        } else {
                            $resolved[] = $name;
                        }
                    } else {
                        $resolved[] = (int) $term->term_id;
                    }
                }
                $append = ! empty( $input['append'] );
                $res = wp_set_post_terms( $post_id, $resolved, $taxonomy, $append );
                if ( is_wp_error( $res ) ) { return $res; }
                $current = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'all' ) );
                $list = array();
                foreach ( $current as $t ) {
                    $list[] = array( 'id' => (int) $t->term_id, 'name' => $t->name, 'slug' => $t->slug );
                }
                return array( 'post_id' => $post_id, 'taxonomy' => $taxonomy, 'terms' => $list, 'append' => $append );
            },
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/update-term', array(
            'label'       => 'Update Term',
            'description' => 'Update a taxonomy term (rename, change slug/description/parent).',
            'category'    => 'np-taxonomy',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'term_id' ),
                'properties' => array(
                    'term_id'     => array( 'type' => 'integer' ),
                    'taxonomy'    => array( 'type' => 'string', 'default' => 'category' ),
                    'name'        => array( 'type' => 'string' ),
                    'slug'        => array( 'type' => 'string' ),
                    'description' => array( 'type' => 'string' ),
                    'parent'      => array( 'type' => 'integer' ),
                ),
            ),
            'execute_callback' => static function ( array $input ) {
                $taxonomy = ! empty( $input['taxonomy'] ) ? sanitize_key( (string) $input['taxonomy'] ) : 'category';
                $args = array();
                foreach ( array( 'name', 'slug', 'description' ) as $k ) {
                    if ( isset( $input[ $k ] ) ) { $args[ $k ] = (string) $input[ $k ]; }
                }
                if ( isset( $input['parent'] ) ) { $args['parent'] = (int) $input['parent']; }
                $res = wp_update_term( (int) $input['term_id'], $taxonomy, $args );
                if ( is_wp_error( $res ) ) { return $res; }
                $term = get_term( (int) $res['term_id'], $taxonomy );
                return array(
                    'id'       => (int) $term->term_id,
                    'name'     => $term->name,
                    'slug'     => $term->slug,
                    'taxonomy' => $taxonomy,
                );
            },
            'permission_callback' => static function () { return current_user_can( 'manage_categories' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/delete-term', array(
            'label'       => 'Delete Term',
            'description' => 'Delete a taxonomy term.',
            'category'    => 'np-taxonomy',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'term_id' ),
                'properties' => array(
                    'term_id'  => array( 'type' => 'integer' ),
                    'taxonomy' => array( 'type' => 'string', 'default' => 'category' ),
                ),
            ),
            'execute_callback' => static function ( array $input ) {
                $taxonomy = ! empty( $input['taxonomy'] ) ? sanitize_key( (string) $input['taxonomy'] ) : 'category';
                $res = wp_delete_term( (int) $input['term_id'], $taxonomy );
                if ( is_wp_error( $res ) ) { return $res; }
                return array( 'deleted' => (bool) $res, 'term_id' => (int) $input['term_id'], 'taxonomy' => $taxonomy );
            },
            'permission_callback' => static function () { return current_user_can( 'manage_categories' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );
    }
}
