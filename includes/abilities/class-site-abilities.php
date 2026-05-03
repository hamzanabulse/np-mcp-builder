<?php
/**
 * Site_Abilities — site-wide control: plugins, themes, settings, permalinks,
 * cache, maintenance mode, system info.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder\Abilities;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Site_Abilities {

    public static function register(): void {
        if ( ! function_exists( 'wp_register_ability' ) ) { return; }

        // ---------- Plugins ----------
        wp_register_ability( 'np/list-plugins', array(
            'label' => 'List plugins', 'category' => 'np-site',
            'description' => 'List all installed plugins with file, name, version, and active status.',
            'input_schema'  => array( 'type' => 'object', 'properties' => array() ),
            'execute_callback'    => array( __CLASS__, 'list_plugins' ),
            'permission_callback' => static function () { return current_user_can( 'activate_plugins' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/activate-plugin', array(
            'label' => 'Activate plugin', 'category' => 'np-site',
            'description' => 'Activate an installed plugin by its plugin file (e.g. "akismet/akismet.php"). Safety: requires confirm=true.',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'plugin', 'confirm' ),
                'properties' => array(
                    'plugin'         => array( 'type' => 'string' ),
                    'network_wide'   => array( 'type' => 'boolean', 'default' => false ),
                    'confirm'        => array( 'type' => 'boolean', 'description' => 'Must be true to activate a plugin.' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'activate_plugin' ),
            'permission_callback' => static function () { return current_user_can( 'activate_plugins' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/deactivate-plugin', array(
            'label' => 'Deactivate plugin', 'category' => 'np-site',
            'description' => 'Deactivate an active plugin by its plugin file. Safety: requires confirm=true.',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'plugin', 'confirm' ),
                'properties' => array(
                    'plugin' => array( 'type' => 'string' ),
                    'confirm' => array( 'type' => 'boolean', 'description' => 'Must be true to deactivate a plugin.' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'deactivate_plugin' ),
            'permission_callback' => static function () { return current_user_can( 'activate_plugins' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        // ---------- Themes ----------
        wp_register_ability( 'np/list-themes', array(
            'label' => 'List themes', 'category' => 'np-site',
            'description' => 'List installed themes with stylesheet, name, version and active status.',
            'input_schema'  => array( 'type' => 'object', 'properties' => array() ),
            'execute_callback'    => array( __CLASS__, 'list_themes' ),
            'permission_callback' => static function () { return current_user_can( 'switch_themes' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/switch-theme', array(
            'label' => 'Switch theme', 'category' => 'np-site',
            'description' => 'Activate a theme by its stylesheet directory name. Safety: requires confirm=true.',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'stylesheet', 'confirm' ),
                'properties' => array(
                    'stylesheet' => array( 'type' => 'string' ),
                    'confirm'    => array( 'type' => 'boolean', 'description' => 'Must be true to switch theme.' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'switch_theme' ),
            'permission_callback' => static function () { return current_user_can( 'switch_themes' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        // ---------- Site settings ----------
        wp_register_ability( 'np/get-site-settings', array(
            'label' => 'Get site settings', 'category' => 'np-site',
            'description' => 'Read core site settings: blogname, blogdescription, admin_email, timezone, date/time format, language, search engine visibility, default category, default post format, page_on_front, show_on_front, posts_per_page.',
            'input_schema'  => array( 'type' => 'object', 'properties' => array() ),
            'execute_callback'    => array( __CLASS__, 'get_site_settings' ),
            'permission_callback' => static function () { return current_user_can( 'manage_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/update-site-settings', array(
            'label' => 'Update site settings', 'category' => 'np-site',
            'description' => 'Update one or more core site settings. Safety: requires confirm=true.',
            'input_schema' => array(
                'type' => 'object',
                'required' => array( 'confirm' ),
                'properties' => array(
                    'blogname'         => array( 'type' => 'string' ),
                    'blogdescription'  => array( 'type' => 'string' ),
                    'admin_email'      => array( 'type' => 'string' ),
                    'timezone_string'  => array( 'type' => 'string' ),
                    'date_format'      => array( 'type' => 'string' ),
                    'time_format'      => array( 'type' => 'string' ),
                    'start_of_week'    => array( 'type' => 'integer' ),
                    'WPLANG'           => array( 'type' => 'string' ),
                    'blog_public'      => array( 'type' => 'integer', 'enum' => array( 0, 1 ) ),
                    'default_category' => array( 'type' => 'integer' ),
                    'default_post_format' => array( 'type' => 'string' ),
                    'page_on_front'    => array( 'type' => 'integer' ),
                    'page_for_posts'   => array( 'type' => 'integer' ),
                    'show_on_front'    => array( 'type' => 'string', 'enum' => array( 'posts', 'page' ) ),
                    'posts_per_page'   => array( 'type' => 'integer' ),
                    'confirm'          => array( 'type' => 'boolean', 'description' => 'Must be true to update site settings.' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'update_site_settings' ),
            'permission_callback' => static function () { return current_user_can( 'manage_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        // ---------- Permalinks ----------
        wp_register_ability( 'np/update-permalinks', array(
            'label' => 'Update permalink structure', 'category' => 'np-site',
            'description' => 'Set permalink structure (e.g. /%postname%/) and flush rewrite rules. Safety: requires confirm=true.',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'structure', 'confirm' ),
                'properties' => array(
                    'structure'          => array( 'type' => 'string' ),
                    'category_base'      => array( 'type' => 'string' ),
                    'tag_base'           => array( 'type' => 'string' ),
                    'confirm'            => array( 'type' => 'boolean', 'description' => 'Must be true to update permalinks.' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'update_permalinks' ),
            'permission_callback' => static function () { return current_user_can( 'manage_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        // ---------- Cache ----------
        wp_register_ability( 'np/clear-cache', array(
            'label' => 'Clear caches', 'category' => 'np-site',
            'description' => 'Clear Elementor CSS cache, WordPress object cache, and optionally expired transients.',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'elementor'   => array( 'type' => 'boolean', 'default' => true ),
                    'object_cache'=> array( 'type' => 'boolean', 'default' => true ),
                    'transients'  => array( 'type' => 'boolean', 'default' => true ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'clear_cache' ),
            'permission_callback' => static function () { return current_user_can( 'manage_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        // ---------- Maintenance ----------
        wp_register_ability( 'np/maintenance-mode', array(
            'label' => 'Maintenance mode', 'category' => 'np-site',
            'description' => 'Enable or disable a front-end maintenance page (returns 503 to non-logged-in visitors). Safety: requires confirm=true.',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'enabled', 'confirm' ),
                'properties' => array(
                    'enabled' => array( 'type' => 'boolean' ),
                    'message' => array( 'type' => 'string' ),
                    'title'   => array( 'type' => 'string' ),
                    'confirm' => array( 'type' => 'boolean', 'description' => 'Must be true to change maintenance mode.' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'maintenance_mode' ),
            'permission_callback' => static function () { return current_user_can( 'manage_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        // ---------- System info ----------
        wp_register_ability( 'np/system-info', array(
            'label' => 'System info', 'category' => 'np-site',
            'description' => 'Return WordPress version, PHP version, MySQL version, active theme, active plugins count, multisite status, memory limit and language.',
            'input_schema'  => array( 'type' => 'object', 'properties' => array() ),
            'execute_callback'    => array( __CLASS__, 'system_info' ),
            'permission_callback' => static function () { return current_user_can( 'manage_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );
    }

    /* ==================================================================== */

    public static function list_plugins(): array {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all    = get_plugins();
        $active = (array) get_option( 'active_plugins', array() );
        $rows   = array();
        foreach ( $all as $file => $data ) {
            $rows[] = array(
                'plugin'      => $file,
                'name'        => $data['Name'] ?? '',
                'version'     => $data['Version'] ?? '',
                'description' => wp_strip_all_tags( $data['Description'] ?? '' ),
                'author'      => wp_strip_all_tags( $data['Author'] ?? '' ),
                'active'      => in_array( $file, $active, true ),
                'network'     => is_plugin_active_for_network( $file ),
            );
        }
        return array( 'count' => count( $rows ), 'plugins' => $rows );
    }

    public static function activate_plugin( array $input ) {
        $confirmed = self::require_confirm( $input, 'activate a plugin' );
        if ( is_wp_error( $confirmed ) ) { return $confirmed; }
        if ( ! function_exists( 'activate_plugin' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin = (string) ( $input['plugin'] ?? '' );
        if ( $plugin === '' || ! file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
            return new \WP_Error( 'np_plugin_missing', 'Plugin file not found.' );
        }
        $res = activate_plugin( $plugin, '', ! empty( $input['network_wide'] ) );
        if ( is_wp_error( $res ) ) { return $res; }
        return array( 'plugin' => $plugin, 'active' => is_plugin_active( $plugin ) );
    }

    public static function deactivate_plugin( array $input ) {
        $confirmed = self::require_confirm( $input, 'deactivate a plugin' );
        if ( is_wp_error( $confirmed ) ) { return $confirmed; }
        if ( ! function_exists( 'deactivate_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin = (string) ( $input['plugin'] ?? '' );
        if ( $plugin === '' ) {
            return new \WP_Error( 'np_plugin_missing', 'Plugin file required.' );
        }
        // Refuse to deactivate self to avoid breaking the chain mid-call.
        if ( $plugin === plugin_basename( NP_MCP_BUILDER_FILE ) ) {
            return new \WP_Error( 'np_self_deactivate', 'Refusing to deactivate the NP MCP Builder plugin via its own ability.' );
        }
        deactivate_plugins( array( $plugin ) );
        return array( 'plugin' => $plugin, 'active' => is_plugin_active( $plugin ) );
    }

    public static function list_themes(): array {
        $themes  = wp_get_themes();
        $current = get_stylesheet();
        $rows    = array();
        foreach ( $themes as $slug => $theme ) {
            $rows[] = array(
                'stylesheet' => $slug,
                'name'       => $theme->get( 'Name' ),
                'version'    => $theme->get( 'Version' ),
                'author'     => wp_strip_all_tags( (string) $theme->get( 'Author' ) ),
                'active'     => $slug === $current,
                'parent'     => $theme->parent() ? $theme->parent()->get_stylesheet() : null,
            );
        }
        return array( 'count' => count( $rows ), 'active' => $current, 'themes' => $rows );
    }

    public static function switch_theme( array $input ) {
        $confirmed = self::require_confirm( $input, 'switch theme' );
        if ( is_wp_error( $confirmed ) ) { return $confirmed; }
        $stylesheet = (string) ( $input['stylesheet'] ?? '' );
        $theme      = wp_get_theme( $stylesheet );
        if ( ! $theme->exists() ) {
            return new \WP_Error( 'np_theme_missing', 'Theme not found.' );
        }
        switch_theme( $stylesheet );
        return array( 'active' => get_stylesheet(), 'name' => $theme->get( 'Name' ) );
    }

    public static function get_site_settings(): array {
        $keys = array(
            'blogname', 'blogdescription', 'admin_email', 'timezone_string',
            'date_format', 'time_format', 'start_of_week', 'WPLANG',
            'blog_public', 'default_category', 'default_post_format',
            'page_on_front', 'page_for_posts', 'show_on_front', 'posts_per_page',
            'permalink_structure', 'category_base', 'tag_base',
        );
        $out = array();
        foreach ( $keys as $k ) { $out[ $k ] = get_option( $k ); }
        $out['siteurl']  = get_option( 'siteurl' );
        $out['home']     = get_option( 'home' );
        $out['language'] = get_locale();
        return $out;
    }

    public static function update_site_settings( array $input ) {
        $confirmed = self::require_confirm( $input, 'update site settings' );
        if ( is_wp_error( $confirmed ) ) { return $confirmed; }
        $allowed = array(
            'blogname', 'blogdescription', 'admin_email', 'timezone_string',
            'date_format', 'time_format', 'start_of_week', 'WPLANG',
            'blog_public', 'default_category', 'default_post_format',
            'page_on_front', 'page_for_posts', 'show_on_front', 'posts_per_page',
        );
        $changed = array();
        foreach ( $allowed as $k ) {
            if ( ! array_key_exists( $k, $input ) ) { continue; }
            $val = $input[ $k ];
            switch ( $k ) {
                case 'admin_email':
                    $val = sanitize_email( (string) $val );
                    if ( ! is_email( $val ) ) { continue 2; }
                    break;
                case 'blog_public':
                case 'start_of_week':
                case 'default_category':
                case 'page_on_front':
                case 'page_for_posts':
                case 'posts_per_page':
                    $val = (int) $val; break;
                case 'show_on_front':
                    $val = ( $val === 'page' ) ? 'page' : 'posts'; break;
                default:
                    $val = sanitize_text_field( (string) $val );
            }
            update_option( $k, $val );
            $changed[ $k ] = $val;
        }
        return array( 'changed' => $changed );
    }

    public static function update_permalinks( array $input ) {
        $confirmed = self::require_confirm( $input, 'update permalinks' );
        if ( is_wp_error( $confirmed ) ) { return $confirmed; }
        global $wp_rewrite;
        $structure = (string) ( $input['structure'] ?? '/%postname%/' );
        $wp_rewrite->set_permalink_structure( $structure );
        if ( isset( $input['category_base'] ) ) {
            $wp_rewrite->set_category_base( sanitize_text_field( (string) $input['category_base'] ) );
        }
        if ( isset( $input['tag_base'] ) ) {
            $wp_rewrite->set_tag_base( sanitize_text_field( (string) $input['tag_base'] ) );
        }
        flush_rewrite_rules( false );
        return array(
            'permalink_structure' => get_option( 'permalink_structure' ),
            'category_base'       => get_option( 'category_base' ),
            'tag_base'            => get_option( 'tag_base' ),
        );
    }

    public static function clear_cache( array $input ): array {
        $report = array();
        if ( ! isset( $input['elementor'] ) || $input['elementor'] ) {
            if ( class_exists( '\\Elementor\\Plugin' ) ) {
                try {
                    \Elementor\Plugin::instance()->files_manager->clear_cache();
                    $report['elementor'] = 'cleared';
                } catch ( \Throwable $e ) {
                    $report['elementor_error'] = $e->getMessage();
                }
            } else {
                $report['elementor'] = 'not_installed';
            }
        }
        if ( ! isset( $input['object_cache'] ) || $input['object_cache'] ) {
            wp_cache_flush();
            $report['object_cache'] = 'flushed';
        }
        if ( ! isset( $input['transients'] ) || $input['transients'] ) {
            global $wpdb;
            // Bulk transient purge — there is no caching layer for the options-table itself,
            // and looping per-row would issue thousands of queries on large sites.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $deleted = (int) $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_%' OR option_name LIKE '\\_site\\_transient\\_%'" );
            $report['transients_deleted'] = $deleted;
        }
        return $report;
    }

    public static function maintenance_mode( array $input ) {
        $confirmed = self::require_confirm( $input, 'change maintenance mode' );
        if ( is_wp_error( $confirmed ) ) { return $confirmed; }
        $opts = (array) get_option( 'np_mcp_builder_options', array() );
        $opts['maintenance_enabled'] = ! empty( $input['enabled'] );
        if ( isset( $input['title'] ) )   { $opts['maintenance_title']   = sanitize_text_field( (string) $input['title'] ); }
        if ( isset( $input['message'] ) ) { $opts['maintenance_message'] = wp_kses_post( (string) $input['message'] ); }
        update_option( 'np_mcp_builder_options', $opts );
        return array(
            'enabled' => $opts['maintenance_enabled'],
            'title'   => $opts['maintenance_title']   ?? '',
            'message' => $opts['maintenance_message'] ?? '',
        );
    }

    public static function system_info(): array {
        global $wpdb, $wp_version;
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return array(
            'wp_version'       => $wp_version,
            'php_version'      => PHP_VERSION,
            'mysql_version'    => $wpdb->db_version(),
            'multisite'        => is_multisite(),
            'memory_limit'     => WP_MEMORY_LIMIT,
            'max_upload_size'  => size_format( wp_max_upload_size() ),
            'language'         => get_locale(),
            'active_theme'     => get_stylesheet(),
            'active_plugins'   => count( (array) get_option( 'active_plugins', array() ) ),
            'plugins_total'    => count( get_plugins() ),
            'home'             => home_url(),
            'siteurl'          => site_url(),
            'rest_url'         => rest_url(),
            'is_ssl'           => is_ssl(),
            'debug_mode'       => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'has_elementor'    => class_exists( '\\Elementor\\Plugin' ),
            'has_yoast'        => defined( 'WPSEO_VERSION' ),
            'has_mcp_adapter'  => class_exists( '\\WP\\MCP\\Core\\McpAdapter' ) || class_exists( '\\WordPress\\MCP\\McpAdapter' ),
        );
    }

    private static function require_confirm( array $input, string $action ) {
        if ( empty( $input['confirm'] ) ) {
            return new \WP_Error( 'np_confirm_required', 'Safety check: pass confirm=true to ' . $action . '.' );
        }
        return true;
    }
}
