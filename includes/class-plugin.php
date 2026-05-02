<?php
/**
 * Main plugin class — registers categories, abilities, MCP tools and admin UI.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder;

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once NP_MCP_BUILDER_DIR . 'includes/class-image-generator.php';
require_once NP_MCP_BUILDER_DIR . 'includes/class-section-builder.php';
require_once NP_MCP_BUILDER_DIR . 'includes/abilities/class-content-abilities.php';
require_once NP_MCP_BUILDER_DIR . 'includes/abilities/class-image-abilities.php';
require_once NP_MCP_BUILDER_DIR . 'includes/abilities/class-taxonomy-abilities.php';
require_once NP_MCP_BUILDER_DIR . 'includes/abilities/class-theme-abilities.php';
require_once NP_MCP_BUILDER_DIR . 'includes/abilities/class-elementor-abilities.php';
require_once NP_MCP_BUILDER_DIR . 'includes/admin/class-settings.php';

class Plugin {

    /** @var Plugin|null */
    private static $instance = null;

    public static function instance(): Plugin {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init(): void {
        // 1. Categories — hooked on the dedicated categories action (WP 6.9+).
        add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );

        // 2. Abilities — hooked on wp_abilities_api_init.
        add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );

        // 3. MCP server config — expose all tools via the default mcp-adapter server.
        add_filter( 'mcp_adapter_default_server_config', array( $this, 'register_mcp_tools' ) );

        // 4. Admin settings page (only when in wp-admin).
        if ( is_admin() ) {
            Admin\Settings::init();
        }

        // 5. Plugin row meta links.
        add_filter( 'plugin_action_links_' . NP_MCP_BUILDER_BASENAME, array( $this, 'plugin_action_links' ) );
    }

    public function register_categories(): void {
        if ( ! function_exists( 'wp_register_ability_category' ) ) {
            return;
        }
        $cats = array(
            'np-site'      => array( 'label' => __( 'NP Site', 'np-mcp-builder' ),      'description' => __( 'Site-level read operations.', 'np-mcp-builder' ) ),
            'np-content'   => array( 'label' => __( 'NP Content', 'np-mcp-builder' ),   'description' => __( 'Content CRUD operations.', 'np-mcp-builder' ) ),
            'np-media'     => array( 'label' => __( 'NP Media', 'np-mcp-builder' ),     'description' => __( 'Media library and AI image generation.', 'np-mcp-builder' ) ),
            'np-taxonomy'  => array( 'label' => __( 'NP Taxonomy', 'np-mcp-builder' ),  'description' => __( 'Categories, tags, and custom taxonomies.', 'np-mcp-builder' ) ),
            'np-theme'     => array( 'label' => __( 'NP Theme', 'np-mcp-builder' ),     'description' => __( 'Theme customizer operations.', 'np-mcp-builder' ) ),
            'np-elementor' => array( 'label' => __( 'NP Elementor', 'np-mcp-builder' ), 'description' => __( 'Mega Elementor blog/page builders.', 'np-mcp-builder' ) ),
        );
        foreach ( $cats as $slug => $args ) {
            wp_register_ability_category( $slug, $args );
        }
    }

    public function register_abilities(): void {
        if ( ! function_exists( 'wp_register_ability' ) ) {
            return;
        }
        Abilities\Content_Abilities::register();
        Abilities\Image_Abilities::register();
        Abilities\Taxonomy_Abilities::register();
        Abilities\Theme_Abilities::register();
        Abilities\Elementor_Abilities::register();
    }

    public function register_mcp_tools( $config ) {
        $tools = array(
            // Content
            'np/site-info', 'np/list-posts', 'np/get-post', 'np/create-post', 'np/update-post',
            // Media
            'np/generate-image',
            // Taxonomy
            'np/list-terms', 'np/create-term', 'np/update-term', 'np/delete-term', 'np/set-post-terms',
            // Theme
            'np/set-theme-mod', 'np/get-theme-mod',
            // Elementor mega
            'np/elementor-build-blog', 'np/elementor-append-sections', 'np/elementor-from-markdown',
        );
        $config['tools'] = array_values( array_unique( array_merge( (array) ( $config['tools'] ?? array() ), $tools ) ) );
        return $config;
    }

    public function plugin_action_links( array $links ): array {
        $settings = '<a href="' . esc_url( admin_url( 'options-general.php?page=np-mcp-builder' ) ) . '">' . esc_html__( 'Settings', 'np-mcp-builder' ) . '</a>';
        array_unshift( $links, $settings );
        return $links;
    }

    /**
     * Helper: get a plugin option with default fallback.
     */
    public static function get_option( string $key, $default = '' ) {
        $opts = get_option( 'np_mcp_builder_options', array() );
        return $opts[ $key ] ?? $default;
    }
}
