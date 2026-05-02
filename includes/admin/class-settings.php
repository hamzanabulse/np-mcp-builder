<?php
/**
 * Settings — top-level NP MCP Builder dashboard with tabs:
 * Overview, Abilities, Tools, Settings, Maintenance, About.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder\Admin;

use NP_MCP_Builder\Plugin;
use NP_MCP_Builder\Abilities\Site_Abilities;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Settings {

    public const OPTION_GROUP = 'np_mcp_builder_group';
    public const OPTION_NAME  = 'np_mcp_builder_options';
    public const PAGE_SLUG    = 'np-mcp-builder';

    public static function init(): void {
        add_action( 'admin_menu',          array( __CLASS__, 'menu' ) );
        add_action( 'admin_init',          array( __CLASS__, 'register' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
        add_action( 'admin_post_np_mcp_clear_cache',         array( __CLASS__, 'handle_clear_cache' ) );
        add_action( 'admin_post_np_mcp_toggle_maintenance',  array( __CLASS__, 'handle_toggle_maintenance' ) );
        add_action( 'admin_notices',       array( __CLASS__, 'maintenance_notice' ) );
        add_filter( 'pre_update_option_' . self::OPTION_NAME, array( __CLASS__, 'pre_update_compute_disabled' ), 10, 2 );
    }

    public static function menu(): void {
        // Top-level menu so the dashboard is one click away.
        $cap = 'manage_options';
        add_menu_page(
            __( 'NP MCP Builder', 'np-mcp-builder' ),
            __( 'NP MCP', 'np-mcp-builder' ),
            $cap,
            self::PAGE_SLUG,
            array( __CLASS__, 'render' ),
            'dashicons-superhero',
            58
        );
        add_submenu_page( self::PAGE_SLUG, __( 'Overview', 'np-mcp-builder' ),     __( 'Overview', 'np-mcp-builder' ),     $cap, self::PAGE_SLUG, array( __CLASS__, 'render' ) );
        add_submenu_page( self::PAGE_SLUG, __( 'Abilities', 'np-mcp-builder' ),    __( 'Abilities', 'np-mcp-builder' ),    $cap, self::PAGE_SLUG . '&tab=abilities',   array( __CLASS__, 'render' ) );
        add_submenu_page( self::PAGE_SLUG, __( 'Tools', 'np-mcp-builder' ),        __( 'Tools', 'np-mcp-builder' ),        $cap, self::PAGE_SLUG . '&tab=tools',       array( __CLASS__, 'render' ) );
        add_submenu_page( self::PAGE_SLUG, __( 'Settings', 'np-mcp-builder' ),     __( 'Settings', 'np-mcp-builder' ),     $cap, self::PAGE_SLUG . '&tab=settings',    array( __CLASS__, 'render' ) );
        add_submenu_page( self::PAGE_SLUG, __( 'Maintenance', 'np-mcp-builder' ),  __( 'Maintenance', 'np-mcp-builder' ),  $cap, self::PAGE_SLUG . '&tab=maintenance', array( __CLASS__, 'render' ) );
        add_submenu_page( self::PAGE_SLUG, __( 'About', 'np-mcp-builder' ),        __( 'About', 'np-mcp-builder' ),        $cap, self::PAGE_SLUG . '&tab=about',       array( __CLASS__, 'render' ) );
    }

    public static function register(): void {
        register_setting( self::OPTION_GROUP, self::OPTION_NAME, array(
            'type'              => 'array',
            'sanitize_callback' => array( __CLASS__, 'sanitize' ),
            'default'           => array(),
        ) );
    }

    public static function sanitize( $input ): array {
        $existing = (array) get_option( self::OPTION_NAME, array() );
        $clean    = is_array( $input ) ? $input : array();

        // Merge sub-tab payloads into the existing option so other tabs are not wiped.
        $merged = $existing;

        if ( array_key_exists( 'gemini_api_key', $clean ) )       { $merged['gemini_api_key']       = trim( (string) $clean['gemini_api_key'] ); }
        if ( array_key_exists( 'default_aspect_ratio', $clean ) ) { $merged['default_aspect_ratio'] = sanitize_text_field( (string) $clean['default_aspect_ratio'] ); }
        if ( array_key_exists( 'default_max_width', $clean ) )    { $merged['default_max_width']    = max( 256, min( 2048, (int) $clean['default_max_width'] ) ); }
        if ( array_key_exists( 'default_quality', $clean ) )      { $merged['default_quality']      = max( 40, min( 95, (int) $clean['default_quality'] ) ); }

        if ( array_key_exists( 'maintenance_enabled', $clean ) )  { $merged['maintenance_enabled']  = ! empty( $clean['maintenance_enabled'] ); }
        if ( array_key_exists( 'maintenance_title', $clean ) )    { $merged['maintenance_title']    = sanitize_text_field( (string) $clean['maintenance_title'] ); }
        if ( array_key_exists( 'maintenance_message', $clean ) )  { $merged['maintenance_message']  = wp_kses_post( (string) $clean['maintenance_message'] ); }

        if ( array_key_exists( 'disabled_abilities', $clean ) ) {
            $valid = array_keys( Plugin::ABILITY_MAP );
            $merged['disabled_abilities'] = array_values( array_intersect( (array) $clean['disabled_abilities'], $valid ) );
        }

        return $merged;
    }

    public static function assets( $hook ): void {
        if ( strpos( (string) $hook, self::PAGE_SLUG ) === false ) { return; }
        $css = '
        .np-mcp-wrap{max-width:1200px}
        .np-mcp-hero{background:linear-gradient(135deg,#0F172A 0%,#1E3A8A 100%);color:#fff;padding:32px;border-radius:14px;margin:16px 0 24px;box-shadow:0 12px 32px rgba(15,23,42,.18)}
        .np-mcp-hero h1{color:#fff;font-size:28px;margin:0 0 8px;display:flex;align-items:center;gap:12px}
        .np-mcp-hero h1 .dashicons{font-size:32px;width:32px;height:32px}
        .np-mcp-hero p{margin:0;opacity:.85;font-size:15px}
        .np-mcp-hero .np-mcp-version{display:inline-block;background:rgba(255,255,255,.18);padding:4px 12px;border-radius:999px;font-size:12px;margin-left:12px;font-weight:600;letter-spacing:.5px}
        .np-mcp-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin:0 0 24px}
        .np-mcp-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px;box-shadow:0 1px 2px rgba(0,0,0,.03)}
        .np-mcp-card h3{margin:0 0 4px;font-size:13px;text-transform:uppercase;letter-spacing:.5px;color:#64748b}
        .np-mcp-card .np-mcp-num{font-size:32px;font-weight:700;color:#0F172A;line-height:1}
        .np-mcp-card .np-mcp-sub{color:#64748b;font-size:13px;margin-top:4px}
        .np-mcp-card.ok{border-color:#10b981}.np-mcp-card.ok .np-mcp-num{color:#059669}
        .np-mcp-card.warn{border-color:#f59e0b}.np-mcp-card.warn .np-mcp-num{color:#d97706}
        .np-mcp-card.err{border-color:#ef4444}.np-mcp-card.err .np-mcp-num{color:#dc2626}
        .np-mcp-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}
        @media(max-width:900px){.np-mcp-grid{grid-template-columns:1fr}}
        .np-mcp-panel{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:16px}
        .np-mcp-panel h2{margin:0 0 12px;font-size:18px;display:flex;align-items:center;gap:8px}
        .np-mcp-tools-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}
        .np-mcp-tool{border:1px solid #e2e8f0;border-radius:10px;padding:14px;background:#f8fafc}
        .np-mcp-tool h4{margin:0 0 6px;font-size:14px}
        .np-mcp-tool p{margin:0 0 10px;color:#64748b;font-size:13px}
        .np-mcp-abilities-table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden}
        .np-mcp-abilities-table th,.np-mcp-abilities-table td{padding:10px 12px;text-align:left;border-bottom:1px solid #f1f5f9;font-size:13px}
        .np-mcp-abilities-table th{background:#f8fafc;font-weight:600;color:#334155}
        .np-mcp-abilities-table tr:last-child td{border-bottom:none}
        .np-mcp-abilities-table code{background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:12px}
        .np-mcp-pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;letter-spacing:.3px;text-transform:uppercase}
        .np-mcp-pill.on{background:#d1fae5;color:#047857}
        .np-mcp-pill.off{background:#fee2e2;color:#b91c1c}
        .np-mcp-toggle{position:relative;display:inline-block;width:44px;height:24px}
        .np-mcp-toggle input{opacity:0;width:0;height:0}
        .np-mcp-toggle span{position:absolute;cursor:pointer;inset:0;background:#cbd5e1;border-radius:24px;transition:.2s}
        .np-mcp-toggle span:before{content:"";position:absolute;height:18px;width:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 2px rgba(0,0,0,.2)}
        .np-mcp-toggle input:checked + span{background:#10b981}
        .np-mcp-toggle input:checked + span:before{transform:translateX(20px)}
        .np-mcp-banner{padding:14px 18px;border-radius:10px;margin-bottom:16px;display:flex;align-items:center;gap:12px}
        .np-mcp-banner.warn{background:#fef3c7;color:#92400e;border:1px solid #fde68a}
        .np-mcp-banner.err{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
        .np-mcp-banner.ok{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7}
        .np-mcp-endpoints code{display:block;background:#0f172a;color:#e2e8f0;padding:10px 12px;border-radius:8px;font-size:12px;margin:6px 0;word-break:break-all}
        .np-mcp-tag{display:inline-block;background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:6px;font-size:11px;margin-right:4px;font-weight:600}
        ';
        wp_register_style( 'np-mcp-admin', false );
        wp_enqueue_style( 'np-mcp-admin' );
        wp_add_inline_style( 'np-mcp-admin', $css );
    }

    public static function maintenance_notice(): void {
        $opts = (array) get_option( self::OPTION_NAME, array() );
        if ( empty( $opts['maintenance_enabled'] ) ) { return; }
        echo '<div class="notice notice-warning"><p><strong>NP MCP:</strong> '
            . esc_html__( 'Maintenance mode is ON. Visitors are seeing a 503 page. Admins still see the site.', 'np-mcp-builder' )
            . ' <a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=maintenance' ) ) . '">'
            . esc_html__( 'Disable', 'np-mcp-builder' ) . '</a></p></div>';
    }

    /* ==================================================================== */

    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        // Read-only routing param; not a form submission, so no nonce required.
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : 'overview'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab routing.

        echo '<div class="wrap np-mcp-wrap">';
        self::render_hero();
        self::render_tabs( $tab );

        switch ( $tab ) {
            case 'abilities':   self::tab_abilities(); break;
            case 'tools':       self::tab_tools(); break;
            case 'settings':    self::tab_settings(); break;
            case 'maintenance': self::tab_maintenance(); break;
            case 'about':       self::tab_about(); break;
            default:            self::tab_overview(); break;
        }

        echo '</div>';
    }

    private static function render_hero(): void {
        $version = defined( 'NP_MCP_BUILDER_VERSION' ) ? NP_MCP_BUILDER_VERSION : '1.0.0';
        echo '<div class="np-mcp-hero">';
        echo '<h1><span class="dashicons dashicons-superhero"></span>' . esc_html__( 'NP MCP Builder', 'np-mcp-builder' );
        echo '<span class="np-mcp-version">v' . esc_html( $version ) . '</span></h1>';
        echo '<p>' . esc_html__( 'Mega WordPress + Elementor abilities exposed as MCP tools. Build blogs, landing pages with auto schema and Yoast SEO, manage plugins, themes, menus, users, caches and more.', 'np-mcp-builder' ) . '</p>';
        echo '</div>';
    }

    private static function render_tabs( string $current ): void {
        $tabs = array(
            'overview'    => array( __( 'Overview', 'np-mcp-builder' ),    'dashboard' ),
            'abilities'   => array( __( 'Abilities', 'np-mcp-builder' ),   'admin-generic' ),
            'tools'       => array( __( 'Tools', 'np-mcp-builder' ),       'admin-tools' ),
            'settings'    => array( __( 'Settings', 'np-mcp-builder' ),    'admin-settings' ),
            'maintenance' => array( __( 'Maintenance', 'np-mcp-builder' ), 'warning' ),
            'about'       => array( __( 'About', 'np-mcp-builder' ),       'info' ),
        );
        echo '<h2 class="nav-tab-wrapper">';
        foreach ( $tabs as $slug => list( $label, $icon ) ) {
            $url   = add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => $slug ), admin_url( 'admin.php' ) );
            $class = 'nav-tab' . ( $current === $slug ? ' nav-tab-active' : '' );
            echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">';
            echo '<span class="dashicons dashicons-' . esc_attr( $icon ) . '" style="vertical-align:text-bottom"></span> ' . esc_html( $label );
            echo '</a>';
        }
        echo '</h2>';
    }

    /* ---------------------------------------------------------------- */
    /* OVERVIEW                                                         */
    /* ---------------------------------------------------------------- */
    private static function tab_overview(): void {
        $info     = Site_Abilities::system_info();
        $enabled  = Plugin::enabled_abilities();
        $total    = count( Plugin::ABILITY_MAP );

        echo '<div class="np-mcp-cards">';
        self::card( __( 'Active abilities', 'np-mcp-builder' ), count( $enabled ) . ' / ' . $total, __( 'enabled MCP tools', 'np-mcp-builder' ), 'ok' );
        self::card( __( 'Plugins', 'np-mcp-builder' ), $info['active_plugins'] . ' / ' . $info['plugins_total'], __( 'active / installed', 'np-mcp-builder' ) );
        self::card( __( 'Active theme', 'np-mcp-builder' ), esc_html( $info['active_theme'] ), '' );
        self::card( __( 'WordPress', 'np-mcp-builder' ), $info['wp_version'], 'PHP ' . $info['php_version'] );
        self::card( __( 'mcp-adapter', 'np-mcp-builder' ), $info['has_mcp_adapter'] ? __( 'Detected', 'np-mcp-builder' ) : __( 'Missing', 'np-mcp-builder' ), '', $info['has_mcp_adapter'] ? 'ok' : 'warn' );
        self::card( __( 'Yoast SEO', 'np-mcp-builder' ), $info['has_yoast'] ? __( 'Detected', 'np-mcp-builder' ) : __( 'Missing', 'np-mcp-builder' ), '', $info['has_yoast'] ? 'ok' : 'warn' );
        self::card( __( 'Elementor', 'np-mcp-builder' ), $info['has_elementor'] ? __( 'Detected', 'np-mcp-builder' ) : __( 'Missing', 'np-mcp-builder' ), '', $info['has_elementor'] ? 'ok' : 'warn' );
        self::card( __( 'Maintenance', 'np-mcp-builder' ), Plugin::get_option( 'maintenance_enabled' ) ? __( 'ON', 'np-mcp-builder' ) : __( 'OFF', 'np-mcp-builder' ), '', Plugin::get_option( 'maintenance_enabled' ) ? 'warn' : 'ok' );
        echo '</div>';

        echo '<div class="np-mcp-grid">';
        echo '<div class="np-mcp-panel"><h2><span class="dashicons dashicons-admin-links"></span> ' . esc_html__( 'Endpoints', 'np-mcp-builder' ) . '</h2><div class="np-mcp-endpoints">';
        echo '<p><strong>' . esc_html__( 'WP REST base', 'np-mcp-builder' ) . '</strong></p><code>' . esc_html( rest_url( 'wp/v2/' ) ) . '</code>';
        echo '<p style="margin-top:12px"><strong>' . esc_html__( 'MCP server', 'np-mcp-builder' ) . '</strong></p><code>' . esc_html( rest_url( 'mcp/v1/streamable' ) ) . '</code>';
        echo '</div></div>';

        echo '<div class="np-mcp-panel"><h2><span class="dashicons dashicons-admin-tools"></span> ' . esc_html__( 'Quick actions', 'np-mcp-builder' ) . '</h2>';
        echo '<p>';
        echo '<a class="button button-primary" href="' . esc_url( add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => 'abilities' ), admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Toggle abilities', 'np-mcp-builder' ) . '</a> ';
        echo '<a class="button" href="' . esc_url( add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => 'tools' ), admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Open tools', 'np-mcp-builder' ) . '</a> ';
        echo '<a class="button" href="' . esc_url( add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => 'settings' ), admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Settings', 'np-mcp-builder' ) . '</a>';
        echo '</p>';
        echo '</div>';
        echo '</div>';
    }

    private static function card( string $label, $value, string $sub = '', string $state = '' ): void {
        $cls = trim( 'np-mcp-card ' . $state );
        echo '<div class="' . esc_attr( $cls ) . '">';
        echo '<h3>' . esc_html( $label ) . '</h3>';
        echo '<div class="np-mcp-num">' . esc_html( (string) $value ) . '</div>';
        if ( $sub !== '' ) { echo '<div class="np-mcp-sub">' . esc_html( $sub ) . '</div>'; }
        echo '</div>';
    }

    /* ---------------------------------------------------------------- */
    /* ABILITIES                                                        */
    /* ---------------------------------------------------------------- */
    private static function tab_abilities(): void {
        $opts     = (array) get_option( self::OPTION_NAME, array() );
        $disabled = (array) ( $opts['disabled_abilities'] ?? array() );

        echo '<form action="options.php" method="post" class="np-mcp-panel">';
        settings_fields( self::OPTION_GROUP );

        // Group abilities by group label.
        $groups = array();
        foreach ( Plugin::ABILITY_MAP as $tool => list( $class, $group, $desc ) ) {
            $groups[ $group ][ $tool ] = $desc;
        }

        echo '<p>' . esc_html__( 'Toggle individual abilities on or off. Disabled abilities are not registered with the Abilities API and are not exposed through MCP.', 'np-mcp-builder' ) . '</p>';

        foreach ( $groups as $group => $tools ) {
            echo '<h2><span class="np-mcp-tag">' . esc_html( $group ) . '</span> ' . count( $tools ) . ' ' . esc_html__( 'abilities', 'np-mcp-builder' ) . '</h2>';
            echo '<table class="np-mcp-abilities-table"><thead><tr>';
            echo '<th style="width:50px">' . esc_html__( 'On', 'np-mcp-builder' ) . '</th>';
            echo '<th>' . esc_html__( 'Tool', 'np-mcp-builder' ) . '</th>';
            echo '<th>' . esc_html__( 'Description', 'np-mcp-builder' ) . '</th>';
            echo '<th style="width:80px">' . esc_html__( 'Status', 'np-mcp-builder' ) . '</th>';
            echo '</tr></thead><tbody>';
            foreach ( $tools as $tool => $desc ) {
                $is_off  = in_array( $tool, $disabled, true );
                // We render the inverse: a checkbox value "1" goes into disabled_abilities[] when UNCHECKED.
                // To keep a simple model we render a checkbox named disabled_abilities[] with the tool
                // as value; checked = disabled.
                $checked_on = ! $is_off;
                echo '<tr>';
                echo '<td><label class="np-mcp-toggle">';
                // Hidden mirror: when the checkbox is checked we want to OMIT the disable entry.
                // So we wire a real checkbox that, when UNCHECKED, posts the tool into the disabled list.
                echo '<input type="checkbox" name="' . esc_attr( self::OPTION_NAME ) . '[__enabled][]" value="' . esc_attr( $tool ) . '"' . checked( $checked_on, true, false ) . ' />';
                echo '<span></span></label></td>';
                echo '<td><code>' . esc_html( $tool ) . '</code></td>';
                echo '<td>' . esc_html( $desc ) . '</td>';
                echo '<td><span class="np-mcp-pill ' . ( $checked_on ? 'on">ON' : 'off">OFF' ) . '</span></td>';
                echo '</tr>';
            }
            echo '</tbody></table><br>';
        }

        // Hidden field with all known tools so sanitize() can compute disabled set.
        $all = array_keys( Plugin::ABILITY_MAP );
        echo '<input type="hidden" name="' . esc_attr( self::OPTION_NAME ) . '[__all_tools]" value="' . esc_attr( implode( ',', $all ) ) . '" />';

        submit_button( __( 'Save abilities', 'np-mcp-builder' ) );
        echo '</form>';
    }

    public static function pre_update_compute_disabled( $value, $old ) {
        if ( ! is_array( $value ) ) { return $value; }
        if ( isset( $value['__all_tools'] ) ) {
            $all     = array_filter( array_map( 'trim', explode( ',', (string) $value['__all_tools'] ) ) );
            $enabled = isset( $value['__enabled'] ) ? (array) $value['__enabled'] : array();
            $value['disabled_abilities'] = array_values( array_diff( $all, $enabled ) );
            unset( $value['__all_tools'], $value['__enabled'] );
        }
        return $value;
    }

    /* ---------------------------------------------------------------- */
    /* TOOLS                                                            */
    /* ---------------------------------------------------------------- */
    private static function tab_tools(): void {
        // Read-only display flag set by our admin-post handler after a verified nonce; safe to read without nonce here.
        $cleared = isset( $_GET['cleared'] ) ? sanitize_key( wp_unslash( (string) $_GET['cleared'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
        if ( $cleared === '1' ) {
            echo '<div class="np-mcp-banner ok"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Caches cleared.', 'np-mcp-builder' ) . '</div>';
        }

        echo '<div class="np-mcp-panel"><h2><span class="dashicons dashicons-admin-tools"></span> ' . esc_html__( 'One-click tools', 'np-mcp-builder' ) . '</h2>';
        echo '<div class="np-mcp-tools-grid">';

        // Clear caches.
        echo '<div class="np-mcp-tool"><h4>' . esc_html__( 'Clear all caches', 'np-mcp-builder' ) . '</h4>';
        echo '<p>' . esc_html__( 'Clears Elementor CSS files, the WordPress object cache and expired transients.', 'np-mcp-builder' ) . '</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'np_mcp_clear_cache' );
        echo '<input type="hidden" name="action" value="np_mcp_clear_cache" />';
        submit_button( __( 'Clear caches', 'np-mcp-builder' ), 'primary small', '', false );
        echo '</form></div>';

        // Endpoint reference.
        echo '<div class="np-mcp-tool"><h4>' . esc_html__( 'MCP endpoint', 'np-mcp-builder' ) . '</h4>';
        echo '<p style="word-break:break-all"><code>' . esc_html( rest_url( 'mcp/v1/streamable' ) ) . '</code></p>';
        echo '<p>' . esc_html__( 'Use this URL with mcp-adapter or any MCP client.', 'np-mcp-builder' ) . '</p></div>';

        // System info dump.
        $info = Site_Abilities::system_info();
        echo '<div class="np-mcp-tool"><h4>' . esc_html__( 'System info', 'np-mcp-builder' ) . '</h4>';
        echo '<p style="font-family:monospace;font-size:12px;line-height:1.7">';
        foreach ( array( 'wp_version', 'php_version', 'mysql_version', 'memory_limit', 'max_upload_size', 'language' ) as $k ) {
            echo '<strong>' . esc_html( $k ) . ':</strong> ' . esc_html( (string) $info[ $k ] ) . '<br>';
        }
        echo '</p></div>';

        echo '</div></div>';
    }

    public static function handle_clear_cache(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'forbidden' ); }
        check_admin_referer( 'np_mcp_clear_cache' );
        Site_Abilities::clear_cache( array() );
        wp_safe_redirect( add_query_arg( array(
            'page' => self::PAGE_SLUG, 'tab' => 'tools', 'cleared' => 1,
        ), admin_url( 'admin.php' ) ) );
        exit;
    }

    /* ---------------------------------------------------------------- */
    /* SETTINGS (general)                                               */
    /* ---------------------------------------------------------------- */
    private static function tab_settings(): void {
        $opts = (array) get_option( self::OPTION_NAME, array() );
        echo '<form action="options.php" method="post" class="np-mcp-panel">';
        settings_fields( self::OPTION_GROUP );
        echo '<h2><span class="dashicons dashicons-admin-settings"></span> ' . esc_html__( 'AI image generation', 'np-mcp-builder' ) . '</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th><label>' . esc_html__( 'Gemini API key', 'np-mcp-builder' ) . '</label></th><td>';
        echo '<input type="password" class="regular-text" name="' . esc_attr( self::OPTION_NAME ) . '[gemini_api_key]" value="' . esc_attr( (string) ( $opts['gemini_api_key'] ?? '' ) ) . '" autocomplete="off" />';
        echo '<p class="description">' . esc_html__( 'Used by np/generate-image (model: gemini-2.5-flash-image). Get a key at aistudio.google.com.', 'np-mcp-builder' ) . '</p>';
        echo '</td></tr>';

        echo '<tr><th><label>' . esc_html__( 'Default aspect ratio', 'np-mcp-builder' ) . '</label></th><td>';
        echo '<select name="' . esc_attr( self::OPTION_NAME ) . '[default_aspect_ratio]">';
        $current = (string) ( $opts['default_aspect_ratio'] ?? '16:9' );
        foreach ( array( '1:1', '2:3', '3:2', '3:4', '4:3', '4:5', '5:4', '9:16', '16:9', '21:9' ) as $r ) {
            echo '<option value="' . esc_attr( $r ) . '"' . selected( $current, $r, false ) . '>' . esc_html( $r ) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th><label>' . esc_html__( 'Default max width (px)', 'np-mcp-builder' ) . '</label></th><td>';
        echo '<input type="number" min="256" max="2048" name="' . esc_attr( self::OPTION_NAME ) . '[default_max_width]" value="' . esc_attr( (string) ( $opts['default_max_width'] ?? 1280 ) ) . '" />';
        echo '</td></tr>';

        echo '<tr><th><label>' . esc_html__( 'Default WebP quality', 'np-mcp-builder' ) . '</label></th><td>';
        echo '<input type="number" min="40" max="95" name="' . esc_attr( self::OPTION_NAME ) . '[default_quality]" value="' . esc_attr( (string) ( $opts['default_quality'] ?? 78 ) ) . '" />';
        echo '</td></tr>';

        echo '</tbody></table>';
        submit_button();
        echo '</form>';
    }

    /* ---------------------------------------------------------------- */
    /* MAINTENANCE                                                      */
    /* ---------------------------------------------------------------- */
    private static function tab_maintenance(): void {
        $opts = (array) get_option( self::OPTION_NAME, array() );
        $on   = ! empty( $opts['maintenance_enabled'] );

        if ( $on ) {
            echo '<div class="np-mcp-banner warn"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Maintenance mode is currently ON. Visitors see a 503 page.', 'np-mcp-builder' ) . '</div>';
        }

        echo '<form action="options.php" method="post" class="np-mcp-panel">';
        settings_fields( self::OPTION_GROUP );
        echo '<h2><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Maintenance mode', 'np-mcp-builder' ) . '</h2>';
        echo '<p>' . esc_html__( 'When enabled, only logged-in admins see the site. Everyone else gets a styled 503 page.', 'np-mcp-builder' ) . '</p>';
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th>' . esc_html__( 'Status', 'np-mcp-builder' ) . '</th><td><label class="np-mcp-toggle"><input type="checkbox" name="' . esc_attr( self::OPTION_NAME ) . '[maintenance_enabled]" value="1"' . checked( $on, true, false ) . ' /><span></span></label> <span class="np-mcp-pill ' . ( $on ? 'on">ON' : 'off">OFF' ) . '</span></td></tr>';

        echo '<tr><th><label>' . esc_html__( 'Title', 'np-mcp-builder' ) . '</label></th><td>';
        echo '<input type="text" class="regular-text" name="' . esc_attr( self::OPTION_NAME ) . '[maintenance_title]" value="' . esc_attr( (string) ( $opts['maintenance_title'] ?? __( 'We will be right back', 'np-mcp-builder' ) ) ) . '" />';
        echo '</td></tr>';

        echo '<tr><th><label>' . esc_html__( 'Message', 'np-mcp-builder' ) . '</label></th><td>';
        echo '<textarea class="large-text" rows="4" name="' . esc_attr( self::OPTION_NAME ) . '[maintenance_message]">' . esc_textarea( (string) ( $opts['maintenance_message'] ?? __( 'The site is undergoing scheduled maintenance. Please check back shortly.', 'np-mcp-builder' ) ) ) . '</textarea>';
        echo '<p class="description">' . esc_html__( 'Basic HTML allowed.', 'np-mcp-builder' ) . '</p>';
        echo '</td></tr>';

        echo '</tbody></table>';
        submit_button( __( 'Save maintenance settings', 'np-mcp-builder' ) );
        echo '</form>';
    }

    public static function handle_toggle_maintenance(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'forbidden' ); }
        check_admin_referer( 'np_mcp_toggle_maintenance' );
        $opts = (array) get_option( self::OPTION_NAME, array() );
        $opts['maintenance_enabled'] = empty( $opts['maintenance_enabled'] );
        update_option( self::OPTION_NAME, $opts );
        wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => 'maintenance' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    /* ---------------------------------------------------------------- */
    /* ABOUT                                                            */
    /* ---------------------------------------------------------------- */
    private static function tab_about(): void {
        echo '<div class="np-mcp-panel"><h2><span class="dashicons dashicons-info"></span> ' . esc_html__( 'About', 'np-mcp-builder' ) . '</h2>';
        echo '<p>' . esc_html__( 'NP MCP Builder bundles high-level WordPress + Elementor + Yoast SEO operations as MCP tools so an AI client (Claude, Cursor, your custom agent) can build, manage and operate a WordPress site through a single secure REST endpoint.', 'np-mcp-builder' ) . '</p>';
        echo '<ul style="list-style:disc;margin-left:24px"><li>' . esc_html__( '40+ abilities across content, media, taxonomy, theme, Elementor, site, menus, users, SEO.', 'np-mcp-builder' ) . '</li>';
        echo '<li>' . esc_html__( 'Conversion landing pages with auto JSON-LD schema (FAQ, LocalBusiness, BreadcrumbList) and Yoast SEO.', 'np-mcp-builder' ) . '</li>';
        echo '<li>' . esc_html__( 'AI images via Gemini, optimized to WebP with full Media Library SEO metadata.', 'np-mcp-builder' ) . '</li>';
        echo '<li>' . esc_html__( 'Per-ability on/off toggles, maintenance mode, one-click cache clearing.', 'np-mcp-builder' ) . '</li></ul>';
        echo '<p><strong>' . esc_html__( 'Source', 'np-mcp-builder' ) . ':</strong> <a href="https://github.com/hamzanabulse/np-mcp-builder" target="_blank" rel="noopener">github.com/hamzanabulse/np-mcp-builder</a></p>';
        echo '<p><strong>' . esc_html__( 'Author', 'np-mcp-builder' ) . ':</strong> Hamza Ali Nabulsi · <a href="https://hamzanabulsi.com" target="_blank" rel="noopener">hamzanabulsi.com</a></p>';
        echo '</div>';
    }
}
