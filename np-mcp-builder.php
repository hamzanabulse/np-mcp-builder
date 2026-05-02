<?php
/**
 * Plugin Name:       NP MCP Builder
 * Plugin URI:        https://github.com/hamzaalinabulsi/np-mcp-builder
 * Description:       The complete WordPress + Elementor MCP control plane: ~140 abilities for content, media (AI Gemini images), taxonomy, themes, plugins, menus, users, site settings, permalinks, cache, maintenance mode, Yoast SEO (global + audit + rendered head + schema graph), and the full bundled MCP Tools for Elementor suite (97 atomic-element-aware tools for pages, widgets, layouts, templates, theme builder, popups, dynamic tags, stock images, custom code).
 * Version:           1.5.0
 * Requires at least: 6.9
 * Requires PHP:      8.0
 * Author:            Hamza Ali Nabulsi
 * Author URI:        https://hamzanabulsi.com
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       np-mcp-builder
 * Domain Path:       /languages
 *
 * @package NP_MCP_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'NP_MCP_BUILDER_VERSION', '1.5.0' );
define( 'NP_MCP_BUILDER_FILE', __FILE__ );
define( 'NP_MCP_BUILDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'NP_MCP_BUILDER_URL', plugin_dir_url( __FILE__ ) );
define( 'NP_MCP_BUILDER_BASENAME', plugin_basename( __FILE__ ) );

// Bootstrap.
require_once NP_MCP_BUILDER_DIR . 'includes/class-plugin.php';

// Bundled MCP Tools for Elementor (vendored from msrbuilds/elementor-mcp, GPL-3.0).
// Self-bootstraps via its own plugins_loaded@20 hook, which checks for Elementor + MCP Adapter.
if ( ! defined( 'ELEMENTOR_MCP_VERSION' ) && file_exists( NP_MCP_BUILDER_DIR . 'vendor/elementor-mcp/elementor-mcp.php' ) ) {
    require_once NP_MCP_BUILDER_DIR . 'vendor/elementor-mcp/elementor-mcp.php';
}

// Initialize on plugins_loaded so all dependencies (Abilities API, mcp-adapter) are present.
add_action( 'plugins_loaded', static function () {
    NP_MCP_Builder\Plugin::instance()->init();
}, 5 );

// Activation: flag so we can show the welcome notice / set defaults.
register_activation_hook( __FILE__, static function () {
    if ( ! get_option( 'np_mcp_builder_activated_at' ) ) {
        update_option( 'np_mcp_builder_activated_at', time(), false );
    }
} );

// Deactivation: nothing to do (settings preserved).
register_deactivation_hook( __FILE__, static function () { /* noop */ } );
