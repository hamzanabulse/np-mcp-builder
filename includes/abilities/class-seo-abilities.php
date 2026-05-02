<?php
/**
 * Seo_Abilities — Yoast SEO global settings + Elementor global kit.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder\Abilities;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Seo_Abilities {

    public static function register(): void {
        if ( ! function_exists( 'wp_register_ability' ) ) { return; }

        // ---------- Yoast global ----------
        wp_register_ability( 'np/get-yoast-global', array(
            'label' => 'Get Yoast SEO global settings', 'category' => 'np-seo',
            'description' => 'Read Yoast SEO global options: titles & metas, social profiles, organization/person, breadcrumbs.',
            'input_schema'  => array( 'type' => 'object', 'properties' => array() ),
            'execute_callback'    => array( __CLASS__, 'get_yoast_global' ),
            'permission_callback' => static function () { return current_user_can( 'manage_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/update-yoast-global', array(
            'label' => 'Update Yoast SEO global settings', 'category' => 'np-seo',
            'description' => 'Update Yoast SEO global options: company/person, social URLs, default OG image, breadcrumbs, sitemap toggle.',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'company_or_person'      => array( 'type' => 'string', 'enum' => array( 'company', 'person' ) ),
                    'company_name'           => array( 'type' => 'string' ),
                    'company_logo'           => array( 'type' => 'string' ),
                    'company_logo_id'        => array( 'type' => 'integer' ),
                    'person_name'            => array( 'type' => 'string' ),
                    'person_logo'            => array( 'type' => 'string' ),
                    'person_logo_id'         => array( 'type' => 'integer' ),
                    'website_name'           => array( 'type' => 'string' ),
                    'alternate_website_name' => array( 'type' => 'string' ),
                    'open_graph_default_image'    => array( 'type' => 'string' ),
                    'open_graph_default_image_id' => array( 'type' => 'integer' ),
                    'social' => array(
                        'type' => 'object',
                        'properties' => array(
                            'facebook_site' => array( 'type' => 'string' ),
                            'twitter_site'  => array( 'type' => 'string' ),
                            'instagram_url' => array( 'type' => 'string' ),
                            'linkedin_url'  => array( 'type' => 'string' ),
                            'youtube_url'   => array( 'type' => 'string' ),
                            'pinterest_url' => array( 'type' => 'string' ),
                            'wikipedia_url' => array( 'type' => 'string' ),
                            'other_social_urls' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
                        ),
                    ),
                    'breadcrumbs_enable' => array( 'type' => 'boolean' ),
                    'enable_xml_sitemap' => array( 'type' => 'boolean' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'update_yoast_global' ),
            'permission_callback' => static function () { return current_user_can( 'manage_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        // ---------- Elementor kit ----------
        wp_register_ability( 'np/get-elementor-kit', array(
            'label' => 'Get Elementor global kit', 'category' => 'np-elementor',
            'description' => 'Read the active Elementor kit: system colors, custom colors, system typography, custom typography, default container settings.',
            'input_schema'  => array( 'type' => 'object', 'properties' => array() ),
            'execute_callback'    => array( __CLASS__, 'get_elementor_kit' ),
            'permission_callback' => static function () { return current_user_can( 'edit_theme_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/update-elementor-kit', array(
            'label' => 'Update Elementor global kit', 'category' => 'np-elementor',
            'description' => 'Update the active Elementor kit (global colors and typography). Pass an object whose keys are kit settings (e.g. system_colors, custom_colors, system_typography, body_typography, h1_typography, container_width).',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'settings' => array( 'type' => 'object' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'update_elementor_kit' ),
            'permission_callback' => static function () { return current_user_can( 'edit_theme_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );
    }

    /* ==================================================================== */

    public static function get_yoast_global(): array {
        if ( ! defined( 'WPSEO_VERSION' ) ) {
            return array( 'yoast_installed' => false );
        }
        $titles  = (array) get_option( 'wpseo_titles', array() );
        $social  = (array) get_option( 'wpseo_social', array() );
        $main    = (array) get_option( 'wpseo', array() );
        $sitemap = (array) get_option( 'wpseo_sitemap', array() );
        return array(
            'yoast_installed' => true,
            'website_name'    => $titles['website_name'] ?? '',
            'company_or_person' => $titles['company_or_person'] ?? '',
            'company_name'    => $titles['company_name'] ?? '',
            'company_logo'    => $titles['company_logo'] ?? '',
            'person_name'     => $titles['person_name'] ?? '',
            'breadcrumbs_enable' => ! empty( $titles['breadcrumbs-enable'] ),
            'open_graph_default_image' => $titles['open_graph_default_image'] ?? '',
            'social' => array(
                'facebook_site' => $social['facebook_site'] ?? '',
                'twitter_site'  => $social['twitter_site']  ?? '',
                'instagram_url' => $social['instagram_url'] ?? '',
                'linkedin_url'  => $social['linkedin_url']  ?? '',
                'youtube_url'   => $social['youtube_url']   ?? '',
                'pinterest_url' => $social['pinterest_url'] ?? '',
                'wikipedia_url' => $social['wikipedia_url'] ?? '',
                'other_social_urls' => $social['other_social_urls'] ?? array(),
            ),
            'enable_xml_sitemap' => ! empty( $main['enable_xml_sitemap'] ),
        );
    }

    public static function update_yoast_global( array $input ): array {
        if ( ! defined( 'WPSEO_VERSION' ) ) {
            return array( 'yoast_installed' => false );
        }
        $titles = (array) get_option( 'wpseo_titles', array() );
        $social = (array) get_option( 'wpseo_social', array() );
        $main   = (array) get_option( 'wpseo', array() );

        $title_keys = array(
            'company_or_person', 'company_name', 'company_logo',
            'person_name', 'person_logo',
            'website_name', 'alternate_website_name',
            'open_graph_default_image',
        );
        foreach ( $title_keys as $k ) {
            if ( array_key_exists( $k, $input ) ) {
                $titles[ $k ] = sanitize_text_field( (string) $input[ $k ] );
            }
        }
        if ( isset( $input['company_logo_id'] ) ) {
            $titles['company_logo_id']    = (int) $input['company_logo_id'];
            $titles['company_logo_meta']  = (string) wp_get_attachment_image_url( (int) $input['company_logo_id'], 'full' );
        }
        if ( isset( $input['person_logo_id'] ) ) {
            $titles['person_logo_id']     = (int) $input['person_logo_id'];
            $titles['person_logo_meta']   = (string) wp_get_attachment_image_url( (int) $input['person_logo_id'], 'full' );
        }
        if ( isset( $input['open_graph_default_image_id'] ) ) {
            $titles['open_graph_default_image_id']   = (int) $input['open_graph_default_image_id'];
            $titles['open_graph_default_image_meta'] = (string) wp_get_attachment_image_url( (int) $input['open_graph_default_image_id'], 'full' );
        }
        if ( isset( $input['breadcrumbs_enable'] ) ) {
            $titles['breadcrumbs-enable'] = (bool) $input['breadcrumbs_enable'];
        }

        if ( ! empty( $input['social'] ) && is_array( $input['social'] ) ) {
            $social_keys = array( 'facebook_site', 'twitter_site', 'instagram_url', 'linkedin_url', 'youtube_url', 'pinterest_url', 'wikipedia_url' );
            foreach ( $social_keys as $k ) {
                if ( array_key_exists( $k, $input['social'] ) ) {
                    $val = (string) $input['social'][ $k ];
                    $social[ $k ] = ( strpos( $val, 'http' ) === 0 ) ? esc_url_raw( $val ) : sanitize_text_field( $val );
                }
            }
            if ( isset( $input['social']['other_social_urls'] ) ) {
                $social['other_social_urls'] = array_values( array_filter( array_map( 'esc_url_raw', (array) $input['social']['other_social_urls'] ) ) );
            }
        }

        if ( isset( $input['enable_xml_sitemap'] ) ) {
            $main['enable_xml_sitemap'] = (bool) $input['enable_xml_sitemap'];
        }

        update_option( 'wpseo_titles', $titles );
        update_option( 'wpseo_social', $social );
        update_option( 'wpseo', $main );
        return array( 'updated' => true );
    }

    public static function get_elementor_kit(): array {
        if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
            return array( 'elementor_installed' => false );
        }
        $kit_id = (int) get_option( 'elementor_active_kit', 0 );
        if ( ! $kit_id ) { return array( 'elementor_installed' => true, 'kit_id' => 0 ); }
        $data = get_post_meta( $kit_id, '_elementor_page_settings', true );
        return array(
            'elementor_installed' => true,
            'kit_id'   => $kit_id,
            'settings' => is_array( $data ) ? $data : array(),
        );
    }

    public static function update_elementor_kit( array $input ) {
        if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
            return new \WP_Error( 'np_no_elementor', 'Elementor is not installed.' );
        }
        $kit_id = (int) get_option( 'elementor_active_kit', 0 );
        if ( ! $kit_id ) { return new \WP_Error( 'np_no_kit', 'No active Elementor kit.' ); }
        $current = get_post_meta( $kit_id, '_elementor_page_settings', true );
        if ( ! is_array( $current ) ) { $current = array(); }
        $patch = (array) ( $input['settings'] ?? array() );
        $merged = array_replace_recursive( $current, $patch );
        update_post_meta( $kit_id, '_elementor_page_settings', $merged );
        // Invalidate Elementor file cache so new globals are written into CSS files.
        try {
            \Elementor\Plugin::instance()->files_manager->clear_cache();
        } catch ( \Throwable $e ) { /* noop */ }
        return array( 'kit_id' => $kit_id, 'settings' => $merged );
    }
}
