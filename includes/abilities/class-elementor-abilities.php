<?php
/**
 * Elementor abilities — build-blog, append-sections, from-markdown.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder\Abilities;

use NP_MCP_Builder\Section_Builder;
use NP_MCP_Builder\Image_Generator;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Elementor_Abilities {

    public static function register(): void {
        wp_register_ability( 'np/elementor-build-blog', array(
            'label'       => 'Build Elementor Blog Post',
            'description' => 'Create or update a complete styled blog post (or page) built with Elementor from a friendly section schema. Supports hero, headings, paragraphs, images (including AI-generated), lists, quotes, dividers, CTAs, two-column rows, and raw HTML. Sets featured image, categories, tags, slug, status, Yoast focus keyword and meta description in one call.',
            'category'    => 'np-elementor',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'title', 'sections' ),
                'properties' => array(
                    'post_id'   => array( 'type' => 'integer' ),
                    'post_type' => array( 'type' => 'string', 'enum' => array( 'post', 'page' ), 'default' => 'post' ),
                    'title'     => array( 'type' => 'string' ),
                    'slug'      => array( 'type' => 'string' ),
                    'status'    => array( 'type' => 'string', 'enum' => array( 'publish', 'draft', 'pending', 'private' ), 'default' => 'publish' ),
                    'excerpt'   => array( 'type' => 'string' ),
                    'categories' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
                    'tags'      => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
                    'featured_image_id'      => array( 'type' => 'integer' ),
                    'featured_image_prompt'  => array( 'type' => 'string' ),
                    'featured_image_alt'     => array( 'type' => 'string' ),
                    'yoast_focus_keyword'    => array( 'type' => 'string' ),
                    'yoast_meta_description' => array( 'type' => 'string' ),
                    'sections' => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'build_blog' ),
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/elementor-append-sections', array(
            'label'       => 'Append Elementor Sections',
            'description' => 'Append (or prepend) styled sections to an existing Elementor post or page (preserves existing layout).',
            'category'    => 'np-elementor',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'post_id', 'sections' ),
                'properties' => array(
                    'post_id'  => array( 'type' => 'integer' ),
                    'sections' => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
                    'position' => array( 'type' => 'string', 'enum' => array( 'append', 'prepend' ), 'default' => 'append' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'append_sections' ),
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/elementor-from-markdown', array(
            'label'       => 'Build Elementor From Markdown',
            'description' => 'Convert a Markdown document into a styled Elementor blog post. Headings, paragraphs, lists, blockquotes, horizontal rules, and images become Elementor widgets.',
            'category'    => 'np-elementor',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'title', 'markdown' ),
                'properties' => array(
                    'post_id'   => array( 'type' => 'integer' ),
                    'post_type' => array( 'type' => 'string', 'enum' => array( 'post', 'page' ), 'default' => 'post' ),
                    'title'     => array( 'type' => 'string' ),
                    'slug'      => array( 'type' => 'string' ),
                    'status'    => array( 'type' => 'string', 'default' => 'publish' ),
                    'markdown'  => array( 'type' => 'string' ),
                    'add_hero'  => array( 'type' => 'boolean', 'default' => true ),
                    'hero_subtitle' => array( 'type' => 'string' ),
                    'categories' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
                    'tags'       => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
                    'featured_image_prompt'  => array( 'type' => 'string' ),
                    'featured_image_id'      => array( 'type' => 'integer' ),
                    'yoast_focus_keyword'    => array( 'type' => 'string' ),
                    'yoast_meta_description' => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'from_markdown' ),
            'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );
    }

    public static function build_blog( array $input ) {
        $post_type = isset( $input['post_type'] ) && in_array( $input['post_type'], array( 'post', 'page' ), true ) ? $input['post_type'] : 'post';
        $post_id   = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
        $status    = isset( $input['status'] ) ? sanitize_key( (string) $input['status'] ) : 'publish';

        $args = array(
            'post_title'  => sanitize_text_field( (string) $input['title'] ),
            'post_status' => $status,
            'post_type'   => $post_type,
        );
        if ( ! empty( $input['slug'] ) )    { $args['post_name']    = sanitize_title( (string) $input['slug'] ); }
        if ( ! empty( $input['excerpt'] ) ) { $args['post_excerpt'] = sanitize_textarea_field( (string) $input['excerpt'] ); }

        if ( $post_id > 0 ) {
            $args['ID'] = $post_id;
            $res = wp_update_post( $args, true );
        } else {
            $args['post_content'] = '';
            $res = wp_insert_post( $args, true );
        }
        if ( is_wp_error( $res ) ) { return $res; }
        $post_id = (int) $res;

        // Featured image: generate if requested.
        if ( empty( $input['featured_image_id'] ) && ! empty( $input['featured_image_prompt'] ) ) {
            $alt = ! empty( $input['featured_image_alt'] ) ? (string) $input['featured_image_alt'] : sanitize_text_field( (string) $input['title'] );
            $gen = Image_Generator::generate( array(
                'prompt'       => (string) $input['featured_image_prompt'],
                'alt'          => $alt,
                'title'        => (string) $input['title'],
                'aspect_ratio' => '16:9',
                'attach_to'    => $post_id,
                'set_featured' => true,
            ) );
            if ( ! is_wp_error( $gen ) && ! empty( $gen['attachment_id'] ) ) {
                $input['featured_image_id'] = (int) $gen['attachment_id'];
            }
        } elseif ( ! empty( $input['featured_image_id'] ) ) {
            set_post_thumbnail( $post_id, (int) $input['featured_image_id'] );
        }

        // Categories / tags via the np/set-post-terms ability if available.
        $set_terms = function ( $taxonomy, $terms ) use ( $post_id ) {
            if ( function_exists( 'wp_get_ability' ) ) {
                $a = wp_get_ability( 'np/set-post-terms' );
                if ( $a ) {
                    $a->execute( array(
                        'post_id'  => $post_id,
                        'taxonomy' => $taxonomy,
                        'terms'    => array_values( array_map( 'strval', $terms ) ),
                    ) );
                    return;
                }
            }
            // Fallback: direct.
            wp_set_post_terms( $post_id, array_values( array_map( 'strval', $terms ) ), $taxonomy, false );
        };
        if ( ! empty( $input['categories'] ) && is_array( $input['categories'] ) ) {
            $set_terms( 'category', $input['categories'] );
        }
        if ( ! empty( $input['tags'] ) && is_array( $input['tags'] ) ) {
            $set_terms( 'post_tag', $input['tags'] );
        }

        if ( ! empty( $input['yoast_focus_keyword'] ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_focuskw', sanitize_text_field( (string) $input['yoast_focus_keyword'] ) );
        }
        if ( ! empty( $input['yoast_meta_description'] ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_textarea_field( (string) $input['yoast_meta_description'] ) );
        }

        // Build Elementor data.
        $ctx = array( 'post_id' => $post_id, 'generated_attachments' => array() );
        $sections = isset( $input['sections'] ) && is_array( $input['sections'] ) ? $input['sections'] : array();
        $elementor = array();
        foreach ( $sections as $sec ) {
            $node = Section_Builder::build( (array) $sec, $ctx );
            if ( $node ) { $elementor[] = $node; }
        }

        update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
        update_post_meta( $post_id, '_elementor_template_type', 'wp-' . $post_type );
        update_post_meta( $post_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
        update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $elementor ) ) );

        delete_post_meta( $post_id, '_elementor_css' );
        if ( class_exists( '\Elementor\Plugin' ) ) {
            try { \Elementor\Plugin::$instance->files_manager->clear_cache(); } catch ( \Throwable $e ) { /* ignore */ }
        }

        return array(
            'post_id'          => $post_id,
            'edit_url'         => admin_url( 'post.php?post=' . $post_id . '&action=elementor' ),
            'view_url'         => get_permalink( $post_id ),
            'sections_built'   => count( $elementor ),
            'images_generated' => $ctx['generated_attachments'],
        );
    }

    public static function append_sections( array $input ) {
        $post_id = (int) $input['post_id'];
        if ( ! get_post( $post_id ) ) { return new \WP_Error( 'np_no_post', 'Post not found' ); }
        $existing_raw = get_post_meta( $post_id, '_elementor_data', true );
        $existing = array();
        if ( is_string( $existing_raw ) && $existing_raw !== '' ) {
            $decoded = json_decode( $existing_raw, true );
            if ( is_array( $decoded ) ) { $existing = $decoded; }
        }
        $ctx = array( 'post_id' => $post_id, 'generated_attachments' => array() );
        $new_nodes = array();
        foreach ( (array) $input['sections'] as $sec ) {
            $node = Section_Builder::build( (array) $sec, $ctx );
            if ( $node ) { $new_nodes[] = $node; }
        }
        $position = isset( $input['position'] ) && $input['position'] === 'prepend' ? 'prepend' : 'append';
        $merged = $position === 'prepend' ? array_merge( $new_nodes, $existing ) : array_merge( $existing, $new_nodes );

        update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
        if ( ! get_post_meta( $post_id, '_elementor_template_type', true ) ) {
            update_post_meta( $post_id, '_elementor_template_type', 'wp-' . get_post_type( $post_id ) );
        }
        update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $merged ) ) );
        delete_post_meta( $post_id, '_elementor_css' );

        return array(
            'post_id'          => $post_id,
            'sections_added'   => count( $new_nodes ),
            'total_sections'   => count( $merged ),
            'view_url'         => get_permalink( $post_id ),
            'edit_url'         => admin_url( 'post.php?post=' . $post_id . '&action=elementor' ),
            'images_generated' => $ctx['generated_attachments'],
        );
    }

    public static function from_markdown( array $input ) {
        $md = (string) $input['markdown'];
        $lines = preg_split( "/\r\n|\n|\r/", $md );
        $sections = array();
        if ( ! empty( $input['add_hero'] ) ) {
            $sections[] = array(
                'type' => 'hero',
                'title' => (string) $input['title'],
                'subtitle' => isset( $input['hero_subtitle'] ) ? (string) $input['hero_subtitle'] : '',
            );
        }

        $buf_para = '';
        $buf_list = array();
        $buf_list_style = 'bulleted';
        $flush_para = function () use ( &$buf_para, &$sections ) {
            if ( trim( $buf_para ) !== '' ) {
                $sections[] = array( 'type' => 'paragraph', 'text' => '<p>' . trim( $buf_para ) . '</p>' );
            }
            $buf_para = '';
        };
        $flush_list = function () use ( &$buf_list, &$buf_list_style, &$sections ) {
            if ( ! empty( $buf_list ) ) {
                $sections[] = array( 'type' => 'list', 'style' => $buf_list_style, 'items' => $buf_list );
                $buf_list = array();
            }
        };

        $in_quote = '';
        foreach ( $lines as $line ) {
            $trim = rtrim( $line );
            if ( $trim === '' ) {
                $flush_para(); $flush_list();
                if ( $in_quote !== '' ) {
                    $sections[] = array( 'type' => 'quote', 'text' => trim( $in_quote ) );
                    $in_quote = '';
                }
                continue;
            }
            if ( preg_match( '/^---+$/', $trim ) || preg_match( '/^\*\*\*+$/', $trim ) ) {
                $flush_para(); $flush_list();
                $sections[] = array( 'type' => 'divider' );
                continue;
            }
            if ( preg_match( '/^(#{1,6})\s+(.+)$/', $trim, $m ) ) {
                $flush_para(); $flush_list();
                $sections[] = array( 'type' => 'heading', 'level' => 'h' . strlen( $m[1] ), 'text' => $m[2] );
                continue;
            }
            if ( preg_match( '/^!\[([^\]]*)\]\(([^)]+)\)\s*$/', $trim, $m ) ) {
                $flush_para(); $flush_list();
                $sections[] = array( 'type' => 'image', 'url' => $m[2], 'caption' => $m[1] );
                continue;
            }
            if ( preg_match( '/^>\s?(.*)$/', $trim, $m ) ) {
                $flush_para(); $flush_list();
                $in_quote .= $m[1] . ' ';
                continue;
            }
            if ( preg_match( '/^[-*+]\s+(.+)$/', $trim, $m ) ) {
                $flush_para();
                $buf_list_style = 'bulleted';
                $buf_list[] = $m[1];
                continue;
            }
            if ( preg_match( '/^\d+\.\s+(.+)$/', $trim, $m ) ) {
                $flush_para();
                $buf_list_style = 'numbered';
                $buf_list[] = $m[1];
                continue;
            }
            $html = $trim;
            $html = preg_replace( '/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $html );
            $html = preg_replace( '/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $html );
            $html = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $html );
            $html = preg_replace_callback( '/\[([^\]]+)\]\(([^)]+)\)/', static function ( $m ) {
                return '<a href="' . esc_url( $m[2] ) . '">' . esc_html( $m[1] ) . '</a>';
            }, $html );
            $buf_para .= ( $buf_para === '' ? '' : ' ' ) . $html;
        }
        $flush_para(); $flush_list();
        if ( $in_quote !== '' ) { $sections[] = array( 'type' => 'quote', 'text' => trim( $in_quote ) ); }

        $payload = array(
            'post_id'   => isset( $input['post_id'] ) ? (int) $input['post_id'] : 0,
            'post_type' => isset( $input['post_type'] ) ? (string) $input['post_type'] : 'post',
            'title'     => (string) $input['title'],
            'status'    => isset( $input['status'] ) ? (string) $input['status'] : 'publish',
            'sections'  => $sections,
        );
        foreach ( array( 'slug', 'categories', 'tags', 'featured_image_id', 'featured_image_prompt', 'featured_image_alt', 'yoast_focus_keyword', 'yoast_meta_description' ) as $k ) {
            if ( isset( $input[ $k ] ) ) { $payload[ $k ] = $input[ $k ]; }
        }
        return self::build_blog( $payload );
    }
}
