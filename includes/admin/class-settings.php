<?php
/**
 * Settings — admin UI under Settings → NP MCP Builder.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder\Admin;

use NP_MCP_Builder\Plugin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Settings {

    public const OPTION_GROUP = 'np_mcp_builder_group';
    public const OPTION_NAME  = 'np_mcp_builder_options';
    public const PAGE_SLUG    = 'np-mcp-builder';

    public static function init(): void {
        add_action( 'admin_menu',  array( __CLASS__, 'menu' ) );
        add_action( 'admin_init',  array( __CLASS__, 'register' ) );
    }

    public static function menu(): void {
        add_options_page(
            __( 'NP MCP Builder', 'np-mcp-builder' ),
            __( 'NP MCP Builder', 'np-mcp-builder' ),
            'manage_options',
            self::PAGE_SLUG,
            array( __CLASS__, 'render' )
        );
    }

    public static function register(): void {
        register_setting( self::OPTION_GROUP, self::OPTION_NAME, array(
            'type'              => 'array',
            'sanitize_callback' => array( __CLASS__, 'sanitize' ),
            'default'           => array(),
        ) );

        add_settings_section( 'np_mcp_builder_general', __( 'General', 'np-mcp-builder' ), '__return_false', self::PAGE_SLUG );

        add_settings_field( 'gemini_api_key', __( 'Gemini API key', 'np-mcp-builder' ),
            array( __CLASS__, 'field_text' ), self::PAGE_SLUG, 'np_mcp_builder_general',
            array( 'key' => 'gemini_api_key', 'type' => 'password',
                'desc' => __( 'Used by np/generate-image (model gemini-2.5-flash-image). Get one at aistudio.google.com.', 'np-mcp-builder' ) )
        );

        add_settings_field( 'default_aspect_ratio', __( 'Default aspect ratio', 'np-mcp-builder' ),
            array( __CLASS__, 'field_select' ), self::PAGE_SLUG, 'np_mcp_builder_general',
            array( 'key' => 'default_aspect_ratio', 'options' => array( '1:1', '2:3', '3:2', '3:4', '4:3', '4:5', '5:4', '9:16', '16:9', '21:9' ), 'default' => '16:9' )
        );

        add_settings_field( 'default_max_width', __( 'Default max width (px)', 'np-mcp-builder' ),
            array( __CLASS__, 'field_text' ), self::PAGE_SLUG, 'np_mcp_builder_general',
            array( 'key' => 'default_max_width', 'type' => 'number', 'default' => 1280, 'desc' => '256–2048' )
        );

        add_settings_field( 'default_quality', __( 'Default WebP quality', 'np-mcp-builder' ),
            array( __CLASS__, 'field_text' ), self::PAGE_SLUG, 'np_mcp_builder_general',
            array( 'key' => 'default_quality', 'type' => 'number', 'default' => 78, 'desc' => '40–95' )
        );
    }

    public static function sanitize( $input ): array {
        $clean = is_array( $input ) ? $input : array();
        if ( isset( $clean['gemini_api_key'] ) )       { $clean['gemini_api_key'] = trim( (string) $clean['gemini_api_key'] ); }
        if ( isset( $clean['default_aspect_ratio'] ) ) { $clean['default_aspect_ratio'] = sanitize_text_field( (string) $clean['default_aspect_ratio'] ); }
        if ( isset( $clean['default_max_width'] ) )    { $clean['default_max_width'] = max( 256, min( 2048, (int) $clean['default_max_width'] ) ); }
        if ( isset( $clean['default_quality'] ) )      { $clean['default_quality']   = max( 40, min( 95, (int) $clean['default_quality'] ) ); }
        return $clean;
    }

    public static function field_text( array $args ): void {
        $opts = get_option( self::OPTION_NAME, array() );
        $key  = $args['key'];
        $val  = isset( $opts[ $key ] ) ? $opts[ $key ] : ( $args['default'] ?? '' );
        $type = $args['type'] ?? 'text';
        printf(
            '<input type="%1$s" class="regular-text" name="%2$s[%3$s]" value="%4$s" autocomplete="off" />',
            esc_attr( $type ),
            esc_attr( self::OPTION_NAME ),
            esc_attr( $key ),
            esc_attr( (string) $val )
        );
        if ( ! empty( $args['desc'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['desc'] ) );
        }
    }

    public static function field_select( array $args ): void {
        $opts = get_option( self::OPTION_NAME, array() );
        $key  = $args['key'];
        $val  = isset( $opts[ $key ] ) ? $opts[ $key ] : ( $args['default'] ?? '' );
        printf( '<select name="%1$s[%2$s]">', esc_attr( self::OPTION_NAME ), esc_attr( $key ) );
        foreach ( (array) $args['options'] as $opt ) {
            printf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( $opt ), selected( $val, $opt, false ) );
        }
        echo '</select>';
    }

    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $rest_url = rest_url( 'wp/v2/' );
        $mcp_url  = function_exists( 'rest_url' ) ? rest_url( 'mcp/v1/' ) : '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'NP MCP Builder', 'np-mcp-builder' ); ?></h1>
            <p><?php esc_html_e( 'Exposes 16 high-level WordPress abilities (content, taxonomy, theme, AI image generation, and Elementor blog builders) to MCP clients such as Claude Desktop.', 'np-mcp-builder' ); ?></p>

            <form action="options.php" method="post">
                <?php
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( self::PAGE_SLUG );
                submit_button();
                ?>
            </form>

            <hr>
            <h2><?php esc_html_e( 'Endpoints', 'np-mcp-builder' ); ?></h2>
            <p><strong><?php esc_html_e( 'REST base:', 'np-mcp-builder' ); ?></strong> <code><?php echo esc_html( $rest_url ); ?></code></p>
            <?php if ( $mcp_url ) : ?>
                <p><strong><?php esc_html_e( 'MCP server (with mcp-adapter plugin):', 'np-mcp-builder' ); ?></strong> <code><?php echo esc_html( $mcp_url ); ?></code></p>
            <?php endif; ?>

            <h2><?php esc_html_e( 'Registered abilities', 'np-mcp-builder' ); ?></h2>
            <ul style="columns:2;-webkit-columns:2;">
                <?php
                foreach ( array(
                    'np/site-info', 'np/list-posts', 'np/get-post', 'np/create-post', 'np/update-post',
                    'np/generate-image',
                    'np/list-terms', 'np/create-term', 'np/update-term', 'np/delete-term', 'np/set-post-terms',
                    'np/set-theme-mod', 'np/get-theme-mod',
                    'np/elementor-build-blog', 'np/elementor-append-sections', 'np/elementor-from-markdown',
                ) as $name ) {
                    echo '<li><code>' . esc_html( $name ) . '</code></li>';
                }
                ?>
            </ul>
        </div>
        <?php
    }
}
