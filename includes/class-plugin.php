<?php
/**
 * Main plugin class — registers categories, abilities, MCP tools and admin UI.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder;

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once NP_MCP_BUILDER_DIR . 'includes/class-license.php';
require_once NP_MCP_BUILDER_DIR . 'includes/class-image-generator.php';
require_once NP_MCP_BUILDER_DIR . 'includes/class-schema-builder.php';
require_once NP_MCP_BUILDER_DIR . 'includes/class-section-builder.php';
require_once NP_MCP_BUILDER_DIR . 'includes/abilities/class-content-abilities.php';
require_once NP_MCP_BUILDER_DIR . 'includes/abilities/class-image-abilities.php';
require_once NP_MCP_BUILDER_DIR . 'includes/abilities/class-taxonomy-abilities.php';
require_once NP_MCP_BUILDER_DIR . 'includes/abilities/class-theme-abilities.php';
require_once NP_MCP_BUILDER_DIR . 'includes/abilities/class-site-abilities.php';
require_once NP_MCP_BUILDER_DIR . 'includes/abilities/class-menu-abilities.php';
require_once NP_MCP_BUILDER_DIR . 'includes/abilities/class-user-abilities.php';
require_once NP_MCP_BUILDER_DIR . 'includes/abilities/class-seo-abilities.php';
require_once NP_MCP_BUILDER_DIR . 'includes/admin/class-settings.php';

class Plugin {

    /** @var Plugin|null */
    private static $instance = null;

    /**
     * Master list of all abilities the plugin can register.
     * Format: tool name => [ class short-name, group label, description ]
     */
    public const ABILITY_MAP = array(
        // Content
        'np/site-info'    => array( 'Content_Abilities', 'Content',  'Site info.' ),
        'np/list-posts'   => array( 'Content_Abilities', 'Content',  'List posts/pages.' ),
        'np/get-post'     => array( 'Content_Abilities', 'Content',  'Read a single post with Yoast meta.' ),
        'np/create-post'  => array( 'Content_Abilities', 'Content',  'Create post or page.' ),
        'np/update-post'  => array( 'Content_Abilities', 'Content',  'Update post fields and Yoast meta.' ),
        // Media
        'np/generate-image' => array( 'Image_Abilities', 'Media', 'Gemini → resize → WebP → Media Library.' ),
        // Taxonomy
        'np/list-terms'      => array( 'Taxonomy_Abilities', 'Taxonomy', 'List taxonomy terms.' ),
        'np/create-term'     => array( 'Taxonomy_Abilities', 'Taxonomy', 'Create term.' ),
        'np/update-term'     => array( 'Taxonomy_Abilities', 'Taxonomy', 'Rename / re-slug / re-parent a term.' ),
        'np/delete-term'     => array( 'Taxonomy_Abilities', 'Taxonomy', 'Delete a term.' ),
        'np/set-post-terms'  => array( 'Taxonomy_Abilities', 'Taxonomy', 'Assign terms to a post.' ),
        // Theme customizer
        'np/set-theme-mod' => array( 'Theme_Abilities', 'Theme', 'Set a Customizer value.' ),
        'np/get-theme-mod' => array( 'Theme_Abilities', 'Theme', 'Read a Customizer value.' ),
        // Site control
        'np/list-plugins'        => array( 'Site_Abilities', 'Site', 'List installed plugins.' ),
        'np/activate-plugin'     => array( 'Site_Abilities', 'Site', 'Activate a plugin.' ),
        'np/deactivate-plugin'   => array( 'Site_Abilities', 'Site', 'Deactivate a plugin.' ),
        'np/list-themes'         => array( 'Site_Abilities', 'Site', 'List installed themes.' ),
        'np/switch-theme'        => array( 'Site_Abilities', 'Site', 'Switch active theme.' ),
        'np/get-site-settings'   => array( 'Site_Abilities', 'Site', 'Read core site settings.' ),
        'np/update-site-settings'=> array( 'Site_Abilities', 'Site', 'Update core site settings.' ),
        'np/update-permalinks'   => array( 'Site_Abilities', 'Site', 'Update permalink structure.' ),
        'np/clear-cache'         => array( 'Site_Abilities', 'Site', 'Clear Elementor + WP caches.' ),
        'np/maintenance-mode'    => array( 'Site_Abilities', 'Site', 'Enable/disable maintenance page.' ),
        'np/system-info'         => array( 'Site_Abilities', 'Site', 'WordPress / PHP / MySQL system info.' ),
        // Menus
        'np/list-menus'           => array( 'Menu_Abilities', 'Menus', 'List nav menus and locations.' ),
        'np/create-menu'          => array( 'Menu_Abilities', 'Menus', 'Create a nav menu.' ),
        'np/update-menu'          => array( 'Menu_Abilities', 'Menus', 'Update items / locations of a menu.' ),
        'np/delete-menu'          => array( 'Menu_Abilities', 'Menus', 'Delete a nav menu.' ),
        'np/assign-menu-location' => array( 'Menu_Abilities', 'Menus', 'Assign menu to a theme location.' ),
        // Users
        'np/list-users'   => array( 'User_Abilities', 'Users', 'List users.' ),
        'np/create-user'  => array( 'User_Abilities', 'Users', 'Create user.' ),
        'np/update-user'  => array( 'User_Abilities', 'Users', 'Update user fields and role.' ),
        'np/delete-user'  => array( 'User_Abilities', 'Users', 'Delete user, optionally reassigning content.' ),
        // SEO + kit
        'np/get-yoast-global'    => array( 'Seo_Abilities', 'SEO',       'Read Yoast SEO global settings.' ),
        'np/update-yoast-global' => array( 'Seo_Abilities', 'SEO',       'Update Yoast SEO global settings.' ),
        'np/get-elementor-kit'   => array( 'Seo_Abilities', 'Elementor', 'Read Elementor kit globals.' ),
        'np/update-elementor-kit'=> array( 'Seo_Abilities', 'Elementor', 'Update Elementor kit globals.' ),
        'np/get-seo-head'        => array( 'Seo_Abilities', 'SEO',       'Get Yoast-rendered SEO head (HTML + JSON + schema graph) for a post or URL.' ),
        'np/audit-seo'           => array( 'Seo_Abilities', 'SEO',       'Audit posts/pages for missing focus keyword, meta description, OG image, schema, etc.' ),
    );

    public static function instance(): Plugin {
        if ( self::$instance === null ) { self::$instance = new self(); }
        return self::$instance;
    }

    private function __construct() {}

    public function init(): void {
        add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );
        add_action( 'wp_abilities_api_init',            array( $this, 'register_abilities' ) );
        add_filter( 'mcp_adapter_default_server_config', array( $this, 'register_mcp_tools' ) );

        if ( is_admin() ) {
            Admin\Settings::init();
        }

        add_filter( 'plugin_action_links_' . NP_MCP_BUILDER_BASENAME, array( $this, 'plugin_action_links' ) );

        add_action( 'wp_head',   array( $this, 'inject_landing_head' ), 20 );
        add_action( 'wp_footer', array( $this, 'inject_landing_footer' ), 20 );

        // Maintenance mode.
        add_action( 'template_redirect', array( $this, 'maybe_maintenance_mode' ), 0 );
    }

    /**
     * Returns the set of enabled ability tool names (defaults to all).
     */
    public static function enabled_abilities(): array {
        $opts     = (array) get_option( 'np_mcp_builder_options', array() );
        $disabled = isset( $opts['disabled_abilities'] ) && is_array( $opts['disabled_abilities'] )
            ? $opts['disabled_abilities'] : array();
        $all = array_keys( self::ABILITY_MAP );
        // License gate: if not Pro, restrict to free tier.
        if ( ! License::is_pro() ) {
            $all = array_values( array_intersect( $all, License::FREE_ABILITIES ) );
        }
        return array_values( array_diff( $all, $disabled ) );
    }

    public function inject_landing_head(): void {
        if ( ! is_singular() ) { return; }
        $post_id = (int) get_queried_object_id();
        if ( ! $post_id ) { return; }
        $schemas = get_post_meta( $post_id, '_np_mcp_schema_jsonld', true );
        if ( is_array( $schemas ) ) {
            foreach ( $schemas as $schema ) {
                if ( is_string( $schema ) ) {
                    $json = trim( $schema );
                } elseif ( is_array( $schema ) || is_object( $schema ) ) {
                    $json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
                } else {
                    $json = '';
                }
                if ( $json === '' ) { continue; }
                echo "<script type=\"application/ld+json\">" . $json . "</script>\n"; // phpcs:ignore
            }
        }
        $css = get_post_meta( $post_id, '_np_mcp_custom_css', true );
        if ( is_string( $css ) && $css !== '' ) {
            echo "<style id=\"np-mcp-custom-css\">" . wp_strip_all_tags( $css ) . "</style>\n"; // phpcs:ignore
        }
    }

    public function inject_landing_footer(): void {
        if ( ! is_singular() ) { return; }
        $post_id = (int) get_queried_object_id();
        if ( ! $post_id ) { return; }
        $js = get_post_meta( $post_id, '_np_mcp_custom_js', true );
        if ( is_string( $js ) && $js !== '' ) {
            echo "<script id=\"np-mcp-custom-js\">(function(){" . $js . "})();</script>\n"; // phpcs:ignore
        }
    }

    public function maybe_maintenance_mode(): void {
        $opts = (array) get_option( 'np_mcp_builder_options', array() );
        if ( empty( $opts['maintenance_enabled'] ) ) { return; }
        if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) { return; }
        if ( defined( 'WP_CLI' ) && WP_CLI ) { return; }
        $uri = isset( $_SERVER['REQUEST_URI'] )
            ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) )
            : '';
        if ( strpos( $uri, '/wp-login' ) !== false ) { return; }
        if ( strpos( $uri, '/wp-admin' ) !== false ) { return; }
        nocache_headers();
        status_header( 503 );
        header( 'Retry-After: 3600' );
        $title   = (string) ( $opts['maintenance_title']   ?? __( 'We will be right back', 'np-mcp-builder' ) );
        $message = (string) ( $opts['maintenance_message'] ?? __( 'The site is undergoing scheduled maintenance. Please check back shortly.', 'np-mcp-builder' ) );
        $title   = wp_strip_all_tags( $title );
        echo "<!doctype html><html><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>" . esc_html( $title ) . "</title>";
        echo "<style>body{font-family:-apple-system,Segoe UI,Roboto,sans-serif;background:#0F172A;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:24px;text-align:center}h1{font-size:clamp(28px,4vw,48px);margin:0 0 16px}p{font-size:18px;opacity:.85;max-width:560px}</style></head><body><div><h1>" . esc_html( $title ) . "</h1><p>" . wp_kses_post( $message ) . "</p></div></body></html>";
        exit;
    }

    public function register_categories(): void {
        if ( ! function_exists( 'wp_register_ability_category' ) ) { return; }
        $cats = array(
            'np-site'      => array( 'label' => __( 'NP Site', 'np-mcp-builder' ),      'description' => __( 'Site-level operations: plugins, themes, settings, cache, maintenance.', 'np-mcp-builder' ) ),
            'np-content'   => array( 'label' => __( 'NP Content', 'np-mcp-builder' ),   'description' => __( 'Content CRUD operations.', 'np-mcp-builder' ) ),
            'np-media'     => array( 'label' => __( 'NP Media', 'np-mcp-builder' ),     'description' => __( 'Media library and AI image generation.', 'np-mcp-builder' ) ),
            'np-taxonomy'  => array( 'label' => __( 'NP Taxonomy', 'np-mcp-builder' ),  'description' => __( 'Categories, tags, and custom taxonomies.', 'np-mcp-builder' ) ),
            'np-theme'     => array( 'label' => __( 'NP Theme', 'np-mcp-builder' ),     'description' => __( 'Theme customizer operations.', 'np-mcp-builder' ) ),
            'np-elementor' => array( 'label' => __( 'NP Elementor', 'np-mcp-builder' ), 'description' => __( 'Mega Elementor blog/landing builders + global kit.', 'np-mcp-builder' ) ),
            'np-menu'      => array( 'label' => __( 'NP Menus', 'np-mcp-builder' ),     'description' => __( 'Nav menus and theme locations.', 'np-mcp-builder' ) ),
            'np-users'     => array( 'label' => __( 'NP Users', 'np-mcp-builder' ),     'description' => __( 'User management.', 'np-mcp-builder' ) ),
            'np-seo'       => array( 'label' => __( 'NP SEO', 'np-mcp-builder' ),       'description' => __( 'Yoast SEO global settings.', 'np-mcp-builder' ) ),
        );
        foreach ( $cats as $slug => $args ) {
            wp_register_ability_category( $slug, $args );
        }
    }

    public function register_abilities(): void {
        if ( ! function_exists( 'wp_register_ability' ) ) { return; }
        // Always register all classes (each guards its own tools); we then
        // unregister disabled tools after the fact.
        $classes = array_unique( array_map( static fn( $r ) => $r[0], self::ABILITY_MAP ) );
        foreach ( $classes as $class ) {
            $fqcn = "NP_MCP_Builder\\Abilities\\{$class}";
            if ( class_exists( $fqcn ) ) {
                call_user_func( array( $fqcn, 'register' ) );
            }
        }
        $disabled = array_diff( array_keys( self::ABILITY_MAP ), self::enabled_abilities() );
        if ( $disabled && function_exists( 'wp_get_ability' ) && function_exists( 'wp_unregister_ability' ) ) {
            foreach ( $disabled as $tool ) {
                if ( wp_get_ability( $tool ) ) {
                    wp_unregister_ability( $tool );
                }
            }
        }
    }

    public function register_mcp_tools( $config ) {
        $tools = self::enabled_abilities();
        $config['tools'] = array_values( array_unique( array_merge( (array) ( $config['tools'] ?? array() ), $tools ) ) );
        return $config;
    }

    public function plugin_action_links( array $links ): array {
        $settings = '<a href="' . esc_url( admin_url( 'admin.php?page=np-mcp-builder' ) ) . '">' . esc_html__( 'Dashboard', 'np-mcp-builder' ) . '</a>';
        array_unshift( $links, $settings );
        return $links;
    }

    public static function get_option( string $key, $default = '' ) {
        $opts = get_option( 'np_mcp_builder_options', array() );
        return $opts[ $key ] ?? $default;
    }
}
